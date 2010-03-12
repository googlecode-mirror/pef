<?php
/*
./lib/vps.lib.php

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

function get_vps_array($by_m_id = false, $by_server = false)
{
    global $db;
    //$sql = 'SELECT vps_id FROM vps '. (($_SESSION[is_admin]) ? 'ORDER BY m_id ASC' : " WHERE m_id = {$db->check($_SESSION[member][m_id])} ");
    $sql = 'SELECT * FROM vps';
    if ($_SESSION["is_admin"]) {
        if ($by_server)
            $sql .= " WHERE vps_server = $by_server ";
        $sql .= ' ORDER BY vps_server,m_id,vps_id ASC';
    } else {
        $sql .= " WHERE m_id = {$db->check($_SESSION["member"]["m_id"])}";
        if ($by_server)
            $sql .= " AND vps_server = $by_server";
    }
    $ret = array();
    if ($result = $db->query($sql))
        while ($row = $db->fetch_array($result)) {
            $ret[] = new vps_load($row["vps_id"]);
        }
    return $ret;
}

function vps_load($veid = false)
{
    $vps = new vps_load($veid);
    return $vps;
}


class vps_load
{

    public $veid = 0;
    public $ve = array();
    public $exists = false;
    public $devices = array("tuntap" => array(T_ENABLE_TUNTAP, 'c:10:200:rw'),
        "iptables" => array(T_ENABLE_IPTABLES, ''), "fuse" => array(T_ENABLE_FUSE,
        'c:10:229:rw'));

    function vps_load($ve_id = 'none')
    {
        global $db;
        if (is_numeric($ve_id)) {
            $sql = 'SELECT * FROM vps,servers,members WHERE vps.m_id = members.m_id AND server_id = vps_server AND vps_id = "' .
                $db->check($ve_id) . '"';
            if ($result = $db->query($sql))
                if ($this->ve = $db->fetch_array($result))
                    if ($this->ve["vps_id"] == $ve_id && (($this->ve["m_id"] == $_SESSION["member"]["m_id"]) ||
                        $_SESSION["is_admin"])) {
                        $this->exists = true;
                        $this->veid = $ve_id;
                    } else
                        $this->exists = false;
                else
                    $this->exists = false;
            else
                $this->exists = false;
        } else
            $this->exists = false;
        return true;
    }

    function create_new($server_id, $template_id, $hostname, $m_id, $privvmpages, $diskspace,
        $info)
    {
        global $db, $cluster;
        if (!$this->exists && $template = template_by_id($template_id)) {
            $sql = 'INSERT INTO vps
						SET m_id = "' . $db->check($m_id) . '",
							vps_template = "' . $db->check($template["templ_id"]) . '",
							vps_info ="' . $db->check(addslashes($info)) . '",
							vps_nameserver ="' . $db->check($cluster->get_first_suitable_dns($cluster->
                get_location_of_server($server_id))) . '",
							vps_hostname ="' . $db->check($hostname) . '",
							vps_server ="' . $db->check($server_id) . '",
							vps_privvmpages = 0,
							vps_diskspace = 0';
            $db->query($sql);
            $this->veid = $db->insert_id();
            $this->exists = true;
            $params["hostname"] = $hostname;
            $params["template"] = $template["templ_name"];
            $params["nameserver"] = $cluster->get_first_suitable_dns($cluster->
                get_location_of_server($server_id));
            $this->ve["vps_server"] = $server_id;
            $this->ve["vps_nameserver"] = $params["nameserver"];
            $this->ve["vps_template"] = $template["templ_name"];
            add_transaction($_SESSION["member"]["m_id"], $server_id, $this->veid,
                T_CREATE_VE, $params);
            $this->set_privvmpages($privvmpages, true);
            $this->set_diskspace($diskspace, true);
        }
    }
    function reinstall()
    {
        global $cluster;
        if ($this->exists) {
            $ips = $this->iplist();
            if ($ips)
                foreach ($ips as $ip) {
                    $this->ipdel($ip["ip_addr"]);
                }
            $template = template_by_id($this->ve["vps_template"]);
            $params["hostname"] = $this->ve["vps_hostname"];
            $params["template"] = $template["templ_name"];
            $params["nameserver"] = $cluster->get_first_suitable_dns($cluster->
                get_location_of_server($this->ve["vps_server"]));
            add_transaction($_SESSION["member"]["m_id"], $this->ve["vps_server"], $this->
                veid, T_REINSTALL_VE, $params);
            $tmp["vps_privvmpages"] = $this->ve["vps_privvmpages"];
            $tmp["vps_diskspace"] = $this->ve["vps_diskspace"];
            $this->set_privvmpages(0, true);
            $this->set_diskspace(0, true);
            $this->set_privvmpages($tmp["vps_privvmpages"], true);
            $this->set_diskspace($tmp["vps_diskspace"], true);
            if ($ips)
                foreach ($ips as $ip) {
                    $this->ipadd($ip["ip_addr"]);
                }
        }
    }
    function change_distro_before_reinstall($template_id)
    {
        global $db;
        $sql = 'UPDATE vps SET vps_template = "' . $db->check($template_id) .
            '" WHERE vps_id=' . $db->check($this->veid);
        if ($result = $db->query($sql)) {
            $this->ve["vps_template"] = $template_id;
        }
    }
    function passwd($user, $new_pass)
    {
        if ($this->exists) {
            $command = '--userpasswd ' . $user . ':' . $new_pass;
            add_transaction($_SESSION["member"]["m_id"], $this->ve["vps_server"], $this->
                veid, T_EXEC_PASSWD, $command);
        }
    }

    function stop()
    {
        if ($this->exists) {
            add_transaction($_SESSION["member"]["m_id"], $this->ve["vps_server"], $this->
                veid, T_STOP_VE);
        }
    }

    function start()
    {
        if ($this->exists) {
            add_transaction($_SESSION["member"]["m_id"], $this->ve["vps_server"], $this->
                veid, T_START_VE);
        }
    }

    function restart()
    {
        if ($this->exists) {
            add_transaction($_SESSION["member"]["m_id"], $this->ve["vps_server"], $this->
                veid, T_RESTART_VE);
        }
    }

    function is_owner($m_id)
    {
        global $db;
        if ($this->exists) {
            return ($this->ve["m_id"] == $m_id);
        }
    }

    function set_hostname($new_hostname)
    {
        global $db;
        if ($this->exists) {
            $sql = 'UPDATE vps SET vps_hostname = "' . $db->check($new_hostname) .
                '" WHERE vps_id=' . $db->check($this->veid);
            if ($result = $db->query($sql)) {
                $command = '--hostname ' . $new_hostname;
                add_transaction($_SESSION["member"]["m_id"], $this->ve["vps_server"], $this->
                    veid, T_EXEC_HOSTNAME, $command);
            }
        }
    }

    function info()
    {
        global $db;
        $sql = 'SELECT * FROM vps_status WHERE vps_id =' . $db->check($this->veid) .
            ' ORDER BY id DESC LIMIT 1';
        if ($result = $db->query($sql))
            if ($s = $db->fetch_array($result)) {
                $this->ve["vps_up"] = $s["vps_up"];
                $this->ve["vps_nproc"] = $s["vps_nproc"];
                $this->ve["vps_vm_used_mb"] = $s["vps_vm_used_mb"];
                $this->ve["vps_disk_used_mb"] = $s["vps_disk_used_mb"];
            }
    }

    function destroy()
    {
        global $db;
        if ($this->exists) {
            $sql = 'DELETE FROM vps WHERE vps_id=' . $db->check($this->veid);
            $sql2 = 'UPDATE vps_ip SET vps_id = 0 WHERE vps_id=' . $db->check($this->veid);
            if ($result = $db->query($sql))
                if ($result2 = $db->query($sql2)) {
                    $this->exists = false;
                    add_transaction($_SESSION["member"]["m_id"], $this->ve["vps_server"], $this->
                        veid, T_DESTROY_VE, 'none');
                } else
                    return false;
            else
                return false;
        }
    }

    function iplist()
    {
        global $db;
        if ($this->exists) {
            $sql = 'SELECT * FROM vps_ip WHERE vps_id="' . $db->check($this->veid) .
                '" ORDER BY ip_v ASC';
            if ($result = $db->query($sql)) {
                while ($row = $db->fetch_array($result)) {
                    $ret[] = $row;
                }
                return $ret;
            } else
                return null;
        }
    }

    function ipadd($ip, $type = 4)
    {
        global $db;
        if ($this->exists) {
            if ($ipadr = ip_exists_in_table($ip)) {
                $sql = 'UPDATE vps_ip
			SET vps_id = "' . $db->check($this->veid) . '"
			WHERE ip_id = "' . $db->check($ipadr["ip_id"]) . '"';
                $db->query($sql);
                if ($db->affected_rows() > 0) {
                    $command = '--ipadd ' . $ip;
                    add_transaction($_SESSION["member"]["m_id"], $this->ve["vps_server"], $this->
                        veid, T_EXEC_IPADD, $command);
                } else
                    return null;
            }
        }
    }

    function ipdel($ip)
    {
        global $db;
        if ($this->exists) {
            $sql = 'UPDATE vps_ip SET vps_id = 0 WHERE ip_addr="' . $db->check($ip) . '"';
            if ($result = $db->query($sql)) {
                $command = '--ipdel ' . $ip;
                add_transaction($_SESSION["member"]["m_id"], $this->ve["vps_server"], $this->
                    veid, T_EXEC_IPDEL, $command);
            } else
                return null;
        }
    }

    function nameserver($nameserver)
    {
        global $db;
        if ($this->exists) {
            $sql = 'UPDATE vps SET vps_nameserver = "' . $db->check($nameserver) .
                '" WHERE vps_id = ' . $db->check($this->veid);
            $db->query($sql);
            $command = '--nameserver ' . $nameserver;
            $this->ve["vps_nameserver"] = $nameserver;
            add_transaction($_SESSION["member"]["m_id"], $this->ve["vps_server"], $this->
                veid, T_EXEC_DNS, $command);
        }
    }

    function offline_migrate($target_id)
    {
        global $db, $cluster;
        if ($this->exists) {
            $servers = list_servers();
            if (isset($servers[$target_id])) {
                $sql = 'UPDATE vps SET vps_server = "' . $db->check($target_id) .
                    '" WHERE vps_id = ' . $db->check($this->veid);
                $db->query($sql);
                $target_server = server_by_id($target_id);
                if ($this->get_location() != $cluster->get_location_of_server($target_id)) {
                    $ips = $this->iplist();
                    if ($ips)
                        foreach ($ips as $ip) {
                            $this->ipdel($ip["ip_addr"]);
                        }
                }
                $params["target"] = $target_server["server_ip4"];
                add_transaction($_SESSION["member"]["m_id"], $this->ve["vps_server"], $this->
                    veid, T_MIGRATE_OFFLINE, $params);
                $this->ve["vps_server"] = $target_id;
                if ($this->get_location() != $cluster->get_location_of_server($target_id)) {
                    $this->nameserver($cluster->get_first_suitable_dns($cluster->
                        get_location_of_server($target_id)));
                }
            }
        }
    }

    function online_migrate($target_id)
    {
        global $db;
        if ($this->exists) {
            $servers = list_servers();
            if (isset($servers[$target_id])) {
                $sql = 'UPDATE vps SET vps_server = "' . $db->check($target_id) .
                    '" WHERE vps_id = ' . $db->check($this->veid);
                $db->query($sql);
                $target_server = server_by_id($target_id);
                $params["target"] = $target_server["server_ip4"];
                $params["target_id"] = $target_id;
                add_transaction($_SESSION["member"]["m_id"], $this->ve["vps_server"], $this->
                    veid, T_MIGRATE_ONLINE, $params);
                $this->ve["vps_server"] = $target_id;
            }
        }
    }
    function change_server($target_id)
    {
        global $db;
        if ($this->exists) {
            $servers = list_servers();
            if (isset($servers[$target_id])) {
                $sql = 'UPDATE vps SET vps_server = "' . $db->check($target_id) .
                    '" WHERE vps_id = ' . $db->check($this->veid);
                $db->query($sql);
                $this->ve["vps_server"] = $target_id;
            }
        }

    }

    function vchown($m_id)
    {
        global $db;
        if ($this->exists && $_SESSION["is_admin"]) {
            $sql = 'UPDATE vps SET m_id = ' . $db->check($m_id) . ' WHERE vps_id = ' . $db->
                check($this->veid);
            $db->query($sql);
            if ($db->affected_rows() == 1) {
                $this->ve["m_id"] = $m_id;
                return true;
            } else
                return false;
        }
    }


    function set_privvmpages($privvmpages, $force = false)
    {
        global $db;
        if (($this->exists && $_SESSION["is_admin"]) || $force) {
            $vm = limit_privvmpages_by_id($privvmpages);
            $vzctl = "{$vm["vm_lim_soft"]}M" . (($vm["vm_lim_hard"]) ? ":{$vm["vm_lim_hard"]}M" :
                '');
            $sql = 'UPDATE vps SET vps_privvmpages = "' . $db->check($privvmpages) .
                '" WHERE vps_id = ' . $db->check($this->veid);
            $db->query($sql);
            if ($db->affected_rows() == 1) {
                $command = '--privvmpages ' . $vzctl;
                $this->ve["vps_privvmpages"] = $privvmpages;
                add_transaction($_SESSION["member"]["m_id"], $this->ve["vps_server"], $this->
                    veid, T_EXEC_LIMITS, $command);
            } else
                return array('0' => '');
        }
    }

    /*  function set_cpulimit($cpulimit) {
    global $db;
    if ($this->exists && $_SESSION["is_admin"]) {
    $sql = 'UPDATE vps SET vps_cpulimit = "'.$db->check($cpulimit).'" WHERE vps_id = '.$db->check($this->veid);
    $db->query($sql);
    if ($db->affected_rows() == 1) {
    $command = '--cpulimit '.$cpulimit;
    add_transaction($_SESSION["member"]["m_id"], $this->ve["vps_server"], $this->veid, T_EXEC_LIMITS, $command);
    } else return array('0' => '');
    }
    }

    function set_cpuprio($cpuunits) {
    global $db;
    if ($this->exists && $_SESSION["is_admin"]) {
    $sql = 'UPDATE vps SET vps_cpuprio = "'.$db->check($cpuunits).'" WHERE vps_id = '.$db->check($this->veid);
    $db->query($sql);
    if ($db->affected_rows() == 1) {
    $command = '--cpuunits '.$cpuunits;
    add_transaction($_SESSION["member"]["m_id"], $this->ve["vps_server"], $this->veid, T_EXEC_LIMITS, $command);
    } else return array('0' => '');
    }
    }
    */
    function set_diskspace($diskspace, $force = false)
    {
        global $db;
        if (($this->exists && $_SESSION["is_admin"]) || $force) {
            $d = limit_diskspace_by_id($diskspace);
            $vzctl = "{$d["d_gb"]}G:{$d["d_gb"]}G";
            $sql = 'UPDATE vps SET vps_diskspace = "' . $db->check($diskspace) .
                '" WHERE vps_id = ' . $db->check($this->veid);
            $db->query($sql);
            if ($db->affected_rows() == 1) {
                $command = '--diskspace ' . $vzctl;
                $this->ve["vps_diskspace"] = $diskspace;
                add_transaction($_SESSION["member"]["m_id"], $this->ve["vps_server"], $this->
                    veid, T_EXEC_LIMITS, $command);
            } else
                return array('0' => '');
        }
    }

    function enable_device($device)
    {
        global $db;
        if (isset($this->devices[$device])) {
            $sql = 'UPDATE vps SET vps_' . $device . '_enabled = "1" WHERE vps_id = ' . $db->
                check($this->veid);
            $db->query($sql);
            if ($db->affected_rows() == 1) {
                if (isset($this->devices[$device][1]))
                    add_transaction($_SESSION["member"]["m_id"], $this->ve["vps_server"], $this->
                        veid, T_ENABLE_DEVICES, $this->get_enabled_devices());
                add_transaction($_SESSION["member"]["m_id"], $this->ve["vps_server"], $this->
                    veid, $this->devices[$device][0]);
            }
        }
    }

    function enable_devices($devices, $force = false)
    {
        if (($this->exists && $_SESSION["is_admin"]) || $force) {
            foreach ($devices as $device) {
                $this->enable_device($device);
            }
        }
    }

    function is_enabled($device)
    {
        global $db;
        if (!isset($this->devices[$device]))
            return false;

        $sql = 'SELECT vps_' . $device . '_enabled FROM vps WHERE vps_id = ' . $db->
            check($this->veid);
        if ($result = $db->query($sql)) {
            if ($row = $db->fetch_row($result)) {
                $ret = $row[0];
            } else {
                $ret = false;
            }
        } else {
            $ret = false;
        }
        return $ret;
    }

    function get_enabled_devices()
    {
        $enabled_devices = array();
        foreach ($this->devices as $device => $device_params) {
            if ($this->is_enabled($device) && isset($device_params[1]))
                $enabled_devices[] = $device_params[1];
        }
        return $enabled_devices;
    }
    function get_location()
    {
        global $cluster;
        return $cluster->get_location_of_server($this->ve["vps_server"]);
    }
}
?>
