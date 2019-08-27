<!-- Left Sidebar Menu -->
<div class="fixed-sidebar-left">
    <ul class="nav navbar-nav side-nav nicescroll-bar">
        <li class="navigation-header">
            <span>Main</span>
            <i class="zmdi zmdi-more"></i>
        </li>
        <?php if($auth -> isSupervisor()){ ?>
        <li>
            <a <?php if($parent == "report") echo 'class="active"'; ?> href="javascript:void(0);" data-toggle="collapse" data-target="#report_dr"><div class="pull-left"><i class="zmdi zmdi-landscape mr-20"></i><span class="right-nav-text">Reports</span></div><div class="pull-right"><i class="zmdi zmdi-caret-down"></i></div><div class="clearfix"></div></a>
            <ul id="report_dr" class="collapse collapse-level-1">
                <li>
                    <a <?php if($page == "Real Time") echo 'class="active"'; ?> href="index.php">Real-time Reports</a>
                </li>
                <li>
                    <a <?php if($page == "dropcall") echo 'class="active"'; ?> href="inbound.php">Inbound Reports</a>
                </li>
                <li>
                    <a <?php if($page == "Drop Calls") echo 'class="active"'; ?> href="dropcall.php">Drop Calls</a>
                </li>
                <li>
                    <a <?php if($page == "Recording Logs") echo 'class="active"'; ?> href="recordinglog.php">Recording Logs</a>
                </li>
                <li>
                    <a <?php if($page == "Agents Performance Details") echo 'class="active"'; ?> href="agentdetails.php">Agents Performance</a>
                </li>
                <li>
                    <a <?php if($page == "Export Calls") echo 'class="active"'; ?> href="export.php">Export Calls</a>
                </li>
            </ul>
        </li>
        <?php } ?>

        <?php if($auth -> isAdmin()){ ?>
        <li>
            <a <?php if($parent == "user") echo 'class="active"'; ?> href="javascript:void(0);" data-toggle="collapse" data-target="#users_dr"><div class="pull-left"><i class="zmdi zmdi-nature-people mr-20"></i><span class="right-nav-text">Users & Phones</span></div><div class="pull-right"><i class="zmdi zmdi-caret-down"></i></div><div class="clearfix"></div></a>
            <ul id="users_dr" class="collapse collapse-level-1">
                <li>
                    <a <?php if($page == "Show Users") echo 'class="active"'; ?> href="showusers.php">Show Users</a>
                </li>
                <li>
                    <a <?php if($page == "Show Phones") echo 'class="active"'; ?> href="showphones.php">Show Phones</a>
                </li>

            </ul>
        </li>
        <?php } ?>
    </ul>
</div>
<!-- /Left Sidebar Menu -->