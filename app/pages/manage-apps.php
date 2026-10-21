<?php
$selected_app_name = '';
if (!empty($selected_app)) {
    foreach ($applications as $a) {
        if ($a['secret'] === $selected_app) { $selected_app_name = $a['name']; break; }
    }
}
?>
<div class="row">
    <div class="col-xxl-12">
        <h5 class="mb-3">Application Example</h5>
        <div class="card">
            <div class="card-body">
                <p class="text-muted">Simply replace the placeholder code in the example with these</p>
                <ul class="nav nav-pills nav-customs nav-danger mb-3" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#csharp_example" role="tab">C#</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#cpp_example" role="tab">C++</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#python_example" role="tab">Python</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#php_example" role="tab">PHP</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#javascript_example" role="tab">JavaScript</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#java_example" role="tab">Java</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#vbnet_example" role="tab">VB.Net</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#rush_example" role="tab">Rush</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#go_example" role="tab">Go</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#lua_example" role="tab">Lua</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#ruby_example" role="tab">Ruby</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#perl_example" role="tab">Perl</a></li>
                </ul>
                <div class="tab-content text-muted">
                    <div class="tab-pane active" id="csharp_example" role="tabpanel">
                        <pre class="language-css"><code>public static api LicenseAuthApp = new api(
    name: "<?= htmlspecialchars($selected_app_name ?: ($applications[0]['name'] ?? 'YourApp')) ?>",
    ownerid: "<?= htmlspecialchars($ownerid) ?>",
    secret: "<?= htmlspecialchars($selected_app ?: ($applications[0]['secret'] ?? 'your_secret')) ?>",
    version: "<?= htmlspecialchars($selected_app_data['version'] ?? '1.0') ?>"
);</code></pre>
                    </div>
                    <div class="tab-pane" id="cpp_example" role="tabpanel">
                        <pre class="language-css"><code>std::string name = skCrypt("<?= htmlspecialchars($selected_app_name ?: ($applications[0]['name'] ?? 'YourApp')) ?>").decrypt();
std::string ownerid = skCrypt("<?= htmlspecialchars($ownerid) ?>").decrypt();
std::string secret = skCrypt("<?= htmlspecialchars($selected_app ?: ($applications[0]['secret'] ?? 'your_secret')) ?>").decrypt();
std::string version = skCrypt("<?= htmlspecialchars($selected_app_data['version'] ?? '1.0') ?>").decrypt();
std::string url = skCrypt("<?= SITE_URL ?>/api/1.2/").decrypt();</code></pre>
                    </div>
                    <div class="tab-pane" id="python_example" role="tabpanel">
                        <pre class="language-css"><code>licenseauthapp = api(
    name = "<?= htmlspecialchars($selected_app_name ?: ($applications[0]['name'] ?? 'YourApp')) ?>",
    ownerid = "<?= htmlspecialchars($ownerid) ?>",
    secret = "<?= htmlspecialchars($selected_app ?: ($applications[0]['secret'] ?? 'your_secret')) ?>",
    version = "<?= htmlspecialchars($selected_app_data['version'] ?? '1.0') ?>",
)</code></pre>
                    </div>
                    <div class="tab-pane" id="php_example" role="tabpanel">
                        <pre class="language-css"><code>$name = "<?= htmlspecialchars($selected_app_name ?: ($applications[0]['name'] ?? 'YourApp')) ?>";
$ownerid = "<?= htmlspecialchars($ownerid) ?>";</code></pre>
                    </div>
                    <div class="tab-pane" id="javascript_example" role="tabpanel">
                        <pre class="language-css"><code>const LicenseAuthApp = new LicenseAuth(
    "<?= htmlspecialchars($selected_app_name ?: ($applications[0]['name'] ?? 'YourApp')) ?>",
    "<?= htmlspecialchars($ownerid) ?>",
    "<?= htmlspecialchars($selected_app ?: ($applications[0]['secret'] ?? 'your_secret')) ?>",
    "<?= htmlspecialchars($selected_app_data['version'] ?? '1.0') ?>",
);</code></pre>
                    </div>
                    <div class="tab-pane" id="java_example" role="tabpanel">
                        <pre class="language-css"><code>private static String ownerid = "<?= htmlspecialchars($ownerid) ?>";
private static String appname = "<?= htmlspecialchars($selected_app_name ?: ($applications[0]['name'] ?? 'YourApp')) ?>";
private static String version = "<?= htmlspecialchars($selected_app_data['version'] ?? '1.0') ?>";</code></pre>
                    </div>
                    <div class="tab-pane" id="vbnet_example" role="tabpanel">
                        <pre class="language-css"><code>Private Shared name As String = "<?= htmlspecialchars($selected_app_name ?: ($applications[0]['name'] ?? 'YourApp')) ?>"
Private Shared ownerid As String = "<?= htmlspecialchars($ownerid) ?>"
Private Shared secret As String = "<?= htmlspecialchars($selected_app ?: ($applications[0]['secret'] ?? 'your_secret')) ?>"
Private Shared version As String = "<?= htmlspecialchars($selected_app_data['version'] ?? '1.0') ?>"</code></pre>
                    </div>
                    <div class="tab-pane" id="rush_example" role="tabpanel">
                        <pre class="language-css"><code>let mut licenseauthapp = licenseauth::v1_2::LicenseauthApi::new(
    "<?= htmlspecialchars($selected_app_name ?: ($applications[0]['name'] ?? 'YourApp')) ?>",
    "<?= htmlspecialchars($ownerid) ?>",
    "<?= htmlspecialchars($selected_app ?: ($applications[0]['secret'] ?? 'your_secret')) ?>",
    "<?= htmlspecialchars($selected_app_data['version'] ?? '1.0') ?>",
    "<?= SITE_URL ?>/api/1.2/",
);</code></pre>
                    </div>
                    <div class="tab-pane" id="go_example" role="tabpanel">
                        <pre class="language-css"><code>var name = "<?= htmlspecialchars($selected_app_name ?: ($applications[0]['name'] ?? 'YourApp')) ?>"
var ownerid = "<?= htmlspecialchars($ownerid) ?>"
var version = "<?= htmlspecialchars($selected_app_data['version'] ?? '1.0') ?>"</code></pre>
                    </div>
                    <div class="tab-pane" id="lua_example" role="tabpanel">
                        <pre class="language-css"><code>local name = "<?= htmlspecialchars($selected_app_name ?: ($applications[0]['name'] ?? 'YourApp')) ?>";
local ownerid = "<?= htmlspecialchars($ownerid) ?>";
local version = "<?= htmlspecialchars($selected_app_data['version'] ?? '1.0') ?>";</code></pre>
                    </div>
                    <div class="tab-pane" id="ruby_example" role="tabpanel">
                        <pre class="language-css"><code>LicenseAuth.new.Api(
    "<?= htmlspecialchars($selected_app_name ?: ($applications[0]['name'] ?? 'YourApp')) ?>",
    "<?= htmlspecialchars($ownerid) ?>",
    "<?= htmlspecialchars($selected_app ?: ($applications[0]['secret'] ?? 'your_secret')) ?>",
    "<?= htmlspecialchars($selected_app_data['version'] ?? '1.0') ?>"
)</code></pre>
                    </div>
                    <div class="tab-pane" id="perl_example" role="tabpanel">
                        <pre class="language-css"><code>LicenseAuth::Api(
    "<?= htmlspecialchars($selected_app_name ?: ($applications[0]['name'] ?? 'YourApp')) ?>",
    "<?= htmlspecialchars($ownerid) ?>",
    "<?= htmlspecialchars($selected_app ?: ($applications[0]['secret'] ?? 'your_secret')) ?>",
    "<?= htmlspecialchars($selected_app_data['version'] ?? '1.0') ?>"
);</code></pre>
                    </div>
                </div>
                <div class="alert alert-warning" role="alert">
                    Example Files: <a href="https://files.licenseauth.cloud" target="_blank">https://files.licenseauth.cloud</a><br>
                    Videos: <a href="https://videos.licenseauth.cloud" target="_blank">https://videos.licenseauth.cloud</a>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#create-app-modal">
                <i class="ri-add-line align-middle me-1"></i> Create New App
            </button>
            <button type="button" class="btn btn-soft-info" data-bs-toggle="modal" data-bs-target="#rename-app-modal">
                <i class="ri-pencil-line align-middle me-1"></i> Rename App
            </button>
            <button type="button" class="btn btn-soft-warning" data-bs-toggle="modal" data-bs-target="#pause-app-modal">
                <i class="ri-pause-line align-middle me-1"></i> Pause App & Users
            </button>
            <button type="button" class="btn btn-soft-success" data-bs-toggle="modal" data-bs-target="#unpause-app-modal">
                <i class="ri-play-line align-middle me-1"></i> Unpause App
            </button>
            <button type="button" class="btn btn-soft-secondary" data-bs-toggle="modal" data-bs-target="#refresh-secret-modal">
                <i class="ri-refresh-line align-middle me-1"></i> Refresh App Secret
            </button>
            <button type="button" class="btn btn-soft-danger" data-bs-toggle="modal" data-bs-target="#delete-app-modal">
                <i class="ri-delete-bin-line align-middle me-1"></i> Delete App
            </button>
        </div>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">All Application</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="applicationTable">
                        <thead>
                            <tr>
                                <th>Application Name</th>
                                <th>App Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
<?php if (!empty($applications)): ?>
<?php foreach ($applications as $app): ?>
                            <tr>
                                <td><?= htmlspecialchars($app['name']) ?></td>
                                <td><span class="badge bg-<?= empty($app['paused']) ? 'success' : 'danger' ?>-subtle text-<?= empty($app['paused']) ? 'success' : 'danger' ?>"><?= empty($app['paused']) ? 'Active' : 'Paused' ?></span></td>
                                <td class="text-end">
                                    <form method="post" action="" style="display:inline;">
                                        <input type="hidden" name="appname" value="<?= htmlspecialchars($app['name']) ?>">
                                        <button type="submit" name="selectApp" class="btn btn-<?= $selected_app === $app['secret'] ? 'success' : 'primary' ?> btn-sm" value="<?= htmlspecialchars($app['name']) ?>">
                                            <?= $selected_app === $app['secret'] ? 'Selected' : 'Select' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
<?php endforeach; ?>
<?php else: ?>
                            <tr><td colspan="3" class="text-center py-4 text-muted">No applications found. Create one above.</td></tr>
<?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create App Modal -->
<div class="modal fade" id="create-app-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New App</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">App Name</label>
                        <input type="text" name="app_name" class="form-control" placeholder="Enter app name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional description"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Version</label>
                        <input type="text" name="version" class="form-control" value="1.0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_app" class="btn btn-primary">Create App</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Rename App Modal -->
<div class="modal fade" id="rename-app-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rename App</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">New App Name</label>
                        <input type="text" name="appname" class="form-control" placeholder="Enter new app name" required>
                    </div>
                    <?php if ($selected_app_data): ?>
                    <p class="text-muted small mb-0">Renaming: <strong><?= htmlspecialchars($selected_app_data['name']) ?></strong>. Your app secret and other settings will remain the same.</p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="rename_app" class="btn btn-info">Rename App</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Pause App Modal -->
<div class="modal fade" id="pause-app-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pause App & Users</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">App Name</label>
                        <input type="text" name="appname" class="form-control" placeholder="Enter app name" required>
                    </div>
                    <div class="alert alert-warning mb-0">
                        <i class="ri-alert-line me-1"></i> This will suspend all active sessions and license keys for this application. Users will not be able to authenticate until unpaused.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="pauseapp" class="btn btn-warning">Pause App</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Unpause App Modal -->
<div class="modal fade" id="unpause-app-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Unpause App</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">App Name</label>
                        <input type="text" name="appname" class="form-control" placeholder="Enter app name" required>
                    </div>
                    <div class="alert alert-success mb-0">
                        <i class="ri-information-line me-1"></i> This will re-activate the application and allow users to authenticate again.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="unpauseapp" class="btn btn-success">Unpause App</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Refresh Secret Modal -->
<div class="modal fade" id="refresh-secret-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Refresh App Secret</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">App Name</label>
                        <input type="text" name="appname" class="form-control" placeholder="Enter app name" required>
                    </div>
                    <div class="alert alert-danger mb-0">
                        <i class="ri-alert-line me-1"></i> Warning: Refreshing the secret will invalidate all existing integrations. You will need to update your application with the new secret.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="refreshapp" class="btn btn-secondary">Refresh Secret</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete App Modal -->
<div class="modal fade" id="delete-app-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete App</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">App Name</label>
                        <input type="text" name="appname" class="form-control" placeholder="Enter app name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type "Confirm" to delete</label>
                        <input type="text" name="confirmappname" class="form-control" placeholder='Type "Confirm"' required>
                    </div>
                    <div class="alert alert-danger mb-0">
                        <i class="ri-alert-line me-1"></i> This action cannot be undone. All licenses, users, and data associated with this app will be permanently deleted.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="deleteapp" class="btn btn-danger">Delete App</button>
                </div>
            </form>
        </div>
    </div>
</div>
