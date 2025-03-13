<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class ArticleController extends Controller
{
    public function index(Request $request): LengthAwarePaginator
    {
        return Article::query()
            ->orderByDesc('published_at')
            ->when($request->has('search'), function (Builder $query) use ($request) {
                $searchKey = "%{$request->get('search')}%";
                $query->where(function (Builder $query) use ($searchKey) {
                    $query->whereLike('title', $searchKey)
                        ->orWhereLike('description', $searchKey)
                        ->orWhereLike('category', $searchKey)
                        ->orWhereLike('author', $searchKey)
                        ->orWhereLike('source', $searchKey);
                });
            })
            ->when($request->has('author'), fn (Builder $query) => $query->where('author', $request->get('author')))
            ->when($request->has('source'), fn (Builder $query) => $query->where('source', $request->get('source')))
            ->when($request->has('category'), fn (Builder $query) => $query->where('category', $request->get('category')))
            ->when($request->has('date'), fn (Builder $query) => $query->whereDate('published_at', $request->get('date')))
            ->when($request->has('from_date') && ! $request->has('to_date'), fn (Builder $query) => $query->whereDate('published_at', '>=', $request->get('from_date')))
            ->when($request->has(['from_date', 'to_date']), function (Builder $query) use ($request) {
                $query->whereBetween('published_at', [
                    Carbon::parse($request->get('from_date'))->startOfDay(),
                    Carbon::parse($request->get('to_date'))->endOfDay(),
                ]);
            })
            ->select(['id', 'title', 'description', 'category', 'author', 'source', 'published_at', 'story_url', 'image_url'])
            ->paginate(
                perPage: min($request->get('per_page', 30), 60),
                page: $request->get('page', 1),
            );
    }

    public function show(Article $article): JsonResponse
    {
        return response()->json($article->makeVisible(['content']));
    }

    public function authors(Request $request): JsonResponse
    {

        // use  cache
        $authors = cache()->remember('authors', 3600, function () use ($request) {
            return Article::query()
                ->when($request->has('search'), function (Builder $query) use ($request) {
                    $searchKey = "%{$request->get('search')}%";
                    $query->where(function (Builder $query) use ($searchKey) {
                        $query->whereLike('author', $searchKey);
                    });
                })
                ->select('author')
                ->distinct()
                ->get()
                ->pluck('author')
                ->filter()
                ->values();
        });

        return response()->json($authors);
    }

    public function sources(): JsonResponse
    {
        $sources = cache()->remember('sources', 3600, function () {
            return Article::query()
                ->select('source')
                ->distinct()
                ->get()
                ->pluck('source')
                ->filter()
                ->values();
        });

        return response()->json($sources);
    }

    public function categories(): JsonResponse
    {
        $categories = cache()->remember('categories', 3600, function () {
            return Article::query()
                ->select('category')
                ->distinct()
                ->get()
                ->pluck('category')
                ->filter()
                ->values();
        });

        return response()->json($categories);
    }
}
