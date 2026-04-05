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

    $db = DB::getInstance();
    $created = $db->insert('price_list', [
        'label' => $label,
        'retail_price' => $retailPrice,
        'vat_rate' => $vatRate,
        'unit' => $unit,
        'company_id' => $company['id']
    ]);

    if (!$created) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Kreiranje artikla nije uspjelo."
        ]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "Artikl je kreiran."
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
