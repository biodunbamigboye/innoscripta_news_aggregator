<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\ArticleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    public function index(Request $request): LengthAwarePaginator
    {
        $request->validate([
            'sort' => ['sometimes', Rule::in(['asc', 'desc'])],
        ]);

        $constraints = $request->only([
            'search',
            'author',
            'source',
            'category',
            'date',
            'from_date',
            'to_date',
        ]);

        return ArticleService::getArticles(
            constraints: $constraints,
            sort: $request->get('sort', 'desc'),
            perPage: $request->get('per_page', 30),
            page: $request->get('page', 1)
        );
    }

    public function show(Article $article): JsonResponse
    {
        return response()->json($article->makeVisible(['content']));
    }

    public function authors(Request $request): JsonResponse
    {

        if ($request->has('search')) {
            cache()->forget('authors');
        }

        $authors = cache()->remember('authors', 3600, function () use ($request) {
            return Article::query()
                ->when($request->has('search'), function (Builder $query) use ($request) {
                    $query->whereLike('author', "%{$request->get('search')}%");
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

    public function sources(Request $request): JsonResponse
    {
        if ($request->has('search')) {
            cache()->forget('sources');
        }

        $sources = cache()->remember('sources', 3600, function () use ($request) {
            return Article::query()
                ->when($request->has('search'), function (Builder $query) use ($request) {
                    $query->whereLike('source', "%{$request->get('search')}%");
                })
                ->select('source')
                ->distinct()
                ->get()
                ->pluck('source')
                ->filter()
                ->values();
        });

        return response()->json($sources);
    }

    public function categories(Request $request): JsonResponse
    {
        if ($request->has('search')) {
            cache()->forget('categories');
        }

        $categories = cache()->remember('categories', 3600, function () use ($request) {
            return Article::query()
                ->when($request->has('search'), function (Builder $query) use ($request) {
                    $query->whereLike('category', "%{$request->get('search')}%");
                })
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
