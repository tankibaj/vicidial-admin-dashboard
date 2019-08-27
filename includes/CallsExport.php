<?php
/**
 * Created by PhpStorm.
 * User: elomelo
 * Date: 21/5/18
 * Time: 6:26 AM
 */

namespace Common;


class CallsExport
{
    var $selectedGroups;
    var $selectedCampaign;
    var $selectedLead;
    var $selectedStatus;
    var $selectedUserGroup;
    var $exportType = "STANDARD";
    var $recording = false;
    var $callnote = false;
    var $archiveData = false;

    var $dateStart;
    var $dateEnd;

    var $exportFields = [];
    var $exportFieldsSQL = [];
    var $output = "";

    var $vicidial_log_table="vicidial_log";
    var $vicidial_closer_log_table="vicidial_closer_log";
    var $vicidial_agent_log_table="vicidial_agent_log";
    var $vicidial_log_extended_table="vicidial_log_extended";
    var $recording_log_table="recording_log";
    var $vicidial_carrier_log_table="vicidial_carrier_log";
    var $vicidial_cpd_log_table="vicidial_cpd_log";
    var $vicidial_did_log_table="vicidial_did_log";
    var $vicidial_outbound_ivr_log_table="vicidial_outbound_ivr_log";

    var $listSQL = "";
    var $campaignSQL = "";
    var $userGroupSQL = "";
    var $statusSQL = "";
    var $groupSQL = "";

    var $isCampaign = false;
    var $isGroup = false;
    var $date_field = "vl.call_date";
    var $callsList = [];

    var $listArr = [];
    var $statusArr = [];



    public function __construct($selectedGroup, $selectedCampaign, $selectedUserGroup, $selectedLead, $selectedStatus,$exportType,$recording,$callnote,$archiveData,$dateStart,$dateEnd)
    {
        $this -> selectedGroups = $selectedGroup;
        $this -> selectedCampaign = $selectedCampaign;
        $this -> selectedUserGroup = $selectedUserGroup;
        $this -> selectedLead = $selectedLead;
        $this -> selectedStatus = $selectedStatus;
        $this -> exportType = $exportType;
        $this -> recording = $recording;
        $this -> callnote = $callnote;
        $this -> archiveData = $archiveData;
        $this -> dateStart = $dateStart . " 00:00:00";
        $this -> dateEnd = $dateEnd . " 23:59:59";

        $this -> setFields();
        $this -> setListArr();
        $this -> setStatusArr();
    }

    private function setFields(){
        if($this -> archiveData){
            $this -> vicidial_log_table="vicidial_log_archive";
            $this -> vicidial_closer_log_table="vicidial_closer_log_archive";
            $this -> vicidial_agent_log_table="vicidial_agent_log_archive";
            $this -> vicidial_log_extended_table="vicidial_log_extended_archive";
            $this -> recording_log_table="recording_log_archive";
            $this -> vicidial_carrier_log_table="vicidial_carrier_log_archive";
            $this -> vicidial_cpd_log_table="vicidial_cpd_log";
            $this -> vicidial_did_log_table="vicidial_did_log";
            $this -> vicidial_outbound_ivr_log_table="vicidial_outbound_ivr_log_archive";
        }


        if (!empty($this-> selectedLead) && !in_array("ALL", $this->selectedLead)) {
            $this -> listSQL = "AND vi.list_id IN(".implode(",", $this -> quoteData($this -> selectedLead)).")";
        }

        if (!empty($this-> selectedUserGroup) && !in_array("ALL", $this->selectedUserGroup)) {
            $this -> userGroupSQL = "AND vl.user_group IN(".implode(",", $this -> quoteData($this -> selectedUserGroup)).")";
        }

        if (!empty($this-> selectedStatus) && !in_array("ALL", $this->selectedStatus)) {
            $this -> statusSQL = "AND vl.status IN(".implode(",", $this -> quoteData($this -> selectedStatus)).")";
        }

        if (!empty($this-> selectedCampaign) && !in_array("NONE", $this->selectedCampaign)) {
            $this -> campaignSQL = "AND vl.campaign_id IN(".implode(",", $this -> quoteData($this -> selectedCampaign)).")";
            $this -> isCampaign = true;
        }else{
            $this -> campaignSQL = "AND campaign_id IN('')";
        }

        if (!empty($this-> selectedGroups) && !in_array("NONE", $this->selectedGroups)) {
            $this -> isGroup = true;
            $this -> groupSQL = "AND vl.campaign_id IN(".implode(",", $this -> quoteData($this -> selectedGroups)).")";
        }else{
            $this -> groupSQL = "AND campaign_id IN('')";
        }



        $this -> exportFieldsSQL = [];
        $this -> exportFields = [];
        if ($this -> exportType == 'EXTENDED')
        {
            $this -> exportFieldsSQL[] = 'entry_date';
            $this -> exportFieldsSQL[] = 'vl.called_count';
            $this -> exportFieldsSQL[] = 'last_local_call_time';
            $this -> exportFieldsSQL[] = 'modify_date';
            $this -> exportFieldsSQL[] = 'called_since_last_reset';
            $this -> exportFieldsSQL[] = 'ifnull(val.dispo_sec+val.dead_sec,0) as dispo_dead';

        }
        if (in_array($this -> exportType,['EXTENDED2','EXTENDED3']))
        {
            $this -> exportFieldsSQL[] = 'entry_date';
            $this -> exportFieldsSQL[] = 'vl.called_count';
            $this -> exportFieldsSQL[] = 'last_local_call_time';
            $this -> exportFieldsSQL[] = 'modify_date';
            $this -> exportFieldsSQL[] = 'called_since_last_reset';
            $this -> exportFieldsSQL[] = 'term_reason';
            $this -> exportFieldsSQL[] = 'ifnull(val.dispo_sec+val.dead_sec,0) as dispo_dead';

        }
        if ($this -> exportType == 'ALTERNATE1')
        {
            $this -> exportFieldsSQL[] = 'vl.called_count';
            $this -> exportFieldsSQL[] = 'last_local_call_time';
        }

    }

    private function setListArr(){
        $query = \PATHAODB::table("vicidial_lists");
        $query->select(["list_id","list_description","list_name"]);
        $result = $query -> get();
        foreach($result as $stats){
            $this -> listArr[$stats -> list_id] = ["list_name" => $stats -> list_name, "list_description" => $stats -> list_description];
        }
    }

    private function setStatusArr(){
        $query = \PATHAODB::table("vicidial_statuses");
        $query->select(["status","status_name"]);
        $result = $query -> get();
        foreach($result as $stats){
            $this -> statusArr[$stats -> status] = $stats -> status_name;
        }

        $query = \PATHAODB::table("vicidial_campaign_statuses");
        $query->select(["status","status_name"]);
        $result = $query -> get();
        foreach($result as $stats){
            $this -> statusArr[$stats -> status] = $stats -> status_name;
        }
    }

    private function getRecordingInfo($vicidial_id){
        $query = \PATHAODB::table($this -> recording_log_table);
        $query->select(["recording_id","filename","location"]);
        $query -> where("vicidial_id","=",$vicidial_id);
        $query -> orderBy("recording_id","DESC");
        $query -> limit(10);
        $this -> rawOutput($query);
        $result = $query -> first();

        if(empty($result -> recording_id)){
            if(strpos($this -> recording_log_table,"archive") === FALSE)
                $this -> recording_log_table = "recording_log_archive";
            else
                $this -> recording_log_table = "recording_log";
            $query = \PATHAODB::table($this -> recording_log_table);
            $query->select(["recording_id","filename","location"]);
            $query -> where("vicidial_id","=",$vicidial_id);
            $query -> orderBy("recording_id","DESC");
            $query -> limit(10);
            $result = $query -> first();
        }

        if(!empty($result -> recording_id)){
            return ["recording_id" => $result -> recording_id, "filename" => $result -> filename, "location" => $result -> location];
        }else{
            return ["recording_id" => "", "filename" => "", "location" => ""];
        }
    }

    private function getCallerCode($uniqueid,$leadid){
        $query = \PATHAODB::table($this -> vicidial_log_extended_table);
        $query->select(["caller_code","server_ip"]);
        $query -> where("uniqueid","LIKE","$uniqueid%");
        $query -> where("lead_id","=",$leadid);
        $result = $query -> first();

        if(!empty($result -> caller_code)){
            return ["caller_code" => $result -> caller_code, "server_ip" => $result -> server_ip];
        }else{
            return ["caller_code" => "", "server_ip" => ""];
        }
    }

    private function hangupReason($uniqueid,$leadid){
        $query = \PATHAODB::table($this -> vicidial_carrier_log_table);
        $query->select(["hangup_cause","dialstatus","channel","dial_time","answered_time"]);
        $query -> where("uniqueid","LIKE","$uniqueid%");
        $query -> where("lead_id","=",$leadid);
        $result = $query -> first();

        if(!empty($result -> hangup_cause)){
            return ["hangup_cause" => $result -> hangup_cause,"dialstatus" => $result -> dialstatus,"channel" => $result -> channel,
                "dial_time" => $result -> dial_time,"answered_time" => $result -> answered_time];
        }else{
            return ["hangup_cause" => "","dialstatus" => "","channel" => "",
                "dial_time" => "","answered_time" => ""];
        }
    }

    private function getResult($callerid){
        $query = \PATHAODB::table($this -> vicidial_cpd_log_table);
        $query->select(["result"]);
        $query -> where("callerid","=",$callerid);
        $result = $query -> first();

        if(!empty($result -> result)){
            return ["result" => $result -> result];
        }else{
            return ["result" => ""];
        }
    }

    private function getExtension($uniqueid){
        $query = \PATHAODB::table($this -> vicidial_did_log_table);
        $query->select(["extension","did_id"]);
        $query -> where("uniqueid","=",$uniqueid);
        $result = $query -> first();

        if(!empty($result -> extension)){

            $query = \PATHAODB::table("vicidial_inbound_dids");
            $query->select(["did_description","custom_one","custom_two","custom_three","custom_four","custom_five","did_carrier_description"]);
            $query -> where("did_id","=",$result -> did_id);
            $result2 = $query -> first();

            return ["extension" => $result -> extension,"did_id" => $result -> did_id,
                "did_description" => $result2 -> did_description,"custom_one" => $result2 -> custom_one,"custom_two" => $result2 -> custom_two,"custom_three" => $result2 -> custom_three,
                "custom_four" => $result2 -> custom_four,"custom_five" => $result2 -> custom_five,"did_carrier_description" => $result2 -> did_carrier_description];

        }else{
            return ["extension" => "", "did_id" => "",
                "did_description" => "","custom_one" => "","custom_two" => "","custom_three" => "",
                "custom_four" => "","custom_five" => "","did_carrier_description" => ""];

        }
    }

    private function getCallNote($vicidial_id){
        $query = \PATHAODB::table("vicidial_call_notes");
        $query->select(["call_notes"]);
        $query -> where("vicidial_id","=",$vicidial_id);
        $result = $query -> first();

        if(!empty($result -> call_notes)){
            return ["call_notes" => $result -> call_notes];
        }else{
            return ["call_notes" => ""];
        }
    }



    private function sortData($r){
        $callArr['call_date'] = $r -> call_date;
        $callArr['phone_number'] = $r -> phone_number;
        $callArr['status'] = $r -> status;
        if(isset($this -> statusArr[$r -> status]))
            $callArr['status_name'] = $this -> statusArr[$r -> status];
        else
            $callArr['status_name'] = "";

        $callArr['user'] = $r -> user;
        $callArr['full_name'] = $r -> full_name;
        $callArr['campaign_id'] = $r -> campaign_id;
        $callArr['vendor_lead_code'] = $r -> vendor_lead_code;
        $callArr['source_id'] = $r -> source_id;
        $callArr['list_id'] = $r -> list_id;
        $callArr['gmt_offset_now'] = $r -> gmt_offset_now;
        $callArr['phone_code'] = $r -> phone_code;
        $callArr['phone_number'] = $r -> phone_number;
        $callArr['title'] = $r -> title;
        $callArr['first_name'] = $r -> first_name;
        $callArr['middle_initial'] = $r -> middle_initial;
        $callArr['last_name'] = $r -> last_name;
        $callArr['address1'] = $r -> address1;
        $callArr['address2'] = $r -> address2;
        $callArr['address3'] = $r -> address3;
        $callArr['city'] = $r -> city;
        $callArr['state'] = $r -> state;
        $callArr['province'] = $r -> province;
        $callArr['postal_code'] = $r -> postal_code;
        $callArr['country_code'] = $r -> country_code;
        $callArr['gender'] = $r -> gender;
        $callArr['date_of_birth'] = $r -> date_of_birth;
        $callArr['email'] = $r -> email;
        $callArr['security_phrase'] = $r -> security_phrase;
        $callArr['comments'] = $r -> comments;
        $callArr['length_in_sec'] = $r -> length_in_sec;
        $callArr['user_group'] = $r -> user_group;

        if(isset($r -> alt_dial))
            $callArr['alt_dial'] = $r -> alt_dial;
        else
            $callArr['alt_dial'] = "";
        if(isset($r -> queue_seconds))
            $callArr['queue_seconds'] = $r -> queue_seconds;
        else
            $callArr['queue_seconds'] = "";

        $callArr['rank'] = $r -> rank;
        $callArr['owner'] = $r -> owner;
        $callArr['lead_id'] = $r -> lead_id;
        if(isset($r -> closecallid))
        {
            $vicidial_id = $r -> closecallid;
            $callArr['closecallid'] = $r -> closecallid;
        }
        else
        {
            $vicidial_id = $r -> uniqueid;
            $callArr['closecallid'] = "";
        }
        $callArr['uniqueid'] = $r -> uniqueid;


        $callArr['entry_list_id'] = $r -> entry_list_id;

        $callArr = array_merge($callArr, $this -> getCallerCode($r -> uniqueid, $vicidial_id));

        if (in_array($this -> exportType,['EXTENDED','EXTENDED2','EXTENDED3']))
        {
            $callArr['dispo_dead'] = $r -> dispo_dead;
            $callArr['entry_date'] = $r -> entry_date;
            $callArr['called_count'] = $r -> called_count;
            $callArr['last_local_call_time'] = $r -> last_local_call_time;
            $callArr['modify_date'] = $r -> modify_date;
            $callArr['called_since_last_reset'] = $r -> called_since_last_reset;

            if (in_array($this -> exportType,['EXTENDED2','EXTENDED3']))
            {
                $callArr['term_reason'] = $r -> term_reason;

            }

            $callArr = array_merge($callArr, $this -> hangupReason($r -> uniqueid, $r -> lead_id));
            $callArr = array_merge($callArr, $this -> getResult($callArr["caller_code"]));
            $callArr = array_merge($callArr, $this -> getExtension($r -> uniqueid));

        }
        elseif ($this -> exportType == 'ALTERNATE1')
        {
            $callArr['called_count'] = $r -> called_count;
            $callArr['last_local_call_time'] = $r -> last_local_call_time;
        }

        $callArr = array_merge($callArr, $this -> listArr[$callArr['list_id']]);

        if($this -> recording){
            $callArr = array_merge($callArr, $this -> getRecordingInfo($vicidial_id));
        }

        if($this -> callnote){
            $callArr = array_merge($callArr, $this -> getCallNote($vicidial_id));
        }

        return $callArr;
    }
    private function runGroup(){
        $date_field = $this -> date_field;
        $defaultFields = 'vl.call_date,vl.phone_number,vl.status,vl.user,vu.full_name,vl.campaign_id,vi.vendor_lead_code,vi.source_id,vi.list_id,vi.gmt_offset_now,vi.phone_code,vi.phone_number,vi.title,vi.first_name,vi.middle_initial,vi.last_name,vi.address1,vi.address2,vi.address3,vi.city,vi.state,vi.province,vi.postal_code,vi.country_code,vi.gender,vi.date_of_birth,vi.alt_phone,vi.email,vi.security_phrase,vi.comments,vl.length_in_sec,vl.user_group,vl.queue_seconds,vi.rank,vi.owner,vi.lead_id,vl.closecallid,vi.entry_list_id,vl.uniqueid';
        $export_fields_SQL = "";
        if(!empty($this -> exportFieldsSQL))
            $export_fields_SQL = "," . implode(',', $this -> exportFieldsSQL);

        $whereSQL = "where ".$date_field." >= '{$this -> dateStart}' and ".$date_field." <= '{$this -> dateEnd}'
        and vu.user=vl.user and vi.lead_id=vl.lead_id 
{$this -> listSQL} {$this -> groupSQL} {$this -> userGroupSQL} {$this -> statusSQL} order by ".$date_field." limit 100000;";

        if (in_array($this -> exportType,['EXTENDED','EXTENDED2','EXTENDED3']))
        {
            $stmt = "SELECT $defaultFields $export_fields_SQL 
                    from vicidial_users vu,vicidial_list vi,".$this -> vicidial_closer_log_table." vl 
                    LEFT OUTER JOIN ".$this -> vicidial_agent_log_table." val 
                    ON vl.uniqueid=val.uniqueid and vl.lead_id=val.lead_id and vl.user=val.user 
                    $whereSQL";
        }
        else
        {
            $stmt = "SELECT $defaultFields $export_fields_SQL 
                    from vicidial_users vu,".$this -> vicidial_closer_log_table." vl,vicidial_list vi 
                    $whereSQL";
        }

        $query = \PATHAODB::query($stmt);
        $result = $query -> get();
        foreach($result as $r){
            $this -> callsList[] = $this -> sortData($r);
        }
    }
    private function runCampaign(){
        $date_field = $this -> date_field;
        $defaultFields = 'vl.call_date,vl.phone_number,vl.status,vl.user,vu.full_name,vl.campaign_id,vi.vendor_lead_code,vi.source_id,vi.list_id,vi.gmt_offset_now,vi.phone_code,vi.phone_number,vi.title,vi.first_name,vi.middle_initial,vi.last_name,vi.address1,vi.address2,vi.address3,vi.city,vi.state,vi.province,vi.postal_code,vi.country_code,vi.gender,vi.date_of_birth,vi.alt_phone,vi.email,vi.security_phrase,vi.comments,vl.length_in_sec,vl.user_group,vl.alt_dial,vi.rank,vi.owner,vi.lead_id,vl.uniqueid,vi.entry_list_id';
        $export_fields_SQL = "";

        if(!empty($this -> exportFieldsSQL))
            $export_fields_SQL = "," . implode(',', $this -> exportFieldsSQL);

        $whereSQL = "where ".$date_field." >= '{$this -> dateStart}' and ".$date_field." <= '{$this -> dateEnd}'
        and vu.user=vl.user and vi.lead_id=vl.lead_id 
{$this -> listSQL} {$this -> campaignSQL} {$this -> userGroupSQL} {$this -> statusSQL} order by ".$date_field." limit 100000;";

        if (in_array($this -> exportType,['EXTENDED','EXTENDED2','EXTENDED3']))
        {
            $stmt = "SELECT $defaultFields $export_fields_SQL 
                    from vicidial_users vu,vicidial_list vi,".$this -> vicidial_log_table." vl 
                    LEFT OUTER JOIN ".$this -> vicidial_agent_log_table." val 
                    ON vl.uniqueid=val.uniqueid and vl.lead_id=val.lead_id and vl.user=val.user 
                    $whereSQL";
        }
        else
        {
            $stmt = "SELECT $defaultFields $export_fields_SQL 
                    from vicidial_users vu,".$this -> vicidial_log_table." vl,vicidial_list vi 
                    $whereSQL";
        }
        $query = \PATHAODB::query($stmt);
        $result = $query -> get();
        foreach($result as $r){
            $this -> callsList[] = $this -> sortData($r);
        }
    }

    public function listData(){

        if($this -> isCampaign)
            $this -> runCampaign();
        if($this -> isGroup)
            $this -> runGroup();
        if(empty($this -> callsList)){
            die("No result found!");
        }
        $headerKeys = array_keys($this -> callsList[0]);
        $this -> output = "\n";
        $this -> setOutput($this -> output,$headerKeys);
        foreach($this -> callsList as $call){
            $this -> setOutput($this -> output,$call);
        }

        $this -> checkDownload();
    }

    public function setOutput(&$output, $arr){
        $arr = array_map(function($val) { return '"' . $val . '"'; }, $arr);
        $output .= implode(",", $arr) . Chr(10);
    }

    public function checkDownload(){
            $FILE_TIME = date("Ymd-His");
            $CSVfilename = "CallExport_$FILE_TIME.csv";
            $this -> downloadHeader();
        $output = $this -> output;

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

    private function downloadHeader(){
        $this -> output = '"Call Export:","Campaigns: '.implode(",",$this -> selectedCampaign).'","Inbound Groups: '.implode(",",$this -> selectedGroups).'","User Groups: '.implode(",",$this -> selectedUserGroup).'","List: '.implode(",",$this -> selectedLead).'","Status: '.implode(",",$this -> selectedStatus).'","'. $this -> dateStart .' - '. $this -> dateEnd .'"' . Chr(10) . $this -> output;
    }

    private function quoteData($obj){
        $obj = array_map(function($val) { return "'" . $val . "'"; }, $obj);
        return $obj;
    }
    private function rawOutput(&$obj)
    {
        $queryObj = $obj->getQuery();
        die($queryObj->getRawSql());
    }
}