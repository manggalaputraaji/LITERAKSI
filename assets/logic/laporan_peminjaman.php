<?php
// Mengambil data peminjaman terfilter dan membuat file ekspor XLSX, DOCX, atau PDF.
$adminPageTab = 'loans';
require __DIR__ . '/adminlogic.php';

function report_escape_xml(string $value): string
{
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? '';
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function report_export_rows(PDO $pdo, ?string $start, ?string $end, string $status, string $search): array
{
    $sql = 'SELECT p.id_peminjaman, b.judul, p.NIS,
            COALESCE(u.nama, p.NIS) AS nama_peminjam,
            COALESCE(a.nama, p.id_petugas) AS nama_petugas,
            p.tgl_peminjam, p.tgl_jatuh_tempo, d.tgl_kembali,
            CASE
                WHEN p.status = \'menunggu_pengambilan\' THEN \'Menunggu pengambilan\'
                WHEN p.status = \'dibatalkan\' THEN \'Dibatalkan\'
                WHEN d.tgl_kembali IS NOT NULL THEN \'Dikembalikan\'
                WHEN p.status = \'dipinjam\' AND p.tgl_jatuh_tempo < CURDATE() THEN \'Terlambat\'
                ELSE \'Sedang dipinjam\'
            END AS status
        FROM detail_peminjaman d
        JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
        LEFT JOIN buku b ON b.id_buku = d.id_buku
        LEFT JOIN `user` u ON u.NIS = p.NIS
        LEFT JOIN `admin` a ON a.id_petugas = p.id_petugas';
    $conditions = [];
    $params = [];
    if ($start !== null) {
        $conditions[] = 'p.tgl_peminjam >= ?';
        $params[] = $start;
    }
    if ($end !== null) {
        $conditions[] = 'p.tgl_peminjam <= ?';
        $params[] = $end;
    }
    if ($status === 'dipinjam') {
        $conditions[] = 'p.status = \'dipinjam\' AND d.tgl_kembali IS NULL';
    } elseif ($status === 'terlambat') {
        $conditions[] = 'p.status = \'dipinjam\' AND d.tgl_kembali IS NULL AND p.tgl_jatuh_tempo < CURDATE()';
    } elseif ($status === 'menunggu') {
        $conditions[] = 'p.status = \'dipinjam\' AND d.tgl_kembali IS NULL AND d.tgl_pengajuan_kembali IS NOT NULL';
    } elseif ($status === 'pengambilan') {
        $conditions[] = 'p.status = \'menunggu_pengambilan\'';
    } elseif ($status === 'kembali') {
        $conditions[] = 'd.tgl_kembali IS NOT NULL';
    }
    if ($search !== '') {
        $conditions[] = '(p.id_peminjaman LIKE ? OR p.NIS LIKE ? OR u.nama LIKE ? OR b.judul LIKE ?)';
        $needle = '%' . $search . '%';
        array_push($params, $needle, $needle, $needle, $needle);
    }
    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY p.tgl_peminjam DESC, p.id_peminjaman, b.judul';
    $query = $pdo->prepare($sql);
    $query->execute($params);
    return $query->fetchAll();
}

function report_make_xlsx(string $title, array $rows): string
{
    $headings = ['ID Peminjaman', 'Buku', 'NIS', 'Nama Peminjam', 'Dilayani Petugas', 'Tanggal Pinjam', 'Jatuh Tempo', 'Tanggal Kembali', 'Status'];
    $allRows = [$headings];
    foreach ($rows as $row) {
        $allRows[] = [
            (string) $row['id_peminjaman'], (string) ($row['judul'] ?? 'Buku tidak ditemukan'),
            (string) $row['NIS'], (string) $row['nama_peminjam'], (string) $row['nama_petugas'],
            (string) $row['tgl_peminjam'], (string) $row['tgl_jatuh_tempo'],
            (string) ($row['tgl_kembali'] ?? '-'), (string) $row['status'],
        ];
    }
    $col = static function (int $number): string {
        $letters = '';
        while ($number > 0) {
            $number--;
            $letters = chr(65 + $number % 26) . $letters;
            $number = intdiv($number, 26);
        }
        return $letters;
    };
    $sheetRows = '';
    foreach ($allRows as $rowIndex => $cells) {
        $cellXml = '';
        foreach ($cells as $columnIndex => $value) {
            $cellRef = $col($columnIndex + 1) . ($rowIndex + 1);
            $style = $rowIndex === 0 ? ' s="1"' : '';
            $cellXml .= '<c r="' . $cellRef . '" t="inlineStr"' . $style . '><is><t xml:space="preserve">'
                . report_escape_xml($value) . '</t></is></c>';
        }
        $sheetRows .= '<row r="' . ($rowIndex + 1) . '">' . $cellXml . '</row>';
    }
    $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"/></sheetViews>'
        . '<cols><col min="1" max="1" width="18" customWidth="1"/><col min="2" max="2" width="36" customWidth="1"/>'
        . '<col min="3" max="3" width="16" customWidth="1"/><col min="4" max="5" width="25" customWidth="1"/>'
        . '<col min="6" max="8" width="18" customWidth="1"/><col min="9" max="9" width="20" customWidth="1"/></cols>'
        . '<sheetData>' . $sheetRows . '</sheetData><autoFilter ref="A1:I' . count($allRows) . '"/></worksheet>';
    $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Laporan Peminjaman" sheetId="1" r:id="rId1"/></sheets></workbook>';
    $tmp = tempnam(sys_get_temp_dir(), 'literaksi-xlsx-');
    $zip = new ZipArchive();
    if ($tmp === false || $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Tidak dapat menyiapkan file XLSX.');
    }
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml', $workbook);
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
    $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF255B48"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs></styleSheet>');
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
    $zip->close();
    return $tmp;
}

function report_make_docx(string $title, array $rows): string
{
    $headers = ['ID', 'Buku', 'NIS', 'Peminjam', 'Petugas', 'Tanggal Pinjam', 'Jatuh Tempo', 'Kembali', 'Status'];
    $matrix = [$headers];
    foreach ($rows as $row) {
        $matrix[] = [(string) $row['id_peminjaman'], (string) ($row['judul'] ?? 'Buku tidak ditemukan'),
            (string) $row['NIS'], (string) $row['nama_peminjam'], (string) $row['nama_petugas'],
            (string) $row['tgl_peminjam'], (string) $row['tgl_jatuh_tempo'],
            (string) ($row['tgl_kembali'] ?? '-'), (string) $row['status']];
    }
    $tableRows = '';
    foreach ($matrix as $rowIndex => $cells) {
        $tableCells = '';
        foreach ($cells as $value) {
            $tableCells .= '<w:tc><w:tcPr><w:tcW w:w="1500" w:type="dxa"/></w:tcPr><w:p><w:r><w:rPr>'
                . ($rowIndex === 0 ? '<w:b/>' : '') . '</w:rPr><w:t xml:space="preserve">'
                . report_escape_xml((string) $value) . '</w:t></w:r></w:p></w:tc>';
        }
        $tableRows .= '<w:tr>' . $tableCells . '</w:tr>';
    }
    $doc = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
        . '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="32"/></w:rPr><w:t>' . report_escape_xml($title) . '</w:t></w:r></w:p>'
        . '<w:p><w:r><w:t>Jumlah catatan: ' . count($rows) . '</w:t></w:r></w:p>'
        . '<w:tbl><w:tblPr><w:tblStyle w:val="TableGrid"/><w:tblW w:w="0" w:type="auto"/></w:tblPr>' . $tableRows . '</w:tbl>'
        . '<w:sectPr><w:pgSz w:w="15840" w:h="12240" w:orient="landscape"/><w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720"/></w:sectPr></w:body></w:document>';
    $tmp = tempnam(sys_get_temp_dir(), 'literaksi-docx-');
    $zip = new ZipArchive();
    if ($tmp === false || $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Tidak dapat menyiapkan file DOCX.');
    }
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
    $zip->addFromString('word/document.xml', $doc);
    $zip->close();
    return $tmp;
}

function report_make_pdf(string $title, array $rows): string
{
    $headers = ['ID', 'Buku', 'NIS', 'Peminjam', 'Petugas', 'Pinjam', 'Tempo', 'Kembali', 'Status'];
    $matrix = [$headers];
    foreach ($rows as $row) {
        $matrix[] = [(string) $row['id_peminjaman'], (string) ($row['judul'] ?? 'Buku tidak ditemukan'),
            (string) $row['NIS'], (string) $row['nama_peminjam'], (string) $row['nama_petugas'],
            (string) $row['tgl_peminjam'], (string) $row['tgl_jatuh_tempo'],
            (string) ($row['tgl_kembali'] ?? '-'), (string) $row['status']];
    }
    $widths = [12, 27, 13, 24, 22, 11, 11, 11, 19];
    $formatted = [];
    foreach ($matrix as $rowIndex => $row) {
        $line = '';
        foreach ($row as $index => $value) {
            $value = preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';
            $value = function_exists('mb_strimwidth') ? mb_strimwidth($value, 0, $widths[$index], '…', 'UTF-8') : substr($value, 0, $widths[$index]);
            $line .= str_pad($value, $widths[$index]) . ' ';
        }
        $formatted[] = $line;
    }
    $chunks = array_chunk($formatted, 43);
    if (!$chunks) { $chunks = [[]]; }
    $objects = [1 => '<< /Type /Catalog /Pages 2 0 R >>', 3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>'];
    $pageRefs = [];
    foreach ($chunks as $pageIndex => $lines) {
        $pageId = 4 + $pageIndex * 2;
        $streamId = $pageId + 1;
        $pageRefs[] = $pageId . ' 0 R';
        $content = "BT\n/F1 8 Tf\n30 565 Td\n(" . report_pdf_text($title) . ") Tj\n0 -14 Td\n(" . report_pdf_text('Jumlah catatan: ' . count($rows)) . ") Tj\n0 -16 Td\n";
        foreach ($lines as $line) {
            $content .= '(' . report_pdf_text($line) . ") Tj\n0 -11 Td\n";
        }
        $content .= 'ET';
        $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R >> >> /Contents ' . $streamId . ' 0 R >>';
        $objects[$streamId] = '<< /Length ' . strlen($content) . ">>\nstream\n" . $content . "\nendstream";
    }
    $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageRefs) . '] /Count ' . count($pageRefs) . ' >>';
    ksort($objects);
    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = [0];
    foreach ($objects as $id => $object) {
        $offsets[$id] = strlen($pdf);
        $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
    }
    $xref = strlen($pdf);
    $size = max(array_keys($objects)) + 1;
    $pdf .= "xref\n0 {$size}\n0000000000 65535 f \n";
    for ($id = 1; $id < $size; $id++) {
        $pdf .= sprintf('%010d 00000 n ', $offsets[$id] ?? 0) . "\n";
    }
    $pdf .= "trailer\n<< /Size {$size} /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    $tmp = tempnam(sys_get_temp_dir(), 'literaksi-pdf-');
    if ($tmp === false || file_put_contents($tmp, $pdf) === false) {
        throw new RuntimeException('Tidak dapat menyiapkan file PDF.');
    }
    return $tmp;
}

function report_pdf_text(string $text): string
{
    $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
    $encoded = $encoded === false ? $text : $encoded;
    return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ''], $encoded);
}

$format = $_GET['format'] ?? '';
if (!is_string($format) || !in_array($format, ['xlsx', 'pdf', 'docx'], true)) {
    if ($format === '') {
        header('Location: ' . app_base_url() . 'admin.php?page=peminjaman');
        exit;
    }
    http_response_code(400);
    exit('Format ekspor tidak didukung.');
}

$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$status = $_GET['status'] ?? 'semua';
$search = $_GET['q'] ?? '';
if (!is_string($status) || !in_array($status, ['semua', 'dipinjam', 'terlambat', 'menunggu', 'pengambilan', 'kembali'], true)
    || !is_string($search)) {
    http_response_code(400);
    exit('Filter ekspor tidak valid.');
}
$parseDate = static function ($value): ?DateTimeImmutable {
    if (!is_string($value) || $value === '') {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $date : null;
};
$startDate = $parseDate($dateFrom);
$endDate = $parseDate($dateTo);
if (($dateFrom !== '' && $startDate === null) || ($dateTo !== '' && $endDate === null)
    || ($startDate !== null && $endDate !== null && $startDate > $endDate)) {
    http_response_code(400);
    exit('Rentang tanggal ekspor tidak valid.');
}
$start = $startDate ? $startDate->format('Y-m-d') : null;
$end = $endDate ? $endDate->format('Y-m-d') : null;
$dateLabel = $startDate && $endDate
    ? $startDate->format('Y-m-d') . '-sampai-' . $endDate->format('Y-m-d')
    : ($startDate ? 'mulai-' . $startDate->format('Y-m-d')
        : ($endDate ? 'sampai-' . $endDate->format('Y-m-d') : 'semua-tanggal'));
$reportTitle = 'Laporan Peminjaman - ' . str_replace('-', ' ', $dateLabel);
$reportRows = report_export_rows($pdo, $start, $end, $status, $search);
$tmp = match ($format) {
    'xlsx' => report_make_xlsx($reportTitle, $reportRows),
    'pdf' => report_make_pdf($reportTitle, $reportRows),
    'docx' => report_make_docx($reportTitle, $reportRows),
};
$mime = [
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'pdf' => 'application/pdf',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
][$format];
$filename = 'laporan-peminjaman-' . $dateLabel . '.' . $format;
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tmp));
readfile($tmp);
unlink($tmp);
exit;
