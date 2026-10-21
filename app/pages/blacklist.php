<?php
if (!$selected_app_data) {
    echo '<div class="text-center py-5"><i class="ri-prohibited-line fs-1 text-muted mb-3 d-block"></i><h5 class="text-muted">Select an application first</h5></div>';
    return;
}
$app_secret = $selected_app_data['secret'];

$stmt = $conn->prepare("SELECT * FROM blacklist WHERE app = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $app_secret);
$stmt->execute();
$blacklist_result = $stmt->get_result();
$blacklist = [];
while ($b = $blacklist_result->fetch_assoc()) {
    $blacklist[] = $b;
}
?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="mb-0">Blacklist Management</h5>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addBanModal"><i class="ri-prohibited-line align-middle me-1"></i> Add Ban</button>
                </div>
                <ul class="nav nav-pills nav-customs nav-danger mb-3" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#hwid-tab">HWID</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#ip-tab">IP Address</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#username-tab">Username</a></li>
                </ul>
                <div class="tab-content">
                    <?php foreach (['hwid' => 'HWID', 'ip' => 'IP Address', 'username' => 'Username'] as $type => $label): ?>
                    <div class="tab-pane <?= $type === 'hwid' ? 'active' : '' ?>" id="<?= $type ?>-tab">
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Value</th>
                                        <th>Reason</th>
                                        <th>Banned</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $filtered = array_filter($blacklist, fn($b) => ($b['type'] ?? '') === $type);
                                    if (empty($filtered)):
                                    ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <i class="ri-prohibited-line fs-1 text-muted mb-2 d-block"></i>
                                            <p class="text-muted mb-0">No <?= $label ?> bans</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($filtered as $b): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($b['value']) ?></code></td>
                                            <td><small class="text-muted"><?= htmlspecialchars($b['reason'] ?? 'N/A') ?></small></td>
                                            <td><small class="text-muted"><?= date('M d, Y', strtotime($b['created_at'])) ?></small></td>
                                            <td>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Unban this entry?')">
                                                    <input type="hidden" name="action" value="unban">
                                                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                                    <input type="hidden" name="app" value="<?= htmlspecialchars($app_secret) ?>">
                                                    <button type="submit" class="btn btn-sm btn-link text-success p-0"><i class="ri-check-line"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addBanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Ban</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_ban">
                    <input type="hidden" name="app" value="<?= htmlspecialchars($app_secret) ?>">
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="hwid">HWID</option>
                            <option value="ip">IP Address</option>
                            <option value="username">Username</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Value</label>
                        <input type="text" name="value" class="form-control" placeholder="Value to ban" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <input type="text" name="reason" class="form-control" placeholder="Ban reason">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Ban</button>
                </div>
            </form>
        </div>
    </div>
</div>
