<?php

namespace App\Services;

use App\Models\PdfTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Pipeline dokumen berbasis file Word (.docx) asli.
 *
 * Template .docx dijadikan sumber kebenaran: placeholder (<<variabel>>, ${variabel},
 * {{ variabel }}, termasuk yang terpotong antar run oleh Word) diganti nilainya,
 * lalu dirender menjadi PDF oleh LibreOffice -- sehingga hasilnya sama persis
 * dengan template Word tanpa konversi HTML.
 */
class DocxPdfService
{
    /**
     * Render PDF dari template Word yang sudah diisi data.
     *
     * @return string|null Binary PDF, atau null bila pipeline tidak tersedia
     *                     (LibreOffice belum terpasang / template tidak punya file Word).
     */
    public static function generatePdf(PdfTemplate $template, array $data = []): ?string
    {
        if (!config('pdf.enabled', true)) {
            return null;
        }

        $soffice = self::sofficeBinary();
        if ($soffice === null) {
            Log::info('DocxPdfService: LibreOffice tidak tersedia, gunakan fallback HTML/DomPDF.');
            return null;
        }

        if (empty($template->docx_path)) {
            return null;
        }

        $sourcePath = storage_path('app/public/' . $template->docx_path);
        if (!is_file($sourcePath)) {
            Log::warning('DocxPdfService: file docx tidak ditemukan di ' . $sourcePath);
            return null;
        }

        try {
            PdfGeneratorService::applySettings($template, $data);
            $data['{{ tanggal_cetak }}'] = $data['{{ tanggal_cetak }}'] ?? date('d F Y');

            $filledDocx = self::fillDocx($sourcePath, $data, $template->catatan ?? '');
            $pdf = self::docxToPdf($filledDocx, $soffice);

            @unlink($filledDocx);

            return $pdf;
        } catch (\Throwable $e) {
            Log::error('DocxPdfService gagal membuat dokumen: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Buat file .docx terisi data (tanpa konversi PDF). Selalu tersedia
     * selama template punya file Word -- tidak butuh LibreOffice.
     *
     * @return string|null Binary docx, atau null bila template tidak punya file Word.
     */
    public static function generateFilledDocx(PdfTemplate $template, array $data = []): ?string
    {
        if (empty($template->docx_path)) {
            return null;
        }

        $sourcePath = storage_path('app/public/' . $template->docx_path);
        if (!is_file($sourcePath)) {
            Log::warning('DocxPdfService: file docx tidak ditemukan di ' . $sourcePath);
            return null;
        }

        try {
            PdfGeneratorService::applySettings($template, $data);
            $data['{{ tanggal_cetak }}'] = $data['{{ tanggal_cetak }}'] ?? date('d F Y');

            $filledDocx = self::fillDocx($sourcePath, $data, $template->catatan ?? '');
            $content = (string) file_get_contents($filledDocx);
            @unlink($filledDocx);

            return $content;
        } catch (\Throwable $e) {
            Log::error('DocxPdfService gagal membuat docx: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Lokasi biner LibreOffice (soffice). Menggunakan LIBREOFFICE_PATH dari env
     * jika diisi, lalu menebak path umum di Windows/Linux/macOS.
     */
    public static function sofficeBinary(): ?string
    {
        $configured = trim((string) config('pdf.libreoffice_path', ''));
        if ($configured !== '') {
            if (is_file($configured)) {
                return $configured;
            }
            Log::warning("DocxPdfService: LIBREOFFICE_PATH ({$configured}) tidak ditemukan.");
        }

        $candidates = [
            'C:\Program Files\LibreOffice\program\soffice.exe',
            'C:\Program Files (x86)\LibreOffice\program\soffice.exe',
            '/usr/bin/soffice',
            '/usr/local/bin/soffice',
            '/opt/libreoffice/program/soffice',
            '/Applications/LibreOffice.app/Contents/MacOS/soffice',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        // Percobaan terakhir: `soffice` tersedia di PATH?
        if (self::commandExists('soffice')) {
            return 'soffice';
        }

        return null;
    }

    /**
     * Isi placeholder pada file .docx dengan nilai dari $data.
     * Menghasilkan file .docx hasil (temp) yang siap dirender.
     */
    public static function fillDocx(string $sourceDocx, array $data, string $catatan = ''): string
    {
        $lookup = [];
        foreach ($data as $key => $value) {
            if ($value === null) {
                $value = '';
            }
            $lookup[self::keySlug($key)] = (string) $value;
        }

        $zip = new ZipArchive();
        $src = $zip->open($sourceDocx);
        if ($src !== true) {
            throw new \RuntimeException("Gagal membuka docx: {$sourceDocx} ({$src})");
        }

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[] = $zip->getNameIndex($i);
        }

        $target = tempnam(sys_get_temp_dir(), 'docxfilled_') . '.docx';
        $out = new ZipArchive();
        $out->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $content = $zip->getFromIndex($i);

            $modified = false;
            if ($name === 'word/document.xml') {
                $content = self::fillBodyXml($content, $lookup);
                if ($catatan !== '') {
                    $content = self::appendCatatan($content, $catatan);
                }
                $modified = true;
            } elseif (preg_match('#^word/(header\d*|footer\d*)\.xml$#', $name)) {
                $content = self::fillBodyXml($content, $lookup);
                $modified = true;
            }

            if ($content === false) {
                $content = '';
            }
            $out->addFromString($name, $content);
        }

        $zip->close();
        $out->close();

        return $target;
    }

    /**
     * Isi placeholder pada XML isi dokumen (word/document.xml).
     */
    private static function fillBodyXml(string $xml, array $lookup): string
    {
        // Placeholder tidak melewati batas paragraf: proses per <w:p>.
        return preg_replace_callback(
            '#<w:p\b[^>]*>.*?</w:p>#su',
            function ($m) use ($lookup) {
                return self::fillParagraph($m[0], $lookup);
            },
            $xml
        ) ?? $xml;
    }

    /**
     * Isi placeholder dalam satu paragraf. Word sering memotong teks menjadi
     * banyak <w:r><w:t>, sehingga teks digabung dulu, token diganti, lalu hasil
     * ditempatkan pada run teks pertama.
     */
    private static function fillParagraph(string $paragraph, array $lookup): string
    {
        if (!self::paragraphHasToken($paragraph)) {
            return $paragraph;
        }

        preg_match_all('#<w:t(?:\s[^>]*)?>(.*?)</w:t>#su', $paragraph, $matches, PREG_OFFSET_CAPTURE);
        if (empty($matches[1])) {
            return $paragraph;
        }

        $segments = [];
        foreach ($matches[1] as [$content]) {
            $segments[] = $content;
        }

        $merged = html_entity_decode(implode('', $segments), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $replaced = self::replaceTokens($merged, $lookup);

        if ($replaced === $merged) {
            return $paragraph;
        }

        // Ganti semua <w:t>...</w:t>: hasil yang sudah diisi ke w:t pertama,
        // sisanya dikosongkan agar posisi/format elemen lain tetap utuh.
        $replacedRan = false;
        $newParagraph = preg_replace_callback(
            '#<w:t(\s[^>]*)?>(.*?)</w:t>#su',
            function ($w) use ($replaced, &$replacedRan) {
                $attrs = $w[1] ?? '';
                // Pastikan xml:space="preserve" saat konten diawali/akhiri spasi.
                if (!preg_match('/\sxml:space\s*=\s*"[^"]*"/i', $attrs)) {
                    $attrs .= ' xml:space="preserve"';
                }
                $text = htmlspecialchars($replacedRan ? '' : $replaced, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $replacedRan = true;
                return '<w:t' . $attrs . '>' . $text . '</w:t>';
            },
            $paragraph
        );

        return $newParagraph ?? $paragraph;
    }

    /**
     * Deteksi awal apakah paragraf mengandung placeholder (cepat, tanpa parse penuh).
     */
    private static function paragraphHasToken(string $paragraph): bool
    {
        return str_contains($paragraph, '&lt;&lt;')
            || str_contains($paragraph, '<<')
            || str_contains($paragraph, '${')
            || str_contains($paragraph, '{{');
    }

    /**
     * Ganti token <<nama>>, ${nama}, {{ nama }} (case-insensitive, spasi & enter
     * diperbolehkan di dalamnya) dengan nilai yang slug-nya cocok di $lookup.
     */
    private static function replaceTokens(string $text, array $lookup): string
    {
        $patterns = [
            '<<\s*(.*?)\s*>>' => '<<$1>>',
            '\$\{\s*(.*?)\s*\}' => '${$1}',
            '\{\{\s*(.*?)\s*\}\}' => '{{$1}}',
        ];

        foreach ($patterns as $pattern => $dummy) {
            $text = preg_replace_callback('/' . $pattern . '/us', function ($m) use ($lookup) {
                $slug = self::keySlug($m[1]);
                return array_key_exists($slug, $lookup) ? $lookup[$slug] : $m[0];
            }, $text) ?? $text;
        }

        return $text;
    }

    /**
     * Ubah nama token menjadi slug kunci data: "{{ Nama Pemohon }}" → "nama_pemohon".
     */
    private static function keySlug(string $key): string
    {
        $key = html_entity_decode(trim((string) $key), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $key = preg_replace('/[\{\}\<\>\$\s]+/u', ' ', $key) ?? $key;
        $key = str_replace(['/', '\\'], ' ', $key);
        return Str::slug($key, '_');
    }

    /**
     * Tambahkan paragraf catatan di akhir isi dokumen.
     */
    private static function appendCatatan(string $xml, string $catatan): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $catatan) ?: [$catatan];

        $runs = '';
        foreach ($lines as $lineIdx => $line) {
            $text = htmlspecialchars($line, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $runs .= '<w:r><w:t xml:space="preserve">' . $text . '</w:t></w:r>';
            if ($lineIdx < count($lines) - 1) {
                $runs .= '<w:r><w:br/></w:r>';
            }
        }

        $paragraph = '<w:p>'
            . '<w:pPr><w:spacing w:before="240" w:after="0"/></w:pPr>'
            . $runs
            . '</w:p>';

        $pos = strrpos($xml, '</w:body>');
        if ($pos === false) {
            return $xml;
        }

        return substr($xml, 0, $pos) . $paragraph . substr($xml, $pos);
    }

    /**
     * Konversi docx → PDF melalui LibreOffice headless.
     */
    public static function docxToPdf(string $docxPath, ?string $soffice = null): string
    {
        $soffice = $soffice ?? self::sofficeBinary();
        if ($soffice === null) {
            throw new \RuntimeException('LibreOffice tidak tersedia');
        }

        $workDir = sys_get_temp_dir() . '/docxpdf_' . uniqid('', true);
        if (!mkdir($workDir, 0755, true)) {
            throw new \RuntimeException('Gagal membuat folder kerja sementara');
        }

        try {
            // Profil user terpisah agar tidak konflik bila LibreOffice sedang terbuka.
            $profile = str_replace('\\', '/', $workDir . '/profile');
            $cmd = [
                $soffice,
                '-env:UserInstallation=file:///' . $profile,
                '--headless',
                '--norestore',
                '--nolockcheck',
                '--nologo',
                '--convert-to',
                'pdf',
                '--outdir',
                $workDir,
                $docxPath,
            ];

            $proc = proc_open($cmd, [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ], $pipes);

            if (!is_resource($proc)) {
                throw new \RuntimeException('Gagal menjalankan LibreOffice');
            }

            stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            $exitCode = proc_close($proc);
            if ($exitCode !== 0) {
                Log::error("DocxPdfService: soffice keluar dengan kode {$exitCode}: " . substr($stderr, 0, 500));
                throw new \RuntimeException("LibreOffice gagal mengonversi docx (kode {$exitCode})");
            }

            $pdfPath = $workDir . '/' . pathinfo($docxPath, PATHINFO_FILENAME) . '.pdf';
            for ($tries = 0; $tries < 20 && !is_file($pdfPath); $tries++) {
                usleep(200000);
            }

            if (!is_file($pdfPath)) {
                throw new \RuntimeException('File PDF hasil konversi tidak ditemukan');
            }

            return (string) file_get_contents($pdfPath);
        } finally {
            self::removeDir($workDir);
        }
    }

    /**
     * Periksa apakah perintah tersedia di PATH.
     */
    private static function commandExists(string $cmd): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $p = proc_open('where ' . escapeshellarg($cmd), [1 => ['pipe', 'w']], $pipes);
            if (is_resource($p)) {
                stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                return proc_close($p) === 0;
            }
            return false;
        }

        $p = proc_open('command -v ' . escapeshellarg($cmd), [1 => ['pipe', 'w']], $pipes);
        if (is_resource($p)) {
            stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            return proc_close($p) === 0;
        }
        return false;
    }

    /**
     * Hapus folder beserta isinya secara rekursif.
     */
    private static function removeDir(string $dir): void
    {
        // Jangan hapus symlink yang menunjuk keluar.
        if (is_link($dir)) {
            @unlink($dir);
            return;
        }
        if (!is_dir($dir)) {
            (is_file($dir)) && @unlink($dir);
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            self::removeDir($dir . DIRECTORY_SEPARATOR . $item);
        }
        @rmdir($dir);
    }
}
