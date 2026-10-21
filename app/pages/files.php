<?php
if (!$selected_app_data) {
    echo '<div class="text-center py-5"><i class="ri-file-line fs-1 text-muted mb-3 d-block"></i><h5 class="text-muted">Select an application first</h5></div>';
    return;
}
$app_secret = $selected_app_data['secret'];

$stmt = $conn->prepare("SELECT * FROM files WHERE app = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $app_secret);
$stmt->execute();
$files_result = $stmt->get_result();
$files = [];
while ($file = $files_result->fetch_assoc()) {
    $files[] = $file;
}
?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="mb-0">File Downloads</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadFileModal"><i class="ri-upload-line align-middle me-1"></i> Upload File</button>
                </div>
                <?php if (empty($files)): ?>
                <div class="text-center py-5">
                    <i class="ri-file-line fs-1 text-muted mb-3 d-block"></i>
                    <h6 class="text-muted">No files uploaded yet</h6>
                    <p class="text-muted small">Upload files that users can download via the API.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Filename</th>
                                <th>Size</th>
                                <th>Downloads</th>
                                <th>Uploaded</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($files as $file): ?>
                            <tr>
                                <td><i class="ri-file-line me-1"></i> <?= htmlspecialchars($file['filename']) ?></td>
                                <td><small class="text-muted"><?= htmlspecialchars($file['size'] ?? 'N/A') ?></small></td>
                                <td><small class="text-muted"><?= intval($file['downloads'] ?? 0) ?></small></td>
                                <td><small class="text-muted"><?= date('M d, Y', strtotime($file['created_at'])) ?></small></td>
                                <td>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this file?')">
                                        <input type="hidden" name="action" value="delete_file">
                                        <input type="hidden" name="id" value="<?= $file['id'] ?>">
                                        <input type="hidden" name="app" value="<?= htmlspecialchars($app_secret) ?>">
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="ri-delete-bin-line"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="uploadFileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="upload_file">
                    <input type="hidden" name="app" value="<?= htmlspecialchars($app_secret) ?>">
                    <div class="mb-3">
                        <label class="form-label">Select File</label>
                        <input type="file" name="file" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="ri-upload-line me-1"></i> Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>
