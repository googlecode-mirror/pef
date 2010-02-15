<?php

/**
 * @author 
 * @copyright 2010
 */

// This code demonstrates how to lookup the country by IP Address

include ("geoip.inc");

// Uncomment if querying against GeoIP/Lite City.
// include("geoipcity.inc");

$gi = geoip_open("GeoIP.dat", GEOIP_STANDARD);
$ip = getRealIpAddr();
$code = geoip_country_code_by_addr($gi, $ip);
$addr = geoip_country_name_by_addr($gi, $ip);
//	echo $code;
//	echo $addr;

geoip_close($gi);


function getRealIpAddr()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) //check ip from share internet
        {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
    //to check ip is pass from proxy
        {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

?>