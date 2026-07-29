<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AnalystController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BeritaAcaraController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\DiscountController;
use App\Http\Controllers\Api\InstrumentPriceController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\FormTemplateController;
use App\Http\Controllers\Api\PdfTemplateController;
use App\Http\Controllers\Api\FormulaController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/roles', [AuthController::class, 'listPublicRoles']);
Route::get('/form-templates/type/{type}', [FormTemplateController::class, 'publicActiveByType']);
Route::get('/form-templates/{id}', [FormTemplateController::class, 'publicShow']);

Route::middleware('api.token')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('/auth/password', [AuthController::class, 'changePassword']);

    // Public read-only instrument prices (for form dropdowns)
    Route::get('/instrument-prices', [InstrumentPriceController::class, 'index']);

    Route::middleware('api.token:berita_acara_create,berita_acara_riwayat_personal,hasil_pengujian_rekap,hasil_pengujian_riwayat_personal,payment_upload,payment_discount,payment_riwayat_personal,certificate_view,certificate_download,discount_riwayat_personal')->prefix('/user')->group(function () {
        Route::get('/samples', [BeritaAcaraController::class, 'sampleIndex']);
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
        Route::post('/payments', [PaymentController::class, 'store'])
            ->middleware('permission:payment_upload');
        Route::post('/payments/{id}/discount', [PaymentController::class, 'requestDiscount'])
            ->middleware('permission:payment_discount');
        Route::post('/payments/{id}/upload-proof', [PaymentController::class, 'uploadProof'])
            ->middleware('permission:payment_upload');
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::get('/payments/{id}', [PaymentController::class, 'show']);

        // Certificates
        Route::get('/certificates/{id}/download', [CertificateController::class, 'download'])
            ->middleware('permission:certificate_download');
    });

    Route::middleware('api.token:berita_acara_riwayat_semua,berita_acara_verify,berita_acara_assign,hasil_pengujian_rekap,hasil_pengujian_riwayat_semua,payment_riwayat_semua,payment_confirm,user_view,user_verify,form_manage_berita_acara,form_manage_rekap_analis,form_manage_perhitungan_biaya,form_manage_kelola_harga_instrumen,discount_verifikasi,discount_riwayat_semua')->prefix('/admin')->group(function () {
        Route::get('/berita-acara', [AdminController::class, 'beritaAcaraIndex']);
        Route::get('/berita-acara/{id}', [AdminController::class, 'beritaAcaraShow']);
        Route::put('/berita-acara/{id}/status', [AdminController::class, 'updateStatus'])
            ->middleware('permission:berita_acara_verify');
        Route::put('/berita-acara/{id}/assign-analyst', [AdminController::class, 'assignAnalyst'])
            ->middleware('permission:berita_acara_assign');
        Route::get('/payments', [AdminController::class, 'paymentIndex'])
            ->middleware('permission:payment_riwayat_semua');
        Route::put('/payments/{id}/confirm', [AdminController::class, 'confirmPayment'])
            ->middleware('permission:payment_confirm');
        Route::get('/samples', [AdminController::class, 'sampleIndex']);
        Route::put('/samples/{id}/mark-taken', [AdminController::class, 'markSampleTaken'])
            ->middleware('permission:hasil_pengujian_rekap');
        Route::get('/analysts', [AdminController::class, 'getAnalysts'])
            ->middleware('permission:berita_acara_assign');

        // Discount management
        Route::put('/berita-acara/{id}/confirm-discount', [AnalystController::class, 'confirmDiscount'])
            ->middleware('permission:discount_verifikasi');

        // Manual discount assignment
        Route::get('/discounts', [DiscountController::class, 'index'])
            ->middleware('permission:discount_riwayat_semua');
        Route::get('/discounts/requests', [DiscountController::class, 'requests'])
            ->middleware('permission:discount_riwayat_semua');
        Route::post('/discounts', [DiscountController::class, 'store'])
            ->middleware('permission:discount_verifikasi');
        Route::put('/discounts/{id}', [DiscountController::class, 'update'])
            ->middleware('permission:discount_verifikasi');
        Route::delete('/discounts/{id}', [DiscountController::class, 'destroy'])
            ->middleware('permission:discount_verifikasi');
        Route::post('/discounts/{id}/approve', [DiscountController::class, 'approve'])
            ->middleware('permission:discount_verifikasi');
        Route::post('/discounts/{id}/reject', [DiscountController::class, 'reject'])
            ->middleware('permission:discount_verifikasi');
        Route::get('/discounts/{id}/history', [DiscountController::class, 'history'])
            ->middleware('permission:discount_riwayat_semua');
        // User management
        Route::get('/users', [AdminController::class, 'getUsers'])
            ->middleware('permission:user_view');
        Route::put('/users/{id}/verify', [AdminController::class, 'verifyUser'])
            ->middleware('permission:user_verify');
        Route::put('/users/{id}/deactivate', [AdminController::class, 'deactivateUser'])
            ->middleware('permission:user_verify');

        Route::apiResource('/form-templates', FormTemplateController::class)
            ->middleware('permission:form_manage_berita_acara');
        Route::put('/form-templates/{id}/fields', [FormTemplateController::class, 'syncFields'])
            ->middleware('permission:form_manage_berita_acara');

        // Formulas
        Route::apiResource('/formulas', FormulaController::class)
            ->middleware('permission:form_manage_perhitungan_biaya');
        Route::post('/formulas/{id}/calculate', [FormulaController::class, 'calculate']);

        // Instrument Prices
        Route::get('/instrument-prices', [InstrumentPriceController::class, 'index']);
        Route::post('/instrument-prices', [InstrumentPriceController::class, 'store'])
            ->middleware('permission:form_manage_kelola_harga_instrumen');
        Route::put('/instrument-prices/{id}', [InstrumentPriceController::class, 'update'])
            ->middleware('permission:form_manage_kelola_harga_instrumen');
        Route::delete('/instrument-prices/{id}', [InstrumentPriceController::class, 'destroy'])
            ->middleware('permission:form_manage_kelola_harga_instrumen');
        // PDF Templates Management
        Route::get('/pdf-templates', [PdfTemplateController::class, 'index'])
            ->middleware('permission:form_manage_berita_acara');
        Route::get('/pdf-templates/{type}', [PdfTemplateController::class, 'show'])
            ->middleware('permission:form_manage_berita_acara');
        Route::put('/pdf-templates/{type}', [PdfTemplateController::class, 'update'])
            ->middleware('permission:form_manage_berita_acara');
        Route::post('/pdf-templates/{type}/import-docx', [PdfTemplateController::class, 'importDocx'])
            ->middleware('permission:form_manage_berita_acara');
        Route::post('/pdf-templates/{type}/preview', [PdfTemplateController::class, 'preview'])
            ->middleware('permission:form_manage_berita_acara');
    });

    Route::middleware('api.token:berita_acara_riwayat_semua,hasil_pengujian_rekap,hasil_pengujian_riwayat_personal')->prefix('/analyst')->group(function () {
        Route::get('/berita-acara', [AnalystController::class, 'beritaAcaraIndex']);
        Route::get('/berita-acara/{id}', [AnalystController::class, 'beritaAcaraShow']);
        Route::put('/berita-acara/{id}/samples/{sampleId}', [AnalystController::class, 'updateSample'])
            ->middleware('permission:hasil_pengujian_rekap');
        Route::put('/berita-acara/{id}/samples/{sampleId}/ready', [AnalystController::class, 'markSampleReady'])
            ->middleware('permission:hasil_pengujian_rekap');

        // Pengujian (Rekap Analis) — permission: hasil_pengujian_rekap
        Route::post('/berita-acara/{id}/pengujian', [AnalystController::class, 'storePengujian'])
            ->middleware('permission:hasil_pengujian_rekap');

        // Rekap Analis — permission: hasil_pengujian_rekap
        Route::put('/berita-acara/{id}/rekap-analis', [AnalystController::class, 'storeRekapAnalis'])
            ->middleware('permission:hasil_pengujian_rekap');

        // Discount Request — permission: hasil_pengujian_rekap
        Route::post('/berita-acara/{id}/discount', [AnalystController::class, 'requestDiscount'])
            ->middleware('permission:hasil_pengujian_rekap');

        // Upload Payment Proof — permission: hasil_pengujian_rekap
        Route::post('/berita-acara/{id}/upload-proof', [AnalystController::class, 'uploadProof'])
            ->middleware('permission:hasil_pengujian_rekap');

        // Calculate cost — permission: hasil_pengujian_rekap
        Route::post('/berita-acara/{id}/calculate', [AnalystController::class, 'calculateCost']);

        // Formulas (read-only for analysts)
        Route::get('/formulas', [AnalystController::class, 'listFormulas']);

        // Discount management — permission: discount_verifikasi
        Route::put('/berita-acara/{id}/confirm-discount', [AnalystController::class, 'confirmDiscount'])
            ->middleware('permission:discount_verifikasi');
    });

    // Public/Authenticated PDF Download
    Route::get('/pdf-generate/{type}/{id}', [PdfTemplateController::class, 'generate']);

    // Superadmin routes for role and user management
    Route::middleware('api.token:superadmin')->prefix('/admin')->group(function () {
        Route::get('/roles/permissions/list', [RoleController::class, 'listPermissions'])->name('roles.permissions');
        Route::apiResource('/roles', RoleController::class);
    });
});

