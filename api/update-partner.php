<?php
require '../core/init.php';

header("Content-Type: application/json; charset=UTF-8");

try {
    Helper::requireAuth();

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "Metoda nije dopustena."
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
    $partnerId = (int)($payload['id'] ?? 0);

    if ($partnerId <= 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Neispravan partner."
        ]);
        exit;
    }

    $updates = [
        'full_name' => trim((string)($payload['full_name'] ?? '')),
        'address' => trim((string)($payload['address'] ?? '')),
        'city' => trim((string)($payload['city'] ?? '')),
        'country' => trim((string)($payload['country'] ?? '')),
        'oib' => trim((string)($payload['oib'] ?? '')),
        'email' => trim((string)($payload['email'] ?? '')),
    ];

    if ($updates['full_name'] === '') {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Full name is required."
        ]);
        exit;
    }

    $db = DB::getInstance();
    $partnerQuery = $db->query('SELECT * FROM partners WHERE id = ?', [$partnerId]);

    if ($partnerQuery->getError() || !$partnerQuery->getResults()) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Partner not found."
        ]);
        exit;
    }

    $updated = $db->update('partners', $updates, ['id' => $partnerId]);

    if (!$updated) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Saving partner failed."
        ]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "Partner updated successfully."
    ]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Saving partner failed."
    ]);
    exit;
}
