<?php
/*
./lib/transact.lib.php

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
define('T_RESTART_NODE', 3);
define('T_START_VE', 1001);
define('T_STOP_VE', 1002);
define('T_RESTART_VE', 1003);
define('T_EXEC_OTHER', 2001);
define('T_EXEC_PASSWD', 2002);
define('T_EXEC_LIMITS', 2003);
define('T_EXEC_HOSTNAME', 2004);
define('T_EXEC_DNS', 2005);
define('T_EXEC_IPADD', 2006);
define('T_EXEC_IPDEL', 2007);
define('T_CREATE_VE', 3001);
define('T_DESTROY_VE', 3002);
define('T_REINSTALL_VE', 3003);
define('T_MIGRATE_OFFLINE', 4001);
define('T_MIGRATE_ONLINE', 4002);
define('T_SNAPSHOT', 5001);
define('T_MOVE_SNAPSHOT', 5002);
define('T_FIREWALL_RELOAD', 6001);
define('T_FIREWALL_FLUSH', 6002);
define('T_CLUSTER_STORAGE_CFG_RELOAD', 7001);
define('T_CLUSTER_STORAGE_STARTUP', 7002);
define('T_CLUSTER_STORAGE_SHUTDOWN', 7003);
define('T_CLUSTER_STORAGE_SOFTWARE_INSTALL', 7004);
define('T_CLUSTER_TEMPLATE_COPY', 7101);
define('T_CLUSTER_TEMPLATE_DELETE', 7102);
define('T_CLUSTER_IP_REGISTER', 7201);
define('T_ENABLE_DEVICES', 8001);
define('T_ENABLE_TUNTAP', 8002);
define('T_ENABLE_IPTABLES', 8003);
define('T_ENABLE_FUSE', 8004);

function add_transaction_clusterwide($m_id, $vps_id, $t_type, $t_param = 'none')
{
    global $db, $cluster;
    $sql = "INSERT INTO transaction_groups
		    SET is_clusterwide=1";
    $db->query($sql);
    $group_id = $db->insert_id();
    $servers = $cluster->list_servers();
    foreach ($servers as $id => $name)
        add_transaction($m_id, $id, $vps_id, $t_type, $t_param, $group_id);
}
function add_transaction_locationwide($m_id, $vps_id, $t_type, $t_param = 'none',
    $location_id)
{
    global $db, $cluster;
    $sql = "INSERT INTO transaction_groups
		    SET is_locationwide=1,
			location_id=" . $db->check($location_id);
    $db->query($sql);
    $group_id = $db->insert_id();
    $servers = $cluster->list_servers_by_location($location_id);
    foreach ($servers as $id => $name)
        add_transaction($m_id, $id, $vps_id, $t_type, $t_param, $group_id);
}
function add_transaction($m_id, $server_id, $vps_id, $t_type, $t_param = 'none',
    $transact_group = null)
{
    global $db;
    $sql_check = 'SELECT COUNT(*) AS count FROM transactions
		WHERE
			t_time > "' . (time() - 5) . '"
		AND	t_m_id = "' . $db->check($m_id) . '"
		AND	t_server = "' . $db->check($server_id) . '"
		AND	t_vps = "' . $db->check($vps_id) . '"
		AND	t_type = "' . $db->check($t_type) . '"
		AND	t_success = 0
		AND	t_done = 0
		AND	t_param = "' . $db->check(serialize($t_param)) . '"';
    $result_check = $db->query($sql_check);
    $row = $db->fetch_array($result_check);
    if ($row['count'] <= 0) {
        $sql = 'INSERT INTO transactions
			SET t_time = "' . $db->check(time()) . '",
			    t_m_id = "' . $db->check($m_id) . '",
			    t_server = "' . $db->check($server_id) . '",
			    t_vps = "' . $db->check($vps_id) . '",
			    t_type = "' . $db->check($t_type) . '",
			    t_success = 0,
			    t_done = 0,
			    t_param = "' . $db->check(serialize($t_param)) . '"';
        if ($transact_group)
            $sql .= ', t_group="' . $transact_group . '"';
        $result = $db->query($sql);
    }
}

function del_transaction($t_id)
{
    global $db;
    $sql = 'DELETE FROM transactions WHERE t_id = ' . $db->check($t_id);
    $result = $db->query($sql);
}

function do_transaction_by_id($t_id)
{
    global $db;
    $sql = 'SELECT * FROM transactions WHERE t_done = 0 AND t_id = ' . $db->check($t_id);
    if ($result = $db->query($sql))
        if ($t = $db->fetch_array($result))
            return do_transaction($t);
        else
            return false;
    else
        return false;
}

function exec_wrapper($command, &$output, &$retval)
{
    exec($command, $output, $retval);
    //echo $command; $retval=0;
}

function do_all_transactions_by_server($server_id, $force = false)
{
    global $db;
    if ($force)
        $sql = 'SELECT * FROM transactions WHERE t_done = 1 AND t_success = 0 AND t_server = ' .
            $db->check($server_id) . ' ORDER BY t_id ASC';
    else
        $sql = 'SELECT * FROM transactions WHERE t_done = 0 AND t_server = ' . $db->
            check($server_id) . ' ORDER BY t_id ASC';
    if ($result = $db->query($sql))
        while ($t = $db->fetch_array($result))
            do_transaction($t);
}

function list_transactions()
{
    global $xtpl;
    global $db;
    if ($_SESSION["is_admin"])
        $sql = 'SELECT * FROM transactions
		LEFT JOIN members
		ON transactions.t_m_id = members.m_id
		LEFT JOIN servers
		ON transactions.t_server = servers.server_id
		ORDER BY transactions.t_id DESC LIMIT 10';
    else
        $sql = 'SELECT * FROM transactions
		LEFT JOIN members
		ON transactions.t_m_id = members.m_id
		LEFT JOIN servers
		ON transactions.t_server = servers.server_id
		WHERE members.m_id = "' . $db->check($_SESSION["member"]["m_id"]) . '"
		ORDER BY transactions.t_id DESC LIMIT 10';
    if ($result = $db->query($sql))
        while ($t = $db->fetch_array($result)) {
            if ($t['t_done'] == 0)
                $status = 'pending';
            if (($t['t_done'] == 1) && ($t['t_success'] == 0))
                $status = 'error';
            if (($t['t_done'] == 1) && ($t['t_success'] == 1))
                $status = 'ok';
            $xtpl->transaction($t['t_id'], ($t["server_name"] == "") ? "---" : $t["server_name"],
                ($t["t_vps"] == 0) ? "---" : $t["t_vps"], transaction_label($t['t_type']), $status);
        }
    $xtpl->transactions_out();
}

function transaction_label($t_type)
{
    switch ($t_type) {
        case T_RESTART_NODE:
            $action_label = 'REBOOT';
            break;
        case T_START_VE:
            $action_label = 'Start';
            break;
        case T_STOP_VE:
            $action_label = 'Stop';
            break;
        case T_RESTART_VE:
            $action_label = 'Restart';
            break;
        case T_EXEC_LIMITS:
            $action_label = 'Limits';
            break;
        case T_EXEC_PASSWD:
            $action_label = 'Passwd';
            break;
        case T_EXEC_HOSTNAME:
            $action_label = 'Hostname';
            break;
        case T_EXEC_DNS:
            $action_label = 'DNS Server';
            break;
        case T_EXEC_IPADD:
            $action_label = 'IP +';
            break;
        case T_EXEC_IPDEL:
            $action_label = 'IP -';
            break;
        case T_EXEC_OTHER:
            $action_label = 'exec';
            break;
        case T_CREATE_VE:
            $action_label = 'New';
            break;
        case T_DESTROY_VE:
            $action_label = 'Delete';
            break;
        case T_REINSTALL_VE:
            $action_label = 'Reinstall';
            break;
        case T_MIGRATE_OFFLINE:
            $action_label = 'Off-Migrace';
            break;
        case T_MIGRATE_ONLINE:
            $action_label = 'ON-MIGRACE';
            break;
        case T_SNAPSHOT:
            $action_label = 'Snapshot';
            break;
        case T_MOVE_SNAPSHOT:
            $action_label = 'Mv snapshot';
            break;
        case T_FIREWALL_RELOAD:
            $action_label = 'FW Reload';
            break;
        case T_FIREWALL_FLUSH:
            $action_label = 'FW Flush';
            break;
        case T_CLUSTER_STORAGE_CFG_RELOAD:
            $action_label = 'STRG rld';
            break;
        case T_CLUSTER_STORAGE_STARTUP:
            $action_label = 'STRG up';
            break;
        case T_CLUSTER_STORAGE_SHUTDOWN:
            $action_label = 'STRG down';
            break;
        case T_CLUSTER_STORAGE_SOFTWARE_INSTALL:
            $action_label = 'STRG install';
            break;
        case T_CLUSTER_TEMPLATE_COPY:
            $action_label = 'TMPL copy';
            break;
        case T_CLUSTER_TEMPLATE_DELETE:
            $action_label = 'TMPL del';
            break;
        case T_CLUSTER_IP_REGISTER:
            $action_label = 'IP reg';
            break;
        case T_ENABLE_DEVICES:
            $action_label = 'Enable devices';
            break;
        case T_ENABLE_TUNTAP:
            $action_label = 'Enable tuntap';
            break;
        case T_ENABLE_IPTABLES:
            $action_label = 'Enable iptables';
            break;
        case T_ENABLE_FUSE:
            $action_label = 'Enable fuse';
            break;
        default:
            $action_label = '[' . $t_type . ']';
    }
    return $action_label;
}

function get_template($template_name)
{
    if (!file_exists('/vz/template/cache/' . $template_name)) {
        exec('wget ' . TEMPLATE_PATH . $template_name . '.tar.gz -O /vz/template/cache/' .
            $template_name . '.tar.gz');
        if (!file_exists('/vz/template/cache/' . $template_name . '.tar.gz')) {
            return false;
        }
        $get_md5 = file_get_contents(TEMPLATE_PATH . $template_name . '.tar.gz.md5sum');
        $get_md5 = trim($get_md5);
        if (trim($get_md5 == "")) {
        	unlink('/vz/template/cache/' . $template_name . '.tar.gz');
            return false;
        } else {
            $template_md5 = md5_file('/vz/template/cache/' . $template_name . '.tar.gz');
            if ($template_md5 != $get_md5) {
            	unlink('/vz/template/cache/' . $template_name . '.tar.gz');
                return false;
            } else {
                return true;
            }
        }
    }
}

function do_transaction($t)
{
    // debug
    print_r($t);
    global $db, $firewall, $cluster_cfg, $cluster;
    $ret = false;
    $output[0] = 'SUCCESS';
    if ($t['t_server'] == SERVER_ID && !(DEMO_MODE))
        switch ($t['t_type']) {
            case T_START_VE:
                if ($vps = vps_load($t['t_vps'])) {
                    exec_wrapper(BIN_VZCTL . ' start ' . $db->check($vps->veid), $output, $retval);
                    $ret = ($retval == 0);
                }
                break;
            case T_STOP_VE:
                if ($vps = vps_load($t['t_vps'])) {
                    exec_wrapper(BIN_VZCTL . ' stop ' . $db->check($vps->veid), $output, $retval);
                    $ret = ($retval == 0);
                }
                break;
            case T_RESTART_VE:
                if ($vps = vps_load($t['t_vps'])) {
                    exec_wrapper(BIN_VZCTL . ' stop ' . $db->check($vps->veid), $output, $retval);
                    if ($retval != 0)
                        $ret = false;
                    else {
                        exec_wrapper(BIN_VZCTL . ' start ' . $db->check($vps->veid), $output, $retval);
                        $ret = ($retval == 0);
                    }
                }
                break;
            case T_EXEC_LIMITS:
            case T_EXEC_PASSWD:
            case T_EXEC_HOSTNAME:
            case T_EXEC_DNS:
            case T_EXEC_IPADD:
            case T_EXEC_IPDEL:
                if ($vps = vps_load($t['t_vps'])) {
                    exec_wrapper(BIN_VZCTL . ' set ' . $db->check($vps->veid) . ' --save ' . $db->
                        check(unserialize($t['t_param'])), $output, $retval);
                    $ret = ($retval == 0);
                }
                break;
            case T_EXEC_OTHER:
                break;
            case T_CREATE_VE:
                $params = unserialize($t['t_param']);
                // download template
                $get_template = get_template($db->check($params['template']));
                if($get_template == false){
					$ret = false;
					$sql = "delete from vps where vpsid=" . $db->check($t['t_vps']);
                    $db->query($sql);
					break;
				}
                exec_wrapper(BIN_VZCTL . ' create ' . $db->check($t['t_vps']) . ' --ostemplate ' .
                    $db->check($params['template']) . ' --hostname ' . $db->check($params['hostname']),
                    $output, $retval);
                if ($retval != 0) {
                    $ret = false;
                    $sql = "delete from vps where vpsid=" . $db->check($t['t_vps']);
                    $db->query($sql);
                } else {
                    exec_wrapper(BIN_VZCTL . ' set ' . $db->check($t['t_vps']) .
                        ' --save --nameserver ' . $db->check($params['nameserver']) . ' --onboot yes', $output,
                        $retval);
                    $ret = ($retval == 0);
                    // delete template
                    unlink('/vz/template/cache/' . $db->check($params['template']) . '.tar.gz');
                }
                break;
            case T_DESTROY_VE:
            	// check if runnig ??
            	$run = exec(BIN_VZLIST . ' ' . $db->check($t['t_vps']));
            	if(strstr($run,'running')){
					// stop
					exec_wrapper(BIN_VZCTL . ' stop ' . $t['t_vps'], $output, $retval);
				}
                exec_wrapper(BIN_VZCTL . ' destroy ' . $db->check($t['t_vps']), $output, $retval);
                $ret = ($retval == 0);
                break;
            case T_REINSTALL_VE:
                $retval = $retvala = $retvalb = $retvalc = $retvald = 1;
                $params = unserialize($t['t_param']);
                exec_wrapper(BIN_VZCTL . ' stop ' . $t['t_vps'], $output, $retval);
                if ($retval == 0)
                    exec_wrapper(BIN_VZCTL . ' destroy ' . $db->check($t['t_vps']), $output, $retvala);
                if ($retvala == 0)
                    exec_wrapper(BIN_VZCTL . ' create ' . $db->check($t['t_vps']) . ' --ostemplate ' .
                        $db->check($params['template']) . ' --hostname ' . $db->check($params['hostname']),
                        $output, $retvalb);
                if ($retvalb == 0)
                    exec_wrapper(BIN_VZCTL . ' set ' . $db->check($t['t_vps']) .
                        ' --save --nameserver ' . $db->check($params['nameserver']) . ' --onboot yes', $output,
                        $retvalc);
                if ($retvalc == 0)
                    exec_wrapper(BIN_VZCTL . ' start ' . $db->check($t['t_vps']), $output, $retvald);
                $ret = ($retvald == 0);
                break;
            case T_MIGRATE_OFFLINE:
                $params = unserialize($t['t_param']);
                exec_wrapper('vzmigrate ' . $db->check($params['target']) . ' ' . $db->check($t['t_vps']),
                    $output, $retval);
                $ret = ($retval == 0);
                break;
            case T_MIGRATE_ONLINE:
                $params = unserialize($t['t_param']);
                exec_wrapper('vzmigrate --online ' . $db->check($params['target']) . ' ' . $db->
                    check($t['t_vps']), $output, $retval);
                // If we were not successful using online migration, fall back to offline one
                if (($retval != 0) && ($params)) {
                    $sql = 'UPDATE transactions SET t_type=' . T_MIGRATE_OFFLINE . ' WHERE t_id=' .
                        $db->check($t['t_id']);
                    $db->query($sql);
                    exec_wrapper('vzmigrate ' . $db->check($params['target']) . ' ' . $db->check($t['t_vps']),
                        $output, $retval);
                }
                $ret = ($retval == 0);
                break;
            case T_SNAPSHOT:
                $params = unserialize($t['t_param']);
                exec_wrapper('vzdump --suspend ' . $db->check($t['t_vps']), $output, $retval);
                $ret = ($retval == 0);
                break;
            case T_FIREWALL_RELOAD:
                $rules_to_apply = unserialize($t['t_param']);
                $fault = false;
                if ($rules_to_apply) {
                    if ($rules_to_apply['ip_v'] == 4) {
                        $firewall->commit_rule('-F OUTPUT_' . $rules_to_apply['ip_id']);
                        $firewall->commit_rule('-F INPUT_' . $rules_to_apply['ip_id']);
                    } else {
                        $firewall->commit_rule6('-F OUTPUT_' . $rules_to_apply['ip_id']);
                        $firewall->commit_rule6('-F INPUT_' . $rules_to_apply['ip_id']);
                    }
                    foreach ($rules_to_apply['rules'] as $rule) {
                        if (!$fault) {
                            if ($rules_to_apply['ip_v'] == 4) {
                                $res = $firewall->commit_rule($rule);
                            } else {
                                $res = $firewall->commit_rule6($rule);
                            }
                            $fault = (!$res);
                        }
                    }
                } else
                    $fault = true;
                if ($fault) {
                    /* TODO Apocalypse scheme */
                }
                $ret = (!$fault);
                break;
            case T_FIREWALL_FLUSH:
                $ip_id = unserialize($t['t_param']);
                $ip = get_ip_by_id($ip_id);
                if ($ip['ip_v'] == 4) {
                    $res1 = $firewall->commit_rule('-F OUTPUT_' . $ip['ip_id']);
                    $res2 = $firewall->commit_rule('-F INPUT_' . $ip['ip_id']);
                } else {
                    $res1 = $firewall->commit_rule6('-F OUTPUT_' . $ip['ip_id']);
                    $res2 = $firewall->commit_rule6('-F INPUT_' . $ip['ip_id']);
                }
                $ret = ($res1 && $res2);
                break;
            case T_CLUSTER_TEMPLATE_COPY:
                $params = unserialize($t["t_param"]);
                $this_node = new cluster_node(SERVER_ID);
                $ret = $this_node->fetch_remote_template($params["templ_id"], $params["remote_server_id"]);
                break;
            case T_CLUSTER_TEMPLATE_DELETE:
                $params = unserialize($t["t_param"]);
                $this_node = new cluster_node(SERVER_ID);
                $ret = $this_node->delete_template($params["templ_id"]);
                break;
            case T_CLUSTER_IP_REGISTER:
                $params = unserialize($t["t_param"]);
                $ret = true;
                if ($params["ip_v"] == 6) {
                    $ret &= $firewall->commit_rule6("-N INPUT_" . $params["ip_id"]);
                    $ret &= $firewall->commit_rule6("-N OUTPUT_" . $params["ip_id"]);
                    $ret &= $firewall->commit_rule6("-A FORWARD -s {$params["ip_addr"]} -g OUTPUT_{$params["ip_id"]}");
                    $ret &= $firewall->commit_rule6("-A FORWARD -d {$params["ip_addr"]} -g INPUT_{$params["ip_id"]}");
                    $ret &= $firewall->commit_rule6("-A aztotal -s {$params["ip_addr"]}");
                    $ret &= $firewall->commit_rule6("-A aztotal -d {$params["ip_addr"]}");
                } else {
                    $ret &= $firewall->commit_rule("-N INPUT_" . $params["ip_id"]);
                    $ret &= $firewall->commit_rule("-N OUTPUT_" . $params["ip_id"]);
                    $ret &= $firewall->commit_rule("-A FORWARD -s {$params["ip_addr"]} -g OUTPUT_{$params["ip_id"]}");
                    $ret &= $firewall->commit_rule("-A FORWARD -d {$params["ip_addr"]} -g INPUT_{$params["ip_id"]}");
                    $ret &= $firewall->commit_rule("-A anix -s {$params["ip_addr"]}");
                    $ret &= $firewall->commit_rule("-A anix -d {$params["ip_addr"]}");
                    $ret &= $firewall->commit_rule("-A atranzit -s {$params["ip_addr"]}");
                    $ret &= $firewall->commit_rule("-A atranzit -d {$params["ip_addr"]}");
                    $ret &= $firewall->commit_rule("-A aztotal -s {$params["ip_addr"]}");
                    $ret &= $firewall->commit_rule("-A aztotal -d {$params["ip_addr"]}");
                }
                break;
            case T_ENABLE_DEVICES:
                $params = unserialize($t["t_param"]);
                $devices_cmd = '';
                if ($params[0]) {
                    foreach ($params as $device) {
                        $devices_cmd .= ' --devices ' . $device;
                    }
                    exec_wrapper(BIN_VZCTL . ' set ' . $db->check($t['t_vps']) . ' ' . $devices_cmd .
                        ' --save', $output, $retval);
                }
                $ret = ($retval == 0);
                break;
            case T_ENABLE_TUNTAP:
                exec_wrapper(BIN_VZCTL . ' stop ' . $db->check($t['t_vps']), $trash, $trash2);
                exec_wrapper(BIN_VZCTL . ' set ' . $db->check($t['t_vps']) .
                    ' --capability net_admin:on --save', $output, $retval);
                exec_wrapper(BIN_VZCTL . ' start ' . $db->check($t['t_vps']), $trash, $trash2);
                if ($retval == 0)
                    exec_wrapper(BIN_VZCTL . ' exec ' . $db->check($t['t_vps']) .
                        ' mkdir -p /dev/net', $output, $retval);
                if ($retval == 0)
                    exec_wrapper(BIN_VZCTL . ' exec ' . $db->check($t['t_vps']) .
                        ' mknod /dev/net/tun c 10 200', $output, $retval);
                if ($retval == 0)
                    exec_wrapper(BIN_VZCTL . ' exec ' . $db->check($t['t_vps']) .
                        ' chmod 600 /dev/net/tun', $output, $retval);
                $ret = ($retval == 0);
                break;
            case T_ENABLE_FUSE:
                exec_wrapper(BIN_VZCTL . ' exec ' . $db->check($t['t_vps']) .
                    ' mknod /dev/fuse c 10 229', $output, $retval);
                $ret = ($retval == 0);
                break;
            case T_ENABLE_IPTABLES:
                exec_wrapper(BIN_VZCTL . ' stop ' . $db->check($t['t_vps']), $trash, $trash2);
                $modules = array('ip_conntrack', 'ip_conntrack_ftp', 'ip_conntrack_irc',
                    'ip_nat_ftp', 'ip_nat_irc', 'ip_tables', 'ipt_LOG', 'ipt_REDIRECT', 'ipt_REJECT',
                    'ipt_TCPMSS', 'ipt_TOS', 'ipt_conntrack', 'ipt_helper', 'ipt_length',
                    'ipt_limit', 'ipt_multiport', 'ipt_state', 'ipt_tcpmss', 'ipt_tos', 'ipt_ttl',
                    'iptable_filter', 'iptable_mangle', 'iptable_nat');
                $iptables_cmd = '';
                foreach ($modules as $module) {
                    $iptables_cmd .= ' --iptables ' . $module;
                }
                exec_wrapper(BIN_VZCTL . ' set ' . $db->check($t['t_vps']) . ' ' . $iptables_cmd .
                    ' --save', $output, $retval);
                if ($retval == 0)
                    exec_wrapper(BIN_VZCTL . ' set ' . $db->check($t['t_vps']) .
                        ' --numiptent 200 --save', $output, $retval);
                exec_wrapper(BIN_VZCTL . ' start ' . $db->check($t['t_vps']), $trash, $trash2);
                $ret = ($retval == 0);
                break;
            case T_RESTART_NODE:
                $sql = 'UPDATE transactions SET t_done=1,
				t_success=1,
				t_output="' . serialize($ret) . '"
				WHERE t_id=' . $db->check($t['t_id']);
                $db->query($sql);
                exec_wrapper('reboot', $output, $retval);
                $ret = true;
                break;
            default:
                return false;
        }
    else
        $ret = false;
    if (DEMO_MODE)
        $ret = true;
    // if success
    if ($ret != false)
        $sql = 'UPDATE transactions SET t_done=1,
				t_success=1,
				t_output="' . serialize($ret) . '"
				WHERE t_id=' . $db->check($t['t_id']);
    else
        $sql = 'UPDATE transactions SET t_done=1, t_success=0 WHERE t_id=' . $db->check($t['t_id']);
    $db->query($sql);
    return $ret;
}

?>
