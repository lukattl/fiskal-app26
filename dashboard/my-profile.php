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
        return 'Not available';
    }

    if (is_bool($value)) {
        return $value ? 'Yes' : 'No';
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
        return 'Not available';
    }

    $timestamp = strtotime((string)$value);
    if ($timestamp === false) {
        return (string)$value;
    }

    return date('d.m.Y', $timestamp);
}
?>
<?php require '../includes/header.php'; ?>

    <main class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">My Profile</h1>
            <a class="btn btn-outline-primary btn-sm" href="dashboard.php">Back to Dashboard</a>
        </div>

        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">User Data</h2>
                <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#editUserModal">Edit User</button>
            </div>
            <dl class="row mb-0">
                <?php foreach ($userFields as $field => $value) { ?>
                    <dt class="col-sm-3"><?php echo htmlspecialchars(profileLabel($field), ENT_QUOTES, 'UTF-8'); ?></dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($field === 'created_at' ? formatProfileDate($value) : profileValue($value), ENT_QUOTES, 'UTF-8'); ?></dd>
                <?php } ?>
            </dl>
        </div>

        <div class="card p-4 mt-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Company Data</h2>
                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#editCompanyModal">Edit Company</button>
            </div>
            <?php if (!empty($companyFields)) { ?>
                <dl class="row mb-0">
                    <?php foreach ($companyFields as $field => $value) { ?>
                        <dt class="col-sm-3"><?php echo htmlspecialchars(profileLabel($field), ENT_QUOTES, 'UTF-8'); ?></dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($field === 'created_at' ? formatProfileDate($value) : profileValue($field === 'pdv' ? ((int)$value === 1 ? 'On' : 'Off') : $value), ENT_QUOTES, 'UTF-8'); ?></dd>
                    <?php } ?>
                </dl>
            <?php } else { ?>
                <p class="text-muted mb-0">No company data found for this user.</p>
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
