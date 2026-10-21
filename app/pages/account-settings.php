<script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
<script src="<?= SITE_URL ?>/assets/dashboard/unixtolocal.js"></script>
<script src="<?= SITE_URL ?>/assets/dashboard/webauthn.js"></script>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <div class="alert alert-danger" role="alert">
                    Change You Account Setting From Here <br>
                    Join Our Discord server <a href="./?page=help" target="_blank"><strong> For Support</strong></a>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form method="POST">
                                    <div class="form-group row">
                                        <label for="example-text-input" class="col-2 col-form-label">Username</label>
                                        <div class="col-10">
                                            <label class="form-control"><?= htmlspecialchars($user['username']) ?></label>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="form-group row">
                                        <label for="example-text-input" class="col-2 col-form-label">OwnerID</label>
                                        <div class="col-10">
                                            <label class="form-control"><?= htmlspecialchars($ownerid) ?></label>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="form-group row">
                                        <label for="example-text-input" class="col-2 col-form-label">Subscription Expires</label>
                                        <div class="col-10">
                                            <label class="form-control">
                                                <script>document.write(convertTimestamp());</script>
                                            </label>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="form-group row">
                                        <label for="example-tel-input" class="col-2 col-form-label">Account logs</label>
                                        <div class="col-10">
                                            <select class="form-control" name="acclogs">
                                                <option value="1" selected="selected">Enabled</option>
                                                <option value="0">Disabled</option>
                                            </select>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="form-group row">
                                        <label for="example-tel-input" class="col-2 col-form-label">New Login Location Verification</label>
                                        <div class="col-10">
                                            <select class="form-control" name="emailVerify">
                                                <option value="1" selected="selected">Enabled</option>
                                                <option value="0">Disabled</option>
                                            </select>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="form-group row">
                                        <label for="example-tel-input" class="col-2 col-form-label">Password</label>
                                        <div class="col-10">
                                            <div class="form-control">Change password here <a href="../forgot/" target="_blank"><?= SITE_URL ?>/forgot/</a></div>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="form-group row">
                                        <label for="example-password-input" class="col-2 col-form-label">Profile Image</label>
                                        <div class="col-10">
                                            <input class="form-control" name="pfp" type="url" maxlength="200" placeholder="Enter link to image for profile picture">
                                        </div>
                                    </div>
                                    <br>
                                    <div class="form-group row">
                                        <label for="example-password-input" class="col-2 col-form-label">Email</label>
                                        <div class="col-10">
                                            <div class="form-control">Change email here <a href="../changeEmail/" target="_blank"><?= SITE_URL ?>/changeEmail/</a></div>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="form-group row">
                                        <label for="example-password-input" class="col-2 col-form-label">Username</label>
                                        <div class="col-10">
                                            <div class="form-control">Change username here <a href="../changeUsername/" target="_blank"><?= SITE_URL ?>/changeUsername/</a></div>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="form-group row">
                                        <label for="example-password-input" class="col-2 col-form-label">Account Deletion</label>
                                        <div class="col-10">
                                            <div class="form-control">Delete account here <a href="../deleteAccount/" target="_blank"><?= SITE_URL ?>/deleteAccount/</a></div>
                                        </div>
                                    </div>
                                    <br>
                                    <button name="updatesettings" class="btn btn-success"><i class="fa fa-check"></i> Save</button>
                                    <a class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#twofa"><i class="fa fa-lock"></i> Enable 2FA</a>
                                    <a class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#webauthn"><i class="fab fa-usb"></i> FIDO2 WebAuthn (Security Keys)</a>
                                </form>

                                <div class="modal fade" id="twofa" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered mw-900px">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h2 class="modal-title">2 Factor Authentication</h2>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="post">
                                                <div class="modal-body">
                                                    <div class="current" data-kt-stepper-element="content">
                                                        <div class="w-100">
                                                            <div class="fv-row mb-10">
                                                                <label class="d-flex align-items-center fs-5 fw-bold mb-2"><span class="required">2FA Code</span></label>
                                                                <label>Scan this QR code into your 2FA App.</label><br><br>
                                                                <img src="https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=otpauth%3A%2F%2Ftotp%2F<?= htmlspecialchars($user['username']) ?>%3Fsecret%3D<?= htmlspecialchars($twofa_secret ?? '') ?>%26issuer%3DLicenseAuth" /><br><br>
                                                                <label>Alternatively, you can set it manually, code: <code><?= htmlspecialchars($twofa_secret ?? '') ?></code></label><br><br>
                                                                <input type="text" name="scan_code" id="scan_code" maxlength="6" placeholder="6 Digit Code from 2FA app" class="form-control mb-4" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button name="submit_code" class="btn btn-primary">Submit</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal fade" id="disabletwofa" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered mw-900px">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h2 class="modal-title">Disable 2 Factor Authentication</h2>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="post">
                                                <div class="modal-body">
                                                    <div class="current" data-kt-stepper-element="content">
                                                        <div class="w-100">
                                                            <div class="fv-row mb-10">
                                                                <label class="d-flex align-items-center fs-5 fw-bold mb-2"><span class="required">2FA Code</span></label>
                                                                <input type="text" name="scan_code" id="scan_code" maxlength="6" placeholder="6 Digit Code from 2FA app" class="form-control mb-4" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button name="submit_code_disable" class="btn btn-primary">Submit</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal fade" id="webauthn" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered mw-900px">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h2 class="modal-title">FIDO2 WebAuthn (Security Keys)</h2>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="post">
                                                <div class="modal-body">
                                                    <div class="current" data-kt-stepper-element="content">
                                                        <div class="w-100">
                                                            <div class="fv-row mb-10">
                                                                <label class="d-flex align-items-center fs-5 fw-bold mb-2"><span class="required">Name</span></label>
                                                                <input type="text" name="webauthn_name" id="webauthn_name" maxlength="99" placeholder="Pick a name for security key" class="form-control mb-4">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" onclick="newregistration()" class="btn btn-primary">Register</button>
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
    </div>
</div>
