<?php
require '../core/init.php';

$user = Helper::requireAuth();
$company = Helper::currentCompany();

function pdfEscape($value)
{
    $value = str_replace('\\', '\\\\', $value);
    $value = str_replace('(', '\(', $value);
    $value = str_replace(')', '\)', $value);
    return str_replace(["\r", "\n"], [' ', ' '], $value);
}

function formatPdfDateValue($value, $withTime = false)
{
    if (empty($value)) {
        return 'Not available';
    }

    $timestamp = strtotime((string)$value);
    if ($timestamp === false) {
        return (string)$value;
    }

    return $withTime ? date('d.m.Y H:i:s', $timestamp) : date('d.m.Y', $timestamp);
}

function formatPdfMoney($value)
{
    return number_format((float)$value, 2, ',', '.') . ' EUR';
}

function normalizePdfText($value)
{
    $map = [
        'č' => 'c', 'ć' => 'c', 'ž' => 'z', 'š' => 's', 'đ' => 'd',
        'Č' => 'C', 'Ć' => 'C', 'Ž' => 'Z', 'Š' => 'S', 'Đ' => 'D',
    ];

    return strtr((string)$value, $map);
}

function addPdfLine(array &$lines, $label, $value = '')
{
    $text = $value === '' ? $label : sprintf('%s: %s', $label, $value);
    $lines[] = normalizePdfText($text);
}

function sanitizeFilenameSegment($value)
{
    $value = normalizePdfText((string)$value);
    $value = preg_replace('/[\\\\\\/:\*\?"<>\|]+/', '-', $value);
    $value = preg_replace('/\s+/', ' ', trim((string)$value));
    return $value !== '' ? $value : 'invoice';
}

function pdfText($x, $y, $text, $size = 10, $font = 'F1')
{
    return sprintf("0 0 0 rg\nBT /%s %s Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n", $font, number_format($size, 2, '.', ''), $x, $y, pdfEscape(normalizePdfText($text)));
}

function pdfLine($x1, $y1, $x2, $y2)
{
    return sprintf("%.2f %.2f m %.2f %.2f l S\n", $x1, $y1, $x2, $y2);
}

function pdfRect($x, $y, $w, $h, $fillRgb = null, $strokeRgb = [0, 0, 0])
{
    $commands = '';
    if (is_array($fillRgb)) {
        $commands .= sprintf("%.3f %.3f %.3f rg\n", $fillRgb[0], $fillRgb[1], $fillRgb[2]);
    }
    if (is_array($strokeRgb)) {
        $commands .= sprintf("%.3f %.3f %.3f RG\n", $strokeRgb[0], $strokeRgb[1], $strokeRgb[2]);
    }
    $commands .= sprintf("%.2f %.2f %.2f %.2f re %s\n", $x, $y, $w, $h, is_array($fillRgb) ? 'B' : 'S');
    return $commands;
}

function pdfFillRect($x, $y, $w, $h, $fillRgb = [0, 0, 0])
{
    return sprintf("%.3f %.3f %.3f rg\n%.2f %.2f %.2f %.2f re f\n", $fillRgb[0], $fillRgb[1], $fillRgb[2], $x, $y, $w, $h);
}

function code39Patterns()
{
    return [
        '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
        '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
        '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
        'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn',
        'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
        'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
        'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn',
        'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn', 'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw',
        'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
        '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '$' => 'nwnwnwnnn',
        '/' => 'nwnwnnnwn', '+' => 'nwnnnwnwn', '%' => 'nnnwnwnwn', '*' => 'nwnnwnwnn',
    ];
}

function sanitizeCode39Value($value)
{
    $value = strtoupper(normalizePdfText((string)$value));
    $value = preg_replace('/[^0-9A-Z\\.\\-\\ \\$\\/\\+\\%]/', ' ', $value);
    $value = preg_replace('/\s+/', ' ', trim((string)$value));
    return $value !== '' ? $value : 'INVOICE';
}

function code39ModuleCount($value)
{
    $patterns = code39Patterns();
    $encoded = '*' . sanitizeCode39Value($value) . '*';
    $modules = 0;
    $length = strlen($encoded);

    for ($i = 0; $i < $length; $i++) {
        $pattern = $patterns[$encoded[$i]] ?? $patterns['*'];
        for ($j = 0; $j < 9; $j++) {
            $modules += $pattern[$j] === 'w' ? 3 : 1;
        }
        if ($i < $length - 1) {
            $modules += 1;
        }
    }

    return $modules;
}

function pdfCode39($x, $y, $maxWidth, $height, $value)
{
    $patterns = code39Patterns();
    $encoded = '*' . sanitizeCode39Value($value) . '*';
    $moduleWidth = $maxWidth / max(code39ModuleCount($value), 1);
    $cursorX = $x;
    $commands = '';
    $length = strlen($encoded);

    for ($i = 0; $i < $length; $i++) {
        $pattern = $patterns[$encoded[$i]] ?? $patterns['*'];
        for ($j = 0; $j < 9; $j++) {
            $segmentWidth = ($pattern[$j] === 'w' ? 3 : 1) * $moduleWidth;
            if ($j % 2 === 0) {
                $commands .= pdfFillRect($cursorX, $y, $segmentWidth, $height);
            }
            $cursorX += $segmentWidth;
        }
        if ($i < $length - 1) {
            $cursorX += $moduleWidth;
        }
    }

    return $commands;
}

function buildStyledPdf($content)
{
    $objects = [];

    $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
    $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>";
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";
    $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $index => $object) {
        $offsets[] = strlen($pdf);
        $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }

    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

    return $pdf;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo 'Method not allowed.';
    exit;
}

$invoiceId = (int)($_GET['invoice_id'] ?? 0);
if (empty($company['id']) || $invoiceId <= 0) {
    http_response_code(400);
    echo 'Missing invoice.';
    exit;
}

$db = DB::getInstance();
$companyQuery = $db->query('SELECT * FROM companys WHERE id = ? LIMIT 1', [(int)($company['id'] ?? 0)]);
if (!$companyQuery->getError() && $companyQuery->getResults()) {
    $company = Helper::toArray($companyQuery->getFirst());
}

$invoiceQuery = $db->query('SELECT * FROM invoices WHERE id = ? AND company_id = ? LIMIT 1', [$invoiceId, $company['id']]);
if ($invoiceQuery->getError() || !$invoiceQuery->getResults()) {
    http_response_code(404);
    echo 'Invoice not found.';
    exit;
}

$invoice = Helper::toArray($invoiceQuery->getFirst());
$customer = [];
$businessUnit = [];
$articles = [];

if (!empty($invoice['customer_id'])) {
    $customerQuery = $db->query('SELECT * FROM customers WHERE id = ? AND company_id = ? LIMIT 1', [(int)$invoice['customer_id'], $company['id']]);
    if (!$customerQuery->getError() && $customerQuery->getResults()) {
        $customer = Helper::toArray($customerQuery->getFirst());
    }
}

if (!empty($invoice['bunit_id'])) {
    $bunitQuery = $db->query('SELECT * FROM business_units WHERE id = ? LIMIT 1', [(int)$invoice['bunit_id']]);
    if (!$bunitQuery->getError() && $bunitQuery->getResults()) {
        $businessUnit = Helper::toArray($bunitQuery->getFirst());
    }
}

$articlesQuery = $db->query('SELECT * FROM invoice_articles WHERE invoice_id = ? ORDER BY id ASC', [$invoiceId]);
if (!$articlesQuery->getError() && $articlesQuery->getResults()) {
    $articles = Helper::toArray($articlesQuery->getResults());
}

$fullNumber = (string)($invoice['number'] ?? '');
$bunitLabel = trim((string)($businessUnit['label'] ?? $businessUnit['code'] ?? ''));
$bunitLabel2 = trim((string)($businessUnit['label2'] ?? ''));
if ($bunitLabel !== '') {
    $fullNumber .= '-' . $bunitLabel;
}
if ($bunitLabel2 !== '') {
    $fullNumber .= '-' . $bunitLabel2;
}

$customerName = (string)($customer['full_name'] ?? 'Unknown customer');
$invoiceDate = formatPdfDateValue($invoice['insert_time'] ?? '');
$fileName = sprintf(
    '%s-%s-%s-%s.pdf',
    sanitizeFilenameSegment($fullNumber),
    sanitizeFilenameSegment($customerName),
    sanitizeFilenameSegment(number_format((float)($invoice['total_price'] ?? 0), 2, ',', '.')),
    sanitizeFilenameSegment($invoiceDate)
);

$companyDisplayName = trim((string)($company['full_name'] ?? $company['short_name'] ?? 'Company'));
$companyAddress = trim((string)($company['address'] ?? ''));
$companyPostalCode = trim((string)($company['postal_code'] ?? ''));
$companyCity = trim((string)($company['city'] ?? ''));
$companyCountry = trim((string)($company['country'] ?? ''));
$companyOib = trim((string)($company['oib'] ?? ''));
$companyIban = trim((string)($company['iban'] ?? ''));
$paymentLabel = ucfirst((string)($invoice['payment'] ?? ''));
$customerAddress = trim((string)($customer['address'] ?? ''));
$customerCityCountry = trim(trim((string)($customer['city'] ?? '')) . ' ' . trim((string)($customer['country'] ?? '')));
$customerOib = trim((string)($customer['oib'] ?? ''));
$customerEmail = trim((string)($customer['email'] ?? ''));
$remarkText = trim((string)($invoice['remark'] ?? ''));
$barcodeAmount = (int)round(((float)($invoice['total_price'] ?? 0)) * 100);
$barcodePayload = trim(sprintf(
    'HR%s %s %s',
    preg_replace('/\s+/', '', $companyIban),
    (string)$barcodeAmount,
    preg_replace('/\s+/', '', $fullNumber)
));

$content = '';
$content .= "0 0 0 RG\n0 0 0 rg\n1 w\n";
$content .= pdfText(40, 806, 'Invoice', 20, 'F2');
$content .= pdfText(355, 806, $fullNumber, 18, 'F2');
$content .= pdfLine(40, 798, 555, 798);

$content .= pdfRect(40, 678, 515, 100, [1, 1, 1]);
$content .= pdfLine(362, 686, 362, 770);

$sellerLines = array_values(array_filter([
    $companyDisplayName,
    $companyAddress,
    trim($companyPostalCode . ' ' . $companyCity . ' ' . $companyCountry),
    $companyOib !== '' ? 'OIB: ' . $companyOib : '',
    $companyIban !== '' ? 'IBAN: ' . $companyIban : '',
]));
$customerLines = array_values(array_filter([
    $customerName,
    $customerAddress,
    $customerCityCountry,
    $customerOib !== '' ? 'OIB: ' . $customerOib : '',
    $customerEmail,
]));

$sellerY = 744;
foreach ($sellerLines as $line) {
    $content .= pdfText(50, $sellerY, $line, 10);
    $sellerY -= 14;
}

$content .= pdfText(375, 752, 'Payment Barcode', 11, 'F2');
$content .= pdfCode39(375, 708, 160, 28, $barcodePayload);
$content .= pdfText(375, 694, 'IBAN: ' . $companyIban, 8.5);
$content .= pdfText(375, 682, 'Amount: ' . formatPdfMoney($invoice['total_price'] ?? 0), 8.5);

$content .= pdfRect(40, 560, 248, 110, [1, 1, 1]);
$content .= pdfRect(307, 560, 248, 110, [1, 1, 1]);
$content .= pdfText(50, 654, 'Customer', 11, 'F2');
$content .= pdfText(317, 654, 'Invoice Details', 11, 'F2');

$customerY = 638;
foreach ($customerLines as $line) {
    $content .= pdfText(50, $customerY, $line, 10);
    $customerY -= 14;
}

$content .= pdfText(317, 638, 'Invoice Date: ' . formatPdfDateValue($invoice['insert_time'] ?? ''), 9.5);
$content .= pdfText(317, 624, 'Due Date: ' . formatPdfDateValue($invoice['due_date'] ?? ''), 9.5);
$content .= pdfText(317, 610, 'Payment: ' . (string)($invoice['payment'] ?? ''), 9.5);
$content .= pdfText(317, 596, 'JIR: ' . (string)($invoice['jir'] ?? ''), 9.5);
$content .= pdfText(317, 582, 'Fiskalization Date: ' . formatPdfDateValue($invoice['fiskaled_at'] ?? '', true), 9.5);

$tableTop = 510;
$rowHeight = 20;
$columns = [
    ['x' => 40, 'w' => 190, 'label' => 'Label'],
    ['x' => 230, 'w' => 55, 'label' => 'Amount'],
    ['x' => 285, 'w' => 70, 'label' => 'Retail Price'],
    ['x' => 355, 'w' => 55, 'label' => 'VAT'],
    ['x' => 410, 'w' => 55, 'label' => 'Unit'],
    ['x' => 465, 'w' => 90, 'label' => 'Final Price'],
];

$content .= pdfRect(40, $tableTop, 515, $rowHeight, [1, 1, 1]);
foreach ($columns as $column) {
    $content .= pdfText($column['x'] + 6, $tableTop + 7, $column['label'], 9.5, 'F2');
}

$currentY = $tableTop - $rowHeight;
foreach ($articles as $article) {
    $content .= pdfRect(40, $currentY, 515, $rowHeight, null);
    $content .= pdfText(46, $currentY + 7, trim((string)($article['label'] ?? 'Article')), 9.5);
    $content .= pdfText(236, $currentY + 7, (string)($article['amount'] ?? '0'), 9.5);
    $content .= pdfText(291, $currentY + 7, formatPdfMoney($article['retail_price'] ?? 0), 9.5);
    $content .= pdfText(361, $currentY + 7, (string)($article['vat_rate'] ?? '0') . '%', 9.5);
    $content .= pdfText(416, $currentY + 7, (string)($article['unit'] ?? ''), 9.5);
    $content .= pdfText(471, $currentY + 7, formatPdfMoney($article['final_price'] ?? 0), 9.5);
    $currentY -= $rowHeight;
}

if (empty($articles)) {
    $content .= pdfRect(40, $currentY, 515, $rowHeight, null);
    $content .= pdfText(46, $currentY + 7, 'No article lines found for this invoice.', 9.5);
    $currentY -= $rowHeight;
}

$totalsBoxY = max($currentY - 80, 140);
$content .= pdfRect(335, $totalsBoxY, 220, 74, [1, 1, 1]);
$content .= pdfText(348, $totalsBoxY + 54, 'Netto Price', 10, 'F2');
$content .= pdfText(470, $totalsBoxY + 54, formatPdfMoney($invoice['netto_price'] ?? 0), 10);
$content .= pdfText(348, $totalsBoxY + 36, 'PDV', 10, 'F2');
$content .= pdfText(470, $totalsBoxY + 36, formatPdfMoney($invoice['vat_amount'] ?? 0), 10);
$content .= pdfLine(345, $totalsBoxY + 28, 545, $totalsBoxY + 28);
$content .= pdfText(348, $totalsBoxY + 12, 'Final Price', 12, 'F2');
$content .= pdfText(455, $totalsBoxY + 12, formatPdfMoney($invoice['total_price'] ?? 0), 12);

if ($remarkText !== '') {
    $remarkY = $totalsBoxY - 60;
    $content .= pdfRect(40, $remarkY, 515, 46, [1, 1, 1]);
    $content .= pdfText(50, $remarkY + 30, 'Remark', 11, 'F2');
    $content .= pdfText(50, $remarkY + 14, $remarkText, 9.5);
}
$pdfContent = buildStyledPdf($content);

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $fileName) . '"');
header('Content-Length: ' . strlen($pdfContent));
echo $pdfContent;
exit;
