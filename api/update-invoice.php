<?php
require '../core/init.php';

header("Content-Type: application/json; charset=UTF-8");

try {
    $user = Helper::requireAuth();
    $company = Helper::currentCompany();
    $bunitId = Helper::currentBusinessUnitId();

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "Metoda nije dopustena."
        ]);
        exit;
    }

    if (empty($company['id']) || empty($user['id']) || empty($bunitId)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Nedostaju podaci o korisniku, tvrtki ili poslovnoj jedinici."
        ]);
        exit;
    }

    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true);

    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Neispravan JSON format."
        ]);
        exit;
    }

    $invoiceId = (int)($data['invoice_id'] ?? 0);
    $payment = (string)($data['payment'] ?? '');
    $invoiceDate = trim((string)($data['invoice_date'] ?? ''));
    $dueDate = trim((string)($data['due_date'] ?? ''));
    $remark = trim((string)($data['remark'] ?? ''));
    $customerPayload = $data['customer'] ?? [];
    $articlesPayload = $data['articles'] ?? [];
    $paymentMap = [
        'cash_payment' => 'cash',
        'card_payment' => 'card',
        'transactional_payment' => 'transaction',
    ];

    if ($invoiceId <= 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Racun nije odabran za izmjenu."
        ]);
        exit;
    }

    if (empty($articlesPayload) || !is_array($articlesPayload)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Odaberite barem jedan artikl."
        ]);
        exit;
    }

    if ($invoiceDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $invoiceDate)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Datum racuna je obavezan."
        ]);
        exit;
    }

    if ($dueDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Datum dospijeca nije ispravan."
        ]);
        exit;
    }

    $customerFullName = trim((string)($customerPayload['full_name'] ?? ''));

    $db = DB::getInstance();
    $conn = $db->getConn();

    $invoiceCheckStmt = $conn->prepare('SELECT id, number, jir, fiskaled_at, DATE(insert_time) AS invoice_date FROM invoices WHERE id = ? AND company_id = ? LIMIT 1');
    $invoiceCheckStmt->execute([$invoiceId, $company['id']]);
    $existingInvoice = $invoiceCheckStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingInvoice) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Racun nije pronadjen."
        ]);
        exit;
    }

    if (!empty($existingInvoice['jir']) && !empty($existingInvoice['fiskaled_at'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Fiskalizirani racun se vise ne moze mijenjati."
        ]);
        exit;
    }

    $currentInvoiceDate = (string)($existingInvoice['invoice_date'] ?? '');
    if ($invoiceDate !== $currentInvoiceDate) {
        $lastIssuedInvoiceDateStmt = $conn->prepare('SELECT DATE(insert_time) AS invoice_date FROM invoices WHERE company_id = ? AND bunit_id = ? AND id <> ? ORDER BY insert_time DESC, id DESC LIMIT 1');
        $lastIssuedInvoiceDateStmt->execute([$company['id'], $bunitId, $invoiceId]);
        $lastIssuedInvoiceDateRow = $lastIssuedInvoiceDateStmt->fetch(PDO::FETCH_ASSOC);
        $lastIssuedInvoiceDate = (string)($lastIssuedInvoiceDateRow['invoice_date'] ?? '');

        if ($lastIssuedInvoiceDate !== '' && $invoiceDate < $lastIssuedInvoiceDate) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Datum racuna ne moze biti stariji od zadnjeg izdanog racuna."
            ]);
            exit;
        }
    }

    $allowedPayments = ['cash_payment'];
    $bunitOptionsQuery = $db->query('SELECT * FROM bunit_options WHERE bunit_id = ?', [$bunitId], 1);
    if (!$bunitOptionsQuery->getError() && $bunitOptionsQuery->getResults()) {
        $bunitOptions = Helper::toArray($bunitOptionsQuery->getFirst());
        if (!empty($bunitOptions['card_payment'])) {
            $allowedPayments[] = 'card_payment';
        }
        if (!empty($bunitOptions['transactional_payment'])) {
            $allowedPayments[] = 'transactional_payment';
        }
    }

    if (!in_array($payment, $allowedPayments, true)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Odabrani nacin placanja nije dozvoljen."
        ]);
        exit;
    }

    $invoicePaymentValue = $paymentMap[$payment] ?? null;
    if ($invoicePaymentValue === null) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Odabrani nacin placanja nije valjan."
        ]);
        exit;
    }

    $customerData = [
        'full_name' => $customerFullName,
        'legal' => (string)($customerPayload['legal'] ?? '') === '2' || !empty($customerPayload['legal_government']) ? 2 : (!empty($customerPayload['legal']) ? 1 : 0),
        'address' => trim((string)($customerPayload['address'] ?? '')),
        'city' => trim((string)($customerPayload['city'] ?? '')),
        'country' => trim((string)($customerPayload['country'] ?? '')),
        'oib' => trim((string)($customerPayload['oib'] ?? '')),
        'email' => trim((string)($customerPayload['email'] ?? '')),
    ];

    $articleIds = [];
    foreach ($articlesPayload as $article) {
        $articleIds[] = (int)($article['id'] ?? 0);
    }
    $articleIds = array_values(array_unique(array_filter($articleIds)));

    if (empty($articleIds)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Odaberite barem jedan artikl."
        ]);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($articleIds), '?'));
    $params = array_merge([$company['id']], $articleIds);
    $priceListQuery = $db->query("SELECT * FROM price_list WHERE company_id = ? AND id IN ({$placeholders})", $params);

    if ($priceListQuery->getError() || !$priceListQuery->getResults()) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Artikli nisu pronadjeni."
        ]);
        exit;
    }

    $priceListMap = [];
    foreach (Helper::toArray($priceListQuery->getResults()) as $priceListItem) {
        $priceListMap[(int)$priceListItem['id']] = $priceListItem;
    }

    $preparedArticles = [];
    $invoiceNettoPrice = 0.0;
    $invoiceVatAmount = 0.0;
    $invoiceTotalPrice = 0.0;
    foreach ($articlesPayload as $article) {
        $articleId = (int)($article['id'] ?? 0);
        if (!isset($priceListMap[$articleId])) {
            continue;
        }

        $priceListItem = $priceListMap[$articleId];
        $amount = (int)($article['amount'] ?? 0);
        $discount = (int)($article['discount'] ?? 0);
        $unit = (string)($article['unit'] ?? 'kom');
        $tipPrice = (int)($article['tip_price'] ?? 0);
        $retailPrice = (float)($priceListItem['retail_price'] ?? 0);
        $vatRate = (float)($priceListItem['vat_rate'] ?? 0);

        if ($amount === 0) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Kolicina artikla ne smije biti nula."
            ]);
            exit;
        }

        if (!in_array($unit, ['kom', 'sati'], true)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Jedinica mora biti kom ili sati."
            ]);
            exit;
        }

        if ($discount < 0 || $discount > 100) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Popust mora biti izmedju 0 i 100."
            ]);
            exit;
        }

        if ($payment !== 'cash_payment') {
            $tipPrice = 0;
        }

        $basePrice = $retailPrice * $amount;
        $discountAmount = $basePrice * ($discount / 100);
        $priceAfterDiscount = $basePrice - $discountAmount;
        $vatAmount = $priceAfterDiscount * ($vatRate / 100);
        $finalPrice = $priceAfterDiscount + $vatAmount + $tipPrice;

        $preparedArticles[] = [
            'label' => (string)($priceListItem['label'] ?? ''),
            'amount' => $amount,
            'retail_price' => number_format($retailPrice, 2, '.', ''),
            'vat_rate' => number_format($vatRate, 2, '.', ''),
            'unit' => $unit,
            'discount' => $discount,
            'tip_price' => number_format($tipPrice, 2, '.', ''),
            'final_price' => number_format($finalPrice, 2, '.', ''),
        ];
        $invoiceNettoPrice += $priceAfterDiscount;
        $invoiceVatAmount += $vatAmount;
        $invoiceTotalPrice += $finalPrice;
    }

    if (empty($preparedArticles)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Nema valjanih artikala za spremanje."
        ]);
        exit;
    }

    $insertTime = $invoiceDate . ' ' . date('H:i:s');

    $conn->beginTransaction();

    $customerId = null;
    if ($customerData['full_name'] !== '' || $customerData['oib'] !== '') {
        if ($customerData['oib'] !== '') {
            $existingCustomerStmt = $conn->prepare('SELECT id FROM customers WHERE company_id = ? AND oib = ? LIMIT 1');
            $existingCustomerStmt->execute([$company['id'], $customerData['oib']]);
        } else {
            $existingCustomerStmt = $conn->prepare('SELECT id FROM customers WHERE company_id = ? AND full_name = ? LIMIT 1');
            $existingCustomerStmt->execute([$company['id'], $customerData['full_name']]);
        }
        $existingCustomer = $existingCustomerStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingCustomer) {
            $customerId = (int)$existingCustomer['id'];
            $customerUpdateStmt = $conn->prepare('UPDATE customers SET full_name = ?, legal = ?, address = ?, city = ?, country = ?, oib = ?, email = ? WHERE id = ?');
            $customerUpdateStmt->execute([
                $customerData['full_name'],
                $customerData['legal'],
                $customerData['address'],
                $customerData['city'],
                $customerData['country'],
                $customerData['oib'],
                $customerData['email'],
                $customerId,
            ]);
        } else {
            $customerInsertStmt = $conn->prepare('INSERT INTO customers (full_name, legal, address, city, country, oib, email, company_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $customerInsertStmt->execute([
                $customerData['full_name'],
                $customerData['legal'],
                $customerData['address'],
                $customerData['city'],
                $customerData['country'],
                $customerData['oib'],
                $customerData['email'],
                $company['id'],
            ]);
            $customerId = (int)$conn->lastInsertId();
        }
    }

    $invoiceUpdateStmt = $conn->prepare('
        UPDATE invoices
        SET
            customer_id = ?,
            bunit_id = ?,
            payment = ?,
            insert_time = ?,
            due_date = ?,
            netto_price = ?,
            vat_amount = ?,
            total_price = ?,
            remark = ?
        WHERE id = ? AND company_id = ?
    ');
    $invoiceUpdateStmt->execute([
        $customerId,
        $bunitId,
        $invoicePaymentValue,
        $insertTime,
        $dueDate !== '' ? $dueDate : null,
        number_format($invoiceNettoPrice, 2, '.', ''),
        number_format($invoiceVatAmount, 2, '.', ''),
        number_format($invoiceTotalPrice, 2, '.', ''),
        $remark,
        $invoiceId,
        $company['id'],
    ]);

    $deleteArticlesStmt = $conn->prepare('DELETE FROM invoice_articles WHERE invoice_id = ?');
    $deleteArticlesStmt->execute([$invoiceId]);

    $invoiceArticlesStmt = $conn->prepare('INSERT INTO invoice_articles (label, amount, retail_price, final_price, vat_rate, unit, discount, tip_price, invoice_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($preparedArticles as $preparedArticle) {
        $invoiceArticlesStmt->execute([
            $preparedArticle['label'],
            $preparedArticle['amount'],
            $preparedArticle['retail_price'],
            $preparedArticle['final_price'],
            $preparedArticle['vat_rate'],
            $preparedArticle['unit'],
            $preparedArticle['discount'],
            $preparedArticle['tip_price'],
            $invoiceId,
        ]);
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Invoice updated successfully.',
        'invoice_id' => $invoiceId,
        'invoice_number' => (int)$existingInvoice['number'],
    ]);
    exit;
} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof PDO && $conn->inTransaction()) {
        $conn->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Updating invoice failed.'
    ]);
    exit;
}
