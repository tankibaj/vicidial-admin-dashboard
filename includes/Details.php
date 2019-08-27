<?php
/**
 * Created by PhpStorm.
 * User: elomelo
 * Date: 20/5/18
 * Time: 12:08 PM
 */

namespace Common;


class Details
{
    var $selectedGroups;
    var $selectedCampaign;
    var $dateStart;
    var $dateEnd;
    var $returnAllUserGroup = false;
    var $returnAllCampaign = false;
    var $availStatus = [];
    var $output = "";
    public function __construct($selectedCampaign,$dateStart,$dateEnd)
    {
        $this -> selectedCampaign = $selectedCampaign;
        $this -> dateStart = $dateStart . " 00:00:00";
        $this -> dateEnd = $dateEnd . " 23:59:59";
        $this -> users = [];

        if (empty($this->selectedGroups) || in_array("ALL", $this->selectedGroups)) {
            $this -> returnAllUserGroup = true;
        }
        if (empty($this->selectedCampaign) || in_array("ALL", $this->selectedCampaign)) {
            $this -> returnAllCampaign = true;
            $this -> listCampaigns();
        }
        //$this -> listCampaigns();
    }

    private function listCampaigns(){
        $query = \PATHAODB::table("vicidial_campaigns");
        $query -> select(["campaign_id"]);
        $result = $query -> get();
        foreach($result as $cid){
            $this -> selectedCampaign[] = $cid -> campaign_id;
        }
    }
    public function downloadHeader(){
        return '"Inbound Call Status:","'.implode(",",$this -> selectedGroups).'","'. $this -> dateStart .' - '. $this -> dateEnd .'"' . Chr(10);
    }

    private function listStatusData(){
        $query = \PATHAODB::table("vicidial_agent_log");
        $query->select(\PATHAODB::raw("user,sum(pause_sec) as sum_pause_sec,sub_status,sum(wait_sec + talk_sec + dispo_sec) as sum_total"));

        $query -> where("event_time","<=", $this -> dateEnd);
        $query -> where("event_time",">=", $this -> dateStart);
        $query -> where("pause_sec","<", 65000);

//        if(!$this -> returnAllUserGroup){
//            $query->whereIn("vicidial_agent_log.user_group", $this->selectedGroups);
//        }
        $query->whereIn("vicidial_agent_log.campaign_id", $this-> selectedCampaign);
        $query->groupBy("user");
        $query->groupBy("sub_status");
        $query -> orderBy("user","DESC");
        $query -> orderBy("sub_status","DESC");
        $query -> limit(500000);
        $agents = $query -> get();
        foreach($agents as $agent){
            if(!isset($this -> users[$agent -> user]['pausecode'][$agent -> sub_status]))
                $this -> users[$agent -> user]['pausecode'][$agent -> sub_status] = 0;
            $this -> users[$agent -> user]['pausecode'][$agent -> sub_status] += $agent -> sum_pause_sec;
            $this -> availStatus[] = $agent -> sub_status;
        }
        $this -> availStatus = array_unique($this -> availStatus);
    }
    public function listData(){
        $this -> listStatusData();
        $query = \PATHAODB::table("vicidial_agent_log");
        $query->select(\PATHAODB::raw("count(*) as calls,sum(talk_sec) as talk,full_name,vicidial_users.user,sum(pause_sec) as sum_pause_sec,sum(wait_sec) as sum_wait_sec,
        sum(dispo_sec) as sum_dispo_sec,status,sum(dead_sec) as sum_dead_sec ,sum(wait_sec + talk_sec + dispo_sec) as sum_total"));
        $query->join('vicidial_users', function($table)
        {
            $table->on('vicidial_users.user', '=', 'vicidial_agent_log.user');
        });

        $query -> where("event_time","<=", $this -> dateEnd);
        $query -> where("event_time",">=", $this -> dateStart);
        $query -> where("pause_sec","<", 65000);
        $query -> where("wait_sec","<", 65000);
        $query -> where("talk_sec","<", 65000);
        $query -> where("dispo_sec","<", 65000);

//        if(!$this -> returnAllUserGroup){
//            $query->whereIn("vicidial_agent_log.user_group", $this->selectedGroups);
//        }
        $query->whereIn("vicidial_agent_log.campaign_id", $this-> selectedCampaign);

        $query->groupBy("user");
        $query->groupBy("full_name");
        $query->groupBy("status");
        $query -> orderBy("user","DESC");
        $query -> orderBy("full_name","DESC");
        $query -> orderBy("status","DESC");
        $query -> limit(500000);
        $agents = $query -> get();

        foreach($agents as $agent){
            $this -> users[$agent -> user]['name'] = $agent -> full_name;
            if(!isset($this -> users[$agent -> user]['dead_sec'])){
                $this -> users[$agent -> user]['dead_sec'] = $agent -> sum_dead_sec;
            }else{
                $this -> users[$agent -> user]['dead_sec'] += $agent -> sum_dead_sec;
            }
            if(!isset($this -> users[$agent -> user]['dispo_sec'])){
                $this -> users[$agent -> user]['dispo_sec'] = $agent -> sum_dispo_sec;
            }else{
                $this -> users[$agent -> user]['dispo_sec'] += $agent -> sum_dispo_sec;
            }
            if(!isset($this -> users[$agent -> user]['wait_sec'])){
                $this -> users[$agent -> user]['wait_sec'] = $agent -> sum_wait_sec;
            }else{
                $this -> users[$agent -> user]['wait_sec'] += $agent -> sum_wait_sec;
            }
            if(!isset($this -> users[$agent -> user]['pause_sec'])){
                $this -> users[$agent -> user]['pause_sec'] = $agent -> sum_pause_sec;
            }else{
                $this -> users[$agent -> user]['pause_sec'] += $agent -> sum_pause_sec;
            }
            if(!isset($this -> users[$agent -> user]['sum_total'])){
                $this -> users[$agent -> user]['sum_total'] = $agent -> sum_total;
            }else{
                $this -> users[$agent -> user]['sum_total'] += $agent -> sum_total;
            }

            if(!isset($this -> users[$agent -> user]['talk_sec'])){
                $this -> users[$agent -> user]['talk_sec'] = $agent -> talk;
            }else{
                $this -> users[$agent -> user]['talk_sec'] += $agent -> talk;
            }

            if(!isset($this -> users[$agent -> user]['status_count']))
                $this -> users[$agent -> user]['status_count'] = 0;

            if(!isset($this -> users[$agent -> user]['total_sec']))
                $this -> users[$agent -> user]['total_sec'] = 0;

            $this -> users[$agent -> user]['total_sec'] += $agent -> sum_pause_sec + $agent -> sum_total;
            if(!isset($this -> users[$agent -> user]['calls'])){
                $this -> users[$agent -> user]['calls'] = 0;
            }

            if(in_array($agent -> status,['A','N'])){
                $this -> users[$agent -> user]['status_count'] += $agent -> calls;
                $this -> users[$agent -> user]['calls'] += $agent -> calls;

            }
        }

        foreach($this -> users as $k => $user){
            $this -> users[$k]['pause_sec'] = $this -> sec_convert($user['pause_sec'],"H");
            $this -> users[$k]['total_sec'] = $this -> sec_convert($user['total_sec'],"H");
            $this -> users[$k]['sum_total'] = $this -> sec_convert($user['sum_total'],"H");
            $this -> users[$k]['talk_sec'] = $this -> sec_convert($user['talk_sec'],"H");
            $this -> users[$k]['wait_sec'] = $this -> sec_convert($user['wait_sec'],"H");
            $this -> users[$k]['dispo_sec'] = $this -> sec_convert($user['dispo_sec'],"H");
            $loginTime = $this -> getUserLoginTime($k);
            $logoutTime = $this -> getUserLogoutTime($k);
            if(!empty($loginTime -> event_epoch))
                $this -> users[$k]['login_time'] = date("H:i:s",$loginTime -> event_epoch);
            else
                $this -> users[$k]['login_time'] = "00:00:00";

            if(!empty($logoutTime -> event_epoch))
                $this -> users[$k]['logout_time'] = date("H:i:s",$logoutTime -> event_epoch);
            else
                $this -> users[$k]['logout_time'] = "00:00:00";

            foreach($this -> users[$k]['pausecode'] as $j => $v){
                $this -> users[$k]['pausecode'][$j] = $this -> sec_convert($this -> users[$k]['pausecode'][$j],"H");
            }
            //$this -> users[$k]['status'] = array_unique($this -> users[$k]['status']);
        }

        uasort($this -> users, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

    }

    private function getUserLoginTime($userid){
        $query = \PATHAODB::table("vicidial_user_log");
        $query -> select(["event_date","event_epoch"]);
        $query -> where("user","=",$userid);
        $query -> where("event_date",">=",$this -> dateStart);
        $query -> where("event_date","<=",$this -> dateEnd);
        $query -> where("user","=",$userid);
        $query -> where("event","=","LOGIN");
        $query -> orderBy("event_date","ASC");
        $query -> limit(1);
        $result = $query -> first();
        return $result;
    }
    private function getUserLogoutTime($userid){
        $query = \PATHAODB::table("vicidial_user_log");
        $query -> select(["event_date","event_epoch"]);
        $query -> where("user","=",$userid);
        $query -> where("event_date",">=",$this -> dateStart);
        $query -> where("event_date","<=",$this -> dateEnd);
        $query -> where("user","=",$userid);
        $query -> where("event","=","LOGOUT");
        $query -> orderBy("event_date","DESC");
        $query -> limit(1);
        $result = $query -> first();
        return $result;
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

    private function rawOutput(&$obj)
    {
        $queryObj = $obj->getQuery();
        die($queryObj->getRawSql());
    }
}