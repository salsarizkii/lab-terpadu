<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AnalystController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BeritaAcaraController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\FormTemplateController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/roles', [AuthController::class, 'listPublicRoles']);
Route::get('/form-templates/{id}', [FormTemplateController::class, 'publicShow']);
Route::get('/form-templates/type/{type}', [FormTemplateController::class, 'publicActiveByType']);

Route::middleware('api.token')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::middleware('api.token:user,berita_acara_create,berita_acara_riwayat_personal')->prefix('/user')->group(function () {
        // Berita Acara — permission: berita_acara_create
        Route::get('/berita-acara', [BeritaAcaraController::class, 'index']);
        Route::post('/berita-acara', [BeritaAcaraController::class, 'store'])
            ->middleware('permission:berita_acara_create');
        Route::get('/berita-acara/{id}', [BeritaAcaraController::class, 'show']);
        Route::put('/berita-acara/{id}', [BeritaAcaraController::class, 'update'])
            ->middleware('permission:berita_acara_create');
        Route::delete('/berita-acara/{id}', [BeritaAcaraController::class, 'destroy'])
            ->middleware('permission:berita_acara_create');

        // Payments
        Route::post('/payments', [PaymentController::class, 'store']);
        Route::post('/payments/{id}/discount', [PaymentController::class, 'requestDiscount']);
        Route::post('/payments/{id}/upload-proof', [PaymentController::class, 'uploadProof']);
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::get('/payments/{id}', [PaymentController::class, 'show']);

        // Certificates
        Route::get('/certificates/{id}/download', [CertificateController::class, 'download']);
    });

    Route::middleware('api.token:admin,berita_acara_view,sample_view,payment_view,pengujian_input,user_view,role_manage')->prefix('/admin')->group(function () {
        Route::get('/berita-acara', [AdminController::class, 'beritaAcaraIndex']);
        Route::get('/berita-acara/{id}', [AdminController::class, 'beritaAcaraShow']);
        Route::put('/berita-acara/{id}/status', [AdminController::class, 'updateStatus']);
        Route::put('/berita-acara/{id}/assign-analyst', [AdminController::class, 'assignAnalyst']);
        Route::get('/payments', [AdminController::class, 'paymentIndex']);
        Route::put('/payments/{id}/confirm', [AdminController::class, 'confirmPayment']);
        Route::get('/samples', [AdminController::class, 'sampleIndex']);
        Route::put('/samples/{id}/mark-taken', [AdminController::class, 'markSampleTaken']);
        Route::get('/analysts', [AdminController::class, 'getAnalysts']);

        Route::apiResource('/form-templates', FormTemplateController::class);
        Route::put('/form-templates/{id}/fields', [FormTemplateController::class, 'syncFields']);
    });

    Route::middleware('api.token:analyst,pengujian_input,berita_acara_view,sample_view')->prefix('/analyst')->group(function () {
        Route::get('/berita-acara', [AnalystController::class, 'beritaAcaraIndex']);
        Route::get('/berita-acara/{id}', [AnalystController::class, 'beritaAcaraShow']);
        Route::put('/berita-acara/{id}/samples/{sampleId}', [AnalystController::class, 'updateSample']);
        Route::put('/berita-acara/{id}/samples/{sampleId}/ready', [AnalystController::class, 'markSampleReady']);

        // Pengujian (Rekap Analis) — permission: pengujian_input
        Route::post('/berita-acara/{id}/pengujian', [AnalystController::class, 'storePengujian'])
            ->middleware('permission:pengujian_input');
    });

    // Superadmin routes for role and user management
    Route::middleware('api.token:superadmin')->prefix('/admin')->group(function () {
        Route::get('/roles/permissions/list', [RoleController::class, 'listPermissions'])->name('roles.permissions');
        Route::apiResource('/roles', RoleController::class);
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::put('/users/{id}/verify', [AdminController::class, 'verifyUser']);
    });
});

