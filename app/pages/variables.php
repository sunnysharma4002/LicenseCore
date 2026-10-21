<?php
if (!$selected_app_data) {
    echo '<div class="text-center py-5"><i class="ri-code-line fs-1 text-muted mb-3 d-block"></i><h5 class="text-muted">Select an application first</h5></div>';
    return;
}
$app_secret = $selected_app_data['secret'];

$stmt = $conn->prepare("SELECT * FROM vars WHERE app = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $app_secret);
$stmt->execute();
$vars_result = $stmt->get_result();
$vars = [];
while ($var = $vars_result->fetch_assoc()) {
    $vars[] = $var;
}
?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="mb-0">Variables</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVarModal"><i class="ri-add-line align-middle me-1"></i> Add Variable</button>
                </div>
                <p class="text-muted small mb-3">Global variables that can be accessed by users via API.</p>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Variable Name</th>
                                <th>Value</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($vars)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <i class="ri-code-line fs-1 text-muted mb-3 d-block"></i>
                                    <h6 class="text-muted">No variables yet</h6>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($vars as $var): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($var['name']) ?></code></td>
                                    <td class="text-truncate" style="max-width:300px"><small class="text-muted"><?= htmlspecialchars($var['value']) ?></small></td>
                                    <td><small class="text-muted"><?= date('M d, Y', strtotime($var['created_at'])) ?></small></td>
                                    <td>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this variable?')">
                                            <input type="hidden" name="action" value="delete_var">
                                            <input type="hidden" name="id" value="<?= $var['id'] ?>">
                                            <input type="hidden" name="app" value="<?= htmlspecialchars($app_secret) ?>">
                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addVarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Variable</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_var">
                    <input type="hidden" name="app" value="<?= htmlspecialchars($app_secret) ?>">
                    <div class="mb-3">
                        <label class="form-label">Variable Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g., welcome_message" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Value</label>
                        <input type="text" name="value" class="form-control" placeholder="Variable value" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>
