<?php
require '../core/init.php';

$user = Helper::requireAuth();
$company = Helper::currentCompany();
$fullName = htmlspecialchars($user['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
$pageTitle = 'Settings - fiskal-app';
$pageKey = 'settings';
$bunitId = Helper::currentBusinessUnitId();
$priceListRows = [];
$bunitOptions = [
    'card_payment' => 0,
    'transactional_payment' => 0,
    'einvoice_sender' => 0,
];

if (!empty($bunitId)) {
    $db = DB::getInstance();
    $bunitOptionsQuery = $db->query('SELECT * FROM bunit_options WHERE bunit_id = ?', [$bunitId]);

    if (!$bunitOptionsQuery->getError() && $bunitOptionsQuery->getResults()) {
        $bunitOptions = array_merge($bunitOptions, Helper::toArray($bunitOptionsQuery->getFirst()));
    }
}

if (!empty($company['id'])) {
    $db = DB::getInstance();
    $priceListQuery = $db->query('SELECT * FROM price_list WHERE company_id = ?', [$company['id']]);

    if (!$priceListQuery->getError() && $priceListQuery->getResults()) {
        $priceListRows = Helper::toArray($priceListQuery->getResults());
    }
}
?>
<?php require '../includes/header.php'; ?>

    <main class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Postavke</h1>
            <a class="btn btn-outline-primary btn-sm" href="dashboard.php">Back to Dashboard</a>
        </div>

        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Opcije poslovne jedinice</h2>
            </div>
            <div class="form-check form-switch mb-3">
                <input
                    class="form-check-input"
                    type="checkbox"
                    id="cardPayments"
                    data-instant-toggle
                    data-endpoint="../api/update-bunit-options.php"
                    data-field="card_payment"
                    <?php echo !empty($bunitOptions['card_payment']) ? 'checked' : ''; ?>
                >
                <label class="form-check-label" for="cardPayments">Kartične uplate</label>
            </div>
            <div class="form-check form-switch mb-3">
                <input
                    class="form-check-input"
                    type="checkbox"
                    id="transactionalPayments"
                    data-instant-toggle
                    data-endpoint="../api/update-bunit-options.php"
                    data-field="transactional_payment"
                    <?php echo !empty($bunitOptions['transactional_payment']) ? 'checked' : ''; ?>
                >
                <label class="form-check-label" for="transactionalPayments">Virmanske uplate</label>
            </div>
            <div class="form-check form-switch mb-3">
                <input
                    class="form-check-input"
                    type="checkbox"
                    id="einoviceSender"
                    data-instant-toggle
                    data-endpoint="../api/update-bunit-options.php"
                    data-field="einvoice_sender"
                    <?php echo !empty($bunitOptions['einvoice_sender']) ? 'checked' : ''; ?>
                >
                <label class="form-check-label" for="einoviceSender">Slanje eračuna</label>
            </div>
            <div class="alert d-none mb-0" id="bunitOptionsMessage" role="alert"></div>
        </div>

        <div class="card p-4 mt-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Cjenik</h2>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#newPriceListModal">
                        Novi artikl
                    </button>
                </div>
            </div>

            <?php if (!empty($priceListRows)) { ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Naziv</th>
                                <th>Maloprodajna cijena</th>
                                <th>Stopa PDV-a</th>
                                <th>Jedinica</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($priceListRows as $article) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)($article['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(number_format((float)($article['retail_price'] ?? 0), 2, ',', '.'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($article['vat_rate'] ?? '0') . '%', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($article['unit'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-end">
                                        <button
                                            class="btn btn-outline-primary btn-sm"
                                            type="button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editPriceListModal-<?php echo (int)($article['id'] ?? 0); ?>"
                                        >
                                            Edit Article
                                        </button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <p class="text-muted mb-0">No price list articles found for this company.</p>
            <?php } ?>
        </div>
    </main>

    <?php require '../modals/price-list-create-modal.php'; ?>
    <?php require '../modals/price-list-edit-modal.php'; ?>

    <?php require '../includes/footer.php'; ?>

    <script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main1.js?v=4"></script>
</body>
</html>
