<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AnalystController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BeritaAcaraController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('api.token')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::middleware('api.token:user')->prefix('/user')->group(function () {
        Route::apiResource('/berita-acara', BeritaAcaraController::class);
        Route::post('/payments', [PaymentController::class, 'store']);
        Route::post('/payments/{id}/discount', [PaymentController::class, 'requestDiscount']);
        Route::post('/payments/{id}/upload-proof', [PaymentController::class, 'uploadProof']);
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::get('/payments/{id}', [PaymentController::class, 'show']);
        Route::get('/certificates/{id}/download', [CertificateController::class, 'download']);
    });

    Route::middleware('api.token:admin')->prefix('/admin')->group(function () {
        Route::get('/berita-acara', [AdminController::class, 'beritaAcaraIndex']);
        Route::get('/berita-acara/{id}', [AdminController::class, 'beritaAcaraShow']);
        Route::put('/berita-acara/{id}/status', [AdminController::class, 'updateStatus']);
        Route::put('/berita-acara/{id}/assign-analyst', [AdminController::class, 'assignAnalyst']);
        Route::get('/payments', [AdminController::class, 'paymentIndex']);
        Route::put('/payments/{id}/confirm', [AdminController::class, 'confirmPayment']);
        Route::get('/samples', [AdminController::class, 'sampleIndex']);
        Route::put('/samples/{id}/mark-taken', [AdminController::class, 'markSampleTaken']);
        Route::get('/analysts', [AdminController::class, 'getAnalysts']);
    });

    Route::middleware('api.token:analyst')->prefix('/analyst')->group(function () {
        Route::get('/berita-acara', [AnalystController::class, 'beritaAcaraIndex']);
        Route::get('/berita-acara/{id}', [AnalystController::class, 'beritaAcaraShow']);
        Route::put('/berita-acara/{id}/samples/{sampleId}', [AnalystController::class, 'updateSample']);
        Route::put('/berita-acara/{id}/samples/{sampleId}/ready', [AnalystController::class, 'markSampleReady']);
        Route::post('/berita-acara/{id}/pengujian', [AnalystController::class, 'storePengujian']);
    });
});
