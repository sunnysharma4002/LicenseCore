<?php
// Admin License Management Page

// Handle license actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'delete_key') {
        $key_id = intval($_POST['key_id'] ?? 0);

        if ($key_id > 0) {
            $stmt = $conn->prepare("DELETE FROM `keys` WHERE id = ?");
            $stmt->bind_param("i", $key_id);
            if ($stmt->execute()) {
                $action_success = 'License key deleted successfully';
                log_action('', 'key_deleted_admin', $user['username'], 'License key deleted by admin');
            } else {
                $action_error = 'Failed to delete license key';
            }
        }
    }
}

// Get license keys with app and user info
$licenses_query = $conn->query("
    SELECT
        k.*,
        a.name as app_name,
        a.secret as app_secret,
        acc.username as owner_name,
        eu.username as used_by_username
    FROM `keys` k
    JOIN apps a ON k.app = a.secret
    JOIN accounts acc ON a.ownerid = acc.ownerid
    LEFT JOIN users eu ON k.used_by = eu.username AND eu.app = k.app
    ORDER BY k.created_at DESC
    LIMIT 100
");

// Get license statistics
$license_stats = $conn->query("
    SELECT
        COUNT(*) as total_keys,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_keys,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_keys,
        SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_keys,
        SUM(CASE WHEN expiry > UNIX_TIMESTAMP() THEN 1 ELSE 0 END) as valid_keys,
        SUM(CASE WHEN expiry <= UNIX_TIMESTAMP() AND expiry > 0 THEN 1 ELSE 0 END) as expired_count
    FROM `keys`
")->fetch_assoc();
?>

<!-- Success/Error Messages -->
<?php if (!empty($action_error)): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
    <i class="mdi mdi-alert text-danger"></i>
    <p class="mb-0 fw-medium"><?= htmlspecialchars($action_error) ?></p>
</div>
<?php endif; ?>

<?php if (!empty($action_success)): ?>
<div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert">
    <i class="mdi mdi-check-circle-outline text-success"></i>
    <p class="mb-0 fw-medium"><?= htmlspecialchars($action_success) ?></p>
</div>
<?php endif; ?>

<!-- License Statistics -->
<div class="row g-2 mb-3">
    <div class="col-md-4 col-lg-2">
        <div class="card bg-dark border-secondary text-center h-100">
            <div class="card-body py-2">
                <p class="text-muted small fw-medium mb-0">Total Keys</p>
                <h5 class="text-primary fw-bold mb-0"><?= number_format($license_stats['total_keys']) ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card bg-dark border-secondary text-center h-100">
            <div class="card-body py-2">
                <p class="text-muted small fw-medium mb-0">Active</p>
                <h5 class="text-success fw-bold mb-0"><?= number_format($license_stats['active_keys']) ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card bg-dark border-secondary text-center h-100">
            <div class="card-body py-2">
                <p class="text-muted small fw-medium mb-0">Inactive</p>
                <h5 class="text-muted fw-bold mb-0"><?= number_format($license_stats['inactive_keys']) ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card bg-dark border-secondary text-center h-100">
            <div class="card-body py-2">
                <p class="text-muted small fw-medium mb-0">Expired</p>
                <h5 class="text-danger fw-bold mb-0"><?= number_format($license_stats['expired_keys']) ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card bg-dark border-secondary text-center h-100">
            <div class="card-body py-2">
                <p class="text-muted small fw-medium mb-0">Valid</p>
                <h5 class="text-warning fw-bold mb-0"><?= number_format($license_stats['valid_keys']) ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card bg-dark border-secondary text-center h-100">
            <div class="card-body py-2">
                <p class="text-muted small fw-medium mb-0">Expired</p>
                <h5 class="text-danger fw-bold mb-0"><?= number_format($license_stats['expired_count']) ?></h5>
            </div>
        </div>
    </div>
</div>

<!-- License Management Table -->
<div class="card bg-dark border-secondary">
    <div class="card-header border-secondary bg-transparent px-3 py-2">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0">License Key Management</h5>
            <small class="text-muted">
                Showing latest 100 keys
            </small>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-dark table-borderless table-hover align-middle mb-0">
            <thead class="table-secondary">
                <tr>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">License Key</th>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">Application</th>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">Owner</th>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">Status</th>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">Expiry</th>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">Used By</th>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">Created</th>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($key = $licenses_query->fetch_assoc()): ?>
                <tr>
                    <td class="px-3 py-1">
                        <div class="font-monospace small text-primary">
                            <?= htmlspecialchars(substr($key['license_key'], 0, 24)) ?>...
                        </div>
                        <div class="text-secondary small mt-1">
                            Format: <?= htmlspecialchars($key['format']) ?>
                        </div>
                    </td>
                    <td class="px-3 py-1">
                        <div class="fw-semibold small text-info"><?= htmlspecialchars($key['app_name']) ?></div>
                        <div class="text-secondary small font-monospace">Secret: <?= htmlspecialchars(substr($key['app_secret'], 0, 12)) ?>...</div>
                    </td>
                    <td class="px-3 py-1">
                        <div class="fw-semibold small text-success"><?= htmlspecialchars($key['owner_name']) ?></div>
                        <?php if (!empty($key['created_by'])): ?>
                            <div class="text-secondary small">Created by: <?= htmlspecialchars($key['created_by']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-1">
                        <?php
                        $badge_class = 'bg-secondary';
                        if ($key['status'] === 'active') $badge_class = 'bg-success';
                        elseif ($key['status'] === 'inactive') $badge_class = 'bg-warning text-dark';
                        elseif ($key['status'] === 'expired') $badge_class = 'bg-danger';
                        ?>
                        <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($key['status']) ?></span>
                    </td>
                    <td class="px-3 py-1 text-muted small">
                        <?php if ($key['expiry'] == 0): ?>
                            <span class="text-secondary">Never</span>
                        <?php elseif ($key['expiry'] <= time()): ?>
                            <span class="text-danger">Expired</span>
                            <div class="text-secondary small">
                                <?= date('M j, Y H:i', $key['expiry']) ?>
                            </div>
                        <?php else: ?>
                            <span class="text-success">Active</span>
                            <div class="text-secondary small">
                                <?= date('M j, Y H:i', $key['expiry']) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-1 text-muted small">
                        <?php if (!empty($key['used_by'])): ?>
                            <div class="text-warning"><?= htmlspecialchars($key['used_by']) ?></div>
                            <?php if (!empty($key['used_at'])): ?>
                                <div class="text-secondary small">
                                    <?= date('M j, H:i', strtotime($key['used_at'])) ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-secondary">Not used</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-1 text-muted small">
                        <?= date('M j, Y', strtotime($key['created_at'])) ?>
                        <div class="text-secondary small">
                            <?= date('H:i', strtotime($key['created_at'])) ?>
                        </div>
                    </td>
                    <td class="px-3 py-1">
                        <div class="d-flex align-items-center gap-1">
                            <button onclick="viewKeyDetails(<?= $key['id'] ?>)" class="btn btn-sm btn-outline-primary" title="View Details">
                                <i class="mdi mdi-eye-outline"></i>
                            </button>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="delete_key">
                                <input type="hidden" name="key_id" value="<?= $key['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this license key?')" title="Delete Key">
                                    <i class="mdi mdi-delete"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php if ($licenses_query->num_rows === 0): ?>
        <div class="p-3 text-center text-muted">
            <i class="mdi mdi-key fs-1 mb-2"></i>
            <p class="mb-0">No license keys found</p>
        </div>
    <?php endif; ?>
</div>

<!-- Key Details Modal -->
<div class="modal fade" id="keyDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold">License Key Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="keyDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function closeModal(modalId) {
    var modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
    if (modal) modal.hide();
}

function viewKeyDetails(keyId) {
    alert('Key ID: ' + keyId + '\n\nDetailed view would show comprehensive license key information, usage history, and settings.');
}
</script>
