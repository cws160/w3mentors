<?php

use App\Http\Controllers\Api\V1\ImageController;
use App\Http\Controllers\SitemapFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'W3Mentors API',
        'version' => '1.0.0',
        'docs' => '/api/v1/health',
    ]);
});

Route::get('/image/editor-image/{path}', [ImageController::class, 'editorImage'])
    ->where('path', '.*');

Route::get('/images/{path}', [ImageController::class, 'editorImage'])
    ->where('path', '.*');

Route::get('/image/show/{fileType}/{recordId}/{size?}/{langId?}', [ImageController::class, 'show'])
    ->where(['fileType' => '[0-9]+', 'recordId' => '[0-9]+', 'langId' => '[0-9]*']);
Route::get('/image/show-by-id/{fileId}/{size?}', [ImageController::class, 'showById'])
    ->where('fileId', '[0-9]+');

Route::get('/admin-dashboard-bridge.php', \App\Http\Controllers\LegacyDashboardBridgeController::class);

Route::get('/sitemap.xml', [SitemapFileController::class, 'index']);
Route::get('/sitemap/list_{number}.xml', [SitemapFileController::class, 'list'])->whereNumber('number');
