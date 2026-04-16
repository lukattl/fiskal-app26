<?php if (!empty($customers)) { ?>
    <?php foreach ($customers as $customer) { ?>
        <div class="modal fade" id="editCustomerModal-<?php echo (int)($customer['id'] ?? 0); ?>" tabindex="-1" aria-labelledby="editCustomerModalLabel-<?php echo (int)($customer['id'] ?? 0); ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form data-json-form data-endpoint="../api/update-customer.php" data-success-message="Customer updated successfully.">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="editCustomerModalLabel-<?php echo (int)($customer['id'] ?? 0); ?>">Uredi kupca</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert d-none" data-form-message role="alert"></div>
                            <input type="hidden" name="id" value="<?php echo (int)($customer['id'] ?? 0); ?>">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="customer-full-name-<?php echo (int)($customer['id'] ?? 0); ?>">Puno ime</label>
                                    <input class="form-control" type="text" id="customer-full-name-<?php echo (int)($customer['id'] ?? 0); ?>" name="full_name" value="<?php echo htmlspecialchars((string)($customer['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="customer-address-<?php echo (int)($customer['id'] ?? 0); ?>">Adresa</label>
                                    <input class="form-control" type="text" id="customer-address-<?php echo (int)($customer['id'] ?? 0); ?>" name="address" value="<?php echo htmlspecialchars((string)($customer['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="customer-city-<?php echo (int)($customer['id'] ?? 0); ?>">Grad</label>
                                    <input class="form-control" type="text" id="customer-city-<?php echo (int)($customer['id'] ?? 0); ?>" name="city" value="<?php echo htmlspecialchars((string)($customer['city'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="customer-country-<?php echo (int)($customer['id'] ?? 0); ?>">Država</label>
                                    <input class="form-control" type="text" id="customer-country-<?php echo (int)($customer['id'] ?? 0); ?>" name="country" value="<?php echo htmlspecialchars((string)($customer['country'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="customer-oib-<?php echo (int)($customer['id'] ?? 0); ?>">OIB</label>
                                    <input class="form-control" type="text" id="customer-oib-<?php echo (int)($customer['id'] ?? 0); ?>" name="oib" value="<?php echo htmlspecialchars((string)($customer['oib'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="customer-email-<?php echo (int)($customer['id'] ?? 0); ?>">Email</label>
                                    <input class="form-control" type="email" id="customer-email-<?php echo (int)($customer['id'] ?? 0); ?>" name="email" value="<?php echo htmlspecialchars((string)($customer['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="customer-legal-<?php echo (int)($customer['id'] ?? 0); ?>" name="legal" value="1" <?php echo !empty($customer['legal']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="customer-legal-<?php echo (int)($customer['id'] ?? 0); ?>">Pravna osoba / Poduzeće</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Odustani</button>
                            <button type="submit" class="btn btn-primary">Spremi kupca</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php } ?>
<?php } ?>
