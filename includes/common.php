<?php
/**
 * Created by PhpStorm.
 * User: elomelo
 * Date: 24/4/18
 * Time: 5:13 PM
 */
session_start();
$_SERVER['REMOTE_ADDR'] = "127.0.0.1";
require 'vendor/autoload.php';
include_once "includes/config.php";
include_once "includes/Auth.php";

use Common\Auth;

$auth = new Auth();

function exportDownload($output,$name){
    $FILE_TIME = date("Ymd-His");
    $CSVfilename = "".$name."_$FILE_TIME.csv";
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

function setOutput(&$output, $arr){
    $arr = array_map(function($val) { return '"' . $val . '"'; }, $arr);
    $output .= implode(",", $arr) . Chr(10);
}

function getUserGroup(){
    $query = \PATHAODB::table("vicidial_user_groups");
    $query->select(["user_group","group_name"]);
    $query->orderBy("user_group","ASC");
    $usergroups = $query->get();
    return $usergroups;
}

function getInboundGroup($checkActive = true){
    $query = \PATHAODB::table("vicidial_inbound_groups");
    $query->select(["group_id","group_name"]);
    if($checkActive)
        $query->where("active","=",'Y');
    $campaigns = $query->get();
    return $campaigns;
}

function getCampaign(){
    $query = \PATHAODB::table("vicidial_campaigns");
    $query->select(["campaign_id","campaign_name"]);
    $campaigns = $query->get();
    return $campaigns;
}

function getListID(){
    $query = \PATHAODB::table("vicidial_lists");
    $query->select(["list_id"]);
    $campaigns = $query->get();
    return $campaigns;
}

function getStatuses(){
    $query = \PATHAODB::table("vicidial_statuses");
    $query->select(["status","status_name"]);
    $campaigns = $query->get();
    return $campaigns;
}

function getStatuses2(){
    $query = \PATHAODB::table("vicidial_campaign_statuses");
    $query->select(["status","status_name"]);
    $campaigns = $query->get();
    return $campaigns;
}

function getServerIPs(){
    $query = \PATHAODB::table("servers");
    $query->select(["server_ip","server_description","external_server_ip"]);
    $campaigns = $query->get();
    return $campaigns;
}
function getSystemSettings(){
    $query = \PATHAODB::table("system_settings");
    $settings = $query->first();
    return $settings;
}

function isAgentExists($user,$userid = false){
    $query = \PATHAODB::table("vicidial_users");
    $query -> where("user",$user);
    if($userid){
        $query -> where("user_id","!=",$userid);
    }
    $result = $query -> first();
    if(!empty($result -> user))
        return true;
    else
        return false;
}
function isPhoneExistsInUser($phone,$userid = false){
    $query = \PATHAODB::table("vicidial_users");
    $query -> where("phone_login",$phone);
    if($userid)
        $query -> where("user","!=",$userid);
    $result = $query -> first();
    if(!empty($result -> phone_login))
        return true;
    else
        return false;
}
function isPhoneExists($phone){
    $query = \PATHAODB::table("phones");
    $query -> where("extension",$phone);
    $result = $query -> first();
    if(!empty($result -> extension))
        return true;
    else
        return false;
}
function checkPhonePass($phone,$pass){
    $query = \PATHAODB::table("phones");
    $query -> where("extension",$phone);
    $query -> where("pass","=",$pass);
    $result = $query -> first();
    if(!empty($result -> extension))
        return true;
    else
        return false;
}

function updateUserPhone($phone,$pass,$oldphone,$ignoreUser = false){
    $data = ["phone_login" => $phone, "phone_pass" => $pass];
    $query = \PATHAODB::table("vicidial_users");
    $query->where("phone_login","=",$oldphone);
    if($ignoreUser){
        $query -> where("user","!=",$ignoreUser);
    }
    $query->update($data);
}

function updatePhone($extension,$pass){
    $query = \PATHAODB::table("phones");
    $query -> where("extension",$extension);
    $result = $query -> first();
    if(!empty($result -> extension)){
        if($pass == $result -> pass) return false;
        $systemSettings = getSystemSettings();
        $data = [
            "pass" => $pass,
            "conf_secret" => $pass
        ];

        \PATHAODB::table("phones")->where("extension","=",$extension)->update($data);
        \PATHAODB::table("servers")-> where("server_ip","=",$result -> server_ip)->update(["rebuild_conf_files" => "Y","generate_vicidial_conf" => "Y","active_asterisk_server" => "Y"]);
        \PATHAODB::table("servers")-> where("server_ip","=",$systemSettings -> active_voicemail_server)->update(["rebuild_conf_files" => "Y","generate_vicidial_conf" => "Y","active_asterisk_server" => "Y"]);
        return true;
    }
    else
        return false;
}


?>