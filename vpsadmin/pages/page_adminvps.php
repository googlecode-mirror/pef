<?php
/*
./pages/page_adminvps.php

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
function print_newvps()
{
    global $xtpl;
    $xtpl->title(_("Create VPS"));
    $xtpl->form_create('?page=adminvps&section=vps&action=new2&create=1', 'post');
    $xtpl->form_add_input(_("Hostname") . ':', 'text', '30', 'vps_hostname', '', _("A-z, a-z"),
        30);
    $xtpl->form_add_select(_("HW server") . ':', 'vps_server', list_servers(), '2',
        '');
    $xtpl->form_add_select(_("Owner") . ':', 'm_id', members_list(), '', '');
    $xtpl->form_add_select(_("Distribution") . ':', 'vps_template', list_templates(),
        '', '');
    $xtpl->form_add_select(_("RAM") . ':', 'vps_privvmpages', list_limit_privvmpages
        (), '1', '');
    $xtpl->form_add_select(_("Disk space") . ':', 'vps_diskspace',
        list_limit_diskspace(), '1', '');
    $xtpl->form_add_checkbox(_("Boot on create") . ':', 'boot_after_create', '1', true,
        $hint = '');
    $xtpl->form_add_textarea(_("Extra information about VPS") . ':', 28, 4,
        'vps_info', '', '');
    $xtpl->table_add_category('&nbsp;');
    $xtpl->table_add_category('&nbsp;');
    $xtpl->table_add_category('&nbsp;');
    $xtpl->form_out(_("Create"));
}


function print_editvps($vps)
{
}

if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"]) {

    $member_of_session = member_load($_SESSION["member"]["m_id"]);

    if ($_GET["run"] == 'stop') {
        $vps = vps_load($_GET["veid"]);
        $xtpl->perex_cmd_output(_("Stop VPS") . " {$_GET["veid"]} " . strtolower(_("planned")),
            $vps->stop());
    }

    if ($_GET["run"] == 'start') {
        if ($member_of_session->has_paid_now()) {
            $vps = vps_load($_GET["veid"]);
            $xtpl->perex_cmd_output(_("Start of") . " {$_GET["veid"]} " . strtolower(_("planned")),
                $vps->start());
        } else
            $xtpl->perex(_("Payment missing"), _("You are not allowed to make \"start\" opetarion.<br />Please pay all your fees. Once we receive all missing payments, your access will be resumed."));
    }

    if ($_GET["run"] == 'restart') {
        $vps = vps_load($_GET["veid"]);
        $xtpl->perex_cmd_output(_("Restart of") . " {$_GET["veid"]} " . strtolower(_("planned")),
            $vps->restart());
    }

    switch ($_GET["action"]) {
        case 'new':
            print_newvps();
            break;
        case 'new2':
            if ((ereg('^[a-zA-Z0-9\.\-]{1,30}$', $_REQUEST["vps_hostname"]) && $_GET["create"] &&
                ($diskspace = limit_diskspace_by_id($_REQUEST["vps_diskspace"])) && ($privvmpages =
                limit_privvmpages_by_id($_REQUEST["vps_privvmpages"]))) && ($server =
                server_by_id($_REQUEST["vps_server"]))) {
                if (!$vps->exists)
                    $vps = vps_load($_REQUEST["veid"]);
                if (!$vps->exists) {
                    $perex = $vps->create_new($server["server_id"], $_REQUEST["vps_template"], $_REQUEST["vps_hostname"],
                        $_REQUEST["m_id"], $_REQUEST["vps_privvmpages"], $_REQUEST["vps_diskspace"], $_REQUEST["vps_info"]);
                    $veid = $vps->veid;
                    if ($_REQUEST["boot_after_create"]) {
                        $vps->start();
                        $xtpl->perex(_("VPS create ") . ' ' . $vps->veid, _("VPS will be created and booted afterwards."));
                    } else
                        $xtpl->perex(_("VPS create ") . ' ' . $vps->veid, _("VPS will be created. You can start it manually."));
                    $list_vps = true;
                } else {
                    $xtpl->perex(_("Error"), _("VPS already exists"));
                    $list_vps = true;
                }
            } else {
                $xtpl->perex(_("Error"), _("Wrong hostname name"));
                print_newvps();
            }
            break;
        case 'delete':
            $xtpl->perex(_("Are you sure you want to delete VPS number") . ' ' . $_GET["veid"] .
                '?', '<a href="?page=adminvps&section=ps">' . strtoupper(_("No")) .
                '</a> | <a href="?page=adminvps&section=vps&action=delete2&veid=' . $_GET["veid"] .
                '">' . strtoupper(_("Yes")) . '</a>');
            break;
        case 'delete2':
            if (!$vps->exists)
                $vps = vps_load($_REQUEST["veid"]);
            $xtpl->perex_cmd_output(_("Deletion of VPS") . " {$_GET["veid"]} " . strtolower
                (_("planned")), $vps->destroy());
            $list_vps = true;
            break;
        case 'info':
            $show_info = true;
            break;
        case 'passwd':
            if (!$vps->exists)
                $vps = vps_load($_REQUEST["veid"]);
            if (($_REQUEST["pass"] == $_REQUEST["pass2"]) && (strlen($_REQUEST["pass"]) >= 5) &&
                (strlen($_REQUEST["user"]) >= 2))
                $xtpl->perex_cmd_output(_("Change of user's password") . ' ' . $_REQUEST["user"] .
                    ' ' . strtolower(_("planned")), $vps->passwd($_REQUEST["user"], $_REQUEST["pass"]));
            else
                $xtpl->perex(_("Error"), _("Wrong username or unsafe password"));
            $show_info = true;
            break;
        case 'hostname':
            if (!$vps->exists)
                $vps = vps_load($_REQUEST["veid"]);
            if (ereg('^[a-zA-Z0-9\.\-]{1,30}$', $_REQUEST["hostname"]))
                $xtpl->perex_cmd_output(_("Hostname change planned"), $vps->set_hostname($_REQUEST["hostname"]));
            else
                $xtpl->perex(_("Error"), _("Wrong hostname name"));
            $show_info = true;
            break;
        case 'editlimits':
            if (isset($_REQUEST["veid"]) && $_SESSION["is_admin"] && ($diskspace =
                limit_diskspace_by_id($_REQUEST["vps_diskspace"])) && ($privvmpages =
                limit_privvmpages_by_id($_REQUEST["vps_privvmpages"]))) {
                if (!$vps->exists)
                    $vps = vps_load($_REQUEST["veid"]);
                $vps->set_privvmpages($_REQUEST["vps_privvmpages"]);
                $vps->set_diskspace($_REQUEST["vps_diskspace"]);
                $xtpl->perex_cmd_output(_("Limits change") . " {$vps->veid} " . strtolower(_("planned")),
                    $out);
                $show_info = true;
            } else {
                $xtpl->perex(_("Error"), 'Error, contact your administrator');
                $show_info = true;
            }
            break;
        case 'chown':
            if (($_REQUEST["m_id"] > 0) && isset($_REQUEST["veid"]) && $_SESSION["is_admin"]) {
                if (!$vps->exists)
                    $vps = vps_load($_REQUEST["veid"]);
                if ($vps->vchown($_REQUEST["m_id"]))
                    $xtpl->perex(_("Owner change"), '' . strtolower(_("planned")));
                else
                    $xtpl->perex(_("Error"), '');
            } else
                $xtpl->perex(_("Error"), '');
            $show_info = true;
            break;
        case 'addip':
            if (isset($_REQUEST["veid"]) && $_SESSION["is_admin"]) {
                if (!$vps->exists)
                    $vps = vps_load($_REQUEST["veid"]);
                if (ip_is_free($_REQUEST["ip_recycle"]))
                    $xtpl->perex_cmd_output(_("Addition of IP planned") . " {$_REQUEST["ip"]}", $vps->
                        ipadd($_POST["ip_recycle"]));
                elseif (ip_is_free($_REQUEST["ip6_recycle"]))
                    $xtpl->perex_cmd_output(_("Addition of IP planned") . " {$_REQUEST["ip"]}", $vps->
                        ipadd($_POST["ip6_recycle"]));
                else
                    $xtpl->perex(_("Error"), 'Contact your administrator');
            } else {
                $xtpl->perex(_("Error"), 'Contact your administrator');
            }
            $show_info = true;
            break;
        case 'delip':
            if ((validate_ip_address($_REQUEST["ip"])) && isset($_REQUEST["veid"]) && $_SESSION["is_admin"]) {
                if (!$vps->exists)
                    $vps = vps_load($_REQUEST["veid"]);
                $xtpl->perex_cmd_output(_("Deletion of IP planned") . " {$_REQUEST["ip"]}", $vps->
                    ipdel($_REQUEST["ip"]));
            } else {
                $xtpl->perex(_("Error"), 'Contact your administrator');
            }
            $show_info = true;
            break;
        case 'nameserver':
            if ((isset($_REQUEST["nameserver"])) && isset($_REQUEST["veid"])) {
                if (!$vps->exists)
                    $vps = vps_load($_REQUEST["veid"]);
                $xtpl->perex_cmd_output(_("DNS change planned"), $vps->nameserver($_REQUEST["nameserver"]));
            } else {
                $xtpl->perex(_("Error"), '');
            }
            $show_info = true;
            break;
        case 'offlinemigrate':
            if ($_SESSION["is_admin"] && isset($_REQUEST["veid"])) {
                if (!$vps->exists)
                    $vps = vps_load($_REQUEST["veid"]);
                $xtpl->perex_cmd_output(_("Offline migration planned"), $vps->offline_migrate($_REQUEST["target_id"]));
            } else {
                $xtpl->perex(_("Error"), '');
            }
            $show_info = true;
            break;
        case 'onlinemigrate':
            if ($_SESSION["is_admin"] && isset($_REQUEST["veid"])) {
                if (!$vps->exists)
                    $vps = vps_load($_REQUEST["veid"]);
                $xtpl->perex_cmd_output(_("Online Migration added to transaction log"), $vps->
                    online_migrate($_REQUEST["target_id"]));
            } else {
                $xtpl->perex(_("Error"), '');
            }
            $show_info = true;
            break;
        case 'alliplist':
            if ($_SESSION["is_admin"]) {
                $xtpl->title(_("List of IP addresses") . ' ' . _("[Admin mode]"));
                $Cluster_ipv4->table_used_out();
                $Cluster_ipv6->table_used_out();
                $xtpl->sbar_add(_("Back"), '?page=adminvps');
            } else
                $list_vps = true;
            break;
        case 'reinstall':
            if (!$vps->exists)
                $vps = vps_load($_REQUEST["veid"]);
            if ($_REQUEST["reinstallsure"] && $_REQUEST["vps_template"]) {
                $xtpl->perex(_("Are you sure you want to reinstall VPS") . ' ' . $_GET["veid"] .
                    '?', '<a href="?page=adminvps">' . strtoupper(_("No")) .
                    '</a> | <a href="?page=adminvps&action=reinstall2&veid=' . $_GET["veid"] . '">' .
                    strtoupper(_("Yes")) . '</a>');
                $vps->change_distro_before_reinstall($_REQUEST["vps_template"]);
            } else
                $list_vps = true;
            break;
        case 'reinstall2':
            if (!$vps->exists)
                $vps = vps_load($_REQUEST["veid"]);
            $xtpl->perex_cmd_output(_("Reinstallation of VPS") . " {$_GET["veid"]} " .
                strtolower(_("planned")) . '<br />' . _("You will have to reset your <b>root</b> password"),
                $vps->reinstall());
            $list_vps = true;
            break;
        case 'enabledevices':
            if (isset($_REQUEST["veid"]) && $_SESSION["is_admin"] && is_array($_REQUEST["devicesenabled"])) {
                if (!$vps->exists)
                    $vps = vps_load($_REQUEST["veid"]);
                $xtpl->perex_cmd_output(_("Enable devices"), $vps->enable_devices($_REQUEST["devicesenabled"]));
            } else {
                $xtpl->perex(_("Error"), '');
            }
            $show_info = true;
            break;
        default:
            // Vypsat všechny VPS registrované ve vpsAdminu
            $list_vps = true;
            break;
    }
    if ($list_vps) {
        if ($_SESSION["is_admin"])
            $xtpl->title(_("VPS list") . ' ' . _("[Admin mode]"));
        else
            $xtpl->title(_("VPS list") . ' ' . _("[User mode]"));
        $xtpl->table_add_category('ID');
        $xtpl->table_add_category('HW');
        $xtpl->table_add_category(strtoupper(_("owner")));
        $xtpl->table_add_category(strtoupper(_("processes")));
        $xtpl->table_add_category(strtoupper(_("hostname")));
        $xtpl->table_add_category(strtoupper(_("used RAM")));
        $xtpl->table_add_category(strtoupper(_("used HDD space")));
        $xtpl->table_add_category('');
        $xtpl->table_add_category('');
        $xtpl->table_add_category('');
        $all_vps = get_vps_array();
        $listed_vps = 0;
        if (is_array($all_vps))
            foreach ($all_vps as $vps) {
                $vps->info();
                $xtpl->table_td('<a href="?page=adminvps&action=info&veid=' . $vps->veid . '">' .
                    $vps->veid . '</a>');
                $xtpl->table_td($vps->ve["server_name"]);
                $xtpl->table_td($vps->ve["m_nick"]);
                $xtpl->table_td($vps->ve["vps_nproc"], false, true);
                $xtpl->table_td('<a href="?page=adminvps&action=info&veid=' . $vps->veid .
                    '"><img src="template/icons/vps_edit.png"  title="Upravit"/> ' . $vps->ve["vps_hostname"] .
                    '</a>');
                $xtpl->table_td(sprintf('%4d MB', $vps->ve["vps_vm_used_mb"]), false, true);
                if ($vps->ve["vps_disk_used_mb"] > 0)
                    $xtpl->table_td(sprintf('%.2f GB', round($vps->ve["vps_disk_used_mb"] / 1024, 2)), false, true);
                else
                    $xtpl->table_td('---', false, true);
                $xtpl->table_td(($vps->ve["vps_up"]) ?
                    '<a href="?page=adminvps&run=restart&veid=' . $vps->veid .
                    '"><img src="template/icons/vps_restart.png" title="' . _("Restart") . '"/></a>' :
                    '<img src="template/icons/vps_restart_grey.png"  title="' . _("Unable to restart") .
                    '" />');
                $xtpl->table_td(($vps->ve["vps_up"]) ? '<a href="?page=adminvps&run=stop&veid=' .
                    $vps->veid . '"><img src="template/icons/vps_stop.png"  title="' . _("Stop") .
                    '"/></a>' : '<a href="?page=adminvps&run=start&veid=' . $vps->veid .
                    '"><img src="template/icons/vps_start.png"  title="' . _("Start") . '"/></a>');
                $xtpl->table_td((!$vps->ve["vps_up"]) ?
                    '<a href="?page=adminvps&action=delete&veid=' . $vps->veid .
                    '"><img src="template/icons/vps_delete.png"  title="' . _("Delete") . '"/></a>' :
                    '<img src="template/icons/vps_delete_grey.png"  title="' . _("Unable to delete") .
                    '"/>');
                $xtpl->table_tr(($vps->ve["vps_up"]) ? false : '#FFCCCC');
                //$xtpl->table_td('', false, false, 3);
                //$xtpl->table_tr();
                $listed_vps++;
            }
        $_SESSION["member"]["number_owned_vps"] = count($all_vps);
        $xtpl->table_out();
        if ($_SESSION["is_admin"]) {
            $xtpl->table_add_category(_("Total number of VPS") . ':');
            $xtpl->table_add_category($listed_vps);
            $xtpl->table_out();
        }
        if ($_SESSION["is_admin"])
            $xtpl->sbar_add('<img src="template/icons/m_add.png"  title="' . _("New VPS") .
                '" /> ' . _("New VPS"), '?page=adminvps&section=vps&action=new');
        if ($_SESSION["is_admin"])
            $xtpl->sbar_add('<img src="template/icons/vps_ip_list.png"  title="' . _("List IP addresses") .
                '" /> ' . _("List IP addresses"), '?page=adminvps&action=alliplist');
    }
    if ($show_info) {
        if (!isset($veid))
            $veid = $_GET["veid"];
        if ($_SESSION["is_admin"])
            $xtpl->title(_("VPS details") . ' ' . _("[Admin mode]"));
        else
            $xtpl->title(_("VPS details") . ' ' . _("[User mode]"));
        if (!$vps->exists)
            $vps = vps_load($veid);
        $vps->info();
        $xtpl->table_add_category('&nbsp;');
        $xtpl->table_add_category('&nbsp;');
        $xtpl->table_td('ID:');
        $xtpl->table_td($vps->veid);
        $xtpl->table_tr();
        $xtpl->table_td(_("Owner") . ':');
        $xtpl->table_td($vps->ve["m_nick"]);
        $xtpl->table_tr();
        $xtpl->table_td(_("RAM") . ':');
        $privvmpages = limit_privvmpages_by_id($vps->ve["vps_privvmpages"]);
        $xtpl->table_td($privvmpages["vm_label"]);
        $xtpl->table_tr();
        $xtpl->table_td(_("Disk space") . ':');
        $diskspace = limit_diskspace_by_id($vps->ve["vps_diskspace"]);
        $xtpl->table_td($diskspace["d_label"]);
        $xtpl->table_tr();
        $xtpl->table_td(_("Status") . ':');
        $xtpl->table_td(($vps->ve["vps_up"]) ? _("running") .
            ' <a href="?page=adminvps&action=info&run=stop&veid=' . $vps->veid . '">(' . _("stop") .
            ')</a>' : _("stopped") . ' <a href="?page=adminvps&action=info&run=start&veid=' .
            $vps->veid . '">(' . _("start") . ')</a>');
        $xtpl->table_tr();
        $xtpl->table_td(_("Processes") . ':');
        $xtpl->table_td($vps->ve["vps_nproc"]);
        $xtpl->table_tr();
        $xtpl->table_td(_("Hostname") . ':');
        $xtpl->table_td($vps->ve["vps_hostname"]);
        $xtpl->table_tr();
        $xtpl->table_td(_("Distribution") . ':');
        $templ = template_by_id($vps->ve["vps_template"]);
        $xtpl->table_td($templ["templ_label"]);
        $xtpl->table_tr();
        $vps_location = $cluster->get_location_by_id($vps->get_location());
        $xtpl->table_td(_("Location") . ':');
        $xtpl->table_td($vps_location["location_label"]);
        $xtpl->table_tr();
        $xtpl->table_out();

        // Password changer
        $xtpl->form_create('?page=adminvps&action=passwd&veid=' . $vps->veid, 'post');
        $xtpl->form_add_input(_("Unix username") . ':', 'text', '30', 'user', 'root', '');
        $xtpl->form_add_input(_("Safe password") . ':', 'password', '30', 'pass', '', _
            (""), -5);
        $xtpl->form_add_input(_("Once again") . ':', 'password', '30', 'pass2', '', '');
        $xtpl->table_add_category(_("Set password"));
        $xtpl->table_add_category(_("(in your VPS, not in vpsAdmin!)"));
        $xtpl->form_out(_("Go >>"));

        // IP addresses
        if ($_SESSION["is_admin"]) {
            $xtpl->form_create('?page=adminvps&action=addip&veid=' . $vps->veid, 'post');
            if ($iplist = $vps->iplist())
                foreach ($iplist as $ip) {
                    if ($ip["ip_v"] == 4)
                        $xtpl->table_td(_("IPv4"));
                    else
                        $xtpl->table_td(_("IPv6"));
                    $xtpl->table_td($ip["ip_addr"]);
                    $xtpl->table_td('<a href="?page=adminvps&action=delip&ip=' . $ip["ip_addr"] .
                        '&veid=' . $vps->veid . '">(' . _("Remove") . ')</a>');
                    $xtpl->table_tr();
                }
            $tmp["0"] = '-------';
            $free_4 = array_merge($tmp, get_free_ip_list(4, $vps->get_location()));
            if ($vps_location["location_has_ipv6"])
                $free_6 = array_merge($tmp, get_free_ip_list(6, $vps->get_location()));
            $xtpl->form_add_select(_("Add IPv4 address") . ':', 'ip_recycle', $free_4, $vps->
                ve["m_id"]);
            if ($vps_location["location_has_ipv6"])
                $xtpl->form_add_select(_("Add IPv6 address") . ':', 'ip6_recycle', $free_6, $vps->
                    ve["m_id"]);
            $xtpl->table_tr();
            $xtpl->table_add_category(_("Add IP address"));
            $xtpl->table_add_category('&nbsp;');
            $xtpl->form_out(_("Go >>"));
        } else {
            $xtpl->table_add_category(_("Add IP address"));
            $xtpl->table_add_category(_("(Please contact administrator for change)"));
            if ($iplist = $vps->iplist())
                foreach ($iplist as $ip) {
                    if ($ip["ip_v"] == 4)
                        $xtpl->table_td(_("IPv4"));
                    else
                        $xtpl->table_td(_("IPv6"));
                    $xtpl->table_td($ip["ip_addr"]);
                    $xtpl->table_tr();
                }
            $xtpl->table_out();
        }

        // DNS Server
        $xtpl->form_create('?page=adminvps&action=nameserver&veid=' . $vps->veid, 'post');
        $xtpl->form_add_select(_("DNS servers address") . ':', 'nameserver', $cluster->
            list_dns_servers($vps->get_location()), $vps->ve["vps_nameserver"], '');
        $xtpl->table_add_category(_("DNS server"));
        $xtpl->table_add_category('&nbsp;');
        $xtpl->form_out(_("Go >>"));

        // Hostname change
        $xtpl->form_create('?page=adminvps&action=hostname&veid=' . $vps->veid, 'post');
        $xtpl->form_add_input(_("Hostname") . ':', 'text', '30', 'hostname', $vps->ve["vps_hostname"],
            _("A-z, a-z"), 30);
        $xtpl->table_add_category(_("Hostname list"));
        $xtpl->table_add_category('&nbsp;');
        $xtpl->form_out(_("Go >>"));

        // Reinstall
        $xtpl->form_create('?page=adminvps&action=reinstall&veid=' . $vps->veid, 'post');
        $xtpl->form_add_checkbox(_("Reinstall distribution") . ':', 'reinstallsure', '1', false,
            $hint = _("Install base system again"));
        $xtpl->form_add_select(_("Distribution") . ':', 'vps_template', list_templates(),
            $vps->ve["vps_template"], '');
        $xtpl->table_add_category(_("Reinstall"));
        $xtpl->table_add_category('&nbsp;');
        $xtpl->form_out(_("Go >>"));

        // VPS Limits
        if ($_SESSION["is_admin"]) {
            $xtpl->form_create('?page=adminvps&action=editlimits&veid=' . $vps->veid, 'post');
            $xtpl->form_add_select(_("RAM") . ':', 'vps_privvmpages', list_limit_privvmpages
                (), $vps->ve["vps_privvmpages"], '');
            $xtpl->form_add_select(_("Disk space") . ':', 'vps_diskspace',
                list_limit_diskspace(), $vps->ve["vps_diskspace"], '');
            $xtpl->table_add_category(_("Change limits"));
            $xtpl->table_add_category('&nbsp;');
            $xtpl->form_out(_("Go >>"));
        }

        // Enable devices/capabilities
        if ($_SESSION["is_admin"]) {
            $xtpl->form_create('?page=adminvps&action=enabledevices&veid=' . $vps->veid,
                'post');
            if (!$vps->is_enabled("tuntap")) {
                $xtpl->form_add_checkbox(_("Enable TUN/TAP") . ':', 'devicesenabled[]', 'tuntap', false);
            } else {
                $xtpl->table_td(_("Enable TUN/TAP"));
                $xtpl->table_td(_("enabled"));
                $xtpl->table_tr();
            }
            if (!$vps->is_enabled("iptables")) {
                $xtpl->form_add_checkbox(_("Enable iptables") . ':', 'devicesenabled[]',
                    'iptables', false);
            } else {
                $xtpl->table_td(_("Enable iptables"));
                $xtpl->table_td(_("enabled"));
                $xtpl->table_tr();
            }
            if (!$vps->is_enabled("fuse")) {
                $xtpl->form_add_checkbox(_("Enable FUSE") . ':', 'devicesenabled[]', 'fuse', false);
            } else {
                $xtpl->table_td(_("Enable FUSE"));
                $xtpl->table_td(_("enabled"));
                $xtpl->table_tr();
            }
            $xtpl->table_add_category(_("Enable devices"));
            $xtpl->table_add_category('&nbsp;');
            $xtpl->form_out(_("Go >>"));
        }

        // Owner change
        if ($_SESSION["is_admin"]) {
            $xtpl->form_create('?page=adminvps&action=chown&veid=' . $vps->veid, 'post');
            $xtpl->form_add_select(_("Owner") . ':', 'm_id', members_list(), $vps->ve["m_id"]);
            $xtpl->table_add_category(_("Change owner"));
            $xtpl->table_add_category('&nbsp;');
            $xtpl->form_out(_("Go >>"));
        }

        //Offline migration
        if ($_SESSION["is_admin"]) {
            $xtpl->form_create('?page=adminvps&action=offlinemigrate&veid=' . $vps->veid,
                'post');
            $xtpl->form_add_select(_("Target server") . ':', 'target_id', $cluster->
                list_servers($vps->ve["vps_server"], $vps->get_location(), true), '');
            $xtpl->table_add_category(_("Offline VPS Migration"));
            $xtpl->table_add_category('&nbsp;');
            $xtpl->form_out(_("Go >>"));
        }
        // Online migration
        if ($_SESSION["is_admin"]) {
            $xtpl->form_create('?page=adminvps&action=onlinemigrate&veid=' . $vps->veid,
                'post');
            $xtpl->form_add_select(_("Target server:") . ':', 'target_id', $cluster->
                list_servers($vps->ve["vps_server"], $vps->get_location()), '');
            $xtpl->table_add_category(_("Online VPS Migration"));
            $xtpl->table_add_category('&nbsp;');
            $xtpl->form_out(_("Go >>"));
        }
    }

    $xtpl->sbar_out('Manage VPS');

} else
    $xtpl->perex(_("Access forbidden"), _("You have to log in to be able to access vpsAdmin's functions"));
?>
