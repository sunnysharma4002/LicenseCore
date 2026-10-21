<?php
$app_data = $selected_app_data;
$settings = [];
if ($app_data && !empty($app_data['settings'])) {
    $settings = json_decode($app_data['settings'], true) ?? [];
}
$panel_link = SITE_URL . '/panel/' . urlencode($ownerid) . '/' . urlencode($app_data['name'] ?? '');
$reseller_webhook = SITE_URL . '/api/reseller/?app=' . urlencode($app_data['secret'] ?? '');

$sel = function($key, $val) use ($settings) { return (isset($settings[$key]) && $settings[$key] == $val) ? 'selected' : ''; };
$v = function($key, $d = '') use ($settings) { return htmlspecialchars($settings[$key] ?? $d); };

if (!$app_data):
?>
<div class="row"><div class="col-lg-12"><div class="card"><div class="card-body text-center py-5">
    <i class="ri-settings-line fs-1 text-muted mb-3 d-block"></i>
    <h5 class="text-muted">Select an application to manage settings</h5>
    <p class="text-muted mb-0">Use the app selector in the top bar to choose an application.</p>
</div></div></div></div>
<?php return; endif; ?>
<!--begin::Container-->
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <div class="alert alert-danger" role="alert">
                    Manage Your Application Setting.
                    <br>
                    Join Our Discord server <a href="./?page=help" target="_blank"><strong> For Support</strong></a>
                </div>
                <form method="post">
                    <button data-bs-toggle="modal" type="button" data-bs-target="#add-hash" class="btn btn-secondary waves-effect waves-light"><i class="ri-add-circle-line"></i> Add hash</button><br><br>
                    <button data-bs-toggle="modal" type="button" data-bs-target="#reset-hash" class="btn btn-danger waves-effect waves-light"><i class="ri-restart-line"></i> Reset program hash</button>
                </form>
                <br>

                <div id="add-hash" class="modal" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header d-flex align-items-center">
                                <h4 class="modal-title">Add hash</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <div class="form-group">
                                        <label for="recipient-name" class="control-label">Application hash:</label>
                                        <input type="text" class="form-control" name="hash" placeholder="MD5 program hash to add" required>
                                    </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button class="btn btn-danger waves-effect waves-light" name="addhash">Add</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" tabindex="-1" id="reset-hash">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-body text-center p-5">
                                <h2 class="modal-title">Reset Hash</h2>
                                <lord-icon src="https://cdn.lordicon.com/tdrtiskw.json" trigger="loop" colors="primary:#f7b84b,secondary:#405189" style="width:130px;height:130px"></lord-icon>
                                <div class="mt-4 pt-3">
                                    <h4 class="mb-3">Notice</h4>
                                    <p class="text-muted mb-4">Are you sure you want to reset hash? Only do if you're releasing new program. This can not be undone.</p>
                                    <form method="POST">
                                        <div class="row g-3">
                                            <div class="col-lg-12">
                                                <div class="hstack gap-2 justify-content-end">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">No, cancel</button>
                                                    <button name="resethash" class="btn btn-danger">Yes, I'm sure</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <form class="form" method="post">
                            <button name="updatesettings" class="btn btn-success"> <i class="fa fa-check"></i> Save settings</button>
                            <br><br>
                            <div class="form-group row">
                                <label for="example-text-input" class="col-2 col-form-label">Status <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="Allow people to open application or not"></i></label>
                                <div class="col-10">
                                    <select class="form-control" name="statusinput">
                                        <option value="0" <?= (!isset($settings['statusinput']) ? '' : $sel('statusinput', '0')) ?>>Disabled</option>
                                        <option value="1" <?= (!isset($settings['statusinput']) ? 'selected' : $sel('statusinput', '1')) ?>>Enabled</option>
                                    </select>
                                </div>
                            </div>
                            <br>
                            <?php if (($user['role'] ?? '') !== 'tester'): ?>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">API custom domain <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="Most auths get blocked by some internet providers falsely. Using a custom domain will fix this, Join Our Discord For Help"></i></label>
                                <div class="col-10">
                                    <label class="form-control" style="height:auto;">You just need to set a CNAME record to <code>api.licenseauth.cloud</code> Make sure you're using Cloudflare!</label>
                                </div>
                            </div>
                            <br>
                            <?php endif; ?>
                            <div class="form-group row">
                                <label for="example-text-input" class="col-2 col-form-label">HWID Lock <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="Lock user to a value from your user's computer which only changes if they reinstall windows. Use this to prevent people sharing your product"></i></label>
                                <div class="col-10">
                                    <select class="form-control" name="hwidinput">
                                        <option value="0" <?= $sel('hwidinput', '0') ?>>Disabled</option>
                                        <option value="1" <?= $sel('hwidinput', '1') ?>>Enabled</option>
                                    </select>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-text-input" class="col-2 col-form-label">Force HWID Lock <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="Deny users logging in with blank HWID (disable for PHP example)"></i></label>
                                <div class="col-10">
                                    <select class="form-control" name="forceHwid">
                                        <option value="0" <?= $sel('forceHwid', '0') ?>>Disabled</option>
                                        <option value="1" <?= $sel('forceHwid', '1') ?>>Enabled</option>
                                    </select>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-text-input" class="col-2 col-form-label">Minimum HWID length <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="Deny users logging in with a HWID shorter than this number in characters."></i></label>
                                <div class="col-10">
                                    <input class="form-control" type="number" name="minHwid" value="<?= $v('minHwid', '20') ?>" placeholder="Minimum HWID length in characters">
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-text-input" class="col-2 col-form-label">VPN Block <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="Block IP addresses associated with VPNs"></i></label>
                                <div class="col-10">
                                    <select class="form-control" name="vpninput">
                                        <option value="0" <?= $sel('vpninput', '0') ?>>Disabled</option>
                                        <option value="1" <?= $sel('vpninput', '1') ?>>Enabled</option>
                                    </select>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-text-input" class="col-2 col-form-label">Hash Check <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="Checks whether the application has been modified since the last time you pressed reset hash button. Used to stop people altering your program to bypass it."></i></label>
                                <div class="col-10">
                                    <select class="form-control" name="hashinput">
                                        <option value="0" <?= $sel('hashinput', '0') ?>>Disabled</option>
                                        <option value="1" <?= $sel('hashinput', '1') ?>>Enabled</option>
                                    </select>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-text-input" class="col-2 col-form-label">Token Validation <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="Checks to see if a user has a valid token to use your application."></i></label>
                                <div class="col-10">
                                    <select class="form-control" name="tokeninput">
                                        <option value="0" <?= $sel('tokeninput', '0') ?>>Disabled</option>
                                        <option value="1" <?= $sel('tokeninput', '1') ?>>Enabled</option>
                                    </select>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Version <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="If you change this, the download link will be opened on your user's computer when they run the loader with the old version."></i></label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="5" name="version" value="<?= $v('version', '2.4') ?>" placeholder="Application Verion.." required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Download</label>
                                <div class="col-10">
                                    <input class="form-control" name="download" value="<?= $v('download') ?>" type="text" placeholder="URL Link That Will Be Opened If Version doesn't match (auto update)" maxlength="120">
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Web Download</label>
                                <div class="col-10">
                                    <input class="form-control" name="webdownload" value="<?= $v('webdownload') ?>" type="text" placeholder="URL link for web loader (this will enable web loader if not empty)" maxlength="120">
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Webhook <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="This is where you put Discord webhooks, not the webhooks page."></i></label>
                                <div class="col-10">
                                    <input class="form-control" name="webhook" value="<?= $v('webhook') ?>" type="text" placeholder="Discord Webhook Link For Sending Notifications & Logs">
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Reseller Store <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="If you're not using built-in reseller system, set a link will show to your resellers for them to buy keys."></i></label>
                                <div class="col-10">
                                    <input class="form-control" name="resellerstore" value="<?= $v('resellerstore') ?>" placeholder="If you don't want to use the inbuilt store for resellers, set a store link." type="text">
                                </div>
                            </div>
                            <?php if (!in_array(($user['role'] ?? ''), ['developer', 'tester'])): ?>
                            <br>
                            <div class="form-group row">
                                <label for="example-text-input" class="col-2 col-form-label">Customer Panel <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="Allows your customers to log in with their username and password (which if using just key is the same) and reset their HWID and download latest application from LicenseAuth website"></i></label>
                                <div class="col-10">
                                    <select class="form-control" name="panelstatus">
                                        <option value="0" <?= $sel('panelstatus', '0') ?>>Disabled</option>
                                        <option value="1" <?= $sel('panelstatus', '1') ?>>Enabled</option>
                                    </select>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Customer panel link</label>
                                <div class="col-10">
                                    <label class="form-control" style="height:auto;"><a href="<?= htmlspecialchars($panel_link) ?>" target="_blank"><?= htmlspecialchars($panel_link) ?></a></label>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Customer panel custom domain <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="Custom domain for customer panel. Please Join Our Discord Server For Help"></i></label>
                                <div class="col-10">
                                    <input class="form-control" name="customDomain" value="<?= $v('customDomain') ?>" placeholder="panel.example.com" type="text">
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Customer panel icon <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="Image shown in SEO and next to title in browser tab"></i></label>
                                <div class="col-10">
                                <input class="form-control" name="customerPanelIcon" value="<?= $v('customerPanelIcon', '<?= SITE_URL ?>/assets/images/favicon.png') ?>" placeholder="<?= SITE_URL ?>/assets/images/favicon.png" type="text" required>
                                </div>
                            </div>
                            <br>
                            <?php endif; ?>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">HWID Reset Cooldown Unit: <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="Delay between the last time your customer reset their HWID to when they can reset again. That way, they can't reset it once, allow their friend to login, and then reset it again for themselves."></i></label>
                                <div class="col-10">
                                    <select name="cooldownexpiry" class="form-control">
                                        <option value="86400" <?= $sel('cooldownexpiry', '86400') ?>>Days</option>
                                        <option value="60" <?= $sel('cooldownexpiry', '60') ?>>Minutes</option>
                                        <option value="3600" <?= $sel('cooldownexpiry', '3600') ?>>Hours</option>
                                        <option value="1" <?= $sel('cooldownexpiry', '1') ?>>Seconds</option>
                                        <option value="604800" <?= $sel('cooldownexpiry', '604800') ?>>Weeks</option>
                                        <option value="2629743" <?= $sel('cooldownexpiry', '2629743') ?>>Months</option>
                                        <option value="31556926" <?= $sel('cooldownexpiry', '31556926') ?>>Years</option>
                                        <option value="315569260" <?= $sel('cooldownexpiry', '315569260') ?>>Lifetime</option>
                                    </select>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">HWID Reset Cooldown Duration:</label>
                                <div class="col-10">
                                    <input name="cooldownduration" type="number" class="form-control" value="<?= $v('cooldownduration', '7') ?>" placeholder="Multiplied by selected cooldown unit" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Session Expiry Unit: <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="This is how long your users can stay logged in for. After it pasts this time, if you call any functions that require a session it will close the loader."></i></label>
                                <div class="col-10">
                                    <select name="sessionexpiry" class="form-control">
                                        <option value="86400" <?= $sel('sessionexpiry', '86400') ?>>Days</option>
                                        <option value="60" <?= $sel('sessionexpiry', '60') ?>>Minutes</option>
                                        <option value="3600" <?= $sel('sessionexpiry', '3600') ?>>Hours</option>
                                        <option value="1" <?= $sel('sessionexpiry', '1') ?>>Seconds</option>
                                        <option value="604800" <?= $sel('sessionexpiry', '604800') ?>>Weeks</option>
                                    </select>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Session Expiry Duration:</label>
                                <div class="col-10">
                                    <input name="sessionduration" type="number" class="form-control" value="<?= $v('sessionduration', '6') ?>" placeholder="Multiplied by selected expiry unit" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Minimum username length: <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="Reject registrations with username having character count under this number"></i></label>
                                <div class="col-10">
                                    <input name="minUsernameLength" type="number" class="form-control" value="<?= $v('minUsernameLength', '0') ?>" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-text-input" class="col-2 col-form-label">Block leaked passwords <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="Uses HaveIBeenPwned API to securely check if password has been leaked without sharing password."></i></label>
                                <div class="col-10">
                                    <select class="form-control" name="blockLeakedPasswords">
                                        <option value="0" <?= $sel('blockLeakedPasswords', '0') ?>>Disabled</option>
                                        <option value="1" <?= $sel('blockLeakedPasswords', '1') ?>>Enabled</option>
                                    </select>
                                </div>
                            </div>
                            <br><br><br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">App Disabled Msg <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="All the textboxes in this section are the custom error responses. Success messages aren't custom since you shouldn't need to show it."></i></label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="100" name="appdisabled" value="<?= $v('appdisabled', 'This application is disabled') ?>" placeholder="Custom response you'd like. Max 100 chars" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Token Validation Msg</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="100" name="tokeninvalid" value="<?= $v('tokeninvalid', 'Session is not validated') ?>" placeholder="Custom response you'd like. Max 100 chars" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Hash check Msg</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="100" name="hashcheckfail" value="<?= $v('hashcheckfail', 'Token Invalid') ?>" placeholder="Custom response you'd like. Max 100 chars" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">VPN Blocked Msg</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="100" name="vpnblocked" value="<?= $v('vpnblocked', 'VPNs are disallowed on this application') ?>" placeholder="Custom response you'd like. Max 100 chars" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Username Taken Msg</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="100" name="usernametaken" value="<?= $v('usernametaken', 'Username Already Exists.') ?>" placeholder="Custom response you'd like. Max 100 chars" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Invalid Key Msg</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="100" name="keynotfound" value="<?= $v('keynotfound', 'Key Not Found.') ?>" placeholder="Custom response you'd like. Max 100 chars" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Used Key Msg</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="100" name="keyused" value="<?= $v('keyused', 'Key Already Used.') ?>" placeholder="Custom response you'd like. Max 100 chars" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Key Banned Msg</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="100" name="keybanned" value="<?= $v('keybanned', 'Your license is banned') ?>" placeholder="Custom response you'd like. Max 100 chars" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">No Subs Msg</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="100" name="nosublevel" value="<?= $v('nosublevel', 'There is no subscription created for your key level. Contact application developer.') ?>" placeholder="Custom response you'd like. Max 100 chars" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">User Banned Msg</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="100" name="userbanned" value="<?= $v('userbanned', 'The user is banned') ?>" placeholder="Custom response you'd like. Max 100 chars" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Username Invalid Msg</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="100" name="usernamenotfound" value="<?= $v('usernamenotfound', 'Username not found.') ?>" placeholder="Custom response you'd like. Max 100 chars" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Password Mismatch Msg</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="100" name="passmismatch" value="<?= $v('passmismatch', 'Password does not match.') ?>" placeholder="Custom response you'd like. Max 100 chars" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Hwid Mismatch Msg</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="100" name="hwidmismatch" value="<?= $v('hwidmismatch', 'HWID Doesn\'t match. Ask for key reset.') ?>" placeholder="Custom response you'd like. Max 100 chars" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Expired Sub Msg</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="100" name="noactivesubs" value="<?= $v('noactivesubs', 'No active subscriptions found.') ?>" placeholder="Custom response you'd like. Max 100 chars" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Blacklisted Msg</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="100" name="hwidblacked" value="<?= $v('hwidblacked', 'You\'ve been blacklisted from our application') ?>" placeholder="Custom response you'd like. Max 100 chars" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Paused Msg</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="100" name="pausedsub" value="<?= $v('pausedsub', 'Your Key is paused and cannot be used at the moment.') ?>" placeholder="Custom response you'd like. Max 100 chars" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Session Unauthenticated Msg</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="100" name="sessionunauthed" value="<?= $v('sessionunauthed', 'This program hash does not match, make sure you\'re using latest version') ?>" placeholder="Custom response you'd like. Max 100 chars" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Successful login Msg</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="100" name="loggedInMsg" value="<?= $v('loggedInMsg', 'Logged in!') ?>" placeholder="Custom response you'd like. Max 100 chars" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Paused application Msg</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="100" name="pausedApp" value="<?= $v('pausedApp', 'Application is currently paused, please wait for the developer to say otherwise.') ?>" placeholder="Custom response you'd like. Max 100 chars" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Username too short Msg</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="100" name="unTooShort" value="<?= $v('unTooShort', 'Username too short, try longer one.') ?>" placeholder="Custom response you'd like. Max 100 chars" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Password leaked Msg</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="100" name="pwLeaked" value="<?= $v('pwLeaked', 'This password has been leaked in a data breach (not from us), please use a different one.') ?>" placeholder="Custom response you'd like. Max 100 chars" required>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Chat delay hit Msg</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="100" name="chatHitDelay" value="<?= $v('chatHitDelay', 'Chat slower, youve hit the delay limit') ?>" placeholder="Custom response you'd like. Max 100 chars" required>
                                </div>
                            </div>
                            <?php if (($user['role'] ?? '') !== 'tester'): ?>
                            <br><br><br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Reseller Webhook Link <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="This is the same if you're using Sellix or Shoppy, create webhook with this link for the event order:paid"></i></label>
                                <div class="col-10">
                                    <label class="form-control" style="height:auto;"><a href="<?= htmlspecialchars($reseller_webhook) ?>" target="_blank" class="secretlink"><?= htmlspecialchars($reseller_webhook) ?></a></label>
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Shoppy Webhook Secret <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="Shoppy webhook secret for reseller system"></i></label>
                                <div class="col-10">
                                    <input class="form-control secret" maxlength="16" name="shoppywebhooksecret" value="<?= $v('shoppywebhooksecret') ?>" placeholder="Webhook secret found in General Shop Settings on Shoppy">
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Shoppy Day Product ID</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="7" name="shoppydayproduct" value="<?= $v('shoppydayproduct') ?>" placeholder="Product ID of Day Reseller Key Shoppy Product">
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Shoppy Week Product ID</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="7" name="shoppyweekproduct" value="<?= $v('shoppyweekproduct') ?>" placeholder="Product ID of Week Reseller Key Shoppy Product">
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Shoppy Month Product ID</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="7" name="shoppymonthproduct" value="<?= $v('shoppymonthproduct') ?>" placeholder="Product ID of Month Reseller Key Shoppy Product">
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Shoppy Lifetime Product ID</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="7" name="shoppylifetimeproduct" value="<?= $v('shoppylifetimeproduct') ?>" placeholder="Product ID of Lifetime Reseller Key Shoppy Product">
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Sellix Webhook Secret <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="Sellix webhook secret for reseller system"></i></label>
                                <div class="col-10">
                                    <input class="form-control secret" maxlength="32" name="sellixwebhooksecret" value="<?= $v('sellixwebhooksecret') ?>" placeholder="Webhook secret found in General Shop Settings on Sellix">
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Sellix Day Product ID</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="13" name="sellixdayproduct" value="<?= $v('sellixdayproduct') ?>" placeholder="Product ID of Day Reseller Key Sellix Product">
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Sellix Week Product ID</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="13" name="sellixweekproduct" value="<?= $v('sellixweekproduct') ?>" placeholder="Product ID of Week Reseller Key Sellix Product">
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Sellix Month Product ID</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="13" name="sellixmonthproduct" value="<?= $v('sellixmonthproduct') ?>" placeholder="Product ID of Month Reseller Key Sellix Product">
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">Sellix Lifetime Product ID</label>
                                <div class="col-10">
                                    <input class="form-control" maxlength="13" name="sellixlifetimeproduct" value="<?= $v('sellixlifetimeproduct') ?>" placeholder="Product ID of Lifetime Reseller Key Sellix Product">
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">SellApp Webhook Secret <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="SellApp webhook secret for reseller system"></i></label>
                                <div class="col-10">
                                    <input class="form-control secret" maxlength="64" name="sellappwebhooksecret" value="<?= $v('sellappwebhooksecret') ?>" placeholder="Webhook secret found in Developer Settings on SellApp">
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">SellApp Day Product URL</label>
                                <div class="col-10">
                                    <input class="form-control" name="sellappdayproduct" value="<?= $v('sellappdayproduct') ?>" placeholder="URL for Day Reseller Key SellApp Product">
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">SellApp Week Product URL</label>
                                <div class="col-10">
                                    <input class="form-control" name="sellappweekproduct" value="<?= $v('sellappweekproduct') ?>" placeholder="URL for Week Reseller Key SellApp Product">
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">SellApp Month Product URL</label>
                                <div class="col-10">
                                    <input class="form-control" name="sellappmonthproduct" value="<?= $v('sellappmonthproduct') ?>" placeholder="URL for Month Reseller Key SellApp Product">
                                </div>
                            </div>
                            <br>
                            <div class="form-group row">
                                <label for="example-tel-input" class="col-2 col-form-label">SellApp Lifetime Product URL</label>
                                <div class="col-10">
                                    <input class="form-control" name="sellapplifetimeproduct" value="<?= $v('sellapplifetimeproduct') ?>" placeholder="URL for Lifetime Reseller Key SellApp Product">
                                </div>
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end::Container-->