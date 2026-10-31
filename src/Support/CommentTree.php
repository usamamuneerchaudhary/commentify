<?php

namespace Usamamuneerchaudhary\Commentify\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use PDO;
use RuntimeException;
use Usamamuneerchaudhary\Commentify\Models\Comment;

class CommentTree
{
    /**
     * @param  null|(callable(mixed): void)  $orderChildren
     */
    public function __construct(
        public Model $commentable,
        public ?string $thread = null,
        private mixed $orderChildren = null,
    ) {}

    public function paginate(string $sort): LengthAwarePaginator
    {
        $loader = $this->loader();
        $query = $this->rootQuery($sort);

        if ($loader === 'cte') {
            $page = $query->paginate($this->perPage());
            $this->attachDescendants($page->getCollection());

            return $page;
        }

        return $query
            ->with($this->nestedEagerLoads($this->maxDepth()))
            ->paginate($this->perPage());
    }

    public function loader(): string
    {
        $loader = config('commentify.tree_loader', 'eager');

        if (! in_array($loader, ['eager', 'cte'], true)) {
            throw new RuntimeException('commentify.tree_loader must be eager or cte.');
        }

        return $loader;
    }

    public function maxDepth(): int
    {
        return max(0, min(50, (int) config('commentify.max_depth', 2)));
    }

    /**
     * @return MorphMany<Comment, Model>
     */
    private function rootQuery(string $sort): MorphMany
    {
        $query = $this->commentable
            ->comments()
            ->with(['user', 'likes'])
            ->parent()
            ->pinnedFirst()
            ->withCount('children');

        if ($this->thread !== null && $this->thread !== '') {
            $query->forThread($this->thread);
        }

        if (config('commentify.require_approval', false)) {
            $query->approved();
        }

        if (! config('commentify.enable_sorting', true)) {
            return $query->newest();
        }

        return match ($sort) {
            'oldest' => $query->oldest(),
            'most_liked' => $query->mostLiked(),
            'most_replied' => $query->mostReplied(),
            default => $query->newest(),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function nestedEagerLoads(int $depth): array
    {
        $loads = [
            'user',
            'likes',
        ];

        if ($depth < 1) {
            return $loads;
        }

        $requireApproval = (bool) config('commentify.require_approval', false);

        $loads['children'] = function ($query) use ($depth, $requireApproval): void {
            $query->with($this->nestedEagerLoads($depth - 1));

            if ($requireApproval) {
                $query->approved();
            }

            if ($this->thread !== null && $this->thread !== '') {
                $query->forThread($this->thread);
            }

            if ($this->orderChildren !== null) {
                ($this->orderChildren)($query);
            }
        };

        return $loads;
    }

    /**
     * @param  Collection<int, Comment>  $roots
     */
    private function attachDescendants(Collection $roots): void
    {
        $maxDepth = $this->maxDepth();

        if ($roots->isEmpty() || $maxDepth < 1) {
            return;
        }

        $this->assertRecursiveCteSupported();

        $rows = $this->descendantRows($roots->modelKeys());
        $depths = [];

        foreach ($rows as $row) {
            $depths[(int) $row->id] = (int) $row->depth;
        }

        $models = $this->descendantQuery(array_keys($depths))->get()->keyBy('id');
        $childrenOf = [];

        foreach ($models as $model) {
            $childrenOf[(int) $model->parent_id][] = $model;
        }

        $this->nest($roots, $childrenOf, $depths, $maxDepth);
    }

    /**
     * @param  array<int, mixed>  $rootIds
     * @return list<object>
     */
    private function descendantRows(array $rootIds): array
    {
        $comment = new Comment;
        $table = $comment->getTable();
        $relation = $this->commentable->comments();

        $anchor = $this->scopedIdQuery()
            ->select($table.'.id', $table.'.parent_id')
            ->selectRaw('0 as depth')
            ->whereIn($table.'.id', $rootIds)
            ->reorder();

        $recursive = $this->scopedIdQuery()
            ->select($table.'.id', $table.'.parent_id')
            ->selectRaw('commentify_tree.depth + 1 as depth')
            ->join('commentify_tree', $table.'.parent_id', '=', 'commentify_tree.id')
            ->where('commentify_tree.depth', '<', $this->maxDepth())
            ->where($table.'.'.$relation->getMorphType(), $this->commentable->getMorphClass())
            ->where($table.'.'.$relation->getForeignKeyName(), $this->commentable->getKey())
            ->reorder();

        $sql = 'with recursive commentify_tree as ('
            .$anchor->toSql()
            .' union all '
            .$recursive->toSql()
            .') select id, parent_id, depth from commentify_tree where depth > 0';

        return $comment->getConnection()->select(
            $sql,
            array_merge($anchor->getBindings(), $recursive->getBindings()),
        );
    }

    /**
     * @return Builder<Comment>
     */
    private function scopedIdQuery(): Builder
    {
        $query = Comment::query();

        if (config('commentify.require_approval', false)) {
            $query->approved();
        }

        return $query;
    }

    /**
     * @param  list<int>  $ids
     * @return Builder<Comment>
     */
    private function descendantQuery(array $ids): Builder
    {
        $query = Comment::query()
            ->whereIn((new Comment)->getTable().'.id', $ids)
            ->with(['user', 'likes']);

        if (config('commentify.require_approval', false)) {
            $query->approved();
        }

        return $query;
    }

    /**
     * @param  Collection<int, Comment>  $nodes
     * @param  array<int, list<Comment>>  $childrenOf
     * @param  array<int, int>  $depths
     */
    private function nest(Collection $nodes, array $childrenOf, array $depths, int $maxDepth): void
    {
        foreach ($nodes as $node) {
            $depth = $depths[(int) $node->id] ?? 0;

            if ($depth >= $maxDepth) {
                continue;
            }

            $kids = (new Collection($childrenOf[(int) $node->id] ?? []))
                ->sortBy(fn (Comment $comment) => $comment->created_at?->getTimestamp() ?? 0)
                ->values();

            $node->setRelation('children', $kids);
            $this->nest($kids, $childrenOf, $depths, $maxDepth);
        }
    }

    private function assertRecursiveCteSupported(): void
    {
        $connection = (new Comment)->getConnection();
        $driver = $connection->getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite', 'sqlsrv'], true)) {
            return;
        }

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException("The cte tree loader does not support the [{$driver}] database driver.");
        }

        $version = (string) $connection->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
        $isMariaDb = $driver === 'mariadb' || str_contains(strtolower($version), 'mariadb');
        $minimum = $isMariaDb ? '10.2.0' : '8.0.0';

        if (preg_match('/(\d+\.\d+\.\d+)/', $version, $matches) === 1 && version_compare($matches[1], $minimum, '>=')) {
            return;
        }

        $engine = $isMariaDb ? 'MariaDB '.$minimum.'+' : 'MySQL '.$minimum.'+';

        throw new RuntimeException("The cte tree loader requires {$engine}. This connection reported [{$version}].");
    }

    private function perPage(): int
    {
        return max(1, (int) config('commentify.pagination_count', 10));
    }
}
