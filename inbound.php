<?php
/**
 * Created by PhpStorm.
 * User: elomelo
 * Date: 24/4/18
 * Time: 5:07 PM
 */

require_once "includes/common.php";
require_once "includes/Reports.php";

use \Common\Reports;

if(!$auth ->checkSession()){
    header("Location: login.php");
    die();
}



$page = "Inbound Reports";
$parent = "report";

$selectedCampaigns = [];
$dateEnd =  date("Y-m-d H:i:s", time());
$dateStart = date("Y-m-d", time()) . " 00:00:00";

$loadScripts = false;
$request_uri = $_SERVER['REQUEST_URI'];

if(isset($_GET['datestart']) && $_GET['datestart'] != "") {
    $selectedCampaigns = (array)$_GET['campaign'];
    $dateStart = $_GET['datestart'];
    $dateEnd = $_GET['dateend'];
    $showHourly = (isset($_GET['hourlyreport']) && $_GET['hourlyreport'] != "") ? true : false;
    $loadScripts = true;

    $reports = new Reports($selectedCampaigns, $dateStart, $dateEnd);
    $totalReports = $reports->totalReports();
    $reports -> checkDownload("totalcalls");

    $callHoldBreakDown = $reports->callHoldBreakdown();
    $reports -> checkDownload("callholdbreakdown");

    $callDropBreakDown = $reports->callHoldBreakdown(true);
    $reports -> checkDownload("calldropbreakdown");

    $callAnsCum = $reports->callAnsCum();
    $reports -> checkDownload("callansbreakdown");

    $hangupReasons = $reports->hangupReasons();
    $reports -> checkDownload("hangupreasons");

    $callStatusStats = $reports->getCallStatusStats();
    $reports -> checkDownload("callstatus");

    $callInitialQueue = $reports->getCallInitialQueueStats();
    $reports -> checkDownload("callqueuebreakdown");

    $agentsStatus = $reports->getAgentStats();
    $reports -> checkDownload("agentstats");

    $hourlyBreakDown = $reports->getHourlyBreakdown();
    $reports -> checkDownload("hourlyansdrop", "DROP");
    $reports -> checkDownload("callanstime");
    $reports -> checkDownload("hourlyreport", "HOURLY");
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
                        <a class="pull-left inline-block mr-15" data-toggle="collapse" href="#searchDiv" aria-expanded="true">
                            <i class="zmdi zmdi-chevron-down"></i>
                            <i class="zmdi zmdi-chevron-up"></i>
                        </a>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div  id="searchDiv" class="panel-wrapper collapse <?php if($loadScripts) echo "out"; else echo "in"; ?>">
                    <div  class="panel-body">
                        <div class="form-wrap">
                            <form action="" method="GET">
                                <div class="form-group col-md-6">
                                    <label class="control-label mb-10 text-left">Start Date</label>
                                    <div class='input-group date datetimepick'>
                                        <input name="datestart" value="<?php echo $dateStart; ?>" type='text' class="form-control" />
                                        <span class="input-group-addon">
																	<span class="fa fa-calendar"></span>
																</span>
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="control-label mb-10 text-left">End Date</label>
                                    <div class='input-group date datetimepick'>
                                        <input name="dateend" value="<?php echo $dateEnd; ?>" type='text' class="form-control" />
                                        <span class="input-group-addon">
																	<span class="fa fa-calendar"></span>
																</span>
                                    </div>
                                </div>
                                <div class="form-group col-md-12">
                                    <label class="control-label mb-10">Inbound Campaign</label>
                                    <select name="campaign[]" class="selectpicker" multiple data-style="form-control btn-default btn-outline" required>
                                        <?php

                                        $query = \PATHAODB::table("vicidial_inbound_groups");
                                        $query->select(["group_id","group_name"]);
                                        $query->where("active","=",'Y');
                                        $campaigns = $query->get();
                                        foreach($campaigns as $campaign){
                                            $selected = "";
                                            if(in_array($campaign -> group_id,$selectedCampaigns)) $selected = " selected";
                                            echo '<option value="'. $campaign -> group_id .'" '.$selected.'>'. $campaign -> group_name .'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-12">
                                    <div class="checkbox checkbox-success">
                                        <input onclick="" id="hourlyreport" name="hourlyreport" type="checkbox">
                                        <label for="hourlyreport">
                                            Show Hourly Report
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group col-md-12">
                                    <button type="submit" class="btn btn-success btn-anim"><i class="icon-rocket"></i><span class="btn-text">submit</span></button>
                                </div>
                                </form>
                        </div>


                    </div>
                </div>
            </div>
        </div>
        <!-- Table Hover -->

        <?php
        if($loadScripts){


        ?>
        <div class="col-md-12">
            <div class="panel panel-default card-view">
                <div class="panel-wrapper collapse in">
                    <div class="panel-body sm-data-box-1">
                        <div class="col-md-8">
                            <ul class="tag-list pl-15 pr-15">
                                <?php
                                foreach($selectedCampaigns as $sCampaign){
                                    echo '<li><a href="#">'.$sCampaign.'</a></li>';
                                }
                                ?>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <span class="txt-dark"><strong>Time Range:</strong> <?php echo $dateStart . " - " . $dateEnd ?></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="col-md-6">
            <div class="panel panel-default card-view">
                <div class="panel-wrapper collapse in">
                    <div class="panel-body sm-data-box-1">
                        <div class="pull-right">
                            <a href="<?php echo $request_uri; ?>&download=totalcalls" class="pull-left inline-block mr-15" data-toggle="tooltip" data-original-title="Download">
                                <i class="zmdi zmdi-download"></i>
                            </a>
                        </div>
                        <span class="uppercase-font weight-500 font-14 block text-center txt-dark">Total Calls</span>
                        <div class="cus-sat-stat weight-500 txt-success text-center mt-5">
                            <span class="counter-anim"><?php echo $totalReports['totalcalls']; ?></span><span></span>
                        </div>
                        <ul class="flex-stat mt-5">
                            <li class="half-width">
                                <span class="block">Avg. Call Length</span>
                                <span class="block txt-dark weight-500 font-15"><?php echo $totalReports['avg_length']; ?>s</span>
                            </li>
                            <li class="half-width">
                                <span class="block">Answered Calls</span>
                                <span class="block txt-dark weight-500 font-15"><?php echo $totalReports['answered'] ?>&nbsp;&nbsp;&nbsp;<span class="txt-danger"><?php echo $totalReports['answer_percent'] . "%"; ?></span></span>
                            </li>
                            <li class="half-width">
                                <span class="block">Avg. Queue Time</span>
                                <span class="block txt-dark weight-500 font-15"><?php echo $totalReports['avg_ans_queue']; ?>s</span>
                            </li>
                            <li class="half-width">
                                <span class="block">Calls Into IVR</span>
                                <span class="block txt-dark weight-500 font-15"><?php echo $totalReports['ivr_calls']; ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="panel panel-default card-view">
                <div class="panel-wrapper collapse in">
                    <div class="panel-body sm-data-box-1" style="padding-bottom: 63px;">
                        <span class="uppercase-font weight-500 font-14 block text-center txt-dark">Drop Calls</span>
                        <div class="cus-sat-stat weight-500 txt-danger text-center mt-5">
                            <span class="counter-anim"><?php echo $totalReports['totaldrop']; ?></span><span></span>
                        </div>
                        <ul class="flex-stat mt-5">
                            <li>
                                <span class="block">Drop Percent</span>
                                <span class="block txt-dark weight-500 font-15"><?php echo $totalReports['drop_percent']; ?>%</span>
                            </li>
                            <li>
                                <span class="block">Drop/Answered</span>
                                <span class="block txt-dark weight-500 font-15"><?php echo $totalReports['drop_ans_percent']; ?></span>
                            </li>
                            <li>
                                <span class="block">Avg. Hold Time</span>
                                <span class="block txt-dark weight-500 font-15"><?php echo $totalReports['avg_hold_drop']; ?>s</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-md-6">
            <div class="panel panel-default card-view">
                <div class="panel-wrapper collapse in">
                    <div class="panel-body sm-data-box-1 ">
                        <span class="uppercase-font weight-500 font-14 block text-center txt-dark">GDE (Answered/Total)</span>
                        <div class="cus-sat-stat weight-500 txt-info text-center mt-5">
                            <span class="counter-anim"><?php echo $totalReports['gde']; ?></span><span>%</span>
                        </div>
                        <ul class="flex-stat mt-5">
                            <li>
                                <span class="block">ACR (Dropped/Answered)</span>
                                <span class="block txt-dark weight-500 font-15"><?php echo $totalReports['acr']; ?>%</span>
                            </li>
                            <li>
                                <span class="block">TMA1 (Ans. <?php echo $totalReports['tma1_sec']; ?>s/Answered)</span>
                                <span class="block txt-dark weight-500 font-15"><?php echo $totalReports['tma1']; ?>%</span>
                            </li>
                            <li>
                                <span class="block">TMA2 (Ans. <?php echo $totalReports['tma2_sec']; ?>s/Answered)</span>
                                <span class="block txt-dark weight-500 font-15"><?php echo $totalReports['tma2']; ?>%</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel panel-default card-view">
                <div class="panel-wrapper collapse in">
                    <div class="panel-body sm-data-box-1 ">
                        <span class="uppercase-font weight-500 font-14 block text-center txt-dark">Calls Entered In Queue</span>
                        <div class="cus-sat-stat weight-500 txt-warning text-center mt-5">
                            <span class="counter-anim"><?php echo $totalReports['totalcall_queue']; ?></span>&nbsp;&nbsp;&nbsp;<span class="txt-primary"><?php echo $totalReports['totalcall_queue_percent']; ?>%</span>
                        </div>
                        <ul class="flex-stat mt-5">
                            <li class="half-width">
                                <span class="block">Avg. Queue Length For Queue Calls</span>
                                <span class="block txt-dark weight-500 font-15"><?php echo $totalReports['avg_length_queue']; ?>s</span>
                            </li>
                            <li class="half-width">
                                <span class="block">Avg. Queue Length For All Calls</span>
                                <span class="block txt-dark weight-500 font-15"><?php echo $totalReports['avg_length_all']; ?>s</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-md-6">
            <div class="panel panel-default card-view">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h6 class="panel-title txt-dark">Call Hold Time Breakdown</h6>
                    </div>
                    <div class="pull-right">
                        <a href="<?php echo $request_uri; ?>&download=callholdbreakdown" class="pull-left inline-block mr-15" data-toggle="tooltip" data-original-title="Download">
                        <i class="zmdi zmdi-download"></i>
                        </a>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="panel-wrapper collapse in">
                    <div id="callholdbreakdownchart" class="morris-chart"></div>
                </div>
            </div>
        </div>

            <div class="col-md-6">
                <div class="panel panel-default card-view">
                    <div class="panel-heading">
                        <div class="pull-left">
                            <h6 class="panel-title txt-dark">Call Drop Time Breakdown</h6>
                        </div>
                        <div class="pull-right">
                            <a href="<?php echo $request_uri; ?>&download=calldropbreakdown" class="pull-left inline-block mr-15" data-toggle="tooltip" data-original-title="Download">
                                <i class="zmdi zmdi-download"></i>
                            </a>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <div class="panel-wrapper collapse in">
                        <div id="calldropbreakdownchart" class="morris-chart"></div>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>

            <div class="col-md-12">
                <div class="panel panel-default card-view">
                    <div class="panel-heading">
                        <div class="pull-left">
                            <h6 class="panel-title txt-dark">Call Answered Time & Percent Breakdown</h6>
                        </div>
                        <div class="pull-right">
                            <a href="<?php echo $request_uri; ?>&download=callansbreakdown" class="pull-left inline-block mr-15" data-toggle="tooltip" data-original-title="Download">
                                <i class="zmdi zmdi-download"></i>
                            </a>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>0</th>
                                    <th>5</th>
                                    <th>10</th>
                                    <th>15</th>
                                    <th>20</th>
                                    <th>25</th>
                                    <th>30</th>
                                    <th>35</th>
                                    <th>40</th>
                                    <th>45</th>
                                    <th>50</th>
                                    <th>55</th>
                                    <th>60</th>
                                    <th>90</th>
                                    <th>+90</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach($callAnsCum as $k=>$cac){
                                    if($k == "total") continue;

                                    $total = 0;
                                    $percentString = "%";
                                    if($k == "interval"){
                                        $percentString = "";
                                        $name = "Interval";
                                        $total = $callAnsCum['total'];
                                    }
                                    if($k == "int_percent") $name = "Interval %";
                                    if($k == "cumulative"){
                                        $percentString = "";
                                        $name = "Cumulative";
                                        $total = $callAnsCum['total'];
                                    }
                                    if($k == "cumu_percent") $name = "Cumulative %";
                                    if($k == "cumu_ans_percent") $name = "Cumu Ans %";
                                    echo "<tr>";
                                    echo "<td>$name</td>";

                                    foreach($cac as $time){
                                        echo "<td>$time$percentString</td>";
                                    }

                                    echo "<td>$total</td>";

                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                        <br>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="panel panel-default card-view">
                    <div class="panel-heading">
                        <div class="pull-left">
                            <h6 class="panel-title txt-dark">Call Status Stats</h6>
                        </div>
                        <div class="pull-right">
                            <a href="<?php echo $request_uri; ?>&download=callstatus" class="pull-left inline-block mr-15" data-toggle="tooltip" data-original-title="Download">
                                <i class="zmdi zmdi-download"></i>
                            </a>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                            <tr>
                                <th>Status</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Calls</th>
                                <th>Total Time</th>
                                <th>Avg. Time</th>
                                <th>Call/Hours</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            foreach($callStatusStats['list'] as $cStats){
                                echo "<tr>";
                                echo "<td>$cStats[status]</td>";
                                echo "<td>$cStats[status_name]</td>";
                                echo "<td>$cStats[status_cat]</td>";
                                echo "<td>$cStats[status_count]</td>";
                                echo "<td>$cStats[status_hours]</td>";
                                echo "<td>$cStats[status_avg]</td>";
                                echo "<td>$cStats[status_rate]</td>";
                                echo "</tr>";
                            }
                            ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="txt-dark">Total</td>
                                    <td class="txt-dark"><?php echo $callStatusStats['total']["count"]; ?></td>
                                    <td class="txt-dark"><?php echo $callStatusStats['total']["hours"]; ?></td>
                                    <td class="txt-dark"><?php echo $callStatusStats['total']["avg"]; ?></td>
                                    <td class="txt-dark"><?php echo $callStatusStats['total']["rate"]; ?></td>
                                </tr>
                            </tfoot>
                        </table>
                        <br>
                    </div>
                </div>
            </div>

            <div class="clearfix"></div>
            <div class="col-md-6">
                <div class="panel panel-default card-view">
                    <div class="panel-heading">
                        <div class="pull-left">
                            <h6 class="panel-title txt-dark">Call Initial Queue Position Breakdown</h6>
                        </div>
                        <div class="pull-right">
                            <a href="<?php echo $request_uri; ?>&download=callqueuebreakdown" class="pull-left inline-block mr-15" data-toggle="tooltip" data-original-title="Download">
                                <i class="zmdi zmdi-download"></i>
                            </a>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <div class="panel-wrapper collapse in">
                        <div id="callqueueposition" class="morris-chart"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="panel panel-default card-view panel-refresh">
                    <div class="refresh-container">
                        <div class="la-anim-1"></div>
                    </div>
                    <div class="panel-heading">
                        <div class="pull-left">
                            <h6 class="panel-title txt-dark">Call Hangup Reason Stats</h6>
                        </div>
                        <div class="pull-right">
                            <a href="<?php echo $request_uri; ?>&download=hangupreasons" class="pull-left inline-block mr-15" data-toggle="tooltip" data-original-title="Download">
                                <i class="zmdi zmdi-download"></i>
                            </a>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <div class="panel-wrapper collapse in">
                        <div class="panel-body row">
                            <div id="hangupreasons" class="morris-chart"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="clearfix"></div>
            <div class="col-md-12">
                <div class="panel panel-default card-view">
                    <div class="panel-heading">
                        <div class="pull-left">
                            <h6 class="panel-title txt-dark">Agents Stats</h6>
                        </div>
                        <div class="pull-right">
                            <a href="<?php echo $request_uri; ?>&download=agentstats" class="pull-left inline-block mr-15" data-toggle="tooltip" data-original-title="Download">
                                <i class="zmdi zmdi-download"></i>
                            </a>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                            <tr>
                                <th>User</th>
                                <th>Name</th>
                                <th>Calls</th>
                                <th>Time</th>
                                <th>Average</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            foreach($agentsStatus['list'] as $cStats){
                                echo "<tr>";
                                echo "<td><a href='showusers.php?userid=$cStats[user]' target='_blank'>$cStats[user]</a></td>";
                                echo "<td>$cStats[name]</td>";
                                echo "<td>$cStats[call_count]</td>";
                                echo "<td>$cStats[total_length]</td>";
                                echo "<td>$cStats[avg_length]</td>";
                                echo "</tr>";
                            }
                            ?>
                            </tbody>
                            <tfoot>
                            <tr>
                                <td colspan="2" class="txt-dark">Total Agents: <?php echo $agentsStatus['total']["agents"]; ?></td>
                                <td class="txt-dark"><?php echo $agentsStatus['total']["calls"]; ?></td>
                                <td class="txt-dark"><?php echo $agentsStatus['total']["time"]; ?></td>
                                <td class="txt-dark"><?php echo $agentsStatus['total']["avg_time"]; ?></td>
                            </tr>
                            </tfoot>
                        </table>
                        <br>
                    </div>
                </div>
            </div>

            <div class="clearfix"></div>

            <div class="col-md-12">
                <div class="panel panel-default card-view panel-refresh">
                    <div class="refresh-container">
                        <div class="la-anim-1"></div>
                    </div>
                    <div class="panel-heading">
                        <div class="pull-left">
                            <h6 class="panel-title txt-dark">Hourly Answer/Drop Breakdown</h6>
                        </div>
                        <div class="pull-right">
                            <a href="<?php echo $request_uri; ?>&download=hourlyansdrop" class="pull-left inline-block mr-15" data-toggle="tooltip" data-original-title="Download">
                                <i class="zmdi zmdi-download"></i>
                            </a>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <div class="panel-wrapper collapse in">
                        <div class="panel-body row">
                            <div id="hourlydrop" class="morris-chart"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="panel panel-default card-view">
                    <div class="panel-heading">
                        <div class="pull-left">
                            <h6 class="panel-title txt-dark">Call Answered Time Breakdown in seconds</h6>
                        </div>
                        <div class="pull-right">
                            <a href="<?php echo $request_uri; ?>&download=callanstime" class="pull-left inline-block mr-15" data-toggle="tooltip" data-original-title="Download">
                                <i class="zmdi zmdi-download"></i>
                            </a>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                            <tr>
                                <th>Hour</th>
                                <th>0</th>
                                <th>5</th>
                                <th>10</th>
                                <th>15</th>
                                <th>20</th>
                                <th>25</th>
                                <th>30</th>
                                <th>35</th>
                                <th>40</th>
                                <th>45</th>
                                <th>50</th>
                                <th>55</th>
                                <th>60</th>
                                <th>90</th>
                                <th>+90</th>
                                <th>Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            foreach($hourlyBreakDown['queue'] as $hour=>$queue){
                                foreach($queue as $minute => $data){
                                    echo "<tr>";
                                    echo "<td class='text-primary'>$hour:$minute</td>";
                                    $totalQueue = 0;
                                    foreach($data as $call){
                                        $totalQueue += $call;
                                        echo "<td>$call</td>";
                                    }
                                    echo "<td class='txt-dark'>$totalQueue</td>";
                                    echo "</tr>";
                                }
                            }
                            ?>
                            </tbody>
                        </table>
                        <br>
                    </div>
                </div>
            </div>

            <?php if($showHourly) { ?>
                <div class="col-md-12">
                    <div class="panel panel-default card-view">
                        <div class="panel-heading">
                            <div class="pull-left">
                                <h6 class="panel-title txt-dark">Hourly Report [<?php echo sprintf("%02s",$reports -> startHour) . ":00 - " . sprintf("%02s",$reports -> endHour) . ":00"?>]</h6>
                            </div>
                            <div class="pull-right">
                                <a href="<?php echo $request_uri; ?>&download=hourlyreport" class="pull-left inline-block mr-15" data-toggle="tooltip" data-original-title="Download">
                                    <i class="zmdi zmdi-download"></i>
                                </a>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                        <div class="table-responsive" id="hourlyingroup">
                            <h5 class="pull-right">Multi-Group Breakdown</h5>
                            <div class="clearfix"></div>
                            <table class="table table-hover mb-0">
                                <thead>
                                <tr>
                                    <th>In Group</th>
                                    <th>Total Calls</th>
                                    <th>Total Answer</th>
                                    <th>Total Talk</th>
                                    <th>Avg. Talk</th>
                                    <th>Total Queue Time</th>
                                    <th>Avg. Queue Time</th>
                                    <th>Max Queue Time</th>
                                    <th>Total Dropped</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                foreach($hourlyBreakDown['hourly'] as $group=>$data){

                                    echo "<tr>";
                                    echo "<td class='text-primary'>$group</td>";
                                    echo "<td>" . $data["total"]["total_call"] . "</td>";
                                    echo "<td>" . $data["total"]["total_ans"] . "</td>";
                                    echo "<td>" . $data["total"]["total_time"] . "</td>";
                                    echo "<td>" . $data["total"]["talk_avg"] . "</td>";
                                    echo "<td>" . $data["total"]["total_queue"] . "</td>";
                                    echo "<td>" . $data["total"]["queue_avg"] . "</td>";
                                    echo "<td>" . $data["total"]["max_queue"] . "</td>";
                                    echo "<td>" . $data["total"]["total_abandoned"] . "</td>";
                                    echo "</tr>";
                                }
                                ?>
                                </tbody>
                            </table>
                            <br>
                        </div>

                        <?php
                        foreach($hourlyBreakDown['hourly'] as $group => $data){
                            echo '<div class="table-responsive mt-10" id="hourlyingroup">
                            <h5 class="pull-right">'.$group.' Breakdown</h5>
                            <div class="clearfix"></div>
                            <table class="table table-hover mb-0">
                                <thead>
                                <tr>
                                    <th>Hour</th>
                                    <th>Total Calls</th>
                                    <th>Total Answer</th>
                                    <th>Total Talk</th>
                                    <th>Avg. Talk</th>
                                    <th>Total Queue Time</th>
                                    <th>Avg. Queue Time</th>
                                    <th>Max Queue Time</th>
                                    <th>Total Dropped</th>
                                </tr>
                                </thead>
                                <tbody>';

                                foreach($data['hourly'] as $hour => $stat){
                                    echo "<tr>";
                                    echo "<td class='text-primary'>$hour:00</td>";
                                    echo "<td>" . $stat["total_call"] . "</td>";
                                    echo "<td>" . $stat["total_ans"] . "</td>";
                                    echo "<td>" . $stat["total_time"] . "</td>";
                                    echo "<td>" . $stat["talk_avg"] . "</td>";
                                    echo "<td>" . $stat["total_queue"] . "</td>";
                                    echo "<td>" . $stat["queue_avg"] . "</td>";
                                    echo "<td>" . $stat["max_queue"] . "</td>";
                                    echo "<td>" . $stat["total_abandoned"] . "</td>";
                                    echo "</tr>";
                                }
                        echo   '</tbody>
                                <tfoot>';

                            echo "<tr>";
                            echo "<td class='txt-dark'>Total</td>";
                            echo "<td class='txt-dark'>" . $data['total']["total_call"] . "</td>";
                            echo "<td class='txt-dark'>" . $data['total']["total_ans"] . "</td>";
                            echo "<td class='txt-dark'>" . $data['total']["total_time"] . "</td>";
                            echo "<td class='txt-dark'>" . $data['total']["talk_avg"] . "</td>";
                            echo "<td class='txt-dark'>" . $data['total']["total_queue"] . "</td>";
                            echo "<td class='txt-dark'>" . $data['total']["queue_avg"] . "</td>";
                            echo "<td class='txt-dark'>" . $data['total']["max_queue"] . "</td>";
                            echo "<td class='txt-dark'>" . $data['total']["total_abandoned"] . "</td>";
                            echo "</tr>";

                        echo    '</tfoot>
                            </table>
                            <br>
                        </div>';
                        }
                        ?>

                    </div>
                </div>

        <?php
            }

        } ?>
    </div>
</div>


<?php
require_once "footer.php";
?>
<!-- Morris Charts JavaScript -->
<script src="vendors/bower_components/raphael/raphael.min.js"></script>
<script src="vendors/bower_components/morris.js/morris.min.js"></script>

<script>
    $('.datetimepick').datetimepicker({
        format: 'YYYY-MM-DD HH:mm:ss',
        icons: {
            time: "fa fa-clock-o",
            date: "fa fa-calendar",
            up: "fa fa-arrow-up",
            down: "fa fa-arrow-down"
        },
    }).on('dp.show', function() {
        if($(this).data("DateTimePicker").date() === null)
            $(this).data("DateTimePicker").date(moment());
    });


    <?php if($loadScripts){ ?>
    $("#searchDiv").collapse('hide');

    // Hold
    Morris.Line({
        // ID of the element in which to draw the chart.
        element: 'callholdbreakdownchart',
        // Chart data records -- each entry in this array corresponds to a point on
        // the chart.
        data: [
            <?php
            foreach($callHoldBreakDown['list'] as $k=>$val){
                if($k == 99) $k = "+90";
                echo "{
                            seconds: '$k',
                            calls: $val
                        },";
            }
            ?>

        ],
        // The name of the data record attribute that contains x-visitss.
        xkey: 'seconds',
        // A list of names of data record attributes that contain y-visitss.
        ykeys: ['calls'],
        // Labels for the ykeys -- will be displayed when you hover over the
        // chart.
        labels: ['Hold Time'],
        // Disables line smoothing
        pointSize: 1,
        pointStrokeColors:['#177ec1'],
        behaveLikeLine: true,
        gridLineColor: '#878787',
        gridTextColor:'#878787',
        lineWidth: 2,
        smooth: true,
        hideHover: 'auto',
        lineColors: ['#177ec1'],
        resize: true,
        parseTime: false,
        gridTextFamily:"Roboto"
    });

    //Drop
    Morris.Line({
        // ID of the element in which to draw the chart.
        element: 'calldropbreakdownchart',
        // Chart data records -- each entry in this array corresponds to a point on
        // the chart.
        data: [
            <?php
            foreach($callDropBreakDown['list'] as $k=>$val){
                if($k == 99) $k = "+90";
                echo "{
                            seconds: '$k',
                            calls: $val
                        },";
            }
            ?>

        ],
        // The name of the data record attribute that contains x-visitss.
        xkey: 'seconds',
        // A list of names of data record attributes that contain y-visitss.
        ykeys: ['calls'],
        // Labels for the ykeys -- will be displayed when you hover over the
        // chart.
        labels: ['Hold Time'],
        // Disables line smoothing
        pointSize: 1,
        pointStrokeColors:['#c1000f'],
        behaveLikeLine: true,
        gridLineColor: '#878787',
        gridTextColor:'#878787',
        lineWidth: 2,
        smooth: true,
        hideHover: 'auto',
        lineColors: ['#c13b28'],
        resize: true,
        parseTime: false,
        gridTextFamily:"Roboto"
    });

    Morris.Area({
        element: 'callqueueposition',
        data: [
            <?php
            foreach($callInitialQueue as $k=>$val){
                echo "{
                            position: '$k',
                            count: $val
                        },";
            }
            ?>
        ],
        xkey: 'position',
        ykeys: ['count'],
        labels: ['Calls'],
        pointSize: 0,
        pointStrokeColors:['#177ec1'],
        behaveLikeLine: true,
        gridLineColor: '#878787',
        lineWidth: 0,
        smooth: true,
        hideHover: 'auto',
        lineColors: ['#177ec1'],
        resize: true,
        parseTime: false,
        gridTextColor:'#878787',
        gridTextFamily:"Roboto",
    });

    //hangup reasons
    Morris.Bar({
        element: 'hangupreasons',
        data: [
            <?php
            foreach($hangupReasons['list'] as $k=>$val){
                echo "{
                            reason: '$val[reason]',
                            count: $val[count]
                        },";
            }
            ?>
        ],
        xkey: 'reason',
        ykeys: ['count'],
        labels: ['Count'],
        barRatio: 0.4,
        xLabelMargin: 10,
        pointSize: 1,
        pointStrokeColors:['#dc4666'],
        behaveLikeLine: true,
        gridLineColor: '#878787',
        gridTextColor:'#878787',
        hideHover: 'auto',
        barColors: ['#dc4666'],
        resize: true,
        parseTime: false,
        gridTextFamily:"Roboto"
    });

    //hourly drop
    Morris.Area({
        element: 'hourlydrop',
        data: [
            <?php
                foreach($hourlyBreakDown['all'] as $hour => $hourlyData){
                    foreach($hourlyData as $minute => $minutesData){
                        echo "{
                            time: '$hour:$minute',
                            calls: $minutesData,
                            dropped: ".$hourlyBreakDown['drop'][$hour][$minute]."
                        },";
                    }
                }
            ?>
        ],
        xkey: 'time',
        ykeys: ['calls', 'dropped'],
        labels: ['Received Calls', 'Dropped Calls'],
        pointSize: 2,
        pointStrokeColors:['#00c14b', '#dc2400',],
        behaveLikeLine: true,
        gridLineColor: '#878787',
        lineWidth: 0,
        smooth: true,
        hideHover: 'auto',
        lineColors: ['#00c14b', '#dc2400'],
        resize: true,
        parseTime: false,
        gridTextColor:'#878787',
        gridTextFamily:"Roboto",
    });

    <?php } ?>
</script>