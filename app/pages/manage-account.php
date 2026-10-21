<?php
$stmt = $conn->prepare("SELECT * FROM accounts WHERE ownerid = ? AND username != ? ORDER BY FIELD(role,'seller','manager'), username");
$stmt->bind_param("ss", $ownerid, $user['username']);
$stmt->execute();
$sub_accounts = $stmt->get_result();
?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <div class="alert alert-danger" role="alert">
                    Here You Can Manage Your Reseller and Manager Accounts.
                    <br>
                    Join Our Discord server <a href="?page=help" target="_blank"><strong> For Support</strong></a>
                </div>

                <button data-bs-toggle="modal" type="button" data-bs-target="#create-account"
                    class="btn btn-secondary waves-effect waves-light"><i class="ri-add-circle-line"></i>
                    Create Accounts</button>
                <br>

                <div id="create-account" class="modal" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header d-flex align-items-center">
                                <h4 class="modal-title">Add Accounts</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <div class="form-group">
                                        <label class="control-label">Account Role: <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="If reseller, account will be able to create as many keys as their balance allows. Balance can be given manually by you by editing account after it's made, or your reseller can purchase their balance. If manager, the account is locked to that certain application and can do just about everything except rename, pause, refresh secret, and delete application."></i></label>
                                        <select name="role" class="form-control">
                                            <option value="Reseller">Reseller</option>
                                            <option value="Manager">Manager</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Account Application:</label>
                                        <select name="app" class="form-control">
                                            <?php foreach ($applications as $a): ?>
                                            <option value="<?= htmlspecialchars($a['name']) ?>"><?= htmlspecialchars($a['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Account Username:</label>
                                        <input type="text" class="form-control" placeholder="Username for account you manage" name="username" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Account Email:</label>
                                        <input type="text" class="form-control" placeholder="Email for account you manage" name="email" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Account Password:</label>
                                        <input type="password" class="form-control" placeholder="Password for account you manage" name="pw" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Allowed License Levels:</label>
                                        <input type="text" class="form-control" placeholder="Enter Levels In Format: 1|2|3, Leave It Blank For All Levels" name="keylevels">
                                    </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button class="btn btn-danger" name="createacc">Add</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="edit-account" class="modal" role="dialog" aria-hidden="true" style="display: none;">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header d-flex align-items-center">
                                <h4 class="modal-title">Edit Account</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <input type="hidden" name="editacc" id="edit-username">
                                    <div class="form-group">
                                        <label class="control-label">Account Application:</label>
                                        <select name="app" id="edit-app" class="form-control">
                                            <?php foreach ($applications as $a): ?>
                                            <option value="<?= htmlspecialchars($a['name']) ?>"><?= htmlspecialchars($a['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group" id="edit-balance-group">
                                        <label class="control-label">Key Balance:</label>
                                        <input type="number" class="form-control" id="edit-balance" name="balance" value="0" min="0">
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">Allowed License Levels:</label>
                                        <input type="text" class="form-control" id="edit-levels" name="allowed_levels" placeholder="Enter Levels In Format: 1|2|3, Leave It Blank For All Levels">
                                    </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button class="btn btn-primary" name="editacc_submit">Save Changes</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <br>

                <table id="kt_datatable_manageaccs" class="display table table-bordered dt-responsive" style="width:100%">
                    <thead>
                        <tr class="fw-bolder fs-6 text-gray-800 px-7">
                            <th>Username</th>
                            <th>Role</th>
                            <th>Application</th>
                            <th>Balance</th>
                            <th>Allowed Levels</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($sub = $sub_accounts->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($sub['username']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($sub['role'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($sub['app'] ?? 'N/A') ?></td>
                            <td><?= ($sub['role'] ?? '') === 'seller' ? ($sub['balance'] ?? 0) : 'N/A' ?></td>
                            <td><?= !empty($sub['allowed_levels']) ? htmlspecialchars($sub['allowed_levels']) : 'N/A' ?></td>
                            <td>
                                <div class="dropdown">
                                    <a class="btn btn-soft-secondary btn-sm dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        Actions
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-account"
                                            data-username="<?= htmlspecialchars($sub['username']) ?>"
                                            data-app="<?= htmlspecialchars($sub['app'] ?? '') ?>"
                                            data-balance="<?= ($sub['role'] ?? '') === 'seller' ? ($sub['balance'] ?? 0) : '' ?>"
                                            data-levels="<?= htmlspecialchars($sub['allowed_levels'] ?? '') ?>"
                                            data-role="<?= htmlspecialchars($sub['role'] ?? '') ?>">
                                            Edit
                                        </button>
                                        <form method="POST" style="display:inline">
                                            <button class="dropdown-item text-danger" name="deleteacc" value="<?= htmlspecialchars($sub['username']) ?>">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#kt_datatable_manageaccs').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[1, 'asc']]
    });

    $('#edit-account').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var username = button.data('username');
        var app = button.data('app');
        var balance = button.data('balance');
        var levels = button.data('levels');
        var role = button.data('role');
        var modal = $(this);
        modal.find('#edit-username').val(username);
        modal.find('#edit-app').val(app);
        modal.find('#edit-balance').val(balance || 0);
        modal.find('#edit-levels').val(levels);
        if (role === 'manager') {
            modal.find('#edit-balance-group').hide();
        } else {
            modal.find('#edit-balance-group').show();
        }
    });
});
</script>