<?php
if (!$selected_app_data) {
    echo '<div class="text-center py-5"><i class="ri-send-to-back-line fs-1 text-muted mb-3 d-block"></i><h5 class="text-muted">Select an application first</h5></div>';
    return;
}
$app_secret = $selected_app_data['secret'];

$stmt = $conn->prepare("SELECT * FROM webhooks WHERE app = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $app_secret);
$stmt->execute();
$webhooks_result = $stmt->get_result();
$webhooks = [];
while ($wh = $webhooks_result->fetch_assoc()) {
    $webhooks[] = $wh;
}
?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="mb-0">Webhooks</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addWebhookModal"><i class="ri-add-line align-middle me-1"></i> Add Webhook</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>URL</th>
                                <th>Event</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($webhooks)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <i class="ri-send-to-back-line fs-1 text-muted mb-3 d-block"></i>
                                    <h6 class="text-muted">No webhooks configured</h6>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($webhooks as $wh): ?>
                                <tr>
                                    <td class="text-truncate" style="max-width:300px"><code class="small"><?= htmlspecialchars($wh['url']) ?></code></td>
                                    <td><span class="badge bg-info-subtle text-info"><?= htmlspecialchars($wh['event']) ?></span></td>
                                    <td><span class="badge bg-success-subtle text-success">Active</span></td>
                                    <td>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this webhook?')">
                                            <input type="hidden" name="action" value="delete_webhook">
                                            <input type="hidden" name="id" value="<?= $wh['id'] ?>">
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

<div class="modal fade" id="addWebhookModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Webhook</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_webhook">
                    <input type="hidden" name="app" value="<?= htmlspecialchars($app_secret) ?>">
                    <div class="mb-3">
                        <label class="form-label">Webhook URL</label>
                        <input type="url" name="url" class="form-control" placeholder="https://example.com/webhook" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Event</label>
                        <select name="event" class="form-select">
                            <option value="license_init">License Init</option>
                            <option value="license_used">License Used</option>
                            <option value="user_register">User Register</option>
                            <option value="subscription">Subscription</option>
                        </select>
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
