<?php
if (!$selected_app_data) {
    echo '<div class="text-center py-5"><i class="ri-group-line fs-1 text-muted mb-3 d-block"></i><h5 class="text-muted">Select an application first</h5></div>';
    return;
}
$app_secret = $selected_app_data['secret'];

$stmt = $conn->prepare("SELECT * FROM `users` WHERE app = ? ORDER BY created_at DESC LIMIT 1000");
$stmt->bind_param("s", $app_secret);
$stmt->execute();
$users_result = $stmt->get_result();
$users = [];
while ($u = $users_result->fetch_assoc()) {
    $users[] = $u;
}

$stmt = $conn->prepare("SELECT * FROM subscriptions WHERE app = ? ORDER BY level ASC");
$stmt->bind_param("s", $app_secret);
$stmt->execute();
$sub_plans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
function sub_options($sub_plans) {
    foreach ($sub_plans as $s) {
        echo '<option value="' . htmlspecialchars($s['name']) . '">' . htmlspecialchars($s['name']) . '</option>';
    }
}
?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <div class="alert alert-danger" role="alert">
                    If You Create User Manually Or After someone registers for your app with a license, they will appear here.
                    <br>
                    Join Our Discord server <a href="./?page=help" target="_blank"><strong>For Support</strong></a>
                </div>

                <form method="POST">
                    <button data-bs-toggle="modal" type="button" data-bs-target="#create-user" class="btn btn-secondary waves-effect waves-light"><i class="ri-add-circle-line"></i> Create User</button>
                    <button data-bs-toggle="modal" type="button" data-bs-target="#set-user-var" class="btn btn-secondary waves-effect waves-light"><i class="ri-add-circle-line"></i> Set Variable</button>
                    <button data-bs-toggle="modal" type="button" data-bs-target="#import-users" class="btn btn-secondary waves-effect waves-light"><i class="ri-upload-cloud-line"></i> Import users</button>
                    <button name="dlusers" class="btn btn-secondary waves-effect waves-light"><i class="ri-download-cloud-line"></i> Export Users</button>
                    <button data-bs-toggle="modal" type="button" data-bs-target="#extend-user" class="btn btn-secondary waves-effect waves-light"><i class="ri-time-line"></i> Extend User(s)</button>
                    <button data-bs-toggle="modal" type="button" data-bs-target="#subtract-user" class="btn btn-secondary waves-effect waves-light"><i class="ri-time-line"></i> Subtract User(s)</button><br><br>
                    <button type="button" data-bs-toggle="modal" data-bs-target="#delete-allusers" class="btn btn-danger waves-effect waves-light"><i class="ri-delete-bin-line"></i> Delete All Users</button>
                    <button type="button" data-bs-toggle="modal" data-bs-target="#delete-allexpired" class="btn btn-danger waves-effect waves-light"><i class="ri-delete-bin-line"></i> Delete Expired Users</button>
                    <button type="button" data-bs-toggle="modal" data-bs-target="#reset-allusers" class="btn btn-danger waves-effect waves-light"><i class="ri-restart-line"></i> HWID Reset All Users</button>
                    <button type="button" data-bs-toggle="modal" data-bs-target="#unban-allusers" class="btn btn-danger waves-effect waves-light"><i class="ri-group-line"></i> Unban All Users</button>
                </form>
                <br>

                <!-- Create User Modal -->
                <div id="create-user" class="modal" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header d-flex align-items-center">
                                <h4 class="modal-title">Add User</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <div class="form-group">
                                        <label class="control-label">Username:</label>
                                        <input type="text" class="form-control" name="username" placeholder="Username for user" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Password:</label>
                                        <input type="password" class="form-control" name="password" placeholder="leave blank to use first password on login">
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Subscription:</label>
                                        <select name="sub" class="form-control">
                                            <?php sub_options($sub_plans); ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Subscription Expiry:</label>
                                        <input class="form-control" type="datetime-local" name="expiry" value="<?= date('Y-m-d\TH:i', strtotime('+30 days')) ?>" required>
                                    </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button class="btn btn-danger" name="adduser">Add</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Set Variable Modal -->
                <div id="set-user-var" class="modal" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header d-flex align-items-center">
                                <h4 class="modal-title">Set Variable</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <div class="form-group">
                                        <label class="control-label">User:</label>
                                        <input type="text" class="form-control" name="user" placeholder="For all users, put: all" value="all" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Variable:</label>
                                        <input type="text" class="form-control" name="var" placeholder="Variable name" list="vars" required>
                                        <datalist id="vars">
                                        </datalist>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Variable Data:</label>
                                        <textarea class="form-control" name="data" placeholder="User variable data" maxlength="500" required rows="3"></textarea>
                                    </div>
                                    <br>
                                    <div class="form-check">
                                        <input class="form-check-input" name="readOnly" type="checkbox" id="flexCheckChecked">
                                        <label class="form-check-label" for="flexCheckChecked">
                                            Read-only <i class="mdi mdi-help-circle-outline text-white-50" data-toggle="tooltip" data-placement="top" title="If checked, the variable can only be updated via Seller API or this dashboard."></i>
                                        </label>
                                    </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button class="btn btn-danger" name="setvar">Add</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Import Users Modal -->
                <div id="import-users" class="modal" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header d-flex align-items-center">
                                <h4 class="modal-title">Import Users</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <div class="form-group">
                                        <label class="control-label">Users:</label>
                                        <input class="form-control" name="users" placeholder="Format: username,hwid,days|username,hwid,days">
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Import from auth.gg:</label>
                                        <input class="form-control" name="authgg" placeholder="Paste in JSON from developers.auth.gg">
                                    </div>
                                    <br>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button class="btn btn-danger waves-effect waves-light" name="importusers">Add</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Extend User Modal -->
                <div id="extend-user" class="modal" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header d-flex align-items-center">
                                <h4 class="modal-title">Extend User(s)</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <div class="form-group">
                                        <label class="control-label">User:</label>
                                        <input type="text" class="form-control" name="user" placeholder="For all users, put: all" value="all" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Subscription:</label>
                                        <select name="sub" class="form-control">
                                            <?php sub_options($sub_plans); ?>
                                        </select>
                                    </div>
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
                                        <label class="control-label">Time To Add:</label>
                                        <input class="form-control" type="number" name="time" placeholder="Multiplied by selected unit of time" required>
                                    </div>
                                    <br>
                                    <input class="form-check-input" style="color:white;border-color:white;" name="activeOnly" type="checkbox" id="flexCheckCheckedExtend">
                                    <label class="form-check-label" for="flexCheckCheckedExtend">
                                        Active users only
                                    </label>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button class="btn btn-danger waves-effect waves-light" name="extenduser">Extend</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subtract User Modal -->
                <div id="subtract-user" class="modal" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header d-flex align-items-center">
                                <h4 class="modal-title">Subtract User(s)</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <div class="form-group">
                                        <label class="control-label">User:</label>
                                        <input type="text" class="form-control" name="user" placeholder="For all users, put: all" value="all" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Subscription:</label>
                                        <select name="sub" class="form-control">
                                            <?php sub_options($sub_plans); ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Unit Of Time To Subtract:</label>
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
                                        <label class="control-label">Time To Subtract:</label>
                                        <input class="form-control" name="time" placeholder="Multiplied by selected unit of time" required>
                                    </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button class="btn btn-danger waves-effect waves-light" name="subtractuser">Subtract</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ban User Modal -->
                <div id="ban-user" class="modal" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header d-flex align-items-center">
                                <h4 class="modal-title">Ban User</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <div class="form-group">
                                        <label class="control-label">Ban reason:</label>
                                        <input type="text" class="form-control" name="reason" placeholder="Reason for ban" required maxlength="99">
                                        <input type="hidden" class="banuser" name="un">
                                    </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button class="btn btn-danger" name="banuser">Ban</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit User Modal -->
                <div id="edit-user" class="modal" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header d-flex align-items-center">
                                <h4 class="modal-title">Edit User</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <input type="hidden" class="edituser" name="un">
                                    <div class="form-group">
                                        <label class="control-label">Subscription:</label>
                                        <select name="sub" class="form-control">
                                            <?php sub_options($sub_plans); ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">HWID:</label>
                                        <input type="text" class="form-control" name="hwid" placeholder="New HWID (leave blank to keep)">
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Subscription Expiry:</label>
                                        <input class="form-control" type="datetime-local" name="expiry">
                                    </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button class="btn btn-danger" name="edituser_submit">Save</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete All Users Modal -->
                <div class="modal fade" tabindex="-1" id="delete-allusers">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-body text-center p-5">
                                <h2 class="modal-title">Delete All Users</h2>
                                <lord-icon src="https://cdn.lordicon.com/tdrtiskw.json" trigger="loop" colors="primary:#f7b84b,secondary:#405189" style="width:130px;height:130px"></lord-icon>
                                <div class="mt-4 pt-3">
                                    <h4 class="mb-3">Notice</h4>
                                    <p class="text-muted mb-4">Are you sure you want to delete all users? This can not be undone.</p>
                                    <form method="POST">
                                        <div class="row g-3">
                                            <div class="col-lg-12">
                                                <div class="hstack gap-2 justify-content-end">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">No, cancel</button>
                                                    <button name="delusers" class="btn btn-danger">Yes, I'm sure</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete Expired Users Modal -->
                <div class="modal fade" tabindex="-1" id="delete-allexpired">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-body text-center p-5">
                                <h2 class="modal-title">Delete All Expired Users</h2>
                                <lord-icon src="https://cdn.lordicon.com/tdrtiskw.json" trigger="loop" colors="primary:#f7b84b,secondary:#405189" style="width:130px;height:130px"></lord-icon>
                                <div class="mt-4 pt-3">
                                    <h4 class="mb-3">Notice</h4>
                                    <p class="text-muted mb-4">Are you sure you want to delete all expired users? This can not be undone.</p>
                                    <form method="POST">
                                        <div class="row g-3">
                                            <div class="col-lg-12">
                                                <div class="hstack gap-2 justify-content-end">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">No, cancel</button>
                                                    <button name="delexpusers" class="btn btn-danger">Yes, I'm sure</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- HWID Reset All Users Modal -->
                <div class="modal fade" tabindex="-1" id="reset-allusers">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-body text-center p-5">
                                <h2 class="modal-title">HWID Reset All Users</h2>
                                <lord-icon src="https://cdn.lordicon.com/tdrtiskw.json" trigger="loop" colors="primary:#f7b84b,secondary:#405189" style="width:130px;height:130px"></lord-icon>
                                <div class="mt-4 pt-3">
                                    <h4 class="mb-3">Notice</h4>
                                    <p class="text-muted mb-4">Are you sure you want to hwid reset all users? This can not be undone.</p>
                                    <form method="POST">
                                        <div class="row g-3">
                                            <div class="col-lg-12">
                                                <div class="hstack gap-2 justify-content-end">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">No, cancel</button>
                                                    <button name="resetall" class="btn btn-danger">Yes, I'm sure</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Unban All Users Modal -->
                <div class="modal fade" tabindex="-1" id="unban-allusers">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-body text-center p-5">
                                <h2 class="modal-title">Unban All Users</h2>
                                <lord-icon src="https://cdn.lordicon.com/tdrtiskw.json" trigger="loop" colors="primary:#f7b84b,secondary:#405189" style="width:130px;height:130px"></lord-icon>
                                <div class="mt-4 pt-3">
                                    <h4 class="mb-3">Notice</h4>
                                    <p class="text-muted mb-4">Are you sure you want to unban all users? This can not be undone.</p>
                                    <form method="POST">
                                        <div class="row g-3">
                                            <div class="col-lg-12">
                                                <div class="hstack gap-2 justify-content-end">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">No, cancel</button>
                                                    <button name="unbanall" class="btn btn-danger">Yes, I'm sure</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <table id="kt_datatable_users" class="display table table-bordered dt-responsive" style="width:100%">
                    <thead>
                        <tr class="fw-bolder fs-6 text-gray-800 px-7">
                            <th>Username</th>
                            <th>HWID</th>
                            <th>IP</th>
                            <th>Creation Date</th>
                            <th>Last Login Date</th>
                            <th>Banned</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No users found</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <?php
                                $created = !empty($u['created_at']) ? date('Y-m-d H:i', strtotime($u['created_at'])) : '-';
                                $last_login = !empty($u['last_login']) ? date('Y-m-d H:i', strtotime($u['last_login'])) : '-';
                                $is_banned = intval($u['banned'] ?? 0);
                                $is_paused = intval($u['paused'] ?? 0);
                                if ($is_banned) {
                                    $status_badge = '<span class="badge bg-danger">Banned</span>';
                                } elseif ($is_paused) {
                                    $status_badge = '<span class="badge bg-warning text-dark">Paused</span>';
                                } else {
                                    $status_badge = '<span class="badge bg-success">Active</span>';
                                }
                                ?>
                            <tr>
                                <td><span class="fw-medium text-break"><?= htmlspecialchars($u['username']) ?></span></td>
                                <td><code class="text-muted small text-break" style="word-break:break-all;"><?= htmlspecialchars($u['hwid'] ?? 'N/A') ?></code></td>
                                <td><small class="text-muted"><?= htmlspecialchars($u['ip'] ?? 'N/A') ?></small></td>
                                <td><small class="text-muted"><?= $created ?></small></td>
                                <td><small class="text-muted"><?= $last_login ?></small></td>
                                <td><?= $status_badge ?></td>
                                <td>
                                    <div class="dropdown">
                                        <a class="btn btn-soft-secondary btn-sm dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Actions</a>
                                        <div class="dropdown-menu menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-bold fs-7 w-125px py-4">
                                            <form method="POST" style="display:contents;">
                                                <div class="dropdown-item"><button class="btn dropdown-item" style="font-size:0.95rem;" name="deleteuser" value="<?= htmlspecialchars($u['username']) ?>">Delete</button></div>
                                                <div class="dropdown-item"><button class="btn dropdown-item" style="font-size:0.95rem;" name="resetuser" value="<?= htmlspecialchars($u['username']) ?>">Reset HWID</button></div>
                                                <?php if ($is_banned): ?>
                                                <div class="dropdown-item"><button class="btn dropdown-item text-success" style="font-size:0.95rem;" name="unbanuser" value="<?= htmlspecialchars($u['username']) ?>">Unban</button></div>
                                                <?php else: ?>
                                                <div class="dropdown-item"><a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#ban-user" onclick="banuser('<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')">Ban</a></div>
                                                <?php endif; ?>
                                                <?php if ($is_paused): ?>
                                                <div class="dropdown-item"><button class="btn dropdown-item text-success" style="font-size:0.95rem;" name="unpauseuser" value="<?= htmlspecialchars($u['username']) ?>">Unpause</button></div>
                                                <?php else: ?>
                                                <div class="dropdown-item"><button class="btn dropdown-item" style="font-size:0.95rem;" name="pauseuser" value="<?= htmlspecialchars($u['username']) ?>">Pause</button></div>
                                                <?php endif; ?>
                                                <div class="dropdown-item"><a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-user" onclick="edituser('<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')">Edit</a></div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <script>
                function banuser(username) {
                    document.querySelector('.banuser').value = username;
                }
                function edituser(username) {
                    document.querySelector('.edituser').value = username;
                }
                </script>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#kt_datatable_users').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[3, 'desc']],
        columnDefs: [
            { orderable: false, targets: [6] }
        ]
    });
});
</script>
