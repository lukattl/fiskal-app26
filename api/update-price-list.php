<?php
require '../core/init.php';

header("Content-Type: application/json; charset=UTF-8");

try {
    Helper::requireAuth();
    $company = Helper::currentCompany();

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "Metoda nije dopustena."
        ]);
        exit;
    }

    if (empty($company['id'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Korisnik nema povezanu tvrtku."
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

    $payload = $data['data'] ?? $data;
    $articleId = (int)($payload['id'] ?? 0);

    if ($articleId <= 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Neispravan artikl."
        ]);
        exit;
    }

    $db = DB::getInstance();
    $articleQuery = $db->query('SELECT * FROM price_list WHERE id = ? AND company_id = ?', [$articleId, $company['id']]);

    if ($articleQuery->getError() || !$articleQuery->getResults()) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Artikl nije pronadjen za ovu tvrtku."
        ]);
        exit;
    }

    $label = trim((string)($payload['label'] ?? ''));
    $retailPrice = str_replace(',', '.', trim((string)($payload['retail_price'] ?? '0')));
    $vatRate = (string)($payload['vat_rate'] ?? '0');
    $unit = (string)($payload['unit'] ?? 'kom');

    if ($label === '') {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Label je obavezna."
        ]);
        exit;
    }

    if (!is_numeric($retailPrice)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Retail price mora biti broj."
        ]);
        exit;
    }

    if (!in_array($vatRate, ['0', '25'], true)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "VAT rate mora biti 0 ili 25."
        ]);
        exit;
    }

    if (!in_array($unit, ['kom', 'sati'], true)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Unit mora biti kom ili sati."
        ]);
        exit;
    }

    $updated = $db->update('price_list', [
        'label' => $label,
        'retail_price' => $retailPrice,
        'vat_rate' => $vatRate,
        'unit' => $unit
    ], ['id' => $articleId]);

    if (!$updated) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Spremanje artikla nije uspjelo."
        ]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "Artikl je spremljen."
    ]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Doslo je do neocekivane greske."
    ]);
    exit;
}
