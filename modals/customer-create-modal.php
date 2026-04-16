<div class="modal fade" id="newCustomerModal" tabindex="-1" aria-labelledby="newCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form data-json-form data-endpoint="../api/create-customer.php" data-success-message="Customer created successfully.">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="newCustomerModalLabel">Novi kupac</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert d-none" data-form-message role="alert"></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="new-customer-full-name">Puno ime</label>
                            <input class="form-control" type="text" id="new-customer-full-name" name="full_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new-customer-address">Adresa</label>
                            <input class="form-control" type="text" id="new-customer-address" name="address">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new-customer-city">Grad</label>
                            <input class="form-control" type="text" id="new-customer-city" name="city">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new-customer-country">Država</label>
                            <input class="form-control" type="text" id="new-customer-country" name="country">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new-customer-oib">OIB</label>
                            <input class="form-control" type="text" id="new-customer-oib" name="oib">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new-customer-email">Email</label>
                            <input class="form-control" type="email" id="new-customer-email" name="email">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="new-customer-legal" name="legal" value="1">
                                <label class="form-check-label" for="new-customer-legal">Pravna osoba / Poduzeće</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Odustani</button>
                    <button type="submit" class="btn btn-primary">Kreiraj kupca</button>
                </div>
            </form>
        </div>
    </div>
</div>
