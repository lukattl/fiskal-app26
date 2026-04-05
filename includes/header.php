<?php
$pageTitle = $pageTitle ?? 'fiskal-app';
$pageKey = $pageKey ?? '';
$fullName = $fullName ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../node_modules/bootstrap/dist/css/bootstrap.min.css">
    <style>
        body { background:#f5f7fb; }
        .card { border: 1px solid #ddd; border-radius: .5rem; }
        .dashboard-grid { display: grid; grid-gap: 1rem; grid-template-columns: repeat(auto-fill,minmax(220px,1fr)); }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">FISKAL 26</a>
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item<?php echo $pageKey === 'profile' ? ' active' : ''; ?>" href="my-profile.php">My Profile</a></li>
                    <li><a class="dropdown-item<?php echo $pageKey === 'settings' ? ' active' : ''; ?>" href="settings.php">Settings</a></li>
                    <li><a class="dropdown-item<?php echo $pageKey === 'invoices' ? ' active' : ''; ?>" href="invoices.php">Invoices</a></li>
                    <li><a class="dropdown-item<?php echo $pageKey === 'customers' ? ' active' : ''; ?>" href="customers.php">Customers</a></li>
                    <li><a class="dropdown-item<?php echo $pageKey === 'payments' ? ' active' : ''; ?>" href="payments.php">Payments</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><button class="dropdown-item text-danger" type="button" data-logout data-logout-url="../api/logout.php">Logout</button></li>
                </ul>
            </div>
        </div>
    </nav>
