<?php
require '../core/init.php';

header("Content-Type: application/json; charset=UTF-8");

require_once '../classes/Fiskalizacija.php';
require_once '../classes/Request.php';
require_once '../classes/BillNumber.php';
require_once '../classes/TaxRate.php';
require_once '../classes/Bill.php';
require_once '../classes/BillRequest.php';

use Fiskalizacija\Fiskalizacija;
use Fiskalizacija\Bill\Bill;
use Fiskalizacija\Bill\BillNumber;
use Fiskalizacija\Bill\TaxRate;
use Fiskalizacija\Bill\BillRequest;

function findXmlNodeValue(DOMXPath $xpath, $localName) {
    $nodes = $xpath->query("//*[local-name()='{$localName}']");

    if ($nodes instanceof DOMNodeList && $nodes->length > 0) {
        return trim((string)$nodes->item(0)->textContent);
    }

    return null;
}

function isValidOib($value) {
    $oib = preg_replace('/\D+/', '', (string)$value);

    if (!preg_match('/^\d{11}$/', $oib)) {
        return false;
    }

    $control = 10;

    for ($i = 0; $i < 10; $i++) {
        $control = ($control + (int)$oib[$i]) % 10;

        if ($control === 0) {
            $control = 10;
        }

        $control = ($control * 2) % 11;
    }

    $checkDigit = 11 - $control;

    if ($checkDigit === 10) {
        $checkDigit = 0;
    } elseif ($checkDigit === 11) {
        $checkDigit = 0;
    }

    return $checkDigit === (int)$oib[10];
}

try {
    $user = Helper::requireAuth();
    $company = Helper::currentCompany();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Metoda nije dopustena.']);
        exit;
    }

    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    $invoiceId = (int)($data['invoice_id'] ?? 0);

    if (empty($company['id']) || $invoiceId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nedostaju podaci za fiskalizaciju.']);
        exit;
    }

    $db = DB::getInstance();
    $conn = $db->getConn();

    $invoiceQuery = $db->query('SELECT * FROM invoices WHERE id = ? AND company_id = ? LIMIT 1', [$invoiceId, $company['id']]);
    if ($invoiceQuery->getError() || !$invoiceQuery->getResults()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Racun nije pronadjen.']);
        exit;
    }

    $invoice = Helper::toArray($invoiceQuery->getFirst());

    if (!empty($invoice['jir']) && !empty($invoice['fiskaled_at'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Racun je vec fiskaliziran.']);
        exit;
    }

    $bunitQuery = $db->query('SELECT * FROM business_units WHERE id = ? LIMIT 1', [$invoice['bunit_id'] ?? 0]);
    $bunit = (!$bunitQuery->getError() && $bunitQuery->getResults()) ? Helper::toArray($bunitQuery->getFirst()) : [];

    $customerQuery = $db->query('SELECT * FROM customers WHERE id = ? LIMIT 1', [$invoice['customer_id'] ?? 0]);
    $customer = (!$customerQuery->getError() && $customerQuery->getResults()) ? Helper::toArray($customerQuery->getFirst()) : [];

    $articlesQuery = $db->query('SELECT * FROM invoice_articles WHERE invoice_id = ? ORDER BY id ASC', [$invoiceId]);
    $articles = (!$articlesQuery->getError() && $articlesQuery->getResults()) ? Helper::toArray($articlesQuery->getResults()) : [];

    if (empty($articles)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Racun nema stavki za fiskalizaciju.']);
        exit;
    }

    $companyOib = trim((string)($company['oib'] ?? ''));
    $operatorOib = trim((string)($user['oib'] ?? $companyOib));
    $businessAreaLabel = trim((string)($bunit['label'] ?? $bunit['code'] ?? '1'));
    $deviceLabel = trim((string)($bunit['label2'] ?? '1'));
    $invoiceNumber = trim((string)($invoice['number'] ?? ''));
    $invoiceDateTime = date('d.m.Y\TH:i:s', strtotime((string)($invoice['insert_time'] ?? 'now')));
    $sequenceLabel = trim((string)($bunit['ozn_slijed'] ?? 'P'));
    $paymentMap = [
        'cash' => 'G',
        'card' => 'K',
        'transaction' => 'T',
    ];
    $paymentCode = $paymentMap[(string)($invoice['payment'] ?? '')] ?? 'T';
    $customerOib = trim((string)($customer['oib'] ?? ''));
    $isLegalCustomer = (string)($customer['legal'] ?? '0') === '1';
    $shouldValidateCustomerOib = $isLegalCustomer
        && strtotime((string)($invoice['insert_time'] ?? 'now')) >= strtotime('2026-01-01 00:00:00')
        && $paymentCode !== 'T'
        && $customerOib !== '';

    if ($companyOib === '' || $invoiceNumber === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nedostaju OIB tvrtke ili broj racuna.']);
        exit;
    }

    if ($shouldValidateCustomerOib && !isValidOib($customerOib)) {
        $invoiceErrorStmt = $conn->prepare('UPDATE invoices SET fina_error = ? WHERE id = ? AND company_id = ?');
        $invoiceErrorStmt->execute(['OIB kupca nije formalno ispravan.', $invoiceId, $company['id']]);

        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'OIB kupca nije formalno ispravan. Ispravite kupca prije fiskalizacije.'
        ]);
        exit;
    }

    $certificateDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'certificates' . DIRECTORY_SEPARATOR;
    $certificateCandidates = [
        $certificateDirectory . $companyOib . '.pfx',
        $certificateDirectory . $companyOib . '.p12',
    ];
    $certificatePath = null;
    $certificatePassword = trim((string)($company['p12_password'] ?? ''));
    $demoMode = true;

    foreach ($certificateCandidates as $candidatePath) {
        if (is_file($candidatePath)) {
            $certificatePath = $candidatePath;
            break;
        }
    }

    if ($certificatePath === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Certifikat za tvrtku nije pronadjen u certificates folderu.']);
        exit;
    }

    if ($certificatePassword === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nedostaje p12_password za tvrtku.']);
        exit;
    }

    $fiskalizacija = new Fiskalizacija($certificatePath, $certificatePassword, 'TLS', $demoMode);
    $privateKey = $fiskalizacija->getPrivateKey();

    $bill = new Bill();
    $bill->setOib($companyOib);
    $bill->setHavePDV(!empty($company['pdv']));
    $bill->setDateTime($invoiceDateTime);
    $bill->setNoteOfOrder($sequenceLabel);
    $bill->setBillNumber(new BillNumber($invoiceNumber, $businessAreaLabel, $deviceLabel));
    $bill->setTotalValue((float)($invoice['total_price'] ?? 0));
    $bill->setTypeOfPlacanje($paymentCode);
    $bill->setOibOperative($operatorOib);

    if ($isLegalCustomer && $customerOib !== '') {
        $bill->setIdKupca($customerOib);
        $bill->setOznakaIdKupca('OIB');
    }

    $pdvRates = [];
    $taxFreeAmount = 0.0;
    foreach ($articles as $article) {
        $amount = (float)($article['amount'] ?? 0);
        $retailPrice = (float)($article['retail_price'] ?? 0);
        $finalPrice = (float)($article['final_price'] ?? 0);
        $tipPrice = (float)($article['tip_price'] ?? 0);
        $vatRate = (float)($article['vat_rate'] ?? 0);
        $discount = (float)($article['discount'] ?? 0);
        $basePrice = $retailPrice * max($amount, 0);
        $discountAmount = $basePrice * ($discount / 100);
        $taxableBase = max(0, $basePrice - $discountAmount);
        $taxableTotal = max(0, $finalPrice - $tipPrice);

        if ($vatRate > 0) {
            $baseValue = $taxableBase;
            $vatValue = max(0, $taxableTotal - $taxableBase);
            $rateKey = number_format($vatRate, 2, '.', '');

            if (!isset($pdvRates[$rateKey])) {
                $pdvRates[$rateKey] = ['rate' => $vatRate, 'base' => 0.0, 'vat' => 0.0];
            }

            $pdvRates[$rateKey]['base'] += $baseValue;
            $pdvRates[$rateKey]['vat'] += $vatValue;
        } else {
            $taxFreeAmount += $taxableBase;
        }
    }

    if (!empty($pdvRates)) {
        $taxRateObjects = [];
        foreach ($pdvRates as $pdvRate) {
            $taxRateObjects[] = new TaxRate($pdvRate['rate'], $pdvRate['base'], $pdvRate['vat'], null);
        }
        $bill->setListPDV($taxRateObjects);
    }

    if ($taxFreeAmount > 0) {
        $bill->setTaxFreeValue($taxFreeAmount);
    }

    $zki = $bill->securityCode(
        $privateKey,
        $companyOib,
        str_replace('T', ' ', $invoiceDateTime),
        $invoiceNumber,
        $businessAreaLabel,
        $deviceLabel,
        number_format((float)($invoice['total_price'] ?? 0), 2, '.', '')
    );
    $bill->setSecurityCode($zki);

    $billRequest = new BillRequest($bill);
    $requestXml = $billRequest->toXML();
    $signedXml = $fiskalizacija->signXML($requestXml);
    $responseXml = $fiskalizacija->sendSoap($signedXml);

    $responseDom = new DOMDocument();
    if (!@$responseDom->loadXML($responseXml)) {
        throw new RuntimeException('Odgovor fiskalizacije nije ispravan XML.');
    }

    $responseXPath = new DOMXPath($responseDom);
    $jir = findXmlNodeValue($responseXPath, 'Jir');
    $errorCode = findXmlNodeValue($responseXPath, 'SifraGreske');
    $errorMessage = findXmlNodeValue($responseXPath, 'PorukaGreske');

    if ($errorCode !== null || $errorMessage !== null) {
        $finaError = $errorMessage !== null && $errorMessage !== '' ? $errorMessage : implode(' - ', array_filter([$errorCode, $errorMessage]));
        $invoiceErrorStmt = $conn->prepare('UPDATE invoices SET fina_error = ? WHERE id = ? AND company_id = ?');
        $invoiceErrorStmt->execute([$finaError !== '' ? $finaError : null, $invoiceId, $company['id']]);

        $messageParts = array_filter([$errorCode, $errorMessage]);
        throw new RuntimeException('Fiskalizacija nije uspjela: ' . implode(' - ', $messageParts));
    }

    if ($jir === null || $jir === '') {
        throw new RuntimeException('Fiskalizacija nije vratila JIR.');
    }

    $fiskaledAt = date('Y-m-d H:i:s');
    $zkiForDatabase = trim((string)$zki);
    $jirForDatabase = trim((string)$jir);

    $conn->beginTransaction();

    $finaXmlCheckStmt = $conn->prepare('SELECT id FROM fina_xml WHERE invoice_id = ? LIMIT 1');
    $finaXmlCheckStmt->execute([$invoiceId]);
    $existingFinaXml = $finaXmlCheckStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingFinaXml) {
        $finaXmlStmt = $conn->prepare('UPDATE fina_xml SET request = ?, response = ? WHERE invoice_id = ?');
        $finaXmlStmt->execute([$signedXml, $responseXml, $invoiceId]);
    } else {
        $finaXmlStmt = $conn->prepare('INSERT INTO fina_xml (invoice_id, request, response) VALUES (?, ?, ?)');
        $finaXmlStmt->execute([$invoiceId, $signedXml, $responseXml]);
    }

    $invoiceUpdateStmt = $conn->prepare('UPDATE invoices SET jir = ?, zki = ?, fiskaled_at = ?, fina_error = NULL WHERE id = ? AND company_id = ?');
    $invoiceUpdateStmt->execute([
        $jirForDatabase !== '' ? $jirForDatabase : null,
        $zkiForDatabase !== '' ? $zkiForDatabase : null,
        $fiskaledAt,
        $invoiceId,
        $company['id'],
    ]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Racun je uspjesno fiskaliziran.',
        'jir' => $jir,
        'fiskaled_at' => $fiskaledAt,
    ]);
    exit;
} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof PDO && $conn->inTransaction()) {
        $conn->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage() !== '' ? $e->getMessage() : 'Generiranje XML zahtjeva nije uspjelo.'
    ]);
    exit;
}
