<?php
require '../core/init.php';

header("Content-Type: application/json; charset=UTF-8");

try {
    $user = Helper::requireAuth();

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
    $allowedFields = array_keys($user);
    $blockedFields = ['id', 'password', 'created_at', 'company_id'];
    $updates = [];

    foreach ($allowedFields as $field) {
        if (in_array($field, $blockedFields, true) || !array_key_exists($field, $payload)) {
            continue;
        }

        $value = $payload[$field];

        if ($field === 'role') {
            $value = in_array((string)$value, ['user', 'admin'], true) ? (string)$value : 'user';
        } elseif (is_string($value)) {
            $value = trim($value);
        }

        $updates[$field] = $value;
    }

    if (empty($updates)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Nema podataka za spremanje."
        ]);
        exit;
    }

    $db = DB::getInstance();
    $updated = $db->update('users', $updates, ['id' => $user['id']]);

    if (!$updated) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Spremanje korisnika nije uspjelo."
        ]);
        exit;
    }

    Helper::refreshAuthenticatedUser();

    echo json_encode([
        "success" => true,
        "message" => "Podaci o korisniku su spremljeni.",
        "user" => Helper::currentUser(),
        "company" => Helper::currentCompany()
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
