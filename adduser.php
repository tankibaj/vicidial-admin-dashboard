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

$page = "Add User";
$parent = "user";
$success = false;
$error = false;
if(isset($_POST['name'])){
    $user = trim($_POST['userid']);
    $pass = trim($_POST['password']);
    $full_name = trim($_POST['name']);
    $userlevel = trim($_POST['userlevel']);
    $usergroup = trim($_POST['usergroup']);
    $phonelogin = trim($_POST['phonelogin']);
    $phonepass = trim($_POST['phonepass']);
    if(strlen($user) <= 1){
        $error = "Please enter username";
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
    if(strlen($phonepass) <= 1){
        $error = "Please enter phone password";
    }
    $pass_hash = "";

    $data = ['user' => $_POST['userid'],
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
        $success = true;
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
        <div class="col-sm-12">
            <?php if(!$success){ ?>
            <div class="panel panel-default card-view">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h6 class="panel-title txt-dark">Add User</h6>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="panel-wrapper collapse in">
                    <?php

                    if($error){
                        echo '<div class="alert alert-danger alert-dismissable">
											<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>'.$error.'
										</div>';
                    }

                    ?>
                    <div class="panel-body">
                        <form method="POST">
                            <div class="form-group">
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
                                    $query = \PATHAODB::table("vicidial_user_groups");
                                    $query->select(["user_group","group_name"]);
                                    $query->orderBy("user_group","ASC");
                                    $usergroups = $query->get();
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
                            <div class="form-group pull-right">
                                <button type="submit" class="btn btn-success">Save</button>
                            </div>
                            <div class="clearfix"></div>
                        </form>
                    </div>
                </div>
            </div>
            <?php }else{ ?>
                <div class="alert alert-success alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>User has been added successfully.
                </div>
            <?php } ?>
        </div>
        <!-- /Table Hover -->
    </div>
</div>


<?php
require_once "footer.php";
?>

