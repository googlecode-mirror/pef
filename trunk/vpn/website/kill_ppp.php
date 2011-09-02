<?php

$local_ip = trim($_GET['local_ip']);
$remote_ip = trim($_GET['remote_ip']);
$type = trim($_GET['type']);


if ($type == 'ppp') {


    preg_match('/[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}/', $local_ip, $matches);
    $local_ip = $matches[0];
    preg_match('/[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}/', $remote_ip, $matches);
    $remote_ip = $matches[0];


    $return = array('status' => '', 'message' => '');

    if ($local_ip == null || $remote_ip == null) {
        $return['status'] = 'error';
        $return['message'] = 'parameter error';
        echo json_encode($return);
        exit();
    }

    $cmd = 'sudo /usr/bin/kill_ppp.sh ' . $local_ip . ' ' . $remote_ip;
    system($cmd, $rvar);

    if ($rvar == 0) {
        $return['status'] = 'ok';
        $return['message'] = 'ok';
        echo json_encode($return);
        exit();
    } else {
        $return['status'] = 'error';
        $return['message'] = 'exec command failed';
        echo json_encode($return);
        exit();
    }
}else if($type == 'openvpn'){
    
    
}

?>