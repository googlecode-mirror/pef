<?php
/*
./index.php

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
define("CRON_MODE", false);

// Include libraries
include WWW_ROOT . 'lib/version.lib.php';
include WWW_ROOT . 'lib/xtemplate.lib.php';
include WWW_ROOT . 'lib/db.lib.php';
include WWW_ROOT . 'lib/functions.lib.php';
include WWW_ROOT . 'lib/transact.lib.php';
include WWW_ROOT . 'lib/vps.lib.php';
include WWW_ROOT . 'lib/members.lib.php';
include WWW_ROOT . 'lib/vps_status.lib.php';
include WWW_ROOT . 'lib/networking.lib.php';
include WWW_ROOT . 'lib/firewall.lib.php';
include WWW_ROOT . 'lib/cluster.lib.php';
include WWW_ROOT . 'lib/cluster_ip.lib.php';
include WWW_ROOT . 'lib/ajax.lib.php';
include WWW_ROOT . 'lib/lang.lib.php';
// include configuration
include WWW_ROOT . 'config_cfg.php';

// connect to database
$db = new sql_db(DB_HOST, DB_USER, DB_PASS, DB_NAME);


// Create a template class
$xtpl = new XTemplate(WWW_ROOT . 'template/template.html');
// Create a langauge class
$lang = new Lang($langs, $xtpl);

$xtpl->assign("L_USERNAME", _("Username"));
$xtpl->assign("L_PASSWORD", _("Password"));
$xtpl->assign("L_LOGIN", _("Login"));
$xtpl->assign("L_LOGOUT", _("Logout"));

$xtpl->assign("L_TRANSACTION_LOG", _("Transaction log"));
$xtpl->assign("L_LAST10", _("last 10"));
$xtpl->assign("L_ACTION", _("Action"));


if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"]) {
    $_member = member_load($_SESSION["member"]["m_id"]);
    if ($_member->has_not_expired_activity()) {
        $_member->touch_activity();
    } else {
        session_destroy();
        $_GET["page"] = "";
    }
}

$Cluster_ipv4 = new Cluster_ipv4($xtpl, $db);
$Cluster_ipv6 = new Cluster_ipv6($xtpl, $db);

switch ($_GET["page"]) {
    case 'adminvps':
        include WWW_ROOT . 'pages/page_adminvps.php';
        break;
    case 'about':
        include WWW_ROOT . 'pages/page_about.php';
        break;
    case 'login':
        include WWW_ROOT . 'pages/page_login.php';
        break;
    case 'adminm':
        include WWW_ROOT . 'pages/page_adminm.php';
        break;
    case 'transactions':
        include WWW_ROOT . 'pages/page_transactions.php';
        break;
    case 'networking':
        include WWW_ROOT . 'pages/page_networking.php';
        break;
    case 'cluster':
        include WWW_ROOT . 'pages/page_cluster.php';
        break;
    case 'status':
    	// 只有管理员,才能查看系统状态
    	if($_SESSION[is_admin]){
			include WWW_ROOT . 'pages/page_index.php';
		}else{
			include WWW_ROOT . 'pages/page_adminvps.php';
		}        
        break;
    case 'lang';
        $lang->change($_GET['newlang']);
        break;
    default:
        //include WWW_ROOT.'pages/page_index.php';
        include WWW_ROOT . 'pages/page_adminvps.php';
}


if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"]) {
    // 如果是管理员,才显示服务器状态信息
    if ($_SESSION[is_admin])
        $xtpl->menu_add(_("Status"), '?page=status', ($_GET["page"] == 'status'));
    $xtpl->menu_add(_("Manage VPS"), '?page=adminvps', ($_GET["page"] == 'adminvps'));
    $xtpl->menu_add(_("Manage Members"), '?page=adminm', ($_GET["page"] == 'adminm'));
    $xtpl->menu_add(_("Manage Networking"), '?page=networking', ($_GET["page"] ==
        'networking'));
    if ($_SESSION[is_admin]) {
        $xtpl->menu_add(_("Manage Cluster"), '?page=cluster', ($_GET["page"] ==
            'cluster'));
        $xtpl->menu_add(_("Transaction log"), '?page=transactions', ($_GET["page"] ==
            'transactions'));
    }
    //   $xtpl->menu_add(_("About vpsAdmin"),'?page=about', ($_GET["page"] == 'about'), true);
    // 页面右侧显示任务日志
    list_transactions();
    $xtpl->assign('AJAX_SCRIPT', ajax_getHTML('ajax.php?page=transactbox',
        'transactions', 5000));
} else {
    if ($_SESSION[is_admin])
        $xtpl->menu_add(_("Status"), '?page=status', ($_GET["page"] == 'status'));
    //  $xtpl->menu_add(_("About vpsAdmin"),'?page=about', ($_GET["page"] == 'about'), true);
}

$xtpl->logbox(isset($_SESSION["logged_in"]) ? $_SESSION["logged_in"] : false,
    isset($_SESSION["member"]) ? $_SESSION["member"]["m_nick"] : false);

$xtpl->adminbox($cluster_cfg->get("adminbox_content"));

$lang->lang_switcher();

$xtpl->parse('main');
$xtpl->out('main');
?>
