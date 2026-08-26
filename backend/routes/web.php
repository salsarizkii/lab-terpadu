<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;

Route::get('/', function () {
    return response()->file(public_path('index.html'));
});

Route::get('/{any}', function ($any) {
    if (str_starts_with($any, 'api/') || str_starts_with($any, 'storage/')) {
        abort(404);
    }
    $file = public_path($any);
    if (is_file($file)) {
        return response()->file($file);
    }
    return response()->file(public_path('index.html'));
})->where('any', '.*');

Route::get('/storage/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath)) {
        abort(404);
    }
    
    $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';
    if (str_ends_with(strtolower($fullPath), '.pdf')) {
        $mimeType = 'application/pdf';
    }
    
    return response()->file($fullPath, [
        'Content-Type' => $mimeType,
        'Access-Control-Allow-Origin' => '*',
        'X-Frame-Options' => 'ALLOWALL',
    ]);
})->where('path', '.*');
