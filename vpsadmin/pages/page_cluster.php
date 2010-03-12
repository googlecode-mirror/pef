<?php
/*
    ./pages/page_cluster.php

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
if ($_SESSION["is_admin"]) {

$xtpl->title(_("Manage Cluster"));
$list_nodes = false;
$list_templates = false;

switch($_REQUEST["action"]) {
    case "restart_node":
	$node = new cluster_node($_REQUEST["id"]);
	$xtpl->perex(_("Are you sure to reboot node").' '.$node->s["server_name"].'?',
	    '<a href="?page=cluster">NO</a> | <a href="?page=cluster&action=restart_node2&id='.$_REQUEST["id"].'">YES</a>');
	break;
    case "restart_node2":
	$node = new cluster_node($_REQUEST["id"]);
	$xtpl->perex(_("WARNING: This will stop ALL VPSes on the node. Really continue?"),
	    '<a href="?page=cluster&action=restart_node3&id='.$_REQUEST["id"].'">YES</a> | <a href="?page=cluster">NO</a>');
	break;
    case "restart_node3":
	if (isset($_REQUEST["id"])) {
	    add_transaction($_SESSION["member"]["m_id"], $_REQUEST["id"], 0, T_RESTART_NODE);
	}
	$list_nodes = true;
	break;
    case "dns":
	$list_dns = true;
	break;
    case "dns_new":
	$xtpl->title2(_("New DNS Server"));
	$xtpl->table_add_category('');
	$xtpl->table_add_category('');
	$xtpl->form_create('?page=cluster&action=dns_new_save', 'post');
	$xtpl->form_add_input(_("IP Address").':', 'text', '30', 'dns_ip', '', '');
	$xtpl->form_add_input(_("Label").':', 'text', '30', 'dns_label', '', _("DNS Label"));
	$xtpl->form_add_checkbox(_("Is this DNS location independent?").':', 'dns_is_universal', '1', false, '');
	$xtpl->form_add_select(_("Location").':', 'dns_location', $cluster->list_locations(), '',  '');
	$xtpl->form_out(_("Save changes"));
	break;
    case "dns_new_save":
	$cluster->set_dns_server(NULL, $_REQUEST["dns_ip"], $_REQUEST["dns_label"], $_REQUEST["dns_is_universal"], $_REQUEST["dns_location"]);
	$xtpl->perex(_("Changes saved"), _("DNS server added."));
	$list_dns = true;
	break;
    case "dns_edit":
	if ($item = $cluster->get_dns_server_by_id($_REQUEST["id"])) {
	    $xtpl->title2(_("Edit DNS Server"));
	    $xtpl->table_add_category('');
	    $xtpl->table_add_category('');
	    $xtpl->form_create('?page=cluster&action=dns_edit_save&id='.$_REQUEST["id"], 'post');
	    $xtpl->form_add_input(_("IP Address").':', 'text', '30', 'dns_ip', $item["dns_ip"], '');
	    $xtpl->form_add_input(_("Label").':', 'text', '30', 'dns_label', $item["dns_label"], _("DNS Label"));
	    $xtpl->form_add_checkbox(_("Is this DNS location independent?").':', 'dns_is_universal', '1', $item["dns_is_universal"], '');
	    $xtpl->form_add_select(_("Location").':', 'dns_location', $cluster->list_locations(), $item["dns_location"],  '');
	    $xtpl->form_out(_("Save changes"));
	} else {
	    $list_dns = true;
	}
	break;
    case "dns_edit_save":
	if ($item = $cluster->get_dns_server_by_id($_REQUEST["id"])) {
	    $cluster->set_dns_server($_REQUEST["id"], $_REQUEST["dns_ip"], $_REQUEST["dns_label"], $_REQUEST["dns_is_universal"], $_REQUEST["dns_location"]);
	    $xtpl->perex(_("Changes saved"), _("DNS server saved."));
	    $list_dns = true;
	} else {
	    $list_dns = true;
	}
	break;
    case "dns_delete":
	if ($item = $cluster->get_dns_by_id($_REQUEST["id"])) {
	    $cluster->delete_dns_server($_REQUEST["id"]);
	    $xtpl->perex(_("Item deleted"), _("DNS Server deleted."));
	}
	$list_locations = true;
	break;
    case "locations":
	$list_locations = true;
	break;
    case "location_delete":
	if ($item = $cluster->get_location_by_id($_REQUEST["id"])) {
	    if ($cluster->get_server_count_in_location($item["location_id"]) <= 0) {
		$cluster->delete_location($_REQUEST["id"]);
		$xtpl->perex(_("Item deleted"), _("Location deleted."));
	    }
	}
	$list_locations = true;
	break;
    case "location_new":
	$xtpl->title2(_("New cluster location"));
	$xtpl->table_add_category('');
	$xtpl->table_add_category('');
	$xtpl->form_create('?page=cluster&action=location_new_save', 'post');
	$xtpl->form_add_input(_("Label").':', 'text', '30', 'location_label', '', _("Location name"));
	$xtpl->form_add_checkbox(_("Has this location IPv6 support?").':', 'has_ipv6', '1', false, '');
	$xtpl->form_out(_("Save changes"));
	break;
    case "location_new_save":
	$cluster->set_location(NULL, $_REQUEST["location_label"], $_REQUEST["has_ipv6"]);
	$xtpl->perex(_("Changes saved"), _("Location added."));
	$list_locations = true;
	break;
    case "location_edit":
	if ($item = $cluster->get_location_by_id($_REQUEST["id"])) {
	    $xtpl->title2(_("Edit location label"));
	    $xtpl->table_add_category('');
	    $xtpl->table_add_category('');
	    $xtpl->form_create('?page=cluster&action=location_edit_save&id='.$_REQUEST["id"], 'post');
	    $xtpl->form_add_input(_("Label").':', 'text', '30', 'location_label', $item["location_label"], _("Location name"));
	    $xtpl->form_add_checkbox(_("Has this location IPv6 support?").':', 'has_ipv6', '1', $item["location_has_ipv6"], '');
	    $xtpl->form_out(_("Save changes"));
	} else {
	    $list_ramlimits = true;
	}
	break;
    case "location_edit_save":
	if ($item = $cluster->get_location_by_id($_REQUEST["id"])) {
	    $cluster->set_location($_REQUEST["id"], $_REQUEST["location_label"], $_REQUEST["has_ipv6"]);
	    $xtpl->perex(_("Changes saved"), _("Location label saved."));
	    $list_locations = true;
	} else {
	    $list_locations = true;
	}
	break;
    case "ipv4addr":
	$Cluster_ipv4->table_used_out(_("Used IP addresses"), true);
	$Cluster_ipv4->table_unused_out(_("Unused IP addresses"), true);
	$xtpl->sbar_add(_("Back"), '?page=cluster');
	$xtpl->sbar_add(_("Add IPv4"), '?page=cluster&action=ipaddr_add&v=4');
	break;
    case "ipv6addr":
	$Cluster_ipv6->table_used_out(_("Used IP addresses"), true);
	$Cluster_ipv6->table_unused_out(_("Unused IP addresses"), true);
	$xtpl->sbar_add(_("Back"), '?page=cluster');
	$xtpl->sbar_add(_("Add IPv6"), '?page=cluster&action=ipaddr_add&v=6');
	break;
    case "ipaddr_delete":
	if (!isset($_REQUEST['vps_id']))
	    $_REQUEST['vps_id'] = -1;
	if ($_REQUEST['v']==4)
	    $res = $Cluster_ipv4->delete($_REQUEST['ip_id']*1, $_REQUEST['vps_id']*1);
	elseif ($_REQUEST['v']==6)
	    $res = $Cluster_ipv6->delete($_REQUEST['ip_id']*1, $_REQUEST['vps_id']*1);
	if ($res==null)
	    $xtpl->perex(_("Operation not succesful"), _("An error has occured, while you were trying to delete IP"));
	else
	    $xtpl->perex(_("Operation succesful"), _("IP address has been successfully deleted."));
	break;
    case "ipaddr_remove":
	if (isset($_REQUEST['vps_id']) && isset($_REQUEST['ip_id'])) {
	    if ($_REQUEST['v']==4)
		$Cluster_ipv4->remove_from_vps($_REQUEST['ip_id']*1, $_REQUEST['vps_id']*1);
	    elseif ($_REQUEST['v']==6)
	    $Cluster_ipv6->remove_from_vps($_REQUEST['ip_id']*1, $_REQUEST['vps_id']*1);
	}
	break;
    case "ipaddr_add":
	if ($_REQUEST['v']==4)
	    $Cluster_ipv4->table_add_1(15);
	elseif ($_REQUEST['v']==6)
	    $Cluster_ipv6->table_add_1(39);
	break;
    case "ipaddr_add2":
	if (isset($_REQUEST['m_ip']) && isset($_REQUEST["m_location"])) {
	    if ($_REQUEST['v']==4)
		$Cluster_ipv4->table_add_2($_REQUEST['m_ip'], $_REQUEST['m_location']);
	    elseif ($_REQUEST['v']==6)
		$Cluster_ipv6->table_add_2($_REQUEST['m_ip'], $_REQUEST['m_location']);
	if ($_REQUEST['v']==4)
	    $Cluster_ipv4->table_add_1(15, $_REQUEST['m_ip'], $_REQUEST['m_location']);
	elseif ($_REQUEST['v']==6)
	    $Cluster_ipv6->table_add_1(39, $_REQUEST['m_ip'], $_REQUEST['m_location']);
	}
	break;
    case "templates":
	$list_templates = true;
	break;
    case "templates_edit":
	if ($template = $cluster->get_template_by_id($_REQUEST["id"])) {
	    $xtpl->title2(_("Edit template"));
	    $xtpl->table_add_category('');
	    $xtpl->table_add_category('');
	    $xtpl->form_create('?page=cluster&action=templates_edit_save&id='.$_REQUEST["id"], 'post');
	    $xtpl->form_add_input(_("Filename").':', 'text', '40', 'templ_name', $template["templ_name"], _("filename without .tar.gz"));
	    $xtpl->form_add_input(_("Label").':', 'text', '30', 'templ_label', $template["templ_label"], _("User friendly label"));
	    $xtpl->form_add_textarea(_("Info").':', 28, 4, 'templ_info', $template["templ_info"], _("Note for administrators"));
	    $xtpl->form_out(_("Save changes"));
	} else {
	    $list_templates = true;
	}
	break;
    case "templates_edit_save":
	if ($template = $cluster->get_template_by_id($_REQUEST["id"])) {
	    if (ereg('^[a-zA-Z0-9_\.\-]{1,63}$',$_REQUEST["templ_name"])) {
		$cluster->set_template($_REQUEST["id"], $_REQUEST["templ_name"], $_REQUEST["templ_label"], $_REQUEST["templ_info"]);
		$xtpl->perex(_("Changes saved"), _("Changes you've made to template were saved."));
		$list_templates = true;
	    } else $list_templates = true;
	} else {
	    $list_templates = true;
	}
	break;
    case "template_register":
	$xtpl->title2(_("Register new template"));
	$xtpl->table_add_category('');
	$xtpl->table_add_category('');
	$xtpl->form_create('?page=cluster&action=template_register_save', 'post');
	$xtpl->form_add_input(_("Filename").':', 'text', '40', 'templ_name', '', _("filename without .tar.gz"));
	$xtpl->form_add_input(_("Label").':', 'text', '30', 'templ_label', '', _("User friendly label"));
	$xtpl->form_add_textarea(_("Info").':', 28, 4, 'templ_info', '', _("Note for administrators"));
	$xtpl->form_out(_("Save changes"));
	$xtpl->helpbox(_("Help"), _("This procedure only <b>registers template</b> into the system database.
				     You need copy the template to proper path onto one of servers
				     and then proceed \"Copy template over nodes\" function.
				    "));
	break;
    case "template_register_save":
	if (ereg('^[a-zA-Z0-9_\.\-]{1,63}$',$_REQUEST["templ_name"])) {
	    $cluster->set_template(NULL, $_REQUEST["templ_name"], $_REQUEST["templ_label"], $_REQUEST["templ_info"]);
	    $xtpl->perex(_("Changes saved"), _("Template successfully registered."));
	    $list_templates = true;
	} else {
	    $list_templates = true;
	}
	break;
    case "templates_copy_over_nodes":
	if ($template = $cluster->get_template_by_id($_REQUEST["id"])) {
	    $xtpl->title2(_("Copy template over cluster nodes"));
	    $xtpl->table_add_category('');
	    $xtpl->table_add_category('');
	    $xtpl->form_create('?page=cluster&action=templates_copy_over_nodes2&id='.$_REQUEST["id"], 'post');
	    $xtpl->form_add_select(_("Source node").':', 'source_node', $cluster->list_servers(), '', '');
	    $xtpl->form_out(_("Copy template"));
	    $xtpl->helpbox(_("Help"), _("This procedure takes template file from source server and copies it using 'scp'
					over all nodes of cluster. This may take large amount of I/O resources,
					therefore it is recommended to use it only when cluster is not under full load.
					"));
	} else {
	    $list_templates = true;
	}
	break;
    case "templates_copy_over_nodes2":
	if ($template = $cluster->get_template_by_id($_REQUEST["id"])) {
	    if ($source_node = new cluster_node($_GET["source_node"])) {
		$cluster->copy_template_to_all_nodes($_REQUEST["id"], $_REQUEST["source_node"]);
		$xtpl->perex(_("Template copy added to transactions log"), $template["templ_name"]);
		$list_templates = true;
	    }
	} else {
	    $list_templates = true;
	}
	break;
    case "templates_delete":
	if ($template = $cluster->get_template_by_id($_REQUEST["id"])) {
	    $xtpl->perex(_("Are you sure to delete template").' '.$template["templ_name"].'?',
			'<a href="?page=cluster&action=templates">'._("No").'</a> | '.
			'<a href="?page=cluster&action=templates_delete2&id='.$template["templ_id"].'">'._("Yes").'</a>');
	}
	break;
    case "templates_delete2":
	if ($template = $cluster->get_template_by_id($_REQUEST["id"])) {
	    $usage = $cluster->get_template_usage($template["templ_id"]);
	    if ($usage <= 0) {
		$nodes_instances = $cluster->list_servers_class();
		foreach ($nodes_instances as $node) {
		    $params = array();
		    $params["templ_id"] = $_REQUEST["id"];
		    add_transaction($_SESSION["member"]["m_id"], $node->s["server_id"], 0, T_CLUSTER_TEMPLATE_DELETE, $params);
		}
		$cluster->delete_template($template["templ_id"]);
	    } else {
		$list_templates = true;
	    }
	} else {
	    $list_templates = true;
	}
	break;
    case "hddlimits":
	$list_hddlimits = true;
	break;
    case "hddlimit_new":
	$xtpl->title2(_("New HDD limits item"));
	$xtpl->table_add_category('');
	$xtpl->table_add_category('');
	$xtpl->form_create('?page=cluster&action=hddlimit_new_save', 'post');
	$xtpl->form_add_input(_("Label").':', 'text', '30', 'd_label', '', _("User friendly label"));
	$xtpl->form_add_input(_("Value [GB]").':', 'text', '10', 'd_gb', '', '');
	$xtpl->form_out(_("Save changes"));
	break;
    case "hddlimit_new_save":
	$cluster->set_hddlimit(NULL, $_REQUEST["d_label"], $_REQUEST["d_gb"]);
	$xtpl->perex(_("Changes saved"), _("HDD Limit item successfully saved."));
	$list_hddlimits = true;
	break;
    case "hddlimit_edit":
	if ($item = $cluster->get_hddlimit_by_id($_REQUEST["id"])) {
	    $xtpl->title2(_("Edit HDD limits item"));
	    $xtpl->table_add_category('');
	    $xtpl->table_add_category('');
	    $xtpl->form_create('?page=cluster&action=hddlimit_edit_save&id='.$_REQUEST["id"], 'post');
	    $xtpl->form_add_input(_("Label").':', 'text', '30', 'd_label', $item["d_label"], _("User friendly label"));
	    $xtpl->form_add_input(_("Value [GB]").':', 'text', '10', 'd_gb', $item["d_gb"], '');
	    $xtpl->form_out(_("Save changes"));
	} else {
	    $list_hddlimits = true;
	}
	break;
    case "hddlimit_edit_save":
	if ($item = $cluster->get_hddlimit_by_id($_REQUEST["id"])) {
	    $cluster->set_hddlimit($_REQUEST["id"], $_REQUEST["d_label"], $_REQUEST["d_gb"]);
	    $xtpl->perex(_("Changes saved"), _("HDD Limit item successfully saved."));
	    $list_hddlimits = true;
	} else {
	    $list_hddlimits = true;
	}
	break;
    case "hddlimit_delete":
	if ($item = $cluster->get_hddlimit_by_id($_REQUEST["id"])) {
	    if ($cluster->get_hddlimit_usage($_REQUEST["id"]) <= 0) {
		$cluster->delete_hddlimit($_REQUEST["id"]);
		$xtpl->perex(_("Item deleted"), _("HDD Limit deleted."));
	    }
	}
	$list_hddlimits = true;
	break;
    case "ramlimits":
	$list_ramlimits = true;
	break;
    case "ramlimit_new":
	$xtpl->title2(_("New RAM limits item"));
	$xtpl->table_add_category('');
	$xtpl->table_add_category('');
	$xtpl->form_create('?page=cluster&action=ramlimit_new_save', 'post');
	$xtpl->form_add_input(_("Label").':', 'text', '30', 'vm_label', '', _("User friendly label"));
	$xtpl->form_add_input(_("Soft limit [MB]").':', 'text', '10', 'vm_lim_soft', '', '');
	$xtpl->form_add_input(_("Hard limit [MB]").':', 'text', '10', 'vm_lim_hard', '', '');
	$xtpl->form_out(_("Save changes"));
	break;
    case "ramlimit_new_save":
	$cluster->set_ramlimit(NULL, $_REQUEST["vm_label"], $_REQUEST["vm_lim_soft"], $_REQUEST["vm_lim_hard"]);
	$xtpl->perex(_("Changes saved"), _("RAM Limit item successfully saved."));
	$list_ramlimits = true;
	break;
    case "ramlimit_edit":
	if ($item = $cluster->get_ramlimit_by_id($_REQUEST["id"])) {
	    $xtpl->title2(_("Edit RAM limits item"));
	    $xtpl->table_add_category('');
	    $xtpl->table_add_category('');
	    $xtpl->form_create('?page=cluster&action=ramlimit_edit_save&id='.$_REQUEST["id"], 'post');
	    $xtpl->form_add_input(_("Label").':', 'text', '30', 'vm_label', $item["vm_label"], _("User friendly label"));
	    $xtpl->form_add_input(_("Soft limit [MB]").':', 'text', '10', 'vm_lim_soft', $item["vm_lim_soft"], '');
	    $xtpl->form_add_input(_("Hard limit [MB]").':', 'text', '10', 'vm_lim_hard', $item["vm_lim_hard"], '');
	    $xtpl->form_out(_("Save changes"));
	} else {
	    $list_ramlimits = true;
	}
	break;
    case "ramlimit_edit_save":
	if ($item = $cluster->get_ramlimit_by_id($_REQUEST["id"])) {
	    $cluster->set_ramlimit($_REQUEST["id"], $_REQUEST["vm_label"], $_REQUEST["vm_lim_soft"], $_REQUEST["vm_lim_hard"]);
	    $xtpl->perex(_("Changes saved"), _("RAM Limit item successfully saved."));
	    $list_ramlimits = true;
	} else {
	    $list_ramlimits = true;
	}
	break;
    case "ramlimit_delete":
	if ($item = $cluster->get_ramlimit_by_id($_REQUEST["id"])) {
	    if ($cluster->get_ramlimit_usage($_REQUEST["id"]) <= 0) {
		$cluster->delete_ramlimit($_REQUEST["id"]);
		$xtpl->perex(_("Item deleted"), _("RAM Limit deleted."));
	    }
	}
	$list_ramlimits = true;
	break;
    case "newnode":
	$xtpl->title2(_("Register new server into cluster"));
	$xtpl->table_add_category('');
	$xtpl->table_add_category('');
	$xtpl->form_create('?page=cluster&action=newnode_save', 'post');
	$xtpl->form_add_input(_("ID").':', 'text', '8', 'server_id', '', '');
	$xtpl->form_add_input(_("Name").':', 'text', '30', 'server_name', '', '');
	$xtpl->form_add_select(_("Location").':', 'server_location', $cluster->list_locations(), '',  '');
	$xtpl->form_add_input(_("Server IPv4 address").':', 'text', '30', 'server_ip4', '', '');
	$xtpl->form_add_textarea(_("Availability icon (if you wish)").':', 28, 4, 'server_availstat', '', _("Paste HTML link here"));
	$xtpl->form_add_input(_("Max VPS count").':', 'text', '8', 'server_maxvps', '', '');
	$xtpl->form_add_input(_("OpenVZ Path").':', 'text', '10', 'server_path_vz', '/var/lib/vz', '');
	$xtpl->form_out(_("Save changes"));
	break;
    case "newnode_save":
	if (isset($_REQUEST["server_id"]) &&
	    isset($_REQUEST["server_name"]) &&
	    isset($_REQUEST["server_ip4"]) &&
	    isset($_REQUEST["server_location"]) &&
	    isset($_REQUEST["server_maxvps"]) &&
	    isset($_REQUEST["server_path_vz"])
	) {
	    $sql = 'INSERT INTO servers
			    SET server_id = "'.$db->check($_REQUEST["server_id"]).'",
				server_name = "'.$db->check($_REQUEST["server_name"]).'",
				server_location = "'.$db->check($_REQUEST["server_location"]).'",
				server_availstat = "'.$db->check($_REQUEST["server_availstat"]).'",
				server_maxvps = "'.$db->check($_REQUEST["server_maxvps"]).'",
				server_ip4 = "'.$db->check($_REQUEST["server_ip4"]).'",
				server_path_vz = "'.$db->check($_REQUEST["server_path_vz"]).'"';
	    $db->query($sql);
	    $list_nodes = true;
	}
    case "fields":
	$xtpl->title2(_("Edit textfields"));
	$xtpl->table_add_category('');
	$xtpl->table_add_category('');
	$xtpl->form_create('?page=cluster&action=fields_save', 'post');
	$xtpl->form_add_textarea(_("Text on sidebar").':', 50, 8, 'adminbox_content', $cluster_cfg->get("adminbox_content"), _("HTML"));
	$xtpl->form_add_textarea(_("Page Status infobox title").':', 50, 8, 'page_index_info_box_title', $cluster_cfg->get("page_index_info_box_title"), _("HTML"));
	$xtpl->form_add_textarea(_("Page Status infobox content").':', 50, 8, 'page_index_info_box_content', $cluster_cfg->get("page_index_info_box_content"), _("HTML"));
	$xtpl->form_out(_("Save changes"));
	break;
    case "fields_save":
	$cluster_cfg->set('adminbox_content', $_REQUEST["adminbox_content"]);
	$cluster_cfg->set('page_index_info_box_title', $_REQUEST["page_index_info_box_title"]);
	$cluster_cfg->set('page_index_info_box_content', $_REQUEST["page_index_info_box_content"]);
	$list_nodes = true;
	break;
    default:
	$list_nodes = true;
}

if ($list_nodes) {
    $xtpl->sbar_add(_("Register new node"), '?page=cluster&action=newnode');
    $xtpl->sbar_add(_("Manage VPS templates"), '?page=cluster&action=templates');
    $xtpl->sbar_add(_("Manage RAM limits"), '?page=cluster&action=ramlimits');
    $xtpl->sbar_add(_("Manage HDD limits"), '?page=cluster&action=hddlimits');
    $xtpl->sbar_add(_("Manage IPv4 address list"), '?page=cluster&action=ipv4addr');
    $xtpl->sbar_add(_("Manage IPv6 address list"), '?page=cluster&action=ipv6addr');
    $xtpl->sbar_add(_("Manage DNS servers"), '?page=cluster&action=dns');
    $xtpl->sbar_add(_("Manage locations"), '?page=cluster&action=locations');
    $xtpl->sbar_add(_("Edit vpsAdmin textfields"), '?page=cluster&action=fields');
    $sql = 'SELECT * FROM servers';
    $list_result = $db->query($sql);
    while ($srv = $db->fetch_array($list_result)) {
	    $xtpl->table_add_category($srv["server_name"]);
	    $node = new cluster_node($srv["server_id"]);
	    $xtpl->table_add_category("");
	    $xtpl->table_add_category("");
	    $xtpl->table_add_category('<a href="?page=cluster&action=restart_node&id='.$srv["server_id"].'"><img src="template/icons/vps_restart.png" title="'._("Reboot node").'"/></a>');
	    $sql = 'SELECT * FROM servers_status WHERE server_id ="'.$srv["server_id"].'" ORDER BY id DESC LIMIT 1';
	    if ($result = $db->query($sql))
		$status = $db->fetch_array($result);
	    $xtpl->table_td(_("CPU load:"));
	    $xtpl->table_td($status["cpu_load"], false, true, 3);
	    $xtpl->table_tr();
	    $xtpl->table_td(_("Free RAM:"));
	    $xtpl->table_td($status["ram_free_mb"].' MB', false, true, 3);
	    $xtpl->table_tr();
	    $xtpl->table_td(_("Free OpenVZ diskspace:"));
	    $xtpl->table_td($status["disk_vz_free_gb"].' GB', false, true, 3);
	    $xtpl->table_tr();
	    $xtpl->table_td(_("Server location:"));
	    $xtpl->table_td($node->get_location_label(), false, false, 3);
	    $xtpl->table_tr();
	    if ($srv["server_availstat"]) {
		$xtpl->table_td(_("Availability:"));
		$xtpl->table_td($srv["server_availstat"], false, true, 3);
		$xtpl->table_tr();
		}
	    $sql = 'SELECT COUNT(*) AS count FROM vps WHERE vps_server='.$db->check($srv["server_id"]);
	    $vps_count = 0;
	    if ($result = $db->query($sql))
		$vps_count = $db->fetch_array($result);
	    $xtpl->table_td(_("VPS count:"));
	    $xtpl->table_td($vps_count["count"]);
	    $xtpl->table_td(_("Free slots:"));
	    $vps_free = ((int)$srv["server_maxvps"]-(int)$vps_count["count"]);
	    $xtpl->table_td($vps_free);

	    if ($vps_free < 0) $xtpl->table_tr(false, "error");
	    else $xtpl->table_tr();
		$icons = "";
		if ($cluster_cfg->get("lock_cron_".$srv["server_id"]))	{
			$icons .= '<img title="'._("The server is currently processing").'" src="template/icons/warning.png"/>';
			$status_text = _("The server is currently processing");
		    }
		elseif ((time()-$status["timestamp"]) > 360) {
			$icons .= '<img title="'._("The server is not responding").'" src="template/icons/error.png"/>';
			$status_text = _("The server is not responding");
		    }
		else {
			$icons .= '<img title="'._("The server is online").'" src="template/icons/server_online.png"/>';
			$status_text = _("The server is online");
		    }
		$xtpl->table_td(_("Status"));
		$xtpl->table_td($status_text, false, false, 2);
		$xtpl->table_td($icons);
		$xtpl->table_tr();
	    $xtpl->table_out();
    }
}
if ($list_templates) {
    $xtpl->title2(_("Templates list"));
    $xtpl->table_add_category(_("ID"));
    $xtpl->table_add_category(_("Filename"));
    $xtpl->table_add_category(_("Label"));
    $xtpl->table_add_category(_("Uses"));
    $xtpl->table_add_category('');
    $xtpl->table_add_category('');
    $xtpl->table_add_category('');
    $templates = $cluster->get_templates();
    foreach($templates as $template) {
	$usage = 0;
	$usage = $cluster->get_template_usage($template["templ_id"]);
	$xtpl->table_td($template["templ_id"], false, true);
	$xtpl->table_td($template["templ_name"].'.tar.gz');
	$xtpl->table_td($template["templ_label"]);
	$xtpl->table_td($usage);
	$xtpl->table_td('<a href="?page=cluster&action=templates_edit&id='.$template["templ_id"].'"><img src="template/icons/edit.png" title="'._("Edit").'"></a>');
	$xtpl->table_td('<a href="?page=cluster&action=templates_copy_over_nodes&id='.$template["templ_id"].'"><img src="template/icons/copy_template.png" title="'._("Copy over nodes").'"></a>');
	if ($usage > 0)
	    $xtpl->table_td('<img src="template/icons/delete_grey.png" title="'._("Delete - N/A, template is in use").'">');
	else
	    $xtpl->table_td('<a href="?page=cluster&action=templates_delete&id='.$template["templ_id"].'"><img src="template/icons/delete.png" title="'._("Delete").'"></a>');
	if ($template["templ_info"]) $xtpl->table_td('<img src="template/icons/info.png" title="'._("Info").'"');
	$xtpl->table_tr();
    }
    $xtpl->table_out();
    $xtpl->sbar_add(_("Register new template"), '?page=cluster&action=template_register');
    $xtpl->helpbox(_("Help"), _("This is simple cluster template management.
				To add new template, save it to one node, then click 'Register new template' and finally copy it over all nodes.
				"));
}
if ($list_hddlimits) {
    $xtpl->title2(_("HDD limits list"));
    $xtpl->table_add_category(_("ID"));
    $xtpl->table_add_category(_("Disk space [GB]"));
    $xtpl->table_add_category(_("Label"));
    $xtpl->table_add_category(_("Uses"));
    $xtpl->table_add_category('');
    $xtpl->table_add_category('');
    $list = $cluster->get_hddlimits();
    if ($list)
    foreach($list as $item) {
	$usage = 0;
	$usage = $cluster->get_hddlimit_usage($item["d_id"], false, true);
	$xtpl->table_td($item["d_id"], false, true);
	$xtpl->table_td($item["d_gb"], false, true);
	$xtpl->table_td($item["d_label"], false, true);
	$xtpl->table_td($usage);
	$xtpl->table_td('<a href="?page=cluster&action=hddlimit_edit&id='.$item["d_id"].'"><img src="template/icons/edit.png" title="'._("Edit").'"></a>');
	if ($usage > 0)
	    $xtpl->table_td('<img src="template/icons/delete_grey.png" title="'._("Delete - N/A, item is in use").'">');
	else
	    $xtpl->table_td('<a href="?page=cluster&action=hddlimit_delete&id='.$item["d_id"].'"><img src="template/icons/delete.png" title="'._("Delete").'"></a>');
	$xtpl->table_tr();
    }
    $xtpl->table_out();
    $xtpl->sbar_add(_("New HDD limit"), '?page=cluster&action=hddlimit_new');
}
if ($list_ramlimits) {
    $xtpl->title2(_("Virtual memory limits list"));
    $xtpl->table_add_category(_("ID"));
    $xtpl->table_add_category(_("Value soft [MB]"));
    $xtpl->table_add_category(_("Value hard [MB]"));
    $xtpl->table_add_category(_("Label"));
    $xtpl->table_add_category(_("Uses"));
    $xtpl->table_add_category('');
    $xtpl->table_add_category('');
    $list = $cluster->get_ramlimits();
    if ($list)
    foreach($list as $item) {
	$usage = 0;
	$usage = $cluster->get_ramlimit_usage($item["vm_id"]);
	$xtpl->table_td($item["vm_id"], false, true);
	$xtpl->table_td($item["vm_lim_soft"], false, true);
	$xtpl->table_td($item["vm_lim_hard"], false, true);
	$xtpl->table_td($item["vm_label"]);
	$xtpl->table_td($usage, false, true);
	$xtpl->table_td('<a href="?page=cluster&action=ramlimit_edit&id='.$item["vm_id"].'"><img src="template/icons/edit.png" title="'._("Edit").'"></a>');
	if ($usage > 0)
	    $xtpl->table_td('<img src="template/icons/delete_grey.png" title="'._("Delete - N/A, item is in use").'">');
	else
	    $xtpl->table_td('<a href="?page=cluster&action=ramlimit_delete&id='.$item["vm_id"].'"><img src="template/icons/delete.png" title="'._("Delete").'"></a>');
	$xtpl->table_tr();
    }
    $xtpl->table_out();
    $xtpl->sbar_add(_("New RAM limit"), '?page=cluster&action=ramlimit_new');
}
if ($list_locations) {
    $xtpl->title2(_("Cluster locations list"));
    $xtpl->table_add_category(_("ID"));
    $xtpl->table_add_category(_("Location label"));
    $xtpl->table_add_category(_("Servers"));
    $xtpl->table_add_category(_("IPv6"));
    $xtpl->table_add_category('');
    $xtpl->table_add_category('');
    $list = $cluster->get_locations();
    if ($list)
    foreach($list as $item) {
	$servers = 0;
	$servers = $cluster->get_server_count_in_location($item["location_id"]);
	$xtpl->table_td($item["location_id"]);
	$xtpl->table_td($item["location_label"]);
	$xtpl->table_td($servers, false, true);
	if ($item["location_has_ipv6"])
	    $xtpl->table_td('<img src="template/icons/transact_ok.png" />');
	else
	    $xtpl->table_td('<img src="template/icons/transact_fail.png" />');
	$xtpl->table_td('<a href="?page=cluster&action=location_edit&id='.$item["location_id"].'"><img src="template/icons/edit.png" title="'._("Edit").'"></a>');
	if ($servers > 0)
	    $xtpl->table_td('<img src="template/icons/delete_grey.png" title="'._("Delete - N/A, item is in use").'">');
	else
	    $xtpl->table_td('<a href="?page=cluster&action=location_delete&id='.$item["location_id"].'"><img src="template/icons/delete.png" title="'._("Delete").'"></a>');
	$xtpl->table_tr();
    }
    $xtpl->table_out();
    $xtpl->sbar_add(_("New location"), '?page=cluster&action=location_new');
}
if ($list_dns) {
    $xtpl->title2(_("DNS Servers list"));
    $xtpl->table_add_category(_("ID"));
    $xtpl->table_add_category(_("IP"));
    $xtpl->table_add_category(_("Label"));
    $xtpl->table_add_category(_("All locations"));
    $xtpl->table_add_category(_("Location"));
    $xtpl->table_add_category('');
    $xtpl->table_add_category('');
    $list = $cluster->get_dns_servers();
    if ($list)
    foreach($list as $item) {
	$xtpl->table_td($item["dns_id"]);
	$xtpl->table_td($item["dns_ip"]);
	$xtpl->table_td($item["dns_label"]);
	$location = $cluster->get_location_by_id($item["dns_location"]);
	if ($item["dns_is_universal"]) {
	    $xtpl->table_td('<img src="template/icons/transact_ok.png" />');
	    $xtpl->table_td('---');
	}
	else {
	    $xtpl->table_td('<img src="template/icons/transact_fail.png" />');
	    $xtpl->table_td($location["location_label"]);
	}
	$xtpl->table_td('<a href="?page=cluster&action=dns_edit&id='.$item["dns_id"].'"><img src="template/icons/edit.png" title="'._("Edit").'"></a>');
	$xtpl->table_td('<a href="?page=cluster&action=dns_delete&id='.$item["dns_id"].'"><img src="template/icons/delete.png" title="'._("Delete").'"></a>');
	$xtpl->table_tr();
    }
    $xtpl->table_out();
    $xtpl->sbar_add(_("New DNS Server"), '?page=cluster&action=dns_new');
}

$xtpl->sbar_out(_("Manage Cluster"));

} else $xtpl->perex(_("Access forbidden"), _("You have to log in to be able to access vpsAdmin's functions"));
?>
