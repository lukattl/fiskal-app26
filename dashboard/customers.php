<?php
require '../core/init.php';

$user = Helper::requireAuth();
$company = Helper::currentCompany();
$fullName = htmlspecialchars($user['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
$pageTitle = 'Customers - fiskal-app';
$pageKey = 'customers';
$customers = [];

$db = DB::getInstance();
$customersQuery = $db->query('SELECT * FROM customers WHERE company_id = ?', [$company['id'] ?? 0]);

if (!$customersQuery->getError() && $customersQuery->getResults()) {
    $customers = Helper::toArray($customersQuery->getResults());
}
?>
<?php require '../includes/header.php'; ?>

    <main class="container pt-0 pb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Kupci</h1>
        </div>

        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
                <h2 class="h5 mb-0">Popis kupaca</h2>
                <div class="d-flex gap-2 flex-wrap">
                    <input
                        class="form-control"
                        type="search"
                        placeholder="Pretraži kupce"
                        data-search-input
                        data-search-target="#customersTableBody"
                        style="max-width: 320px;"
                    >
                    <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#newCustomerModal">
                        Novi kupac
                    </button>
                </div>
            </div>

            <?php if (!empty($customers)) { ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Naziv</th>
                                <th>Adresa</th>
                                <th>Grad</th>
                                <th>Država</th>
                                <th>OIB</th>
                                <th>Email</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody id="customersTableBody">
                            <?php foreach ($customers as $customer) { ?>
                                <tr data-search-row>
                                    <td><?php echo htmlspecialchars((string)($customer['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($customer['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($customer['city'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($customer['country'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($customer['oib'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($customer['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-end">
                                        <button
                                            class="btn btn-outline-primary btn-sm"
                                            type="button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editCustomerModal-<?php echo (int)($customer['id'] ?? 0); ?>"
                                        >
                                            Uredi
                                        </button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <p class="text-muted mb-0">Nema pronađenih kupaca.</p>
            <?php } ?>
        </div>
    </main>

    <?php require '../modals/customer-create-modal.php'; ?>
    <?php require '../modals/customer-edit-modal.php'; ?>

    <?php require '../includes/footer.php'; ?>

    <script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main1.js?v=6"></script>
</body>
</html>
