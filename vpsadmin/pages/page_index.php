<?php
/*
    ./pages/page_index.php

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
$xtpl->title(_("Cluster statistics"));

$xtpl->table_add_category('');
$xtpl->table_add_category('');
$xtpl->table_td(_("Members total:"));
	$clenu = 0;
	$sql = 'SELECT * FROM members WHERE m_id != 2';
	$result = $db->query($sql);
	while ($db->fetch_row($result)) $clenu++;
$xtpl->table_td($clenu);
$xtpl->table_tr();
$xtpl->table_td(_("Total number of VPS:"));
	$clenu = 0;
	$sql = 'SELECT * FROM vps';
	$result = $db->query($sql);
	while ($db->fetch_row($result)) $serveru++;
$xtpl->table_td($serveru);
$xtpl->table_tr();
$xtpl->table_td(_("Total number of free IPv4 addresses:"));
	$ip4 = count((array)get_free_ip_list(4));
$xtpl->table_td($ip4);
$xtpl->table_tr();

$xtpl->table_out();

$xtpl->table_add_category(_("Node"));
$xtpl->table_add_category(_("Availability"));
$xtpl->table_add_category(_("VPS total"));
$xtpl->table_add_category(_("Free VPS slots"));
$xtpl->table_add_category(_("Last update"));
$xtpl->table_add_category(_("vpsAdmin version"));
$xtpl->table_add_category('');
	$sql = 'SELECT * FROM servers';
	$rslt = $db->query($sql);
	while ($srv = $db->fetch_array($rslt)) {
		$xtpl->table_td($srv["server_name"]);
		if ($srv["server_availstat"]) $xtpl->table_td($srv["server_availstat"]);
		else $xtpl->table_td('---');
		$sql = 'SELECT * FROM servers_status WHERE server_id ="'.$srv["server_id"].'" ORDER BY id DESC LIMIT 1';
		if ($result = $db->query($sql))
		    $status = $db->fetch_array($result);
		$sql = 'SELECT COUNT(*) AS count FROM vps WHERE vps_server='.$db->check($srv["server_id"]);
		$vpses = 0;
		if ($result = $db->query($sql))
		$vpses = $db->fetch_array($result);
		$xtpl->table_td($vpses["count"]);
		$xtpl->table_td(($srv["server_maxvps"]-$vpses["count"]));
		$xtpl->table_td(date('Y-m-d H:i:s', $status["timestamp"]).' ('.date('i:s', (time()-$status["timestamp"])).')');
		$xtpl->table_td($status["vpsadmin_version"]);
		$icons = "";
		if ($cluster_cfg->get("lock_cron_".$srv["server_id"]))
		    $icons .= '<img title="'._("The server is currently processing").'" src="template/icons/warning.png"/>';
		elseif ((time()-$status["timestamp"]) > 360)
		    $icons .= '<img title="'._("The server is not responding").'" src="template/icons/error.png"/>';
		else
		    $icons .= '<img title="'._("The server is online").'" src="template/icons/server_online.png"/>';
		$xtpl->table_td($icons);
		$xtpl->table_tr();
	}
$xtpl->table_out();

$xtpl->table_add_category($cluster_cfg->get('page_index_info_box_title'));
$xtpl->table_td($cluster_cfg->get('page_index_info_box_content'));
$xtpl->table_tr();
$xtpl->table_out();
?>
