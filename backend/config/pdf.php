<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pipeline PDF dari file Word (.docx)
    |--------------------------------------------------------------------------
    | Ketika aktif dan template memiliki file Word yang diunggah, dokumen
    | dirender langsung dari .docx via LibreOffice sehingga hasilnya sama
    | persis dengan template Word (bukan konversi HTML).
    |
    | LIBREOFFICE_PATH: path lengkap ke biner soffice. Kosongkan agar sistem
    | menebak path umum (Windows: C:\Program Files\LibreOffice\...).
    */

    'enabled' => env('PDF_DOCX_ENABLED', true),

    'libreoffice_path' => env('LIBREOFFICE_PATH', ''),
];
