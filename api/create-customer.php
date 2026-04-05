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
    $insert = [
        'full_name' => trim((string)($payload['full_name'] ?? '')),
        'address' => trim((string)($payload['address'] ?? '')),
        'city' => trim((string)($payload['city'] ?? '')),
        'country' => trim((string)($payload['country'] ?? '')),
        'oib' => trim((string)($payload['oib'] ?? '')),
        'email' => trim((string)($payload['email'] ?? '')),
        'legal' => !empty($payload['legal']) ? 1 : 0,
        'company_id' => $company['id'] ?? 0,
    ];

    if ($insert['full_name'] === '') {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Full name is required."
        ]);
        exit;
    }

    if (empty($insert['company_id'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Company is required."
        ]);
        exit;
    }

    $db = DB::getInstance();
    $created = $db->insert('customers', $insert);

    if (!$created) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Creating customer failed."
        ]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "Customer created successfully."
    ]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Creating customer failed."
    ]);
    exit;
}
