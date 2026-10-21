<?php // Profile Page ?>
<h4 class="fw-bold mb-4">My Profile</h4>

<div class="row g-3">
    <!-- Profile Card -->
    <div class="col-lg-4">
        <div class="card bg-dark border-secondary">
            <div class="card-body text-center mb-3">
                <div class="d-flex align-items-center justify-content-center mx-auto rounded-circle bg-gradient mb-3" style="width:96px;height:96px;background:linear-gradient(135deg,#60a5fa,#7c3aed)">
                    <i class="mdi mdi-account text-white fs-2"></i>
                </div>
                <h5 class="card-title fw-bold mb-0"><?= htmlspecialchars($user['username']) ?></h5>
                <p class="text-muted small mt-1 mb-0"><?= htmlspecialchars($user['email']) ?></p>
            </div>

            <div class="card-footer bg-transparent border-secondary">
                <div class="d-flex flex-column gap-2">
                    <div class="bg-secondary rounded p-3">
                        <p class="small text-muted mb-0">Account ID</p>
                        <p class="font-monospace small mt-1 mb-0"><?= substr($ownerid, 0, 16) ?>...</p>
                    </div>
                    <div class="bg-secondary rounded p-3">
                        <p class="small text-muted mb-0">Member Since</p>
                        <p class="small mt-1 mb-0"><?= date('M d, Y', strtotime($user['created_at'])) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile -->
    <div class="col-lg-8">
        <div class="card bg-dark border-secondary">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">Edit Profile</h5>

                <form class="d-flex flex-column gap-3">
                    <div>
                        <label class="form-label small mb-1">Username</label>
                        <input type="text" value="<?= htmlspecialchars($user['username']) ?>" class="form-control bg-dark border-secondary text-light">
                    </div>

                    <div>
                        <label class="form-label small mb-1">Email</label>
                        <input type="email" value="<?= htmlspecialchars($user['email']) ?>" class="form-control bg-dark border-secondary text-light">
                    </div>

                    <div class="border-top border-secondary pt-3">
                        <h6 class="fw-semibold mb-3">Change Password</h6>
                        <div class="d-flex flex-column gap-3">
                            <input type="password" placeholder="Current Password" class="form-control bg-dark border-secondary text-light">
                            <input type="password" placeholder="New Password" class="form-control bg-dark border-secondary text-light">
                            <input type="password" placeholder="Confirm New Password" class="form-control bg-dark border-secondary text-light">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary fw-medium">
                        <i class="mdi mdi-content-save me-2"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
