<div class="modal fade" id="newInvoiceModal" tabindex="-1" aria-labelledby="newInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form id="newInvoiceForm" data-invoice-form data-endpoint="../api/create-invoice.php">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="newInvoiceModalLabel">New Invoice</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert d-none" id="invoiceFormMessage" role="alert"></div>

                    <div class="card p-3 mb-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label" for="invoice-number-preview">Invoice Number</label>
                                <input class="form-control" type="text" id="invoice-number-preview" value="<?php echo htmlspecialchars((string)$nextInvoiceNumber, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="invoice-payment">Payment</label>
                                <select class="form-select" id="invoice-payment" name="payment" required>
                                    <?php foreach ($invoicePayments as $paymentValue => $paymentLabel) { ?>
                                        <option value="<?php echo htmlspecialchars($paymentValue, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($paymentLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="invoice-insert-time">Insert Time</label>
                                <input class="form-control" type="text" id="invoice-insert-time" value="<?php echo htmlspecialchars(date('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="card p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="h6 mb-0">Customer</h3>
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-muted">Search by full name or OIB to auto-fill</small>
                                <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#invoiceCustomerDetails" aria-expanded="false" aria-controls="invoiceCustomerDetails">
                                    Show/Hide Details
                                </button>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="invoice-customer-full-name">Full Name</label>
                                <input class="form-control" type="text" id="invoice-customer-full-name" name="customer_full_name" list="invoice-customer-names" autocomplete="off" required>
                                <datalist id="invoice-customer-names">
                                    <?php foreach ($customers as $customer) { ?>
                                        <option value="<?php echo htmlspecialchars((string)($customer['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></option>
                                    <?php } ?>
                                </datalist>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="invoice-customer-oib">OIB</label>
                                <input class="form-control" type="text" id="invoice-customer-oib" name="customer_oib" list="invoice-customer-oibs" autocomplete="off">
                                <datalist id="invoice-customer-oibs">
                                    <?php foreach ($customers as $customer) { ?>
                                        <option value="<?php echo htmlspecialchars((string)($customer['oib'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></option>
                                    <?php } ?>
                                </datalist>
                            </div>
                        </div>
                        <div class="collapse mt-3" id="invoiceCustomerDetails">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="invoice-customer-address">Address</label>
                                    <input class="form-control" type="text" id="invoice-customer-address" name="customer_address">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="invoice-customer-city">City</label>
                                    <input class="form-control" type="text" id="invoice-customer-city" name="customer_city">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="invoice-customer-country">Country</label>
                                    <input class="form-control" type="text" id="invoice-customer-country" name="customer_country">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="invoice-customer-email">Email</label>
                                    <input class="form-control" type="email" id="invoice-customer-email" name="customer_email">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="h6 mb-0">Articles</h3>
                            <small class="text-muted">Search and add articles from the price list</small>
                        </div>

                        <?php if (!empty($priceListRows)) { ?>
                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <label class="form-label" for="invoice-article-search">Article</label>
                                    <input class="form-control" type="text" id="invoice-article-search" list="invoice-article-options" autocomplete="off" placeholder="Search article by name">
                                    <datalist id="invoice-article-options">
                                        <?php foreach ($priceListRows as $article) { ?>
                                            <option value="<?php echo htmlspecialchars((string)($article['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></option>
                                        <?php } ?>
                                    </datalist>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button class="btn btn-primary w-100" type="button" id="addInvoiceArticleButton">Add Article</button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Label</th>
                                            <th>Retail Price</th>
                                            <th>VAT</th>
                                            <th style="width: 110px;">Amount</th>
                                            <th style="width: 110px;">Unit</th>
                                            <th style="width: 120px;">Discount %</th>
                                            <th style="width: 120px;">Tip</th>
                                            <th>Final Price</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="invoiceArticlesTableBody"></tbody>
                                </table>
                            </div>
                            <template id="invoiceArticleRowTemplate">
                                <tr data-invoice-article-row data-article-id="">
                                    <td>
                                        <div class="fw-semibold" data-article-label></div>
                                    </td>
                                    <td data-article-retail-price></td>
                                    <td data-article-vat-rate></td>
                                    <td>
                                        <input class="form-control form-control-sm" type="number" min="1" step="1" value="1" data-article-amount>
                                    </td>
                                    <td>
                                        <span class="small text-muted" data-article-unit-label></span>
                                        <input type="hidden" data-article-unit value="">
                                    </td>
                                    <td>
                                        <input class="form-control form-control-sm" type="number" min="0" max="100" step="1" value="0" data-article-discount>
                                    </td>
                                    <td>
                                        <input class="form-control form-control-sm" type="number" min="0" step="1" value="0" data-article-tip>
                                    </td>
                                    <td>
                                        <span class="fw-semibold" data-article-final-price>0.00</span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-outline-danger btn-sm" type="button" data-remove-article>Remove</button>
                                    </td>
                                </tr>
                            </template>
                        <?php } else { ?>
                            <p class="text-muted mb-0">No articles found in the company price list.</p>
                        <?php } ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>
