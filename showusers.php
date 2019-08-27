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

$page = "Show Users";
$parent = "user";

require_once "header.php";

$successEdit = false;
$successDelete = false;
$successAdd = false;
$error = false;
if(isset($_POST['deleteuser']) && is_numeric($_POST['deleteuser'])){
    $userid = trim($_POST['deleteuser']);
    \PATHAODB::table("vicidial_users")->where('user_id', $userid)->delete();
    $successDelete = $userid;
}
if(isset($_POST['edituser'])){
    $userid = trim($_POST['edituser']);
    $oldphone = trim($_POST['oldphone']);
    $oldphonepass = trim($_POST['oldphonepass']);
    $user = strtolower(trim($_POST['userid']));
    $pass = trim($_POST['password']);
    $full_name = trim($_POST['name']);
    $userlevel = trim($_POST['userlevel']);
    $usergroup = trim($_POST['usergroup']);
    $phonelogin = trim($_POST['phonelogin']);
    $phonepass = trim($_POST['phonepass']);
    $manualcall = trim($_POST['manualcall']);
    $isactive = trim($_POST['isactive']);

    if(strlen($user) <= 1){
        $error = "Please enter username";
    }

    if(isAgentExists($user,$userid)){
        $error = "Agent username is already taken";
    }

    if(strlen($pass) <= 1){
        $error = "Please enter password";
    }
    if(!is_numeric($userlevel)){
        $error = "Please enter user level";
    }
    if(strlen($full_name) <= 1){
        $error = "Please enter full name";
    }
    if(!is_numeric($phonelogin)){
        $error = "Please enter phone login";
    }
    if(!isPhoneExists($phonelogin)){
        $error = "Phone login doesn't exists";
    }
    if(strlen($phonepass) <= 1){
        $error = "Please enter phone password";
    }
    if($oldphone != $phonelogin){
        if(isPhoneExistsInUser($phonelogin,$userid)){
            $error = "Another agent is already using this phone";
        }
        elseif(!checkPhonePass($phonelogin,$phonepass)){
            $error = "Phone password is incorrect. Please check";
        }
    }
    $pass_hash = "";

    $data = ['user' => $user,
        "pass" => $pass,
        "full_name" => $full_name,
        "user_level" => $userlevel,
        "user_group" => $usergroup,
        "phone_login" => $phonelogin,
        "phone_pass" => $phonepass,
        "pass_hash" => $pass_hash,
        "agentcall_manual" => $manualcall,
        "active" => $manualcall
    ];

    if(!$error){
        \PATHAODB::table("vicidial_users")->where('user_id', $userid)->update($data);
        if($oldphone == $phonelogin && $oldphonepass != $phonepass){
            updatePhone($phonelogin,$phonepass);
        }
        $successEdit = $userid;
    }
}
if(isset($_POST['adduser'])){
    $user = strtolower(trim($_POST['userid']));
    $pass = trim($_POST['password']);
    $full_name = trim($_POST['name']);
    $userlevel = trim($_POST['userlevel']);
    $usergroup = trim($_POST['usergroup']);
    $phonelogin = trim($_POST['phonelogin']);
    $phonepass = trim($_POST['phonepass']);
    $manualcall = trim($_POST['manualcall']);

    if(strlen($user) <= 1){
        $error = "Please enter username";
    }

    if(isAgentExists($user)){
        $error = "Agent username is already taken";
    }

    if(strlen($pass) <= 1){
        $error = "Please enter password";
    }
    if(!is_numeric($userlevel)){
        $error = "Please enter user level";
    }
    if(strlen($full_name) <= 1){
        $error = "Please enter full name";
    }
    if(!is_numeric($phonelogin)){
        $error = "Please enter phone login";
    }
    if(!isPhoneExists($phonelogin)){
        $error = "Phone login doesn't exists";
    }
    if(isPhoneExistsInUser($phonelogin)){
        $error = "Another agent is already using this phone";
    }
    if(strlen($phonepass) <= 1){
        $error = "Please enter phone password";
    }
    if(!checkPhonePass($phonelogin,$phonepass)){
        $error = "Phone password is incorrect. Please check";
    }
    $pass_hash = "";

    $data = ['user' => $user,
        "pass" => $pass,
        "full_name" => $full_name,
        "user_level" => $userlevel,
        "user_group" => $usergroup,
        "phone_login" => $phonelogin,
        "phone_pass" => $phonepass,
        "pass_hash" => $pass_hash,
        "agentcall_manual" => $manualcall
    ];

    if(!$error){
        \PATHAODB::table("vicidial_users")->insert($data);
        $successAdd = true;
    }
}

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
                $query = \PATHAODB::table("vicidial_users");
                $query->select(\PATHAODB::raw("count(user) as tc"));

                if(isset($_GET['userid']) && !empty($_GET['userid'])){
                    $query->where("user","=",$_GET['userid']);
                }
                if(isset($_GET['name']) && !empty($_GET['name'])){
                    $query->where("full_name","LIKE","%" . $_GET['name'] . "%");
                }
                if(isset($_GET['usergroup']) && is_array($_GET['usergroup']) && !in_array("ALL",$_GET['usergroup'])){
                    $query->whereIn("user_group",$_GET['usergroup']);
                }

                if(isset($_GET['isactive']) && $_GET['isactive'] == "N"){
                    $query->where("active","=","N");
                }else{
                    $query->where("active","=","Y");
                }
                if(isset($_GET['phone']) && !empty($_GET['phone'])){
                    $query->where("phone_login","=",$_GET['phone']);
                }

                $result = $query->first();

                return (int) $result->tc;
            });

            $paginator->setSliceCallback(function ($offset,$length){
                $query = \PATHAODB::table("vicidial_users");
                $query->select(["user","user_id","full_name","user_level","user_group","active","agentcall_manual","pass","phone_login","phone_pass"]);
                $query->limit($length);
                $query->offset($offset);
                $query->orderBy('user', 'ASC');

                if(isset($_GET['userid']) && !empty($_GET['userid'])){
                    $query->where("user","=",$_GET['userid']);
                }
                if(isset($_GET['name']) && !empty($_GET['name'])){
                    $query->where("full_name","LIKE","%" . $_GET['name'] . "%");
                }
                if(isset($_GET['usergroup']) && is_array($_GET['usergroup']) && !in_array("ALL",$_GET['usergroup'])){
                    $query->whereIn("user_group",$_GET['usergroup']);
                }

                if(isset($_GET['isactive']) && $_GET['isactive'] == "N"){
                    $query->where("active","=","N");
                }else{
                    $query->where("active","=","Y");
                }
                if(isset($_GET['phone']) && !empty($_GET['phone'])){
                    $query->where("phone_login","=",$_GET['phone']);
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
                            <h6 class="panel-title txt-dark">Users List</h6>
                        </div>
                        <div class="pull-right">
                            <button data-toggle="modal" data-target="#search-modal"  class="btn btn-primary btn-rounded btn-icon left-icon"> <i style="color:white;" class="fa fa-search"></i> <span>Search</span></button>
                            <button data-toggle="modal" data-target="#add-modal"  class="btn btn-success btn-rounded btn-icon left-icon"> <i style="color:white;" class="fa fa-user-plus"></i> <span>Add User</span></button>

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
                                            <h5 class="modal-title">Add New User</h5>
                                        </div>

                                        <form method="POST" action="showusers.php">
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <input type="hidden" name="adduser" value="1">

                                                    <label class="control-label mb-10" for="userid">User Number</label>
                                                    <div class="input-group">
                                                        <div class="input-group-addon"><i class="icon-user-follow"></i></div>
                                                        <input type="text" class="form-control" name="userid" id="userid" value="" placeholder="Enter User Number">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label mb-10" for="password">Password</label>
                                                    <div class="input-group">
                                                        <div class="input-group-addon"><i class="icon-lock"></i></div>
                                                        <input type="password" class="form-control" name="password" id="password" value="" placeholder="Enter Password">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label mb-10" for="name">Full Name</label>
                                                    <div class="input-group">
                                                        <div class="input-group-addon"><i class="icon-user-follow"></i></div>
                                                        <input type="text" class="form-control" name="name" id="name" value="" placeholder="Enter Full Name">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label mb-10">Level</label>
                                                    <select name="userlevel" class="selectpicker" data-style="form-control btn-default btn-outline">
                                                        <option value="1" selected>Level 1</option>
                                                        <option value="2">Level 2</option>
                                                        <option value="3">Level 3</option>
                                                        <option value="4">Level 4</option>
                                                        <option value="5">Level 5</option>
                                                        <option value="6">Level 6</option>
                                                        <option value="7">Level 7</option>
                                                        <option value="8">Level 8</option>
                                                        <option value="9">Level 9</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label mb-10">User Group</label>
                                                    <select name="usergroup" class="selectpicker" data-style="form-control btn-default btn-outline">
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
                                                    <label class="control-label mb-10" for="phonelogin">Phone Login</label>
                                                    <div class="input-group">
                                                        <div class="input-group-addon"><i class="icon-phone"></i></div>
                                                        <input type="text" class="form-control" name="phonelogin" id="phonelogin" value="" placeholder="Enter Phone">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label mb-10" for="phonepass">Phone Password</label>
                                                    <div class="input-group">
                                                        <div class="input-group-addon"><i class="icon-lock"></i></div>
                                                        <input type="password" class="form-control" name="phonepass" id="phonepass" value="" placeholder="Enter Phone Password">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label mb-10">Manual Call</label>
                                                    <select name="manualcall" class="selectpicker" data-style="form-control btn-default btn-outline">
                                                        <option value="0" selected>No</option>
                                                        <option value="1">Yes</option>
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
                                            <h5 class="modal-title">Edit User</h5>
                                        </div>

                                        <form method="POST" action="showusers.php">
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <input type="hidden" id="edituser" name="edituser" value="1">
                                                    <input type="hidden" id="oldphone" name="oldphone" value="1">
                                                    <input type="hidden" id="oldphonepass" name="oldphonepass" value="1">
                                                    <label class="control-label mb-10" for="userid">User Number</label>
                                                    <div class="input-group">
                                                        <div class="input-group-addon"><i class="icon-user-follow"></i></div>
                                                        <input type="text" class="form-control" name="userid" id="euserid" value="" placeholder="Enter User Number">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label mb-10" for="password">Password</label>
                                                    <div class="input-group">
                                                        <div class="input-group-addon"><i class="icon-lock"></i></div>
                                                        <input type="password" class="form-control" name="password" id="epassword" value="" placeholder="Enter Password">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label mb-10" for="name">Full Name</label>
                                                    <div class="input-group">
                                                        <div class="input-group-addon"><i class="icon-user-follow"></i></div>
                                                        <input type="text" class="form-control" name="name" id="ename" value="" placeholder="Enter Full Name">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label mb-10">Level</label>
                                                    <select id="euserlevel" name="userlevel" class="selectpicker" data-style="form-control btn-default btn-outline">
                                                        <option value="1" selected>Level 1</option>
                                                        <option value="2">Level 2</option>
                                                        <option value="3">Level 3</option>
                                                        <option value="4">Level 4</option>
                                                        <option value="5">Level 5</option>
                                                        <option value="6">Level 6</option>
                                                        <option value="7">Level 7</option>
                                                        <option value="8">Level 8</option>
                                                        <option value="9">Level 9</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label mb-10">User Group</label>
                                                    <select id="eusergroup" name="usergroup" class="selectpicker" data-style="form-control btn-default btn-outline">
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
                                                    <label class="control-label mb-10" for="phonelogin">Phone Login</label>
                                                    <div class="input-group">
                                                        <div class="input-group-addon"><i class="icon-phone"></i></div>
                                                        <input type="text" class="form-control" name="phonelogin" id="ephonelogin" value="" placeholder="Enter Phone">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label mb-10" for="phonepass">Phone Password</label>
                                                    <div class="input-group">
                                                        <div class="input-group-addon"><i class="icon-lock"></i></div>
                                                        <input type="password" class="form-control" name="phonepass" id="ephonepass" value="" placeholder="Enter Phone Password">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label mb-10">Is Active?</label>
                                                    <select id="eactive" name="isactive" class="selectpicker" data-style="form-control btn-default btn-outline">
                                                        <option value="N">No</option>
                                                        <option value="Y">Yes</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label mb-10">Manual Call</label>
                                                    <select id="emanualcall" name="manualcall" class="selectpicker" data-style="form-control btn-default btn-outline">
                                                        <option value="0">No</option>
                                                        <option value="1">Yes</option>
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
                                                    <label class="control-label mb-10" for="userid">UserID</label>
                                                    <div class="input-group">
                                                        <div class="input-group-addon"><i class="icon-user-follow"></i></div>
                                                        <input type="text" class="form-control" name="userid" id="userid" value="" placeholder="Search user id">
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="control-label mb-10" for="name">Name</label>
                                                    <div class="input-group">
                                                        <div class="input-group-addon"><i class="icon-user-follow"></i></div>
                                                        <input type="text" class="form-control" name="name" id="name" value="" placeholder="Search name">
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
                                                <div class="form-group">
                                                    <label class="control-label mb-10">Is Active?</label>
                                                    <select name="isactive" class="selectpicker" data-style="form-control btn-default btn-outline">
                                                        <option value="Y" selected>Yes</option>
                                                        <option value="N" >No</option>

                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label mb-10" for="phone">Phone</label>
                                                    <div class="input-group">
                                                        <div class="input-group-addon"><i class="icon-phone"></i></div>
                                                        <input type="text" class="form-control" name="phone" id="phone" value="" placeholder="Phone">
                                                    </div>
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
                                            <th>UserID</th>
                                            <th>Full Name</th>
                                            <th>Level</th>
                                            <th>Group</th>
                                            <th>Active</th>
                                            <th>Manual Call</th>
                                            <th>Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $users = $pagination->getItems();

                                        foreach($users as $user){
                                            if($user->active == "Y"){
                                                $activeStatus = "<span class='label label-success'>Yes</span>";
                                            }
                                            else{
                                                $activeStatus = "<span class='label label-danger'>No</span>";
                                            }

                                            if($user -> agentcall_manual == 1){
                                                $mCall = "<span class='label label-success'>Yes</span>";
                                            }
                                            else{
                                                $mCall = "<span class='label label-danger'>No</span>";
                                            }
                                            echo '<tr>
                                        <td>' . $user -> user . '</td>
                                        <td>' . $user -> full_name . '</td>
                                        <td><span class=\'label label-info\'>' . $user->user_level . '</span></td>
                                        <td><span class=\'label label-primary\'>' . $user->user_group . '</span></td>
                                        <td>' . $activeStatus . '</td>
                                        <td>'.$mCall.'</td>
                                        <td>
                                            <a href="#" class="mr-25" data-toggle="modal" data-target="#edit-modal">
                                             <i data-userid="' . $user -> user_id . '" data-user="' . $user -> user . '" data-pass="' . $user -> pass . '" data-name="' . $user -> full_name . '"
                                              data-level="' . $user -> user_level . '" data-group="' . $user -> user_group . '" data-isactive="' . $user -> active . '"
                                               data-ismanual="' . $user -> agentcall_manual . '" data-phone="' . $user -> phone_login . '" data-phonepass="' . $user -> phone_pass . '"
                                                data-toggle="tooltip" data-original-title="Edit" class="editBtn fa fa-pencil text-inverse m-r-10"></i>
                                            </a>
                                            
                                            <a href="recordinglog.php?userid=' . $user -> user . '" target="_blank" class="mr-25">
                                             <i data-toggle="tooltip" data-original-title="Call History" class="fa fa-history text-inverse m-r-10"></i>
                                            </a>
                                            <a href="#" data-toggle="modal" data-userid="' . $user -> user_id . '" data-user="' . $user -> user . '" data-target="#delete-modal" class="mr-25">
                                             <i data-toggle="tooltip" data-original-title="Delete Agent" class="fa fa-trash text-danger text-inverse m-r-10"></i>
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
                    Are you sure you want to remove this agent?
                    <input type="hidden" id="deleteuser" name="deleteuser" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Remove Agent</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once "footer.php";
?>

<script>
    $(".editBtn").on("click",function(){
        $("#edituser").val($(this).data("userid"));
        $("#oldphone").val($(this).data("phone"));
        $("#oldphonepass").val($(this).data("phonepass"));
        $("#euserid").val($(this).data("user"));
        $("#epassword").val($(this).data("pass"));
        $("#ename").val($(this).data("name"));
        $("#ephonelogin").val($(this).data("phone"));
        $("#ephonepass").val($(this).data("phonepass"));
        $('#eusergroup').val($(this).data("group")).trigger('change');
        $('#eactive').val($(this).data("isactive")).trigger('change');
        $('#emanualcall').val($(this).data("ismanual")).trigger('change');
        $('#euserlevel').val($(this).data("level")).trigger('change');
    });
    $('#delete-modal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var userid = button.data('userid');
        var agent = button.data('user');
        var modal = $(this)
        modal.find('.modal-title').text('Remove Agent ' + agent)
        modal.find('#deleteuser').val(userid)
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
        text: 'Agent Added Successfully',
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
        text: 'Agent #<?php echo $successEdit; ?> Update Successfully',
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
        text: 'Agent #<?php echo $successDelete; ?> Removed Successfully',
        position: 'top-right',
        loaderBg:'#e69a2a',
        icon: 'success',
        hideAfter: 3500,
        stack: 6
    });
    <?php } ?>
</script>