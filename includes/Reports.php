<?php
/**
 * Created by PhpStorm.
 * User: elomelo
 * Date: 8/5/18
 * Time: 6:34 PM
 */

namespace Common;


class Reports
{
    var $selectedGroups;
    var $dateStart;
    var $dateEnd;
    var $totalCalls = 0;
    var $totalSeconds = 0;
    var $totalDropCalls = 0;
    var $statsNameArr = [];
    var $statsCatArr = [];
    var $output = "";
    var $hourlyOutput = "";
    var $dropOutput = "";
    var $startHour = 0;
    var $endHour = 0;
     public function __construct($selectedGroup,$dateStart,$dateEnd)
     {
         $this -> selectedGroups = $selectedGroup;
         $this -> dateStart = $dateStart;
         $this -> dateEnd = $dateEnd;

         $date = new \DateTime($this -> dateStart);
         $this -> startHour = (int) $date -> format("H");
         $date = new \DateTime($this -> dateEnd);
         $this -> endHour = (int) $date -> format("H");
     }


     public function totalReports(){
         $this -> output = "";
         $query = \PATHAODB::table("vicidial_closer_log");
         $query->select(\PATHAODB::raw("count(*) as totalData, sum(length_in_sec) as lengthInSec"));
         $query->where("call_date", ">=", $this -> dateStart);
         $query->where("call_date", "<=", $this -> dateEnd);
         $query->whereIn("campaign_id", $this->selectedGroups);
         $resultLength = $query->first();

         $query = \PATHAODB::table("vicidial_closer_log");
         $query->select(\PATHAODB::raw("count(*) as totalData, sum(queue_seconds) as queueSeconds"));
         $query->where("call_date", ">=", $this -> dateStart);
         $query->where("call_date", "<=", $this -> dateEnd);
         $query->whereIn("campaign_id", $this->selectedGroups);
         $query->whereNotIn("status",['DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','INBND','MAXCAL']);
         $resultAnswered = $query->first();

         $query = \PATHAODB::table("live_inbound_log");
         $query->select(\PATHAODB::raw("count(*) as totalData"));
         $query->where("start_time", ">=", $this -> dateStart);
         $query->where("start_time", "<=", $this -> dateEnd);
         $query->whereIn("comment_a", $this->selectedGroups);
         $query->where("comment_b", "=", 'START');
         $resultIVR = $query->first();

         $TOTALcalls =	sprintf("%10s", $resultLength -> totalData);
         $IVRcalls =	sprintf("%10s", $resultIVR -> totalData);
         $this -> totalSeconds = $TOTALsec =		$resultLength -> lengthInSec;

         $average_call_seconds = $this -> MathZDC($TOTALsec, $resultLength -> totalData);
         $average_call_seconds = round($average_call_seconds, 0);
         $average_call_seconds =	sprintf("%10s", $average_call_seconds);

         $ANSWEREDcalls  =	sprintf("%10s", $resultAnswered -> totalData);

         $ANSWEREDpercent = ($this -> MathZDC($ANSWEREDcalls, $TOTALcalls) * 100);
         $ANSWEREDpercent = round($ANSWEREDpercent, 0);

         $average_answer_seconds = $this -> MathZDC($resultAnswered -> queueSeconds, $resultAnswered -> totalData);
         $average_answer_seconds = round($average_answer_seconds, 2);
         $average_answer_seconds =	sprintf("%10s", $average_answer_seconds);


         $return['totalcalls'] = $TOTALcalls;
         $return['avg_length'] = $average_call_seconds;
         $return['answered'] = $ANSWEREDcalls;
         $return['answer_percent'] = $ANSWEREDpercent;
         $return['avg_ans_queue'] = $average_answer_seconds;
         $return['ivr_calls'] = $IVRcalls;

         $this -> output .= "\n\n\"Total\"\n";
         $this -> output .= '"Total calls taken in to this In-Group", "'.$return['totalcalls'].'"' . Chr(10);
         $this -> output .= '"Average Call Length for all Calls", "'.$return['avg_length'].' seconds"' . Chr(10);
         $this -> output .= '"Answered Calls", "'.$return['answered'].'", "'.$return['answer_percent'].'%"' . Chr(10);
         $this -> output .= '"Average queue time for Answered Calls", "'.$return['avg_ans_queue'].' seconds"' . Chr(10);
         $this -> output .= '"Calls taken into the IVR for this In-Group", "'.$return['ivr_calls'].'"' . Chr(10);

         $query = \PATHAODB::table("vicidial_closer_log");
         $query->select(\PATHAODB::raw("count(*) as totalData, sum(length_in_sec) as lengthInSec"));
         $query->where("call_date", ">=", $this -> dateStart);
         $query->where("call_date", "<=", $this -> dateEnd);
         $query->whereIn("campaign_id", $this->selectedGroups);
         $query->whereIn("status", ['DROP','XDROP']);
         $query->where(function($q){
             $q -> where("length_in_sec","<=","49999");
             $q -> orWhereNull("length_in_sec");
         });
         $resultDropLength = $query->first();

         $DROPcalls =	sprintf("%10s", $resultDropLength -> totalData);
         $DROPpercent = ($this -> MathZDC($DROPcalls, $TOTALcalls) * 100);
         $DROPpercent = round($DROPpercent, 0);

         $average_hold_seconds = $this -> MathZDC($resultDropLength -> lengthInSec, $resultDropLength -> totalData);
         $average_hold_seconds = round($average_hold_seconds, 0);
         $average_hold_seconds =	sprintf("%10s", $average_hold_seconds);

         $DROP_ANSWEREDpercent = ($this -> MathZDC($DROPcalls, $ANSWEREDcalls) * 100);
         $DROP_ANSWEREDpercent = round($DROP_ANSWEREDpercent, 0);

         $return['totaldrop'] = $DROPcalls;
         $return['drop_percent'] = $DROPpercent;
         $return['drop_ans_percent'] = $DROP_ANSWEREDpercent;
         $return['avg_hold_drop'] = $average_hold_seconds;

         $this -> output .= "\n\n\"Drops\"\n";
         $this -> output .= '"Total DROP Calls", "'.$return['totaldrop'].'", "'.$return['drop_percent'].'%","drop/answered", "'.$return['drop_ans_percent'].'%"' . Chr(10);
         $this -> output .= '"Average hold time for DROP Calls", "'.$return['avg_hold_drop'].' seconds"' . Chr(10);

         $query = \PATHAODB::table("vicidial_inbound_groups");
         $query->select(["answer_sec_pct_rt_stat_one","answer_sec_pct_rt_stat_two"]);
         $query->whereIn("group_id", $this->selectedGroups);
         $query->orderBy("answer_sec_pct_rt_stat_one","DESC");
         $viciResult = $query->first();
         $Sanswer_sec_pct_rt_stat_one = $viciResult -> answer_sec_pct_rt_stat_one;
         $Sanswer_sec_pct_rt_stat_two = $viciResult -> answer_sec_pct_rt_stat_two;


         $query = \PATHAODB::table("vicidial_closer_log");
         $query->select(\PATHAODB::raw("count(*) as totalData"));
         $query->where("call_date", ">=", $this -> dateStart);
         $query->where("call_date", "<=", $this -> dateEnd);
         $query->whereIn("campaign_id", $this->selectedGroups);
         $query->where("queue_seconds", "<=", $Sanswer_sec_pct_rt_stat_one);
         $query->whereNotIn("status", ['DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','INBND','MAXCAL']);
         $result = $query->first();
         $answer_sec_pct_rt_stat_one = $result -> totalData;


         $query = \PATHAODB::table("vicidial_closer_log");
         $query->select(\PATHAODB::raw("count(*) as totalData"));
         $query->where("call_date", ">=", $this -> dateStart);
         $query->where("call_date", "<=", $this -> dateEnd);
         $query->whereIn("campaign_id", $this->selectedGroups);
         $query->where("queue_seconds", "<=", $Sanswer_sec_pct_rt_stat_two);
         $query->whereNotIn("status", ['DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','INBND','MAXCAL']);
         $result = $query->first();
         $answer_sec_pct_rt_stat_two = $result -> totalData;

         $PCTanswer_sec_pct_rt_stat_one = ($this -> MathZDC($answer_sec_pct_rt_stat_one, $ANSWEREDcalls) * 100);
         $PCTanswer_sec_pct_rt_stat_one = round($PCTanswer_sec_pct_rt_stat_one, 0);
         $PCTanswer_sec_pct_rt_stat_two = ($this -> MathZDC($answer_sec_pct_rt_stat_two, $ANSWEREDcalls) * 100);
         $PCTanswer_sec_pct_rt_stat_two = round($PCTanswer_sec_pct_rt_stat_two, 0);

         $return['gde'] = $ANSWEREDpercent;
         $return['acr'] = $DROP_ANSWEREDpercent;
         $return['tma1_sec'] = $Sanswer_sec_pct_rt_stat_one;
         $return['tma2_sec'] = $Sanswer_sec_pct_rt_stat_two;
         $return['tma1'] = $PCTanswer_sec_pct_rt_stat_one;
         $return['tma2'] = $PCTanswer_sec_pct_rt_stat_two;

         $this -> output .= "\n\n\"CUSTOM INDICATORS\"\n";
         $this -> output .= '"GDE (Answered/Total calls taken in to this In-Group):", "'.$return['gde'].'%"' . Chr(10);
         $this -> output .= '"ACR (Dropped/Answered):", "'.$return['acr'].'%"' . Chr(10);
         $this -> output .= '"TMR1 (Answered within '.$return['tma1_sec'].' seconds/Answered):", "'.$return['tma1'].'%"' . Chr(10);
         $this -> output .= '"TMR2 (Answered within '.$return['tma2_sec'].' seconds/Answered):", "'.$return['tma2'].'%"' . Chr(10);


         $query = \PATHAODB::table("vicidial_closer_log");
         $query->select(\PATHAODB::raw("count(*) as totalData, sum(queue_seconds) as queueSeconds"));
         $query->where("call_date", ">=", $this -> dateStart);
         $query->where("call_date", "<=", $this -> dateEnd);
         $query->whereIn("campaign_id", $this->selectedGroups);
         $query->where("queue_seconds", ">", "0");
         $result = $query->first();

         $QUEUEcalls =	sprintf("%10s", $result -> totalData);
         $QUEUEpercent = ($this -> MathZDC($QUEUEcalls, $TOTALcalls) * 100);
         $QUEUEpercent = round($QUEUEpercent, 0);

         $average_queue_seconds = $this -> MathZDC($result -> queueSeconds, $result -> totalData);
         $average_queue_seconds = round($average_queue_seconds, 2);
         $average_queue_seconds = sprintf("%10.2f", $average_queue_seconds);

         $average_total_queue_seconds = $this -> MathZDC($result -> queueSeconds, $TOTALcalls);
         $average_total_queue_seconds = round($average_total_queue_seconds, 2);
         $average_total_queue_seconds = sprintf("%10.2f", $average_total_queue_seconds);

         $return['totalcall_queue'] = $QUEUEcalls;
         $return['totalcall_queue_percent'] = $QUEUEpercent;
         $return['avg_length_queue'] = $average_queue_seconds;
         $return['avg_length_all'] = $average_total_queue_seconds;

         $this -> output .= "\n\n\"QUEUE STATS\"\n";
         $this -> output .= '"Total Calls That entered Queue:", "'.$return['totalcall_queue'].'", "'.$return['totalcall_queue_percent'].'%"' . Chr(10);
         $this -> output .= '"Average QUEUE Length for queue calls:", "'.$return['avg_length_queue'].' seconds"' . Chr(10);
         $this -> output .= '"Average QUEUE Length across all calls:", "'.$return['avg_length_all'].' seconds"' . Chr(10);

         return $return;
     }


     public function setOutput(&$output, $arr){
         $arr = array_map(function($val) { return '"' . $val . '"'; }, $arr);
         $output .= implode(",", $arr) . Chr(10);
     }
     public function checkDownload($key, $type = false){
         if(isset($_GET['download']) && $_GET['download'] == $key){
             $FILE_TIME = date("Ymd-His");
             $CSVfilename = "InboundReport_".$key."_$FILE_TIME.csv";
             $header = false;
             if($type == "HOURLY") $header = true;
             $this -> downloadHeader($header);
             if(!$type)
                 $output = $this -> output;
             elseif($type == "HOURLY")
                 $output = $this -> hourlyOutput;
             elseif($type == "DROP")
                 $output = $this -> dropOutput;

             $output=preg_replace('/^\s+/', '', $output);
             $output=preg_replace('/ +\"/', '"', $output);
             $output=preg_replace('/\" +/', '"', $output);
             header('Content-type: application/octet-stream');

             header("Content-Disposition: attachment; filename=\"$CSVfilename\"");
             header('Expires: 0');
             header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
             header('Pragma: public');
             ob_clean();
             flush();

             die($output);
         }
     }

     private function downloadHeader($hourly = false){
         if(!$hourly)
             $this -> output = '"Inbound Call Status:","'.implode(",",$this -> selectedGroups).'","'. $this -> dateStart .' - '. $this -> dateEnd .'"' . Chr(10) . $this -> output;
         else
             $this -> hourlyOutput = '"Inbound Hourly Breakdown:","'.implode(",",$this -> selectedGroups).'","'. $this -> dateStart .' - '. $this -> dateEnd .'"' . Chr(10) . $this -> hourlyOutput;
     }

     public function callHoldBreakdown($drop = false){
         $this -> output = "\n";
         $query = \PATHAODB::table("vicidial_closer_log");
         $query->select(\PATHAODB::raw("count(*) as totalData, queue_seconds"));
         $query->where("call_date", ">=", $this -> dateStart);
         $query->where("call_date", "<=", $this -> dateEnd);
         $query->whereIn("campaign_id", $this->selectedGroups);
         if($drop)
             $query->whereIn("status",['DROP','XDROP']);
         $query->groupBy("queue_seconds");

         $result = $query -> get();

         $listArray = [0 => 0,5 => 0,10=> 0,15=>0,20=>0,25=>0,30=>0,35=>0,40=>0,45=>0,50=>0,55=>0,60=>0,90=>0,99=>0];
         $TOTALcalls = 0;
         foreach($result as $qData){
             if ($qData -> queue_seconds == 0) {
                 $secIndex = 0;
             }
             if ( ($qData -> queue_seconds > 0) and ($qData -> queue_seconds <= 5) ) {
                 $secIndex = 5;
             }
             if ( ($qData -> queue_seconds > 5) and ($qData -> queue_seconds <= 10) ) {
                 $secIndex = 10;
             }
             if ( ($qData -> queue_seconds > 10) and ($qData -> queue_seconds <= 15) ) {
                 $secIndex = 15;
             }
             if ( ($qData -> queue_seconds > 15) and ($qData -> queue_seconds <= 20) ) {
                 $secIndex = 20;
             }
             if ( ($qData -> queue_seconds > 20) and ($qData -> queue_seconds <= 25) ) {
                 $secIndex = 25;
             }
             if ( ($qData -> queue_seconds > 25) and ($qData -> queue_seconds <= 30) ) {
                 $secIndex = 30;
             }
             if ( ($qData -> queue_seconds > 30) and ($qData -> queue_seconds <= 35) ) {
                 $secIndex = 35;
             }
             if ( ($qData -> queue_seconds > 35) and ($qData -> queue_seconds <= 40) ) {
                 $secIndex = 40;
             }
             if ( ($qData -> queue_seconds > 40) and ($qData -> queue_seconds <= 45) ) {
                 $secIndex = 45;
             }
             if ( ($qData -> queue_seconds > 45) and ($qData -> queue_seconds <= 50) ) {
                 $secIndex = 50;
             }
             if ( ($qData -> queue_seconds > 50) and ($qData -> queue_seconds <= 55) ) {
                 $secIndex = 55;
             }
             if ( ($qData -> queue_seconds > 55) and ($qData -> queue_seconds <= 60) ) {
                 $secIndex = 60;
             }
             if ( ($qData -> queue_seconds > 60) and ($qData -> queue_seconds <= 90) ) {
                 $secIndex = 90;
             }
             if ($qData -> queue_seconds > 90) {
                 $secIndex = 99;
             }

             $TOTALcalls += $qData -> totalData;
             $listArray[$secIndex] += $qData -> totalData;
             
             if($drop)
                 $this -> totalDropCalls = $TOTALcalls;
             else
                 $this -> totalCalls = $TOTALcalls;

         }

         if($drop){
             $this->setOutput($this->output, ['CALL HOLD TIME BREAKDOWN IN SECONDS']);
         }else {
             $this->setOutput($this->output, ['CALL DROP TIME BREAKDOWN IN SECONDS']);
         }
         $this -> setOutput($this -> output,[0,5,10,15,20,25,30,35,40,45,50,55,60,90,">90","Total"]);
         $this -> setOutput($this -> output,[$listArray[0],$listArray[5],$listArray[10],$listArray[15],$listArray[20],$listArray[25],$listArray[30],
             $listArray[35],$listArray[40],$listArray[45],$listArray[50],$listArray[55],$listArray[60],$listArray[90],$listArray[99],$TOTALcalls]);
         return ["total" => $TOTALcalls, "list" => $listArray];

     }

     public function callAnsCum(){
         $this -> output = "\n";
         $query = \PATHAODB::table("vicidial_closer_log");
         $query->select(\PATHAODB::raw("count(*) as totalData, queue_seconds"));
         $query->where("call_date", ">=", $this -> dateStart);
         $query->where("call_date", "<=", $this -> dateEnd);
         $query->whereIn("campaign_id", $this->selectedGroups);
         $query->whereNotIn("status",['DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','INBND','MAXCAL']);
         $query->groupBy("queue_seconds");

         $result = $query -> get();

         $listArray = [0 => 0,5 => 0, 10=> 0,15=>0,20=>0,25=>0,30=>0,35=>0,40=>0,45=>0,50=>0,55=>0,60=>0,90=>0,99=>0];
         $BDansweredCALLS = 0;
         foreach($result as $qData){
             if ($qData -> queue_seconds == 0) {
                 $secIndex = 0;
             }
             if ( ($qData -> queue_seconds > 0) and ($qData -> queue_seconds <= 5) ) {
                 $secIndex = 5;
             }
             if ( ($qData -> queue_seconds > 5) and ($qData -> queue_seconds <= 10) ) {
                 $secIndex = 10;
             }
             if ( ($qData -> queue_seconds > 10) and ($qData -> queue_seconds <= 15) ) {
                 $secIndex = 15;
             }
             if ( ($qData -> queue_seconds > 15) and ($qData -> queue_seconds <= 20) ) {
                 $secIndex = 20;
             }
             if ( ($qData -> queue_seconds > 20) and ($qData -> queue_seconds <= 25) ) {
                 $secIndex = 25;
             }
             if ( ($qData -> queue_seconds > 25) and ($qData -> queue_seconds <= 30) ) {
                 $secIndex = 30;
             }
             if ( ($qData -> queue_seconds > 30) and ($qData -> queue_seconds <= 35) ) {
                 $secIndex = 35;
             }
             if ( ($qData -> queue_seconds > 35) and ($qData -> queue_seconds <= 40) ) {
                 $secIndex = 40;
             }
             if ( ($qData -> queue_seconds > 40) and ($qData -> queue_seconds <= 45) ) {
                 $secIndex = 45;
             }
             if ( ($qData -> queue_seconds > 45) and ($qData -> queue_seconds <= 50) ) {
                 $secIndex = 50;
             }
             if ( ($qData -> queue_seconds > 50) and ($qData -> queue_seconds <= 55) ) {
                 $secIndex = 55;
             }
             if ( ($qData -> queue_seconds > 55) and ($qData -> queue_seconds <= 60) ) {
                 $secIndex = 60;
             }
             if ( ($qData -> queue_seconds > 60) and ($qData -> queue_seconds <= 90) ) {
                 $secIndex = 90;
             }
             if ($qData -> queue_seconds > 90) {
                 $secIndex = 99;
             }

             $BDansweredCALLS += $qData -> totalData;
             $listArray[$secIndex] += $qData -> totalData;

         }
         $cadArray = [];
         $cadArray[0] =$listArray[0];
         $cadArray[5] =($cadArray[0] + $listArray[5]);
         $cadArray[10] =($cadArray[5] + $listArray[10]);
         $cadArray[15] =($cadArray[10] + $listArray[15]);
         $cadArray[20] =($cadArray[15] + $listArray[20]);
         $cadArray[25] =($cadArray[20] + $listArray[25]);
         $cadArray[30] =($cadArray[25] + $listArray[30]);
         $cadArray[35] =($cadArray[30] + $listArray[35]);
         $cadArray[40] =($cadArray[35] + $listArray[40]);
         $cadArray[45] =($cadArray[40] + $listArray[45]);
         $cadArray[50] =($cadArray[45] + $listArray[50]);
         $cadArray[55] =($cadArray[50] + $listArray[55]);
         $cadArray[60] =($cadArray[55] + $listArray[60]);
         $cadArray[90] =($cadArray[60] + $listArray[90]);
         $cadArray[99] =($cadArray[90] + $listArray[99]);

         $padArray = [0 => 0,5 => 0,10=> 0,15=>0,20=>0,25=>0,30=>0,35=>0,40=>0,45=>0,50=>0,55=>0,60=>0,90=>0,99=>0];
         $pCadArray = [0 => 0,5 => 0,10=> 0,15=>0,20=>0,25=>0,30=>0,35=>0,40=>0,45=>0,50=>0,55=>0,60=>0,90=>0,99=>0];
         $ApCadArray = [0 => 0,5 => 0,10=> 0,15=>0,20=>0,25=>0,30=>0,35=>0,40=>0,45=>0,50=>0,55=>0,60=>0,90=>0,99=>0];
         if ( ($BDansweredCALLS > 0) and ($this -> totalCalls > 0) )
         {
             $padArray[0] = ($this -> MathZDC($listArray[0], $this -> totalCalls) * 100);	$padArray[0] = round($padArray[0], 0);
             $padArray[5] = ($this -> MathZDC($listArray[5], $this -> totalCalls) * 100);	$padArray[5] = round($padArray[5], 0);
             $padArray[10] = ($this -> MathZDC($listArray[10], $this -> totalCalls) * 100);	$padArray[10] = round($padArray[10], 0);
             $padArray[15] = ($this -> MathZDC($listArray[15], $this -> totalCalls) * 100);	$padArray[15] = round($padArray[15], 0);
             $padArray[20] = ($this -> MathZDC($listArray[20], $this -> totalCalls) * 100);	$padArray[20] = round($padArray[20], 0);
             $padArray[25] = ($this -> MathZDC($listArray[25], $this -> totalCalls) * 100);	$padArray[25] = round($padArray[25], 0);
             $padArray[30] = ($this -> MathZDC($listArray[30], $this -> totalCalls) * 100);	$padArray[30] = round($padArray[30], 0);
             $padArray[35] = ($this -> MathZDC($listArray[35], $this -> totalCalls) * 100);	$padArray[35] = round($padArray[35], 0);
             $padArray[40] = ($this -> MathZDC($listArray[40], $this -> totalCalls) * 100);	$padArray[40] = round($padArray[40], 0);
             $padArray[45] = ($this -> MathZDC($listArray[45], $this -> totalCalls) * 100);	$padArray[45] = round($padArray[45], 0);
             $padArray[50] = ($this -> MathZDC($listArray[50], $this -> totalCalls) * 100);	$padArray[50] = round($padArray[50], 0);
             $padArray[55] = ($this -> MathZDC($listArray[55], $this -> totalCalls) * 100);	$padArray[55] = round($padArray[55], 0);
             $padArray[60] = ($this -> MathZDC($listArray[60], $this -> totalCalls) * 100);	$padArray[60] = round($padArray[60], 0);
             $padArray[90] = ($this -> MathZDC($listArray[90], $this -> totalCalls) * 100);	$padArray[90] = round($padArray[90], 0);
             $padArray[99] = ($this -> MathZDC($listArray[99], $this -> totalCalls) * 100);	$padArray[99] = round($padArray[99], 0);


             $pCadArray[0] = ($this -> MathZDC($cadArray[0], $this -> totalCalls) * 100);	$pCadArray[0] = round($pCadArray[0], 0);
             $pCadArray[5] = ($this -> MathZDC($cadArray[5], $this -> totalCalls) * 100);	$pCadArray[5] = round($pCadArray[5], 0);
             $pCadArray[10] = ($this -> MathZDC($cadArray[10], $this -> totalCalls) * 100);	$pCadArray[10] = round($pCadArray[10], 0);
             $pCadArray[15] = ($this -> MathZDC($cadArray[15], $this -> totalCalls) * 100);	$pCadArray[15] = round($pCadArray[15], 0);
             $pCadArray[20] = ($this -> MathZDC($cadArray[20], $this -> totalCalls) * 100);	$pCadArray[20] = round($pCadArray[20], 0);
             $pCadArray[25] = ($this -> MathZDC($cadArray[25], $this -> totalCalls) * 100);	$pCadArray[25] = round($pCadArray[25], 0);
             $pCadArray[30] = ($this -> MathZDC($cadArray[30], $this -> totalCalls) * 100);	$pCadArray[30] = round($pCadArray[30], 0);
             $pCadArray[35] = ($this -> MathZDC($cadArray[35], $this -> totalCalls) * 100);	$pCadArray[35] = round($pCadArray[35], 0);
             $pCadArray[40] = ($this -> MathZDC($cadArray[40], $this -> totalCalls) * 100);	$pCadArray[40] = round($pCadArray[40], 0);
             $pCadArray[45] = ($this -> MathZDC($cadArray[45], $this -> totalCalls) * 100);	$pCadArray[45] = round($pCadArray[45], 0);
             $pCadArray[50] = ($this -> MathZDC($cadArray[50], $this -> totalCalls) * 100);	$pCadArray[50] = round($pCadArray[50], 0);
             $pCadArray[55] = ($this -> MathZDC($cadArray[55], $this -> totalCalls) * 100);	$pCadArray[55] = round($pCadArray[55], 0);
             $pCadArray[60] = ($this -> MathZDC($cadArray[60], $this -> totalCalls) * 100);	$pCadArray[60] = round($pCadArray[60], 0);
             $pCadArray[90] = ($this -> MathZDC($cadArray[90], $this -> totalCalls) * 100);	$pCadArray[90] = round($pCadArray[90], 0);
             $pCadArray[99] = ($this -> MathZDC($cadArray[99], $this -> totalCalls) * 100);	$pCadArray[99] = round($pCadArray[99], 0);
             
             $ApCadArray[0] = ($this -> MathZDC($cadArray[0], $BDansweredCALLS) * 100);	$ApCadArray[0] = round($ApCadArray[0], 0);
             $ApCadArray[5] = ($this -> MathZDC($cadArray[5], $BDansweredCALLS) * 100);	$ApCadArray[5] = round($ApCadArray[5], 0);
             $ApCadArray[10] = ($this -> MathZDC($cadArray[10], $BDansweredCALLS) * 100);	$ApCadArray[10] = round($ApCadArray[10], 0);
             $ApCadArray[15] = ($this -> MathZDC($cadArray[15], $BDansweredCALLS) * 100);	$ApCadArray[15] = round($ApCadArray[15], 0);
             $ApCadArray[20] = ($this -> MathZDC($cadArray[20], $BDansweredCALLS) * 100);	$ApCadArray[20] = round($ApCadArray[20], 0);
             $ApCadArray[25] = ($this -> MathZDC($cadArray[25], $BDansweredCALLS) * 100);	$ApCadArray[25] = round($ApCadArray[25], 0);
             $ApCadArray[30] = ($this -> MathZDC($cadArray[30], $BDansweredCALLS) * 100);	$ApCadArray[30] = round($ApCadArray[30], 0);
             $ApCadArray[35] = ($this -> MathZDC($cadArray[35], $BDansweredCALLS) * 100);	$ApCadArray[35] = round($ApCadArray[35], 0);
             $ApCadArray[40] = ($this -> MathZDC($cadArray[40], $BDansweredCALLS) * 100);	$ApCadArray[40] = round($ApCadArray[40], 0);
             $ApCadArray[45] = ($this -> MathZDC($cadArray[45], $BDansweredCALLS) * 100);	$ApCadArray[45] = round($ApCadArray[45], 0);
             $ApCadArray[50] = ($this -> MathZDC($cadArray[50], $BDansweredCALLS) * 100);	$ApCadArray[50] = round($ApCadArray[50], 0);
             $ApCadArray[55] = ($this -> MathZDC($cadArray[55], $BDansweredCALLS) * 100);	$ApCadArray[55] = round($ApCadArray[55], 0);
             $ApCadArray[60] = ($this -> MathZDC($cadArray[60], $BDansweredCALLS) * 100);	$ApCadArray[60] = round($ApCadArray[60], 0);
             $ApCadArray[90] = ($this -> MathZDC($cadArray[90], $BDansweredCALLS) * 100);	$ApCadArray[90] = round($ApCadArray[90], 0);
             $ApCadArray[99] = ($this -> MathZDC($cadArray[99], $BDansweredCALLS) * 100);	$ApCadArray[99] = round($ApCadArray[99], 0);
         }


         $this->setOutput($this->output, ['CALL ANSWERED TIME AND PERCENT BREAKDOWN IN SECONDS']);
         $this -> setOutput($this -> output,['',0,5,10,15,20,25,30,35,40,45,50,55,60,90,">90","Total"]);
         $this -> setOutput($this -> output,['INTERVAL',$listArray[0],$listArray[5],$listArray[10],$listArray[15],$listArray[20],$listArray[25],$listArray[30],
             $listArray[35],$listArray[40],$listArray[45],$listArray[50],$listArray[55],$listArray[60],$listArray[90],$listArray[99], $BDansweredCALLS]);
         $this -> setOutput($this -> output,['INT %',$padArray[0] . "%",$padArray[5] . "%",$padArray[10] . "%",$padArray[15] . "%",$padArray[20] . "%",$padArray[25] . "%",$padArray[30] . "%",
             $padArray[35] . "%",$padArray[40] . "%",$padArray[45] . "%",$padArray[50] . "%",$padArray[55] . "%",$padArray[60] . "%",$padArray[90] . "%",$padArray[99] . "%", 0]);
         $this -> setOutput($this -> output,['CUMULATIVE',$cadArray[0],$cadArray[5],$cadArray[10],$cadArray[15],$cadArray[20],$cadArray[25],$cadArray[30],
             $cadArray[35],$cadArray[40],$cadArray[45],$cadArray[50],$cadArray[55],$cadArray[60],$cadArray[90],$cadArray[99], $BDansweredCALLS]);
         $this -> setOutput($this -> output,['CUM %',$pCadArray[0] . "%",$pCadArray[5] . "%",$pCadArray[10] . "%",$pCadArray[15] . "%",$pCadArray[20] . "%",$pCadArray[25] . "%",$pCadArray[30] . "%",
             $pCadArray[35] . "%",$pCadArray[40] . "%",$pCadArray[45] . "%",$pCadArray[50] . "%",$pCadArray[55] . "%",$pCadArray[60] . "%",$pCadArray[90] . "%",$pCadArray[99] . "%", 0]);
         $this -> setOutput($this -> output,['CUM ANS %',$ApCadArray[0] . "%",$ApCadArray[5] . "%",$ApCadArray[10] . "%",$ApCadArray[15] . "%",$ApCadArray[20] . "%",$ApCadArray[25] . "%",$ApCadArray[30] . "%",
             $ApCadArray[35] . "%",$ApCadArray[40] . "%",$ApCadArray[45] . "%",$ApCadArray[50] . "%",$ApCadArray[55] . "%",$ApCadArray[60] . "%",$ApCadArray[90] . "%",$ApCadArray[99] . "%", 0]);

         $return["interval"] = $listArray;
         $return["int_percent"] = $padArray;
         $return["cumulative"] = $cadArray;
         $return['cumu_percent'] = $pCadArray;
         $return['cumu_ans_percent'] = $ApCadArray;
         $return['total'] = $BDansweredCALLS;
         return $return;
     }

     public function hangupReasons(){
         $this -> output = "\n";
         $query = \PATHAODB::table("vicidial_closer_log");
         $query->select(\PATHAODB::raw("count(*) as totalData, term_reason"));
         $query->where("call_date", ">=", $this -> dateStart);
         $query->where("call_date", "<=", $this -> dateEnd);
         $query->whereIn("campaign_id", $this->selectedGroups);
         $query->groupBy("term_reason");
         $reasons = $query -> get();
         $reasonArray = [];
         $reasonArray['total'] = 0;

         $this->setOutput($this->output, ['CALL HANGUP REASON STATS']);
         $this -> setOutput($this -> output,['HANGUP REASON',"CALLS"]);
         foreach($reasons as $reason){
             $reasonArray['total'] += $reason -> totalData;
             $reasonArray["list"][] = ["reason" => $reason -> term_reason, "count" => $reason -> totalData];
             $this -> setOutput($this -> output,[$reason -> term_reason, $reason -> totalData]);
         }

         $this -> setOutput($this -> output,["Total:", $reasonArray['total']]);

         return $reasonArray;
     }

     public function getCallStatusStats(){
         $this -> output = "\n";
         $this -> getStatsList();

         $query = \PATHAODB::table("vicidial_closer_log");
         $query->select(\PATHAODB::raw("count(*) as statusCount, status, sum(length_in_sec) as lengthInSec"));
         $query->where("call_date", ">=", $this -> dateStart);
         $query->where("call_date", "<=", $this -> dateEnd);
         $query->whereIn("campaign_id", $this->selectedGroups);
         $query->groupBy("status");

         $result = $query -> get();

         $totalCalls = 0;

         $return = [];
         $this -> setOutput($this -> output,["CALL STATUS STATS"]);
         $this -> setOutput($this -> output,["STATUS","DESCRIPTION","CATEGORY","CALLS","TOTAL TIME","AVG TIME","CALL / HOUR"]);
         foreach($result as $status){

             $totalCalls += $status -> statusCount;
             $STATUSrate =	($this -> MathZDC($status -> statusCount, $this -> MathZDC($this -> totalSeconds, 3600)) );

             $STATUShours =		$this -> sec_convert($status -> lengthInSec,'H');
             $STATUSavg_sec =	$this -> MathZDC($status -> lengthInSec, $status -> statusCount);
             $STATUSavg =		$this -> sec_convert($STATUSavg_sec,'H');
             $status_name = (isset($this -> statsNameArr[$status -> status]) ? $this -> statsNameArr[$status -> status] : "");
             $statcat = (isset($this -> statsCatArr[$status -> status]) ? $this -> statsCatArr[$status -> status] : "");
             $arr['status'] = $status -> status;
             $arr['status_name'] = $status_name;
             $arr['status_cat'] = $statcat;
             $arr['status_count'] = $status -> statusCount;
             $arr['status_hours'] = $STATUShours;
             $arr['status_avg'] = $STATUSavg;
             $arr['status_rate'] = round($STATUSrate, 2);

             $this -> setOutput($this -> output,[$status -> status,$status_name,$statcat,$status -> statusCount,$STATUShours,$STATUSavg,$arr['status_rate']]);

             $return["list"][] = $arr;
         }

         if ($totalCalls < 1)
         {
             $TOTALhours =	'0:00:00';
             $TOTALavg =		'0:00:00';
             $TOTALrate =	'0.00';
         }
         else
         {
             $TOTALrate =	round($this -> MathZDC($totalCalls, $this ->  MathZDC($this -> totalSeconds, 3600) ), 2);
             $TOTALhours =		$this -> sec_convert($this -> totalSeconds,'H');
             $TOTALavg_sec =		$this -> MathZDC($this -> totalSeconds, $totalCalls);
             $TOTALavg =			$this -> sec_convert($TOTALavg_sec,'H');
         }

         $this -> setOutput($this -> output,["TOTAL:","","",$totalCalls,$TOTALhours,$TOTALavg,$TOTALrate]);

         $return['total'] = ["count" => $totalCalls, "hours" => $TOTALhours, "avg" => $TOTALavg, "rate" => $TOTALrate];

        return $return;
     }


     public function getCallInitialQueueStats(){
         $this -> output = "\n";
         $query = \PATHAODB::table("vicidial_closer_log");
         $query->select(\PATHAODB::raw("count(*) as totalCount, queue_position"));
         $query->where("call_date", ">=", $this -> dateStart);
         $query->where("call_date", "<=", $this -> dateEnd);
         $query->whereIn("campaign_id", $this->selectedGroups);
         $query->groupBy("queue_position");

         $result = $query -> get();
         $listArray = [0 => 0,1 => 0,2=> 0,3=>0,4=>0,5=>0,6=>0,7=>0,8=>0,9=>0,10=>0,15=>0,20=>0,25=>0,"25+"=>0];

         foreach($result as $queue){
             $index = 1;
             if($queue -> queue_position > 0 && $queue -> queue_position <= 1){
                 $index = 1;
             }
             elseif($queue -> queue_position > 1 && $queue -> queue_position <= 2){
                 $index = 2;
             }
             elseif($queue -> queue_position > 2 && $queue -> queue_position <= 3){
                 $index = 3;
             }
             elseif($queue -> queue_position > 3 && $queue -> queue_position <= 4){
                 $index = 4;
             }
             elseif($queue -> queue_position > 4 && $queue -> queue_position <= 5){
                 $index = 5;
             }
             elseif($queue -> queue_position > 5 && $queue -> queue_position <= 6){
                 $index = 6;
             }
             elseif($queue -> queue_position > 6 && $queue -> queue_position <= 7){
                 $index = 7;
             }
             elseif($queue -> queue_position > 7 && $queue -> queue_position <= 8){
                 $index = 8;
             }
             elseif($queue -> queue_position > 8 && $queue -> queue_position <= 9){
                 $index = 9;
             }
             elseif($queue -> queue_position > 9 && $queue -> queue_position <= 10){
                 $index = 10;
             }
             elseif($queue -> queue_position > 10 && $queue -> queue_position <= 15){
                 $index = 15;
             }
             elseif($queue -> queue_position > 15 && $queue -> queue_position <= 20){
                 $index = 20;
             }
             elseif($queue -> queue_position > 20 && $queue -> queue_position <= 25){
                 $index = 25;
             }
             else{
                 $index = "25+";
             }

             $listArray[$index] += $queue -> totalCount;
         }

         $this->setOutput($this->output, ['CALL INITIAL QUEUE POSITION BREAKDOWN']);
         $this -> setOutput($this -> output,[0,1,2,3,4,5,6,7,8,9,10,15,20,25,">25","Total"]);
         $this -> setOutput($this -> output,[$listArray[0],$listArray[1],$listArray[2],$listArray[3],$listArray[4],$listArray[5],$listArray[6],$listArray[7],$listArray[8],
             $listArray[9],$listArray[10],$listArray[15],$listArray[20],$listArray[25],$listArray["25+"],$this -> totalCalls]);
         return $listArray;
     }

     public function getAgentStats(){
         $this -> output = "\n";
         $query = \PATHAODB::table("vicidial_closer_log");
         $query->select(\PATHAODB::raw("vicidial_closer_log.user,full_name,count(*) as totalCount,sum(length_in_sec) as lengthSum,avg(length_in_sec) as lengthAvg"));
         $query->where("call_date", ">=", $this -> dateStart);
         $query->where("call_date", "<=", $this -> dateEnd);
         $query->whereIn("campaign_id", $this->selectedGroups);
         $query->whereNotNull("vicidial_closer_log.user");
         $query->whereNotNull("length_in_sec");
         $query->join('vicidial_users', 'vicidial_users.user', '=', 'vicidial_closer_log.user');
         $query->groupBy("vicidial_closer_log.user");

         $result = $query -> get();
         $return = [];
         $totalAgents = 0;
         $this->setOutput($this->output, ['AGENT STATS']);
         $this->setOutput($this->output, ['AGENT','CALLS','TIME H:M:S','AVERAGE']);
         foreach($result as $agent){
             $totalAgents++;
             $arr['user'] = $agent -> user;
             $arr['name'] = $agent -> full_name;
             $arr['call_count'] = $agent -> totalCount;
             $arr['total_length'] = $this -> sec_convert($agent -> lengthSum, "H");
             $arr['avg_length'] = $this -> sec_convert($agent -> lengthAvg, "H");

             $return['list'][] = $arr;

             $this->setOutput($this->output, [$arr['user'] . " - " . $arr['name'],$arr['call_count'],$arr['total_length'],$arr['avg_length']]);

         }

         $TOTavg = $this -> MathZDC($this -> totalSeconds, $this -> totalCalls);
         $TOTavg =	$this -> sec_convert($TOTavg,'H');


         $return['total'] = ["agents" => $totalAgents,"calls" => $this -> totalCalls, "time" => $this -> sec_convert($this -> totalSeconds,'H'), "avg_time" => $TOTavg];

         $this->setOutput($this->output, ["Total Agents: " . $totalAgents,$this -> totalCalls,$return['total']['time'],$return['total']['avg_time']]);

         return $return;
     }

     public function getHourlyBreakdown(){
         $query = \PATHAODB::table("vicidial_closer_log");
         //$query->select(["status","queue_seconds","call_date"]);
         $query -> select(\PATHAODB::raw("status,length_in_sec,queue_seconds,call_date,phone_number,campaign_id"));
         $query->where("call_date", ">=", $this -> dateStart);
         $query->where("call_date", "<=", $this -> dateEnd);
         $query->whereIn("campaign_id", $this->selectedGroups);

         $results = $query -> get();

         $queueList = $dropList = $dailyList = array(
             "00" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "01" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "02" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "03" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "04" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "05" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "06" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "07" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "08" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "09" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "10" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "11" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "12" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "13" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "14" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "15" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "16" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "17" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "18" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "19" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "20" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "21" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "22" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
             "23" => ["00" => 0, "15" => 0, "30" => 0, "45" => 0],
         );

         $hourData = ["total_call" => 0,"total_time" => 0, "total_ans" => 0, "total_queue" => 0, "total_abandoned" => 0, "max_queue" => 0];

         $hour24 = array(
             "00" => $hourData,
             "01" => $hourData,
             "02" => $hourData,
             "03" => $hourData,
             "04" => $hourData,
             "05" => $hourData,
             "06" => $hourData,
             "07" => $hourData,
             "08" => $hourData,
             "09" => $hourData,
             "10" => $hourData,
             "11" => $hourData,
             "12" => $hourData,
             "13" => $hourData,
             "14" => $hourData,
             "15" => $hourData,
             "16" => $hourData,
             "17" => $hourData,
             "18" => $hourData,
             "19" => $hourData,
             "20" => $hourData,
             "21" => $hourData,
             "22" => $hourData,
             "23" => $hourData,
         );
         $groupList = [];

         foreach($this -> selectedGroups as $groupID){
             $groupList[$groupID]["hourly"] = $hour24;
             $groupList[$groupID]["total"] = $hourData;
         }


         foreach($queueList as $n=>$k){
             foreach($k as $m=>$j){
                 $queueList[$n][$m] = [0 => 0,5 => 0,10=> 0,15=>0,20=>0,25=>0,30=>0,35=>0,40=>0,45=>0,50=>0,55=>0,60=>0,90=>0,99=>0];
             }
         }
         foreach($results as $report){
             $this -> output = "";
             $date = new \DateTime($report -> call_date);
             $hour = $date -> format("H");
             $minutes = (int) $date -> format("i");
             $minutesIndex = "00";

             //hourly report
             $groupList[$report -> campaign_id]['hourly'][$hour]["total_call"]++;
             $groupList[$report -> campaign_id]['hourly'][$hour]["total_time"] += $report -> length_in_sec;
             $groupList[$report -> campaign_id]['hourly'][$hour]["total_queue"] += $report -> queue_seconds;
             if($report -> queue_seconds > $groupList[$report -> campaign_id]['hourly'][$hour]["max_queue"]){
                 $groupList[$report -> campaign_id]['hourly'][$hour]["max_queue"] = $report -> queue_seconds;
             }
             if($report -> status == "DROP")
                 $groupList[$report -> campaign_id]['hourly'][$hour]["total_abandoned"]++;
             else
                 $groupList[$report -> campaign_id]['hourly'][$hour]["total_ans"]++;

             //hourly report

             if($minutes >= 0 && $minutes < 15){
                 $minutesIndex = "00";
             }
             elseif($minutes >= 15 && $minutes < 30){
                 $minutesIndex = "15";
             }
             elseif($minutes >= 30 && $minutes < 45){
                 $minutesIndex = "30";
             }else{
                 $minutesIndex = "45";
             }

             if ($report -> queue_seconds == 0) {
                 $secIndex = 0;
             }
             if ( ($report -> queue_seconds > 0) and ($report -> queue_seconds <= 5) ) {
                 $secIndex = 5;
             }
             if ( ($report -> queue_seconds > 5) and ($report -> queue_seconds <= 10) ) {
                 $secIndex = 10;
             }
             if ( ($report -> queue_seconds > 10) and ($report -> queue_seconds <= 15) ) {
                 $secIndex = 15;
             }
             if ( ($report -> queue_seconds > 15) and ($report -> queue_seconds <= 20) ) {
                 $secIndex = 20;
             }
             if ( ($report -> queue_seconds > 20) and ($report -> queue_seconds <= 25) ) {
                 $secIndex = 25;
             }
             if ( ($report -> queue_seconds > 25) and ($report -> queue_seconds <= 30) ) {
                 $secIndex = 30;
             }
             if ( ($report -> queue_seconds > 30) and ($report -> queue_seconds <= 35) ) {
                 $secIndex = 35;
             }
             if ( ($report -> queue_seconds > 35) and ($report -> queue_seconds <= 40) ) {
                 $secIndex = 40;
             }
             if ( ($report -> queue_seconds > 40) and ($report -> queue_seconds <= 45) ) {
                 $secIndex = 45;
             }
             if ( ($report -> queue_seconds > 45) and ($report -> queue_seconds <= 50) ) {
                 $secIndex = 50;
             }
             if ( ($report -> queue_seconds > 50) and ($report -> queue_seconds <= 55) ) {
                 $secIndex = 55;
             }
             if ( ($report -> queue_seconds > 55) and ($report -> queue_seconds <= 60) ) {
                 $secIndex = 60;
             }
             if ( ($report -> queue_seconds > 60) and ($report -> queue_seconds <= 90) ) {
                 $secIndex = 90;
             }
             if ($report -> queue_seconds > 90) {
                 $secIndex = 99;
             }

             $dailyList[$hour][$minutesIndex]++;

             if($report -> status == "DROP")
                 $dropList[$hour][$minutesIndex]++;
             if(!in_array($report -> status,["DROP","XDROP","HXFER","QVMAIL","HOLDTO","LIVE","QUEUE","TIMEOT","AFTHRS","NANQUE","INBND","MAXCAL"])){
                 $queueList[$hour][$minutesIndex][$secIndex]++;
             }
         }
         
         //hourly stat sort
         $hourlyExG = '"MULTI-GROUP BREAKDOWN:"' . Chr(10) . '"In-Group","Total Calls","Total Answered","Total Talk","Avg. Talk","Total Queue Time","Avg. Queue Time","Max Queue Time","Total Dropped"' . Chr(10);
         $hourlyEx = "";
         foreach($groupList as $group => $data){
             $hourlyEx .= '"'.$group.' HOURLY BREAKDOWN:"' . Chr(10) . '"Hour","Total Calls","Total Answered","Total Talk","Avg. Talk","Total Queue Time","Avg. Queue Time","Max Queue Time","Total Dropped"' . Chr(10);

             foreach($data['hourly'] as $hour => $hData){
                 if((int) $hour < $this -> startHour || (int) $hour > $this -> endHour){
                     unset($groupList[$group]['hourly'][$hour]);
                     continue;
                 }
                 $groupList[$group]['total']["total_call"] += $hData["total_call"];
                 $groupList[$group]['total']["total_time"] += $hData["total_time"];
                 $groupList[$group]['total']["total_ans"] += $hData["total_ans"];
                 $groupList[$group]['total']["total_queue"] += $hData["total_queue"];
                 $groupList[$group]['total']["total_abandoned"] += $hData["total_abandoned"];
                 if($hData["max_queue"] > $groupList[$group]['total']["max_queue"]){
                     $groupList[$group]['total']["max_queue"] = $hData["max_queue"];
                 }

                 $groupList[$group]['hourly'][$hour]["talk_avg"] = $this -> sec_convert($this -> MathZDC($hData["total_time"], $hData["total_ans"]), "H");
                 $groupList[$group]['hourly'][$hour]["queue_avg"] = $this -> sec_convert($this -> MathZDC($hData["total_queue"], $hData["total_call"]), "H");
                 $groupList[$group]['hourly'][$hour]["total_time"] = $this -> sec_convert($hData["total_time"],"H");
                 $groupList[$group]['hourly'][$hour]["total_queue"] = $this -> sec_convert($hData["total_queue"],"H");
                 $groupList[$group]['hourly'][$hour]["max_queue"] = $this -> sec_convert($hData["max_queue"],"H");

                 $hourlyEx .= '"'.$hour.':00","'.$hData["total_call"].'","'.$hData["total_ans"].'","'.$groupList[$group]['hourly'][$hour]["total_time"].'","'.$groupList[$group]['hourly'][$hour]["talk_avg"].'","'.$groupList[$group]['hourly'][$hour]["total_queue"].'","'.$groupList[$group]['hourly'][$hour]["queue_avg"].'","'.$groupList[$group]['hourly'][$hour]["max_queue"].'","'.$groupList[$group]['hourly'][$hour]["total_abandoned"].'"' . Chr(10);
             }


             $groupList[$group]['total']["talk_avg"] = $this -> sec_convert($this -> MathZDC($groupList[$group]['total']["total_time"], $groupList[$group]['total']["total_ans"]), "H");
             $groupList[$group]['total']["queue_avg"] = $this -> sec_convert($this -> MathZDC($groupList[$group]['total']["total_queue"], $groupList[$group]['total']["total_call"]), "H");
             $groupList[$group]['total']["total_time"] = $this -> sec_convert($groupList[$group]['total']["total_time"],"H");
             $groupList[$group]['total']["total_queue"] = $this -> sec_convert($groupList[$group]['total']["total_queue"],"H");
             $groupList[$group]['total']["max_queue"] = $this -> sec_convert($groupList[$group]['total']["max_queue"],"H");

             $hourlyEx .= '"Total","'.$groupList[$group]['total']["total_call"].'","'.$groupList[$group]['total']["total_ans"].'","'.$groupList[$group]['total']["total_time"].'","'.$groupList[$group]['total']["talk_avg"].'","'.$groupList[$group]['total']["total_queue"].'","'.$groupList[$group]['total']["queue_avg"].'","'.$groupList[$group]['total']["max_queue"].'","'.$groupList[$group]['total']["total_abandoned"].'"' . Chr(10);
             $hourlyExG .= '"'.$group.'","'.$groupList[$group]['total']["total_call"].'","'.$groupList[$group]['total']["total_ans"].'","'.$groupList[$group]['total']["total_time"].'","'.$groupList[$group]['total']["talk_avg"].'","'.$groupList[$group]['total']["total_queue"].'","'.$groupList[$group]['total']["queue_avg"].'","'.$groupList[$group]['total']["max_queue"].'","'.$groupList[$group]['total']["total_abandoned"].'"' . Chr(10);

             $hourlyEx .= Chr(10);
         }

         //drop list output
         $this -> setOutput($this -> dropOutput,["TIME STATS"]);
         $this -> setOutput($this -> dropOutput,["HOUR","DROPS","TOTAL"]);
         foreach($dailyList as $hour => $hourlyData){
             foreach($hourlyData as $minute => $minutesData){
                 $this -> setOutput($this -> dropOutput,["$hour:$minute",$dropList[$hour][$minute],"$minutesData"]);
             }
         }
         //

         //queue list output
         $this -> setOutput($this -> output,["CALL ANSWERED TIME BREAKDOWN IN SECONDS"]);
         $this -> setOutput($this -> output,['Hour',0,5,10,15,20,25,30,35,40,45,50,55,60,90,">90","Total"]);
         $totalQueueCount = 0;
         foreach($queueList as $hour=>$queue){
             foreach($queue as $minute => $data){
                 $qArr = [];
                 $totalQueue = 0;
                 $qArr[] = "$hour:$minute";
                 foreach($data as $call){
                     $totalQueue += $call;
                     $qArr[] = $call;
                 }
                 $qArr[] = $totalQueue;
                 $this -> setOutput($this -> output,$qArr);
                 $totalQueueCount += $totalQueue;
             }
         }
         $this -> setOutput($this -> output,['Total:',"","","","","","","","","","","","","","","",$totalQueueCount]);

         $this -> hourlyOutput = $hourlyExG . Chr(10) . $hourlyEx;
         return ["all" => $dailyList, "drop" => $dropList,"queue" => $queueList,"hourly" => $groupList];
     }

     private function getStatsList(){
         $query = \PATHAODB::table("vicidial_statuses");
         $query->select(["status","status_name","human_answered","category"]);
         $result = $query -> get();
         foreach($result as $stats){
             $this -> statsNameArr[$stats -> status] = $stats -> status_name;
             $this -> statsCatArr[$stats -> status] = $stats -> category;
         }

         $query = \PATHAODB::table("vicidial_campaign_statuses");
         $query->select(["status","status_name","human_answered","category"]);
         $result = $query -> get();
         foreach($result as $stats){
             $this -> statsNameArr[$stats -> status] = $stats -> status_name;
             $this -> statsCatArr[$stats -> status] = $stats -> category;
         }
     }
    private function rawOutput(&$obj)
    {
        $queryObj = $obj->getQuery();
        die($queryObj->getRawSql());
    }

    private function sec_convert($sec,$precision)
    {
        $sec = round($sec,0);

        if ($sec < 1)
        {
            if ($precision == 'HF' || $precision == 'H')
            {return "0:00:00";}
            else
            {
                if ($precision == 'S')
                {return "0";}
                else
                {return "0:00";}

            }
        }
        else
        {
            if ($precision == 'HF')
            {$precision='H';}
            else
            {
                # if ( ($sec < 3600) and ($precision != 'S') ) {$precision='M';}
            }

            if ($precision == 'H')
            {
                $Fhours_H =	$this -> MathZDC($sec, 3600);
                $Fhours_H_int = floor($Fhours_H);
                $Fhours_H_int = intval("$Fhours_H_int");
                $Fhours_M = ($Fhours_H - $Fhours_H_int);
                $Fhours_M = ($Fhours_M * 60);
                $Fhours_M_int = floor($Fhours_M);
                $Fhours_M_int = intval("$Fhours_M_int");
                $Fhours_S = ($Fhours_M - $Fhours_M_int);
                $Fhours_S = ($Fhours_S * 60);
                $Fhours_S = round($Fhours_S, 0);
                if ($Fhours_S < 10) {$Fhours_S = "0$Fhours_S";}
                if ($Fhours_M_int < 10) {$Fhours_M_int = "0$Fhours_M_int";}
                $Ftime = "$Fhours_H_int:$Fhours_M_int:$Fhours_S";
            }
            if ($precision == 'M')
            {
                $Fminutes_M = $this -> MathZDC($sec, 60);
                $Fminutes_M_int = floor($Fminutes_M);
                $Fminutes_M_int = intval("$Fminutes_M_int");
                $Fminutes_S = ($Fminutes_M - $Fminutes_M_int);
                $Fminutes_S = ($Fminutes_S * 60);
                $Fminutes_S = round($Fminutes_S, 0);
                if ($Fminutes_S < 10) {$Fminutes_S = "0$Fminutes_S";}
                $Ftime = "$Fminutes_M_int:$Fminutes_S";
            }
            if ($precision == 'S')
            {
                $Ftime = $sec;
            }
            return "$Ftime";
        }
    }

    private function MathZDC($dividend, $divisor, $quotient = 0)
    {
        if ($divisor == 0) {
            return $quotient;
        } else if ($dividend == 0) {
            return 0;
        } else {
            return ($dividend / $divisor);
        }
    }


}