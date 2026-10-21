<script src="<?= SITE_URL ?>/assets/dashboard/unixtolocal.js"></script>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <div class="alert alert-danger" role="alert">
                    Allow your users to chat with each other <br>
                    Join Our Discord server <a href="./?page=help" target="_blank"><strong> For Support</strong></a>
                </div>

                <form method="POST">
                    <button data-bs-toggle="modal" type="button" data-bs-target="#create-channel" class="btn btn-secondary waves-effect waves-light"><i class="ri-add-circle-line"></i> Create Channel</button>
                    <button data-bs-toggle="modal" type="button" data-bs-target="#unmute-user" class="btn btn-secondary waves-effect waves-light"><i class="ri-restart-line"></i> Unmute User</button><br><br>
                    <button data-bs-toggle="modal" type="button" data-bs-target="#clear-channel" class="btn btn-danger waves-effect waves-light"><i class="ri-upload-cloud-line"></i> Clear channel</button>
                </form>
                <br>

                <div id="create-channel" class="modal" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header d-flex align-items-center">
                                <h4 class="modal-title">Add Channels</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <div class="form-group">
                                        <label for="recipient-name" class="control-label">Name:</label>
                                        <input class="form-control" name="name" placeholder="Chat channel name" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="recipient-name" class="control-label">Chat cooldown Unit:</label>
                                        <select name="unit" class="form-control">
                                            <option value="1">Seconds</option>
                                            <option value="60">Minutes</option>
                                            <option value="3600">Hours</option>
                                            <option value="86400">Days</option>
                                            <option value="604800">Weeks</option>
                                            <option value="2629743">Months</option>
                                            <option value="31556926">Years</option>
                                            <option value="315569260">Lifetime</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="recipient-name" class="control-label">Chat cooldown: <i class="mdi mdi-help-circle-outline text-white-50" data-bs-toggle="tooltip" data-bs-placement="top" title="Delay users will have to wait to send their next message"></i></label>
                                        <input name="delay" type="number" class="form-control" placeholder="Multiplied by selected delay unit" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button class="btn btn-danger" name="addchannel">Add</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="unmute-user" class="modal" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header d-flex align-items-center">
                                <h4 class="modal-title">Unmute User</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <div class="form-group">
                                        <label for="recipient-name" class="control-label">Name:</label>
                                        <select class="form-control" name="user"></select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button class="btn btn-danger" name="unmuteuser">Unmute</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="clear-channel" class="modal" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header d-flex align-items-center">
                                <h4 class="modal-title">Clear Channel</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <div class="form-group">
                                        <label for="recipient-name" class="control-label">Channel name:</label>
                                        <select class="form-control" name="channel"></select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button class="btn btn-danger" name="clearchannel">Clear</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="mute-user" class="modal" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header d-flex align-items-center">
                                <h4 class="modal-title">Mute User</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <div class="form-group">
                                        <label for="recipient-name" class="control-label">Unit Of Time Muted:</label>
                                        <select name="muted" class="form-control">
                                            <option value="86400">Days</option>
                                            <option value="60">Minutes</option>
                                            <option value="3600">Hours</option>
                                            <option value="1">Seconds</option>
                                            <option value="604800">Weeks</option>
                                            <option value="2629743">Months</option>
                                            <option value="31556926">Years</option>
                                            <option value="315569260">Lifetime</option>
                                        </select>
                                        <input type="hidden" class="muteuser" name="user">
                                    </div>
                                    <div class="form-group">
                                        <label for="recipient-name" class="control-label">Time Muted:</label>
                                        <input class="form-control" name="time" placeholder="Multiplied by selected unit of time muted" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button class="btn btn-danger" name="muteuser">Mute</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <table id="kt_datatable_chats" class="display table table-bordered dt-responsive" style="width:100%">
                    <thead>
                        <tr class="fw-bolder fs-6 text-gray-800 px-7">
                            <th>Name</th>
                            <th>Delay</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>

                <br><br>

                <table id="kt_datatable_messages" class="display table table-bordered dt-responsive" style="width:100%">
                    <thead>
                        <tr class="fw-bolder fs-6 text-gray-800 px-7">
                            <th>Author</th>
                            <th>Message</th>
                            <th>Time Sent</th>
                            <th>Channel</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>

                <script>
                    function muteuser(key) {
                        var muteuser = $('.muteuser');
                        muteuser.attr('value', key);
                    }
                </script>
            </div>
        </div>
    </div>
</div>
