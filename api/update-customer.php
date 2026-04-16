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
    $customerId = (int)($payload['id'] ?? 0);

    if ($customerId <= 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Neispravan customer."
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
        'legal' => !empty($payload['legal_government']) ? 2 : (!empty($payload['legal']) ? 1 : 0),
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
    $customerQuery = $db->query('SELECT * FROM customers WHERE id = ? AND company_id = ?', [$customerId, $company['id'] ?? 0]);

    if ($customerQuery->getError() || !$customerQuery->getResults()) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Customer not found."
        ]);
        exit;
    }

    $updated = $db->update('customers', $updates, ['id' => $customerId]);

    if (!$updated) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Saving customer failed."
        ]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "Customer updated successfully."
    ]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Saving customer failed."
    ]);
    exit;
}
