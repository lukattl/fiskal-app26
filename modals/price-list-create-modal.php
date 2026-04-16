<div class="modal fade" id="newPriceListModal" tabindex="-1" aria-labelledby="newPriceListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form data-json-form data-endpoint="../api/create-price-list.php" data-success-message="Artikl uspješno spremljen!" data-error-message="Došlo je do greške prilikom spremanja artikla. Pokušajte ponovo.">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="newPriceListModalLabel">Novi artikl</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert d-none" data-form-message role="alert"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="new-article-label">Naziv</label>
                            <input class="form-control" type="text" id="new-article-label" name="label" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="new-article-price">Osnovna cijena</label>
                            <input class="form-control" type="number" id="new-article-price" name="retail_price" min="0" step="0.01" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="new-article-vat">Stopa PDV-a</label>
                            <select class="form-select" id="new-article-vat" name="vat_rate" required>
                                <option value="0">0%</option>
                                <option value="25">25%</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="new-article-unit">Jedinica</label>
                            <select class="form-select" id="new-article-unit" name="unit" required>
                                <option value="kom">kom</option>
                                <option value="sati">sati</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Odustani</button>
                    <button type="submit" class="btn btn-primary">Kreiraj artikl</button>
                </div>
            </form>
        </div>
    </div>
</div>
