<?php
/**
 * Created by PhpStorm.
 * User: elomelo
 * Date: 28/4/18
 * Time: 7:12 AM
 */

namespace Common;


class Realtime
{

    var $isInbound;
    var $phone;
    var $selectedCampaign;
    var $selectedGroup;
    var $closerCampaign;
    var $allInboundCampaign;
    var $miniStatsArray;
    var $isVSCAT = false;
    var $returnAllActive = false;
    var $returnAllUserGroup = false;
    var $box_agentonlycount=0;
    var $box_out_total=0;
    var $box_out_ring=0;
    var $box_out_live=0;
    var $box_in_ivr=0;
    var $box_D_active_calls=0;
    var $box_agent_incall=0;
    var $box_agent_ready=0;
    var $box_agent_paused=0;
    var $box_agent_dispo=0;
    var $box_agent_dead=0;
    var $box_agent_total=0;
    var $box_ring_agents=0;
    var $vcCustPhonesArray = [];
    var $vcCallerIDs = [];
    var $vcAgentsArray = [];

    var $vcWaitingList = [];

    function __construct($isInbound, $phone, $selectedCampaign, $selectedGroup)
    {
        $this->isInbound = $isInbound;
        $this->phone = $phone;
        $this->selectedCampaign = $selectedCampaign;
        $this->selectedGroup = $selectedGroup;

        $this->initData();
    }

    public function initData()
    {
        if (empty($this->selectedCampaign) || in_array("ALL", $this->selectedCampaign)) {
            $this->getAvailableCampaigns();
            $this->returnAllActive = true;
        }

        if (empty($this->selectedGroup) || in_array("ALL", $this->selectedGroup)) {
            $this -> returnAllUserGroup = true;
        }

        if ($this->isInbound != "NO")
            $this->listCloserCampaigns();
        if ($this->isInbound == "NO")
            $this->listInboundGroup();
    }


    public function getAgentPaused()
    {
        $query = \PATHAODB::table("vicidial_campaigns");
        $query->select(\PATHAODB::raw("count(campaign_id) as totalPaused"));
        $query->where("agent_pause_codes_active", "!=", "N");

        if (!$this->returnAllActive && $this->isInbound == "YES") {
            $query->whereIn("campaign_id", array_merge($this->selectedCampaign, $this->closerCampaign));
        } else {
            $query->whereIn("campaign_id", $this->selectedCampaign);
        }

        $result = $query->first();
        return $result->totalPaused;
    }

    private function agentNonPaused()
    {
        $query = \PATHAODB::table("vicidial_campaign_stats");
        $query->select(["agent_non_pause_sec"]);
        $query->whereIn("campaign_id", $this->selectedCampaign);

        $result = $query->first();

        return $result->agent_non_pause_sec;

    }

    private function rawOutput(&$obj)
    {
        $queryObj = $obj->getQuery();
        die($queryObj->getRawSql());
    }

    private function listCloserCampaigns()
    {
//        $query = \PATHAODB::table("vicidial_campaigns");
//        $query->select(["closer_campaigns"]);
//        $query->whereIn("campaign_id", $this->selectedCampaign);
//        $query->where("active", "=", "Y");
//
//        $result = $query->get();
//        foreach ($result as $cCamp) {
//            $cCamp->closer_campaigns = preg_replace("/^ | -$/", "", $cCamp->closer_campaigns);
//            $this->closerCampaign[] = $cCamp->closer_campaigns;
//        }
        $inbound = getInboundGroup(true);
        foreach($inbound as $closer){
            $this->closerCampaign[] = $closer -> group_id;
        }
    }

    private function listInboundGroup()
    {
        $query = \PATHAODB::table("vicidial_inbound_groups");
        $query->select(["group_id"]);

        $result = $query->get();
        foreach ($result as $cCamp) {
            $this->allInboundCampaign[] = $cCamp->group_id;
        }
    }

    private function getAvailableCampaigns()
    {
        $query = \PATHAODB::table("vicidial_campaigns");
        $query->select(["campaign_id", "campaign_name"]);
        $query->where("active", "=", "Y");

        $result = $query->get();
        foreach ($result as $cCamp) {
            $this->selectedCampaign[] = $cCamp->campaign_id;
        }
    }

    private function getTrunkShortage()
    {
        $query = \PATHAODB::table("vicidial_campaign_server_stats");
        $query->select(\PATHAODB::raw("sum(local_trunk_shortage) as balanceShort"));
        $query->whereIn("campaign_id", $this->selectedCampaign);
        $result = $query->first();
        return $result->balanceShort;
    }

    private function getHopperCount()
    {
        $query = \PATHAODB::table("vicidial_hopper");
        $query->select(\PATHAODB::raw("count(*) as totalHopper"));
        $query->whereIn("campaign_id", $this->selectedCampaign);
        $result = $query->first();
        return $result->totalHopper;
    }

    private function getListMix()
    {
        $query = \PATHAODB::table("vicidial_campaigns_list_mix");
        $query->select(["vcl_id"]);
        $query->whereIn("campaign_id", $this->selectedCampaign);
        $query->limit(1);
        $result = $query->first();
        return $result->vcl_id;
    }


    private function inboundOnlyMiniStats()
    {
        $selects[] = "sum(calls_today) as sum_calls_today";
        $selects[] = "sum(drops_today) as sum_drops_today";
        $selects[] = "sum(answers_today) as sum_answers_today";

        if ($this->isVSCAT) {
            $selects[] = "max(status_category_1) as max_status_category_1";
            $selects[] = "sum(status_category_count_1) as sum_status_category_count_1";
            $selects[] = "max(status_category_2) as max_status_category_2";
            $selects[] = "sum(status_category_count_2) as sum_status_category_count_2";
            $selects[] = "max(status_category_3) as max_status_category_3";
            $selects[] = "sum(status_category_count_3) as sum_status_category_count_3";
            $selects[] = "max(status_category_4) as max_status_category_4";
            $selects[] = "sum(status_category_count_4) as sum_status_category_count_4";
        }

        $selects[] = "sum(hold_sec_stat_one) as sum_hold_sec_stat_one";
        $selects[] = "sum(hold_sec_stat_two) as sum_hold_sec_stat_two";
        $selects[] = "sum(hold_sec_answer_calls) as sum_hold_sec_answer_calls";
        $selects[] = "sum(hold_sec_drop_calls) as sum_hold_sec_drop_calls";
        $selects[] = "sum(hold_sec_queue_calls) as sum_hold_sec_queue_calls";

        $query = \PATHAODB::table("vicidial_campaign_stats");
        $query->select(\PATHAODB::raw(implode($selects, ", ")));

        $query->whereIn("campaign_id", $this->closerCampaign);

        $result = $query->first();

        $this->miniStatsArray['calls_today'] = $result->sum_calls_today;
        $this->miniStatsArray['drops_today'] = round($result->sum_drops_today);
        $this->miniStatsArray['answers_today'] = $result->sum_answers_today;
        $this->miniStatsArray['outbound_today'] = $this->miniStatsArray['calls_today'] - ($this->miniStatsArray['drops_today'] + $this->miniStatsArray['answers_today']);

        if ($this->isVSCAT) {
            $this->miniStatsArray['max_status_category_1'] = $result->max_status_category_1;
            $this->miniStatsArray['sum_status_category_count_1'] = $result->sum_status_category_count_1;
            $this->miniStatsArray['max_status_category_2'] = $result->max_status_category_2;
            $this->miniStatsArray['sum_status_category_count_2'] = $result->sum_status_category_count_2;
            $this->miniStatsArray['max_status_category_3'] = $result->max_status_category_3;
            $this->miniStatsArray['sum_status_category_count_3'] = $result->sum_status_category_count_3;
            $this->miniStatsArray['max_status_category_4'] = $result->max_status_category_4;
            $this->miniStatsArray['sum_status_category_count_4'] = $result->sum_status_category_count_4;
        }


        $this->miniStatsArray['drop_percent'] = sprintf("%01.2f",
            round(($this->MathZDC($this->miniStatsArray['drops_today'], $this->miniStatsArray['answers_today']) * 100), 2)
        );


        $this->miniStatsArray['avg_hold_queue'] = round($this->MathZDC($result->sum_hold_sec_queue_calls, $this->miniStatsArray['calls_today']), 0);
        $this->miniStatsArray['avg_drop_queue'] = round($this->MathZDC($result->sum_hold_sec_drop_calls, $this->miniStatsArray['drops_today']), 0);

        $this->miniStatsArray['tma1'] = sprintf("%01.2f",
            round(($this->MathZDC($result->sum_hold_sec_stat_one, $this->miniStatsArray['answers_today']) * 100), 2)
        );

        $this->miniStatsArray['tma2'] = sprintf("%01.2f",
            round(($this->MathZDC($result->sum_hold_sec_stat_two, $this->miniStatsArray['answers_today']) * 100), 2)
        );

        $this->miniStatsArray['avg_hold_answered'] = round($this->MathZDC($result->sum_hold_sec_answer_calls, $this->miniStatsArray['answers_today']), 0);

        $this->miniStatsArray['avg_answer_agent_nonpaused'] = sprintf("%01.2f",
            round(($this->MathZDC($this->miniStatsArray['answers_today'], $this->agentNonPaused()) * 60), 2)
        );

    }

    public function prettyPrint()
    {

    }

    private function completeMiniStats()
    {

        $selects[] = "avg(auto_dial_level) as avg_auto_dial_level";

        $selects[] = "min(dial_status_a) as min_dial_status_a";
        $selects[] = "min(dial_status_b) as min_dial_status_b";
        $selects[] = "min(dial_status_c) as min_dial_status_c";
        $selects[] = "min(dial_status_d) as min_dial_status_d";
        $selects[] = "min(dial_status_e) as min_dial_status_e";

        $selects[] = "min(lead_order) as min_lead_order";
        $selects[] = "min(lead_filter_id) as min_lead_filter_id";
        $selects[] = "sum(hopper_level) as sum_hopper_level";
        $selects[] = "min(dial_method) as min_dial_method";

        $selects[] = "avg(adaptive_maximum_level) as avg_adaptive_maximum_level";
        $selects[] = "avg(adaptive_dropped_percentage) as avg_adaptive_dropped_percentage";
        $selects[] = "avg(adaptive_dl_diff_target) as avg_adaptive_dl_diff_target";
        $selects[] = "avg(adaptive_intensity) as avg_adaptive_intensity";

        $selects[] = "min(available_only_ratio_tally) as min_available_only_ratio_tally";
        $selects[] = "min(adaptive_latest_server_time) as min_adaptive_latest_server_time";
        $selects[] = "min(local_call_time) as min_local_call_time";

        $selects[] = "avg(dial_timeout) as avg_dial_timeout";
        $selects[] = "min(dial_statuses) as min_dial_statuses";
        $selects[] = "max(agent_pause_codes_active) as max_agent_pause_codes_active";
        $selects[] = "max(list_order_mix) as max_list_order_mix";
        $selects[] = "max(auto_hopper_level) as max_auto_hopper_level";
        $selects[] = "max(ofcom_uk_drop_calc) as max_ofcom_uk_drop_calc";

        $query = \PATHAODB::table("vicidial_campaigns");
        $query->select(\PATHAODB::raw(implode($selects, ", ")));

        if (!$this->returnAllActive && $this->isInbound == "YES") {
            $query->whereIn("campaign_id", array_merge($this->selectedCampaign, $this->closerCampaign));
        } else {
            $query->where("active", "=", "Y");
            $query->whereIn("campaign_id", $this->selectedCampaign);
        }

        $viciResult = $query->first();

        $selects = [];
        $selects[] = "sum(dialable_leads) as sum_dialable_leads";
        $selects[] = "sum(calls_today) as sum_calls_today";
        $selects[] = "sum(drops_today) as sum_drops_today";

        $selects[] = "avg(drops_answers_today_pct) as avg_drops_answers_today_pct";
        $selects[] = "avg(differential_onemin) as avg_differential_onemin";
        $selects[] = "avg(agents_average_onemin) as avg_agents_average_onemin";


        $selects[] = "sum(balance_trunk_fill) as sum_balance_trunk_fill";
        $selects[] = "sum(answers_today) as sum_answers_today";

        if ($this->isVSCAT) {
            $selects[] = "max(status_category_1) as max_status_category_1";
            $selects[] = "sum(status_category_count_1) as sum_status_category_count_1";
            $selects[] = "max(status_category_2) as max_status_category_2";
            $selects[] = "sum(status_category_count_2) as sum_status_category_count_2";
            $selects[] = "max(status_category_3) as max_status_category_3";
            $selects[] = "sum(status_category_count_3) as sum_status_category_count_3";
            $selects[] = "max(status_category_4) as max_status_category_4";
            $selects[] = "sum(status_category_count_4) as sum_status_category_count_4";
        }

        $selects[] = "sum(agent_calls_today) as sum_agent_calls_today";
        $selects[] = "sum(agent_wait_today) as sum_agent_wait_today";
        $selects[] = "sum(agent_custtalk_today) as sum_agent_custtalk_today";
        $selects[] = "sum(agent_acw_today) as sum_agent_acw_today";
        $selects[] = "sum(agent_pause_today) as sum_agent_pause_today";
        $selects[] = "sum(agenthandled_today) as sum_agenthandled_today";

        $query = \PATHAODB::table("vicidial_campaign_stats");
        $query->select(\PATHAODB::raw(implode($selects, ", ")));

        $query->where("calls_today", ">", "-1");

        if ($this->returnAllActive && $this->isInbound == "NO") {
            $query->whereNotIn("campaign_id", $this->allInboundCampaign);
        } elseif ($this->isInbound == "YES") {
            $query->whereIn("campaign_id", array_merge($this->selectedCampaign, $this->closerCampaign));
        } else {
            $query->whereIn("campaign_id", $this->selectedCampaign);
        }

        //$this -> rawOutput($query);

        $viciStatsResult = $query->first();

        $multidrop = false;
        if($this -> returnAllActive || (!$this -> returnAllActive && $this -> isInbound == "YES")){
            $multidrop = true;
        }
        $this->miniStatsArray['dial_level'] = sprintf("%01.3f", $viciResult->avg_auto_dial_level);
        $this->miniStatsArray['trunk_short_fill'] = $this->getTrunkShortage() . "/" . $viciStatsResult->sum_balance_trunk_fill;
        $this->miniStatsArray['dial_filter'] = $viciResult->min_lead_filter_id;
        $this->miniStatsArray['dialable_leads'] = $viciStatsResult -> sum_dialable_leads;
        $this->miniStatsArray['calls_today'] = $viciStatsResult->sum_calls_today;
        $this->miniStatsArray['avg_agents'] = $viciStatsResult->avg_agents_average_onemin;
        $this->miniStatsArray['dial_method'] = $viciResult->min_dial_method;
        $this->miniStatsArray['hopper'] = $viciResult->sum_hopper_level . "/" . $viciResult->max_auto_hopper_level;
        $this->miniStatsArray['dropped'] = $viciStatsResult->sum_drops_today . "/" . $viciStatsResult->sum_answers_today;
        $this->miniStatsArray['drops_today'] = round($viciStatsResult->sum_drops_today);
        $this->miniStatsArray['answers_today'] = $viciStatsResult->sum_answers_today;
        $this->miniStatsArray['dl_diff'] = sprintf("%01.2f", $viciStatsResult->avg_differential_onemin);
        $this->miniStatsArray['statuses'] = $viciResult->min_dial_statuses;

        $this->miniStatsArray['outbound_today'] = $this->miniStatsArray['calls_today'] - ($this->miniStatsArray['drops_today'] + $this->miniStatsArray['answers_today']);


        $this->miniStatsArray['leads_in_hopper'] = $this->getHopperCount();
        //if($viciResult -> avg_adaptive_maximum_level)
        $this->miniStatsArray['drop_percent'] = $viciStatsResult -> sum_drops_today;
//        $this->miniStatsArray['drop_percent'] = sprintf("%01.2f",
//            round(($this->MathZDC($viciStatsResult -> sum_drops_today, $viciStatsResult -> sum_answers_today + $viciStatsResult -> sum_agenthandled_today) * 100), 2)
//        );
        $this->miniStatsArray['drop_percent'] = sprintf("%01.2f",
            round(($this->MathZDC($viciStatsResult -> sum_drops_today, $viciStatsResult -> sum_answers_today) * 100), 2)
        );
        $this->miniStatsArray['diff'] = sprintf("%01.2f",
            round(($this->MathZDC($viciStatsResult -> avg_differential_onemin, $viciStatsResult -> avg_agents_average_onemin) * 100), 2)
        );
        $this->miniStatsArray['order'] = $viciResult -> min_lead_order;


    }


    public function callStatus(){
        $query = \PATHAODB::table("vicidial_auto_calls");
        $query->select(\PATHAODB::raw("status,campaign_id,phone_number,server_ip,UNIX_TIMESTAMP(call_time) as unix_call_time,call_type,queue_priority,agent_only"));

        $query->whereNotIn("status",['XFER']);

        if ($this->isInbound != "NO") {
            //$query->whereNotIn("campaign_id", $this->allInboundCampaign);
            $query->where(function($query){
                $query->where(function($q){
                    $q -> where("call_type","=","IN");
                    $q -> whereIn("campaign_id",$this -> closerCampaign);
                });
                $query -> orWhere(function($q){
                    $q -> whereIn("call_type",['OUT','OUTBALANCE']);
                    $q -> whereIn("campaign_id",$this->selectedCampaign);
                });
            });
        } else {
            $query->whereIn("campaign_id", $this->selectedCampaign);
        }

        $query -> orderBy("queue_priority", "DESC");
        $query -> orderBy("campaign_id", "ASC");
        $query -> orderBy("call_time", "ASC");
        $results = $query -> get();

        $STARTtime = date("U");

        if(!empty($results)){
            foreach($results as $callData){
                if($callData -> status == "LIVE"){
                    $this -> box_out_live++;

                    $arr = ["status" => $callData -> status,
                        "campaign" => $callData -> campaign_id,
                        "phone" => $callData -> phone_number,
                        "serverip" => $callData -> server_ip,
                        "dialtime" => $this -> sec_convert($STARTtime - $callData -> unix_call_time, "M"),
                        "call_type" => $callData -> call_type,
                        "priority" => $callData -> queue_priority];
                    $this -> vcWaitingList[] = $arr;

                    if($callData -> agent_only > 0) $this ->
                    box_agentonlycount++;
                }
                else{
                    if($callData -> status == "IVR"){
                        $this -> box_in_ivr++;

                        $arr = ["status" => $callData -> status,
                            "campaign" => $callData -> campaign_id,
                            "phone" => $callData -> phone_number,
                            "serverip" => $callData -> server_ip,
                            "dialtime" => $this -> sec_convert($STARTtime - $callData -> unix_call_time, "M"),
                            "call_type" => $callData -> call_type,
                            "priority" => $callData -> queue_priority];
                        $this -> vcWaitingList[] = $arr;

                        if($callData -> agent_only > 0) $this -> box_agentonlycount++;

                    }
                    if($callData -> status == "CLOSER"){
                        //do nothing?
                    }
                    else{
                        $this -> box_out_ring++;
                    }
                }
                $this -> box_out_total++;
            }
        }
        //$this -> rawOutput($query);
    }

    public function listCallerIDs(){
        $query = \PATHAODB::table("vicidial_auto_calls");
        $query->select(["callerid","lead_id","phone_number"]);
        $result = $query->get();
        foreach($result as $call){
            $this -> vcCallerIDs[] = $call -> callerid;
            $this -> vcCustPhonesArray[$call -> lead_id] = $call -> phone_number;
        }
    }
    public function listAgents(){
        $query = \PATHAODB::table("vicidial_live_agents");
        $query->select(\PATHAODB::raw("extension,vicidial_live_agents.user,conf_exten,vicidial_live_agents.status,vicidial_live_agents.server_ip,
        UNIX_TIMESTAMP(last_call_time) as lct,UNIX_TIMESTAMP(last_call_finish) as lcf,call_server_ip,vicidial_live_agents.campaign_id,
        vicidial_users.user_group,vicidial_users.full_name,vicidial_live_agents.comments,vicidial_live_agents.calls_today,vicidial_live_agents.closer_campaigns,
        vicidial_live_agents.callerid,lead_id,UNIX_TIMESTAMP(last_state_change) as lsf,on_hook_agent,ring_callerid,agent_log_id"));
        $query->join('vicidial_users', function($table)
        {
            $table->on('vicidial_users.user', '=', 'vicidial_live_agents.user');
            $table->where('vicidial_users.user_hide_realtime', '=', '0');
        });

        if(!$this -> returnAllActive){
            $query->whereIn("vicidial_live_agents.campaign_id", $this -> selectedCampaign);
        }

        if(!$this -> returnAllUserGroup){
            $query->whereIn("vicidial_users.user_group", $this -> selectedGroup);
        }


        $results = $query -> get();

        /**
         * $agents -> extension
         * $agents -> user
         * $agents -> conf_exten
         * $agents -> status
         * $agents -> server_ip
         * $agents -> lct
         * $agents -> lcf
         * $agents -> call_server_ip
         * $agents -> campaign_id
         * $agents -> user_group
         * $agents -> full_name
         * $agents -> comments
         * $agents -> calls_today
         * $agents -> callerid
         * $agents -> lead_id
         * $agents -> lsf
         * $agents -> on_hook_agent
         * $agents -> ring_callerid
         * $agents -> agent_log_id
         */

        $agents_pause_code_active = $this -> getAgentPaused();

        foreach($results as $agents){

            if($agents -> on_hook_agent == "Y"){
                $this -> box_ring_agents++;
                if (strlen($agents -> ring_callerid) > 18)
                    $agents -> status = "RING";
            }
            if($agents -> lead_id != 0){
                $mostrecent = $this -> checkThreeWay($agents -> lead_id);
                if($mostrecent)
                    $agents -> status = "3-WAY";
            }

            if (preg_match("/READY|PAUSED/i",$agents -> status))
            {
                $agents -> lct = $agents -> lsf;

                if ($agents -> lead_id > 0)
                {
                    $agents -> status =	'DISPO';
                }
            }

            if($agents_pause_code_active > 0){
                $pausecode = 'N/A';
            }else{
                $pausecode = 'N/A';
            }

            if (preg_match("/INCALL/i",$agents -> status))
            {
                $parked_channel = $this -> getParkedCount($agents -> callerid);

                if ($parked_channel > 0)
                {
                    $agents -> status =	'PARK';
                }
                else
                {
                    if (!in_array($agents -> callerid,$this -> vcCallerIDs) && !preg_match("/EMAIL/i",$agents -> comments) && !preg_match("/CHAT/i",$agents -> comments))
                    {
                        $agents -> lct = $agents -> lsf;
                        $agents -> status =	'DEAD';
                    }
                }

                if ( (preg_match("/AUTO/i",$agents -> comments)) or (strlen($agents -> comments)<1) )
                {
                    $CM='A';
                }
                else
                {
                    if (preg_match("/INBOUND/i",$agents -> comments))
                    {
                        $CM='I';
                    }
                    else if (preg_match("/EMAIL/i",$agents -> comments))
                    {
                        $CM='E';
                    }
                    else
                    {
                        $CM='M';
                    }
                }
            }
            else {
                $CM=' ';
            }

            $STARTtime = date("U");
            $call_time_S = 0;
            if (!preg_match("/INCALL|QUEUE|PARK|3-WAY/i",$agents -> status))
            {
                $call_time_S = ($STARTtime - $agents -> lsf);
            }
            else if (preg_match("/3-WAY/i",$agents -> status))
            {
                $call_time_S = ($STARTtime - $mostrecent);
            }
            else
            {
                $call_time_S = ($STARTtime - $agents -> lct);
            }

            $call_time_MS =		$this -> sec_convert($call_time_S,'M');
            $call_time_MS =		sprintf("%7s", $call_time_MS);
            $custPhone = "";


            //lets update agents count
            switch ($agents -> status){
                case "DEAD":
                    if($call_time_S < 21600){
                        $this -> box_agent_total++;
                        $this -> box_agent_dead++;
                    }
                    break;
                case "DISPO":
                    if($call_time_S < 21600){
                        $this -> box_agent_total++;
                        $this -> box_agent_dispo++;
                    }
                    break;
                case "PAUSED":
                    if($call_time_S < 21600){
                        $this -> box_agent_total++;
                        $this -> box_agent_paused++;
                    }
                    break;
                case "INCALL":
                case "3-WAY":
                case "QUEUE":
                    $this -> box_agent_incall++;
                    $this -> box_agent_total++;
                    $custPhone = isset($this -> vcCustPhonesArray[$agents -> lead_id]) ? $this -> vcCustPhonesArray[$agents -> lead_id] : "";

                    break;
                case "READY":
                case "CLOSER":
                    $this -> box_agent_ready++;
                    $this -> box_agent_total++;
                    break;

            }

            if(in_array($agents -> status,["DEAD","DISPO","PAUSED"]) && $call_time_S >= 21600) continue;

            if($agents -> status == "PAUSED"){
                if ($agents_pause_code_active > 0)
                {
                    $pcode = $this -> getPauseCode($agents -> agent_log_id,$agents -> user);
                    if($pcode && !empty($pcode))
                        $pausecode = sprintf("%-6s", $pcode);
                    else
                        $pausecode = "N/A";
                }
                else
                {
                    $pausecode='N/A';
                }
            }

//            $agents -> closer_campaigns = str_replace('-', "", $agents -> closer_campaigns);
//            $agents -> closer_campaigns = str_replace(' ', "<br>", $agents -> closer_campaigns);

            $vcAgent = [];
            $vcAgent['extension'] = $agents -> extension;
            $vcAgent["phone"] = sprintf("%-12s",$this -> retrivePhone($agents -> extension, $agents -> server_ip));
            $vcAgent['cust_phone'] = $custPhone;
            $vcAgent['user'] = sprintf("%-20s", $agents -> user);
            $vcAgent['sessionid'] = sprintf("%-9s", $agents -> conf_exten);
            $vcAgent['status'] = sprintf("%-6s", $agents -> status);
            $vcAgent['serverip'] = sprintf("%-15s", $agents -> server_ip);
            $vcAgent['call_serverip'] = sprintf("%-15s", $agents -> call_server_ip);
            $vcAgent['campaign_id'] = sprintf("%-10s", $agents -> campaign_id);
            $vcAgent['comments'] = $agents -> comments;
            $vcAgent['calls_today'] = sprintf("%-5s", $agents -> calls_today);
            $vcAgent['user_group'] = sprintf("%-12s", $agents -> closer_campaigns);
            $vcAgent['full_name'] = sprintf("%-60s", $agents -> full_name);
            $vcAgent['pausecode'] = $pausecode;
            $vcAgent['call_time'] = $call_time_MS;
            $vcAgent['call_type'] = 0;
            if ($CM == 'I')
            {
                $query = \PATHAODB::table("vicidial_auto_calls");
                $query -> select(\PATHAODB::raw("count(*) as total"));
                $query->join('vicidial_inbound_groups', function($table)
                {
                    $table->on('vicidial_auto_calls.campaign_id', '=', 'vicidial_inbound_groups.group_id');
                });
                $query -> where("vicidial_auto_calls.callerid","=",$agents -> callerid);

                $result = $query -> first();

                if(!empty($result) && $result -> total > 0){
                    $vcAgent['call_type'] = 1;
                }

            }

            $this -> vcAgentsArray[] = $vcAgent;
        }


    }

    private function getPauseCode($log_id,$user){
        $query = \PATHAODB::table("vicidial_agent_log");
        $query->select(["sub_status"]);
        $query->where("agent_log_id",">=",$log_id);
        $query->where("user","=",$user);
        $query -> orderBy("agent_log_id","DESC");
        $result = $query -> first();

        if(!empty($result -> sub_status))
            return $result -> sub_status;
        else
            return false;
    }
    private function getParkedCount($callerid){
        $query = \PATHAODB::table("parked_channels");
        $query->select(\PATHAODB::raw("count(*) as totalCount"));
        $query->where("channel_group","=",$callerid);
        $result = $query -> first();
        return $result -> totalCount;
    }
    private function checkThreeWay($lead_id){
        $query = \PATHAODB::table("vicidial_live_agents");
        $query->select(\PATHAODB::raw("UNIX_TIMESTAMP(last_call_time) as lct"));
        $query->where("status","=","INCALL");
        $query->where("lead_id","=",$lead_id);
        $query->orderBy(\PATHAODB::raw("UNIX_TIMESTAMP(last_call_time)"),"DESC");

        if($query -> count() > 1){
            $result = $query -> first();
            return $result -> lct;
        }else{
            return false;
        }
    }
    private function retrivePhone($extension, $serverip){

        $query = \PATHAODB::table("phones");
        $query->select(["login"]);
        $query->where("server_ip","=",$serverip);

        if (preg_match("/R\//i",$extension))
        {
            $protocol = 'EXTERNAL';
            $dialplan = preg_replace('/R\//i', '',$extension);
            $dialplan = preg_replace('/\@.*/i', '',$dialplan);
            $query->where("dialplan_number","=",$dialplan);
        }
        elseif (preg_match("/Local\//i",$extension))
        {
            $protocol = 'EXTERNAL';
            $dialplan = preg_replace('/Local\//i', '',$extension);
            $dialplan = preg_replace('/\@.*/i', '',$dialplan);
            $query->where("dialplan_number","=",$dialplan);
        }
        elseif (preg_match('/SIP\//i',$extension))
        {
            $protocol = 'SIP';
            $dialplan = preg_replace('/SIP\//i', '',$extension);
            $dialplan = preg_replace('/\-.*/i', '',$dialplan);
            $query->where("extension","=",$dialplan);
        }
        elseif (preg_match('/IAX2\//i',$extension))
        {
            $protocol = 'IAX2';
            $dialplan = preg_replace('/IAX2\//i', '',$extension);
            $dialplan = preg_replace('/\-.*/i', '',$dialplan);
            $query->where("extension","=",$dialplan);
        }
        elseif (preg_match('/Zap\//i',$extension))
        {
            $protocol = 'Zap';
            $dialplan = preg_replace('/Zap\//i', '',$extension);
            $query->where("extension","=",$dialplan);
        }
        elseif (preg_match('/DAHDI\//i',$extension))
        {
            $protocol = 'Zap';
            $dialplan = preg_replace('/DAHDI\//i', '',$extension);
            $query->where("extension","=",$dialplan);
        }

        $query->where("protocol","=",$protocol);
        $result = $query->first();
        if(!empty($result -> login))
            return $result -> login;
        else
            return $extension;
    }

    private function getBoxStatus(){
        $agent['active_calls'] = $this -> box_out_total;
        $agent['call_ringing'] = $this -> box_out_ring;
        $agent['call_waiting'] = $this -> box_out_live;
        $agent['call_ivr'] = $this -> box_in_ivr;
        $agent['total_agents'] = $this -> box_agent_total;
        $agent['agents_in_call'] = $this -> box_agent_incall;
        $agent['agents_waiting'] = $this -> box_agent_ready;
        $agent['agents_paused'] = $this -> box_agent_paused;
        $agent['agents_dead'] = $this -> box_agent_dead;
        $agent['agents_dispo'] = $this -> box_agent_dispo;
        return $agent;
    }
    public function miniStats()
    {

        if ($this->isInbound == "ONLY")
            $this->inboundOnlyMiniStats();
        else {
            $this->completeMiniStats();
        }

        $this -> callStatus();
        $this -> listCallerIDs();
        $this -> listAgents();

        $return['stats'] = $this->miniStatsArray;
        $return['agents'] = $this -> vcAgentsArray;
        $return['callstatus'] = $this -> getBoxStatus();
        $return['waiting'] = $this -> vcWaitingList;
        return $return;
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
}