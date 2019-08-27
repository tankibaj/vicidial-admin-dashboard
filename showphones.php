<?php
/**
 * Created by PhpStorm.
 * User: elomelo
 * Date: 24/4/18
 * Time: 5:07 PM
 */

require_once "includes/common.php";


if(!$auth ->checkSession()){
    header("Location: login.php");
    die();
}

$page = "Show Phones";
$parent = "user";


$successEdit = false;
$successAdd = false;
$successDelete = false;
$error = false;
if(isset($_POST['deletephone']) && $_POST['deletephone'] != ""){
    $extension = trim($_POST['deletephone']);
    if(isPhoneExistsInUser($extension)){
        $error = "$extension exists in agent account. Please update agent account first";
    }
    if(!$error){
        \PATHAODB::table("phones")->where('extension', $extension)->delete();
        $successDelete = $extension;
    }
}
if(isset($_POST['editphone'])){
    $oldextension = trim($_POST['editphone']);
    $extension = strtolower(trim($_POST['extension']));
    $pass = trim($_POST['password']);
    $outbound_cid = trim($_POST['outbound_cid']);
    $usergroup = trim($_POST['usergroup']);
    $server_ip = trim($_POST['server_ip']);
    $status = trim($_POST['status']);
    $isactive = trim($_POST['isactive']);
    $protocol = trim($_POST['protocol']);

    $systemSettings = getSystemSettings();
    if(strlen($extension) <= 1){
        $error = "Please enter Extension";
    }
    if(!isPhoneExists($oldextension)){
        $error = "Target phone extension doesn't exists";
    }
    if($extension != $extension && isPhoneExists($extension)){
        $error = "Phone extension already exists";
    }
    if(strlen($pass) <= 1){
        $error = "Please enter password";
    }
    if(strlen($outbound_cid) <= 1){
        $error = "Please enter Outbound Number";
    }
    if(strlen($usergroup) <= 1){
        $error = "Please select usergroup";
    }
    if(strlen($server_ip) <= 1){
        $error = "Please enter server ip";
    }
    if(strlen($status) <= 1){
        $error = "Please select status";
    }

    $pass_hash = "";

    $data = ['extension' => $extension,
        "dialplan_number" => $extension,
        "voicemail_id" => $extension,
        "server_ip" => $server_ip,
        "user_group" => $usergroup,
        "login" => $extension,
        "pass" => $pass,
        "status" => $status,
        "active" => $isactive,
        "fullname" => $extension,
        "protocol" => $protocol,
        "outbound_cid" => $outbound_cid,
        "conf_secret" => $pass,
        "phone_type" => $extension
    ];

    if(!$error){
        \PATHAODB::table("phones")->where("extension","=",$oldextension)->update($data);

        //lets ask asterisk to rebuild conf files
        \PATHAODB::table("servers")-> where("server_ip","=","$server_ip")->update(["rebuild_conf_files" => "Y","generate_vicidial_conf" => "Y","active_asterisk_server" => "Y"]);
        \PATHAODB::table("servers")-> where("server_ip","=",$systemSettings -> active_voicemail_server)->update(["rebuild_conf_files" => "Y","generate_vicidial_conf" => "Y","active_asterisk_server" => "Y"]);
        updateUserPhone($extension,$pass,$oldextension);
        $successEdit = true;
    }
}
if(isset($_POST['addphone'])){
    $extension = strtolower(trim($_POST['extension']));
    $pass = trim($_POST['password']);
    $outbound_cid = trim($_POST['outbound_cid']);
    $usergroup = trim($_POST['usergroup']);
    $server_ip = trim($_POST['server_ip']);
    $status = trim($_POST['status']);
    $isactive = trim($_POST['isactive']);
    $protocol = trim($_POST['protocol']);

    $systemSettings = getSystemSettings();
    if(strlen($extension) <= 1){
        $error = "Please enter Extension";
    }

    if(isPhoneExists($extension)){
        $error = "Phone extension already exists";
    }
    if(strlen($pass) <= 1){
        $error = "Please enter password";
    }
    if(strlen($outbound_cid) <= 1){
        $error = "Please enter Outbound Number";
    }
    if(strlen($usergroup) <= 1){
        $error = "Please select usergroup";
    }
    if(strlen($server_ip) <= 1){
        $error = "Please enter server ip";
    }
    if(strlen($status) <= 1){
        $error = "Please select status";
    }

    $pass_hash = "";

    $data = ['extension' => $extension,
        "dialplan_number" => $extension,
        "voicemail_id" => $extension,
        "server_ip" => $server_ip,
        "user_group" => $usergroup,
        "login" => $extension,
        "pass" => $pass,
        "status" => $status,
        "active" => $isactive,
        "fullname" => $extension,
        "protocol" => $protocol,
        "outbound_cid" => $outbound_cid,
        "conf_secret" => $pass,
        "phone_type" => $extension,
        "template_id" => ""
    ];

    if(!$error){
        \PATHAODB::table("phones")->insert($data);

        //lets ask asterisk to rebuild conf files
        \PATHAODB::table("servers")-> where("server_ip","=","$server_ip")->update(["rebuild_conf_files" => "Y","generate_vicidial_conf" => "Y","active_asterisk_server" => "Y"]);
        \PATHAODB::table("servers")-> where("server_ip","=",$systemSettings -> active_voicemail_server)->update(["rebuild_conf_files" => "Y","generate_vicidial_conf" => "Y","active_asterisk_server" => "Y"]);

        $successAdd = true;
    }
}


require_once "header.php";

?>

<div class="container-fluid pt-25">
    <!-- Title -->
    <div class="row heading-bg">
        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
            <h5 class="txt-dark"><?php echo $page; ?></h5>
        </div>
        <!-- Breadcrumb -->
        <div class="col-lg-9 col-sm-8 col-md-8 col-xs-12">
            <ol class="breadcrumb">
                <li>Reports</li>
                <li class="active"><span><?php echo $page; ?></span></li>
            </ol>
        </div>
        <!-- /Breadcrumb -->
    </div>
    <!-- /Title -->

    <div class="row">
        <!-- Table Hover -->
        <?php

        $per_page = 50;
        if(isset($_GET['pp']) && is_numeric($_GET['pp'])){
            $per_page = (int) $_GET['pp'];
        }
        $paginator = new AshleyDawson\SimplePagination\Paginator();
        $paginator
            ->setItemsPerPage($per_page)
            ->setPagesInRange(20)
        ;

        $paginator->setItemTotalCallback(function () {
            $query = \PATHAODB::table("phones");
            $query->select(\PATHAODB::raw("count(extension) as tc"));

            if(isset($_GET['extension']) && !empty($_GET['extension'])){
                $query->where("extension","=",$_GET['extension']);
            }
            if(isset($_GET['usergroup']) && is_array($_GET['usergroup']) && !in_array("ALL",$_GET['usergroup'])){
                $query->whereIn("user_group",$_GET['usergroup']);
            }

            $result = $query->first();

            return (int) $result->tc;
        });

        $paginator->setSliceCallback(function ($offset,$length){
            $query = \PATHAODB::table("phones");
            $query->select(["extension","protocol","server_ip","outbound_cid","voicemail_id","status","fullname","messages",
                "old_messages","user_group","active","pass"]);
            $query->limit($length);
            $query->offset($offset);
            $query->orderBy('extension', 'ASC');

            if(isset($_GET['extension']) && !empty($_GET['extension'])){
                $query->where("extension","=",$_GET['extension']);
            }

            if(isset($_GET['usergroup']) && is_array($_GET['usergroup']) && !in_array("ALL",$_GET['usergroup'])){
                $query->whereIn("user_group",$_GET['usergroup']);
            }



            $result = $query->get();
            return (array) $result;
        });

        $currentPage = 1;
        if(isset($_GET['p'])){
            $currentPage = $_GET['p'];
        }
        $pagination = $paginator->paginate((int) $currentPage);

        ?>
        <div class="col-sm-12">
            <div class="panel panel-default card-view">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h6 class="panel-title txt-dark">Phone List</h6>
                    </div>
                    <div class="pull-right">
                        <button data-toggle="modal" data-target="#search-modal"  class="btn btn-primary btn-rounded btn-icon left-icon"> <i style="color:white;" class="fa fa-search"></i> <span>Search</span></button>
                        <button data-toggle="modal" data-target="#add-modal"  class="btn btn-success btn-rounded btn-icon left-icon"> <i style="color:white;" class="fa fa-plus"></i> <span>Add Phone</span></button>

                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="panel-wrapper collapse in">
                    <div class="panel-body">
                        <div id="add-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                        <h5 class="modal-title">Add New Phone</h5>
                                    </div>

                                    <form method="POST" action="showphones.php">
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <input type="hidden" name="addphone" value="1">

                                                <label class="control-label mb-10" for="extension">Phone Extension</label>
                                                <div class="input-group">
                                                    <div class="input-group-addon"><i class="icon-phone"></i></div>
                                                    <input type="text" class="form-control" name="extension" value="" placeholder="Enter Phone Extension" required>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label mb-10" for="password">Password</label>
                                                <div class="input-group">
                                                    <div class="input-group-addon"><i class="icon-lock"></i></div>
                                                    <input type="password" class="form-control" name="password" value="" placeholder="Enter Password" required>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label mb-10" for="outbound_cid">Outbound CallerID</label>
                                                <div class="input-group">
                                                    <div class="input-group-addon"><i class="icon-user-follow"></i></div>
                                                    <input type="text" class="form-control" name="outbound_cid" value="" placeholder="Enter Outbound caller id" required>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label mb-10">User Group</label>
                                                <select name="usergroup" class="selectpicker" data-style="form-control btn-default btn-outline" required>
                                                    <?php
                                                    $usergroups = getUserGroup();
                                                    echo '<option value="---ALL---" selected>All Admin User Group</option>';
                                                    foreach($usergroups as $ugroup){
                                                        $arr = json_decode($ugroup, true);
                                                        echo '<option value="'. $ugroup -> user_group .'">'. $ugroup -> group_name .'</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label mb-10">Server IP</label>
                                                <select name="server_ip" class="selectpicker" data-style="form-control btn-default btn-outline" required>
                                                    <?php
                                                    $serverips = getServerIPs();
                                                    foreach($serverips as $sip){
                                                        $arr = json_decode($ugroup, true);
                                                        echo '<option value="'. $sip -> server_ip .'">'. $sip -> server_ip .' - '. $sip -> server_description .' - '. $sip -> external_ip .'</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label mb-10">Status</label>
                                                <select name="status" class="selectpicker" data-style="form-control btn-default btn-outline" required>
                                                        <option value="ACTIVE" selected="">Active</option>
                                                        <option value="SUSPENDED">Suspended</option>
                                                        <option value="CLOSED">Closed</option>
                                                        <option value="PENDING">Pending</option>
                                                        <option value="ADMIN">Admin</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label mb-10">Account Active?</label>
                                                <select name="isactive" class="selectpicker" data-style="form-control btn-default btn-outline" required>
                                                    <option value="N">No</option>
                                                    <option value="Y" selected>Yes</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label mb-10">Protocol</label>
                                                <select name="protocol" class="selectpicker" data-style="form-control btn-default btn-outline" required>
                                                        <option selected="">SIP</option>
                                                        <option>Zap</option>
                                                        <option>IAX2</option>
                                                        <option value="EXTERNAL">EXTERNAL</option>
                                                        <option>DAHDI</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-success">Add</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div id="edit-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                        <h5 class="modal-title">Edit Phone</h5>
                                    </div>

                                    <form method="POST" action="showphones.php">
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <input type="hidden" id="editphone" name="editphone" value="1">

                                                <label class="control-label mb-10" for="extension">Phone Extension</label>
                                                <div class="input-group">
                                                    <div class="input-group-addon"><i class="icon-phone"></i></div>
                                                    <input type="text" class="form-control" name="extension" id="extension" value="" placeholder="Enter Phone Extension" required>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label mb-10" for="password">Password</label>
                                                <div class="input-group">
                                                    <div class="input-group-addon"><i class="icon-lock"></i></div>
                                                    <input type="password" class="form-control" name="password" id="password" value="" placeholder="Enter Password" required>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label mb-10" for="outbound_cid">Outbound CallerID</label>
                                                <div class="input-group">
                                                    <div class="input-group-addon"><i class="icon-user-follow"></i></div>
                                                    <input type="text" class="form-control" name="outbound_cid" id="outbound_cid" value="" placeholder="Enter Outbound caller id" required>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label mb-10">User Group</label>
                                                <select name="usergroup" id="usergroup" class="selectpicker" data-style="form-control btn-default btn-outline" required>
                                                    <?php
                                                    $usergroups = getUserGroup();
                                                    echo '<option value="---ALL---" selected>All Admin User Group</option>';
                                                    foreach($usergroups as $ugroup){
                                                        $arr = json_decode($ugroup, true);
                                                        echo '<option value="'. $ugroup -> user_group .'">'. $ugroup -> group_name .'</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label mb-10">Server IP</label>
                                                <select name="server_ip" id="server_ip" class="selectpicker" data-style="form-control btn-default btn-outline" required>
                                                    <?php
                                                    $serverips = getServerIPs();
                                                    foreach($serverips as $sip){
                                                        $arr = json_decode($ugroup, true);
                                                        echo '<option value="'. $sip -> server_ip .'">'. $sip -> server_ip .' - '. $sip -> server_description .' - '. $sip -> external_ip .'</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label mb-10">Status</label>
                                                <select name="status" id="status" class="selectpicker" data-style="form-control btn-default btn-outline" required>
                                                    <option value="ACTIVE" selected="">Active</option>
                                                    <option value="SUSPENDED">Suspended</option>
                                                    <option value="CLOSED">Closed</option>
                                                    <option value="PENDING">Pending</option>
                                                    <option value="ADMIN">Admin</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label mb-10">Account Active?</label>
                                                <select name="isactive" id="isactive" class="selectpicker" data-style="form-control btn-default btn-outline" required>
                                                    <option value="N">No</option>
                                                    <option value="Y" selected>Yes</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label mb-10">Protocol</label>
                                                <select name="protocol" id="protocol" class="selectpicker" data-style="form-control btn-default btn-outline" required>
                                                    <option selected="">SIP</option>
                                                    <option>Zap</option>
                                                    <option>IAX2</option>
                                                    <option value="EXTERNAL">EXTERNAL</option>
                                                    <option>DAHDI</option>
                                                </select>
                                            </div>

                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-success">Update</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div id="search-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                        <h5 class="modal-title">Search</h5>
                                    </div>

                                    <form method="GET">
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label class="control-label mb-10" for="extension">Extension</label>
                                                <div class="input-group">
                                                    <div class="input-group-addon"><i class="icon-user-follow"></i></div>
                                                    <input type="text" class="form-control" name="extension" id="extension" value="" placeholder="Search extension">
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="control-label mb-10" for="dialplan">Dial Plan</label>
                                                <div class="input-group">
                                                    <div class="input-group-addon"><i class="icon-user-follow"></i></div>
                                                    <input type="text" class="form-control" name="dialplan" id="dialplan" value="" placeholder="Search Dial Plan">
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="control-label mb-10">User Group</label>
                                                <select name="usergroup[]" class="selectpicker" multiple data-style="form-control btn-default btn-outline">
                                                    <?php
                                                    $usergroups = getUserGroup();
                                                    echo '<option value="ALL" selected>All</option>';
                                                    foreach($usergroups as $ugroup){
                                                        $arr = json_decode($ugroup, true);
                                                        echo '<option value="'. $ugroup -> user_group .'">'. $ugroup -> group_name .'</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-success">Search</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="table-wrap">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                    <tr>
                                        <th>Extension</th>
                                        <th>Protocol</th>
                                        <th>Server</th>
                                        <th>Outbound CID</th>
                                        <th>Status</th>
                                        <th>Name</th>
                                        <th>Voice mail</th>
                                        <th>User Group</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $phones = $pagination->getItems();

                                    foreach($phones as $phone){
                                        $statusClass = "success";
                                        if($phone -> status == "SUSPENDED")
                                            $statusClass = "danger";
                                        if($phone -> status == "CLOSED")
                                            $statusClass = "warning";
                                        if($phone -> status == "PENDING")
                                            $statusClass = "info";
                                        if($phone -> status == "ADMIN")
                                            $statusClass = "primary";
                                        echo '<tr>
                                        <td><span class=\'label label-danger\'>' . $phone -> extension . '</span></td>
                                        <td>' . $phone -> protocol . '</td>
                                        <td><span class=\'label label-info\'>' . $phone -> server_ip . '</span></td>
                                        <td><span class=\'label label-primary\'>' . $phone -> outbound_cid . '</span></td>
                                        <td><span class="label label-'.$statusClass.'">' . $phone -> status . '</span></td>
                                        <td>' . $phone -> fullname . '</td>
                                        <td>' . $phone -> voicemail_id . '</td>
                                        <td><span class=\'label label-success\'>' . $phone -> user_group . '</span></td>
                                        <td data-extension="' . $phone -> extension . '" data-protocol="'.$phone -> protocol.'"  data-serverip="'.$phone -> server_ip.'"
                                         data-cid="'.$phone -> outbound_cid.'" data-status="'.$phone -> status.'" data-usergroup="'.$phone -> user_group.'" data-active="'.$phone -> active.'"
                                          data-pass="'.$phone -> pass.'">
                                            <a href="#" class="mr-25" data-toggle="modal" data-target="#edit-modal">
                                             <i data-toggle="tooltip" data-original-title="Edit" class="editBtn fa fa-pencil text-inverse m-r-10"></i>
                                            </a>
                                            <a href="showusers.php?phone=' . $phone -> extension . '" target="_blank" class="mr-25">
                                             <i data-toggle="tooltip" data-original-title="Phone User" class="fa fa-history text-inverse m-r-10"></i>
                                            </a>
                                            <a href="#" data-toggle="modal" data-target="#delete-modal" class="mr-25">
                                             <i data-toggle="tooltip" data-original-title="Delete Phone" class="fa fa-trash text-danger text-inverse m-r-10"></i>
                                            </a>
                                        </td>
                                    </tr>';
                                    }
                                    ?>


                                    </tbody>


                                </table>
                                <div class="fixed-table-pagination">

                                    <?php

                                    if($current = ($pagination->getFirstPageNumber() == $pagination->getCurrentPageNumber())){
                                        $current = "disabled";
                                    }

                                    echo '<ul class="pagination"><li class="prev '.$current.'"><a href="'.$pagination->getQueryString().'p=' . $pagination->getPreviousPageNumber() . '"><span class="fa fa-chevron-left"></span></a></li>
                        <li class="prev '.$current.'"><a href="'.$pagination->getQueryString().'p=' . $pagination->getFirstPageNumber() . '">First</a></li>
                        ';
                                    foreach ($pagination->getPages() as $page) {
                                        if($current = ($page == $pagination->getCurrentPageNumber())){
                                            $current = "active";
                                        }
                                        echo '<li class="'.$current.'"><a href="'.$pagination->getQueryString().'p=' . $page . '">' . $page . '</a></li>';
                                    }
                                    if($current = ($pagination->getLastPageNumber() == $pagination->getCurrentPageNumber())){
                                        $current = "disabled";
                                    }
                                    echo '<li class="next '.$current.'"><a href="'.$pagination->getQueryString().'p=' . $pagination->getLastPageNumber() . '">Last</a></li>
                        <li class="next '.$current.'"><a href="'.$pagination->getQueryString().'p=' . $pagination->getNextPageNumber() . '"><span class="fa fa-chevron-right"></span></a></li>
                        </ul>';

                                    ?>
                                    <div class="pull-right">
                                        <?php

                                        echo '<span>Showing: '.$pagination->getItemsTill().' out of '.$pagination->getTotalNumberOfItems().'</span>';
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Table Hover -->
    </div>
</div>
<div class="modal fade" id="delete-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h5 class="modal-title" id="exampleModalLabel1">Confirmation</h5>
            </div>
            <form method="POST">
                <div class="modal-body">
                    Are you sure you want to remove this phone?
                    <input type="hidden" id="deletephone" name="deletephone" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Remove Phone</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once "footer.php";
?>

<script>
    $('#edit-modal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget).parent();
        $("#extension").val(button.data('extension'));
        $("#password").val(button.data('pass'));
        $("#outbound_cid").val(button.data('cid'));
        $("#usergroup").val(button.data('usergroup')).trigger('change');
        $("#server_ip").val(button.data('serverip')).trigger('change');
        $("#status").val(button.data('status')).trigger('change');
        $("#isactive").val(button.data('active')).trigger('change');
        $("#protocol").val(button.data('protocol')).trigger('change');
        $("#editphone").val(button.data('extension'));
    });
    $('#delete-modal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget).parent();
        var extension = button.data('extension');
        var modal = $(this)
        modal.find('.modal-title').text('Remove Phone ' + extension)
        modal.find('#deletephone').val(extension)
    });
    <?php if($error){ ?>
    $.toast({
        heading: 'Error',
        text: '<?php echo $error; ?>',
        position: 'top-right',
        loaderBg:'#e69a2a',
        icon: 'danger',
        hideAfter: 3500,
        stack: 6
    });
    <?php } ?>

    <?php if($successAdd){ ?>
    $.toast({
        heading: 'Success',
        text: 'Phone Added Successfully',
        position: 'top-right',
        loaderBg:'#e69a2a',
        icon: 'success',
        hideAfter: 3500,
        stack: 6
    });
    <?php } ?>
    <?php if($successEdit){ ?>
    $.toast({
        heading: 'Success',
        text: 'Phone #<?php echo $successEdit; ?> Update Successfully',
        position: 'top-right',
        loaderBg:'#e69a2a',
        icon: 'success',
        hideAfter: 3500,
        stack: 6
    });
    <?php } ?>
    <?php if($successDelete){ ?>
    $.toast({
        heading: 'Success',
        text: 'Phone #<?php echo $successDelete; ?> Removed Successfully',
        position: 'top-right',
        loaderBg:'#e69a2a',
        icon: 'success',
        hideAfter: 3500,
        stack: 6
    });
    <?php } ?>
</script>