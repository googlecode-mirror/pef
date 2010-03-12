#!/usr/bin/php
<?php
/*
./cron.php

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


include '/etc/vpsadmin/config.php';
session_start();
define('CRON_MODE', true);
define('DEMO_MODE', false);

// Include libraries
include WWW_ROOT . 'lib/cli.lib.php';
include WWW_ROOT . 'lib/xtemplate.lib.php';
include WWW_ROOT . 'lib/db.lib.php';
include WWW_ROOT . 'lib/functions.lib.php';
include WWW_ROOT . 'lib/transact.lib.php';
include WWW_ROOT . 'lib/vps.lib.php';
include WWW_ROOT . 'lib/members.lib.php';
include WWW_ROOT . 'lib/vps_status.lib.php';
include WWW_ROOT . 'lib/networking.lib.php';
include WWW_ROOT . 'lib/version.lib.php';
include WWW_ROOT . 'lib/cluster.lib.php';
include WWW_ROOT . 'lib/cluster_status.lib.php';


$db = new sql_db(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$ggg = $cluster_cfg->get("lock_cron_" . SERVER_ID);
echo $ggg;
if (!$ggg) {
    $cluster_cfg->set("lock_cron_" . SERVER_ID, 1);
    do_all_transactions_by_server(SERVER_ID);
    update_all_vps_status();

    $all_ips = get_all_ip_list(6);
    if (DEMO_MODE) {
        $accounting->fake_update_traffic_table();
    } else {
        $accounting->update_traffic_table();
        foreach ($all_ips as $ip) {
            exec('ip -6 neigh add proxy ' . $ip . ' dev eth0');
        }
    }
    // cluster_status.lib.php
    update_server_status();
    $cluster_cfg->set("lock_cron_" . SERVER_ID, 0);
}
?>
