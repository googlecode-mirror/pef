<?php
/*
    ./lib/db.lib.php

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

class sql_db
  {
    public $connection;
    
    function sql_db($host,$user,$pass,$name)
    {
    $this->connection = mysql_connect($host,$user,$pass);
    mysql_select_db($name,$this->connection);
    if (!$this->connection) die ('Unable connect to database.');
    return $this->connection;
    }
    
    function query($cmd)
    {
	//echo $cmd.'<br />';
    return mysql_query($cmd,$this->connection);
    }
    
    function fetch_row($handle)
    {
    return mysql_fetch_row($handle);
    }

    function fetch_array($handle)
    {
    return mysql_fetch_array($handle);
    }

    function fetch_object($handle)
    {
    return mysql_fetch_object($handle);
    }

    function num_fields($of_what)
    {
    return mysql_num_fields($of_what); 
    }
    
    function affected_rows()
    {
    return mysql_affected_rows();
    }
	
	function insert_id() {
		return mysql_insert_id();
	}
	
	function check($string) {
		return mysql_real_escape_string($string);
	}
	
  };
?>
