<?php // Documentation Page ?>
<h4 class="fw-bold mb-4">API Documentation</h4>

<div class="row g-3">
    <!-- Navigation -->
    <div class="col-lg-3">
        <div class="card bg-dark border-secondary">
            <div class="card-body">
                <h6 class="card-title fw-bold mb-3">Quick Links</h6>
                <div class="d-flex flex-column gap-2">
                    <a href="#getting-started" class="btn btn-secondary btn-sm text-start">Getting Started</a>
                    <a href="#authentication" class="btn btn-sm text-start text-light">Authentication</a>
                    <a href="#endpoints" class="btn btn-sm text-start text-light">API Endpoints</a>
                    <a href="#examples" class="btn btn-sm text-start text-light">Code Examples</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="col-lg-9 d-flex flex-column gap-3">
        <!-- Getting Started -->
        <div id="getting-started" class="card bg-dark border-secondary">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">Getting Started</h5>
                <p class="text-muted mb-3">Welcome to the LicenseAuth API documentation. This guide will help you integrate our authentication system into your applications.</p>

                <div class="bg-secondary rounded p-3">
                    <p class="small text-muted mb-1">Base URL</p>
                    <code class="small text-primary"><?= SITE_URL ?>/api/1.2/</code>
                </div>
            </div>
        </div>

        <!-- Authentication -->
        <div id="authentication" class="card bg-dark border-secondary">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">Authentication</h5>
                <p class="text-muted mb-3">All API requests require authentication using your application secret.</p>

                <div class="bg-black border border-secondary rounded p-3 font-monospace small overflow-auto">
                    <pre class="text-light mb-0">POST <?= SITE_URL ?>/api/1.2/index.php
Content-Type: application/x-www-form-urlencoded

name=YourAppName
ownerid=<?= htmlspecialchars($ownerid) ?>
secret=YOUR_APP_SECRET
type=init</pre>
                </div>
            </div>
        </div>

        <!-- Endpoints -->
        <div id="endpoints" class="card bg-dark border-secondary">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">API Endpoints</h5>

                <div class="d-flex flex-column gap-3">
                    <div class="ps-3 border-start border-4 border-primary">
                        <h6 class="fw-semibold mb-0">Initialize (init)</h6>
                        <p class="small text-muted mt-1 mb-0">Initialize a session and verify app credentials</p>
                        <code class="small text-primary mt-1 d-block">type=init&ver=1.0&enckey=yourkey</code>
                    </div>

                    <div class="ps-3 border-start border-4 border-success">
                        <h6 class="fw-semibold mb-0">User Login</h6>
                        <p class="small text-muted mt-1 mb-0">Authenticate a user with username and password</p>
                        <code class="small text-success mt-1 d-block">type=login&sessionid=xxx&username=user&pass=password&hwid=xxx</code>
                    </div>

                    <div class="ps-3 border-start border-4 border-info">
                        <h6 class="fw-semibold mb-0">License Validation</h6>
                        <p class="small text-muted mt-1 mb-0">Validate a license key</p>
                        <code class="small text-info mt-1 d-block">type=license&sessionid=xxx&key=LICENSE_KEY&hwid=xxx</code>
                    </div>

                    <div class="ps-3 border-start border-4 border-warning">
                        <h6 class="fw-semibold mb-0">Register User</h6>
                        <p class="small text-muted mt-1 mb-0">Register a new user with license key</p>
                        <code class="small text-warning mt-1 d-block">type=register&sessionid=xxx&username=user&pass=password&key=LICENSE_KEY</code>
                    </div>

                    <div class="ps-3 border-start border-4 border-danger">
                        <h6 class="fw-semibold mb-0">Check Session</h6>
                        <p class="small text-muted mt-1 mb-0">Verify session is still valid</p>
                        <code class="small text-danger mt-1 d-block">type=check&sessionid=xxx</code>
                    </div>
                </div>
            </div>
        </div>

        <!-- Examples -->
        <div id="examples" class="card bg-dark border-secondary">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">PHP Example</h5>

                <div class="bg-black border border-secondary rounded p-3 font-monospace small overflow-auto">
                    <pre class="text-light mb-0"><span class="text-info">&lt;?php</span>
<span class="text-primary">$api_url</span> = <span class="text-success">'<?= SITE_URL ?>/api/1.2/'</span>;
<span class="text-primary">$data</span> = <span class="text-warning">array</span>(
    <span class="text-success">'type'</span> => <span class="text-success">'init'</span>,
    <span class="text-success">'ver'</span> => <span class="text-success">'1.0'</span>,
    <span class="text-success">'name'</span> => <span class="text-success">'YourAppName'</span>,
    <span class="text-success">'ownerid'</span> => <span class="text-success">'<?= substr($ownerid, 0, 16) ?>...'</span>,
    <span class="text-success">'secret'</span> => <span class="text-success">'YOUR_APP_SECRET'</span>
);

<span class="text-primary">$ch</span> = <span class="text-warning">curl_init</span>();
<span class="text-warning">curl_setopt</span>(<span class="text-primary">$ch</span>, <span class="text-danger">CURLOPT_URL</span>, <span class="text-primary">$api_url</span>);
<span class="text-warning">curl_setopt</span>(<span class="text-primary">$ch</span>, <span class="text-danger">CURLOPT_POST</span>, <span class="text-danger">true</span>);
<span class="text-warning">curl_setopt</span>(<span class="text-primary">$ch</span>, <span class="text-danger">CURLOPT_POSTFIELDS</span>, <span class="text-warning">http_build_query</span>(<span class="text-primary">$data</span>));
<span class="text-warning">curl_setopt</span>(<span class="text-primary">$ch</span>, <span class="text-danger">CURLOPT_RETURNTRANSFER</span>, <span class="text-danger">true</span>);

<span class="text-primary">$response</span> = <span class="text-warning">curl_exec</span>(<span class="text-primary">$ch</span>);
<span class="text-primary">$result</span> = <span class="text-warning">json_decode</span>(<span class="text-primary">$response</span>, <span class="text-danger">true</span>);

<span class="text-warning">print_r</span>(<span class="text-primary">$result</span>);
<span class="text-info">?></span></pre>
                </div>
            </div>
        </div>
    </div>
</div>
