<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title><?php echo $page; ?> - PathaoCS</title>
    <meta name="description" content="" />
    <meta name="keywords" content="" />
    <meta name="author" content=""/>

    <!-- Favicon -->
    <link rel="shortcut icon" href="favicon.ico">
    <link rel="icon" href="favicon.ico" type="image/x-icon">

    <!-- Morris Charts CSS -->
    <link href="vendors/bower_components/morris.js/morris.css" rel="stylesheet" type="text/css"/>

    <!-- Data table CSS -->
    <link href="vendors/bower_components/datatables/media/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css"/>

    <link href="vendors/bower_components/jquery-toast-plugin/dist/jquery.toast.min.css" rel="stylesheet" type="text/css">

    <!-- Bootstrap Datetimepicker CSS -->
    <link href="vendors/bower_components/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.min.css" rel="stylesheet" type="text/css"/>

    <!-- Bootstrap Daterangepicker CSS -->
    <link href="vendors/bower_components/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css"/>

    <!-- bootstrap-select CSS -->
    <link href="vendors/bower_components/bootstrap-select/dist/css/bootstrap-select.min.css" rel="stylesheet" type="text/css"/>
    <!-- multi-select CSS -->
    <link href="vendors/bower_components/multiselect/css/multi-select.css" rel="stylesheet" type="text/css"/>
    <!-- select2 CSS -->
    <link href="vendors/bower_components/select2/dist/css/select2.min.css" rel="stylesheet" type="text/css"/>
    <!-- switchery CSS -->
    <link href="vendors/bower_components/switchery/dist/switchery.min.css" rel="stylesheet" type="text/css"/>
    <!-- Bootstrap Switches CSS -->
    <link href="vendors/bower_components/bootstrap-switch/dist/css/bootstrap3/bootstrap-switch.min.css" rel="stylesheet" type="text/css"/>
    <!-- bootstrap-touchspin CSS -->
    <link href="vendors/bower_components/bootstrap-touchspin/dist/jquery.bootstrap-touchspin.min.css" rel="stylesheet" type="text/css"/>
    <!-- Custom CSS -->
    <link href="dist/css/style.css" rel="stylesheet" type="text/css">
</head>

<body>
<!-- Preloader -->
<div class="preloader-it">
    <div class="la-anim-1"></div>
</div>
<!-- /Preloader -->
<div class="wrapper theme-1-active pimary-color-red">
    <!-- Top Menu Items -->
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="mobile-only-brand pull-left">
            <div class="nav-header pull-left">
                <div class="logo-wrap">
                    <a href="index.php">
                        <img class="brand-img" src="dist/img/logo-pathao.png" style="height: 21px;" alt="brand"/>
                        <span class="brand-text">pathaoCS</span>
                    </a>
                </div>
            </div>
            <a id="toggle_nav_btn" class="toggle-left-nav-btn inline-block ml-20 pull-left" href="javascript:void(0);"><i class="zmdi zmdi-menu"></i></a>
            <a id="toggle_mobile_nav" class="mobile-only-view" href="javascript:void(0);"><i class="zmdi zmdi-more"></i></a>

        </div>
        <div id="mobile_only_nav" class="mobile-only-nav pull-right">
            <ul class="nav navbar-right top-nav pull-right">
                <li>
                    <a id="open_right_sidebar" href="#"><i class="zmdi zmdi-settings top-nav-icon"></i></a>
                </li>

                <li class="dropdown auth-drp">
                    <a href="#" class="dropdown-toggle pr-0" data-toggle="dropdown"><img src="dist/img/user1.png" alt="user_auth" class="user-auth-img img-circle"/><span class="user-online-status"></span></a>
                    <ul class="dropdown-menu user-auth-dropdown" data-dropdown-in="flipInX" data-dropdown-out="flipOutX">
                        <li>
                            <a href="#"><i class="zmdi zmdi-account"></i><span>Hi, <?php echo $auth -> adminName(); ?></span></a>
                        </li>
                        <li class="divider"></li>
                        <li class="bg-primary text-center">
                            <?php
                            if($auth -> isSuperAdmin())
                                $adminText = "Super Administrator";
                            elseif($auth -> isAdmin())
                                $adminText = "Administrator";
                            elseif($auth -> isSupervisor())
                                $adminText = "Supervisor";
                            elseif($auth -> isAgent())
                                $adminText = "Agent";
                            ?>
                            <i class="fa fa-star" style="color: white;"></i>&nbsp;<?php echo $adminText; ?>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a href="login.php?logout=true"><i class="zmdi zmdi-power"></i><span>Log Out</span></a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
    <!-- /Top Menu Items -->



<?php
    require_once "leftbar.php";

?>

    <!-- Main Content -->
    <div class="page-wrapper">
