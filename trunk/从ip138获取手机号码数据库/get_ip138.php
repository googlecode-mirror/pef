#!/usr/bin/php
<?php

/**
 * @author tang
 * @email chijiao@gmail.com
 * @website www.unxmail.com
 * @copyright 2010
 */


$start = $argv[1];
if ($start == '') {
    echo "Input error \r\n";
    exit();
}


$link = mysql_connect('localhost', 'root', '');
mysql_select_db($start, $link);
if (file_exists('status')) {
    $s = trim(file_get_contents('status'));
    $s = intval($s);
} else {
    $s = 1;
}

for ($i = $s; $i < 10000; $i++) {
    if (strlen($i) == 1) {
        $ii = '000' . $i;
    } else
        if (strlen($i) == 2) {
            $ii = '00' . $i;
        } else
            if (strlen($i) == 3) {
                $ii = '0' . $i;
            }else{
				$ii = $i;
			}
    $num = $start . $ii;
    $url = 'http://www.ip138.com:8080/search.asp?action=mobile&mobile=' . $num;
    //get data
    get_data($num, $url);
    //recode status
    file_put_contents('status', $ii);
}


mysql_close($link);


/**
 * Insert to mysql database
 * $num: mobile number
 */
function insert_db($num, $data)
{
    global $link, $start;

    $address = strip_tags(trim($data[0]));
    $type = strip_tags(trim($data[1]));
    $zipcode = strip_tags(trim($data[2]));
    $sql = "INSERT INTO `mobile`.`$start` (`num`, `address`, `type`, `zipcode`) VALUES ('$num', '$address', '$type', '$zipcode')";
    //  echo $sql;
    $res = mysql_query($sql, $link);
    if ($res) {
        file_put_contents('status', $num);
        return true;
    } else {
        //error
        $fp = fopen('insert_error', 'a+');
        fputs($fp, $num . '\r\n');
        fclose($fp);
        return false;
    }
}


function get_data($num, $url)
{
    $str = file_get_contents($url);
    $str = trim($str);
    $str = strtolower($str);
    if ($str == '') {
        $str = file_get_contents($url);
    }
    if ($str == '') {
        // recode error
        $fp = fopen('cantdown_' . $num, 'a+');
        fputs($fp, $num . '\r\n');
        fclose($fp);
    } else {
        $data = parse_html($str);
        if (insert_db($num, $data)) {
            echo $num . "is ok \r\n";
        }
    }
}

function parse_html($str)
{
    preg_match_all('/<td width=[\S\s]*>[\s\S]*<\/td>/', $str, $arr);
    $str = $arr[0];
    $arr = explode('</td>', $str[0]);
    $address = $arr[3];
    preg_match('/>[\S]*/', $address, $a);
    $address = str_replace('&nbsp;', ' ', $a[0]);
    $address = substr($address, 1);
    $address = trim($address);
    if ($address == '未知') {
        $address = '0';
    }
    $type = $arr[5];
    preg_match('/>[\S]*/', $type, $a);
    $type = str_replace('&nbsp;', ' ', $a[0]);
    $type = substr($type, 1);
    $type = trim($type);
    if ($type == '未知') {
        $type = '0';
    }

    $zipcode = $arr[7];
    preg_match('/>[\S]*/', $zipcode, $a);
    $zipcode = str_replace('&nbsp;', ' ', $a[0]);
    $zipcode = substr($zipcode, 1);
    if ($zipcode == '') {
        $zipcode = '0';
    }

    $data[0] = $address;
    $data[1] = $type;
    $data[2] = $zipcode;
    return $data;
}




?>