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

$page = "Drop Calls";
$parent = "report";
$request_uri = $_SERVER['REQUEST_URI'];

if(isset($_GET['download']) && $_GET['download'] == "yes" && isset($_GET['daterange']) && $_GET['daterange'] != "") {
    $query = \PATHAODB::table("vicidial_closer_log");
    $query->select(["call_date", "campaign_id", "phone_number", "length_in_sec", "status"]);
    $query->limit($length);
    $query->offset($offset);
    $query->orderBy('call_date', 'DESC');

//search conditions
    $query->where("status", "=", "DROP");
    $selectedCampaign = (array)$_GET['campaign'];
    if (!empty($selectedCampaign) && !in_array("ALL", $selectedCampaign)) {
        $query->whereIn('campaign_id', $selectedCampaign);
    }

    $dateRange = explode(" - ", $_GET['daterange']);
    $query->where(\PATHAODB::raw("DATE(call_date) >= '$dateRange[0]' AND DATE(call_date) <= '$dateRange[1]'"));
//search conditions
    $result = $query->get();
    $output .= '"In-Group:","'.implode(",",$_GET['campaign']).'","'.$_GET['daterange'].'"' . Chr(10) . Chr(10);
    $output .= '"Date","Time","Campaign","Phone","Length","Status"' . Chr(10);
    foreach($result as $dCall){
        $cDate = new DateTime($dCall -> call_date);
        $output .= '"' . $cDate -> format("Y-m-d"). '","' . $cDate -> format("h:i:s a"). '","' . $dCall->campaign_id . '","' . $dCall->phone_number . '","' . $dCall->length_in_sec. 's","' . $dCall-> status. '"' . Chr(10);
    }
    exportDownload($output,"DropCalls");
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
                                    <label class="control-label mb-10">Inbound Group</label>
                                    <select name="campaign[]" class="selectpicker" multiple data-style="form-control btn-default btn-outline" required>
                                        <?php

                                        $campaigns = getInboundGroup();
                                        echo '<option value="ALL">All</option>';
                                        foreach($campaigns as $campaign){
                                            echo '<option value="'. $campaign -> group_id .'">'. $campaign -> group_name .'</option>';
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
        <!-- Table Hover -->
        <?php

        if(isset($_GET['daterange']) && $_GET['daterange'] != ""){
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
                $query = \PATHAODB::table("vicidial_closer_log");
                $query->select(\PATHAODB::raw("count(lead_id) as tc"));
                //search conditions
                $query->where("status","=","DROP");
                $selectedCampaign = (array) $_GET['campaign'];
                if(!empty($selectedCampaign) && !in_array("ALL", $selectedCampaign)){
                    $query->whereIn('campaign_id', $selectedCampaign);
                }

                $dateRange = explode(" - ",$_GET['daterange']);
                $query->where(\PATHAODB::raw("DATE(call_date) >= '$dateRange[0]' AND DATE(call_date) <= '$dateRange[1]'"));
                //search conditions

                $result = $query->first();

                return (int) $result->tc;
            });

            $paginator->setSliceCallback(function ($offset,$length){
                $query = \PATHAODB::table("vicidial_closer_log");
                $query->select(["call_date","campaign_id","phone_number","length_in_sec","status"]);
                $query->limit($length);
                $query->offset($offset);
                $query->orderBy('call_date', 'DESC');

                //search conditions
                $query->where("status","=","DROP");
                $selectedCampaign = (array) $_GET['campaign'];
                if(!empty($selectedCampaign) && !in_array("ALL", $selectedCampaign)){
                    $query->whereIn('campaign_id', $selectedCampaign);
                }

                $dateRange = explode(" - ",$_GET['daterange']);
                $query->where(\PATHAODB::raw("DATE(call_date) >= '$dateRange[0]' AND DATE(call_date) <= '$dateRange[1]'"));
                //search conditions

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
                        <h6 class="panel-title txt-dark">Dropped Call List</h6>
                    </div>
                    <div class="pull-right">
                        <a href="<?php echo $request_uri; ?>&download=yes" class="pull-left inline-block mr-15" data-toggle="tooltip" data-original-title="Download">
                            <i class="zmdi zmdi-download"></i>
                        </a>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="panel-wrapper collapse in">
                    <div class="panel-body">
                        <div class="table-wrap mt-40">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Campaign</th>
                                        <th>Phone</th>
                                        <th>Length</th>
                                        <th>Status</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $droppedCalls = $pagination->getItems();

                                    foreach($droppedCalls as $dCall){
                                        $cDate = new DateTime($dCall -> call_date);

                                        echo '<tr>
                                        <td>' . $cDate -> format("Y-m-d"). '</td>
                                        <td>' . $cDate -> format("h:i:s a"). '</td>
                                        <td>' . $dCall->campaign_id . '</td>
                                        <td>' . $dCall->phone_number . '</td>
                                        <td>' . $dCall->length_in_sec. 's</td>
                                        <td>' . $dCall-> status. '</td>
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
        <?php } ?>
    </div>
</div>


<?php
require_once "footer.php";
?>

