<?php
require '../core/init.php';

$user = Helper::requireAuth();
$company = Helper::currentCompany();
$db = DB::getInstance();

$fullName = htmlspecialchars($user['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
$companyName = htmlspecialchars($company['name'] ?? $company['company_name'] ?? 'Your company', ENT_QUOTES, 'UTF-8');
$pageTitle = 'Dashboard - fiskal-app';
$pageKey = 'dashboard';

$companyId = (int)($company['id'] ?? 0);
$stats = [
    'total_invoices' => 0,
    'fiscalized_invoices' => 0,
    'not_fiscalized_invoices' => 0,
    'revenue' => 0.0,
    'customers' => 0,
];
$recentInvoices = [];

if ($companyId > 0) {
    $statsQuery = $db->query(
        'SELECT 
            COUNT(*) AS total_invoices,
            SUM(CASE WHEN jir IS NOT NULL AND jir != "" AND fiskaled_at IS NOT NULL THEN 1 ELSE 0 END) AS fiscalized_invoices,
            SUM(CASE WHEN jir IS NULL OR jir = "" OR fiskaled_at IS NULL THEN 1 ELSE 0 END) AS not_fiscalized_invoices,
            COALESCE(SUM(total_price), 0) AS revenue
         FROM invoices
         WHERE company_id = ?',
        [$companyId]
    );

    if (!$statsQuery->getError() && $statsQuery->getResults()) {
        $stats = array_merge($stats, Helper::toArray($statsQuery->getFirst()));
    }

    $customersQuery = $db->query('SELECT COUNT(*) AS total_customers FROM customers WHERE company_id = ?', [$companyId]);
    if (!$customersQuery->getError() && $customersQuery->getResults()) {
        $customerStats = Helper::toArray($customersQuery->getFirst());
        $stats['customers'] = (int)($customerStats['total_customers'] ?? 0);
    }

    $recentInvoicesQuery = $db->query(
        'SELECT i.*, c.full_name AS customer_name, b.label AS bunit_label, b.label2 AS bunit_label2
         FROM invoices i
         LEFT JOIN customers c ON c.id = i.customer_id
         LEFT JOIN business_units b ON b.id = i.bunit_id
         WHERE i.company_id = ?
         ORDER BY COALESCE(i.insert_time, i.created_at) DESC, i.id DESC
         LIMIT 8',
        [$companyId]
    );

    if (!$recentInvoicesQuery->getError() && $recentInvoicesQuery->getResults()) {
        $recentInvoices = Helper::toArray($recentInvoicesQuery->getResults());
    }
}
?>
<?php require '../includes/header.php'; ?>

    <main class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h1 class="h4 mb-1">Dashboard</h1>
                <small class="text-muted">Welcome back, <?php echo $fullName; ?> from <?php echo $companyName; ?></small>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-primary btn-sm" href="invoices.php">All Invoices</a>
                <a class="btn btn-primary btn-sm" href="new-invoice.php">New Invoice</a>
            </div>
        </div>

        <div class="dashboard-grid mb-4">
            <div class="card p-3">
                <h6 class="text-muted mb-2">Total Invoices</h6>
                <p class="display-6 mb-0"><?php echo (int)($stats['total_invoices'] ?? 0); ?></p>
            </div>
            <div class="card p-3">
                <h6 class="text-muted mb-2">Fiscalized</h6>
                <p class="display-6 mb-0"><?php echo (int)($stats['fiscalized_invoices'] ?? 0); ?></p>
            </div>
            <div class="card p-3">
                <h6 class="text-muted mb-2">Not Fiscalized</h6>
                <p class="display-6 mb-0"><?php echo (int)($stats['not_fiscalized_invoices'] ?? 0); ?></p>
            </div>
            <div class="card p-3">
                <h6 class="text-muted mb-2">Revenue</h6>
                <p class="display-6 mb-0"><?php echo number_format((float)($stats['revenue'] ?? 0), 2, ',', '.'); ?> EUR</p>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mb-3">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Recent Invoices</h5>
                        <a class="btn btn-outline-secondary btn-sm" href="invoices.php">Open list</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentInvoices)) { ?>
                                    <?php foreach ($recentInvoices as $invoice) { ?>
                                        <?php
                                        $isFiscalized = !empty($invoice['jir']) && !empty($invoice['fiskaled_at']);
                                        $invoiceNumberLabel = trim((string)($invoice['number'] ?? ''));
                                        $bunitLabel = trim((string)($invoice['bunit_label'] ?? ''));
                                        $bunitLabel2 = trim((string)($invoice['bunit_label2'] ?? ''));
                                        $fullInvoiceNumber = $invoiceNumberLabel;
                                        if ($bunitLabel !== '' || $bunitLabel2 !== '') {
                                            $fullInvoiceNumber .= '/' . $bunitLabel;
                                            if ($bunitLabel2 !== '') {
                                                $fullInvoiceNumber .= '/' . $bunitLabel2;
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($fullInvoiceNumber, ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo !empty($invoice['insert_time']) ? htmlspecialchars(date('d.m.Y', strtotime((string)$invoice['insert_time'])), ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                                            <td><?php echo htmlspecialchars((string)($invoice['customer_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <?php if ($isFiscalized) { ?>
                                                    <span class="badge bg-success">Fiscalized</span>
                                                <?php } else { ?>
                                                    <span class="badge bg-danger">Not fiscalized</span>
                                                <?php } ?>
                                            </td>
                                            <td class="text-end"><?php echo number_format((float)($invoice['total_price'] ?? 0), 2, ',', '.'); ?> EUR</td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr>
                                        <td colspan="5" class="text-muted text-center py-4">No invoices found for this company yet.</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="card p-3 h-100">
                    <h5>Quick Actions</h5>
                    <div class="d-grid gap-2 mb-4">
                        <a class="btn btn-primary" href="new-invoice.php">New Invoice</a>
                        <a class="btn btn-outline-primary" href="customers.php">Customers</a>
                        <a class="btn btn-outline-secondary" href="settings.php">Settings</a>
                    </div>
                    <h6 class="text-muted">At a Glance</h6>
                    <div class="small text-body-secondary">
                        <div class="mb-2"><strong>Company:</strong> <?php echo $companyName; ?></div>
                        <div class="mb-2"><strong>Customers:</strong> <?php echo (int)($stats['customers'] ?? 0); ?></div>
                        <div class="mb-2"><strong>Recent invoices shown:</strong> <?php echo count($recentInvoices); ?></div>
                        <div><strong>User:</strong> <?php echo $fullName; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require '../includes/footer.php'; ?>

    <script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main1.js?v=8"></script>
</body>
</html>
