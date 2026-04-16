<?php if (!empty($priceListRows)) { ?>
    <?php foreach ($priceListRows as $article) { ?>
        <div class="modal fade" id="editPriceListModal-<?php echo (int)($article['id'] ?? 0); ?>" tabindex="-1" aria-labelledby="editPriceListModalLabel-<?php echo (int)($article['id'] ?? 0); ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form data-json-form data-endpoint="../api/update-price-list.php" data-success-message="Article updated successfully.">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="editPriceListModalLabel-<?php echo (int)($article['id'] ?? 0); ?>">Uredi artikal</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert d-none" data-form-message role="alert"></div>
                            <input type="hidden" name="id" value="<?php echo (int)($article['id'] ?? 0); ?>">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="article-label-<?php echo (int)($article['id'] ?? 0); ?>">Naziv</label>
                                    <input
                                        class="form-control"
                                        type="text"
                                        id="article-label-<?php echo (int)($article['id'] ?? 0); ?>"
                                        name="label"
                                        value="<?php echo htmlspecialchars((string)($article['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        required
                                    >
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="article-price-<?php echo (int)($article['id'] ?? 0); ?>">Jedinična cijena</label>
                                    <input
                                        class="form-control"
                                        type="number"
                                        id="article-price-<?php echo (int)($article['id'] ?? 0); ?>"
                                        name="retail_price"
                                        value="<?php echo htmlspecialchars((string)($article['retail_price'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        min="0"
                                        step="0.01"
                                        required
                                    >
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="article-vat-<?php echo (int)($article['id'] ?? 0); ?>">PDV stopa</label>
                                    <select class="form-select" id="article-vat-<?php echo (int)($article['id'] ?? 0); ?>" name="vat_rate" required>
                                        <option value="0" <?php echo ((string)($article['vat_rate'] ?? '0') === '0') ? 'selected' : ''; ?>>0%</option>
                                        <option value="25" <?php echo ((string)($article['vat_rate'] ?? '') === '25') ? 'selected' : ''; ?>>25%</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="article-unit-<?php echo (int)($article['id'] ?? 0); ?>">Jedinica mjere</label>
                                    <select class="form-select" id="article-unit-<?php echo (int)($article['id'] ?? 0); ?>" name="unit" required>
                                        <option value="kom" <?php echo ((string)($article['unit'] ?? '') === 'kom') ? 'selected' : ''; ?>>kom</option>
                                        <option value="sati" <?php echo ((string)($article['unit'] ?? '') === 'sati') ? 'selected' : ''; ?>>sati</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Odustani</button>
                            <button type="submit" class="btn btn-primary">Spremi artikl</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php } ?>
<?php } ?>
