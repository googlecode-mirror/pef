<?php
/*
    ./lib/xtemplate.lib.php

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
    along with this program.  If not, see <http://www.gnu.org/licenses/>

    Xtemplate
     Copyright (c) 2000-2001 Barnabas Debreceni [cranx@users.sourceforge.net] XTemplate
     Copyright (c) 2002-2007 Jeremy Coates [cocomp@users.sourceforge.net] XTemplate & CachingXTemplate

*/
// if we are using xtemplate, this is not CLI application
$_SESSION["cli_mode"] = false;

class XTemplate {
	var $filecontents = '';
	var $blocks = array();
	var $parsed_blocks = array();
	var $preparsed_blocks = array();
	var $block_parse_order = array();
	var $sub_blocks = array();
	var $vars = array();
	var $filevars = array();
	var $filevar_parent = array();
	var $filecache = array();
	var $tpldir = '';
	var $files = null;
	var $filename = '';
	var $file_delim = '';//"/\{FILE\s*\"([^\"]+)\"\s*\}/m"; 
	var $filevar_delim = '';//"/\{FILE\s*\{([A-Za-z0-9\._]+?)\}\s*\}/m";  
	var $filevar_delim_nl = '';//"/^\s*\{FILE\s*\{([A-Za-z0-9\._]+?)\}\s*\}\s*\n/m";  
	var $block_start_delim = '<!-- ';         /* block start delimiter */
	var $block_end_delim = '-->';                 /* block end delimiter */
	var $block_start_word = 'BEGIN:';         /* block start word */
	var $block_end_word = 'END:';                 /* block end word */

	var $tag_start_delim = '{';
	var $tag_end_delim = '}';

	var $mainblock = 'main';
	var $output_type = 'HTML';
	var $_null_string = array('' => '');             /* null string for unassigned vars */
	var $_null_block = array('' => '');  /* null string for unassigned blocks */
	var $_error = '';
	var $_autoreset = true;                                     /* auto-reset sub blocks */
	var $_ignore_missing_blocks = true ;          // NW 17 oct 2002 - Set to FALSE to
	var $_file_name_full_path = '';
	function XTemplate ($file,  $tpldir = '', $files = null, $mainblock = 'main', $autosetup = true) {
		$this->filename = $file;
		$this->_file_name_full_path = realpath($file);
		$this->tpldir = $tpldir;
		if (is_array($files)) {
			$this->files = $files;
		}
		$this->mainblock = $mainblock;
		if ($autosetup) {
			$this->setup();
		}
	}
	function restart ($file, $tpldir = '', $files = null, $mainblock = 'main', $autosetup = true, $tag_start = '{', $tag_end = '}') {
		$this->filename = $file;
		$this->_file_name_full_path = realpath($file);
		$this->tpldir = $tpldir;
		if (is_array($files)) {
			$this->files = $files;
		}
		$this->mainblock = $mainblock;
		$this->tag_start_delim = $tag_start;
		$this->tag_end_delim = $tag_end;
		$this->filecontents = '';
		$this->blocks = array();
		$this->parsed_blocks = array();
		$this->preparsed_blocks = array();
		$this->block_parse_order = array();
		$this->sub_blocks = array();
		$this->vars = array();
		$this->filevars = array();
		$this->filevar_parent = array();
		$this->filecache = array();
		if ($autosetup) {
			$this->setup();
		}
	}
	function setup ($add_outer = false) {
		$this->tag_start_delim = preg_quote($this->tag_start_delim);
		$this->tag_end_delim = preg_quote($this->tag_end_delim);
		$this->file_delim = "/" . $this->tag_start_delim . "FILE\s*\"([^\"]+)\"\s*" . $this->tag_end_delim . "/m";
		$this->filevar_delim = "/" . $this->tag_start_delim . "FILE\s*" . $this->tag_start_delim . "([A-Za-z0-9\._]+?)" . $this->tag_end_delim . "\s*" . $this->tag_end_delim . "/m";
		$this->filevar_delim_nl = "/^\s*" . $this->tag_start_delim . "FILE\s*" . $this->tag_start_delim . "([A-Za-z0-9\._]+?)" . $this->tag_end_delim . "\s*" . $this->tag_end_delim . "\s*\n/m";
		if (empty($this->filecontents)) {
			$this->filecontents = $this->_r_getfile($this->filename);
		}
		if ($add_outer) {
			$this->_add_outer_block();
		}
		$this->blocks = $this->_maketree($this->filecontents, '');
		$this->filevar_parent = $this->_store_filevar_parents($this->blocks);
		$this->scan_globals();
	}
	function assign ($name, $val = '') {
		if (is_array($name)) {
			foreach ($name as $k => $v) {
				$this->vars[$k] = $v;
			}
		} else {
			$this->vars[$name] = $val;
		}
	}

	function assign_file ($name, $val = '') {
		if (is_array($name)) {
			foreach ($name as $k => $v) {
				$this->_assign_file_sub($k, $v);
			}
		} else {
			$this->_assign_file_sub($name, $val);
		}
	}

	function parse ($bname) {
		if (isset($this->preparsed_blocks[$bname])) {
			$copy = $this->preparsed_blocks[$bname];
		} elseif (isset($this->blocks[$bname])) {
			$copy = $this->blocks[$bname];
		} elseif ($this->_ignore_missing_blocks) {
			$this->_set_error("parse: blockname [$bname] does not exist");
			return;
		} else {
			$this->_set_error("parse: blockname [$bname] does not exist");
		}
		if (!isset($copy)) {
			die('Block: ' . $bname);
		}
		$copy = preg_replace($this->filevar_delim_nl, '', $copy);
		$var_array = array();
		preg_match_all("/" . $this->tag_start_delim . "([A-Za-z0-9\._]+? ?#?.*?)" . $this->tag_end_delim. "/", $copy, $var_array);
		$var_array = $var_array[1];
		foreach ($var_array as $k => $v) {
			$any_comments = explode('#', $v);
			$v = rtrim($any_comments[0]);
			if (sizeof($any_comments) > 1) {
				$comments = $any_comments[1];
			} else {
				$comments = '';
			}
			$sub = explode('.', $v);
			if ($sub[0] == '_BLOCK_') {
				unset($sub[0]);
				$bname2 = implode('.', $sub);
				$var = isset($this->parsed_blocks[$bname2]) ? $this->parsed_blocks[$bname2] : null;
				$nul = (!isset($this->_null_block[$bname2])) ? $this->_null_block[''] : $this->_null_block[$bname2];
				if ($var == '') {
					if ($nul == '') {
						$copy = preg_replace("/" . $this->tag_start_delim . $v . $this->tag_end_delim . "/m", '', $copy);
					} else {
						$copy = preg_replace("/" . $this->tag_start_delim . $v . $this->tag_end_delim . "/", "$nul", $copy);
					}
				} else {
					$var = trim($var);
					$var = str_replace('\\', '\\\\', $var);
					$var = str_replace('$', '\\$', $var);
					$var = str_replace('\\|', '|', $var);
					$copy = preg_replace("|" . $this->tag_start_delim . $v . $this->tag_end_delim . "|", "$var", $copy);
				}
			} else {
				$var = $this->vars;
				foreach ($sub as $v1) {
					if (!isset($var[$v1]) || (!is_array($var[$v1]) && strlen($var[$v1]) == 0)) {
						if (defined($v1)) {
							$var[$v1] = constant($v1);
						} else {
							$var[$v1] = null;
						}
					}
					$var = $var[$v1];
				}
				$nul = (!isset($this->_null_string[$v])) ? ($this->_null_string[""]) : ($this->_null_string[$v]);
				$var = (!isset($var)) ? $nul : $var;
				if ($var == '') {
					$copy=preg_replace("|\s*" . $this->tag_start_delim . $v . " ?#?" . $comments . $this->tag_end_delim . "\s*\n|m", '', $copy);
				}
				$var = trim($var);
				$var = str_replace('\\', '\\\\', $var);
				$var = str_replace('$', '\\$', $var);
				$var = str_replace('\\|', '|', $var);
				$copy=preg_replace("|" . $this->tag_start_delim . $v . " ?#?" . $comments . $this->tag_end_delim . "|", "$var", $copy);
			}
		}
		if (isset($this->parsed_blocks[$bname])) {
			$this->parsed_blocks[$bname] .= $copy;
		} else {
			$this->parsed_blocks[$bname] = $copy;
		}
		/* reset sub-blocks */
		if ($this->_autoreset && (!empty($this->sub_blocks[$bname]))) {
			reset($this->sub_blocks[$bname]);
			foreach ($this->sub_blocks[$bname] as $k => $v) {
				$this->reset($v);
			}
		}
	}
	function rparse ($bname) {
		if (!empty($this->sub_blocks[$bname])) {
			reset($this->sub_blocks[$bname]);
			foreach ($this->sub_blocks[$bname] as $k => $v) {
				if (!empty($v)) {
					$this->rparse($v);
				}
			}
		}
		$this->parse($bname);
	}
	function insert_loop ($bname, $var, $value = '') {
		$this->assign($var, $value);
		$this->parse($bname);
	}
	function array_loop ($bname, $var, &$values) {
		if (is_array($values)) {
			foreach($values as $v) {
				$this->assign($var, $v);
				$this->parse($bname);
			}
		}
	}
	function text ($bname = '') {
		$text = '';
		$bname = !empty($bname) ? $bname : $this->mainblock;
		$text .= isset($this->parsed_blocks[$bname]) ? $this->parsed_blocks[$bname] : $this->get_error();
		return $text;
	}
	function out ($bname) {
		$out = $this->text($bname);
		echo $out;
	}
	function out_file ($bname, $fname) {
		if (!empty($bname) && !empty($fname) && is_writeable($fname)) {
			$fp = fopen($fname, 'w');
			fwrite($fp, $this->text($bname));
			fclose($fp);
		}
	}
	function reset ($bname) {
		$this->parsed_blocks[$bname] = '';
	}
	function parsed ($bname) {
		return (!empty($this->parsed_blocks[$bname]));
	}
	function SetNullString ($str, $varname = '') {
		$this->_null_string[$varname] = $str;
	}
	function SetNullBlock ($str, $bname = '') {
		$this->_null_block[$bname] = $str;
	}
	function set_autoreset () {
		$this->_autoreset = true;
	}
	function clear_autoreset () {
		$this->_autoreset = false;
	}
	function scan_globals () {
		reset($GLOBALS);
		foreach ($GLOBALS as $k => $v) {
			$GLOB[$k] = $v;
		}
		$this->assign('PHP', $GLOB);
	}
	function get_error () {
		$retval = false;
		if ($this->_error != '') {
			switch ($this->output_type) {
				case 'HTML':
				case 'html':
				$retval = '<b>[XTemplate]</b><ul>' . nl2br(str_replace('* ', '<li>', str_replace(" *\n", "</li>\n", $this->_error))) . '</ul>';
				break;
				default:
				$retval = '[XTemplate] ' . str_replace(' *\n', "\n", $this->_error);
				break;
			}
		}
		return $retval;
	}
	function _maketree ($con, $parentblock='') {
		$blocks = array();
		$con2 = explode($this->block_start_delim, $con);
		if (!empty($parentblock)) {
			$block_names = explode('.', $parentblock);
			$level = sizeof($block_names);
		} else {
			$block_names = array();
			$level = 0;
		}
		foreach($con2 as $k => $v) {
			$patt = "($this->block_start_word|$this->block_end_word)\s*(\w+) ?#?.*?\s*$this->block_end_delim(.*)";
			$res = array();
			if (preg_match_all("/$patt/ims", $v, $res, PREG_SET_ORDER)) {
				$block_word	= $res[0][1];
				$block_name	= $res[0][2];
				$content	= $res[0][3];
				if (strtoupper($block_word) == $this->block_start_word) {
					$parent_name = implode('.', $block_names);
					$block_names[++$level] = $block_name;
					$cur_block_name=implode('.', $block_names);
					$this->block_parse_order[] = $cur_block_name;
					$blocks[$cur_block_name] = isset($blocks[$cur_block_name]) ? $blocks[$cur_block_name] . $content : $content;
					$blocks[$parent_name] .= str_replace('\\', '', $this->tag_start_delim) . '_BLOCK_.' . $cur_block_name . str_replace('\\', '', $this->tag_end_delim);
					$this->sub_blocks[$parent_name][] = $cur_block_name;
					$this->sub_blocks[$cur_block_name][] = '';
				} else if (strtoupper($block_word) == $this->block_end_word) {
					unset($block_names[$level--]);
					$parent_name = implode('.', $block_names);
					$blocks[$parent_name] .= $res[0][3];
				}
			} else {
				$tmp = implode('.', $block_names);
				if ($k) {
					$blocks[$tmp] .= $this->block_start_delim;
				}
				$blocks[$tmp] = isset($blocks[$tmp]) ? $blocks[$tmp] . $v : $v;
			}
		}
		return $blocks;
	}
	function _assign_file_sub ($name, $val) {
		if (isset($this->filevar_parent[$name])) {
			if ($val != '') {
				$val = $this->_r_getfile($val);
				foreach($this->filevar_parent[$name] as $parent) {
					if (isset($this->preparsed_blocks[$parent]) && !isset($this->filevars[$name])) {
						$copy = $this->preparsed_blocks[$parent];
					} elseif (isset($this->blocks[$parent])) {
						$copy = $this->blocks[$parent];
					}
					$res = array();
					preg_match_all($this->filevar_delim, $copy, $res, PREG_SET_ORDER);
					if (is_array($res) && isset($res[0])) {
						foreach ($res[0] as $v) {
							$copy = preg_replace("/" . preg_quote($v) . "/", "$val", $copy);
							$this->preparsed_blocks = array_merge($this->preparsed_blocks, $this->_maketree($copy, $parent));
							$this->filevar_parent = array_merge($this->filevar_parent, $this->_store_filevar_parents($this->preparsed_blocks));
						}
					}
				}
			}
		}
		$this->filevars[$name] = $val;
	}
	function _store_filevar_parents ($blocks){
		$parents = array();
		foreach ($blocks as $bname => $con) {
			$res = array();
			preg_match_all($this->filevar_delim, $con, $res);
			foreach ($res[1] as $k => $v) {
				$parents[$v][] = $bname;
			}
		}
		return $parents;
	}
	function _set_error ($str)    {
		$this->_error .= '* ' . $str . " *\n";
	}
	function _getfile ($file) {
		if (!isset($file)) {
			$this->_set_error('!isset file name!' . $file);
			return '';
		}
		if (isset($this->files)) {
			if (isset($this->files[$file])) {
				$file = $this->files[$file];
			}
		}
		if (!empty($this->tpldir)) {
			$file = $this->tpldir. '/' . $file;
		}
		if (isset($this->filecache[$file])) {
			$file_text=$this->filecache[$file];
		} else {
			if (is_file($file)) {
				if (!($fh = fopen($file, 'r'))) {
					$this->_set_error('Cannot open file: ' . $file);
					return '';
				}
				$file_text = fread($fh,filesize($file));
				fclose($fh);
			} else {
				$this->_set_error("[" . realpath($file) . "] ($file) does not exist");
				$file_text = "<b>__XTemplate fatal error: file [$file] does not exist__</b>";
			}
			$this->filecache[$file] = $file_text;
		}
		return $file_text;
	}
	function _r_getfile ($file) {
		$text = $this->_getfile($file);
		$res = array();
		while (preg_match($this->file_delim,$text,$res)) {
			$text2 = $this->_getfile($res[1]);
			$text = preg_replace("'".preg_quote($res[0])."'",$text2,$text);
		}
		return $text;
	}
	function _add_outer_block () {
		$before = $this->block_start_delim . $this->block_start_word . ' ' . $this->mainblock . ' ' . $this->block_end_delim;
		$after = $this->block_start_delim . $this->block_end_word . ' ' . $this->mainblock . ' ' . $this->block_end_delim;
		$this->filecontents = $before . "\n" . $this->filecontents . "\n" . $after;
	}
	function _pre_var_dump () {
		echo '<pre>';
		var_dump(func_get_args());
		echo '</pre>';
	}
/* XTemplate extensions for vpsAdmin for lazy programmers */
	/**
	  * Set title of the page
	  * @param $title - title of page
	*/
	function title ($title) {
	    $this->assign('TITLE', $title);
	}
	function title2 ($title2) {
	    $this->assign('S_TITLE', $title2);
	    $this->parse('main.s_title');
	}
	function title3 ($title3) {
	    $this->assign('S_S_TITLE', $title3);
	    $this->parse('main.s_s_title');
	}
	/**
	  * @param $code - lang code
	  * @param $icon - lang icon code
	  * @param $lang - language name
	  * @param $class - class of image
	**/
	function lang_add($code, $icon, $lang, $class) {
	    $this->assign('LANG_CODE', $code);
	    $this->assign('LANG_ICON', $icon);
	    $this->assign('LANG_IMG_CLASS', $class);
	    $this->assign('LANG_LANG', $lang);
	    $this->parse('main.langitem');
	}
	/**
	  * Parse out the login box
	  * @param $logged - is user logged in?
	  * @param $user_name - if so, what is is nick?
	  */
	function logbox ($logged = false, $user_name = 'none') {
		if ($logged) {
			$this->assign('USER_NAME', $user_name);
			$this->parse('main.loggedbox');
			}
		  else
			$this->parse('main.logbox');  
	}
	/**
	  * Add item to menu
	  * @param $title - title of the perex
	  * @param $link - URL link of item
	  * @param $active - Is user currently here?
	  * @param $is_last - Is this item last in the menu?
	  */
	function menu_add ($title = 'Titulek', $link = '#', $active = false, $is_last = false) {
		$this->assign('MENU_LINK', $link);
		$this->assign('MENU_TITLE', $title);
		$this->assign('MENU_ACTIVE', ($active) ? 'id="nav-active"' : '');
		$this->assign('MENU_LAST', ($is_last) ? 'class="last"' : '');
		$this->parse('main.menu_item');
	}
	/**
	  * Add perex to page
	  * @param $title - title of the perex
	  * @param $content - HTML content of the perex
	  */
	function perex ($title, $content) {
		$this->assign('PEREX_TITLE', $title);
		$this->assign('PEREX_CONTENT', $content);
		$this->parse('main.perex');
	}
	/**
	  * Add perex - command output to page
	  * @param $title - title
	  * @param $output - array of lines
	  */
	function perex_cmd_output ($title, $output) {
		$this->assign('PEREX_TITLE', $title);
		if (is_array($output))
		foreach ($output as $line) $content .= $line.' <br />';
		$this->assign('PEREX_CONTENT', $content);
		$this->parse('main.perex');
	}
	/**
	  * Add link to sidebar
	  * @param $title - link title
	  * @param $link - link URL
	  */
	function sbar_add ($title, $link) {
		$this->assign('SBI_TITLE' ,$title);
		$this->assign('SBI_LINK', $link);
		$this->parse('main.sidebar.sb_item');
	}
	/**
	  * Parse out the sidebar
	  * @param $title - tile for the sidebar
	  */
	function sbar_out ($title) {
		$this->assign('SB_TITLE',$title);
		$this->parse('main.sidebar');
	}
	/**
	  * Add table category to table header
	  * @param $name - HTML content of the category header
	  */
	function table_add_category ($name) {
		$this->assign('TABLE_CATEGORY', $name);
		$this->parse('main.table.category');
	}
	/**
	  * Add table cell
	  * @param $content - HTML content of the cell
	  * @param $td_back_color - HTML background color
	  * @param $toright - Right side text align
	  */
	function table_td ($content, $td_back_color=false, $toright = false, $colspan = '1', $rowspan = '1') {
		$tdstyle = 'style="';
		if ($td_back_color) $tdstyle .= 'background:'.$td_back_color.';';
		if ($toright) $tdstyle .= 'text-align:right;';
		$tdstyle .='" colspan="'.$colspan.'" rowspan="'.$rowspan.'"';
		$this->assign('TDSTYLE',$tdstyle);
		$this->assign('TABLE_TD',$content);
		$this->parse('main.table.tr.td');
		$this->assign('TDSTYLE','');
	}
	/**
	  * Parse out the table row
	  * @param $tr_back_color - background color of 
	  * @param $tr_class - CSS class of row
	  * @param $tr_class_hover - CSS class when mouse over
	  */
	function table_tr ($tr_back_color=false, $tr_class=false, $tr_class_hover=false) {
		// UPDATED BY toms
		if ($tr_back_color) 
			$this->assign('TRSTYLE','style="background:'.$tr_back_color.'"');
		elseif ($tr_class)
			$this->assign('TRSTYLE', 'class="'.$tr_class.'"');
		else
			$this->assign('TRSTYLE', '');

		if ($tr_class)
			$this->assign('TRCLASS', $tr_class);
		else 
			$this->assign('TRCLASS', 'none');

		if ($tr_class_hover)
			$this->assign('TRCLASS_HOVER', $tr_class_hover);
		else
			$this->assign('TRCLASS_HOVER', 'bg');

		$this->parse('main.table.tr');
	}
	/**
	  * Parse out the table
	  */
	function table_out() {
		$this->parse('main.table');
	}
	/**
	  * Add form to the page, begin with this
	  * @param $action - action URL
	  * @param $method - GET or POST
	  */
	function form_create($action = '?page=', $method = 'post') {
		$this->assign('TABLE_FORM_BEGIN','<form action="'.$action.'" method="'.$method.'">');
	}
	/**
	  * Add input to form
	  * @param $label - label of textarea
	  * @param $type - type of HTML input
	  * @param $size - size of element
	  * @param $name - $_RESULT[name]
	  * @param $value - default value
	  * @param $hint - helping hint
	  */
	function form_add_input($label = 'popisek', $type = 'text', $size = '30', $name = 'input_fromgen', $value = '', $hint = '', $nchar = 0) {
    $uid = uniqid();

		$this->table_td($label);
    
    $maxlength = '';

    if ($nchar!=0) {
      $code   = moo_inputremaining("input".$uid, "inputh".$uid, $nchar, $uid);
      if ($nchar>0) {
        $output = sprintf(_("<span id='inputh%s'>%d</span> chars remaining; "), $uid, $nchar-strlen($value));
        $maxlength = 'maxlength="'.$nchar.'"';
      }
      else
        $output = sprintf(_("<span id='inputh%s'>%d</span> chars needed; "), $uid, ($nchar-strlen($value))*-1);
      $hint = $code.$output.$hint;
    }

    $this->table_td('<input type="'.$type.'" size="'.$size.'" name="'.$name.'" id="input'. $uid .'" value="'.$value.'" '.$maxlength.' />');

		if ($hint != '') {
      $this->table_td( $hint );
    }
		$this->table_tr();
	}
	/**
	  * Add select (combobox) to form
	  * @param $label - label of textarea
	  * @param $name - $_RESULT[name]
	  * @param $options - array of options, $option[option_name] = "Option Label"
	  * @param $selected_value - default selected value
	  * @param $hint - helping hint
	  */
	function form_add_select($label = 'popisek', $name = 'select_fromgen', $options, $selected_value = '', $hint = '') {
		$this->table_td($label);
		$code = ('<select  name="'.$name.'" id="input">');
		if ($options)
		foreach ($options as $key=>$value) {
		if ($selected_value == $key)
			$code .= '<option selected="selected" value="'.$key.'" title="">'.$value.'</option> \n';
			else $code .= '<option value="'.$key.'" title="">'.$value.'</option> \n';
		}
		$code .= ('</select>');
		$this->table_td($code);
		if ($hint != '') $this->table_td($hint);
		$this->table_tr();
	}
	/**
	  * Add textarea to form
	  * @param $label - label of textarea
	  * @param $cols - number of columns
	  * @param $rows - number of rows
	  * @param $name - $_RESULT[name]
	  * @param $value - default value
	  * @param $hint - helping hint
	  */
	function form_add_textarea($label = 'popisek', $cols = 10, $rows = 4, $name = 'textarea_formgen', $value = '', $hint = '') {
		$this->table_td($label);
		$this->table_td('<textarea name="'.$name.'" cols="'.$cols.'" rows="'.$rows.'" id="input">'.$value.'</textarea>');
		if ($hint != '') $this->table_td($hint);
		$this->table_tr();
	}
	/**
	  * Add checkobox to form
	  * @param $label - label of checkbox
	  * @param $name - $_RESULT[name]
	  * @param $value - value if checked
	  * @param $checked - if it is checked by default
	  * @param $hint - helping hint
	  */
	function form_add_checkbox($label = 'popisek', $name = 'input_fromgen', $value = '', $checked=false, $hint = '') {
		$this->table_td($label);
		$this->table_td('<input type="checkbox" name="'.$name.'" id="input" value="'.$value.'" '.(($checked) ? 'checked':'').' />');
		if ($hint != '') $this->table_td($hint);
		$this->table_tr();
	}

	/**
	  * Parse out the form
	  * @param $submit_label - label of submit button of the form
	  */
	function form_out($submit_label) {
		$this->table_td('');
		$this->table_td('<input type="submit" value=" '.$submit_label.' "  id="button"/>');
		$this->table_tr('');
		$this->assign('TABLE_FORM_END','</form>');
		$this->table_out();
	}
	/**
	  * Add transaction line to rightside shortlog
	  * @param $t_id - transaction ID
	  * @param $t_server - server_id, where was transaction run
	  * @param $t_vps - vps_id, which was transaction for
	  * @param $t_class - background color class (CSS)
	  */
	function transaction($t_id, $t_server, $t_vps, $t_label, $t_class = "pending") {
		$this->assign('T_ID', $t_id);
		$this->assign('T_SERVER', $t_server);
		$this->assign('T_VPS', $t_vps);
		$this->assign('T_LABEL', $t_label);
		$this->assign('T_CLASS', $t_class);
		switch ($t_class) {
		    case "pending":
			$this->assign('T_ICO', '<img src="template/icons/transact_pending.gif"> ');
			break;
		    case "ok":
			$this->assign('T_ICO', '<img src="template/icons/transact_ok.png"> ');
			break;
		    default:
			$this->assign('T_ICO', '<img src="template/icons/transact_fail.png"> ');
			break;
		}
		$this->parse('main.transactions.item');
	}
	/**
	  * Parse out rightside transact shortlog
	  */
	function transactions_out() {
		$this->parse('main.transactions');
	}
	/**
	  * @param $title - helpbox title
	  * @param $content - helpbox content, must not have \<br\>'s
	  */
	function helpbox($title, $content) {
		$this->assign('HELPBOX_TITLE', $title);
		$this->assign('HELPBOX_CONTENT', nl2br($content));
		$this->parse('main.helpbox');
	}
	/**
	  * @param $data - HTML code, which will be added above the table
	  */
	function table_begin($data) {
	  $this->assign('TABLE_FORM_BEGIN', $data);
	}
	/**
	  * @param $data - HTML code, which will be added below the table
	  */
	function table_end($data) {
	  $this->assign('TABLE_FORM_END', $data);
	}
	/**
	  * @param $content - HTML content of adminbox
	  */
	function adminbox($content) {
	    $this->assign('ADMINBOX_CONTENT', $content);
	    $this->parse('main.adminbox');
	}
	/**
	  * @param $url - Where to redirect
	  * @param $delay - Delay in msecs 
	  */
	function delayed_redirect($url, $delay = 1500) {
	$script ='
		    <script language="JavaScript">
			function redirect () {
			    setTimeout("top.location.href = \'' . $url . '\'", ' . $delay . ');
			}
		    </script>
		';
	$this->assign('SCRIPT', $script);
	$this->assign('SCRIPT_BODY', 'onload="redirect()"');
	}
}
/**
  * pages are indexed from 0, however they are displayed from 1, therefore there are some cases when -1 had to be added
  * @param $current integer - current page number 
  * @param $pages   integer - number of all possible pages
  * @param $offset  integer - number of pages displayed left or right from current page
  * @param $display boolean - default false - returns HTML code, true - prints the code on STDO and also returns it;
  * 
  * @return String - HTML code for the listing 
  */
function gen_pages_listing($current, $pages, $offset, $display=false)
{
  $out = '<div class="page_listing">';

  $begin = 0;
  if ($current>$offset) {
    $begin = $current-$offset;
    $out .= '<a href="?page=transactions&amp;page_number=0">&laquo;</a> ';
  }

  $end = $pages;
  if (($pages-$current-1)>$offset) 
    $end = $current+$offset;
   
  for ($page=$begin; $page<$end; $page++) {
    $out .= '&nbsp;';
    if ($page == $current) 
      $out .= '<b>'.($page+1).'</b>';
    else 
      $out .= '<a href="?page=transactions&amp;page_number='.$page.'">'.($page+1).'</a>';
    $out .= '&nbsp;';
  }
  if (($pages-1-$current)>$offset) 
    $out .= ' <a href="?page=transactions&amp;page_number='.($pages-1).'">&raquo;</a> ';
  
  $out .= '</div>';

  if ($display)
    echo $out;

  return $out;
}
?>
