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
$invoicePrefill = null;
$fullName = htmlspecialchars($user['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
$pageTitle = 'New Invoice - fiskal-app';
$pageKey = '';
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
                'legal' => !empty($sourceCustomer['legal']) ? 1 : 0,
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
$invoicePrefillJson = json_encode($invoicePrefill, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
if ($invoicePrefillJson === false) {
    $invoicePrefillJson = 'null';
}
require '../includes/header.php';
?>
<main class="invoice-page">
    <style>
        .invoice-page{width:100%;padding:0}.invoice-editor{background:#eef4fb;border-top:1px solid #c9d8ea;border-bottom:1px solid #c9d8ea}.invoice-bar{display:flex;justify-content:space-between;align-items:center;gap:.75rem;padding:.75rem 1rem;background:linear-gradient(180deg,#fbfdff 0%,#e8f0fb 100%);border-bottom:1px solid #c9d8ea}.invoice-bar__group{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap}.invoice-bar__title{margin:0;color:#0e4f92;font-size:1.2rem;font-weight:700}.invoice-wrap{padding:1rem}.invoice-box{background:#fff;border:1px solid #c8d8ed}.invoice-box__head{display:flex;justify-content:space-between;align-items:center;gap:.75rem;padding:.5rem .75rem;background:#d9e5f3;border-bottom:1px solid #c8d8ed;font-weight:600;color:#243b5f}.invoice-box__body{padding:.75rem}.invoice-customer-grid{display:grid;grid-template-columns:minmax(260px,2fr) minmax(180px,1fr) auto;gap:.75rem;align-items:end}.invoice-details-grid,.invoice-meta-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem}.invoice-field label{display:block;margin-bottom:.22rem;font-size:.78rem;color:#61748e;font-weight:700;text-transform:uppercase;letter-spacing:.03em}.invoice-field--plain label{text-transform:none;letter-spacing:0;font-size:.95rem;font-weight:500;color:#243b5f}.invoice-field input,.invoice-field select,.invoice-note{border-radius:0;border-color:#b9cade;min-height:38px}.invoice-field input[type="date"]{padding-right:.75rem}.invoice-note{min-height:120px;resize:vertical}.invoice-hint{margin:.7rem 0 0;color:#7a8aa0}.invoice-section{margin-top:1rem}.invoice-search{display:grid;grid-template-columns:minmax(280px,1.8fr) auto;gap:.75rem;align-items:end;margin-bottom:.75rem}.invoice-table-wrap{border:1px solid #c8d8ed;background:#fff}.invoice-table{margin-bottom:0;min-width:1040px}.invoice-table thead th{background:#d6d6d6;color:#233856;border-bottom:1px solid #bcc9d9;white-space:nowrap}.invoice-table .form-control{border-radius:0;min-height:34px}.invoice-article-label{font-weight:600;color:#1d3659}.invoice-actions,.invoice-totals{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap}.invoice-summary{margin-left:auto;min-width:320px;background:#fff;border:1px solid #c8d8ed;padding:.75rem 1rem}.invoice-summary__row{display:flex;justify-content:space-between;gap:1rem;padding:.25rem 0;color:#243b5f}.invoice-summary__row--strong{font-size:1.1rem;font-weight:700}.invoice-control{background:#fff;border:1px solid #c8d8ed;padding:.75rem 1rem;min-width:250px}@media (max-width:1199px){.invoice-customer-grid,.invoice-details-grid,.invoice-meta-grid,.invoice-search{grid-template-columns:1fr}}
    </style>
    <form id="newInvoiceForm" data-invoice-form data-endpoint="../api/create-invoice.php" data-update-endpoint="../api/update-invoice.php" class="invoice-editor" novalidate>
        <div class="invoice-bar">
            <div class="invoice-bar__group">
                <a class="btn btn-outline-secondary btn-sm" href="dashboard.php">Natrag</a>

                <button class="btn btn-outline-primary btn-sm d-none" type="button" id="saveInvoiceChangesButton">Spremi izmjene</button>
            </div>
            <h1 class="invoice-bar__title">Račun br. <span data-invoice-number-label><?php echo htmlspecialchars((string)$nextInvoiceNumber, ENT_QUOTES, 'UTF-8'); ?></span> - Koncept</h1>
            <div class="invoice-bar__group">
                <button class="btn btn-primary btn-sm" type="submit">Izdaj račun</button>
            </div>
        </div>
        <div class="invoice-wrap">
            <div class="alert d-none mb-3" id="invoiceFormMessage" role="alert"></div>
            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-6">
                    <section class="invoice-box h-100">
                        <div class="invoice-box__head"><span>Kupac</span><button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#invoiceCustomerDetails" aria-expanded="false" aria-controls="invoiceCustomerDetails">Detalji</button></div>
                        <div class="invoice-box__body">
                            <div class="invoice-customer-grid">
                                <div class="invoice-field invoice-field--plain">
                                    <label for="invoice-customer-full-name">Puno ime</label>
                                    <input class="form-control" type="text" id="invoice-customer-full-name" name="customer_full_name" list="invoice-customer-names" autocomplete="off" placeholder="Upišite ili odaberite kupca..." value="<?php echo htmlspecialchars((string)($prefillCustomer['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <datalist id="invoice-customer-names"><?php foreach ($customers as $customer) { ?><option value="<?php echo htmlspecialchars((string)($customer['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></option><?php } ?></datalist>
                                </div>
                                <div class="invoice-field invoice-field--plain">
                                    <label for="invoice-customer-oib">OIB</label>
                                    <input class="form-control" type="text" id="invoice-customer-oib" name="customer_oib" list="invoice-customer-oibs" autocomplete="off" inputmode="numeric" maxlength="11" pattern="\d{11}" value="<?php echo htmlspecialchars((string)($prefillCustomer['oib'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <datalist id="invoice-customer-oibs"><?php foreach ($customers as $customer) { ?><option value="<?php echo htmlspecialchars((string)($customer['oib'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></option><?php } ?></datalist>
                                </div>
                                <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" id="invoice-customer-legal" name="customer_legal" value="1" <?php echo !empty($prefillCustomer['legal']) ? 'checked' : ''; ?>><label class="form-check-label" for="invoice-customer-legal">Pravna osoba</label></div>
                            </div>
                           
                            <div class="collapse mt-3" id="invoiceCustomerDetails">
                                <div class="invoice-details-grid">
                                    <div class="invoice-field"><label for="invoice-customer-address">Adresa</label><input class="form-control" type="text" id="invoice-customer-address" name="customer_address" value="<?php echo htmlspecialchars((string)($prefillCustomer['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                                    <div class="invoice-field"><label for="invoice-customer-city">Grad</label><input class="form-control" type="text" id="invoice-customer-city" name="customer_city" value="<?php echo htmlspecialchars((string)($prefillCustomer['city'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                                    <div class="invoice-field"><label for="invoice-customer-country">Država</label><input class="form-control" type="text" id="invoice-customer-country" name="customer_country" value="<?php echo htmlspecialchars((string)($prefillCustomer['country'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                                    <div class="invoice-field"><label for="invoice-customer-email">Email</label><input class="form-control" type="email" id="invoice-customer-email" name="customer_email" value="<?php echo htmlspecialchars((string)($prefillCustomer['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="col-12 col-md-6 col-xl-6">
                    <section class="invoice-box h-100">
                        <div class="invoice-box__head"><span>Zagreb, datum <span data-invoice-date-heading><?php echo htmlspecialchars(date('d.m.Y', strtotime($defaultInvoiceDate)), ENT_QUOTES, 'UTF-8'); ?></span></span><span><span data-invoice-customer-type-code>F2</span> R1</span></div>
                        <div class="invoice-box__body">
                            <div class="invoice-meta-grid">
                                <div class="invoice-field"><label for="invoice-number-preview">Broj računa</label><input class="form-control" type="text" id="invoice-number-preview" value="<?php echo htmlspecialchars((string)$nextInvoiceNumber, ENT_QUOTES, 'UTF-8'); ?>" readonly></div>
                                <div class="invoice-field"><label for="invoice-payment">Plaćanje</label><select class="form-select" id="invoice-payment" name="payment"><?php foreach ($invoicePayments as $paymentValue => $paymentLabel) { ?><option value="<?php echo htmlspecialchars($paymentValue, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selectedInvoicePayment === $paymentValue ? 'selected' : ''; ?>><?php echo htmlspecialchars($paymentLabel, ENT_QUOTES, 'UTF-8'); ?></option><?php } ?></select></div>
                                <div class="invoice-field"><label for="invoice-date">Datum računa</label><input class="form-control" type="date" id="invoice-date" name="invoice_date" value="<?php echo htmlspecialchars($prefillInvoiceDate, ENT_QUOTES, 'UTF-8'); ?>"></div>
                                <div class="invoice-field"><label for="invoice-due-date">Datum dospijeća</label><input class="form-control" type="date" id="invoice-due-date" name="due_date" value="<?php echo htmlspecialchars($prefillDueDate, ENT_QUOTES, 'UTF-8'); ?>"></div>
                            </div>
                            <div class="mt-3 text-body-secondary">
                                <div><strong>Dospijeće:</strong> <span data-invoice-due-date-label><?php echo htmlspecialchars(date('d.m.Y', strtotime($prefillDueDate)), ENT_QUOTES, 'UTF-8'); ?></span></div>
                                <div><strong>Poslovna jedinica:</strong> <?php echo htmlspecialchars((string)($bunit['name'] ?? $bunit['label'] ?? 'Default unit'), ENT_QUOTES, 'UTF-8'); ?></div>
                                <div><strong>Tip računa:</strong> Maloprodajni račun - cijene bez PDV-a</div>
                                <div><strong>Jezik:</strong> HR</div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
            <section class="invoice-box invoice-section">
                <div class="invoice-box__head"><span>Artikli</span></div>
                <div class="invoice-box__body">
                    <?php if (!empty($priceListRows)) { ?>
                        <div class="invoice-search">
                            <div class="invoice-field position-relative">
                                <label for="invoice-article-search">Pretraži artikal</label>
                                <input class="form-control" type="text" id="invoice-article-search" autocomplete="off" placeholder="Pretraži artikal po nazivu">
                                <div class="list-group position-absolute start-0 end-0 mt-1 shadow-sm d-none" id="invoice-article-suggestions" style="z-index:1055;max-height:220px;overflow-y:auto;"></div>
                            </div>
                            <button class="btn btn-primary" type="button" id="invoiceSecondaryAddArticleButton">Dodaj artikal</button>
                        </div>
                        <div class="invoice-table-wrap table-responsive">
                            <table class="table table-sm align-middle invoice-table">
                                <thead><tr><th>#</th><th>Artikl / Usluga</th><th>Kol</th><th>Jedinica</th><th>Cijena bez PDV-a</th><th>Popust %</th><th>PDV %</th><th>Napojnica</th><th>Ukupno</th><th class="text-end"></th></tr></thead>
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
                    <div class="invoice-actions mt-3">
                        <div class="invoice-control">Artikli: <strong data-invoice-item-count>0</strong></div>
                        <div class="invoice-summary">
                            <div class="invoice-summary__row"><span>Ukupno bez PDV-a</span><span data-invoice-total-base>0.00 EUR</span></div>
                            <div class="invoice-summary__row"><span>PDV iznos</span><span data-invoice-total-vat>0.00 EUR</span></div>
                            <div class="invoice-summary__row invoice-summary__row--strong"><span>Iznos </span><span data-invoice-grand-total>0.00 EUR</span></div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="invoice-field"><label for="invoice-ending-note">Završni tekst</label><textarea class="form-control invoice-note" id="invoice-ending-note" rows="4"><?php echo htmlspecialchars($prefillRemark, ENT_QUOTES, 'UTF-8'); ?></textarea></div>
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

