<?php
/**
 * Created by PhpStorm.
 * User: elomelo
 * Date: 24/4/18
 * Time: 5:20 PM
 */

namespace Common;


class Auth
{
    protected $_userInfo;
    protected  $login_error_text;
    public function __construct(){

    }

    public function checkLogin($username,$password){
        $username = strtolower($username);
        $password = md5($password);
        $query = \PATHAODB::table('pathaocs')
            ->where("username","=",$username)
            ->where("password","=",$password);

        $result = $query->first();

        if ($result) {
            $row =  (array) $result;
            $this->_userInfo = $row;
            $this->createSession();
            return true;

        }else{
            $this->login_error_text = "Incorrect username / password";
            return false;
        }

    }
    public function logout(){
        session_destroy();
    }
    public function loginError(){
        return $this->login_error_text;
    }
    public function adminLevel(){
        return $_SESSION['pcp']['user']['level'];
    }
    public function adminPhone(){
        return $_SESSION['pcp']['user']['phone'];
    }
    public function adminName(){
        return $_SESSION['pcp']['user']['name'];
    }
    public function isSuperAdmin(){
        return ($this -> adminLevel() == "SUPERADMIN");
    }
    public function isAdmin(){
        return ($this -> isSuperAdmin() || $this -> adminLevel() == "ADMIN");
    }
    public function isSupervisor(){
        return ($this -> isAdmin() || $this -> adminLevel() == "SUPERVISOR");
    }
    public function isAgent(){
        return ($this -> isSupervisor() || $this -> adminLevel() == "AGENT");
    }
    public function checkSession(){
        //return true;
        $dyn_ip = explode(".",$_SERVER['REMOTE_ADDR']);
        $m_ip = $dyn_ip[0].".".$dyn_ip[1];
        $ip = md5($m_ip);
        $agent = md5($_SERVER['HTTP_USER_AGENT']);

        if(isset($_SESSION['pcp']['ip']) && $_SESSION['pcp']['ip'] != $ip)
            return false;
        if(isset($_SESSION['pcp']['agent']) && $_SESSION['pcp']['agent'] != $agent)
            return false;
        if(!isset($_SESSION['pcp']['user']['id']))
            return false;
        if(!isset($_SESSION['pcp']['user']['level']))
            return false;



        return true;
    }

    private function createSession(){
        $_SESSION['pcp']['user'] = $this->_userInfo;
        $dyn_ip = explode(".",$_SERVER['REMOTE_ADDR']);
        $m_ip = $dyn_ip[0].".".$dyn_ip[1];
        $_SESSION['pcp']['ip'] = md5($m_ip);
        $_SESSION['pcp']['agent'] = md5($_SERVER['HTTP_USER_AGENT']);
        return true;
    }


    public function createAdmin($info){
        $input_sql = array(
            "username" => $info['admin_user'],
            "password" => $info['admin_pass'],
            "name" => $info['admin_name'],
            "level" => $info['admin_level']
        );
        return \PATHAODB::table('lse_admin_users')->insert($input_sql);
    }

    public function editAdmin($info,$user){

        \PATHAODB::table('pathaocs')->where("id","=",$user)
            ->update($info);
        return true;

    }

    public function deleteAdmin($user){
        \PATHAODB::table('pathaocs')->where("id","=",$user)->delete();
    }

}