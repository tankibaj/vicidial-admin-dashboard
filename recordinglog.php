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

$page = "Recording Logs";
$parent = "report";

require_once "header.php";


?>

<link href="vendors/footerplayer/css/style.css" rel="stylesheet">
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
                $query = \PATHAODB::table("recording_log");
                $query->select(\PATHAODB::raw("count(*) as tc"));
                $query->whereNotNull("length_in_sec");

                if(isset($_GET['ext']) && is_numeric($_GET['ext']) && $_GET['ext'] != ""){
                    $query -> where("extension","=",$_GET['ext']);
                }
                if(isset($_GET['userid']) && $_GET['userid'] != ""){
                    $query -> where("user","=",$_GET['userid']);
                }
                if(isset($_GET['usergroup']) && is_array($_GET['usergroup'])){
                    $query -> where(function($q){
                        $firstQ = true;
                       foreach($_GET['usergroup'] as $uGroup){
                           if($firstQ){
                               $q -> where("filename","LIKE","%" . $uGroup . "%");
                               $firstQ = false;
                           }else{
                               $q -> orWhere("filename","LIKE","%" . $uGroup . "%");
                           }
                       }
                    });
                    //$query -> where(\PATHAODB::raw("`filename` REGEXP '".implode("|", $_GET['usergroup'])."'"));
                }
                if(isset($_GET['phone']) && is_numeric($_GET['phone']) && $_GET['phone'] != ""){
                    $query -> where("filename","LIKE","%" . $_GET['phone'] . "%");
                }
                if(isset($_GET['useDateRange']) && $_GET['useDateRange'] != "" && isset($_GET['daterange'])){

                    $dateData = explode(" - ",$_GET['daterange']);
                    $query -> where("start_time",">=",$dateData[0] . " 00:00:00");
                    $query -> where("start_time","<=",$dateData[1]. " 23:59:59") ;
                }
                $result = $query->first();



                return (int) $result->tc;
            });

            $paginator->setSliceCallback(function ($offset,$length){
                $query = \PATHAODB::table("recording_log");
                 $query->limit($length);
                $query->offset($offset);
                $query->orderBy('start_time', 'DESC');

                //search conditions
                $query->whereNotNull("length_in_sec");

                if(isset($_GET['ext']) && is_numeric($_GET['ext']) && $_GET['ext'] != ""){
                    $query -> where("extension","=",$_GET['ext']);
                }
                if(isset($_GET['userid']) && $_GET['userid'] != ""){
                    $query -> where("user","=",$_GET['userid']);
                }
                if(isset($_GET['usergroup']) && is_array($_GET['usergroup'])){
                    $query -> where(function($q){
                        $firstQ = true;
                        foreach($_GET['usergroup'] as $uGroup){
                            if($firstQ){
                                $q -> where("filename","LIKE","%" . $uGroup . "%");
                                $firstQ = false;
                            }else{
                                $q -> orWhere("filename","LIKE","%" . $uGroup . "%");
                            }
                        }
                    });
                    //$query -> where(\PATHAODB::raw("`filename` REGEXP '".implode("|", $_GET['usergroup'])."'"));
                }
                if(isset($_GET['phone']) && is_numeric($_GET['phone']) && $_GET['phone'] != ""){
                    $query -> where("filename","LIKE","%" . $_GET['phone'] . "%");
                }

                if(isset($_GET['useDateRange']) && $_GET['useDateRange'] != "" && isset($_GET['daterange'])){
                    $dateData = explode(" - ",$_GET['daterange']);
                    $query -> where("start_time",">=",$dateData[0] . " 00:00:00");
                    $query -> where("start_time","<=",$dateData[1]. " 23:59:59") ;
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
                            <h6 class="panel-title txt-dark">Recording Logs</h6>
                        </div>
                        <div class="pull-right">
                            <button data-toggle="modal" data-target="#search-modal"  class="btn btn-primary btn-rounded btn-icon left-icon"> <i style="color:white;" class="fa fa-search"></i> <span>Search</span></button>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <div class="panel-wrapper collapse in">
                        <div class="panel-body">
                            <div id="search-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                            <h5 class="modal-title">Settings</h5>
                                        </div>

                                        <form method="GET">
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label class="control-label mb-10" for="userid">AgentID</label>
                                                    <div class="input-group">
                                                        <div class="input-group-addon"><i class="icon-user-follow"></i></div>
                                                        <input type="text" class="form-control" name="userid" id="userid" value="" placeholder="Search agent id">
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="control-label mb-10" for="name">Extension</label>
                                                    <div class="input-group">
                                                        <div class="input-group-addon"><i class="icon-user-follow"></i></div>
                                                        <input type="text" class="form-control" name="ext" id="ext" value="" placeholder="Search extension">
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="control-label mb-10">User Group</label>
                                                    <select name="usergroup[]" class="selectpicker" multiple data-style="form-control btn-default btn-outline">
                                                        <?php
                                                        $query = \PATHAODB::table("vicidial_user_groups");
                                                        $query->select(["user_group","group_name"]);
                                                        $query->orderBy("user_group","ASC");
                                                        $usergroups = $query->get();
                                                        foreach($usergroups as $ugroup){
                                                            $arr = json_decode($ugroup, true);
                                                            echo '<option value="'. $ugroup -> user_group .'">'. $ugroup -> group_name .'</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label class="control-label mb-10" for="phone">Customer Phone</label>
                                                    <div class="input-group">
                                                        <div class="input-group-addon"><i class="icon-phone"></i></div>
                                                        <input type="text" class="form-control" name="phone" id="phone" value="" placeholder="Phone">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="checkbox checkbox-success">
                                                        <input onclick="" id="useDateRange" name="useDateRange" type="checkbox">
                                                        <label for="useDateRange">
                                                            Use Date Range
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <input class="form-control input-daterange-datepicker2" type="text" name="daterange" value=""/>
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

                            <div class="table-wrap mt-40">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Extension</th>
                                            <th>Agent</th>
                                            <th>Customer</th>
                                            <th>Group</th>
                                            <th>Length</th>
                                            <th>Call Type</th>
                                            <th>Time</th>
                                            <th>Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $recordings = $pagination->getItems();

                                        foreach($recordings as $dCall){
                                            if(preg_match('#\d{8}-\d{6,8}_(\d+)_([A-Z0-9]+)_([a-zA-Z0-9]+)#', $dCall -> filename, $matched)){
                                                $call_type = "<span class='label label-success'>Outgoing</span>";
                                                $custphone = $matched[1];
                                                $group = $matched[2];
                                            }
                                            elseif(preg_match('#(\d+)_\d{8}-\d{6,8}_([A-Z0-9]+)_([a-zA-Z0-9]+)#', $dCall -> filename, $matched)){
                                                $call_type = "<span class='label label-primary'>Incoming</span>";
                                                $custphone = $matched[1];
                                                $group = $matched[2];
                                            }
                                            else{
                                                continue;
                                            }
                                            echo '<tr>
                                        <td>' . $dCall -> recording_id. '</td>
                                        <td>' . $dCall -> extension . '</td>
                                        <td><a href="showusers.php?userid=' . $dCall -> user . '" target="_blank">' . $dCall -> user . '</a></td>
                                        <td><span class=\'label label-danger\'>'.$custphone.'</span></td>
                                        <td><span class=\'label label-info\'>'.$group.'</span></td>
                                        <td>' . $dCall -> length_in_sec . ' seconds</td>
                                        <td>'.$call_type.'</td>
                                        <td>' . $dCall-> start_time . '</td>';

                                            if(!empty($dCall -> location)){
                                                echo '<td>
                                        <a href="#" data-mp3="/RECORDINGS/MP3/'.$dCall -> filename.'-all.mp3" data-action="add" data-id="" data-title="'.$dCall -> filename.'" data-artist="'.$custphone.'" data-album="" data-cover="false" class="plManager mr-25" data-toggle="tooltip" data-original-title="Listen"> <i class="fa fa-play txt-primary text-inverse m-r-10"></i> </a>
                                        <a href="/RECORDINGS/MP3/'.$dCall -> filename.'-all.mp3" class="mr-25" data-toggle="tooltip" data-original-title="Download"> <i class="fa fa-download txt-success text-inverse m-r-10"></i> </a>
</td>';
                                            }
                                            else{
                                                echo '<td><span class="label label-primary"><i class="fa fa-hourglass text-inverse m-r-10"></i> Pending</span></td>';
                                            }

                                        echo '</tr>';
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


<?php
require_once "footer.php";
?>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.11.1/jquery-ui.min.js"></script>
<script src="vendors/footerplayer/js/jquery.ui.touch-punch.min.js"></script>
<script src="vendors/footerplayer/js/jquery.jplayer.min.js"></script>
<script src="vendors/footerplayer/js/jplayer.playlist.min.js"></script>
<script src="vendors/footerplayer/js/id3.min.js"></script>
<script src="vendors/footerplayer/js/iscroll.min.js"></script>
<script src="vendors/footerplayer/js/jquery.linerPlayer.min.js"></script>
<script>
    jQuery(function ($) {
        $('body').linerPlayer({
            firstPlaying: 0,
            autoplay: true,
            shuffle: false,
            //veryThin: true,
            slideAlbumsName: false,
            nowplaying2title: true,
            roundedCorners: true,
            //continuous: true,
            pluginPath: "vendors/footerplayer/", // <<< IMPORTANT! - Change this to your path to the plugin folder
        });

    });
    $('.input-daterange-datepicker2').daterangepicker({
        buttonClasses: ['btn', 'btn-sm'],
        applyClass: 'btn-info',
        cancelClass: 'btn-default',
        locale: {
            format: 'YYYY-MM-DD'
        }
    });
</script>
