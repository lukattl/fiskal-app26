<?php if (!empty($partners)) { ?>
    <?php foreach ($partners as $partner) { ?>
        <div class="modal fade" id="editPartnerModal-<?php echo (int)($partner['id'] ?? 0); ?>" tabindex="-1" aria-labelledby="editPartnerModalLabel-<?php echo (int)($partner['id'] ?? 0); ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form data-json-form data-endpoint="../api/update-partner.php" data-success-message="Partner updated successfully.">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="editPartnerModalLabel-<?php echo (int)($partner['id'] ?? 0); ?>">Edit Partner</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert d-none" data-form-message role="alert"></div>
                            <input type="hidden" name="id" value="<?php echo (int)($partner['id'] ?? 0); ?>">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="partner-full-name-<?php echo (int)($partner['id'] ?? 0); ?>">Full Name</label>
                                    <input class="form-control" type="text" id="partner-full-name-<?php echo (int)($partner['id'] ?? 0); ?>" name="full_name" value="<?php echo htmlspecialchars((string)($partner['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="partner-address-<?php echo (int)($partner['id'] ?? 0); ?>">Address</label>
                                    <input class="form-control" type="text" id="partner-address-<?php echo (int)($partner['id'] ?? 0); ?>" name="address" value="<?php echo htmlspecialchars((string)($partner['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="partner-city-<?php echo (int)($partner['id'] ?? 0); ?>">City</label>
                                    <input class="form-control" type="text" id="partner-city-<?php echo (int)($partner['id'] ?? 0); ?>" name="city" value="<?php echo htmlspecialchars((string)($partner['city'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="partner-country-<?php echo (int)($partner['id'] ?? 0); ?>">Country</label>
                                    <input class="form-control" type="text" id="partner-country-<?php echo (int)($partner['id'] ?? 0); ?>" name="country" value="<?php echo htmlspecialchars((string)($partner['country'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="partner-oib-<?php echo (int)($partner['id'] ?? 0); ?>">OIB</label>
                                    <input class="form-control" type="text" id="partner-oib-<?php echo (int)($partner['id'] ?? 0); ?>" name="oib" value="<?php echo htmlspecialchars((string)($partner['oib'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="partner-email-<?php echo (int)($partner['id'] ?? 0); ?>">Email</label>
                                    <input class="form-control" type="email" id="partner-email-<?php echo (int)($partner['id'] ?? 0); ?>" name="email" value="<?php echo htmlspecialchars((string)($partner['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Partner</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php } ?>
<?php } ?>
