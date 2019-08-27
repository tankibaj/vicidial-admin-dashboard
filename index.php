<?php
/**
 * Created by PhpStorm.
 * User: elomelo
 * Date: 24/4/18
 * Time: 5:07 PM
 */


require_once "includes/common.php";
require_once "includes/Realtime.php";

use Common\Realtime;

if(!$auth ->checkSession()){
    header("Location: login.php");
    die();
}


if(isset($_GET['json']) && $_GET['json'] == "1"){




    $isInboundOnly = $_GET['inbound'];
    $selectedCampaigns = (array) $_GET['campaign'];
    $selectedGroup = (array) $_GET['usergroup'];
    $phoneID = $_GET['phone'];


    $realtime = new Realtime($isInboundOnly,$phoneID,$selectedCampaigns,$selectedGroup);
    $returnData = $realtime -> miniStats();
    echo json_encode($returnData);
    die();

}
if(isset($_POST['monitor'])) {
    $startMS = microtime();
    $StarTtime = date("U");
    $NOW_DATE = date("Y-m-d");
    $NOW_TIME = date("Y-m-d H:i:s");

    $session_id = trim($_POST['session_id']);
    $server_ip = trim($_POST['server_ip']);
    $stage = trim($_POST['stage']);
    $phone_login = $_POST['phone'];
    $query = \PATHAODB::table("vicidial_conferences");
    $query->select(\PATHAODB::raw("count(*) as totalCount"));
    $query->where("conf_exten", "=", $session_id);
    $query->where("server_ip", "=", $server_ip);
    $result = $query->first();

    if ($result->totalCount < 1) {
        $result = 'ERROR';
        $result_reason = "blind_monitor INVALID SESSION ID";
        echo "$result: $result_reason - $session_id|$server_ip\n";
        die();
    } else {
        $query = \PATHAODB::table("phones");
        $query->select(["dialplan_number","server_ip","outbound_cid"]);
        $query->where("login", "=", $phone_login);
        $result = $query->first();

        if (empty($result -> dialplan_number)) {
            $result = 'ERROR';
            $result_reason = "blind_monitor INVALID PHONE LOGIN";
            echo "$result: $result_reason - $phone_login\n";
            $data = "$phone_login";
            die();
        } else {
            $dialplan_number = $result -> dialplan_number;
            $monitor_server_ip = $result -> server_ip;
            $outbound_cid = $result -> outbound_cid;

            $S = '*';
            $D_s_ip = explode('.', $server_ip);
            if (strlen($D_s_ip[0]) < 2) {
                $D_s_ip[0] = "0$D_s_ip[0]";
            }
            if (strlen($D_s_ip[0]) < 3) {
                $D_s_ip[0] = "0$D_s_ip[0]";
            }
            if (strlen($D_s_ip[1]) < 2) {
                $D_s_ip[1] = "0$D_s_ip[1]";
            }
            if (strlen($D_s_ip[1]) < 3) {
                $D_s_ip[1] = "0$D_s_ip[1]";
            }
            if (strlen($D_s_ip[2]) < 2) {
                $D_s_ip[2] = "0$D_s_ip[2]";
            }
            if (strlen($D_s_ip[2]) < 3) {
                $D_s_ip[2] = "0$D_s_ip[2]";
            }
            if (strlen($D_s_ip[3]) < 2) {
                $D_s_ip[3] = "0$D_s_ip[3]";
            }
            if (strlen($D_s_ip[3]) < 3) {
                $D_s_ip[3] = "0$D_s_ip[3]";
            }
            $monitor_dialstring = "$D_s_ip[0]$S$D_s_ip[1]$S$D_s_ip[2]$S".sprintf("%02s",$D_s_ip[3])."$S";

            $user = "abcdef";
            $PADuser = sprintf("%08s", $user);
            while (strlen($PADuser) > 8) {
                $PADuser = substr("$PADuser", 0, -1);
            }
            $BMquery = "BM$StarTtime$PADuser";

            if ((preg_match('/MONITOR/', $stage)) or (strlen($stage) < 1)) {
                $stage = '0';
            }
            if (preg_match('/BARGE/', $stage)) {
                $stage = '';
            }
            if (preg_match('/HIJACK/', $stage)) {
                $stage = '';
            }
            if (preg_match('/WHISPER/', $stage)) {
                if ($agent_whisper_enabled == '1') {
                    $stage = '47378218';
                } else {
                    # WHISPER not enabled
                    $stage = '0';
                }
            }
            ### insert a new lead in the system with this phone number
            $data = [
                "entry_date" => $NOW_TIME,
                "status" => "NEW",
                "response" => "N",
                "server_ip" => $monitor_server_ip,
                "channel" => "",
                "action" => "Originate",
                "callerid" => $BMquery,
                "cmd_line_b" => "Channel: Local/$monitor_dialstring$stage$session_id@default",
                "cmd_line_c" => "Context: default",
                "cmd_line_d" => "Exten: $dialplan_number",
                "cmd_line_e" => "Priority: 1",
                "cmd_line_f" => "Callerid: \"VC Blind Monitor\" <$outbound_cid>",
                "cmd_line_g" => "",
                "cmd_line_h" => "",
                "cmd_line_i" => "",
                "cmd_line_j" => "",
                "cmd_line_k" => ""];
            $insertIds = \PATHAODB::table('vicidial_manager')->insert($data);
            if ($insertIds > 0) {
                $man_id = $insertIds;
                $data = ["caller_code" => "$BMquery",
                    "lead_id" => "0",
                    "server_ip" => "$monitor_server_ip",
                    "call_date" => "$NOW_TIME",
                    "extension" => "$dialplan_number",
                    "channel" => "Local/$monitor_dialstring$stage$session_id@default",
                    "timeout" => "0",
                    "outbound_cid" => "\"VC Blind Monitor\" <$outbound_cid>",
                    "context" => "default",];
                \PATHAODB::table('vicidial_dial_log')->insert($data);
                ##### BEGIN log visit to the vicidial_report_log table #####
                $endMS = microtime();
                $startMSary = explode(" ", $startMS);
                $endMSary = explode(" ", $endMS);
                $runS = ($endMSary[0] - $startMSary[0]);
                $runM = ($endMSary[1] - $startMSary[1]);
                $TOTALrun = ($runS + $runM);
                $result = 'SUCCESS';
                $result_reason = "blind_monitor HAS BEEN LAUNCHED";
                echo "$result: $result_reason - $phone_login|$monitor_dialstring$stage$session_id|$dialplan_number|$session_id|$man_id|$user\n";
                die();
            }
        }
    }
}
$page = "Real-Time";
$parent = "report";

require_once "header.php";


?>

<div class="container-fluid pt-25">
    <!-- Block Row -->
    <div class="row">
        <div class="col-md-2">
            <div class="panel panel-default card-view">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h6 class="panel-title txt-dark">Call Status</h6>
                    </div>
                    <div class="pull-right">
                        <a href="#"  data-toggle="modal" data-target="#setting-modal" class="pull-left inline-block">
                            <i class="zmdi zmdi-settings"></i>
                        </a>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="panel-wrapper collapse in">
                    <div class="panel-body row">
                        <div class="">
                            <div class="pl-15 pr-15 mb-15">
                                <div class="pull-left">
                                    <i class="zmdi zmdi-phone-in-talk inline-block mr-10 font-16"></i>
                                    <span class="inline-block txt-dark">Incoming Calls</span>
                                </div>
                                <span class="inline-block txt-primary pull-right weight-500" id="box_active_calls">...</span>
                                <div class="clearfix"></div>
                            </div>
                            <hr class="light-grey-hr mt-0 mb-15"/>
                            <div class="pl-15 pr-15 mb-15">
                                <div class="pull-left">
                                    <i class="zmdi zmdi-phone-ring inline-block mr-10 font-16"></i>
                                    <span class="inline-block txt-dark">Calls ringing</span>
                                </div>
                                <span class="inline-block txt-primary pull-right weight-500" id="box_call_ringing">...</span>
                                <div class="clearfix"></div>
                            </div>
                            <hr class="light-grey-hr mt-0 mb-15"/>
                            <div class="pl-15 pr-15 mb-15">
                                <div class="pull-left">
                                    <i class="zmdi zmdi-phone-in-talk inline-block mr-10 font-16"></i>
                                    <span class="inline-block txt-dark">Calls waiting for agents</span>
                                </div>
                                <span class="inline-block txt-primary pull-right weight-500" id="box_call_waiting">...</span>
                                <div class="clearfix"></div>
                            </div>
                            <hr class="light-grey-hr mt-0 mb-15"/>
                            <div class="pl-15 pr-15 mb-15">
                                <div class="pull-left">
                                    <i class="zmdi zmdi-airplanemode-active inline-block mr-10 font-16"></i>
                                    <span class="inline-block txt-dark">Calls in IVR</span>
                                </div>
                                <span class="inline-block txt-primary pull-right weight-500" id="box_call_ivr">...</span>
                                <div class="clearfix"></div>
                            </div>

                            <hr class="light-grey-hr mt-0 mb-15"/>
                            <div class="pl-15 pr-15 mb-15">
                                <div class="pull-left">
                                    <i class="zmdi zmdi-male inline-block mr-10 font-16"></i>
                                    <span class="inline-block txt-dark">Agents logged in</span>
                                </div>
                                <span class="inline-block txt-primary pull-right weight-500" id="box_total_agents">...</span>
                                <div class="clearfix"></div>
                            </div>
                            <hr class="light-grey-hr mt-0 mb-15"/>
                            <div class="pl-15 pr-15 mb-15">
                                <div class="pull-left">
                                    <i class="zmdi zmdi-accounts inline-block mr-10 font-16"></i>
                                    <span class="inline-block txt-dark">Agents In Call</span>
                                </div>
                                <span class="inline-block txt-primary pull-right weight-500" id="box_agents_in_call">...</span>
                                <div class="clearfix"></div>
                            </div>
                            <hr class="light-grey-hr mt-0 mb-15"/>
                            <div class="pl-15 pr-15 mb-15">
                                <div class="pull-left">
                                    <i class="zmdi zmdi-accounts-list inline-block mr-10 font-16"></i>
                                    <span class="inline-block txt-dark">Agents Waiting</span>
                                </div>
                                <span class="inline-block txt-primary pull-right weight-500" id="box_agents_waiting">...</span>
                                <div class="clearfix"></div>
                            </div>

                            <hr class="light-grey-hr mt-0 mb-15"/>
                            <div class="pl-15 pr-15 mb-15">
                                <div class="pull-left">
                                    <i class="zmdi zmdi-phone-paused inline-block mr-10 font-16"></i>
                                    <span class="inline-block txt-dark">Paused Agents</span>
                                </div>
                                <span class="inline-block txt-primary pull-right weight-500" id="box_agents_paused">...</span>
                                <div class="clearfix"></div>
                            </div>
                            <hr class="light-grey-hr mt-0 mb-15"/>
                            <div class="pl-15 pr-15 mb-15">
                                <div class="pull-left">
                                    <i class="zmdi zmdi-pin-drop inline-block mr-10 font-16"></i>
                                    <span class="inline-block txt-dark">Agents dead in call</span>
                                </div>
                                <span class="inline-block txt-primary pull-right weight-500" id="box_agents_dead">...</span>
                                <div class="clearfix"></div>
                            </div>
                            <hr class="light-grey-hr mt-0 mb-15"/>
                            <div class="pl-15 pr-15 mb-15">
                                <div class="pull-left">
                                    <i class="zmdi zmdi-assignment-alert inline-block mr-10 font-16"></i>
                                    <span class="inline-block txt-dark">Agents in dispo</span>
                                </div>
                                <span class="inline-block txt-primary pull-right weight-500" id="box_agents_dispo">...</span>
                                <div class="clearfix"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-10">
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                <div class="panel panel-default card-view pa-0 ">
                    <div class="panel-wrapper collapse in">
                        <div class="panel-body pa-0">
                            <div class="sm-data-box bg-primary">
                                <div class="container-fluid">
                                    <div class="row">
                                        <div class="col-xs-6 text-center pl-0 pr-0 data-wrap-left">
                                            <span class="txt-light block counter"><span id="widget_call_today">00</span></span>
                                            <span class="weight-500 uppercase-font txt-light block font-13">Calls Today</span>
                                        </div>
                                        <div class="col-xs-6 text-center  pl-0 pr-0 data-wrap-right">
                                            <i class="zmdi zmdi-phone-sip txt-light data-right-rep-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                <div class="panel panel-default card-view pa-0">
                    <div class="panel-wrapper collapse in">
                        <div class="panel-body pa-0">
                            <div class="sm-data-box bg-green">
                                <div class="container-fluid">
                                    <div class="row">
                                        <div class="col-xs-6 text-center pl-0 pr-0 data-wrap-left">
                                            <span class="txt-light block counter"><span id="widget_answered">00</span></span>
                                            <span class="weight-500 uppercase-font txt-light block font-13">Answered</span>
                                        </div>
                                        <div class="col-xs-6 text-center  pl-0 pr-0 data-wrap-right">
                                            <i class="zmdi zmdi-phone-in-talk txt-light data-right-rep-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                <div class="panel panel-default card-view pa-0">
                    <div class="panel-wrapper collapse in">
                        <div class="panel-body pa-0">
                            <div class="sm-data-box bg-red">
                                <div class="container-fluid">
                                    <div class="row">
                                        <div class="col-xs-6 text-center pl-0 pr-0 data-wrap-left">
                                            <span class="txt-light block counter"><span id="widget_dropped">00</span>
                                                 / <span style="color: #fff7fb;"><span id="widget_drop_percent">00</span>%</span>
                                            </span>
                                            <span class="weight-500 uppercase-font txt-light block font-13">Dropped</span>
                                        </div>
                                        <div class="col-xs-6 text-center  pl-0 pr-0 data-wrap-right">
                                            <i class="zmdi zmdi-trending-down txt-light data-right-rep-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                <div class="panel panel-default card-view pa-0">
                    <div class="panel-wrapper collapse in">
                        <div class="panel-body pa-0">
                            <div class="sm-data-box" style="background-color: #760cc4;">
                                <div class="container-fluid">
                                    <div class="row">
                                        <div class="col-xs-6 text-center pl-0 pr-0 data-wrap-left">
                                            <span class="txt-light block counter"><span id="widget_outbound_today">00</span></span>
                                            <span class="weight-500 uppercase-font txt-light block font-13">Outgoing</span>
                                        </div>
                                        <div class="col-xs-6 text-center  pl-0 pr-0 data-wrap-right">
                                            <i class="fa fa-phone txt-light data-right-rep-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="setting-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                            <h5 class="modal-title">Settings</h5>
                        </div>
                        <div class="modal-body">
                            <form id="settings_form" action="#">
                                <div class="form-group">
                                    <label class="control-label mb-10">Campaign</label>
                                    <select id="settings_campaign" name="campaign[]" class="selectpicker" multiple data-style="form-control btn-default btn-outline">
                                        <?php
                                        $query = \PATHAODB::table("vicidial_inbound_groups");
                                        $query->select(["group_id","group_name"]);
                                        $query->where("active","=",'Y');
                                        $campaigns = $query->get();
                                        echo '<option value="ALL" selected>All</option>';
                                        foreach($campaigns as $campaign){
                                            echo '<option value="'. $campaign -> group_id .'">'. $campaign -> group_name .'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="control-label mb-10">User Group</label>
                                    <select id="settings_usergroup" name="usergroup[]" class="selectpicker" multiple data-style="form-control btn-default btn-outline">
                                        <?php
                                        $query = \PATHAODB::table("vicidial_user_groups");
                                        $query->select(["user_group","group_name"]);
                                        $query->orderBy("user_group","ASC");
                                        $usergroups = $query->get();
                                        echo '<option value="ALL" selected>All</option>';
                                        foreach($usergroups as $ugroup){
                                            $arr = json_decode($ugroup, true);
                                            echo '<option value="'. $ugroup -> user_group .'">'. $ugroup -> group_name .'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="control-label mb-10">Inbound</label>
                                    <select id="settings_inbound" name="inbound" class="selectpicker" data-style="form-control btn-default btn-outline">
                                        <option value="YES" selected>Yes</option>
                                        <option value="ONLY" >Only</option>
                                        <option value="NO" >No</option>

                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="control-label mb-10" for="settings_phone">Phone</label>
                                    <div class="input-group">
                                        <div class="input-group-addon"><i class="icon-phone"></i></div>
                                        <input type="text" class="form-control" id="settings_phone" name="phone" value="<?php echo $auth -> adminPhone(); ?>" placeholder="Phone">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label mb-10">Refresh Interval</label>
                                    <input class="touchspin" id="settings_refresh_interval" type="text" value="30" name="refresh_interval" data-bts-button-down-class="btn btn-default" data-bts-button-up-class="btn btn-default">
                                </div>

                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-danger" id="settings_save" data-dismiss="modal">Save changes</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="waitingTable col-md-12">
                <div class="panel panel-default card-view">
                    <div class="panel-heading">
                        <div class="pull-left">
                            <h6 class="panel-title txt-dark">Call Waiting</h6>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <div class="panel-wrapper collapse in">
                        <div class="panel-body">
                            <div class="table-wrap">
                                <div class="table-responsive">
                                    <table id="waittable" class="table table-hover mb-0">
                                        <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th>Campaign</th>
                                            <th>Phone</th>
                                            <th>Dial Time</th>
                                            <th>Call Type</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td>...</td>
                                            <td>...</td>
                                            <td>...</td>
                                            <td>...</td>
                                            <td>...</td>
                                        </tr>

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="panel panel-default card-view">
                    <div class="panel-heading">
                        <div class="pull-left">
                            <h6 class="panel-title txt-dark">Agents</h6>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <div class="panel-wrapper collapse in">
                        <div class="panel-body">
                            <div class="table-wrap">
                                <div class="table-responsive">
                                    <table id="agenttable" class="table table-hover mb-0">
                                        <thead>
                                        <tr>
                                            <th>Station</th>
                                            <th>Phone</th>
                                            <th>User</th>
                                            <th>Group</th>
                                            <th>SessionID</th>
                                            <th>Status</th>
                                            <th>CustPhone</th>
                                            <th>MM:SS</th>
                                            <th>Campaign</th>
                                            <th>Calls</th>
                                            <th>Call Type</th>
                                            <th>Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td>...</td>
                                            <td>...</td>
                                            <td>...</td>
                                            <td>...</td>
                                            <td>...</td>
                                            <td>...</td>
                                            <td>...</td>
                                            <td>...</td>
                                            <td>...</td>
                                            <td>...</td>
                                            <td>...</td>
                                            <td>...</td>
                                        </tr>

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Row -->
</div>


<?php
require_once "footer.php";
?>

<script>

    var timeoutHandle = null;

    function send_monitor(session_id,server_ip,stage)
    {
        var monitor_phone = $("#settings_phone").val();
        var xmlhttp=false;

        if (!xmlhttp && typeof XMLHttpRequest!='undefined')
        {
            xmlhttp = new XMLHttpRequest();
        }
        if (xmlhttp)
        {
            var monitorQuery = "monitor=1&phone=" + monitor_phone + "&session_id=" + session_id + '&server_ip=' + server_ip + '&stage=' + stage;
            xmlhttp.open('POST', 'index.php');
            xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
            xmlhttp.send(monitorQuery);
            xmlhttp.onreadystatechange = function()
            {
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
                {
                    //	alert(xmlhttp.responseText);
                    var Xoutput = null;
                    Xoutput = xmlhttp.responseText;
                    var regXFerr = new RegExp("ERROR:","g");
                    var regXFscs = new RegExp("SUCCESS:","g");
                    if (Xoutput.match(regXFerr))
                    {alert(xmlhttp.responseText);
                        $.toast({
                            heading: 'Opps! somthing wents wrong',
                            text: xmlhttp.responseText,
                            position: 'top-right',
                            loaderBg:'#e69a2a',
                            icon: 'error',
                            hideAfter: 3500
                        });
                    }
                    if (Xoutput.match(regXFscs))
                    {
                        $.toast({
                            heading: 'Monitor',
                            text: 'Calling ' + monitor_phone,
                            position: 'top-right',
                            loaderBg:'#e69a2a',
                            icon: 'success',
                            hideAfter: 3500,
                            stack: 6
                        });
                    }
                }
            }
            delete xmlhttp;
        }
    }

    function realTime(){
        var refreshInterval = $("#settings_refresh_interval").val();
        $.getJSON('index.php?json=1&' + $("#settings_form").serialize(), function(data)
        {
            if(data.callstatus !== undefined){
                var call = data.callstatus;
                $("#box_active_calls").html(call.active_calls);
                $("#box_call_ringing").html(call.call_ringing);
                $("#box_call_waiting").html(call.call_waiting);
                $("#box_call_ivr").html(call.call_ivr);
                $("#box_total_agents").html(call.total_agents);
                $("#box_agents_in_call").html(call.agents_in_call);
                $("#box_agents_waiting").html(call.agents_waiting);
                $("#box_agents_paused").html(call.agents_paused);
                $("#box_agents_dead").html(call.agents_dead);
                $("#box_agents_dispo").html(call.agents_dispo);
            }

            var stats = data.stats;
            $("#widget_call_today").text(stats.calls_today);
            $("#widget_answered").text(stats.answers_today);
            $("#widget_dropped").text(stats.drops_today);
            $("#widget_drop_percent").text(stats.drop_percent);
            $("#widget_outbound_today").text(stats.outbound_today);

            ///set agents table
           dTable.clear();
            $.each(data.agents, function(i, item) {
                var statusSpan = "<span class='label' style='background-color: rgba(7,5,30,0.87);'>" + item.status + "</span>";
                var actionSpan = "";
                var calltype = "";
                var custPhone = "";
                var callCount = "<span class='label' style='background-color: rgba(35,37,133,0.87);'>" + item.calls_today + "</span>";
                var agentID = "<span style='cursor: pointer;' data-txt='" + item.full_name + "' onclick='infoToggle(this);' class='label label-primary'>" + item.user + "</span>";
                var userGroup = "<span class='label' style='background-color: #ff606c; color: white;'>" + item.user_group + "</span>";

                if(item.status === "INCALL"){
                    statusSpan = "<span class='a label' style='background-color: rgb(0,104,97);'> " + item.status + "</span>";
                    actionSpan = "<a href='#' onClick='send_monitor(\"" + item.sessionid + "\",\"" + item.serverip + "\",\"MONITOR\");'><i class='fa txt-primary fa-play'></i></a>";
                    if(item.call_type === 0){
                        calltype = "<span class='label label-primary'>Outgoing</span>";
                    }else{
                        calltype = "<span class='label label-success'>Incoming</span>";
                    }
                }
                if(item.status === "3-WAY"){
                    actionSpan = "<a href='#' onClick='send_monitor(\"" + item.sessionid + "\",\"" + item.serverip + "\",\"MONITOR\");'><i class='fa txt-primary fa-play'></i></a>";
                }

                if(item.cust_phone !== ""){
                    custPhone = "<span class='label label-info'>" + item.cust_phone + "</span>";
                }

                if(item.status === "PAUSED"){
                    statusSpan = "<span class='a label label-danger'> " + item.status + "</span>";
                    if(item.pausecode !== null){
                        statusSpan += "&nbsp;<span class='a label label-info'> " + item.pausecode + "</span>";
                    }
                }

                //$(".itooltip").tooltip();

                dTable.rows.add(
                    [[ item.extension,
                        item.phone,
                        agentID,
                        userGroup,
                        item.sessionid,
                        statusSpan,
                        custPhone,
                        item.call_time,
                        item.campaign_id,
                        callCount,
                        calltype,
                        actionSpan]]
                ).draw();
            });

            wTable.clear();
            if(data.waiting.length > 0) {
                $(".waitingTable").show();
            }else{
                $(".waitingTable").hide();
            }
            $.each(data.waiting, function (i, item){
                var calltype = '<span class=\'label label-danger\'>OUT</span>';
                if(item.call_type === 'IN'){
                    calltype = '<span class=\'label label-success\'>IN</span>';
                }
                wTable.rows.add(
                    [[ item.status,
                        item.campaign,
                        item.phone,
                        item.dialtime,
                        calltype,
                        ]]
                ).draw();
            });
        })
            .always(function(){
                if(refreshInterval === $("#settings_refresh_interval").val())
                    timeoutHandle = setTimeout(realTime,refreshInterval * 1000);
            });

        dTable.order( [ 5, 'asc' ] ).draw();
    }

    function infoToggle(div){
        var newData = $(div).data("txt");
        var oldData = $(div).text();
        $(div).data("txt",oldData);
        $(div).text(newData);
    }



    var dTable = null;
    var wTable = null;
    $(document).ready(function() {
        $(".waitingTable").hide();
        wTable = $('#waittable').DataTable({ "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]] });
        dTable = $('#agenttable').DataTable({ "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]] });
        realTime();

    });

    $("#settings_save").on("click",function(){
        realTime();
    })

</script>