<?php
if (!$selected_app_data) {
    echo '<div class="text-center py-5"><i class="ri-time-line fs-1 text-muted mb-3 d-block"></i><h5 class="text-muted">Select an application first</h5></div>';
    return;
}
$app_secret = $selected_app_data['secret'];

$stmt = $conn->prepare("SELECT *, UNIX_TIMESTAMP(created_at) AS created_ts FROM sessions WHERE app = ? ORDER BY created_at DESC LIMIT 500");
$stmt->bind_param("s", $app_secret);
$stmt->execute();
$sessions_result = $stmt->get_result();
$sessions = [];
while ($s = $sessions_result->fetch_assoc()) {
    $sessions[] = $s;
}
?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <div class="alert alert-danger" role="alert">
                    All of your active sessions. (Active Users)
                    <br>
                    Join Our Discord server <a href="./?page=help" target="_blank"><strong> For Support</strong></a>
                </div>

                <form method="post">
                    <button type="button" data-bs-toggle="modal" data-bs-target="#killallsessions" class="btn btn-danger waves-effect waves-light"><i class="ri-delete-bin-line"></i> Kill All Sessions</button>
                </form>

                <br>

                <table id="kt_datatable_sessions" class="display table table-bordered dt-responsive" style="width:100%">
                    <thead>
                        <tr class="fw-bolder fs-6 text-gray-800 px-7">
                            <th>ID</th>
                            <th>Credential</th>
                            <th>Expires</th>
                            <th>Authenticated</th>
                            <th>IP Address</th>
                            <th>Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sessions)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No sessions found</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($sessions as $s): ?>
                                <?php
                                $expiry = intval($s['expiry'] ?? 0);
                                $expires_str = $expiry ? date('Y-m-d H:i:s', $expiry) : '-';
                                $is_expired = $expiry > 0 && $expiry < time();
                                $is_validated = intval($s['validated'] ?? 0);
                                $validated_badge = $is_validated ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-warning">No</span>';
                                $credential = htmlspecialchars($s['credential'] ?? 'N/A');
                                $ip_addr = htmlspecialchars($s['ip'] ?? 'N/A');
                                $session_id_short = htmlspecialchars($s['id'] ?? '');
                                ?>
                            <tr>
                                <td><code class="small text-break" style="word-break:break-all;"><?= $session_id_short ?></code></td>
                                <td><span class="fw-medium"><?= $credential ?: '—' ?></span></td>
                                <td><small class="<?= $is_expired ? 'text-danger' : 'text-muted' ?>"><?= $expires_str ?></small></td>
                                <td><?= $validated_badge ?></td>
                                <td><small class="text-muted"><?= $ip_addr ?></small></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <button class="btn btn-sm btn-danger" name="killsession" value="<?= htmlspecialchars($s['id']) ?>" onclick="return confirm('Delete this session?')">Kill</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="modal fade" tabindex="-1" id="killallsessions">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-body text-center p-5">
                                <h2 class="modal-title">Kill All Sessions</h2>
                                <lord-icon src="https://cdn.lordicon.com/tdrtiskw.json" trigger="loop" colors="primary:#f7b84b,secondary:#405189" style="width:130px;height:130px"></lord-icon>
                                <div class="mt-4 pt-3">
                                    <h4 class="mb-3">Notice</h4>
                                    <p class="text-muted mb-4">Are you sure you want to delete all sessions? This can not be undone.</p>
                                    <form method="POST">
                                        <div class="row g-3">
                                            <div class="col-lg-12">
                                                <div class="hstack gap-2 justify-content-end">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">No, cancel</button>
                                                    <button name="killall" class="btn btn-danger">Yes, I'm sure</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#kt_datatable_sessions').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[2, 'desc']],
        columnDefs: [
            { orderable: false, targets: [5] }
        ]
    });
});
</script>
