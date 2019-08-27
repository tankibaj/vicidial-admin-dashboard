<?php
/**
 * Created by PhpStorm.
 * User: elomelo
 * Date: 24/4/18
 * Time: 5:07 PM
 */

require_once "includes/common.php";
require_once "includes/CallsExport.php";

use \Common\CallsExport;

if(!$auth ->checkSession()){
    header("Location: login.php");
    die();
}

$page = "Export Calls";
$parent = "report";
$request_uri = $_SERVER['REQUEST_URI'];
$showTable = false;

if(isset($_GET['daterange']) && $_GET['daterange'] != ""){
    $dateRange = explode(" - ", $_GET['daterange']);
    $selectedUsergroup = (array)$_GET['usergroup'];
    $selectedGroup = (array)$_GET['group'];
    $selectedCampaign = (array)$_GET['campaign'];
    $selectedList = (array)$_GET['list'];
    $selectedStatus = (array)$_GET['status'];
    $exportType = $_GET['exporttype'];
    $callnote = (isset($_GET['callnote']) && $_GET['callnote'] != "") ? true : false;
    $recording = (isset($_GET['recording']) && $_GET['recording'] != "") ? true : false;
    $searchArchive = (isset($_GET[' ']) && $_GET['searcharchive'] != "") ? true : false;

    $callExport = new CallsExport($selectedGroup,$selectedCampaign,$selectedUsergroup,$selectedList,$selectedStatus,
        $exportType, $recording, $callnote, $searchArchive, $dateRange[0],$dateRange[1]);
    $callExport -> listData();
    die();
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
        <div class="col-md-12">
            <div class="panel panel-default card-view panel-refresh">
                <div class="refresh-container">
                    <div class="la-anim-1"></div>
                </div>
                <div class="panel-heading">
                    <div class="pull-left">
                        <h6 class="panel-title txt-dark">Search</h6>
                    </div>
                    <div class="pull-right">
                        <a class="pull-left inline-block mr-15" data-toggle="collapse" href="#collapse_1" aria-expanded="true">
                            <i class="zmdi zmdi-chevron-down"></i>
                            <i class="zmdi zmdi-chevron-up"></i>
                        </a>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div  id="collapse_1" class="panel-wrapper collapse in">
                    <div  class="panel-body">
                        <div class="form-wrap">
                            <form action="" method="GET">
                                <div class="form-group col-md-6">
                                    <label class="control-label mb-10 text-left">Date Range</label>
                                    <input class="form-control input-daterange-datepicker" type="text" name="daterange" value="" required/>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="control-label mb-10">CampaignID</label>
                                    <select name="campaign[]" class="selectpicker" multiple data-style="form-control btn-default btn-outline">
                                        <?php
                                        $campaigns = getCampaign();
                                        echo '<option value="NONE">None</option>';
                                        foreach($campaigns as $campaign){
                                            echo '<option value="'. $campaign -> campaign_id .'">'. $campaign -> campaign_id .'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="clearfix"></div>
                                <div class="form-group col-md-6">
                                    <label class="control-label mb-10">Statuses</label>
                                    <select name="status[]" class="selectpicker" multiple data-style="form-control btn-default btn-outline">
                                        <?php
                                        echo '<option value="ALL" selected>All</option>';
                                        $campaigns = getStatuses();
                                        foreach($campaigns as $campaign){
                                            echo '<option value="'. $campaign -> status .'">'. $campaign -> status_name .'</option>';
                                        }

                                        $campaigns = getStatuses2();
                                        foreach($campaigns as $campaign){
                                            echo '<option value="'. $campaign -> status .'">'. $campaign -> status_name .'</option>';
                                        }

                                        ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="control-label mb-10">User Group</label>
                                    <select name="usergroup[]" class="selectpicker" multiple data-style="form-control btn-default btn-outline">
                                        <?php
                                        $campaigns = getUserGroup();
                                        echo '<option value="ALL" selected>All</option>';
                                        foreach($campaigns as $campaign){
                                            echo '<option value="'. $campaign -> user_group .'">'. $campaign -> group_name .'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="clearfix"></div>
                                <div class="form-group col-md-6">
                                    <label class="control-label mb-10">Lists</label>
                                    <select name="list[]" class="selectpicker" multiple data-style="form-control btn-default btn-outline">
                                        <?php
                                        $campaigns = getListID();
                                        echo '<option value="ALL" selected>All</option>';
                                        foreach($campaigns as $campaign){
                                            echo '<option value="'. $campaign -> list_id .'">'. $campaign -> list_id .'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="control-label mb-10">Inbound Group</label>
                                    <select name="group[]" class="selectpicker" multiple data-style="form-control btn-default btn-outline">
                                        <?php
                                        $campaigns = getInboundGroup(false);
                                        echo '<option value="NONE">None</option>';
                                        foreach($campaigns as $campaign){
                                            echo '<option value="'. $campaign -> group_id .'">'. $campaign -> group_name .'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="control-label mb-10">Export Type</label>
                                    <select name="exporttype" class="selectpicker" data-style="form-control btn-default btn-outline">
                                        <?php
                                        echo '<option value="STANDARD" selected>Standard</option>';
                                        echo '<option value="EXTENDED">Extended 1</option>';
                                        echo '<option value="EXTENDED2">Extended 2</option>';
                                        echo '<option value="EXTENDED3">Extended 3</option>';
                                        echo '<option value="ALTERNATIVE1">Alternative 1</option>';
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <div class="checkbox checkbox-success">
                                        <input onclick="" id="callnote" name="callnote" type="checkbox">
                                        <label for="callnote">
                                            Per Call Notes
                                        </label>
                                    </div>
                                    <div class="checkbox checkbox-primary">
                                        <input onclick="" id="recording" name="recording" type="checkbox">
                                        <label for="recording">
                                            Recording Fields
                                        </label>
                                    </div>
                                    <div class="checkbox checkbox-danger">
                                        <input onclick="" id="searcharchive" name="searcharchive" type="checkbox">
                                        <label for="searcharchive">
                                            Search Archived Logs
                                        </label>
                                    </div>
                                </div>
                                <div class="clearfix"></div>

                                <div class="form-group col-md-6">
                                    <button type="submit" class="btn btn-success btn-anim"><i class="icon-rocket"></i><span class="btn-text">submit</span></button>
                                </div>
                                <div class="clearfix"></div>
                            </form>
                        </div>


                    </div>
                </div>
            </div>
        </div>

        <?php
        require_once "footer.php";
        ?>

