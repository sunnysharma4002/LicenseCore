<?php
$current_role = strtolower($user['role'] ?? 'free');
$role_plan_map = ['developer' => 'developer', 'tester' => 'tester', 'seller' => 'seller'];
$current_plan_key = $role_plan_map[$current_role] ?? '';
?>
<div class="row">
	<div class="col-md-4 col-sm-12">
		<div class="card" style="zoom: 1;">
			<div class="card-body">
				<div class="form-group">
					<h4 class="card-title">Tester <sup><small>$</small></sup>0 <span class="fs-13 text-muted">/Month</span></h4><br>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Limited Keys</p>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> File Uploads (10MB)</p>
					<p><i class="ri-close-circle-fill fs-15 align-middle" style="color: #e03131;"></i> No webhooks (hide your API link)</p>
					<p><i class="ri-close-circle-fill fs-15 align-middle" style="color: #e03131;"></i> No web loader (control program from website)</p>
					<p><i class="ri-close-circle-fill fs-15 align-middle" style="color: #e03131;"></i> No chat channels inside program</p>
					<p><i class="ri-close-circle-fill fs-15 align-middle" style="color: #e03131;"></i> No Customer App Panel (with custom domain)</p>
					<p><i class="ri-close-circle-fill fs-15 align-middle" style="color: #e03131;"></i> No custom domain for API</p>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Limited Logs</p>
					<p><i class="ri-close-circle-fill fs-15 align-middle" style="color: #e03131;"></i> No Reseller System</p>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Limited Support</p>
					<p><i class="ri-close-circle-fill fs-15 align-middle" style="color: #e03131;"></i> No Discord Role</p>
					<p><i class="ri-close-circle-fill fs-15 align-middle" style="color: #e03131;"></i> Discord Bot</p>
					<p><i class="ri-close-circle-fill fs-15 align-middle" style="color: #e03131;"></i> SellerAPI (auto send Sellix, Shoppy, SellApp)</p>
					<br>
					<button class="btn btn-lg btn-block font-medium btn-outline btn-outline-success btn-active-light-success block-card" disabled>Already Have</button> <span class="badge badge-pill badge-success">Active forever</span>
				</div>
			</div>
		</div>
	</div>

	<div class="col-md-4 col-sm-12">
		<div class="card">
			<div class="card-body">
				<div class="form-group">
					<h4 class="card-title">Developer <sup><small>$</small></sup>0 <span class="fs-13 text-muted">/Month</span></h4><br>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Unlimited Keys</p>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Unlimited Files (50MB each)</p>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Webhooks (hide your API link)</p>
					<p><i class="ri-close-circle-fill fs-15 align-middle" style="color: #e03131;"></i> No web loader (control program from website)</p>
					<p><i class="ri-close-circle-fill fs-15 align-middle" style="color: #e03131;"></i> No chat channels inside program</p>
					<p><i class="ri-close-circle-fill fs-15 align-middle" style="color: #e03131;"></i> No Customer App Panel (with custom domain)</p>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Custom domain for API</p>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Unlimited Logs</p>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Reseller System</p>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Prioritzed Support</p>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Discord Role</p>
					<p><i class="ri-close-circle-fill fs-15 align-middle" style="color: #e03131;"></i> Discord Bot</p>
					<p><i class="ri-close-circle-fill fs-15 align-middle" style="color: #e03131;"></i> SellerAPI (auto send Sellix, Shoppy, SellApp)</p>
					<br>
					<?php if ($current_plan_key === 'developer'): ?>
					<button class="btn btn-lg btn-block font-medium btn-outline btn-outline-warning btn-active-light-warning block-sidenav" disabled>Already Have</button>
					<?php else: ?>
					<button data-bs-toggle="modal" type="button" data-bs-target="#devoloper-plan" class="btn btn-lg btn-block font-medium btn-outline btn-outline-warning btn-active-light-warning block-sidenav">Get Free</button>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<div class="col-md-4 col-sm-12">
		<div class="card">
			<div class="card-body">
				<div class="form-group">
					<h4 class="card-title">Seller <sup><small>$</small></sup>0 <span class="fs-13 text-muted">/Month</span></h4><br>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Unlimited Keys</p>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Unlimited Files (75MB each)</p>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Webhooks (hide your API link)</p>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Web loader (control program from website)</p>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Chat channels inside program</p>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Customer App Panel (with custom domain)</p>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Custom domain for API</p>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Unlimited Logs</p>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Reseller System</p>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Prioritzed support</p>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Supreme Discord Role</p>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> Discord Bot</p>
					<p><i class="ri-checkbox-circle-fill fs-15 align-middle" style="color: #0ca678;"></i> SellerAPI (auto send Sellix, Shoppy, SellApp)</p>
					<br>
					<?php if ($current_plan_key === 'seller'): ?>
					<button class="btn btn-lg btn-block font-medium btn-outline btn-outline-danger btn-active-light-danger block-sidenav" disabled>Already Have</button>
					<?php else: ?>
					<button data-bs-toggle="modal" type="button" data-bs-target="#seller-plan" class="btn btn-lg btn-block font-medium btn-outline btn-outline-danger btn-active-light-danger block-sidenav">Get Free</button>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" tabindex="-1" id="devoloper-plan">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-body text-center p-5">
				<h2 class="modal-title">Upgrade Account</h2>
				<lord-icon src="https://cdn.lordicon.com/tdrtiskw.json" trigger="loop" colors="primary:#f7b84b,secondary:#405189" style="width:130px;height:130px"></lord-icon>
				<div class="mt-4 pt-3">
					<h4 class="mb-3">Notice</h4>
					<p class="text-muted mb-4">Upgrade Account To Developer Plan. Click Yes For Upgrade.</p>
					<form method="POST">
						<div class="row g-3">
							<div class="col-lg-12">
								<div class="hstack gap-2 justify-content-end">
									<button type="button" class="btn btn-light" data-bs-dismiss="modal">No, cancel</button>
									<button name="devoloperplan" class="btn btn-danger">Yes, I'm sure</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" tabindex="-1" id="seller-plan">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-body text-center p-5">
				<h2 class="modal-title">Upgrade Account</h2>
				<lord-icon src="https://cdn.lordicon.com/tdrtiskw.json" trigger="loop" colors="primary:#f7b84b,secondary:#405189" style="width:130px;height:130px"></lord-icon>
				<div class="mt-4 pt-3">
					<h4 class="mb-3">Notice</h4>
					<p class="text-muted mb-4">Upgrade Account To Seller Plan. Click Yes For Upgrade.</p>
					<form method="POST">
						<div class="row g-3">
							<div class="col-lg-12">
								<div class="hstack gap-2 justify-content-end">
									<button type="button" class="btn btn-light" data-bs-dismiss="modal">No, cancel</button>
									<button name="sellerplan" class="btn btn-danger">Yes, I'm sure</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>