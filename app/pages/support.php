<?php // Support Page ?>
<h4 class="fw-bold mb-4">Support Center</h4>

<div class="row g-3">
    <!-- Contact Form -->
    <div class="col-lg-8">
        <div class="card bg-dark border-secondary">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">Submit a Support Ticket</h5>

                <form class="d-flex flex-column gap-3">
                    <div>
                        <label class="form-label small mb-1">Subject</label>
                        <input type="text" placeholder="Brief description of your issue" class="form-control bg-dark border-secondary text-light">
                    </div>

                    <div>
                        <label class="form-label small mb-1">Category</label>
                        <select class="form-select bg-dark border-secondary text-light">
                            <option>Technical Issue</option>
                            <option>Billing Question</option>
                            <option>Feature Request</option>
                            <option>Account Help</option>
                            <option>Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label small mb-1">Priority</label>
                        <select class="form-select bg-dark border-secondary text-light">
                            <option>Low</option>
                            <option>Medium</option>
                            <option>High</option>
                            <option>Critical</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label small mb-1">Description</label>
                        <textarea rows="6" placeholder="Provide detailed information about your issue..." class="form-control bg-dark border-secondary text-light"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary fw-medium">
                        <i class="mdi mdi-send me-2"></i> Submit Ticket
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Quick Help -->
    <div class="col-lg-4 d-flex flex-column gap-3">
        <!-- Contact Info -->
        <div class="card bg-dark border-secondary">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">Contact Us</h5>

                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-start gap-2">
                        <i class="mdi mdi-email-outline text-primary mt-1"></i>
                        <div>
                            <p class="small fw-medium mb-0">Email</p>
                            <p class="small text-muted mt-1 mb-0">support@licenseauth.com</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-2">
                        <i class="fab fa-discord mt-1" style="color:#5865F2"></i>
                        <div>
                            <p class="small fw-medium mb-0">Discord</p>
                            <p class="small text-muted mt-1 mb-0">Join our community</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-2">
                        <i class="mdi mdi-clock-outline text-success mt-1"></i>
                        <div>
                            <p class="small fw-medium mb-0">Response Time</p>
                            <p class="small text-muted mt-1 mb-0">Usually within 24 hours</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ -->
        <div class="card bg-dark border-secondary">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">Common Questions</h5>

                <div class="d-flex flex-column gap-2">
                    <a href="#" class="btn btn-secondary btn-sm text-start">
                        <i class="mdi mdi-help-circle-outline text-primary me-2"></i>
                        How do I generate a license?
                    </a>
                    <a href="#" class="btn btn-secondary btn-sm text-start">
                        <i class="mdi mdi-help-circle-outline text-primary me-2"></i>
                        API integration guide
                    </a>
                    <a href="#" class="btn btn-secondary btn-sm text-start">
                        <i class="mdi mdi-help-circle-outline text-primary me-2"></i>
                        Reset password
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
