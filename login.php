<?php
/**
 * Created by PhpStorm.
 * User: elomelo
 * Date: 24/4/18
 * Time: 11:39 PM
 */
include_once "includes/common.php";

if(isset($_GET['logout'])){
    $auth->logout();
    header("Location: login.php");
    die();
}

if($auth->checkSession()){
    header("Location: index.php?alreadylogged");
    die();
}

$error = false;

if(isset($_POST['username']) && isset($_POST['password'])){
    if($auth->checkLogin($_POST['username'],$_POST['password'])){
        if(isset($_GET['redirect'])){
            header("Location: ".urldecode($_GET['redirect']));
        }else{
            header("Location: index.php");
        }
        die();
    }else{
        $error = $auth->loginError();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>PathaoCS</title>
    <meta name="description" content="" />
    <meta name="keywords" content="" />
    <meta name="author" content=""/>

    <!-- Favicon -->
    <link rel="shortcut icon" href="favicon.ico">
    <link rel="icon" href="favicon.ico" type="image/x-icon">

    <!-- vector map CSS -->
    <link href="vendors/bower_components/jasny-bootstrap/dist/css/jasny-bootstrap.min.css" rel="stylesheet" type="text/css"/>



    <!-- Custom CSS -->
    <link href="dist/css/style.css" rel="stylesheet" type="text/css">
</head>
<body>
<!--Preloader-->
<div class="preloader-it">
    <div class="la-anim-1"></div>
</div>
<!--/Preloader-->

<div class="wrapper pa-0">
    <header class="sp-header">
        <div class="sp-logo-wrap pull-left">
            <a href="index.html">
                <img class="brand-img mr-10" src="dist/img/logo.png" alt="brand"/>
                <span class="brand-text">pathaoCS</span>
            </a>
        </div>
        <div class="clearfix"></div>
    </header>

    <!-- Main Content -->
    <div class="page-wrapper pa-0 ma-0 auth-page">
        <div class="container-fluid">
            <!-- Row -->
            <div class="table-struct full-width full-height">
                <div class="table-cell vertical-align-middle auth-form-wrap">
                    <div class="auth-form  ml-auto mr-auto no-float">
                        <div class="row">
                            <div class="col-sm-12 col-xs-12">
                                <div class="mb-30">
                                    <h3 class="text-center txt-dark mb-10">Sign in to PathaoCS</h3>
                                    <h6 class="text-center nonecase-font txt-grey">Enter your details below</h6>
                                </div>
                                <?php
                                if($error){
                                    echo '<div class="alert alert-danger alert-dismissable alert-style-1">
											<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
											<i class="zmdi zmdi-block"></i>'.$error.'
										</div>';
                                }
                                ?>
                                <div class="form-wrap">
                                    <form action="" method="POST">
                                        <div class="form-group">
                                            <label class="control-label mb-10" for="exampleInputEmail_2">Username</label>
                                            <input type="text" class="form-control" name="username" required="" placeholder="Enter Username">
                                        </div>
                                        <div class="form-group">
                                            <label class="pull-left control-label mb-10" for="exampleInputpwd_2">Password</label>
                                            <div class="clearfix"></div>
                                            <input type="password" class="form-control" name="password" required="" placeholder="Enter Password">
                                        </div>

                                        <div class="form-group text-center">
                                            <button type="submit" class="btn btn-info btn-rounded">sign in</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Row -->
        </div>

    </div>
    <!-- /Main Content -->

</div>
<!-- /#wrapper -->

<!-- JavaScript -->

<!-- jQuery -->
<script src="vendors/bower_components/jquery/dist/jquery.min.js"></script>

<!-- Bootstrap Core JavaScript -->
<script src="vendors/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="vendors/bower_components/jasny-bootstrap/dist/js/jasny-bootstrap.min.js"></script>

<!-- Slimscroll JavaScript -->
<script src="dist/js/jquery.slimscroll.js"></script>

<!-- Init JavaScript -->
<script src="dist/js/init.js"></script>
</body>
</html>

