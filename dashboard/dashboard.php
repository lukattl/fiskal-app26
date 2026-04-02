<?php
require '../core/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$fullName = htmlspecialchars($_SESSION['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - fiskal-app</title>
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
            <a class="navbar-brand" href="#">fiskal-app</a>
            <div class="d-flex">
                <span class="me-3">Admin</span>
                <a class="btn btn-outline-secondary btn-sm" href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <main class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4">Dashboard</h1>
            <small class="text-muted">Welcome back, <?php echo $fullName; ?></small>
        </div>

        <div class="dashboard-grid mb-4">
            <div class="card p-3">
                <h6>Total Invoices</h6>
                <p class="display-6 mb-0">122</p>
            </div>
            <div class="card p-3">
                <h6>Paid</h6>
                <p class="display-6 mb-0">87</p>
            </div>
            <div class="card p-3">
                <h6>Pending</h6>
                <p class="display-6 mb-0">35</p>
            </div>
            <div class="card p-3">
                <h6>Revenue</h6>
                <p class="display-6 mb-0">$45,600</p>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mb-3">
                <div class="card p-3 h-100">
                    <h5>Recent Transactions</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th><th>Client</th><th>Status</th><th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>2026-04-01</td><td>ABC Co.</td><td><span class="badge bg-success">Paid</span></td><td>$780</td></tr>
                                <tr><td>2026-03-30</td><td>XYZ Ltd.</td><td><span class="badge bg-warning">Pending</span></td><td>$1,120</td></tr>
                                <tr><td>2026-03-28</td><td>StartUp</td><td><span class="badge bg-success">Paid</span></td><td>$450</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="card p-3 h-100">
                    <h5>Quick Actions</h5>
                    <div class="d-grid gap-2">
                        <a class="btn btn-primary" href="/dashboard/new-invoice.php">New Invoice</a>
                        <a class="btn btn-outline-primary" href="/dashboard/customers.php">Customers</a>
                        <a class="btn btn-outline-secondary" href="/dashboard/reports.php">Reports</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
