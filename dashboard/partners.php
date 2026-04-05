<?php
require '../core/init.php';

$user = Helper::requireAuth();
$fullName = htmlspecialchars($user['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
$pageTitle = 'Partners - fiskal-app';
$pageKey = 'partners';
$partners = [];

$db = DB::getInstance();
$partnersQuery = $db->query('SELECT * FROM partners');

if (!$partnersQuery->getError() && $partnersQuery->getResults()) {
    $partners = Helper::toArray($partnersQuery->getResults());
}
?>
<?php require '../includes/header.php'; ?>

    <main class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Partners</h1>
            <a class="btn btn-outline-primary btn-sm" href="dashboard.php">Back to Dashboard</a>
        </div>

        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
                <h2 class="h5 mb-0">Partner List</h2>
                <input
                    class="form-control"
                    type="search"
                    placeholder="Search partners..."
                    data-search-input
                    data-search-target="#partnersTableBody"
                    style="max-width: 320px;"
                >
            </div>

            <?php if (!empty($partners)) { ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Address</th>
                                <th>City</th>
                                <th>Country</th>
                                <th>OIB</th>
                                <th>Email</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="partnersTableBody">
                            <?php foreach ($partners as $partner) { ?>
                                <tr data-search-row>
                                    <td><?php echo htmlspecialchars((string)($partner['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($partner['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($partner['city'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($partner['country'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($partner['oib'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($partner['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-end">
                                        <button
                                            class="btn btn-outline-primary btn-sm"
                                            type="button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editPartnerModal-<?php echo (int)($partner['id'] ?? 0); ?>"
                                        >
                                            Edit Partner
                                        </button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <p class="text-muted mb-0">No partners found.</p>
            <?php } ?>
        </div>
    </main>

    <?php require '../modals/partner-edit-modal.php'; ?>

    <?php require '../includes/footer.php'; ?>

    <script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main1.js?v=5"></script>
</body>
</html>
