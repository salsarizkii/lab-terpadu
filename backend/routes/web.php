<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;

Route::get('/', function () {
    return view('welcome');
});

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
