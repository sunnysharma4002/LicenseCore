<?php
if (!$selected_app_data) {
    echo '<div class="text-center py-5"><i class="ri-key-line fs-1 text-muted mb-3 d-block"></i><h5 class="text-muted">Select an application first</h5><p class="text-muted">Go to Manage App and choose an application to manage its licenses.</p></div>';
    return;
}
$app_secret = $selected_app_data['secret'];

$stmt = $conn->prepare("SELECT *, UNIX_TIMESTAMP(created_at) AS created_ts FROM `keys` WHERE app = ? ORDER BY created_at DESC LIMIT 1000");
$stmt->bind_param("s", $app_secret);
$stmt->execute();
$keys_result = $stmt->get_result();
$keys = [];
while ($key = $keys_result->fetch_assoc()) {
    $keys[] = $key;
}

$stmt = $conn->prepare("SELECT * FROM subscriptions WHERE app = ? ORDER BY level ASC");
$stmt->bind_param("s", $app_secret);
$stmt->execute();
$sub_levels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<?php if (!empty($license_error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="ri-error-warning-line me-1 align-middle"></i> <?= htmlspecialchars($license_error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
<?php if (!empty($license_success)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="ri-checkbox-circle-line me-1 align-middle"></i> <?= htmlspecialchars($license_success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">

                <div class="alert alert-danger" role="alert">
                    Upgrade Your Account To Developer Plan Free <a href="./?page=upgrade" target="_blank">Upgrade Option</a>
                    <br>
                    Join Our Discord server <a href="./?page=help" target="_blank"><strong> For Support</strong></a>
                </div>

                <form method="POST">
                    <button type="button" data-bs-toggle="modal" data-bs-target="#create-keys" class="btn btn-secondary waves-effect waves-light"><i class="ri-add-circle-line"></i> Create keys</button>
                    <button data-bs-toggle="modal" type="button" data-bs-target="#comp-keys" class="btn btn-secondary waves-effect waves-light"><i class="ri-time-line"></i> Add Time</button>
                    <button type="button" data-bs-toggle="modal" data-bs-target="#import-keys" class="btn btn-secondary waves-effect waves-light"><i class="ri-upload-cloud-line"></i> Import keys</button>
                    <button name="dlkeys" class="btn btn-secondary waves-effect waves-light"><i class="ri-download-cloud-line"></i> Download All keys</button><br><br>
                    <button type="button" data-bs-toggle="modal" data-bs-target="#delete-allkeys" class="btn btn-danger waves-effect waves-light"><i class="ri-delete-bin-line"></i> Delete All keys</button>
                    <button type="button" data-bs-toggle="modal" data-bs-target="#delete-allunusedkeys" class="btn btn-danger waves-effect waves-light"><i class="ri-delete-bin-line"></i> Delete All Unused Keys</button>
                    <button type="button" data-bs-toggle="modal" data-bs-target="#delete-allusedkeys" class="btn btn-danger waves-effect waves-light"><i class="ri-delete-bin-line"></i> Delete All Used Keys</button>
                </form>
                <br>

                <div class="modal fade" tabindex="-1" id="create-keys">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header d-flex align-items-center">
                                <h4 class="modal-title">Add Licenses</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <div class="form-group">
                                        <label class="control-label">Amount:</label>
                                        <input type="number" min="1" class="form-control" name="amount" placeholder="Default 1" value="1" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Key Mask: <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="Format keys are in. You can do custom by putting whatever, or do * for random character"></i></label>
                                        <?php
                                        $default_mask = $_SESSION['last_key_mask'] ?? '*******-*******-*******-*******';
                                        ?>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($default_mask) ?>" placeholder="Using * will assign a random character." name="mask" required maxlength="49">
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col">
                                            <div class="form-check">
                                                <input class="form-check-input" name="lowercaseLetters" type="checkbox" id="lowercaseCheck" checked>
                                                <label class="form-check-label" for="lowercaseCheck">Lowercase Letters <i class="mdi mdi-help-circle-outline text-white-50" data-toggle="tooltip" data-placement="top" title="If checked, lowercase letters will be added to generated keys."></i></label>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="form-check">
                                                <input class="form-check-input" name="capitalLetters" type="checkbox" id="capitalCheck" checked>
                                                <label class="form-check-label" for="capitalCheck">Capital Letters <i class="mdi mdi-help-circle-outline text-white-50" data-toggle="tooltip" data-placement="top" title="If checked, capital letters will be added to generated keys."></i></label>
                                            </div>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="form-group">
                                        <label class="control-label">License Level: <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="This needs to coordinate to the level of subscription you want to give to user when they redeem license."></i></label>
                                        <select name="level" class="form-control">
                                            <?php if (!empty($sub_levels)): ?>
                                                <?php foreach ($sub_levels as $sl): ?>
                                                <option value="<?= intval($sl['level']) ?>"><?= intval($sl['level']) ?> (<?= htmlspecialchars($sl['name']) ?>)</option>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option value="1">1 (default)</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">License Note:</label>
                                        <input type="text" maxlength="69" class="form-control" name="note" placeholder="Optional, e.g. this license was for Joe" value="">
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">License Expiry Unit:</label>
                                        <?php $default_expiry = intval($_SESSION['last_key_expiry'] ?? 86400); ?>
                                        <select name="expiry" class="form-control">
                                            <option value="86400"<?= $default_expiry===86400?' selected':'' ?>>Days</option>
                                            <option value="60"<?= $default_expiry===60?' selected':'' ?>>Minutes</option>
                                            <option value="3600"<?= $default_expiry===3600?' selected':'' ?>>Hours</option>
                                            <option value="1"<?= $default_expiry===1?' selected':'' ?>>Seconds</option>
                                            <option value="604800"<?= $default_expiry===604800?' selected':'' ?>>Weeks</option>
                                            <option value="2629743"<?= $default_expiry===2629743?' selected':'' ?>>Months</option>
                                            <option value="31556926"<?= $default_expiry===31556926?' selected':'' ?>>Years</option>
                                            <option value="315569260"<?= $default_expiry===315569260?' selected':'' ?>>Lifetime</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">License Duration: <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="When the key is redeemed, a subscription with the duration of the key will be added to the user who redeemed the key."></i></label>
                                        <input name="duration" type="text" class="form-control" placeholder="Multiplied by selected Expiry unit" value="1" required pattern="\d*" maxlength="4">
                                    </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button class="btn btn-danger" name="genkeys">Add</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" tabindex="-1" id="import-keys">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header d-flex align-items-center">
                                <h4 class="modal-title">Import Licenses</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <div class="form-group">
                                        <label class="control-label">Keys: <i class="mdi mdi-help-circle-outline text-white-50" data-toggle="tooltip" data-placement="top" title="Make sure you have a subscription created that matches each level of the keys you're importing."></i></label>
                                        <input class="form-control" name="keys" placeholder="Format: KEYHERE,LVLHERE,DAYSHERE|KEYHERE,LVLHERE,DAYSHERE">
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Import from auth.gg:</label>
                                        <input class="form-control" name="authgg" placeholder="Paste in JSON from developers.auth.gg">
                                    </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button class="btn btn-danger" name="importkeys">Import</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" tabindex="-1" id="comp-keys">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header d-flex align-items-center">
                                <h4 class="modal-title">Add Time</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <div class="form-group">
                                        <label class="control-label">Unit Of Time To Add:</label>
                                        <select name="expiry" class="form-control">
                                            <option value="86400">Days</option>
                                            <option value="60">Minutes</option>
                                            <option value="3600">Hours</option>
                                            <option value="1">Seconds</option>
                                            <option value="604800">Weeks</option>
                                            <option value="2629743">Months</option>
                                            <option value="31556926">Years</option>
                                            <option value="315569260">Lifetime</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Time To Add: <i class="mdi mdi-help-circle-outline text-white-50" data-toggle="tooltip" data-placement="top" title="If the key is used, this will do nothing. Used keys are turned into users so if you want to add time to a user, go to users tab and click extend user(s)"></i></label>
                                        <input class="form-control" name="time" type="number" placeholder="Multiplied by selected unit of time" required>
                                    </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button class="btn btn-danger" name="addtime">Add</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" tabindex="-1" id="ban-key">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header d-flex align-items-center">
                                <h4 class="modal-title">Ban License</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <div class="alert alert-warning" role="alert">
                                        This will not ban the user (prevent them from logging in) <u>unless</u> you check <b>Ban User Too</b>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Ban reason:</label>
                                        <br><br>
                                        <input type="text" class="form-control" name="reason" placeholder="Reason for ban" required>
                                        <br>
                                        <input class="form-check-input" style="color:white;border-color:white;" name="banUserToo" type="checkbox" id="banUserCheck">
                                        <label class="form-check-label" for="banUserCheck">Ban User Too <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete both the key and the user"></i></label>
                                    </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button class="btn btn-danger bankey" name="bankey">Ban</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" tabindex="-1" id="del-key">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header d-flex align-items-center">
                                <h4 class="modal-title">Delete License</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <div class="alert alert-warning" role="alert">
                                        This will not delete the user (prevent them from logging in) <u>unless</u> you check <b>Delete User Too</b>
                                    </div>
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" style="color:white;border-color:white;" name="delUserToo" type="checkbox" id="delUserCheck">
                                            <label class="form-check-label" for="delUserCheck">Delete User Too <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete both the key and the user"></i></label>
                                        </div>
                                    </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button class="btn btn-danger delkey" name="deletekey">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" tabindex="-1" id="delete-allkeys">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-body text-center p-5">
                                <h2 class="modal-title">Delete All Keys</h2>
                                <lord-icon src="https://cdn.lordicon.com/tdrtiskw.json" trigger="loop" colors="primary:#f7b84b,secondary:#405189" style="width:130px;height:130px"></lord-icon>
                                <div class="mt-4 pt-3">
                                    <h4 class="mb-3">Notice</h4>
                                    <p class="text-muted mb-4">Are you sure you want to delete all of your keys? This can not be undone.</p>
                                    <div class="alert alert-warning" role="alert">
                                        This will not delete users (prevent them from logging in). Go to <a href="./?page=users">Manage Users</a> for that.
                                    </div>
                                    <form method="POST">
                                        <div class="row g-3">
                                            <div class="col-lg-12">
                                                <div class="hstack gap-2 justify-content-end">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">No, cancel</button>
                                                    <button name="delkeys" class="btn btn-danger">Yes, I'm sure</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" tabindex="-1" id="delete-allunusedkeys">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-body text-center p-5">
                                <h2 class="modal-title">Delete All Unused Keys</h2>
                                <lord-icon src="https://cdn.lordicon.com/tdrtiskw.json" trigger="loop" colors="primary:#f7b84b,secondary:#405189" style="width:130px;height:130px"></lord-icon>
                                <div class="mt-4 pt-3">
                                    <h4 class="mb-3">Notice</h4>
                                    <p class="text-muted mb-4">Are you sure you want to delete all of your keys? This can not be undone.</p>
                                    <div class="alert alert-warning" role="alert">
                                        This will not delete users (prevent them from logging in). Go to <a href="./?page=users">Manage Users</a> for that.
                                    </div>
                                    <form method="POST">
                                        <div class="row g-3">
                                            <div class="col-lg-12">
                                                <div class="hstack gap-2 justify-content-end">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">No, cancel</button>
                                                    <button name="deleteallunused" class="btn btn-danger">Yes, I'm sure</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" tabindex="-1" id="delete-allusedkeys">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-body text-center p-5">
                                <h2 class="modal-title">Delete All Used Keys</h2>
                                <lord-icon src="https://cdn.lordicon.com/tdrtiskw.json" trigger="loop" colors="primary:#f7b84b,secondary:#405189" style="width:130px;height:130px"></lord-icon>
                                <div class="mt-4 pt-3">
                                    <h4 class="mb-3">Notice</h4>
                                    <p class="text-muted mb-4">Are you sure you want to delete all of your keys? This can not be undone.</p>
                                    <div class="alert alert-warning" role="alert">
                                        This will not delete users (prevent them from logging in). Go to <a href="./?page=users">Manage Users</a> for that.
                                    </div>
                                    <form method="POST">
                                        <div class="row g-3">
                                            <div class="col-lg-12">
                                                <div class="hstack gap-2 justify-content-end">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">No, cancel</button>
                                                    <button name="deleteallused" class="btn btn-danger">Yes, I'm sure</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <table id="kt_datatable_licenses" class="display table table-bordered dt-responsive" style="width:100%">
                    <thead>
                        <tr class="fw-bolder fs-6 text-gray-800 px-7">
                            <th>Key</th>
                            <th>Creation Date</th>
                            <th>Generated By</th>
                            <th>Duration</th>
                            <th>Note</th>
                            <th>Used On</th>
                            <th>Used By</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($keys)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="ri-key-line fs-1 text-muted mb-3 d-block"></i>
                                <h6 class="text-muted">No license keys yet</h6>
                                <p class="text-muted mb-3">Create your first license key to get started</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($keys as $key):
                                $expiry_ts = intval($key['expiry'] ?? 0);
                                $is_expired = $expiry_ts > 0 ? ($expiry_ts < time()) : false;
                                $is_used = !empty($key['used_by']);
                                $is_banned = intval($key['banned'] ?? 0) === 1;
                                $duration_display = '';
                                if ($expiry_ts > 0) {
                                    $created_ts = intval($key['created_ts'] ?? 0);
                                    $total_duration = $expiry_ts - $created_ts;
                                    if ($total_duration > 0) {
                                        $days = floor($total_duration / 86400);
                                        $hours = floor(($total_duration % 86400) / 3600);
                                        $duration_display = $days > 0 ? "$days days" : "$hours hours";
                                    } else {
                                        $duration_display = 'Expired';
                                    }
                                } else {
                                    $duration_display = 'Lifetime';
                                }
                            ?>
                            <tr>
                                <td><code><?= htmlspecialchars($key['license_key']) ?></code></td>
                                <td><?= date('Y-m-d H:i', strtotime($key['created_at'])) ?></td>
                                <td><?= htmlspecialchars($key['created_by'] ?? '-') ?></td>
                                <td><?= $duration_display ?></td>
                                <td><?= htmlspecialchars($key['note'] ?? 'N/A') ?></td>
                                <td><?= !empty($key['used_at']) ? date('Y-m-d H:i', strtotime($key['used_at'])) : 'N/A' ?></td>
                                <td><?= $is_used ? htmlspecialchars($key['used_by']) : 'N/A' ?></td>
                                <td>
                                    <?php if ($is_banned): ?>
                                        <span class="badge bg-dark-subtle text-dark">Banned</span>
                                    <?php elseif ($is_expired): ?>
                                        <span class="badge bg-danger-subtle text-danger">Expired</span>
                                    <?php elseif ($is_used): ?>
                                        <span class="badge bg-primary-subtle text-primary">Used</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <a class="btn btn-soft-secondary btn-sm dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Actions
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#ban-key" onclick="bankey('<?= htmlspecialchars($key['license_key']) ?>')">Ban</button>
                                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#del-key" onclick="delkey('<?= htmlspecialchars($key['license_key']) ?>')">Delete</button>
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
function bankey(key) {
    $('.bankey').attr('value', key);
}
function delkey(key) {
    $('.delkey').attr('value', key);
}

$(document).ready(function() {
    $('#kt_datatable_licenses').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[1, 'desc']],
        columnDefs: [
            { orderable: false, targets: [8] }
        ]
    });
});
</script>