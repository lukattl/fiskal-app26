<?php
require '../core/init.php';

$user = Helper::requireAuth();
$company = Helper::currentCompany();
$db = DB::getInstance();
$fullName = htmlspecialchars($user['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
$companyName = htmlspecialchars($company['full_name'] ?? $company['full_name'] ?? 'Tvrtka', ENT_QUOTES, 'UTF-8');
$pageTitle = 'Naslovna - fiskal-app';
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
$topCustomers = [];

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
         LIMIT 5',
        [$companyId]
    );

    if (!$recentInvoicesQuery->getError() && $recentInvoicesQuery->getResults()) {
        $recentInvoices = Helper::toArray($recentInvoicesQuery->getResults());
    }

    $topCustomersQuery = $db->query(
        'SELECT 
            c.id,
            c.full_name,
            COALESCE(SUM(i.total_price), 0) AS total_amount
         FROM customers c
         LEFT JOIN invoices i ON i.customer_id = c.id AND i.company_id = ?
         WHERE c.company_id = ?
         GROUP BY c.id, c.full_name
         HAVING total_amount > 0
         ORDER BY total_amount DESC, c.full_name ASC
         LIMIT 10',
        [$companyId, $companyId]
    );

    if (!$topCustomersQuery->getError() && $topCustomersQuery->getResults()) {
        $topCustomers = Helper::toArray($topCustomersQuery->getResults());
    }
}
?>
<?php require '../includes/header.php'; ?>

    <main class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h1 class="h4 mb-1">Ploča</h1>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-primary btn-sm" href="new-invoice.php">Novi račun</a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card p-3 h-100">
                    <h6 class="text-muted mb-2">Ukupno računa</h6>
                    <p class="display-6 mb-0"><?php echo (int)($stats['total_invoices'] ?? 0); ?></p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card p-3 h-100">
                    <h6 class="text-muted mb-2">Fiskalizirani</h6>
                    <p class="display-6 mb-0"><?php echo (int)($stats['fiscalized_invoices'] ?? 0); ?></p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card p-3 h-100">
                    <h6 class="text-muted mb-2">Nefiskalizirani</h6>
                    <p class="display-6 mb-0"><?php echo (int)($stats['not_fiscalized_invoices'] ?? 0); ?></p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card p-3 h-100">
                    <h6 class="text-muted mb-2">Prihod</h6>
                    <p class="display-6 mb-0"><?php echo number_format((float)($stats['revenue'] ?? 0), 2, ',', '.'); ?> EUR</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mb-3">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Zadnji računi</h5>
                        <a class="btn btn-outline-secondary btn-sm" href="invoices.php">Otvori </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Računi  </th>
                                    <th>Datum</th>
                                    <th>Kupac</th>
                                    <th>Status</th>
                                    <th class="text-end">Iznos</th>
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
                                                    <span class="badge bg-success">Fiskalizirani</span>
                                                <?php } else { ?>
                                                    <span class="badge bg-danger">Nefiskalizirani</span>
                                                <?php } ?>
                                            </td>
                                            <td class="text-end"><?php echo number_format((float)($invoice['total_price'] ?? 0), 2, ',', '.'); ?> EUR</td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr>
                                        <td colspan="5" class="text-muted text-center py-4">Nema računa za tvrtku još!</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Kupci po prometu</h5>
                        <a class="btn btn-outline-primary btn-sm" href="customers.php">Otvori</a>
                    </div>
                    <?php if (!empty($topCustomers)) { ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($topCustomers as $customerStat) { ?>
                                <?php
                                $customerTotal = (float)($customerStat['total_amount'] ?? 0);
                                $revenueTotal = (float)($stats['revenue'] ?? 0);
                                $customerPercent = $revenueTotal > 0 ? (int)(($customerTotal / $revenueTotal) * 100) : 0;
                                ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars((string)($customerStat['full_name'] ?? 'Nepoznati kupac'), ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="small text-body-secondary"><?php echo (int)round($customerPercent); ?>%</div>
                                        </div>
                                        <div class="text-end fw-semibold">
                                            <?php echo number_format($customerTotal, 2, ',', '.'); ?> EUR
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } else { ?>
                        <p class="text-muted mb-0">Još nema podataka o prometu po kupcima.</p>
                    <?php } ?>
                </div>
            </div>
        </div>
    </main>

    <?php require '../includes/footer.php'; ?>

    <script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main1.js?v=8"></script>
</body>
</html>


