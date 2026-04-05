<?php
require '../core/init.php';

header("Content-Type: application/json; charset=UTF-8");

try {
    Helper::requireAuth();
    $bunitId = Helper::currentBusinessUnitId();

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "Metoda nije dopustena."
        ]);
        exit;
    }

    if (empty($bunitId)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Korisnik nema povezanu poslovnu jedinicu."
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

    $fieldAliases = [
        'card_payment' => 'card_payment',
        'transactional_payment' => 'transactional_payment',
        'einvoice_sender' => 'einvoice_sender',
    ];
    $field = (string)($data['field'] ?? '');
    $value = !empty($data['value']) ? 1 : 0;

    if (!isset($fieldAliases[$field])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Neispravna opcija."
        ]);
        exit;
    }

    $dbField = $fieldAliases[$field];
    $db = DB::getInstance();
    $optionsQuery = $db->query('SELECT * FROM bunit_options WHERE bunit_id = ?', [$bunitId]);

    if ($optionsQuery->getError()) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Dohvat opcija nije uspio."
        ]);
        exit;
    }

    if ($optionsQuery->getResults()) {
        $options = Helper::toArray($optionsQuery->getFirst());
        $updated = $db->update('bunit_options', [$dbField => $value], ['id' => $options['id']]);
    } else {
        $payload = [
            'bunit_id' => $bunitId,
            'card_payment' => 0,
            'transactional_payment' => 0,
            'einvoice_sender' => 0,
        ];
        $payload[$dbField] = $value;
        $updated = $db->insert('bunit_options', $payload);
    }

    if (!$updated) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Spremanje opcije nije uspjelo."
        ]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "Opcija je spremljena.",
        "field" => $dbField,
        "value" => $value
    ]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Spremanje opcije nije uspjelo."
    ]);
    exit;
}
