<?php
require '../core/init.php';

header("Content-Type: application/json; charset=UTF-8");

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "Metoda nije dopuštena."
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

    $email = trim($data["email"] ?? "");
    $password = trim($data["password"] ?? "");

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Email i lozinka su obavezni."
        ]);
        exit;
    }

    $db = DB::getInstance();
    $sql = "SELECT * from users where email = ?";
    $query = $db->query($sql, [$email]);

    if ($query->getError() || !$query->getResults()) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Korisnik s tim emailom ne postoji."
        ]);
        exit;
    }

    $user = $query->getFirst();

    if (!password_verify($password, $user->password)) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Pogrešna lozinka."
        ]);
        exit;
    }

    session_regenerate_id(true);

    Helper::storeAuthenticatedUser($user);

    echo json_encode([
        "success" => true,
        "message" => "Prijava uspješna.",
        "redirect" => "dashboard/dashboard.php"
    ]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Greška baze podataka."
    ]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Došlo je do neočekivane greške."
    ]);
    exit;
}
