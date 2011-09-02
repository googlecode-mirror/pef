<?php

/**
 * @author henry
 * @copyright 2011
 */

require 'config.php';

class user
{

    private $db;
    public function user($db)
    {
        if (is_null($db)) {
            exit();
        }
        $this->db = $db;
    }

    /**
     * get user traffic
     */
    public function traffic($user)
    {
        $user = mysql_escape_string($user);
        $sql = "SELECT SUM( acctinputoctets + acctoutputoctets )FROM radacct WHERE username = '$user' AND date_format( acctstarttime, '%Y-%m-%d' ) >= date_format( now( ) , '%Y-%m-01' )AND date_format( acctstoptime, '%Y-%m-%d' ) <= last_day( now( ) ) >= \"SELECT value FROM radgroupreply WHERE groupname='user' AND attribute='Max-Monthly-Traffic'\"";
        //$sql = "SELECT SUM( acctinputoctets + acctoutputoctets )FROM radacct WHERE username = \'$user\' AND date_format( acctstarttime, \'%Y-%m-%d\' ) >= date_format( now( ) , \'%Y-%m-01\' ) AND date_format( acctstoptime, \'%Y-%m-%d\' ) <= last_day( now( ) ) >= \"SELECT value FROM radgroupreply WHERE groupname=\'user\' AND attribute=\'Max-Monthly-Traffic\'\"";
        $res = $this->db->fetchRow($sql);
        return $res;
    }

    public function online($start = 0, $end = 50)
    {
        $sql = "select * from radacct where acctstoptime is NULL order by acctstarttime limit $start,$end";
        $res = $this->db->fetchAll($sql);
        return $res;
    }

    public function all($start = 0, $end = 50)
    {
        $sql = "select * from radcheck where attribute = 'User-Password' order by id desc limit $start,$end";
        $res = $this->db->fetchAll($sql);
        return $res;
    }


    /**
     * connect time
     */
    public function long_times($user)
    {
        $sql = "select sum(acctsessiontime) from radacct where username = '$user'";
        $res = $this->db->fetchRow($sql);
        return $res;
    }

    public function expiration_date($user)
    {
        $sql = "select * from radcheck where attribute = 'Expiration' and username = '$user'";
        $res = $this->db->fetchRow($sql);
        return $res;
    }

    public function status($user)
    {
        $sql = "select radacctid from radacct where acctstoptime is NULL and username = '$user'";
        $res = $this->db->fetchRow($sql);
        if (count($res['radacctid']) == 1) {
            return true;
        }
        return false;
    }

    /**
     * get user connect ip address
     * @return ipaddress
     */
    public function connect_ip($user)
    {
        $res = $this->db->fetchRow("select framedipaddress from radacct where username = '$user' and acctstoptime is NULL");
        if ($res != null) {
            return $res['framedipaddress'];
        }
        return null;
    }

    /** get user connect vpn type
     * @return ppp or openvpn or null
     */
    public function vpn_type($user)
    {
        $res = $this->db->fetchRow("select nasporttype from radacct where username = '$user' and acctstoptime is NULL");
        if ($res != null) {
            if ($res['nasporttype'] == 'Async') {
                return 'ppp';
            } else {
                return 'openvpn';
            }
        }
        return null;
    }


    public function info($user)
    {
        $sql = "select * from radcheck,radusergroup where radcheck.username = radusergroup.username and radcheck.username = '$user'";
        $res = $this->db->fetchAll($sql);
        if ($res[0] == null) {
            return $res;
        }
        foreach ($res as $value) {
            if ($value['attribute'] == 'User-Password')
                $info['password'] = $value['value'];
            if ($value['attribute'] == 'Expiration')
                $info['expiration'] = $value['value'];
            $info['groupname'] = $value['groupname'];
        }
        $login_count_sql = "select count(radacctid) from radacct where username = '$user'";
        $res = $this->db->fetchRow($login_count_sql);
        $times = $res['count(radacctid)'];
        $info['logincounts'] = $times;
        $traffic = $this->traffic($user);
        $info['traffic'] = $traffic['SUM( acctinputoctets + acctoutputoctets )'];
        $login_times = $this->long_times($user);
        $info['logintimes'] = $login_times['sum(acctsessiontime)'];
        $info['status'] = $this->status($user);
        // get createtime
        $users = $this->db->fetchRow("select * from user where username = '$user'");
        $info['enabled'] = $users['enabled'];
        $info['email'] = $users['email'];
        $info['createtime'] = $users['createtime'];
        return $info;
    }

    public function query($sql)
    {
        return $this->db->query($sql);
    }

    /**
     * get user login host
     * @return node host info
     */
    public function login_node($user)
    {
        $user_connect_ip = $this->db->fetchRow("select framedipaddress from radacct where username = '$user' and acctstoptime is NULL");
        $user_connect_ip = $user_connect_ip['framedipaddress'];
        if ($user_connect_ip != null) {
            $tmp = explode('.', $user_connect_ip);
            //  print_r($tmp);
            $vpn_id = $tmp[1];
            $node_info = $this->db->fetchRow("select * from client_node where vpn_id =$vpn_id");
            return $node_info;
        }
        return null;
    }


    public function kick($user)
    {
        $node_host = $this->login_node($user);
        if ($node_host != null) {
            $node_vpn_ip = $node_host['vpn_ip'];
            $connect_ip = $this->connect_ip($user);
            $tmp = explode('.', $connect_ip);
            $host_ip = $tmp[0] . '.' . $tmp[1] . '.' . $tmp[2] . '.1';
            $vpn_type = $this->vpn_type($user);
            if($vpn_type == 'ppp')
                // request kick ppp user
                $url = 'http://' . $node_vpn_ip . ':8081/kill_ppp.php?type=' . $vpn_type .'&remote_ip=' . $connect_ip . '&local_ip=' . $host_ip;
            else
                $url = 'http://' . $node_vpn_ip . ':8081/kill_ppp.php?type=' . $vpn_type .'&username=' . $user;
            echo $url;
            $res = file_get_contents($url);
            if($res != null){
                $res = json_decode(trim($res));
                var_dump($res);
                // return ok
               // if($res->status == 'ok'){
                    // update database
                    $time = date("Y-m-d H:i:s");
                    $sql = "update radacct  set acctstoptime = '$time' where acctstoptime is NULL and username = '$user'";
                    //echo $sql;
                    $this->db->query($sql);
             //   }
                return $res;
            }else{
                return false;
            }
        }
        return false;
    }
    
    public function disable($user){
        // disabled user
        $this->db->query("update user set enabled = 0 where username = '$user'");
        // change userpassword
        $userpassword = $this->db->fetchRow("select value from radcheck where username = '$user' and attribute = 'User-Password'");
        //print_r($userpassword);
        $password = $userpassword['value'];
        $changed_password = base64_encode($password);
        //echo $changed_password;
        $this->db->query("update radcheck set value = '$changed_password' where username = '$user' and attribute = 'User-Password'");
        return true;
    }
    
    public function enable($user){
        // enabled user
        $this->db->query("update user set enabled = 1 where username = '$user'");
        // change userpassword
        $userpassword = $this->db->fetchRow("select value from radcheck where username = '$user' and attribute = 'User-Password'");
        //print_r($userpassword);
        $password = $userpassword['value'];
        $changed_password = base64_decode($password);
        //echo $changed_password;
        $this->db->query("update radcheck set value = '$changed_password' where username = '$user' and attribute = 'User-Password'");
        return true;
    }
    
    
    public function add($userinfo){
        // check user exits
        $username = $userinfo['username'];
        $res = $this->db->fetchAll("select * from user where username = '$username'");
        if(count($res) > 0){
            return "User exist";
        }
        $radcheck['username'] = $userinfo['username'];
        $radcheck['value'] = $userinfo['password'];
        $radcheck['attribute'] = 'User-Password';
        $radcheck['op'] = ':=';
        print_r($userinfo);
        $res = $this->db->insert('radcheck',$radcheck);
        // add Expiration
        unset($radcheck);
        $radcheck['username'] = $userinfo['username'];
        $radcheck['value'] = $userinfo['expiration'];
        $radcheck['attribute'] = 'Expiration';
        $radcheck['op'] = ':=';
        $res = $this->db->insert('radcheck',$radcheck);
        // add group
        $group['groupname'] = 'user';
        $group['priority'] = 1;
        $group['username'] = $username;
        $res = $this->db->insert('radusergroup',$group);
        // add to user table
        $insert['username'] = $username;
        $insert['email'] = $userinfo['email'];
        $insert['createtime'] = date("Y-m-d H:i:s");
        $insert['password'] = $userinfo['password'];
        $res = $this->db->insert('user',$insert);
        return $res;
    }
    
    
    public function delete($user){
        //check user exits
        $check = "select * from user where username = '$user'";
        $check = $this->db->fetchAll($check);
        if(count($check) == 0){
            return false;
        }
        $sql = "delete from user where username = '$user';delete from radcheck where username = '$user';delete from radusergroup where username = '$user'";
        $res = $this->db->query($sql);
        if($res != false){
            return true;
        }
        return true;
    }


}


class node
{
    private $db;
    public function node($db)
    {
        if (is_null($db)) {
            exit();
        }
        $this->db = $db;
    }

    public function node_list()
    {
        $res = $this->db->fetchAll("select * from client_node");
        return $res;
    }
    
    public function node_online_user_count($vpn_id){
        $sql = "SELECT count(*) FROM  `radacct` WHERE  `framedipaddress` REGEXP  ('10.$vpn_id.[0-9]{1,3}.[0-9]{1,3}') and acctstoptime is NULL ";
        $res = $this->db->fetchRow($sql);
        return $res['count(*)'];
    }

}


function formatbytes($size, $type = 'MB')
{
    $size = intval($size);
    switch ($type) {
        case "KB":
            $filesize = $size * .0009765625; // bytes to KB
            break;
        case "MB":
            $filesize = $size * .0009765625 * .0009765625; // bytes to MB
            break;
        case "GB":
            $filesize = $size * .0009765625 * .000976562 * .0009765625; // bytes to GB
            break;
    }
    if ($filesize <= 0) {
        return $filesize = '0';
    } else {
        return round($filesize, 2) . ' ' . $type;
    }
}


function sectohour($sec)
{
    $hour = $sec / 3600;
    return round($hour, 2);
}

/** change date formate
 * $date  eg: 21 Aug 2011 12:12:12
 * $return eg: 2011-08-21 12:12:12
 */ 
function change_date_view($date){
    $format = 'd M Y H:i:s';
    if($date == ''){
        $date = date($format);
    }
    $dates = DateTime::createFromFormat($format, $date);
    return $dates->format('Y-m-d');
    //return date('Y-m-d H:i:s',$date);
}

?>