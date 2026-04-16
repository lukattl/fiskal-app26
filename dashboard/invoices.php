<?php
require '../core/init.php';

$user = Helper::requireAuth();
$company = Helper::currentCompany();
$bunitId = Helper::currentBusinessUnitId();
$fullName = htmlspecialchars($user['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
$pageTitle = 'Invoices - fiskal-app';
$pageKey = 'invoices';
$db = DB::getInstance();
$invoices = [];
$customersMap = [];
$invoiceArticlesMap = [];
$bunitMap = [];
$selectedCustomerId = (int)($_GET['customer_id'] ?? 0);
$selectedInvoiceDate = trim((string)($_GET['invoice_date'] ?? ''));
$selectedFiscalStatus = trim((string)($_GET['fiscal_status'] ?? ''));

if (!empty($company['id'])) {
    $customersQuery = $db->query('SELECT * FROM customers WHERE company_id = ?', [$company['id']]);
    if (!$customersQuery->getError() && $customersQuery->getResults()) {
        foreach (Helper::toArray($customersQuery->getResults()) as $customer) {
            $customersMap[(int)($customer['id'] ?? 0)] = $customer;
        }
    }

    $invoiceSql = 'SELECT * FROM invoices WHERE company_id = ?';
    $invoiceParams = [$company['id']];

    if ($selectedCustomerId > 0) {
        $invoiceSql .= ' AND customer_id = ?';
        $invoiceParams[] = $selectedCustomerId;
    }

    if ($selectedInvoiceDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedInvoiceDate)) {
        $invoiceSql .= ' AND DATE(insert_time) = ?';
        $invoiceParams[] = $selectedInvoiceDate;
    }

    if ($selectedFiscalStatus === 'fiscalized') {
        $invoiceSql .= ' AND jir IS NOT NULL AND jir != "" AND fiskaled_at IS NOT NULL';
    } elseif ($selectedFiscalStatus === 'not_fiscalized') {
        $invoiceSql .= ' AND (jir IS NULL OR jir = "" OR fiskaled_at IS NULL)';
    }

    $invoiceSql .= ' ORDER BY number DESC';
    $invoicesQuery = $db->query($invoiceSql, $invoiceParams);
    if (!$invoicesQuery->getError() && $invoicesQuery->getResults()) {
        $invoices = Helper::toArray($invoicesQuery->getResults());
    }
}

if (!empty($company['id'])) {
    $bunitsQuery = $db->query('SELECT * FROM business_units WHERE company_id = ?', [$company['id']]);
    if (!$bunitsQuery->getError() && $bunitsQuery->getResults()) {
        foreach (Helper::toArray($bunitsQuery->getResults()) as $businessUnit) {
            $bunitMap[(int)($businessUnit['id'] ?? 0)] = $businessUnit;
        }
    }
}

if (!empty($invoices)) {
    $invoiceIds = array_values(array_filter(array_map(static function ($invoice) {
        return (int)($invoice['id'] ?? 0);
    }, $invoices)));

    if (!empty($invoiceIds)) {
        $placeholders = implode(',', array_fill(0, count($invoiceIds), '?'));
        $invoiceArticlesQuery = $db->query("SELECT * FROM invoice_articles WHERE invoice_id IN ({$placeholders}) ORDER BY invoice_id ASC, id ASC", $invoiceIds);
        if (!$invoiceArticlesQuery->getError() && $invoiceArticlesQuery->getResults()) {
            foreach (Helper::toArray($invoiceArticlesQuery->getResults()) as $article) {
                $invoiceArticlesMap[(int)($article['invoice_id'] ?? 0)][] = $article;
            }
        }
    }
}

function formatInvoiceDateValue($value)
{
    if (empty($value)) {
        return "-";
    }

    $timestamp = strtotime((string)$value);
    if ($timestamp === false) {
        return (string)$value;
    }

    return date('d.m.Y', $timestamp);
}

function formatInvoiceDateTimeValue($value)
{
    if (empty($value)) {
        return "-";
    }

    $timestamp = strtotime((string)$value);
    if ($timestamp === false) {
        return (string)$value;
    }

    return date('d.m.Y H:i:s', $timestamp);
}

function formatInvoiceMoney($value)
{
    return number_format((float)$value, 2, ',', '.');
}

function formatCompanyAddressLine($company)
{
    if (!is_array($company)) {
        return 'Nedostupno';
    }

    $parts = array_filter([
        $company['address'] ?? '',
        $company['city'] ?? '',
        $company['postal_code'] ?? '',
    ], static function ($value) {
        return $value !== null && trim((string)$value) !== '';
    });

    return !empty($parts) ? implode(', ', $parts) : 'Nedostupno';
}

function formatInvoicePayment($value)
{
    $paymentMap = [
        'cash' => 'Gotovina',
        'card' => 'Kartice',
        'transaction' => 'Transakcija',
    ];

    $paymentKey = strtolower(trim((string)$value));

    return $paymentMap[$paymentKey] ?? (string)$value;
}

function invoiceActionIcon($type)
{
    $icons = [
        'preview' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.12 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/><path d="M8 5.5A2.5 2.5 0 1 0 8 10.5A2.5 2.5 0 0 0 8 5.5"/></svg>',
        'storno' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0-1A6 6 0 1 0 8 2a6 6 0 0 0 0 12"/><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/></svg>',
        'copy' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M10 1.5v1A1.5 1.5 0 0 0 11.5 4h1A1.5 1.5 0 0 0 14 2.5h-1A1.5 1.5 0 0 0 11.5 1z"/><path d="M4 3a2 2 0 0 0-2 2v7.5A1.5 1.5 0 0 0 3.5 14H10a2 2 0 0 0 2-2V7h-1v5a1 1 0 0 1-1 1H3.5a.5.5 0 0 1-.5-.5V5a1 1 0 0 1 1-1h5V3z"/><path d="M7.5 1A1.5 1.5 0 0 0 6 2.5v7A1.5 1.5 0 0 0 7.5 11h5A1.5 1.5 0 0 0 14 9.5v-5a1.5 1.5 0 0 0-.44-1.06l-1.5-1.5A1.5 1.5 0 0 0 11 1z"/></svg>',
        'pdf' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5zM9.5 3A1.5 1.5 0 0 0 11 4.5H13L9.5 1z"/><path d="M4.603 12.087a.81.81 0 0 1 .78-.652h.734c.928 0 1.495.411 1.495 1.132 0 .725-.584 1.101-1.459 1.101h-.538v.777a.81.81 0 0 1-1.62 0zm1.515.658c.312 0 .468-.105.468-.325 0-.215-.152-.332-.456-.332h-.515v.657zM8.626 12.087a.81.81 0 0 1 .78-.652h.438c1.252 0 1.941.685 1.941 1.92 0 1.236-.69 1.925-1.923 1.925h-.456a.81.81 0 0 1-.78-.652zm1.218 2.516c.533 0 .87-.277.87-1.248 0-.97-.337-1.232-.87-1.232h-.218v2.48zM12.95 12.087a.81.81 0 0 1 .78-.652h1.193a.659.659 0 1 1 0 1.32h-.79v.387h.57a.627.627 0 1 1 0 1.255h-.57v.861a.81.81 0 1 1-1.62 0z"/></svg>',
    ];

    return $icons[$type] ?? '';
}

?>
<?php require '../includes/header.php'; ?>

    <main class="container pt-0 pb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Računi</h1>
        </div>

        <div class="card p-4">
            <form class="row g-3 align-items-end mb-4" method="get">
                <div class="col-md-4">
                    <label class="form-label" for="invoice-filter-customer">Kupac</label>
                    <select class="form-select" id="invoice-filter-customer" name="customer_id">
                        <option value="">Svi kupci</option>
                        <?php foreach ($customersMap as $customerOption) { ?>
                            <option value="<?php echo (int)($customerOption['id'] ?? 0); ?>" <?php echo $selectedCustomerId === (int)($customerOption['id'] ?? 0) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string)($customerOption['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="invoice-filter-date">Datum računa</label>
                    <input class="form-control" type="date" id="invoice-filter-date" name="invoice_date" value="<?php echo htmlspecialchars($selectedInvoiceDate, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="invoice-filter-status">Status fiskalizacije</label>
                    <select class="form-select" id="invoice-filter-status" name="fiscal_status">
                        <option value="">Svi statusi</option>
                        <option value="fiscalized" <?php echo $selectedFiscalStatus === 'fiscalized' ? 'selected' : ''; ?>>Fiskaliziran</option>
                        <option value="not_fiscalized" <?php echo $selectedFiscalStatus === 'not_fiscalized' ? 'selected' : ''; ?>>Nefiskaliziran</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary btn-sm" type="submit">Filtriraj</button>
                    <a class="btn btn-outline-secondary btn-sm" href="invoices.php">Resetiraj</a>
                </div>
            </form>

            <?php if (!empty($invoices)) { ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Računi</th>
                                <th>Datum računa</th>
                                <th>Rok plaćanja</th>
                                <th>Kupac</th>
                                <th>Netto cijena</th>
                                <th>Ukupna cijena</th>
                                <th>Fiskalizacija</th>
                                <th>Napomena</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $invoice) { ?>
                                <?php
                                $invoiceNumber = (string)($invoice['number'] ?? '');
                                $invoiceBunit = $bunitMap[(int)($invoice['bunit_id'] ?? 0)] ?? [];
                                $invoiceBunitLabel = trim((string)($invoiceBunit['label'] ?? $invoiceBunit['code'] ?? ''));
                                $invoiceBunitLabel2 = trim((string)($invoiceBunit['label2'] ?? ''));
                                $fullNumber = $invoiceNumber;
                                if ($invoiceBunitLabel !== '') {
                                    $fullNumber .= '/' . $invoiceBunitLabel;
                                }
                                if ($invoiceBunitLabel2 !== '') {
                                    $fullNumber .= '/' . $invoiceBunitLabel2;
                                }
                                $customer = $customersMap[(int)($invoice['customer_id'] ?? 0)] ?? null;
                                $customerName = is_array($customer) ? (string)($customer['full_name'] ?? '-') : '-';
                                $invoiceDate = formatInvoiceDateValue($invoice['insert_time'] ?? '');
                                $dueDate = formatInvoiceDateValue($invoice['due_date'] ?? '');
                                $invoiceArticles = $invoiceArticlesMap[(int)($invoice['id'] ?? 0)] ?? [];
                                $isFiskalized = !empty($invoice['jir']) && !empty($invoice['fiskaled_at']);
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($fullNumber, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($invoiceDate, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($dueDate, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(formatInvoiceMoney($invoice['netto_price'] ?? 0) . ' EUR', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(formatInvoiceMoney($invoice['total_price'] ?? 0) . ' EUR', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if ($isFiskalized) { ?>
                                            <span class="badge bg-success">Fiskalizirani</span>
                                        <?php } else { ?>
                                            <span class="badge bg-danger">Nefiskalizirani</span>
                                        <?php } ?>
                                    </td>
                                    <td><?php echo htmlspecialchars((string)($invoice['remark'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#previewInvoiceModal-<?php echo (int)($invoice['id'] ?? 0); ?>" title="Preview" aria-label="Preview">
                                                <?php echo invoiceActionIcon('preview'); ?>
                                            </button>
                                            <?php if (!$isFiskalized) { ?>
                                                <button class="btn btn-danger btn-sm" type="button" data-fiscalize-invoice data-invoice-id="<?php echo (int)($invoice['id'] ?? 0); ?>" data-endpoint="../api/generate-fiscal-xml.php">Fiskaliziraj</button>
                                            <?php } ?>
                                            <a class="btn btn-outline-danger btn-sm" href="new-invoice.php?storno_invoice_id=<?php echo (int)($invoice['id'] ?? 0); ?>" title="Storno" aria-label="Storno">
                                                <?php echo invoiceActionIcon('storno'); ?>
                                            </a>
                                            <a class="btn btn-outline-secondary btn-sm" href="new-invoice.php?copy_invoice_id=<?php echo (int)($invoice['id'] ?? 0); ?>" title="Copy" aria-label="Copy">
                                                <?php echo invoiceActionIcon('copy'); ?>
                                            </a>
                                            <a class="btn btn-outline-dark btn-sm" href="../api/export-invoice-pdf.php?invoice_id=<?php echo (int)($invoice['id'] ?? 0); ?>" title="Export PDF" aria-label="Export PDF">
                                                <?php echo invoiceActionIcon('pdf'); ?>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <?php foreach ($invoices as $invoice) { ?>
                    <?php
                    $invoiceNumber = (string)($invoice['number'] ?? '');
                    $invoiceBunit = $bunitMap[(int)($invoice['bunit_id'] ?? 0)] ?? [];
                    $invoiceBunitLabel = trim((string)($invoiceBunit['label'] ?? $invoiceBunit['code'] ?? ''));
                    $invoiceBunitLabel2 = trim((string)($invoiceBunit['label2'] ?? ''));
                    $fullNumber = $invoiceNumber;
                    if ($invoiceBunitLabel !== '') {
                        $fullNumber .= '-' . $invoiceBunitLabel;
                    }
                    if ($invoiceBunitLabel2 !== '') {
                        $fullNumber .= ' - ' . $invoiceBunitLabel2;
                    }
                    $customer = $customersMap[(int)($invoice['customer_id'] ?? 0)] ?? null;
                    $invoiceDate = formatInvoiceDateValue($invoice['insert_time'] ?? '');
                    $dueDate = formatInvoiceDateValue($invoice['due_date'] ?? '');
                    $invoiceArticles = $invoiceArticlesMap[(int)($invoice['id'] ?? 0)] ?? [];
                    $issuerNotInPdv = (string)($company['pdv'] ?? '0') !== '1';
                    ?>
                    <div class="modal fade" id="previewInvoiceModal-<?php echo (int)($invoice['id'] ?? 0); ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h2 class="modal-title fs-5">Račun br. <?php echo htmlspecialchars($fullNumber, ENT_QUOTES, 'UTF-8'); ?></h2>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row g-4">
                                        <div class="col-lg-6">
                                            <div class="card p-3 h-100">
                                                <h3 class="h6 mb-3">Izdavatelj</h3>
                                                <dl class="row mb-4">
                                                    <dt class="col-sm-4">Naziv</dt>
                                                    <dd class="col-sm-8"><?php echo htmlspecialchars((string)($company['full_name'] ?? $company['short_name'] ?? 'Nedostupno'), ENT_QUOTES, 'UTF-8'); ?></dd>
                                                    <dt class="col-sm-4">Adresa</dt>
                                                    <dd class="col-sm-8"><?php echo htmlspecialchars(formatCompanyAddressLine($company), ENT_QUOTES, 'UTF-8'); ?></dd>
                                                    <dt class="col-sm-4">OIB</dt>
                                                    <dd class="col-sm-8"><?php echo htmlspecialchars((string)($company['oib'] ?? 'Nedostupno'), ENT_QUOTES, 'UTF-8'); ?></dd>
                                                    <dt class="col-sm-4">IBAN</dt>
                                                    <dd class="col-sm-8"><?php echo htmlspecialchars((string)($company['iban'] ?? 'Nedostupno'), ENT_QUOTES, 'UTF-8'); ?></dd>
                                                </dl>

                                                <h3 class="h6 mb-3">Kupac</h3>
                                                <?php if (is_array($customer)) { ?>
                                                    <dl class="row mb-0">
                                                        <dt class="col-sm-4">Naziv</dt>
                                                        <dd class="col-sm-8"><?php echo htmlspecialchars((string)($customer['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd>
                                                        <dt class="col-sm-4">OIB</dt>
                                                        <dd class="col-sm-8"><?php echo htmlspecialchars((string)($customer['oib'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd>
                                                        <dt class="col-sm-4">Adresa</dt>
                                                        <dd class="col-sm-8"><?php echo htmlspecialchars((string)($customer['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd>
                                                        <dt class="col-sm-4">Grad</dt>
                                                        <dd class="col-sm-8"><?php echo htmlspecialchars((string)($customer['city'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd>
                                                        <dt class="col-sm-4">Država</dt>
                                                        <dd class="col-sm-8"><?php echo htmlspecialchars((string)($customer['country'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd>
                                                        <dt class="col-sm-4">Email</dt>
                                                        <dd class="col-sm-8"><?php echo htmlspecialchars((string)($customer['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd>
                                                    </dl>
                                                <?php } else { ?>
                                                    <p class="text-muted mb-0">Kupac građanin</p>
                                                <?php } ?>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="card p-3 h-100">
                                                <h3 class="h6 mb-3">Detalji računa</h3>
                                                <dl class="row mb-0">
                                                    <dt class="col-sm-5">Datum računa</dt>
                                                    <dd class="col-sm-7"><?php echo htmlspecialchars($invoiceDate, ENT_QUOTES, 'UTF-8'); ?></dd>
                                                    <dt class="col-sm-5">Rok plaćanja</dt>
                                                    <dd class="col-sm-7"><?php echo htmlspecialchars($dueDate, ENT_QUOTES, 'UTF-8'); ?></dd>
                                                    <dt class="col-sm-5">Plaćanje</dt>
                                                    <dd class="col-sm-7"><?php echo htmlspecialchars(formatInvoicePayment($invoice['payment'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd>
                                                    <dt class="col-sm-5">Fiskalizacija</dt>
                                                    <dd class="col-sm-7"><?php echo !empty($invoice['jir']) && !empty($invoice['fiskaled_at']) ? 'Da' : 'Ne'; ?></dd>
                                                    <dt class="col-sm-5">JIR</dt>
                                                    <dd class="col-sm-7"><?php echo htmlspecialchars((string)($invoice['jir'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd>
                                                    <dt class="col-sm-5">Vrijeme fiskalizacije</dt>
                                                    <dd class="col-sm-7"><?php echo htmlspecialchars(formatInvoiceDateTimeValue($invoice['fiskaled_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd>
                                                    <dt class="col-sm-5">Napomena</dt>
                                                    <dd class="col-sm-7"><?php echo htmlspecialchars((string)($invoice['remark'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card p-3 mt-4">
                                        <h3 class="h6 mb-3">Artikli</h3>
                                        <?php if (!empty($invoiceArticles)) { ?>
                                            <div class="table-responsive">
                                                <table class="table table-sm align-middle mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Oznaka</th>
                                                            <th>Količina</th>
                                                            <th>Osnovica</th>
                                                            <th>PDV</th>
                                                            <th>Jedinica</th>
                                                            <th>Popust</th>
                                                            <th>Napojnica</th>
                                                            <th>Konačna cijena</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($invoiceArticles as $article) { ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars((string)($article['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td><?php echo htmlspecialchars((string)($article['amount'] ?? '0'), ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td><?php echo htmlspecialchars(formatInvoiceMoney($article['retail_price'] ?? 0) . ' EUR', ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td><?php echo htmlspecialchars((string)($article['vat_rate'] ?? '0') . '%', ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td><?php echo htmlspecialchars((string)($article['unit'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td><?php echo htmlspecialchars((string)($article['discount'] ?? '0') . '%', ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td><?php echo htmlspecialchars(formatInvoiceMoney($article['tip_price'] ?? 0) . ' EUR', ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td><?php echo htmlspecialchars(formatInvoiceMoney($article['final_price'] ?? 0) . ' EUR', ENT_QUOTES, 'UTF-8'); ?></td>
                                                            </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php } else { ?>
                                            <p class="text-muted mb-0">Nisu pronađeni artikli za ovaj račun.</p>
                                        <?php } ?>
                                    </div>

                                    <div class="row g-3 mt-4 align-items-stretch">
                                        <?php if ($issuerNotInPdv) { ?>
                                            <div class="col-md-6">
                                                <div class="card p-3 h-100">
                                                    <h3 class="h6 mb-3">Napomena o PDV-u</h3>
                                                    <p class="mb-0">Subjekt nije u sustavu PDV-a prema čl. 90. st. 2 Zakona o PDV-u, PDV nije obračunat</p>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <div class="<?php echo $issuerNotInPdv ? 'col-md-6' : 'col-12'; ?>">
                                            <div class="card p-3 h-100">
                                                <h3 class="h6 mb-3 text-end">Ukupni iznosi</h3>
                                                <dl class="row mb-0">
                                                    <dt class="col-sm-6 text-sm-end">Osnovica</dt>
                                                    <dd class="col-sm-6 text-sm-end"><?php echo htmlspecialchars(formatInvoiceMoney($invoice['netto_price'] ?? 0) . ' EUR', ENT_QUOTES, 'UTF-8'); ?></dd>
                                                    <dt class="col-sm-6 text-sm-end">Iznos PDV-a</dt>
                                                    <dd class="col-sm-6 text-sm-end"><?php echo htmlspecialchars(formatInvoiceMoney($invoice['vat_amount'] ?? 0) . ' EUR', ENT_QUOTES, 'UTF-8'); ?></dd>
                                                    <dt class="col-sm-6 text-sm-end">Ukupna cijena</dt>
                                                    <dd class="col-sm-6 text-sm-end fw-semibold"><?php echo htmlspecialchars(formatInvoiceMoney($invoice['total_price'] ?? 0) . ' EUR', ENT_QUOTES, 'UTF-8'); ?></dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <p class="text-muted mb-0">Nisu pronađeni računi.</p>
            <?php } ?>
        </div>
    </main>

    <?php require '../includes/footer.php'; ?>

    <script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main1.js?v=9"></script>
</body>
</html>


