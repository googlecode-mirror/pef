<?php
/*
    ./page/page_networking.php

    vpsAdmin
    Web-admin interface for OpenVZ (see http://openvz.org)
    Copyright (C) 2008-2009 Pavel Snajdr, snajpa@snajpa.net
    Copyright (C) 2009 Frantisek Kucera, franta [at] vpsfree.cz

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
if ($_SESSION[logged_in]) {

$xtpl->title(_("Manage Networking"));

$show_list = false;
$dao = new FirewallDAO($GLOBALS["db"]);

switch ($_REQUEST[action]) {
/*    case "firewall":
	if ($dao->checkRights($_SESSION["is_admin"], $_REQUEST["ip"], $_SESSION["member"]["m_id"])) {
	    $xtpl->title(_("Firewall"));

	    $rules = $dao->getRules($_REQUEST["ip"]);
	    foreach ($rules as $rule) {
		$xtpl->table_td($rule->command);
		if ($rule->approved) {
		    $xtpl->table_td(_("Yes"));
		    $xtpl->table_tr("#CCFFCC");
		} else {
		    if ($_SESSION[is_admin])
			$xtpl->table_td(_("No").' <a href="?page=networking&action=firewall_approve_rule&ip='.$_REQUEST["ip"].'&id='.$rule->id.'"><img title="'._("Approve").'" src="template/icons/firewall_approve.png"/></a>');
		    else $xtpl->table_td(_("No"));
		    $xtpl->table_tr();
		}
	    }

	    $xtpl->sbar_add(_("Edit firewall rules"), "?page=networking&action=firewall_edit&ip=" . urlencode($_REQUEST["ip"]));
	    $xtpl->sbar_out(_("Firewall rules"));

	    $xtpl->table_add_category(_("Rule"));
	    $xtpl->table_add_category(_("Approved"));
	    $xtpl->table_out();

	} else {
	    $xtpl->perex(_("Access denied"), _("Insufficient privelege level to edit firewall of this IP"));
	}
	break;
    case "firewall_edit":
	if ($dao->checkRights($_SESSION["is_admin"], $_REQUEST["ip"], $_SESSION["member"]["m_id"])) {
	    $xtpl->title(_("Firewall"));
	    $rulesString = "";
	    $rules = $dao->getRules($_REQUEST["ip"]);
	    foreach ($rules as $rule) {
		$rulesString = $rulesString . $rule->command . "\n";
	    }
	    $xtpl->form_create("?page=networking&action=firewall_save&ip=" . urlencode($_REQUEST["ip"]), "post");
	    $xtpl->form_add_textarea(_("iptables rules"), 100, 10, "rules", $rulesString, "");
	    $xtpl->table_add_category('');
	    $xtpl->table_add_category('');
	    $xtpl->form_out(_("Save changes"));
	    $xtpl->helpbox(_("Help"), _("For more information please consult
					<a href=\"https://vpsfree.cz/trac/vpsfree.cz/wiki/UsersManual\">vpsAdmin's UsersManual</a>.
					"));
	} else {
	    $xtpl->perex(_("Access denied"), _("Insufficient privelege level to edit firewall of this IP"));
	}
	break;
    case "firewall_save":
	if ($dao->checkRights($_SESSION["is_admin"], $_REQUEST["ip"], $_SESSION["member"]["m_id"])) {
	    $rules = explode("\r\n", $_REQUEST["rules"]);
	    $dao->saveRules($rules, $_REQUEST["ip"]);
	    $dao->checkRules($_REQUEST["ip"]);
	    if ($rules_to_apply = $dao->getCheckedRules($_REQUEST["ip"])) {
		$ip = get_ip_by_id($_REQUEST["ip"]);
		$vps = vps_load($ip["vps_id"]);
		add_transaction_locationwide($_SESSION["member"]["m_id"], $vps->ve["vps_id"], T_FIREWALL_RELOAD, $rules_to_apply, $vps->get_location());
	    }
	    $xtpl->perex(_("Saved"), _("Your changes to firewall rules were saved. You can continue ").'<a href="?page=networking&action=firewall&amp;ip='.urlencode($_REQUEST["ip"]).'">'._(" here ").'</a>');
	} else {
	    $xtpl->perex(_("Access denied"), _("Insufficient privelege level to edit firewall of this IP"));
	}
	break;
    case "firewall_reload":
	if ($dao->checkRights($_SESSION["is_admin"], $_REQUEST["ip"], $_SESSION["member"]["m_id"])) {
	    if ($rules_to_apply = $dao->getCheckedRules($_REQUEST["ip"])) {
		$ip = get_ip_by_id($_REQUEST["ip"]);
		$vps = vps_load($ip["vps_id"]);
		add_transaction_locationwide($_SESSION["member"]["m_id"], $vps->ve["vps_id"], T_FIREWALL_RELOAD, $rules_to_apply, $vps->get_location());
		$xtpl->perex(_("Reload planned"),_("Reload of firewall was added to transaction log to process"));
	    } else {
		$xtpl->perex(_("Error"),_("Firewall will be NOT reloaded, possible causes:<br />a) There is at least one unapproved rule<br />b) There are no rules at all"));
	    }
	    $show_list = true;
	} else {
	    $xtpl->perex(_("Access denied"), _("Insufficient privelege level to edit firewall of this IP"));
	}
	break;
    case "firewall_flush":
	if ($dao->checkRights($_SESSION["is_admin"], $_REQUEST["ip"], $_SESSION["member"]["m_id"])) {
	    $ip = get_ip_by_id($_REQUEST["ip"]);
	    $vps = vps_load($ip["vps_id"]);
	    add_transaction_locationwide($_SESSION["member"]["m_id"], $vps->ve["vps_id"], T_FIREWALL_FLUSH, $_REQUEST["ip"], $vps->get_location());
	    $xtpl->perex("OK",'');
	} else {
	    $xtpl->perex(_("Access denied"), _("Insufficient privelege level to edit firewall of this IP"));
	}
	$show_list = true;
	break;
    case "firewall_approve_rule":
	if ($_SESSION["is_admin"]) {
	    $sql = 'UPDATE firewall SET approved = 1 WHERE id = '.$db->check($_REQUEST["id"]);
	    $db->query($sql);
	    $xtpl->perex(_("Rule approved"), _("You can continue").' <a href="?page=networking&action=firewall&ip='.$_REQUEST["ip"].'">'._("here").'.</a>');
	}
	break;*/
    default:
	$show_list = true;
}
if ($show_list) {
    $all_vpses = get_vps_array();
	$xtpl->table_add_category('');
	$xtpl->table_add_category('');
	$xtpl->table_add_category('');
	$xtpl->table_add_category('');
	$xtpl->table_add_category('');
/*	$xtpl->table_add_category('');
	$xtpl->table_add_category('');
	$xtpl->table_add_category('');*/
    if ($all_vpses)
    foreach ($all_vpses as $vps) {
	$vps_ips = $vps->iplist();
	$m = member_load($vps->ve["m_id"]);
	$xtpl->table_td($vps->ve["vps_id"]. ' '.$m->m["m_nick"].' ['.$vps->ve["vps_hostname"].']', '#5EAFFF; color:#FFF; font-weight:bold;', false, 1, (count($vps_ips)+1));
	$xtpl->table_td(_("IP Address"), '#5EAFFF; color:#FFF; font-weight:bold;');
	$xtpl->table_td(_("NIX [GB]"), '#5EAFFF; color:#FFF; font-weight:bold;');
	$xtpl->table_td(_("TRANZIT [GB]"), '#5EAFFF; color:#FFF; font-weight:bold;');
	$xtpl->table_td(_("TOTAL [GB]"), '#5EAFFF; color:#FFF; font-weight:bold;');
/*	$xtpl->table_td('', '#5EAFFF; color:#FFF; font-weight:bold;');
	$xtpl->table_td('', '#5EAFFF; color:#FFF; font-weight:bold;');
	$xtpl->table_td('', '#5EAFFF; color:#FFF; font-weight:bold;');*/
	$xtpl->table_tr();
	if ($vps_ips)
	foreach ($vps_ips as $ip) {
	    $xtpl->table_td($ip["ip_addr"]);
	    $traffic = $accounting->get_traffic_by_ip_this_month($ip["ip_addr"]);
	    if ($ip["ip_v"] == 4) {
		$xtpl->table_td(round($traffic["nix"]["total"]/1024/1024/1024, 2), false, true);
		$xtpl->table_td(round($traffic["tzt"]["total"]/1024/1024/1024, 2), false, true);
		$xtpl->table_td(round(($traffic["tzt"]["total"]+$traffic["nix"]["total"])/1024/1024/1024, 2), false, true);
	    } else {
		$xtpl->table_td('---',false,true);
		$xtpl->table_td('---',false,true);
		$xtpl->table_td(round(($traffic["nix"]["total"])/1024/1024/1024, 2), false, true);
	    }
/*	    if ($dao->hasUnapprovedRules($ip["ip_id"]))
		 $xtpl->table_td('<a href="?page=networking&action=firewall&ip='.$ip["ip_id"].'"><img title="'._("Firewall").'" src="template/icons/firewall.png"/><img title="'._("There is at least one unapproved rule").'" src="template/icons/error.png"/></a>');
	    else $xtpl->table_td('<a href="?page=networking&action=firewall&ip='.$ip["ip_id"].'"><img title="'._("Firewall").'" src="template/icons/firewall.png"/></a>');
	    $xtpl->table_td('<a href="?page=networking&action=firewall_reload&ip='.$ip["ip_id"].'"><img title="'._("Firewall reload").'" src="template/icons/firewall.png"/><img title="'._("Firewall reload").'" src="template/icons/firewall_reload.png"/></a>');
	    $xtpl->table_td('<a href="?page=networking&action=firewall_flush&ip='.$ip["ip_id"].'"><img title="'._("Firewall flush").'" src="template/icons/firewall.png"/><img title="'._("Firewall flush").'" src="template/icons/firewall_flush.png"/></a>');*/
	    $xtpl->table_tr();
	}
    }
    $xtpl->table_out();
}

} else $xtpl->perex(_("Access forbidden"), _("You have to log in to be able to access vpsAdmin's functions"));
?>