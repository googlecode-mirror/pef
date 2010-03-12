<?php
/*
    ./pages/page_adminm.php

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
function print_newm() {
	global $xtpl, $cfg_privlevel;
	$xtpl->title(_("Add a member"));
	$xtpl->table_add_category('&nbsp;');
	$xtpl->table_add_category('&nbsp;');
	$xtpl->table_add_category('&nbsp;');
	$xtpl->form_create('?page=adminm&section=members&action=new2', 'post');
	$xtpl->form_add_input(_("Nickname").':', 'text', '30', 'm_nick', '', _("A-Z, a-z, dot, dash"), 63);
	$xtpl->form_add_select(_("Privileges").':', 'm_level', $cfg_privlevel, '2',  ' ');
	$xtpl->form_add_input(_("Password").':', 'password', '30', 'm_pass', '', _(""), -5);
	$xtpl->form_add_input(_("Repeat password").':', 'password', '30', 'm_pass2', '', ' ');
	$xtpl->form_add_input(_("Full name").':', 'text', '30', 'm_name', '', _("A-Z, a-z, with diacritic"), 255);
	$xtpl->form_add_input(_("E-mail").':', 'text', '30', 'm_mail', '', ' ');
	$xtpl->form_add_input(_("Postal address").':', 'text', '30', 'm_address', '', ' ');
	$xtpl->form_add_textarea(_("Info").':', 28, 4, 'm_info', '', _("Note for administrators"));
	$xtpl->form_out(_("Add"));
}

function print_editm($member) {
	global $xtpl, $cfg_privlevel;
	$xtpl->title(_("Manage members"));
	$xtpl->table_add_category('&nbsp;');
	$xtpl->table_add_category('&nbsp;');
	$xtpl->table_add_category('&nbsp;');
	$xtpl->form_create('?page=adminm&section=members&action=edit2&id='.$_GET["id"], 'post');
	$xtpl->form_add_input(_("Nickname").':', 'text', '30', 'm_nick', $member->m["m_nick"], _("A-Z, a-z, dot, dash"), 63);
	if ($_SESSION["is_admin"]) $xtpl->form_add_select(_("Privileges").':', 'm_level', $cfg_privlevel, $member->m["m_level"],  '');
	else {$xtpl->table_td(_("Privileges").':'); $xtpl->table_td($cfg_privlevel[$member->m["m_level"]]); $xtpl->table_tr();}
	$xtpl->form_add_input(_("Password").':', 'password', '30', 'm_pass', '', _("fill in only when change is required"), -5);
	$xtpl->form_add_input(_("Repeat password").':', 'password', '30', 'm_pass2', '', _("fill in only when change is required"), -5);
	$xtpl->form_add_input(_("Full name").':', 'text', '30', 'm_name', $member->m["m_name"], _("A-Z, a-z, with diacritic"), 255);
	$xtpl->form_add_input(_("E-mail").':', 'text', '30', 'm_mail', $member->m["m_mail"], ' ');
	$xtpl->form_add_input(_("Postal address").':', 'text', '30', 'm_address', $member->m["m_address"], ' ');
	if ($_SESSION["is_admin"]) $xtpl->form_add_textarea(_("Info").':', 28, 4, 'm_info', $member->m["m_info"], _("Note for administrators"));
	$xtpl->form_out(_("Save"));
}

if ($_SESSION["logged_in"]) {
	if ($_SESSION["is_admin"])
		$xtpl->sbar_add('<img src="template/icons/m_add.png"  title="'._("New member").'" /> '._("New member"), '?page=adminm&section=members&action=new');
	$xtpl->sbar_out(_("Manage members"));
	switch ($_GET["action"]) {
		case 'new':
		if ($_SESSION["is_admin"]) {
			print_newm();
		}
			break;
		case 'new2':
		if ($_SESSION["is_admin"]) {
			$ereg_ok = false;
			if (ereg('^[a-zA-Z0-9\.\-]{1,63}$',$_REQUEST["m_nick"]))
				if (ereg('^[0-9]{1,4}$',$_REQUEST["m_level"]))
					if (($_REQUEST["m_pass"] == $_REQUEST["m_pass2"]) && (strlen($_REQUEST["m_pass"]) >= 5))
							if (is_string($_REQUEST["m_mail"]))
									{
									$ereg_ok = true;
									$m = member_load();
									if (!$m->exists)
										if ($m->create_new($_REQUEST))
											$xtpl->perex(_("Member added"), _("Continue").' <a href="?page=adminm&section=members">'.strtolower(_("Here")).'</a>');
										else $xtpl->perex(_("Error"), _("Continue").' <a href="?page=adminm&section=members">'.strtolower(_("Here")).'</a>');
									else $xtpl->perex(_("Error").': '._("User already exists"), _("Continue").' <a href="?page=adminm&section=members">'.strtolower(_("Here")).'</a>');
									}
							else $xtpl->perex(_("Invalid entry").': '._("E-mail"),'');
					else $xtpl->perex(_("Invalid entry").': '._("Password"),'');
				else $xtpl->perex(_("Invalid entry").': '._("Privileges"),'');
			else $xtpl->perex(_("Invalid entry").': '._("Nickname"),'');
			if (!$ereg_ok) {
				print_newm();
				}
		}
			break;
		case 'delete':
		if ($_SESSION["is_admin"] && ($m = member_load($_GET["id"]))) {
			$xtpl->perex(_("Are you sure, you want to delete").' '.$m->m["m_nick"].'?', '<a href="?page=adminm&section=members">'.strtoupper(_("User already exists")).'</a> | <a href="?page=adminm&section=members&action=delete2&id='.$_GET["id"].'">'.strtoupper(_("Yes")).'</a>');
		}
			break;
		case 'delete2':
		if ($_SESSION["is_admin"]) {
			if ($m = member_load($_GET["id"]))
				if ($m->destroy())
					$xtpl->perex(_("Member deleted"), _("Continue").' <a href="?page=adminm&section=members">'.strtolower(_("Here")).'</a>');
				else $xtpl->perex(_("Error"), _("Continue").' <a href="?page=adminm&section=members">'.strtolower(_("Here")).'</a>');
			break;
		}
		case 'edit':
			if ($member = member_load($_GET["id"])) {
				print_editm($member);
			}
			break;
		case 'edit2':
			$ereg_ok = false;
			$member = member_load($_GET["id"]);
			if (ereg('^[a-zA-Z0-9\.\-]{1,63}$',$_REQUEST["m_nick"]))
				if (ereg('^[0-9]{1,4}$',$_REQUEST["m_level"]) || (!$_SESSION["is_admin"]))
					if (($_REQUEST["m_pass"] == $_REQUEST["m_pass2"]))
							if (is_string($_REQUEST["m_mail"]))
									{
									$ereg_ok = true;
									if ($member->exists) {
										$member->m["m_nick"] = $_REQUEST["m_nick"];
										if ($_SESSION["is_admin"]) $member->m["m_level"] = $_REQUEST["m_level"];
										if (($_REQUEST["m_pass"] != '') && (strlen($_REQUEST["m_pass"]) >= 5))
											$member->m["m_pass"] = md5($_REQUEST["m_nick"].$_REQUEST["m_pass"]);
										$member->m["m_name"] = $_REQUEST["m_name"];
										$member->m["m_mail"] = $_REQUEST["m_mail"];
										$member->m["m_address"] = $_REQUEST["m_address"];
										$member->m["m_info"] = $_REQUEST["m_info"];
										if ($member->save_changes())
											$xtpl->perex(_("Changes saved"), _("Continue").' <a href="?page=adminm&section=members">'.strtolower(_("Here")).'</a>');
										else
											$xtpl->perex(_("No change"), _("Continue").' <a href="?page=adminm&section=members">'.strtolower(_("Here")).'</a>');
									} else $xtpl->perex(_("Error"), _("Continue").' <a href="?page=adminm&section=members">'.strtolower(_("Here")).'</a>');
									}
							else $xtpl->perex(_("Invalid entry").': '._("E-mail"),'');
					else $xtpl->perex(_("Invalid entry").': '._("Password"),'');
				else $xtpl->perex(_("Invalid entry").': '._("Privileges"),'');
			else $xtpl->perex(_("Invalid entry").': '._("Nickname"),'');
			if (!$ereg_ok) {
				print_editm($member);
			}
			break;
		case 'payset':
			if (($member = new member_load($_GET["id"])) && $_SESSION["is_admin"]) {
				$xtpl->title(_("Edit payments"));
				$xtpl->form_create('?page=adminm&section=members&action=payset2&id='.$_GET["id"], 'post');
				$xtpl->table_td(_("Paid until").':');
				if ($member->m["m_paid_until"] > 0)
					$lastpaidto = date('Y-m-d', $member->m["m_paid_until"]);
				else $lastpaidto = _("Never been paid");
				$xtpl->table_td($lastpaidto);
				$xtpl->table_tr();
				$xtpl->table_td(_("Nickname").':');
				$xtpl->table_td($member->m["m_nick"]);
				$xtpl->table_tr();
				$xtpl->form_add_input(_("Newly paid until").':', 'text', '30', 'paid_until', '', 'Y-m-d, eg. 2009-05-01');
				$xtpl->table_add_category('');
				$xtpl->table_add_category('');
				$xtpl->form_out(_("Save"));
			}
			break;
		case 'payset2':
			if (($member = member_load($_GET["id"]))&& $_SESSION["is_admin"]) {
				if ($member->set_paid_until($_REQUEST["paid_until"]))
					$xtpl->perex(_("Payment successfully set"), _("Continue").' <a href="?page=adminm&section=members">'.strtolower(_("Here")).'</a>');
				else $xtpl->perex(_("Error"), _("Continue").' <a href="?page=adminm&section=members">'.strtolower(_("Here")).'</a>');
			}
			break;

		default:
			if ($_SESSION["is_admin"])
				// 如果是管理员
				$xtpl->title(_("Manage members [Admin mode]"));
			else
				$xtpl->title(_("Manage members"));
			$xtpl->table_add_category('ID');
			$xtpl->table_add_category(_("NICKNAME"));
			$xtpl->table_add_category(_("FULL NAME"));
			$xtpl->table_add_category(_("E-MAIL"));
			$xtpl->table_add_category(_("LAST ACTIVITY"));
			$xtpl->table_add_category(_("PAYMENT"));
			$xtpl->table_add_category('');
			if ($_SESSION["is_admin"])
				$xtpl->table_add_category('');
			$listed_members = 0;
			if ($members = get_members_array())
				foreach ($members as $member) {
					$xtpl->table_td($member->m["m_id"]);
					$xtpl->table_td($member->m["m_nick"]);
					$xtpl->table_td($member->m["m_name"]);
					if (strlen($member->m["m_mail"]) > 20)
						$xtpl->table_td(substr($member->m["m_mail"], 0, 20).'...');
					else
						$xtpl->table_td($member->m["m_mail"]);
					$paid = $member->has_paid_now();
					if ($member->m["m_last_activity"]) {
					    // Month
					    if     (($member->m["m_last_activity"]+2592000) < time())
						$xtpl->table_td(date('Y-m-d H:i:s', $member->m["m_last_activity"]), '#FFF');
					    // Week
					    elseif (($member->m["m_last_activity"]+604800) < time())
						$xtpl->table_td(date('Y-m-d H:i:s', $member->m["m_last_activity"]), '#99FF66');
					    // Day
					    elseif (($member->m["m_last_activity"]+86400) < time())
						$xtpl->table_td(date('Y-m-d H:i:s', $member->m["m_last_activity"]), '#66FF33');
					    // Less
					    else
						$xtpl->table_td(date('Y-m-d H:i:s', $member->m["m_last_activity"]), '#33CC00');
					} else {
					    $xtpl->table_td("---", '#FFF');
					}
					if ($_SESSION["is_admin"])
						if ($paid == (-1))
							$xtpl->table_td('<a href="?page=adminm&section=members&action=payset&id='.$member->mid.'">'._("info. missing").'</a>', '#FF8C00');
						elseif ($paid == 0)
							$xtpl->table_td('<a href="?page=adminm&section=members&action=payset&id='.$member->mid.'"><b>'._("not paid!").'</b></a>', '#B22222');
						else {
						$paid_until = date('Y-m-d', $member->m["m_paid_until"]);
						$xtpl->table_td('<a href="?page=adminm&section=members&action=payset&id='.$member->mid.'">'._("Until").' '.$paid_until.'</a>', '#66FF66');
					} else
						if ($paid == (-1))
							$xtpl->table_td(_("info. missing"), '#FF8C00');
						elseif ($paid == 0)
							$xtpl->table_td('<b>'._("not paid!").'</b>', '#B22222');
						else {
							$paid_until = date('Y-m-d', $member->m["m_paid_until"]);
							$xtpl->table_td(_("Until").' '.$paid_until, '#66FF66');
						}
					$xtpl->table_td('<a href="?page=adminm&section=members&action=edit&id='.$member->mid.'"><img src="template/icons/m_edit.png"  title="'. _("Edit") .'" /></a>');
					// 如果是管理员,则显示删除
					if ($_SESSION["is_admin"])
						$xtpl->table_td('<a href="?page=adminm&section=members&action=delete&id='.$member->mid.'"><img src="template/icons/m_delete.png"  title="'. _("Delete") .'" /></a>');
					if ($_SESSION["is_admin"] && ($member->m["m_info"]!=''))
						$xtpl->table_td('<img src="template/icons/info.png" title="'._("Info").'"');

					if ($member->m["m_level"] >= PRIV_SUPERADMIN) $xtpl->table_tr('#22FF22');
					elseif ($member->m["m_level"] >= PRIV_ADMIN) $xtpl->table_tr('#66FF66');
					elseif ($member->m["m_level"] >= PRIV_POWERUSER) $xtpl->table_tr('#BBFFBB');
					else $xtpl->table_tr();
					$listed_members++;
				}
			$xtpl->table_out();
			if ($_SESSION["is_admin"]) {
				$xtpl->table_add_category(_("Members in total").':');
				$xtpl->table_add_category($listed_members);
				$xtpl->table_out();
			}
			break;
	}

} else $xtpl->perex(_("Access forbidden"), _("You have to log in to be able to access vpsAdmin's functions"));
?>
