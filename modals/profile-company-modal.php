<div class="modal fade" id="editCompanyModal" tabindex="-1" aria-labelledby="editCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-lg-down">
        <div class="modal-content" style="max-height: 100vh;">
            <?php
            $companyModalLabels = [
                'full_name' => 'Naziv',
                'short_name' => 'Kratki naziv',
                'address' => 'Adresa',
                'city' => 'Grad',
                'postal_code' => 'Poštanski broj',
                'oib' => 'OIB',
                'pdv' => 'PDV',
                'iban' => 'IBAN',
                'p12_password' => 'P12 lozinka',
            ];
            ?>
            <form class="d-flex flex-column h-100" data-json-form data-endpoint="../api/update-company.php" data-success-message="Tvrtka uspješno ažurirana!" data-error-message="Došlo je do greške prilikom ažuriranja tvrtke. Pokušajte ponovo.">
                <div class="modal-header sticky-top bg-white">
                    <h2 class="modal-title fs-5" id="editCompanyModalLabel">Uređivanje podataka tvrtke</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body overflow-auto">
                    <div class="alert d-none" data-form-message role="alert"></div>
                    <?php if (!empty($companyFields)) { ?>
                        <div class="row g-3">
                            <?php foreach ($companyFields as $field => $value) { ?>
                                <?php $companyFieldLabel = $companyModalLabels[$field] ?? profileLabel($field); ?>
                                <div class="col-md-6">
                                    <?php if ($field === 'created_at') { ?>
                                        <label class="form-label"><?php echo htmlspecialchars($companyFieldLabel, ENT_QUOTES, 'UTF-8'); ?></label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light">
                                            <?php echo htmlspecialchars(formatProfileDate($value), ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php } elseif ($field === 'pdv') { ?>
                                        <div class="form-check form-switch mt-4 pt-2">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                role="switch"
                                                id="company-pdv"
                                                name="pdv"
                                                value="1"
                                                <?php echo isCheckedValue($value) ? 'checked' : ''; ?>
                                            >
                                            <label class="form-check-label" for="company-pdv">PDV</label>
                                        </div>
                                    <?php } else { ?>
                                        <label class="form-label" for="company-<?php echo htmlspecialchars((string)$field, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($companyFieldLabel, ENT_QUOTES, 'UTF-8'); ?>
                                        </label>
                                        <input
                                            class="form-control"
                                            type="text"
                                            id="company-<?php echo htmlspecialchars((string)$field, ENT_QUOTES, 'UTF-8'); ?>"
                                            name="<?php echo htmlspecialchars((string)$field, ENT_QUOTES, 'UTF-8'); ?>"
                                            value="<?php echo htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); ?>"
                                        >
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } else { ?>
                        <p class="text-muted mb-0">Nema podataka o tvrtki za ovog korisnika.</p>
                    <?php } ?>
                </div>
                <div class="modal-footer position-sticky bottom-0 bg-white border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Odustani</button>
                    <button type="submit" class="btn btn-primary">Spremi podatke</button>
                </div>
            </form>
        </div>
    </div>
</div>
