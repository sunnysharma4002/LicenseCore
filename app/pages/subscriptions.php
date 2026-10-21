<?php
if (!$selected_app_data) {
    echo '<div class="text-center py-5"><i class="ri-vip-crown-line fs-1 text-muted mb-3 d-block"></i><h5 class="text-muted">Select an application first</h5><p class="text-muted">Go to Manage App and choose an application to manage its subscriptions.</p></div>';
    return;
}
$app_secret = $selected_app_data['secret'];

$stmt = $conn->prepare("SELECT * FROM subscriptions WHERE app = ? ORDER BY level ASC");
$stmt->bind_param("s", $app_secret);
$stmt->execute();
$subs_result = $stmt->get_result();
$subs = [];
while ($sub = $subs_result->fetch_assoc()) {
    $subs[] = $sub;
}

// Auto-create default subscription if none exist
if (empty($subs)) {
    $stmt = $conn->prepare("INSERT IGNORE INTO subscriptions (app, name, level) VALUES (?, 'default', 1)");
    $stmt->bind_param("s", $app_secret);
    $stmt->execute();
    // Re-fetch
    $stmt = $conn->prepare("SELECT * FROM subscriptions WHERE app = ? ORDER BY level ASC");
    $stmt->bind_param("s", $app_secret);
    $stmt->execute();
    $subs_result = $stmt->get_result();
    $subs = [];
    while ($sub = $subs_result->fetch_assoc()) {
        $subs[] = $sub;
    }
}
?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">

                <div class="alert alert-danger" role="alert">
                    Subscription plans are organized into multiple tiers or levels.
                    <br>
                    Join Our Discord server <a href="./?page=help" target="_blank"><strong> For Support</strong></a>
                </div>

                <button data-bs-toggle="modal" type="button" data-bs-target="#create-subscription" class="btn btn-secondary waves-effect waves-light"><i class="ri-add-circle-line"></i> Create Subscription</button>
                <br><br>

                <div class="modal fade" tabindex="-1" id="create-subscription">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header d-flex align-items-center">
                                <h4 class="modal-title">Add Subscription</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <div class="form-group">
                                        <label class="control-label">Subscription Name:</label>
                                        <input type="text" class="form-control" name="subname" placeholder="Anything you want" required maxlength="49">
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Subscription Level: <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="When the keys you create are redeemed, LicenseAuth will assign the subscriptions with the same level as the key to the user being created."></i></label>
                                        <input type="text" class="form-control" placeholder="License key level (number only)" name="level" maxlength="12" required>
                                    </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button class="btn btn-danger" name="addsub">Add</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" tabindex="-1" id="edit-subscription">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header d-flex align-items-center">
                                <h4 class="modal-title">Edit Subscription</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <input type="hidden" name="editsub" id="edit-sub-id">
                                    <div class="form-group">
                                        <label class="control-label">Subscription Name:</label>
                                        <input type="text" class="form-control" id="edit-sub-name" name="subname" placeholder="Anything you want" required maxlength="49">
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Subscription Level:</label>
                                        <input type="text" class="form-control" id="edit-sub-level" name="level" placeholder="License key level (number only)" maxlength="12" required>
                                    </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button class="btn btn-primary" name="editsub_submit">Save</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <table id="kt_datatable_subs" class="display table table-bordered dt-responsive" style="width:100%">
                    <thead>
                        <tr class="fw-bolder fs-6 text-gray-800 px-7">
                            <th>Subscription Name</th>
                            <th>License Level</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subs)): ?>
                        <tr>
                            <td colspan="3" class="text-center py-5">
                                <i class="ri-vip-crown-line fs-1 text-muted mb-3 d-block"></i>
                                <h6 class="text-muted">No subscriptions yet</h6>
                                <p class="text-muted mb-3">Create your first subscription plan</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($subs as $sub): ?>
                            <tr>
                                <td><span class="fw-medium"><?= htmlspecialchars($sub['name']) ?></span></td>
                                <td><span class="text-white"><?= intval($sub['level']) ?></span></td>
                                <td>
                                    <div class="dropdown">
                                        <a class="btn btn-soft-secondary btn-sm dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Actions
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-subscription"
                                                data-id="<?= htmlspecialchars($sub['name']) ?>"
                                                data-name="<?= htmlspecialchars($sub['name']) ?>"
                                                data-level="<?= intval($sub['level']) ?>">Edit</button>
                                            <form method="POST" style="display:inline">
                                                <button class="dropdown-item text-danger" name="deletesub" value="<?= htmlspecialchars($sub['name']) ?>">Delete</button>
                                            </form>
                                        </div>
                                    </div>
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

<script>
$(document).ready(function() {
    $('#kt_datatable_subs').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[1, 'asc']]
    });

    $('#edit-subscription').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var name = button.data('name');
        var level = button.data('level');
        $(this).find('#edit-sub-id').val(id);
        $(this).find('#edit-sub-name').val(name);
        $(this).find('#edit-sub-level').val(level);
    });
});
</script>