<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form data-json-form data-endpoint="../api/update-user.php" data-success-message="User data updated successfully.">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="editUserModalLabel">Uređivanje podataka korisnika</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert d-none" data-form-message role="alert"></div>
                    <div class="row g-3">
                        <?php foreach ($userFields as $field => $value) { ?>
                            <div class="col-md-6">
                                <?php if ($field === 'created_at') { ?>
                                    <label class="form-label"><?php echo htmlspecialchars(profileLabel($field), ENT_QUOTES, 'UTF-8'); ?></label>
                                    <div class="form-control-plaintext border rounded px-3 py-2 bg-light">
                                        <?php echo htmlspecialchars(formatProfileDate($value), ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php } elseif ($field === 'role') { ?>
                                    <label class="form-label" for="user-role"><?php echo htmlspecialchars(profileLabel($field), ENT_QUOTES, 'UTF-8'); ?></label>
                                    <select class="form-select" id="user-role" name="role">
                                        <option value="user" <?php echo ((string)$value === 'user') ? 'selected' : ''; ?>>user</option>
                                        <option value="admin" <?php echo ((string)$value === 'admin') ? 'selected' : ''; ?>>admin</option>
                                    </select>
                                <?php } else { ?>
                                    <label class="form-label" for="user-<?php echo htmlspecialchars((string)$field, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars(profileLabel($field), ENT_QUOTES, 'UTF-8'); ?>
                                    </label>
                                    <input
                                        class="form-control"
                                        type="text"
                                        id="user-<?php echo htmlspecialchars((string)$field, ENT_QUOTES, 'UTF-8'); ?>"
                                        name="<?php echo htmlspecialchars((string)$field, ENT_QUOTES, 'UTF-8'); ?>"
                                        value="<?php echo htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Odustani</button>
                    <button type="submit" class="btn btn-primary">Spremi</button>
                </div>
            </form>
        </div>
    </div>
</div>
