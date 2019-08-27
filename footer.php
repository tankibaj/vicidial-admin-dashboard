    <!-- Footer -->
    <footer class="footer container-fluid pl-30 pr-30">
        <div class="row">
            <div class="col-sm-12">
                <p>2018 &copy; PathaoCS</p>
            </div>
        </div>
    </footer>
    <!-- /Footer -->

    </div>
    <!-- /Main Content -->

</div>
<!-- /#wrapper -->

<!-- JavaScript -->

<!-- jQuery -->
<script src="vendors/bower_components/jquery/dist/jquery.min.js"></script>

<!-- Bootstrap Core JavaScript -->
<script src="vendors/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>

<!-- Data table JavaScript -->
<script src="vendors/bower_components/datatables/media/js/jquery.dataTables.min.js"></script>

    <!-- Moment JavaScript -->
    <script type="text/javascript" src="vendors/bower_components/moment/min/moment-with-locales.min.js"></script>


    <!-- Slimscroll JavaScript -->
<script src="dist/js/jquery.slimscroll.js"></script>

    <!-- Bootstrap Datetimepicker JavaScript -->
    <script type="text/javascript" src="vendors/bower_components/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js"></script>

    <!-- Bootstrap Daterangepicker JavaScript -->
    <script src="vendors/bower_components/bootstrap-daterangepicker/daterangepicker.js"></script>
    <!-- Multiselect JavaScript -->
    <script src="vendors/bower_components/multiselect/js/jquery.multi-select.js"></script>

    <!-- Select2 JavaScript -->
    <script src="vendors/bower_components/select2/dist/js/select2.full.min.js"></script>

    <!-- Bootstrap Select JavaScript -->
    <script src="vendors/bower_components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>


    <!-- Switchery JavaScript -->
    <script src="vendors/bower_components/switchery/dist/switchery.min.js"></script>

    <!-- Bootstrap Switch JavaScript -->
    <script src="vendors/bower_components/bootstrap-switch/dist/js/bootstrap-switch.min.js"></script>

<!-- simpleWeather JavaScript -->
<script src="vendors/bower_components/moment/min/moment.min.js"></script>
<script src="vendors/bower_components/simpleWeather/jquery.simpleWeather.min.js"></script>
<script src="dist/js/simpleweather-data.js"></script>

<!-- Progressbar Animation JavaScript -->
<script src="vendors/bower_components/waypoints/lib/jquery.waypoints.min.js"></script>
<script src="vendors/bower_components/jquery.counterup/jquery.counterup.min.js"></script>

<!-- Fancy Dropdown JS -->
<script src="dist/js/dropdown-bootstrap-extended.js"></script>

<!-- Sparkline JavaScript -->
<script src="vendors/jquery.sparkline/dist/jquery.sparkline.min.js"></script>

<!-- Owl JavaScript -->
<script src="vendors/bower_components/owl.carousel/dist/owl.carousel.min.js"></script>

<!-- ChartJS JavaScript -->
<script src="vendors/chart.js/Chart.min.js"></script>

<!-- Morris Charts JavaScript -->
<script src="vendors/bower_components/raphael/raphael.min.js"></script>
<script src="vendors/bower_components/morris.js/morris.min.js"></script>
<script src="vendors/bower_components/jquery-toast-plugin/dist/jquery.toast.min.js"></script>

<!-- Switchery JavaScript -->
<script src="vendors/bower_components/switchery/dist/switchery.min.js"></script>

    <!-- Bootstrap Touchspin JavaScript -->
    <script src="vendors/bower_components/bootstrap-touchspin/dist/jquery.bootstrap-touchspin.min.js"></script>

    <!-- Init JavaScript -->
<script src="dist/js/init.js"></script>

    <script>
        $(document).ready(function() {
            /* Daterange picker Init*/
            $('.input-daterange-datepicker').daterangepicker({
                buttonClasses: ['btn', 'btn-sm'],
                applyClass: 'btn-info',
                cancelClass: 'btn-default',
                locale: {
                    format: 'YYYY-MM-DD'
                }
            });

            $('.input-daterange-timepicker').daterangepicker({
                timePicker: true,
                format: 'YYYY-MM-DD h:mm A',
                timePickerIncrement: 30,
                timePicker12Hour: true,
                timePickerSeconds: false,
                buttonClasses: ['btn', 'btn-sm'],
                applyClass: 'btn-info',
                cancelClass: 'btn-default'
            });

            $('.selectpicker').selectpicker();
            $(".touchspin").TouchSpin();
        });
    </script>

</body>

</html>