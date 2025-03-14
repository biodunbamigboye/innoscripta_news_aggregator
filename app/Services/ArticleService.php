<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class ArticleService
{
    public static function getArticles(array $constraints, string $sort = 'desc', int $perPage = 30, int $page = 1): LengthAwarePaginator
    {
        $constraintKeys = array_keys($constraints);

        return Article::query()
            ->orderBy('published_at', $sort)
            ->when(in_array('search', $constraintKeys), function (Builder $query) use ($constraints) {
                $searchKey = "%{$constraints['search']}%";
                $query->where(function (Builder $query) use ($searchKey) {
                    $query->whereLike('title', $searchKey)
                        ->orWhereLike('description', $searchKey)
                        ->orWhereLike('category', $searchKey)
                        ->orWhereLike('author', $searchKey)
                        ->orWhereLike('source', $searchKey);
                });
            })
            ->when(in_array('author', $constraintKeys), fn (Builder $query) => $query->where('author', $constraints['author']))
            ->when(in_array('source', $constraintKeys), fn (Builder $query) => $query->where('source', $constraints['source']))
            ->when(in_array('category', $constraintKeys), fn (Builder $query) => $query->where('category', $constraints['category']))
            ->when(in_array('date', $constraintKeys), fn (Builder $query) => $query->whereDate('published_at', $constraints['date']))
            ->when(in_array('from_date', $constraintKeys) && ! in_array('to_date', $constraintKeys), fn (Builder $query) => $query->whereDate('published_at', '>=', $constraints['from_date']))
            ->when(in_array('from_date', $constraintKeys) && in_array('to_date', $constraintKeys), function (Builder $query) use ($constraints) {
                $query->whereBetween('published_at', [
                    Carbon::parse($constraints['from_date'])->startOfDay(),
                    Carbon::parse($constraints['to_date'])->endOfDay(),
                ]);
            })
            ->select(['id', 'title', 'description', 'category', 'author', 'source', 'published_at', 'story_url', 'image_url'])
            ->paginate(
                perPage: min($perPage, 60),
                page: $page
            );
    }
}
