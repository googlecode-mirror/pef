<?php

/**
 * @author henry
 * @copyright 2011
 */


$return = array('status' => 'ERROR', 'message' => 'OK');

$action = $_GET['action'];

switch ($action) {
    case 'disconnect':
        $arg_key = $_GET['arg_key'];
        $arg_value = $_GET['arg_value'];
        if($arg_key != 'client_ip'){
            $return['message'] = "输入参数错误，应该为客户连接后的IP地址";
            //$return = json_encode($return);
            echo $return['message'];
            exit(0);
        }
        if(trim($arg_value) == null){
            $return['message'] = "输入参数错误，未能传入客户端连接后的IP地址";
            //$return = json_encode($return);
            echo $return['message'];
            exit(0);
        }
        $cmd = "ps -ef | grep /usr/sbin/pppd |grep $arg_value | awk '{print $2}' ";
        echo $cmd;
        $pid = system($cmd,$rvar);
        if($rvar != 0){
            $return['message'] = "获取PID发生错误";
            //$return = json_encode($return);
            echo $return['message'];
            exit(0);
        }
        $pid = trim($pid);
        if(is_null($pid)){
            $return['message'] = "未获取到PID信息";
            //$return = json_encode($return);
            echo $return['message'];
            exit(0);
        }
        // 踢掉用户 
        $res = system("kill -9 $pid",$rvar);
        if($rvar != 0){
            $return['message'] = "未能退出PPPD连接," . trim($res);
            //$return = json_encode($return);
            echo $return['message'];
            exit(0);
        }
        $return['status'] = "OK";
        //$return = json_encode($return);
        echo $return['message'];
        exit(0);
    break;
    default:
        $return['message'] = 'Action 未知错误';
        $return['status'] = 'ERROR';
        //$return = json_encode($return);
        echo $return['message'];
}



?>