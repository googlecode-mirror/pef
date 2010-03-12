<?php
/*
    ./pages/page_transactions.php

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

  $xtpl->title(_("Transaction log"));

  $sql = 'SELECT COUNT(*) as `entries` FROM transactions, servers, members WHERE members.m_id = transactions.t_m_id AND transactions.t_server = servers.server_id ORDER BY t_id DESC;';
  $t = $db->fetch_array($db->query($sql));
  $entries = $t["entries"];

  $pages = ceil($entries/$cfg_transactions["per_page"]);

  if (isset($_GET["id"])) {
/*   $xtpl->table_add_category('');
 *   $xtpl->table_add_category('');
 *   $xtpl->table_td('Not Implemented Yet');
 *   $xtpl->table_td('');
 *   $xtpl->table_tr();
 *   $xtpl->table_out();
 */}	

  $xtpl->table_begin(gen_pages_listing($_GET["page_number"], $pages, $cfg_transactions["max_offset_listing"]));

  $xtpl->table_add_category('T_ID');
  $xtpl->table_add_category(strtoupper(_("time of addition")));
  $xtpl->table_add_category(strtoupper(_("member")));
  $xtpl->table_add_category(strtoupper(_("server")));
  $xtpl->table_add_category(strtoupper(_("vps")));
  $xtpl->table_add_category(strtoupper(_("type of action")));
  $xtpl->table_add_category(strtoupper(_("done")));
  $xtpl->table_add_category(strtoupper(_("ok?")));

  $sql = 'SELECT * FROM transactions
	    LEFT JOIN members
	    ON transactions.t_m_id = members.m_id
	    LEFT JOIN servers
	    ON transactions.t_server = servers.server_id
	    ORDER BY transactions.t_id DESC LIMIT '.(($_GET["page_number"]*$cfg_transactions["per_page"])*1).','.$cfg_transactions["per_page"].';';

  if ($result = $db->query($sql))
      while ($t = $db->fetch_array($result)) {
	$xtpl->table_td($t["t_id"]);
	$xtpl->table_td(strftime("%Y-%m-%d %H:%M", $t["t_time"]));
	$xtpl->table_td($t["m_nick"]);
	$xtpl->table_td(($t["server_name"] == "") ? _("--Every--") : $t["server_name"]);
	$xtpl->table_td(($t["t_vps"] == 0) ? _("--Every--") : $t["t_vps"]);
	$xtpl->table_td(transaction_label($t["t_type"]));
	$xtpl->table_td($t["t_done"]);
	$xtpl->table_td($t["t_success"]);
	if ($t["t_done"]==1 && $t["t_success"]==1)
		$xtpl->table_tr(false, 'ok');
	elseif ($t["t_done"]==1 && $t["t_success"]==0)
		$xtpl->table_tr(false, 'error');
	elseif ($t["t_done"]==0 && $t["t_success"]==0)
		$xtpl->table_tr(false, 'pending');
	else
		$xtpl->table_tr();
      }

  $xtpl->table_end(gen_pages_listing($_GET["page_number"], $pages, $cfg_transactions["max_offset_listing"]));

  $xtpl->table_out();

} else $xtpl->perex(_("Access forbidden"), _("You have to log in to be able to access vpsAdmin's functions"));
?>
