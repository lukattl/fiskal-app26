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

    <main class="container pt-0 pb-4">
        <style>
            .dashboard-hero {
                position: relative;
                overflow: hidden;
                border-radius: 1.25rem;
                border: 1px solid rgba(13, 110, 253, 0.08);
                background:
                    radial-gradient(circle at top right, rgba(13, 110, 253, 0.14), transparent 18rem),
                    linear-gradient(135deg, #ffffff 0%, #f4f8ff 100%);
                box-shadow: 0 20px 40px rgba(15, 23, 42, 0.06);
            }

            .dashboard-hero__eyebrow {
                display: inline-flex;
                align-items: center;
                gap: .4rem;
                font-size: .72rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .12em;
                color: #5c7aa6;
                margin-bottom: .75rem;
            }

            .dashboard-hero__actions .btn {
                min-width: 9rem;
            }

            .dashboard-stat {
                position: relative;
                overflow: hidden;
                background: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, rgba(247,250,255,0.98) 100%);
            }

            .dashboard-stat__icon {
                width: 3rem;
                height: 3rem;
                border-radius: 1rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-weight: 800;
                font-size: 1rem;
                color: #0d6efd;
                background: rgba(13, 110, 253, 0.1);
                box-shadow: inset 0 0 0 1px rgba(13, 110, 253, 0.08);
            }

            .dashboard-stat__value {
                font-size: 2rem;
                font-weight: 800;
                line-height: 1;
                color: #17324d;
            }

            .dashboard-stat__hint {
                font-size: .9rem;
                color: #6b7d92;
            }

            .dashboard-panel__title {
                display: flex;
                align-items: center;
                gap: .6rem;
            }

            .dashboard-panel__dot {
                width: .75rem;
                height: .75rem;
                border-radius: 999px;
                background: linear-gradient(135deg, #0d6efd 0%, #84b6ff 100%);
                box-shadow: 0 0 0 .25rem rgba(13, 110, 253, 0.12);
            }

            .dashboard-table thead th {
                font-size: .78rem;
                text-transform: uppercase;
                letter-spacing: .04em;
                color: #70839a;
                border-bottom-color: #dbe4f0;
            }

            .dashboard-customer-item {
                border: 1px solid #edf2f8;
                border-radius: .95rem;
                background: linear-gradient(180deg, #fff 0%, #fbfdff 100%);
                padding: .85rem .95rem;
            }

            .dashboard-customer-item + .dashboard-customer-item {
                margin-top: .75rem;
            }

            .dashboard-customer-progress {
                height: .45rem;
                border-radius: 999px;
                background: #eef3f9;
                overflow: hidden;
            }

            .dashboard-customer-progress__bar {
                height: 100%;
                border-radius: 999px;
                background: linear-gradient(90deg, #0d6efd 0%, #7fb2ff 100%);
            }
        </style>

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="dashboard-hero w-100 p-4 p-lg-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <div class="dashboard-hero__eyebrow">Pregled poslovanja</div>
                        <h1 class="display-6 fw-bold mb-2">Dobrodošao natrag, <?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></h1>
                    </div>
                    <div class="col-lg-4">
                        <div class="dashboard-hero__actions d-flex flex-wrap justify-content-lg-end gap-2">
                            <a class="btn btn-primary" href="new-invoice.php">Novi račun</a>
                            <a class="btn btn-outline-primary" href="invoices.php">Svi računi</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card dashboard-stat p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="text-muted text-uppercase small fw-semibold mb-2">Ukupno računa</div>
                            <div class="dashboard-stat__value"><?php echo (int)($stats['total_invoices'] ?? 0); ?></div>
                            <div class="dashboard-stat__hint mt-2">Svi izdani računi</div>
                        </div>
                        <div class="dashboard-stat__icon">R</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card dashboard-stat p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="text-muted text-uppercase small fw-semibold mb-2">Fiskalizirani</div>
                            <div class="dashboard-stat__value"><?php echo (int)($stats['fiscalized_invoices'] ?? 0); ?></div>
                            <div class="dashboard-stat__hint mt-2">Uspješno poslani u FINA-u</div>
                        </div>
                        <div class="dashboard-stat__icon text-success bg-success bg-opacity-10">F</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card dashboard-stat p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="text-muted text-uppercase small fw-semibold mb-2">Nefiskalizirani</div>
                            <div class="dashboard-stat__value"><?php echo (int)($stats['not_fiscalized_invoices'] ?? 0); ?></div>
                            <div class="dashboard-stat__hint mt-2">Čekaju fiskalizaciju</div>
                        </div>
                        <div class="dashboard-stat__icon text-danger bg-danger bg-opacity-10">N</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card dashboard-stat p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="text-muted text-uppercase small fw-semibold mb-2">Prihod</div>
                            <div class="dashboard-stat__value"><?php echo number_format((float)($stats['revenue'] ?? 0), 2, ',', '.'); ?></div>
                            <div class="dashboard-stat__hint mt-2">EUR ukupnog prometa</div>
                        </div>
                        <div class="dashboard-stat__icon text-warning bg-warning bg-opacity-10">€</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mb-3">
                <div class="card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="dashboard-panel__title">
                            <span class="dashboard-panel__dot"></span>
                            <h5 class="mb-0">Zadnji računi</h5>
                        </div>
                        <a class="btn btn-outline-secondary btn-sm" href="invoices.php">Otvori</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle dashboard-table">
                            <thead>
                                <tr>
                                    <th>Račun</th>
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
                                            <td><div class="fw-semibold"><?php echo htmlspecialchars($fullInvoiceNumber, ENT_QUOTES, 'UTF-8'); ?></div></td>
                                            <td><?php echo !empty($invoice['insert_time']) ? htmlspecialchars(date('d.m.Y', strtotime((string)$invoice['insert_time'])), ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                                            <td><?php echo htmlspecialchars((string)($invoice['customer_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <?php if ($isFiscalized) { ?>
                                                    <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle">Fiskalizirani</span>
                                                <?php } else { ?>
                                                    <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle">Nefiskalizirani</span>
                                                <?php } ?>
                                            </td>
                                            <td class="text-end"><?php echo number_format((float)($invoice['total_price'] ?? 0), 2, ',', '.'); ?> EUR</td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr>
                                        <td colspan="5" class="text-muted text-center py-4">Nema računa za tvrtku još.</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="dashboard-panel__title">
                            <span class="dashboard-panel__dot"></span>
                            <h5 class="mb-0">Kupci po prometu</h5>
                        </div>
                        <a class="btn btn-outline-primary btn-sm" href="customers.php">Otvori</a>
                    </div>
                    <?php if (!empty($topCustomers)) { ?>
                        <div>
                            <?php foreach ($topCustomers as $customerStat) { ?>
                                <?php
                                $customerTotal = (float)($customerStat['total_amount'] ?? 0);
                                $revenueTotal = (float)($stats['revenue'] ?? 0);
                                $customerPercent = $revenueTotal > 0 ? (int)(($customerTotal / $revenueTotal) * 100) : 0;
                                ?>
                                <div class="dashboard-customer-item">
                                    <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars((string)($customerStat['full_name'] ?? 'Nepoznati kupac'), ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="small text-body-secondary"><?php echo (int)round($customerPercent); ?>% ukupnog prometa</div>
                                        </div>
                                        <div class="text-end fw-semibold">
                                            <?php echo number_format($customerTotal, 2, ',', '.'); ?> EUR
                                        </div>
                                    </div>
                                    <div class="dashboard-customer-progress">
                                        <div class="dashboard-customer-progress__bar" style="width: <?php echo max(0, min(100, (int)round($customerPercent))); ?>%;"></div>
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
