<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('data-sources', \App\Http\Controllers\DataSourceController::class)
    ->middleware('auth:sanctum')
    ->names([
        'index' => 'Get Data Sources',
        'store' => 'Create Data Source',
        'show' => 'Get Data Source',
        'update' => 'Update Data Source',
        'destroy' => 'Delete Data Source',
    ]);

Route::get('/articles', [\App\Http\Controllers\ArticleController::class, 'index'])->name('Get Articles');
Route::get('/articles/{article}', [\App\Http\Controllers\ArticleController::class, 'show'])->name('Get Article');
Route::get('/authors', [\App\Http\Controllers\ArticleController::class, 'authors'])->name('Get Authors');
Route::get('/categories', [\App\Http\Controllers\ArticleController::class, 'categories'])->name('Get Categories');
Route::get('/sources', [\App\Http\Controllers\ArticleController::class, 'sources'])->name('Get Sources');
