<?php
require 'core/init.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiskalizacija App - Prijava</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <h1>Fiscalio</h1>
            <p class="subtitle">
                Web aplikacija za unos, pregled i fiskalizaciju računa prema sustavu Porezne uprave Republike Hrvatske.
            </p>

            <div class="features">
                <div class="feature-box">
                    <h3>Unos računa</h3>
                    <p>Jednostavan unos podataka računa kroz pregledno web sučelje.</p>
                </div>

                <div class="feature-box">
                    <h3>XML fiskalizacija</h3>
                    <p>Generiranje i slanje XML zahtjeva prema propisanoj shemi.</p>
                </div>

                <div class="feature-box">
                    <h3>JIR i ZKI</h3>
                    <p>Pohrana i prikaz fiskalnih oznaka nakon uspješne obrade.</p>
                </div>
            </div>
        </div>

        <div class="right-panel">
            <div class="login-card">
                <h2>Prijava korisnika</h2>

                <form id="loginForm">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>

                    <label for="password">Lozinka</label>
                    <input type="password" id="password" name="password" required>

                    <button type="submit" id="loginBtn">Prijava</button>
                </form>

                <p id="loginMessage" class="form-message"></p>

                <p class="note">Demo verzija</p>
            </div>
        </div>
    </div>

    <?php require 'includes/footer.php'; ?>

    <script src="assets/js/main1.js"></script>
</body>
</html>
