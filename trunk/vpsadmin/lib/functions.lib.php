<?php
/*
    ./lib/functions.lib.php

    vpsAdmin
    Web-admin interface for OpenVZ (see http://openvz.org)
    Copyright (C) 2008-2009 Pavel Snajdr, snajpa@snajpa.net

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

function members_list () {
	global $db;
	if ($_SESSION[is_admin]) {
		$sql = 'SELECT * FROM members';
		if ($result = $db->query($sql))
			while ($m = $db->fetch_array($result)) {
			$out[$m[m_id]] = $m[m_nick];
			}
		else $out = false;
		return $out;
	}
	else return array($_SESSION[member][m_id] => $_SESSION[member][m_nick]);
}

function get_all_ip_list ($v = 4) {
	global $db;
	$sql = "SELECT * FROM vps_ip WHERE ip_v = {$db->check($v)}";
	$ret = array();
	if ($result = $db->query($sql))
		while ($row = $db->fetch_array($result))
			$ret[$row[ip_id]] = $row[ip_addr];
	return $ret;
}
function get_all_ip_list_array () {
	global $db;
	$sql = "SELECT * FROM vps_ip";
	$ret = array();
	if ($result = $db->query($sql))
		while ($row = $db->fetch_array($result))
			$ret[] = $row;
	return $ret;
}
function get_ip_by_id($ip_id) {
	global $db;
	$sql = "SELECT * FROM vps_ip WHERE ip_id=".$db->check($ip_id);
	if ($result = $db->query($sql))
	    return $db->fetch_array($result);
}
function get_free_ip_list ($v = 4, $location=false) {
	global $db;
	$sql = 'SELECT * FROM vps_ip WHERE vps_id = 0 AND ip_v = "'.$db->check($v).'"';
	if ($location)
	    $sql .=  ' AND ip_location = "'.$db->check($location).'"';
	$ret = array();
	if ($result = $db->query($sql))
		while ($row = $db->fetch_array($result))
			$ret[$row[ip_addr]] = $row[ip_addr];
	return $ret;
}

function validate_ip_address($ip_addr) {
	global $Cluster_ipv4, $Cluster_ipv6;
	if ($Cluster_ipv4->check_syntax($ip_addr))
		return 4;
	elseif ($Cluster_ipv6->check_syntax($ip_addr))
		return 6;
	else
		return false;
}

function ip_exists_in_table($ip_addr) {
	global $db;
	$sql = 'SELECT ip_id,ip_addr,vps_id FROM vps_ip WHERE ip_addr = "'.$db->check($ip_addr).'"';
	if ($result = $db->query($sql))
		if ($row = $db->fetch_array($result))
			return $row;
		else return false;
	else return false;
}

function ip_is_free($ip_addr) {
	if (validate_ip_address($ip_addr))
		$ip_try = ip_exists_in_table($ip_addr);
	else return false;
	if (!$ip_try)
		return true;
	if ($ip_try[vps_id] == 0)
		return true;
	else return false;
}

function list_limit_diskspace() {
    global $db;
    $sql = 'SELECT * FROM cfg_diskspace';
    if ($result = $db->query($sql))
	while ($row = $db->fetch_array($result))
	    $ret[$row[d_id]] = $row[d_label];
    return $ret;
}

function limit_diskspace_by_id ($id) {
    global $db;
    $sql = 'SELECT * FROM cfg_diskspace WHERE d_id="'.$db->check($id).'" LIMIT 1';
    if ($result = $db->query($sql))
	if ($row = $db->fetch_array($result))
	    return $row;
    return false;
}

function list_limit_privvmpages($force = false) {
    global $db;
    $sql = 'SELECT * FROM cfg_privvmpages'.(($force) ? ' WHERE vm_usable=1' : '');
    if ($result = $db->query($sql))
	while ($row = $db->fetch_array($result))
	    $ret[$row[vm_id]] = $row[vm_label];
    return $ret;
}

function limit_privvmpages_by_id ($id) {
    global $db;
    $sql = 'SELECT * FROM cfg_privvmpages WHERE vm_id="'.$db->check($id).'" LIMIT 1';
    if ($result = $db->query($sql))
	if ($row = $db->fetch_array($result))
	    return $row;
    return false;
}

function list_templates() {
    global $db;
    $sql = 'SELECT * FROM cfg_templates';
    if ($result = $db->query($sql))
	while ($row = $db->fetch_array($result))
	    $ret[$row[templ_id]] = $row[templ_label];
    return $ret;
}

function template_by_id ($id) {
    global $db;
    $sql = 'SELECT * FROM cfg_templates WHERE templ_id="'.$db->check($id).'" LIMIT 1';
    if ($result = $db->query($sql))
	if ($row = $db->fetch_array($result))
	    return $row;
    return false;
}

function list_servers($without_id = false) {
    global $db;
	if ($without_id)
		$sql = 'SELECT * FROM servers WHERE server_id != '.$db->check($without_id);
	else
		$sql = 'SELECT * FROM servers';
    if ($result = $db->query($sql))
	{
		while ($row = $db->fetch_array($result)){
		$server_id = $row['server_id'];
		// ���Ƶ��������
		$server_max = $row['server_maxvps'];
		$sql = 'SELECT COUNT(*) AS count FROM vps WHERE vps_server='.$db->check($server_id);
		$vpses = 0;
		if ($res = $db->query($sql))
			$vpses = $db->fetch_array($res);
		$left_count = ($server_max-$vpses["count"]);
		// ����÷���������ʣ��vps������
		if($left_count > 0)
			$ret[$row[server_id]] = $row[server_name];
		}
	}
	
    return $ret;
}

function server_by_id ($id) {
    global $db;
    $sql = 'SELECT * FROM servers WHERE server_id="'.$db->check($id).'" LIMIT 1';
    if ($result = $db->query($sql))
	if ($row = $db->fetch_array($result))
	    return $row;
    return false;
}

?>
