<?php
require '../core/init.php';
$user = Helper::requireAuth();
$company = Helper::currentCompany();
$bunitId = Helper::currentBusinessUnitId();
$db = DB::getInstance();
$priceListRows = [];
$customers = [];
$bunit = [];
$invoicePayments = ['cash_payment' => 'Gotovina', 'card_payment' => 'Kartica', 'transactional_payment' => 'Transakcijski'];
$nextInvoiceNumber = 1;
$defaultInvoiceDate = date('Y-m-d');
$defaultDueDate = date('Y-m-d', strtotime('+15 days'));
$lastIssuedInvoiceDate = '';
$invoicePrefill = null;
$fullName = htmlspecialchars($user['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
$pageTitle = 'New Invoice - fiskal-app';
$pageKey = 'new-invoice';
if (!empty($company['id'])) {
    $priceListQuery = $db->query('SELECT * FROM price_list WHERE company_id = ?', [$company['id']]);
    if (!$priceListQuery->getError() && $priceListQuery->getResults()) {
        $priceListRows = Helper::toArray($priceListQuery->getResults());
    }
    $customersQuery = $db->query('SELECT * FROM customers');
    if (!$customersQuery->getError() && $customersQuery->getResults()) {
        $customers = Helper::toArray($customersQuery->getResults());
    }
}
if (!empty($bunitId)) {
    $bunitQuery = $db->query('SELECT * FROM business_units WHERE id = ?', [$bunitId], 1);
    if (!$bunitQuery->getError() && $bunitQuery->getResults()) {
        $bunit = Helper::toArray($bunitQuery->getFirst());
        $nextInvoiceNumber = ((int)($bunit['invoice_counter'] ?? 0)) + 1;
    }
    $bunitOptionsQuery = $db->query('SELECT * FROM bunit_options WHERE bunit_id = ?', [$bunitId], 1);
    if (!$bunitOptionsQuery->getError() && $bunitOptionsQuery->getResults()) {
        $bunitOptions = Helper::toArray($bunitOptionsQuery->getFirst());
        if (!empty($bunitOptions['card_payment'])) {
            $invoicePayments['card_payment'] = 'Kartica';
        }
        if (!empty($bunitOptions['transactional_payment'])) {
            $invoicePayments['transactional_payment'] = 'Transakcijski';
        }
    }

    $lastIssuedInvoiceQuery = $db->query(
        'SELECT DATE(insert_time) AS invoice_date FROM invoices WHERE company_id = ? AND bunit_id = ? ORDER BY insert_time DESC, id DESC',
        [$company['id'] ?? 0, $bunitId],
        1
    );
    if (!$lastIssuedInvoiceQuery->getError() && $lastIssuedInvoiceQuery->getResults()) {
        $lastIssuedInvoiceRow = Helper::toArray($lastIssuedInvoiceQuery->getFirst());
        $lastIssuedInvoiceDate = (string)($lastIssuedInvoiceRow['invoice_date'] ?? '');
    }
}

$sourceInvoiceId = (int)($_GET['storno_invoice_id'] ?? 0);
$prefillMode = $sourceInvoiceId > 0 ? 'storno' : '';

if ($sourceInvoiceId <= 0) {
    $sourceInvoiceId = (int)($_GET['copy_invoice_id'] ?? 0);
    if ($sourceInvoiceId > 0) {
        $prefillMode = 'copy';
    }
}

if (!empty($company['id']) && $sourceInvoiceId > 0 && $prefillMode !== '') {
    $sourceInvoiceQuery = $db->query('SELECT * FROM invoices WHERE id = ? AND company_id = ? LIMIT 1', [$sourceInvoiceId, $company['id']]);
    if (!$sourceInvoiceQuery->getError() && $sourceInvoiceQuery->getResults()) {
        $sourceInvoice = Helper::toArray($sourceInvoiceQuery->getFirst());

        $sourceCustomer = [];
        if (!empty($sourceInvoice['customer_id'])) {
            $sourceCustomerQuery = $db->query('SELECT * FROM customers WHERE id = ? AND company_id = ? LIMIT 1', [(int)$sourceInvoice['customer_id'], $company['id']]);
            if (!$sourceCustomerQuery->getError() && $sourceCustomerQuery->getResults()) {
                $sourceCustomer = Helper::toArray($sourceCustomerQuery->getFirst());
            }
        }

        $sourceArticles = [];
        $sourceInvoiceArticlesQuery = $db->query('SELECT * FROM invoice_articles WHERE invoice_id = ? ORDER BY id ASC', [$sourceInvoiceId]);
        if (!$sourceInvoiceArticlesQuery->getError() && $sourceInvoiceArticlesQuery->getResults()) {
            foreach (Helper::toArray($sourceInvoiceArticlesQuery->getResults()) as $sourceArticle) {
                $priceListMatch = null;

                foreach ($priceListRows as $priceListRow) {
                    if ((string)($priceListRow['label'] ?? '') === (string)($sourceArticle['label'] ?? '')) {
                        $priceListMatch = $priceListRow;
                        break;
                    }
                }

                if ($priceListMatch === null) {
                    continue;
                }

                $articleAmount = abs((int)($sourceArticle['amount'] ?? 0));
                $articleTipPrice = abs((int)round((float)($sourceArticle['tip_price'] ?? 0)));

                if ($prefillMode === 'storno') {
                    $articleAmount *= -1;
                    $articleTipPrice *= -1;
                }

                $sourceArticles[] = [
                    'id' => (int)($priceListMatch['id'] ?? 0),
                    'amount' => $articleAmount,
                    'discount' => (int)($sourceArticle['discount'] ?? 0),
                    'tip_price' => $articleTipPrice,
                ];
            }
        }

        $prefillDueDateValue = $prefillMode === 'storno'
            ? date('Y-m-d')
            : (string)($sourceInvoice['due_date'] ?? $defaultDueDate);
        $prefillRemarkValue = $prefillMode === 'storno'
            ? 'Storno racuna ' . (string)($sourceInvoice['number'] ?? '')
            : (string)($sourceInvoice['remark'] ?? '');

        $invoicePrefill = [
            'mode' => $prefillMode,
            'source_invoice_id' => $sourceInvoiceId,
            'customer' => [
                'full_name' => (string)($sourceCustomer['full_name'] ?? ''),
                'legal' => (int)($sourceCustomer['legal'] ?? 0),
                'address' => (string)($sourceCustomer['address'] ?? ''),
                'city' => (string)($sourceCustomer['city'] ?? ''),
                'country' => (string)($sourceCustomer['country'] ?? ''),
                'oib' => (string)($sourceCustomer['oib'] ?? ''),
                'email' => (string)($sourceCustomer['email'] ?? ''),
            ],
            'payment' => (string)($sourceInvoice['payment'] ?? ''),
            'invoice_date' => $defaultInvoiceDate,
            'due_date' => $prefillDueDateValue,
            'remark' => $prefillRemarkValue,
            'articles' => $sourceArticles,
        ];
    }
}

$prefillCustomer = $invoicePrefill['customer'] ?? [];
$prefillPayment = (string)($invoicePrefill['payment'] ?? '');
$selectedInvoicePayment = $prefillPayment === 'cash' ? 'cash_payment'
    : ($prefillPayment === 'card' ? 'card_payment'
    : ($prefillPayment === 'transaction' ? 'transactional_payment' : 'cash_payment'));

if (!isset($invoicePayments[$selectedInvoicePayment])) {
    $selectedInvoicePayment = 'cash_payment';
}

$prefillInvoiceDate = (string)($invoicePrefill['invoice_date'] ?? $defaultInvoiceDate);
$prefillDueDate = (string)($invoicePrefill['due_date'] ?? $defaultDueDate);
$prefillRemark = (string)($invoicePrefill['remark'] ?? '');
$prefillFiskalTypeCode = ((int)($prefillCustomer['legal'] ?? 0) > 0) ? 'F2' : 'F1';
$invoicePrefillJson = json_encode($invoicePrefill, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
if ($invoicePrefillJson === false) {
    $invoicePrefillJson = 'null';
}
require '../includes/header.php';
?>
<main class="container-fluid px-3 px-xl-4 pt-0 pb-4">
    <form id="newInvoiceForm" data-invoice-form data-endpoint="../api/create-invoice.php" data-update-endpoint="../api/update-invoice.php" data-last-issued-date="<?php echo htmlspecialchars($lastIssuedInvoiceDate, ENT_QUOTES, 'UTF-8'); ?>" class="needs-validation" novalidate>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div class="d-flex gap-2 align-items-center flex-wrap">
                <a class="btn btn-outline-secondary btn-sm rounded-pill px-3" href="invoices.php">Svi računi</a>
                <button class="btn btn-outline-primary btn-sm rounded-pill px-3 d-none" type="button" id="saveInvoiceChangesButton">Spremi izmjene</button>
                </div>
                <h1 class="invoice-bar__title h4 mb-0 text-primary fw-bold">Račun br. <span data-invoice-number-label><?php echo htmlspecialchars((string)$nextInvoiceNumber, ENT_QUOTES, 'UTF-8'); ?></span> - Koncept</h1>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                <button class="btn btn-primary btn-sm rounded-pill px-4" type="submit">Izdaj račun</button>
                </div>
            </div>
        </div>
        <div>
            <div class="alert d-none mb-3" id="invoiceFormMessage" role="alert"></div>
            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-6">
                    <section class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center gap-3 py-3">
                            <span class="fw-semibold">Kupac</span>
                            <button class="btn btn-outline-primary btn-sm rounded-pill px-3" type="button" data-bs-toggle="collapse" data-bs-target="#invoiceCustomerDetails" aria-expanded="false" aria-controls="invoiceCustomerDetails">Detalji</button>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 align-items-end">
                                <div class="col-lg-6">
                                    <label class="form-label fw-semibold small text-uppercase text-secondary mb-1" for="invoice-customer-full-name">Puno ime</label>
                                    <input class="form-control" type="text" id="invoice-customer-full-name" name="customer_full_name" list="invoice-customer-names" autocomplete="off" placeholder="Upišite ili odaberite kupca..." value="<?php echo htmlspecialchars((string)($prefillCustomer['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <datalist id="invoice-customer-names"><?php foreach ($customers as $customer) { ?><option value="<?php echo htmlspecialchars((string)($customer['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></option><?php } ?></datalist>
                                </div>
                                <div class="col-lg-3">
                                    <label class="form-label fw-semibold small text-uppercase text-secondary mb-1" for="invoice-customer-oib">OIB</label>
                                    <input class="form-control" type="text" id="invoice-customer-oib" name="customer_oib" list="invoice-customer-oibs" autocomplete="off" inputmode="numeric" maxlength="11" pattern="\d{11}" value="<?php echo htmlspecialchars((string)($prefillCustomer['oib'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <datalist id="invoice-customer-oibs"><?php foreach ($customers as $customer) { ?><option value="<?php echo htmlspecialchars((string)($customer['oib'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></option><?php } ?></datalist>
                                </div>
                                <div class="col-lg-3">
                                <div class="d-flex flex-wrap gap-3 border rounded-3 px-3 py-2 bg-light">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="invoice-customer-legal" name="customer_legal" value="1" <?php echo (string)($prefillCustomer['legal'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="invoice-customer-legal">Pravna osoba</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="invoice-customer-government" name="customer_government" value="1" <?php echo (string)($prefillCustomer['legal'] ?? '0') === '2' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="invoice-customer-government">Država</label>
                                    </div>
                                </div>
                                </div>
                            </div>
                           
                            <div class="collapse mt-3" id="invoiceCustomerDetails">
                                <div class="row g-3">
                                    <div class="col-md-6 col-xl-3"><label class="form-label fw-semibold small text-uppercase text-secondary mb-1" for="invoice-customer-address">Adresa</label><input class="form-control" type="text" id="invoice-customer-address" name="customer_address" value="<?php echo htmlspecialchars((string)($prefillCustomer['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                                    <div class="col-md-6 col-xl-3"><label class="form-label fw-semibold small text-uppercase text-secondary mb-1" for="invoice-customer-city">Grad</label><input class="form-control" type="text" id="invoice-customer-city" name="customer_city" value="<?php echo htmlspecialchars((string)($prefillCustomer['city'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                                    <div class="col-md-6 col-xl-3"><label class="form-label fw-semibold small text-uppercase text-secondary mb-1" for="invoice-customer-country">Država</label><input class="form-control" type="text" id="invoice-customer-country" name="customer_country" value="<?php echo htmlspecialchars((string)($prefillCustomer['country'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                                    <div class="col-md-6 col-xl-3"><label class="form-label fw-semibold small text-uppercase text-secondary mb-1" for="invoice-customer-email">Email</label><input class="form-control" type="email" id="invoice-customer-email" name="customer_email" value="<?php echo htmlspecialchars((string)($prefillCustomer['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="col-12 col-md-6 col-xl-6">
                    <section class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center gap-3 py-3"><span class="fw-semibold">Zagreb, datum <span data-invoice-date-heading><?php echo htmlspecialchars(date('d.m.Y', strtotime($prefillInvoiceDate)), ENT_QUOTES, 'UTF-8'); ?></span></span><span class="badge rounded-pill text-bg-primary"><span data-invoice-customer-type-code><?php echo $prefillFiskalTypeCode; ?></span> R1</span></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6 col-xl-3"><label class="form-label fw-semibold small text-uppercase text-secondary mb-1" for="invoice-number-preview">Broj računa</label><input class="form-control" type="text" id="invoice-number-preview" value="<?php echo htmlspecialchars((string)$nextInvoiceNumber, ENT_QUOTES, 'UTF-8'); ?>" readonly></div>
                                <div class="col-md-6 col-xl-3"><label class="form-label fw-semibold small text-uppercase text-secondary mb-1" for="invoice-payment">Plaćanje</label><select class="form-select" id="invoice-payment" name="payment"><?php foreach ($invoicePayments as $paymentValue => $paymentLabel) { ?><option value="<?php echo htmlspecialchars($paymentValue, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selectedInvoicePayment === $paymentValue ? 'selected' : ''; ?>><?php echo htmlspecialchars($paymentLabel, ENT_QUOTES, 'UTF-8'); ?></option><?php } ?></select></div>
                                <div class="col-md-6 col-xl-3"><label class="form-label fw-semibold small text-uppercase text-secondary mb-1" for="invoice-date">Datum računa</label><input class="form-control" type="date" id="invoice-date" name="invoice_date" value="<?php echo htmlspecialchars($prefillInvoiceDate, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $lastIssuedInvoiceDate !== '' ? 'min="' . htmlspecialchars($lastIssuedInvoiceDate, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>></div>
                                <div class="col-md-6 col-xl-3"><label class="form-label fw-semibold small text-uppercase text-secondary mb-1" for="invoice-due-date">Datum dospijeća</label><input class="form-control" type="date" id="invoice-due-date" name="due_date" value="<?php echo htmlspecialchars($prefillDueDate, ENT_QUOTES, 'UTF-8'); ?>"></div>
                            </div>
                            <?php if ($lastIssuedInvoiceDate !== '') { ?>
                                <div class="form-text mt-2">Najraniji dozvoljeni datum izdavanja je <?php echo htmlspecialchars(date('d.m.Y', strtotime($lastIssuedInvoiceDate)), ENT_QUOTES, 'UTF-8'); ?>.</div>
                            <?php } ?>
                            <div class="list-group list-group-flush small mt-3">
                                <div class="list-group-item px-0 d-flex justify-content-between gap-3"><span class="text-secondary">Dospijeće</span><strong data-invoice-due-date-label><?php echo htmlspecialchars(date('d.m.Y', strtotime($prefillDueDate)), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                <div class="list-group-item px-0 d-flex justify-content-between gap-3"><span class="text-secondary">Poslovna jedinica</span><strong><?php echo htmlspecialchars((string)($bunit['name'] ?? $bunit['label'] ?? 'Default unit'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                <div class="list-group-item px-0 d-flex justify-content-between gap-3"><span class="text-secondary">Tip računa</span><strong>Maloprodajni račun</strong></div>
                                <div class="list-group-item px-0 d-flex justify-content-between gap-3"><span class="text-secondary">Jezik</span><strong>HR</strong></div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
            <section class="card shadow-sm border-0 mt-3">
                <div class="card-header bg-white py-3"><span class="fw-semibold">Artikli</span></div>
                <div class="card-body">
                    <?php if (!empty($priceListRows)) { ?>
                        <div class="row g-3 align-items-end mb-3">
                            <div class="col-lg-8 position-relative">
                                <label class="form-label fw-semibold small text-uppercase text-secondary mb-1" for="invoice-article-search">Pretraži artikal</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">🔎</span>
                                    <input class="form-control border-start-0" type="text" id="invoice-article-search" autocomplete="off" placeholder="Pretraži artikal po nazivu">
                                </div>
                                <div class="list-group position-absolute start-0 end-0 mt-1 shadow-sm d-none" id="invoice-article-suggestions" style="z-index:1055;max-height:220px;overflow-y:auto;"></div>
                            </div>
                            <div class="col-lg-4 d-grid d-lg-flex justify-content-lg-end">
                                <button class="btn btn-primary rounded-pill px-4" type="button" id="invoiceSecondaryAddArticleButton">Dodaj artikal</button>
                            </div>
                        </div>
                        <div class="table-responsive border rounded-4">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light"><tr><th>#</th><th>Artikl / Usluga</th><th>Kol</th><th>Jedinica</th><th>Cijena bez PDV-a</th><th>Popust %</th><th>PDV %</th><th>Napojnica</th><th>Ukupno</th><th class="text-end"></th></tr></thead>
                                <tbody id="invoiceArticlesTableBody"></tbody>
                            </table>
                        </div>
                        <template id="invoiceArticleRowTemplate">
                            <tr data-invoice-article-row data-article-id="">
                                <td data-article-index class="text-muted">1</td>
                                <td><div class="invoice-article-label" data-article-label></div></td>
                                <td><input class="form-control form-control-sm" type="number" step="1" value="1" data-article-amount></td>
                                <td><span class="small text-muted" data-article-unit-label></span><input type="hidden" data-article-unit value=""></td>
                                <td data-article-retail-price></td>
                                <td><input class="form-control form-control-sm" type="number" min="0" max="100" step="1" value="0" data-article-discount></td>
                                <td data-article-vat-rate></td>
                                <td><input class="form-control form-control-sm" type="number" step="1" value="0" data-article-tip></td>
                                <td><span class="fw-semibold" data-article-final-price>0.00</span></td>
                                <td class="text-end"><button class="btn btn-outline-danger btn-sm" type="button" data-remove-article>X</button></td>
                            </tr>
                        </template>
                    <?php } else { ?><p class="text-muted mb-0">Nema pronađenih artikla</p><?php } ?>
                    <div class="row g-3 mt-1">
                        <div class="col-lg-4">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-body">Artikli: <strong data-invoice-item-count>0</strong></div>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between gap-3 py-1"><span>Ukupno bez PDV-a</span><span data-invoice-total-base>0.00 EUR</span></div>
                                    <div class="d-flex justify-content-between gap-3 py-1"><span>PDV iznos</span><span data-invoice-total-vat>0.00 EUR</span></div>
                                    <div class="d-flex justify-content-between gap-3 py-1 fw-bold fs-5"><span>Iznos</span><span data-invoice-grand-total>0.00 EUR</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div><label class="form-label fw-semibold small text-uppercase text-secondary mb-1" for="invoice-ending-note">Završni tekst</label><textarea class="form-control" id="invoice-ending-note" rows="4"><?php echo htmlspecialchars($prefillRemark, ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                    </div>
                </div>
            </section>
        </div>
    </form>
</main>
<script id="invoiceCustomersData" type="application/json"><?php echo json_encode($customers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?></script>
<script id="invoiceArticlesData" type="application/json"><?php echo json_encode($priceListRows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?></script>
<script>window.invoicePrefillData = <?php echo $invoicePrefillJson; ?>;</script>
<script id="invoicePrefillData" type="application/json"><?php echo $invoicePrefillJson; ?></script>
<?php require '../includes/footer.php'; ?>
<script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main1.js?v=11"></script>
</body>
</html>



