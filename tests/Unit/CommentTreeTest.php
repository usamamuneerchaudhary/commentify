<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Usamamuneerchaudhary\Commentify\Models\Comment;
use Usamamuneerchaudhary\Commentify\Models\User;
use Usamamuneerchaudhary\Commentify\Support\CommentTree;

class CommentTreeTest extends TestCase
{
    private User $user;

    private ArticleStub $article;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->article = ArticleStub::create([
            'slug' => 'tree-article',
        ]);
    }

    public function test_eager_loader_stops_at_the_configured_depth(): void
    {
        [$root, $child, $grandchild] = $this->thread();

        $loadedRoot = $this->loadedRoot($root);

        $loadedChild = $loadedRoot->children->firstWhere('id', $child->id);
        $loadedGrandchild = $loadedChild->children->firstWhere('id', $grandchild->id);

        $this->assertNotNull($loadedGrandchild);
        $this->assertFalse($loadedGrandchild->relationLoaded('children'));
    }

    public function test_cte_loader_matches_the_eager_depth_and_uses_one_recursive_query(): void
    {
        config()->set('commentify.tree_loader', 'cte');
        [$root, $child, $grandchild] = $this->thread();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $loadedRoot = $this->loadedRoot($root);
        $sql = strtolower(implode("\n", array_column(DB::getQueryLog(), 'query')));

        $loadedChild = $loadedRoot->children->firstWhere('id', $child->id);
        $loadedGrandchild = $loadedChild->children->firstWhere('id', $grandchild->id);

        $this->assertNotNull($loadedGrandchild);
        $this->assertFalse($loadedGrandchild->relationLoaded('children'));
        $this->assertStringContainsString('commentify_tree', $sql);
    }

    public function test_cte_loader_honors_global_scopes_and_approval(): void
    {
        config()->set('commentify.tree_loader', 'cte');
        config()->set('commentify.require_approval', true);

        $root = $this->make(null, 'Root', Carbon::parse('2026-01-01 00:00:00'), true);
        $visible = $this->make($root, 'Visible reply', Carbon::parse('2026-01-01 00:01:00'), true);
        $this->make($root, 'secret reply', Carbon::parse('2026-01-01 00:02:00'), true);
        $this->make($root, 'Pending reply', Carbon::parse('2026-01-01 00:03:00'), false);
        $this->make($root, 'Deleted reply', Carbon::parse('2026-01-01 00:04:00'), true)->delete();

        Comment::addGlobalScope('commentify-test-hide-secret', function ($builder): void {
            $builder->where('body', 'not like', 'secret%');
        });

        try {
            $loadedRoot = $this->loadedRoot($root);
            $bodies = $loadedRoot->children->pluck('body')->all();

            $this->assertSame(['Visible reply'], $bodies);
            $this->assertTrue($visible->is($loadedRoot->children->first()));
        } finally {
            $this->removeGlobalScope('commentify-test-hide-secret');
        }
    }

    public function test_max_depth_limits_the_cte(): void
    {
        config()->set('commentify.tree_loader', 'cte');
        config()->set('commentify.max_depth', 1);

        [$root, $child] = $this->thread();
        $loadedRoot = $this->loadedRoot($root);
        $loadedChild = $loadedRoot->children->firstWhere('id', $child->id);

        $this->assertNotNull($loadedChild);
        $this->assertFalse($loadedChild->relationLoaded('children'));
    }

    public function test_unknown_tree_loader_is_rejected(): void
    {
        config()->set('commentify.tree_loader', 'nested-set');

        $this->expectException(RuntimeException::class);

        (new CommentTree($this->article))->paginate('newest');
    }

    /**
     * @return array{0: Comment, 1: Comment, 2: Comment}
     */
    private function thread(): array
    {
        $root = $this->make(null, 'Root', Carbon::parse('2026-01-01 00:00:00'));
        $child = $this->make($root, 'Child', Carbon::parse('2026-01-01 00:01:00'));
        $grandchild = $this->make($child, 'Grandchild', Carbon::parse('2026-01-01 00:02:00'));
        $this->make($grandchild, 'Great grandchild', Carbon::parse('2026-01-01 00:03:00'));

        return [$root, $child, $grandchild];
    }

    private function make(?Comment $parent, string $body, Carbon $createdAt, bool $approved = true): Comment
    {
        return $this->article->comments()->create([
            'body' => $body,
            'user_id' => $this->user->id,
            'parent_id' => $parent?->id,
            'created_at' => $createdAt,
            'is_approved' => $approved,
        ]);
    }

    private function loadedRoot(Comment $root): Comment
    {
        $loaded = (new CommentTree($this->article))
            ->paginate('oldest')
            ->getCollection()
            ->firstWhere('id', $root->id);

        $this->assertInstanceOf(Comment::class, $loaded);

        return $loaded;
    }

    private function removeGlobalScope(string $name): void
    {
        $property = new ReflectionProperty(Model::class, 'globalScopes');
        $scopes = $property->getValue();
        unset($scopes[Comment::class][$name]);
        $property->setValue(null, $scopes);
    }
}
