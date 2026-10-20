<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pipeline PDF dari file Word (.docx)
    |--------------------------------------------------------------------------
    | Default: file .docx terisi data dirender menjadi PDF oleh Chrome headless
    | dengan pustaka docx-preview (aset di public/pdf-render) — mesin render
    | yang sama dengan pratinjau di aplikasi, jadi hasil PDF identik dengan
    | pratinjau (spasi, font, tabel).
    |
    | PDF_USE_WORD_COM=true → coba Microsoft Word COM lebih dulu (hasil persis
    | Word). NONAKTIF default karena bisa hang tanpa batas waktu bila Word
    | sedang terbuka / instance macet.
    |
    | CHROME_PATH: path lengkap ke chrome/chromium/msedge. Kosongkan agar
    | sistem menebak lokasi umum Windows/Linux.
    */

    'enabled' => env('PDF_DOCX_ENABLED', true),

    'use_word_com' => env('PDF_USE_WORD_COM', false),

    'chrome_path' => env('PDF_CHROME_PATH', ''),
];