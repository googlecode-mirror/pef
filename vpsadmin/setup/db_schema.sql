CREATE TABLE `cfg_diskspace` (
  `d_id` int(10) unsigned NOT NULL auto_increment,
  `d_gb` int(10) unsigned NOT NULL,
  `d_label` varchar(64) NOT NULL,
  PRIMARY KEY  (`d_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
CREATE TABLE `cfg_dns` (
  `dns_id` int(10) unsigned NOT NULL auto_increment,
  `dns_ip` varchar(63) NOT NULL,
  `dns_label` varchar(63) NOT NULL,
  `dns_is_universal` tinyint(1) unsigned default '0',
  `dns_location` int(10) unsigned default NULL,
  PRIMARY KEY  (`dns_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
CREATE TABLE `cfg_privvmpages` (
  `vm_id` int(10) unsigned NOT NULL auto_increment,
  `vm_label` varchar(64) NOT NULL,
  `vm_lim_hard` int(10) unsigned NOT NULL,
  `vm_lim_soft` int(10) unsigned NOT NULL,
  `vm_usable` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`vm_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
CREATE TABLE `cfg_templates` (
  `templ_id` int(10) unsigned NOT NULL auto_increment,
  `templ_name` varchar(64) NOT NULL,
  `templ_label` varchar(64) NOT NULL,
  `templ_info` text,
  PRIMARY KEY  (`templ_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
CREATE TABLE `firewall` (
  `id` int(11) NOT NULL auto_increment,
  `ip` int(11) NOT NULL,
  `command` varchar(255) character set latin1 default NULL,
  `ordinal` int(11) default NULL,
  `approved` tinyint(1) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
CREATE TABLE `locations` (
  `location_id` int(10) unsigned NOT NULL auto_increment,
  `location_label` varchar(63) NOT NULL,
  `location_has_ipv6` tinyint(1) NOT NULL,
  PRIMARY KEY  (`location_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
CREATE TABLE `members` (
  `m_info` text,
  `m_id` int(10) unsigned NOT NULL auto_increment,
  `m_level` int(10) unsigned NOT NULL,
  `m_nick` varchar(63) NOT NULL,
  `m_name` varchar(255) NOT NULL,
  `m_pass` varchar(255) NOT NULL,
  `m_mail` varchar(127) NOT NULL,
  `m_address` text NOT NULL,
  `m_lang` varchar(16) default NULL,
  `m_paid_until` varchar(32) default NULL,
  `m_last_activity` int(10) unsigned default NULL,
  PRIMARY KEY  (`m_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
CREATE TABLE `servers` (
  `server_id` int(10) unsigned NOT NULL auto_increment,
  `server_name` varchar(64) NOT NULL,
  `server_location` int(10) unsigned NOT NULL,
  `server_availstat` text,
  `server_ip4` varchar(127) NOT NULL,
  `server_maxvps` int(10) unsigned default NULL,
  `server_path_vz` varchar(63) NOT NULL default '/var/lib/vz',
  PRIMARY KEY  (`server_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
CREATE TABLE `servers_status` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `server_id` int(10) unsigned NOT NULL,
  `timestamp` int(10) unsigned NOT NULL,
  `ram_free_mb` int(10) unsigned default NULL,
  `disk_vz_free_gb` float unsigned default NULL,
  `cpu_load` float unsigned default NULL,
  `vpsadmin_version` varchar(63) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
CREATE TABLE `sysconfig` (
  `cfg_name` varchar(127) NOT NULL,
  `cfg_value` text,
  PRIMARY KEY  (`cfg_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
CREATE TABLE `transactions` (
  `t_id` int(10) unsigned NOT NULL auto_increment,
  `t_group` int(10) unsigned default NULL,
  `t_time` int(10) unsigned default NULL,
  `t_m_id` int(10) unsigned default NULL,
  `t_server` int(10) unsigned default NULL,
  `t_vps` int(10) unsigned default NULL,
  `t_type` int(10) unsigned NOT NULL,
  `t_success` int(10) unsigned NOT NULL,
  `t_done` tinyint(1) unsigned NOT NULL,
  `t_param` text,
  `t_output` text,
  PRIMARY KEY  (`t_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
CREATE TABLE `transaction_groups` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `is_clusterwide` tinyint(1) unsigned default '0',
  `is_locationwide` tinyint(1) unsigned default '0',
  `location_id` int(10) unsigned default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
CREATE TABLE `transfered` (
  `tr_id` int(10) unsigned NOT NULL auto_increment,
  `tr_ip` varchar(127) NOT NULL,
  `tr_nix_in` bigint(63) unsigned default '0',
  `tr_nix_out` bigint(63) unsigned default '0',
  `tr_tzt_in` bigint(63) unsigned default '0',
  `tr_tzt_out` bigint(63) unsigned default '0',
  `tr_time` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`tr_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
CREATE TABLE `vps` (
  `vps_id` int(10) unsigned NOT NULL auto_increment,
  `m_id` int(63) unsigned NOT NULL,
  `vps_hostname` varchar(64) default 'darkstar',
  `vps_template` int(10) unsigned NOT NULL default '1',
  `vps_info` mediumtext,
  `vps_nameserver` varchar(255) NOT NULL default '4.2.2.2',
  `vps_privvmpages` int(10) unsigned NOT NULL default '1',
  `vps_cpulimit` varchar(255) default NULL,
  `vps_cpuprio` varchar(255) default NULL,
  `vps_diskspace` int(10) unsigned NOT NULL default '1',
  `vps_server` int(11) unsigned NOT NULL,
  `vps_fuse_enabled` tinyint(1) default '0',
  `vps_tuntap_enabled` tinyint(1) default '0',
  `vps_iptables_enabled` tinyint(1) default '0',
  PRIMARY KEY  (`vps_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=100;
CREATE TABLE `vps_ip` (
  `ip_id` int(10) unsigned NOT NULL auto_increment,
  `vps_id` int(10) unsigned NOT NULL,
  `ip_v` int(10) unsigned NOT NULL default '4',
  `ip_location` int(10) unsigned NOT NULL,
  `ip_addr` varchar(40) NOT NULL,
  PRIMARY KEY  (`ip_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
CREATE TABLE `vps_snapshots` (
  `s_id` int(10) unsigned NOT NULL auto_increment,
  `vps_id` int(10) unsigned NOT NULL,
  `server_id` varchar(63) NOT NULL,
  `s_name` varchar(63) NOT NULL,
  `s_time` int(10) unsigned NOT NULL,
  `s_size_mb` int(10) unsigned default NULL,
  PRIMARY KEY  (`s_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
CREATE TABLE `vps_status` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `vps_id` int(10) unsigned NOT NULL,
  `timestamp` int(10) unsigned NOT NULL,
  `vps_up` tinyint(1) unsigned default NULL,
  `vps_nproc` int(10) unsigned default NULL,
  `vps_vm_used_mb` int(10) unsigned default NULL,
  `vps_disk_used_mb` int(10) unsigned default NULL,
  `vps_admin_ver` varchar(63) default 'not set',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
INSERT INTO `members` (
`m_info`, `m_id`, `m_level`,
`m_nick`, `m_name`, `m_pass`,
`m_mail`, `m_address`, `m_lang`,
`m_paid_until`, `m_last_activity` )
 VALUES (NULL ,  '1',  '99',
'admin',  'fill in',
'f6fdffe48c908deb0f4c3bd36c032e72',
'admin@admin.example',  'fill in', NULL , NULL , NULL);