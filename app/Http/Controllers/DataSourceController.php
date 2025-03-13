<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateDataSourceRequest;
use App\Http\Requests\UpdateDataSourceRequest;
use App\Models\DataSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class DataSourceController extends Controller
{
    public function index(Request $request): LengthAwarePaginator
    {
        return DataSource::query()
            ->when($request->has('search'), function ($query) use ($request) {
                $query->whereLike('name', "%{$request->get('search')}%");
            })
            ->paginate(
                perPage: min($request->get('per_page', 20), 60),
                page: $request->get('page', 1),
            );
    }

    public function store(CreateDataSourceRequest $request): JsonResponse
    {
        return response()->json(
            data: DataSource::query()->create($request->validated()),
            status: JsonResponse::HTTP_CREATED
        );
    }

    public function show(DataSource $dataSource): JsonResponse
    {
        return response()->json(data: $dataSource);
    }

    public function update(UpdateDataSourceRequest $request, DataSource $dataSource): JsonResponse
    {
        $dataSource->update($request->validated());

        return response()->json(data: $dataSource->refresh());
    }

    public function destroy(DataSource $dataSource): JsonResponse
    {
        $dataSource->delete();

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
