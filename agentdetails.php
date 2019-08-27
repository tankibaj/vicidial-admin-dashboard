<?php
/**
 * Created by PhpStorm.
 * User: elomelo
 * Date: 24/4/18
 * Time: 5:07 PM
 */

require_once "includes/common.php";
require_once "includes/Details.php";

use \Common\Details;

if(!$auth ->checkSession()){
    header("Location: login.php");
    die();
}

$page = "Agents Performance Details";
$parent = "report";
$request_uri = $_SERVER['REQUEST_URI'];
$showTable = false;

if(isset($_GET['daterange']) && $_GET['daterange'] != ""){
    $dateRange = explode(" - ", $_GET['daterange']);
    $selectedCampaign = (array)$_GET['campaign'];
    $agentData = new Details($selectedCampaign, $dateRange[0],$dateRange[1]);
    $agentData -> listData();
    $showTable = true;
    if(isset($_GET['download']) && $_GET['download'] == "callstatus"){
        $output = $agentData -> downloadHeader();
        setOutput($output,["Call Status Breakdown: (Statistics related to handling of calls only)"]);
        setOutput($output,['Username','AgentID','Login Time','Logout Time','Total Time','Active Time','Calls','Pause','Wait','Talk','Dispo']);
        foreach($agentData -> users as $user => $data){
            setOutput($output,[$data["name"],$user,$data["login_time"],$data["logout_time"],$data["total_sec"],$data["sum_total"],$data["calls"],
                $data["pause_sec"],$data["wait_sec"],$data["talk_sec"],$data["talk_sec"]]);
        }
        $output .= "\n\n";

        setOutput($output,["Pause Code Breakdown:"]);
        $headerArr = ['Username','AgentID','Total','NonPause','Pause'];
        foreach($agentData -> availStatus as $status){
            if($status == "") $status = "N/A";
            $headerArr[] = $status;
        }
        setOutput($output,$headerArr);
        foreach($agentData -> users as $user => $data){
            $bodyArr = [$data["name"],$user,$data["total_sec"],$data["sum_total"],$data["pause_sec"]];
            foreach($agentData -> availStatus as $status){
                if(isset($data['pausecode'][$status])){
                    $bodyArr[] = $data['pausecode'][$status];
                }else{
                    $bodyArr[] = "00:00:00";
                }
            }
            setOutput($output, $bodyArr);
        }
        exportDownload($output,"AgentPerformance");
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
                                <div class="form-group">
                                    <label class="control-label mb-10 text-left">Date Range</label>
                                    <input class="form-control input-daterange-datepicker" type="text" name="daterange" value="" required/>
                                </div>
                                <div class="form-group">
                                    <label class="control-label mb-10">Campaign</label>
                                    <select name="campaign[]" class="selectpicker" multiple data-style="form-control btn-default btn-outline" required>
                                        <?php
                                        $campaigns = getCampaign();
                                        echo '<option value="ALL">All</option>';
                                        foreach($campaigns as $campaign){
                                            echo '<option value="'. $campaign -> campaign_id .'">'. $campaign -> campaign_name .'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success btn-anim"><i class="icon-rocket"></i><span class="btn-text">submit</span></button>
                            </form>
                        </div>


                    </div>
                </div>
            </div>
        </div>
        <?php
        if($showTable) {
            ?>
        <div class="col-md-12">
            <div class="panel panel-default card-view">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h6 class="panel-title txt-dark">Call Status Breakdown: (Statistics related to handling of calls only) </h6>
                    </div>
                    <div class="pull-right">
                        <a href="<?php echo $request_uri; ?>&download=callstatus" class="pull-left inline-block mr-15" data-toggle="tooltip" data-original-title="Download">
                            <i class="zmdi zmdi-download"></i>
                        </a>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="table-responsive" id="hourlyingroup">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>Username</th>
                            <th>AgentID</th>
                            <th>Login Time</th>
                            <th>Logout Time</th>
                            <th>Total Time</th>
                            <th>Active Time</th>
                            <th>Calls</th>
                            <th>Pause</th>
                            <th>Wait</th>
                            <th>Talk</th>
                            <th>Dispo</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach($agentData -> users as $user => $data){

                            echo "<tr>";

                            echo "<td class='text-primary'><a href='showusers.php?userid=$user' target='_blank'>$data[name]</a></td>";
                            echo "<td>" . $user . "</td>";

                            echo "<td>" . $data["login_time"] . "</td>";
                            echo "<td>" . $data["logout_time"] . "</td>";
                            echo "<td>" . $data["total_sec"] . "</td>";
                            echo "<td>" . $data["sum_total"] . "</td>";
                            echo "<td>" . $data["calls"] . "</td>";
                            echo "<td>" . $data["pause_sec"] . "</td>";
                            echo "<td>" . $data["wait_sec"] . "</td>";
                            echo "<td>" . $data["talk_sec"] . "</td>";
                            echo "<td>" . $data["talk_sec"] . "</td>";
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
                        <h6 class="panel-title txt-dark">Pause Code Breakdown</h6>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="table-responsive" id="hourlyingroup">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>Username</th>
                            <th>AgentID</th>
                            <th>Total</th>
                            <th>NonPause</th>
                            <th>Pause</th>
                            <?php
                            foreach($agentData -> availStatus as $status){
                                if($status == "") $status = "N/A";
                                echo "<th>$status</th>";
                            }
                            ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach($agentData -> users as $user => $data){

                            echo "<tr>";
                            echo "<td class='text-primary'><a href='showusers.php?userid=$user' target='_blank'>$data[name]</a></td>";
                            echo "<td>" . $user . "</td>";
                            echo "<td>" . $data["total_sec"] . "</td>";
                            echo "<td>" . $data["sum_total"] . "</td>";
                            echo "<td>" . $data["pause_sec"] . "</td>";

                            foreach($agentData -> availStatus as $status){
                                if(isset($data['pausecode'][$status])){
                                    echo "<td>".$data['pausecode'][$status]."</td>";
                                }else{
                                    echo "<td>00:00:00</td>";
                                }
                            }

                            echo "</tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                    <br>
                </div>
            </div>
        </div>

        <?php } ?>


<?php
require_once "footer.php";
?>

