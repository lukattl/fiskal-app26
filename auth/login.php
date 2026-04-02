<?php
#require_once "../config/db.php";
require '../core/init.php';

if (Input::exists()) {

    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if (empty($email) || empty($password)) {
        die("Sva polja su obavezna.");
    }

    try {
        $db = DB::getInstance();
        $sql = "SELECT id, full_name, email, password from users where email = ?";
        $query = $db->query($sql, [$email]);

        if (!$query->getError() && $query->getResults()) {
            $user = $query->getFirst();

            // provjera lozinke (bcrypt / password_hash)
            if (password_verify($password, $user->password)) {
    
                // session
                $_SESSION["user_id"] = $user->id;
                $_SESSION["full_name"] = $user->full_name;
                $_SESSION["email"] = $user->email;
    
                // redirect
                header("Location: ../dashboard/dashboard.php");
                exit;
    
            } else {
                echo "Pogrešna lozinka.";
            }
        } else {
            echo "Korisnik ne postoji.";
        }

    } catch (PDOException $e) {
        echo "Greška: " . $e->getMessage();
    }
}
