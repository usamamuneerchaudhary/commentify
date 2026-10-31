<?php

namespace Usamamuneerchaudhary\Commentify\Support;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class MentionSearch
{
    /**
     * Users whose handle matches the mention term, with LIKE wildcards escaped.
     *
     * @return Collection<int, Model>
     */
    public static function defaultQuery(string $term): Collection
    {
        $model = config('commentify.user_model');
        $column = config('commentify.mentions.handle_column') ?: 'name';
        $like = '%'.self::escapeLike($term).'%';
        $query = $model::query();
        $wrapped = $query->getGrammar()->wrap($column);

        return $query
            ->whereRaw($wrapped.' like ? escape ?', [$like, '\\'])
            ->orderBy($column)
            ->limit(5)
            ->get();
    }

    /**
     * Escape backslashes and LIKE wildcards so the term is matched literally.
     */
    public static function escapeLike(string $term): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
    }
}
