<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Web Loader</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info" role="alert">
                    Configure your web loader settings. Web loader allows users to load your application directly from the browser.
                    <br>
                    Join Our Discord server <a href="./?page=help" target="_blank"><strong>For Support</strong></a>
                </div>

                <?php if ($selected_app_data): ?>
                <form method="post" class="form">
                    <div class="form-group row mb-3">
                        <label class="col-2 col-form-label">Web Download URL</label>
                        <div class="col-10">
                            <input class="form-control" name="webdownload" value="<?= htmlspecialchars($selected_app_data['settings'] ? (json_decode($selected_app_data['settings'], true)['webdownload'] ?? '') : '') ?>" type="text" placeholder="URL link for web loader" maxlength="120">
                            <small class="text-muted">Set this URL to enable the web loader for your application. Leave empty to disable.</small>
                        </div>
                    </div>
                    <div class="form-group row mb-3">
                        <label class="col-2 col-form-label">Download URL</label>
                        <div class="col-10">
                            <input class="form-control" name="download" value="<?= htmlspecialchars($selected_app_data['settings'] ? (json_decode($selected_app_data['settings'], true)['download'] ?? '') : '') ?>" type="text" placeholder="Auto-update download URL" maxlength="120">
                            <small class="text-muted">URL opened when user runs an outdated version.</small>
                        </div>
                    </div>
                    <div class="form-group row mb-3">
                        <label class="col-2 col-form-label">Application Version</label>
                        <div class="col-10">
                            <input class="form-control" maxlength="5" name="version" value="<?= htmlspecialchars($selected_app_data['version']) ?>" placeholder="Application Version" required>
                        </div>
                    </div>
                    <div class="form-group row mb-3">
                        <label class="col-2 col-form-label">Customer Panel</label>
                        <div class="col-10">
                            <label class="form-control" style="height:auto;">
                                <a href="<?= SITE_URL ?>/panel/<?= urlencode($ownerid) ?>/<?= urlencode($selected_app_data['name']) ?>" target="_blank"><?= SITE_URL ?>/panel/<?= urlencode($ownerid) ?>/<?= urlencode($selected_app_data['name']) ?></a>
                            </label>
                        </div>
                    </div>
                    <br>
                    <button name="updatesettings" class="btn btn-success px-4"><i class="ri-save-line me-1"></i> Save settings</button>
                </form>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="ri-window-line fs-1 text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">Select an application</h5>
                    <p class="text-muted mb-0">Use the app selector in the top bar to choose an application to configure the web loader for.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>