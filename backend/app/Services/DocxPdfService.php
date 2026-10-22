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
 * lalu file .docx terisi dirender menjadi PDF oleh Chrome headless dengan pustaka
 * docx-preview -- mesin render yang sama dengan pratinjau di aplikasi, sehingga
 * hasil PDF identik dengan pratinjau (spasi, font, tabel) tanpa konversi HTML.
 */
class DocxPdfService
{
    /**
     * Render PDF dari template Word yang sudah diisi data.
     *
     * Pipeline: .docx terisi → Chrome headless + docx-preview (hasil identik
     * dengan pratinjau aplikasi). Bila PDF_USE_WORD_COM=true, Microsoft Word
     * COM dicoba lebih dulu (hasil persis Word). Fallback bila Chrome tidak
     * tersedia: PhpWord → PDF (DomPDF), murni PHP.
     *
     * @return string|null Binary PDF, atau null bila pipeline tidak tersedia
     *                     (template tidak punya file Word / gagal konversi).
     */
    public static function generatePdf(PdfTemplate $template, array $data = []): ?string
    {
        if (!config('pdf.enabled', true)) {
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

            $pdf = null;
            try {
                // Opsional: konversi via Microsoft Word COM (persis Word).
                if (config('pdf.use_word_com', false)) {
                    $pdf = self::docxToPdfViaWord($filledDocx);
                }
            } catch (\Throwable $e) {
                Log::error('DocxPdfService: Word COM gagal: ' . $e->getMessage());
            }

            // Utama: render .docx terisi dengan docx-preview di Chrome headless —
            // mesin yang sama dengan pratinjau, jadi PDF identik dengan pratinjau.
            if ($pdf === null) {
                $pdf = self::docxToPdfViaChrome($filledDocx);
            }

            // Fallback: PhpWord → PDF (DomPDF) — murni PHP.
            if ($pdf === null) {
                $pdf = self::renderDocxToPdfViaPhpWord($sourcePath, $data, $template->catatan ?? '');
            }

            @unlink($filledDocx);

            return $pdf;
        } catch (\Throwable $e) {
            Log::error('DocxPdfService gagal membuat dokumen: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            // Jangan lempar exception supaya controller fallback ke render
            // HTML/DomPDF yang selalu tersedia.
            return null;
        }
    }

    /**
     * Render .docx terisi → PDF via PhpWord + DomPDF (murni PHP).
     * Mempertahankan struktur dokumen (tabel, font, ukuran) sehingga hasilnya
     * mirip render Word, bukan konversi HTML.
     *
     * @return string|null Binary PDF, atau null bila gagal.
     */
    private static function renderDocxToPdfViaPhpWord(string $docxPath, array $data = [], string $catatan = ''): ?string
    {
        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($docxPath);

            $htmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
            $html = $htmlWriter->getContent();

            // Ganti placeholder di HTML string (bukan di docx): PhpWord menulis
            // tiap run dalam <span> terpisah, jadi normalisasi dulu format
            // <<x>> / ${x} / {{ x }} yang terpotong antar tag, lalu isi data.
            $html = self::fillPlaceholdersInHtml($html, $data, $catatan);

            // PhpWord HTML writer memberi border hitam + lebar 100% ke SEMUA
            // tabel/sel via CSS global, dan TIDAK membaca border/lebar tabel
            // dari docx (reader mengabaikan w:tblBorders/w:tblW). Baca info
            // asli dari XML, lalu tulis INLINE per elemen: lebar asli + border
            // hanya untuk tabel yang benar-benar ber-border (inline style
            // lebih andal di DomPDF daripada class selector).
            $tables = self::docxTableInfo($docxPath);
            if ($tables) {
                $tableIdx = 0;
                $inBordered = false;
                $html = preg_replace_callback('/<table([^>]*)>|<\/table>|<t([dh])([^>]*)>/i', function ($m) use (&$tableIdx, &$inBordered, $tables) {
                    if ($m[0] === '</table>') {
                        $inBordered = false;

                        return '</table>';
                    }
                    if (str_starts_with($m[0], '<table')) {
                        $attr = $m[1];
                        $info = $tables[$tableIdx++] ?? null;
                        $styles = [];
                        if ($info && $info['width'] !== null) {
                            $styles[] = 'width: ' . $info['width'] . 'pt';
                        }
                        if ($info && $info['border']) {
                            $inBordered = true;
                            $styles[] = 'border: 1px solid #000';
                            $styles[] = 'border-collapse: collapse';
                        }
                        if ($styles) {
                            $attr = self::mergeHtmlAttr($attr, 'style', implode('; ', $styles));
                        }

                        return '<table' . $attr . '>';
                    }

                    // <td ...> / <th ...>
                    $attr = $m[3];
                    if ($inBordered) {
                        $attr = self::mergeHtmlAttr($attr, 'style', 'border: 1px solid #000');
                    }

                    return '<t' . $m[2] . $attr . '>';
                }, $html) ?? $html;
            }

            // Buang border universal PhpWord; border kini inline per tabel.
            // (Sekali jalan lewat callback agar rule td yang baru disisipkan
            // tidak ikut dihapus.)
            $css = 'table { border-spacing: 0; }';

            // @page bernama (page1) dari PhpWord membuat DomPDF berhenti
            // merender border tabel — hapus, margin halaman dipindah ke body.
            if (preg_match('/@page\s+page1\s*\{([^}]*)\}/s', $html, $pm)) {
                $sides = ['top' => '0.5in', 'right' => '0.5in', 'bottom' => '0.5in', 'left' => '0.5in'];
                foreach ($sides as $side => $fallback) {
                    if (preg_match('/margin-' . $side . '\s*:\s*([^;]+)/', $pm[1], $mm)) {
                        $sides[$side] = trim($mm[1]);
                    }
                }
                $css .= ' body { margin: ' . $sides['top'] . ' ' . $sides['right'] . ' ' . $sides['bottom'] . ' ' . $sides['left'] . '; }';
                $html = preg_replace('/@page\s+page1\s*\{[^}]*\}\s*/s', '', $html) ?? $html;
            }

            $html = preg_replace_callback('/(table|td)\s*\{[^}]*\}/s', function ($match) use ($css) {
                return $match[1] === 'table' ? $css : '';
            }, $html, 2) ?? $html;

            $options = new \Dompdf\Options();
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->setPaper('a4', 'portrait');
            $dompdf->loadHtml($html);
            $dompdf->render();

            return $dompdf->output();
        } catch (\Throwable $e) {
            Log::error('DocxPdfService: PhpWord→PDF gagal: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Ganti placeholder di HTML yang dihasilkan PhpWord dengan data.
     * Placeholder bisa terpotong antar tag span (tiap run PhpWord = 1 span):
     * << << /span><span>nama_pemohon >> >> → dinormalisasi lalu diisi data.
     * Catatan ditambahkan sebelum </body>.
     */
    private static function fillPlaceholdersInHtml(string $html, array $data, string $catatan): string
    {
        // Lookup slug → nilai, sama seperti fillDocx (keySlug menormalkan
        // "{{ Nama Pemohon }}" → "nama_pemohon"; lookup case-insensitive).
        $lookup = [];
        foreach ($data as $key => $value) {
            if ($value === null) {
                $value = '';
            }
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $lookup[self::keySlug($key)] = e((string) $value);
        }

        // Normalisasi placeholder yang terpotong antar tag: PhpWord menulis
        // tiap run dalam <span> terpisah DAN meng-escape < >, mis.
        // "&lt;&lt;< /span><span>nama</span><span>&gt;&gt;" → gabungkan dulu
        // menjadi token utuh {{...}} (aman di-parse HTML) lalu isi data.
        $tags = '(?:\s*<\/?[a-zA-Z][^>]*>)*';
        $html = preg_replace('/&lt;&lt;' . $tags . '([^<>]*)' . $tags . '&gt;&gt;/s', '{{$1}}', $html) ?? $html;
        $html = preg_replace('/<<' . $tags . '([^<>]*)' . $tags . '>>/s', '{{$1}}', $html) ?? $html;
        $html = preg_replace('/\$\{' . $tags . '([^<>]*)' . $tags . '\}/', '{{$1}}', $html) ?? $html;

        $html = self::replaceTokens($html, $lookup);

        if ($catatan !== '') {
            $html = str_replace('</body>', '<div style="margin-top: 16pt; white-space: pre-wrap;">' . e($catatan) . '</div></body>', $html);
        }

        return $html;
    }

    /**
     * Tambah/ubah atribut HTML pada string atribut elemen yang sudah ada.
     * Atribut duplikat dihindari (DOMDocument mengabaikan duplikat).
     */
    private static function mergeHtmlAttr(string $attr, string $name, string $value): string
    {
        if (preg_match('/\b' . preg_quote($name, '/') . '="([^"]*)"/', $attr, $m)) {
            return str_replace($m[0], $name . '="' . $m[1] . ($m[1] !== '' ? '; ' : '') . $value . '"', $attr);
        }

        // Jangan trim — spasi pemisah atribut harus dipertahankan
        // (<tableclass=... → tag tidak dikenali, tabel kehilangan parent).
        return $attr . ' ' . $name . '="' . $value . '"';
    }

    /**
     * Baca info tabel dari XML docx (urutan = urutan <w:tbl> di document.xml):
     * apakah tabel ber-border (w:tblBorders bukan "nil") dan lebar aslinya
     * (w:tblW, twips → pt). PhpWord reader tidak mengekspos data ini.
     *
     * @return array<int, array{border: bool, width: int|null}>
     */
    private static function docxTableInfo(string $docxPath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($docxPath) !== true) {
            return [];
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false) {
            return [];
        }

        preg_match_all('/<w:tbl>.*?<\/w:tbl>/s', $xml, $m);

        $info = [];
        foreach ($m[0] as $tbl) {
            $border = false;
            if (preg_match('/<w:tblBorders>(.*?)<\/w:tblBorders>/s', $tbl, $bm)) {
                $border = preg_match('/w:val="(?!nil)[a-z]+"/', $bm[1]) === 1;
            }

            $width = null;
            if (preg_match('/<w:tblW w:w="(\d+)" w:type="dxa"/', $tbl, $wm)) {
                $width = (int) round($wm[1] / 20); // twips → pt
            }

            $info[] = ['border' => $border, 'width' => $width];
        }

        return $info;
    }

    /**
     * Render .docx terisi → PDF via Chrome headless + docx-preview (aset di
     * public/pdf-render). Ini mesin render yang sama dengan pratinjau di
     * aplikasi, sehingga hasil PDF identik dengan pratinjau.
     *
     * @return string|null Binary PDF, atau null bila Chrome/aset tidak tersedia.
     */
    private static function docxToPdfViaChrome(string $docxPath): ?string
    {
        try {
            $chrome = self::chromeBinary();
            if ($chrome === null) {
                Log::info('DocxPdfService: Chrome/Edge tidak ditemukan, lewati render docx-preview.');
                return null;
            }

            $assets = public_path('pdf-render');
            $template = $assets . '/render.html';
            if (!is_file($template)) {
                Log::warning('DocxPdfService: aset public/pdf-render/render.html tidak ditemukan.');
                return null;
            }

            $docx = (string) file_get_contents($docxPath);
            if ($docx === '') {
                return null;
            }

            $workDir = sys_get_temp_dir() . '/chromepdf_' . uniqid('', true);
            if (!mkdir($workDir, 0755, true)) {
                return null;
            }

            try {
                foreach (['render.html', 'jszip.min.js', 'docx-preview.min.js'] as $f) {
                    $src = $assets . '/' . $f;
                    if (is_file($src)) {
                        copy($src, $workDir . '/' . $f);
                    }
                }

                $html = str_replace('__DOCX_B64__', base64_encode($docx), (string) file_get_contents($workDir . '/render.html'));
                file_put_contents($workDir . '/render.html', $html);

                $pdfPath = $workDir . '/out.pdf';
                $url = 'file:///' . str_replace('\\', '/', $workDir . '/render.html');

                $cmd = escapeshellarg($chrome)
                    . ' --headless=new --disable-gpu --no-sandbox --no-first-run'
                    . ' --no-default-browser-check --disable-extensions'
                    . ' --disable-background-networking --no-pdf-header-footer'
                    . ' --virtual-time-budget=20000'
                    . ' --user-data-dir=' . escapeshellarg($workDir . '/prof')
                    . ' --print-to-pdf=' . escapeshellarg($pdfPath)
                    . ' ' . escapeshellarg($url) . ' 2>&1';

                exec($cmd, $output, $exitCode);

                if (!is_file($pdfPath) || filesize($pdfPath) === 0) {
                    Log::warning('DocxPdfService: Chrome gagal menghasilkan PDF.', [
                        'exitCode' => $exitCode,
                        'output' => implode("\n", array_slice($output, -15)),
                    ]);
                    return null;
                }

                return (string) file_get_contents($pdfPath);
            } finally {
                self::removeDir($workDir);
            }
        } catch (\Throwable $e) {
            Log::error('DocxPdfService: Chrome→PDF gagal: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Lokasi biner Chrome/Edge (Chromium). Menggunakan CHROME_PATH dari env
     * jika diisi, lalu menebak path umum di Windows/Linux.
     */
    private static function chromeBinary(): ?string
    {
        $configured = trim((string) (config('pdf.chrome_path') ?? ''));
        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }

        $candidates = [];
        if (PHP_OS_FAMILY === 'Windows') {
            foreach ([
                'C:/Program Files/Google/Chrome/Application/chrome.exe',
                'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
                'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
                'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
            ] as $candidate) {
                $candidates[] = $candidate;
            }
            $local = getenv('LOCALAPPDATA');
            if (is_string($local) && $local !== '') {
                $candidates[] = str_replace('\\', '/', $local) . '/Google/Chrome/Application/chrome.exe';
            }
        } else {
            foreach (['google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser'] as $candidate) {
                $path = trim((string) @shell_exec('command -v ' . escapeshellarg($candidate) . ' 2>/dev/null'));
                if ($path !== '') {
                    $candidates[] = $path;
                }
            }
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Deteksi apakah file .docx sedang dibuka oleh Microsoft Word
     * (lock file ~$<nama>.docx di folder yang sama).
     */
    private static function isFileLockedByWord(string $docxPath): bool
    {
        $dir = dirname($docxPath);
        $base = basename($docxPath);
        $lock = $dir . DIRECTORY_SEPARATOR . '~$' . ltrim($base, '~$');
        if (is_file($lock)) {
            Log::warning("DocxPdfService: file docx sedang terbuka di Word ({$lock}), lewati Word COM.");
            return true;
        }

        return false;
    }

    /**
     * Konversi .docx → PDF melalui Microsoft Word (COM automation).
     * Hanya tersedia di Windows dengan Word terpasang.
     *
     * @return string|null Binary PDF, atau null bila Word tidak tersedia/gagal.
     */
    private static function docxToPdfViaWord(string $docxPath): ?string
    {
        // Word COM automation hang tanpa batas waktu bila instance Word sedang
        // berjalan/sibuk — lewati bila Word sudah terbuka atau file terkunci.
        if (self::isWordRunning() || self::isFileLockedByWord($docxPath)) {
            return null;
        }

        $word = null;
        // Jangan menutup Word milik user bila COM menempel ke instance yang
        // sudah berjalan sebelumnya.
        $wasRunning = self::isWordRunning();
        try {
            $word = new \COM('Word.Application');
            $word->Visible = false;
            $word->DisplayAlerts = 0;

            $doc = $word->Documents->Open($docxPath, false, true);
            $pdfPath = sys_get_temp_dir() . '/docxpdf_' . uniqid('', true) . '.pdf';
            $doc->ExportAsFixedFormat($pdfPath, 17);
            $doc->Close(false);

            if (!is_file($pdfPath)) {
                return null;
            }

            $content = (string) file_get_contents($pdfPath);
            @unlink($pdfPath);

            return $content;
        } catch (\Throwable $e) {
            Log::error('DocxPdfService: Word COM gagal: ' . $e->getMessage());
            return null;
        } finally {
            if ($word !== null && !$wasRunning) {
                try {
                    $word->Quit();
                } catch (\Throwable $ignored) {
                }
            }
        }
    }

    /**
     * Periksa apakah proses Microsoft Word sudah berjalan.
     */
    private static function isWordRunning(): bool
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return false;
        }
        $p = proc_open('tasklist /FI "IMAGENAME eq WINWORD.EXE" /NH', [1 => ['pipe', 'w']], $pipes);
        if (!is_resource($p)) {
            return false;
        }
        $out = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        proc_close($p);
        return str_contains($out, 'WINWORD.EXE');
    }

    /**
     * Buat file .docx terisi data (tanpa konversi PDF). Selalu tersedia
     * selama template punya file Word -- tidak butuh aplikasi eksternal.
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
            || str_contains($paragraph, '&lt;')
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
