<?php
require '../core/init.php';

$user = Helper::requireAuth();
$fullName = htmlspecialchars($user['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
$pageTitle = 'Payments - fiskal-app';
$pageKey = 'payments';
?>
<?php require '../includes/header.php'; ?>

    <main class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Payments</h1>
            <a class="btn btn-outline-primary btn-sm" href="dashboard.php">Back to Dashboard</a>
        </div>

        <div class="card p-4">
            <h2 class="h5 mb-3">Recent Payments</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#INV-1001</td>
                            <td>2026-04-01</td>
                            <td><span class="badge bg-success">Paid</span></td>
                            <td>780 EUR</td>
                        </tr>
                        <tr>
                            <td>#INV-1002</td>
                            <td>2026-03-30</td>
                            <td><span class="badge bg-warning text-dark">Pending</span></td>
                            <td>1,120 EUR</td>
                        </tr>
                        <tr>
                            <td>#INV-1003</td>
                            <td>2026-03-28</td>
                            <td><span class="badge bg-success">Paid</span></td>
                            <td>450 EUR</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <?php require '../includes/footer.php'; ?>

    <script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main1.js?v=4"></script>
</body>
</html>
