<?php
require '../core/init.php';

$user = Helper::requireAuth();
$company = Helper::currentCompany();
$fullName = htmlspecialchars($user['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
$companyName = htmlspecialchars($company['name'] ?? $company['company_name'] ?? 'Not available', ENT_QUOTES, 'UTF-8');
$pageTitle = 'My Profile - fiskal-app';
$pageKey = 'profile';
$userFields = array_filter($user, static function ($key) {
    return !in_array($key, ['id', 'company_id'], true);
}, ARRAY_FILTER_USE_KEY);
$companyFields = array_filter($company, static function ($key) {
    return $key !== 'id';
}, ARRAY_FILTER_USE_KEY);

function profileLabel($field) {
    return ucwords(str_replace('_', ' ', (string)$field));
}

function profileValue($value) {
    if ($value === null || $value === '') {
        return 'Nedostupno';
    }

    if (is_bool($value)) {
        return $value ? 'Da' : 'Ne';
    }

    if (is_scalar($value)) {
        return (string)$value;
    }

    return json_encode($value);
}

function isCheckedValue($value) {
    return in_array((string)$value, ['1', 'true', 'on', 'yes'], true);
}

function formatProfileDate($value) {
    if ($value === null || $value === '') {
        return 'Nedostupno';
    }

    $timestamp = strtotime((string)$value);
    if ($timestamp === false) {
        return (string)$value;
    }

    return date('d.m.Y', $timestamp);
}

$userColumnOne = array_filter([
    'Ime i prezime' => $user['full_name'] ?? '',
    'Alias' => $user['alias'] ?? '',
    'Korisničko ime' => $user['username'] ?? '',
], static function ($value) {
    return $value !== null && $value !== '';
});

$userColumnTwo = array_filter([
    'OIB' => $user['oib'] ?? '',
    'Email' => $user['email'] ?? '',
    'Uloga' => $user['role'] ?? '',
], static function ($value) {
    return $value !== null && $value !== '';
});

$companyAddressParts = array_filter([
    $company['address'] ?? '',
    $company['city'] ?? '',
    $company['postal_code'] ?? '',
], static function ($value) {
    return $value !== null && trim((string)$value) !== '';
});

$companyColumnOne = array_filter([
    'Naziv' => $company['full_name'] ?? $company['short_name'] ?? '',
    'Adresa' => !empty($companyAddressParts) ? implode(', ', $companyAddressParts) : '',
], static function ($value) {
    return $value !== null && $value !== '';
});

$companyColumnTwo = array_filter([
    'OIB' => $company['oib'] ?? '',
    'PDV' => array_key_exists('pdv', $company) ? ((int)$company['pdv'] === 1 ? 'Da' : 'Ne') : '',
    'IBAN' => $company['iban'] ?? '',
    'P12 lozinka' => $company['p12_password'] ?? '',
], static function ($value) {
    return $value !== null && $value !== '';
});
?>
<?php require '../includes/header.php'; ?>

    <main class="container pt-0 pb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Moj profil</h1>
            <a class="btn btn-outline-primary btn-sm" href="dashboard.php">Natrag na ploču</a>
        </div>

        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Podaci o korisniku</h2>
                <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#editUserModal">Uredi korisnika</button>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <dl class="row mb-0">
                        <?php foreach ($userColumnOne as $field => $value) { ?>
                            <dt class="col-sm-4"><?php echo htmlspecialchars(profileLabel($field), ENT_QUOTES, 'UTF-8'); ?></dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars(profileValue($value), ENT_QUOTES, 'UTF-8'); ?></dd>
                        <?php } ?>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl class="row mb-0">
                        <?php foreach ($userColumnTwo as $field => $value) { ?>
                            <dt class="col-sm-4"><?php echo htmlspecialchars(profileLabel($field), ENT_QUOTES, 'UTF-8'); ?></dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars(profileValue($value), ENT_QUOTES, 'UTF-8'); ?></dd>
                        <?php } ?>
                    </dl>
                </div>
            </div>
        </div>

        <div class="card p-4 mt-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Podaci o tvrtci</h2>
                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#editCompanyModal">Uredi tvrtku</button>
            </div>
            <?php if (!empty($companyFields)) { ?>
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row mb-0">
                            <?php foreach ($companyColumnOne as $field => $value) { ?>
                                <dt class="col-sm-4"><?php echo htmlspecialchars(profileLabel($field), ENT_QUOTES, 'UTF-8'); ?></dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars(profileValue($value), ENT_QUOTES, 'UTF-8'); ?></dd>
                            <?php } ?>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row mb-0">
                            <?php foreach ($companyColumnTwo as $field => $value) { ?>
                                <dt class="col-sm-4"><?php echo htmlspecialchars(profileLabel($field), ENT_QUOTES, 'UTF-8'); ?></dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars(profileValue($value), ENT_QUOTES, 'UTF-8'); ?></dd>
                            <?php } ?>
                        </dl>
                    </div>
                </div>
            <?php } else { ?>
                <p class="text-muted mb-0">Nisu pronađeni podaci o tvrtci za ovog korisnika.</p>
            <?php } ?>
        </div>
    </main>
    <?php require '../modals/profile-user-modal.php'; ?>
    <?php require '../modals/profile-company-modal.php'; ?>

    <?php require '../includes/footer.php'; ?>

    <script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main1.js?v=4"></script>
</body>
</html>
