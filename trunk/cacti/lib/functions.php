<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2003 Ian Berry                                            |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | cacti: a php-based graphing solution                                    |
 +-------------------------------------------------------------------------+
 | Most of this code has been designed, written and is maintained by       |
 | Ian Berry. See about.php for specific developer credit. Any questions   |
 | or comments regarding this code should be directed to:                  |
 | - iberry@raxnet.net                                                     |
 +-------------------------------------------------------------------------+
 | - raXnet - http://www.raxnet.net/                                       |
 +-------------------------------------------------------------------------+
*/

function title_trim($text, $max_length) {
	if (strlen($text) > $max_length) {
		return substr($text, 0, $max_length) . "...";
	}else{
		return $text;
	}
}

function read_graph_config_option($config_name) {
	global $config;
	
	include ($config["include_path"] . "/config_settings.php");
	
	/* users must have cacti user auth turned on to use this */
	if (read_config_option("global_auth") != "on") {
		return $settings_graphs[$config_name]["default"];
	}
	
	if (isset($_SESSION["sess_graph_config_array"])) {
		$graph_config_array = unserialize($_SESSION["sess_graph_config_array"]);
	}
	
	if (!isset($graph_config_array[$config_name])) {
		$graph_config_array[$config_name] = db_fetch_cell("select value from settings_graphs where name='$config_name' and user_id=" . $_SESSION["sess_user_id"]);
		
		if (empty($graph_config_array[$config_name])) {
			$graph_config_array[$config_name] = $settings_graphs[$config_name]["default"];
		}
			
		$_SESSION["sess_graph_config_array"] = serialize($graph_config_array);
	}
	
	return $graph_config_array[$config_name];
}

function read_config_option($config_name) {
	global $config;
	
	include ($config["include_path"] . "/config_settings.php");
	
	if (isset($_SESSION["sess_config_array"])) {
		$config_array = unserialize($_SESSION["sess_config_array"]);
	}
	
	if (!isset($config_array[$config_name])) {
		$config_array[$config_name] = db_fetch_cell("select value from settings where name='$config_name'");
		
		if ((empty($config_array[$config_name])) && (isset($settings[$config_name]["default"]))) {
			$config_array[$config_name] = $settings[$config_name]["default"];
		}
		
		$_SESSION["sess_config_array"] = serialize($config_array);
	}
	
	return $config_array[$config_name];
}

function form_input_validate($field_value, $field_name, $regexp_match, $allow_nulls, $custom_message = 3) {
	/* write current values to the "field_values" array so we can retain them */
	if (isset($_SESSION["sess_field_values"])) {
		$array_field_names = unserialize($_SESSION["sess_field_values"]);
	}
	
	$array_field_names[$field_name] = $field_value;
	$_SESSION["sess_field_values"] = serialize($array_field_names);
	
	if (($allow_nulls == true) && ($field_value == "")) {
		return $field_value;
	}
	
	/* php 4.2+ complains about empty regexps */
	if (empty($regexp_match)) { $regexp_match = ".*"; }
	
	if ((!ereg($regexp_match, $field_value) || (($allow_nulls == false) && ($field_value == "")))) {
		raise_message($custom_message);
		
		if (isset($_SESSION["sess_error_fields"])) {
			$array_error_fields = unserialize($_SESSION["sess_error_fields"]);
		}
		
		$array_error_fields[$field_name] = $field_name;
		$_SESSION["sess_error_fields"] = serialize($array_error_fields);
	}else{
		if (isset($_SESSION["sess_error_fields"])) {
			$array_error_fields = unserialize($_SESSION["sess_error_fields"]);
		}
		
		$array_field_names[$field_name] = $field_value;
		$_SESSION["sess_field_values"] = serialize($array_field_names);
	}
	
	return $field_value;
}

function is_error_message() {
	global $config;
	
	include($config["include_path"] . "/config_arrays.php");
	
	if (isset($_SESSION["sess_messages"])) {
		$array_messages = unserialize($_SESSION["sess_messages"]);
		
		if (is_array($array_messages)) {
			foreach (array_keys($array_messages) as $current_message_id) {
				if ($messages[$current_message_id]["type"] == "error") { return true; }
			}
		}
	}
	
	return false;
}

function raise_message($message_id) {
	if (isset($_SESSION["sess_messages"])) {
		$array_messages = unserialize($_SESSION["sess_messages"]);
	}
	
	$array_messages[$message_id] = $message_id;
	$_SESSION["sess_messages"] = serialize($array_messages);
}

function display_output_messages() {
	global $config;
	
	include($config["include_path"] . "/config_arrays.php");
	include_once($config["include_path"] . "/form.php");
	
	if (isset($_SESSION["sess_messages"])) {
		$error_message = is_error_message();
		
		$array_messages = unserialize($_SESSION["sess_messages"]);
		
		if (is_array($array_messages)) {
			foreach (array_keys($array_messages) as $current_message_id) {
				eval ('$message = "' . $messages[$current_message_id]["message"] . '";');
				
				switch ($messages[$current_message_id]["type"]) {
				case 'info':
					if ($error_message == false) {
						print "<table align='center' width='98%' style='background-color: #ffffff; border: 1px solid #bbbbbb;'>";
						print "<tr><td bgcolor='#f5f5f5'><p class='textInfo'>$message</p></td></tr>";
						print "</table><br>";
						
						/* we don't need these if there are no error messages */
						kill_session_var("sess_field_values");
					}
					break;
				case 'error':
					print "<table align='center' width='98%' style='background-color: #ffffff; border: 1px solid #ff0000;'>";
					print "<tr><td bgcolor='#f5f5f5'><p class='textError'>Error: $message</p></td></tr>";
					print "</table><br>";
					break;
				}
			}
		}
	}
	
	kill_session_var("sess_messages");
}

function clear_messages() {
	kill_session_var("sess_messages");
}

function kill_session_var($var_name) {
	/* register_global = off: reset local settings cache so the user sees the new settings */
	session_unregister($var_name);
	
	/* register_global = on: reset local settings cache so the user sees the new settings */
	unset($_SESSION[$var_name]);
}

function array_rekey($array, $key, $key_value) {
	$ret_array = array();
	
	if (sizeof($array) > 0) {
	foreach ($array as $item) {
		$item_key = $item[$key];
		
		$ret_array[$item_key] = $item[$key_value];
	}
	}
	
	return $ret_array;
}

function draw_menu() {
	global $colors, $config;
	
	include ($config["include_path"] . "/config_arrays.php");
	
	print "<tr><td width='100%'><table cellpadding='3' cellspacing='0' border='0' width='100%'>\n";
	
	foreach (array_keys($menu) as $header) {
		print "<tr><td class='textMenuHeader'>$header</td></tr>\n";
		if (sizeof($menu[$header]) > 0) {
			foreach (array_keys($menu[$header]) as $url) {
				if (basename($_SERVER["PHP_SELF"]) == basename($url)) {
					print "<tr><td class='textMenuItemSelected' background='images/menu_line.gif'><a href='$url'>".$menu[$header][$url]."</a></td></tr>\n";
				}else{
					print "<tr><td class='textMenuItem' background='images/menu_line.gif'><a href='$url'>".$menu[$header][$url]."</a></td></tr>\n";
				}
			}
		}
	}
	
	print '</table></td></tr>';
}

function log_data($string, $output = false) {
	/* fill in the current date for printing in the log */
	$date = date("m/d/Y g:i A");
	
	/* echo the data to the log (append) */
	$fp = fopen(read_config_option("path_webroot") . read_config_option("path_webcacti") . "/log/rrd.log", "a");
	@fwrite($fp, "$date - $string\n");
	fclose($fp);
	
	if ($output == true) {
		print "$string\n";
	}
}

function get_full_script_path($local_data_id) {
	$data_source = db_fetch_row("select
		data_template_data.id,
		data_template_data.data_input_id,
		data_input.type_id,
		data_input.input_string
		from data_template_data,data_input
		where data_template_data.data_input_id=data_input.id
		and data_template_data.local_data_id=$local_data_id");
	
	/* snmp-actions don't have paths */
	if (($data_source["type_id"] == "2") || ($data_source["type_id"] == "3")) {
		return false;
	}
	
	$data = db_fetch_assoc("select
		data_input_fields.data_name,
		data_input_data.value
		from data_input_fields
		left join data_input_data
		on data_input_fields.id=data_input_data.data_input_field_id
		where data_input_fields.data_input_id=" . $data_source["data_input_id"] . "
		and data_input_data.data_template_data_id=" . $data_source["id"] . "
		and data_input_fields.input_output='in'");
	
	$full_path = $data_source["input_string"];
	
	if (sizeof($data) > 0) {
	foreach ($data as $item) {
		$full_path = str_replace("<" . $item["data_name"] . ">", $item["value"], $full_path);
	}
	}
	
	$full_path = str_replace("<path_cacti>", read_config_option("path_webroot") . read_config_option("path_webcacti"), $full_path);
	$full_path = str_replace("<path_snmpget>", read_config_option("path_snmpget"), $full_path);
	$full_path = str_replace("<path_php_binary>", read_config_option("path_php_binary"), $full_path);
	
	/* sometimes a certain input value will not have anything entered... null out these fields
	in the input string so we don't mess up the script */
	$full_path = preg_replace("/(<[A-Za-z0-9_]+>)+/", "", $full_path);
	
	return $full_path;
}

function get_data_source_name($data_template_rrd_id) {    
	if (empty($data_template_rrd_id)) { return ""; }
	
	$data_source = db_fetch_row("select
		data_template_rrd.data_source_name,
		data_template_data.name
		from data_template_rrd,data_template_data
		where data_template_rrd.local_data_id=data_template_data.local_data_id
		and data_template_rrd.id=$data_template_rrd_id");
	
	/* use the cacti ds name by default or the user defined one, if entered */
	if (empty($data_source["data_source_name"])) {
		/* limit input to 19 characters */
		$data_source_name = clean_up_name($data_source["name"]);
		$data_source_name = substr(strtolower($data_source_name),0,(19-strlen($data_template_rrd_id))) . $data_template_rrd_id;
		
		return $data_source_name;
	}else{
		return $data_source["data_source_name"];
	}
}

function get_data_source_path($local_data_id, $expand_paths) {
    	if (empty($local_data_id)) { return ""; }
    	
    	$data_source = db_fetch_row("select name,data_source_path from data_template_data where local_data_id=$local_data_id");
    	
	if (sizeof($data_source) > 0) {
		if (empty($data_source["data_source_path"])) {
			/* no custom path was specified */
			$data_source_path = generate_data_source_path($local_data_id);
		}else{
			if (!strstr($data_source["data_source_path"], "/")) {
				$data_source_path = "<path_rra>/" . $data_source["data_source_path"];
			}else{
				$data_source_path = $data_source["data_source_path"];
			}
		}
		
		/* whether to show the "actual" path or the <path_rra> variable name (for edit boxes) */
		if ($expand_paths == true) {
			$data_source_path = str_replace("<path_rra>", read_config_option("path_webroot") . read_config_option("path_webcacti") . "/rra", $data_source_path);
		}
		
		return $data_source_path;
	}
}

function stri_replace($find, $replace, $string) {
	$parts = explode(strtolower($find), strtolower($string));
	
	$pos = 0;
	
	foreach ($parts as $key=>$part) {
		$parts[$key] = substr($string, $pos, strlen($part));
		$pos += strlen($part) + strlen($find);
	}
	
	return (join($replace, $parts));
}

function clean_up_name($string) {
	$string = preg_replace("/[\s\.]+/", "_", $string);
	$string = preg_replace("/_{2,}/", "_", $string);
	$string = preg_replace("/[^a-zA-Z0-9_]+/", "", $string);
	
	return $string;
}

function update_data_source_title_cache_from_template($data_template_id) {
	$data = db_fetch_assoc("select local_data_id from data_template_data where data_template_id=$data_template_id and local_data_id>0");
	
	if (sizeof($data) > 0) {
	foreach ($data as $item) {
		update_data_source_title_cache($item["local_data_id"]);
	}
	}
}

function update_data_source_title_cache_from_query($snmp_query_id, $snmp_index) {
	$data = db_fetch_assoc("select id from data_local where snmp_query_id=$snmp_query_id and snmp_index=$snmp_index");
	
	if (sizeof($data) > 0) {
	foreach ($data as $item) {
		update_data_source_title_cache($item["id"]);
	}
	}
}

function update_data_source_title_cache_from_host($host_id) {
	$data = db_fetch_assoc("select id from data_local where host_id=$host_id");
	
	if (sizeof($data) > 0) {
	foreach ($data as $item) {
		update_data_source_title_cache($item["id"]);
	}
	}
}

function update_data_source_title_cache($local_data_id) {
	db_execute("update data_template_data set name_cache='" . get_data_source_title($local_data_id) . "' where local_data_id=$local_data_id");
}

function update_graph_title_cache_from_template($graph_template_id) {
	$graphs = db_fetch_assoc("select local_graph_id from graph_templates_graph where graph_template_id=$graph_template_id and local_graph_id>0");
	
	if (sizeof($graphs) > 0) {
	foreach ($graphs as $item) {
		update_graph_title_cache($item["local_graph_id"]);
	}
	}
}

function update_graph_title_cache_from_query($snmp_query_id, $snmp_index) {
	$graphs = db_fetch_assoc("select id from graph_local where snmp_query_id=$snmp_query_id and snmp_index=$snmp_index");
	
	if (sizeof($graphs) > 0) {
	foreach ($graphs as $item) {
		update_graph_title_cache($item["id"]);
	}
	}
}

function update_graph_title_cache_from_host($host_id) {
	$graphs = db_fetch_assoc("select id from graph_local where host_id=$host_id");
	
	if (sizeof($graphs) > 0) {
	foreach ($graphs as $item) {
		update_graph_title_cache($item["id"]);
	}
	}
}

function update_graph_title_cache($local_graph_id) {
	db_execute("update graph_templates_graph set title_cache='" . get_graph_title($local_graph_id) . "' where local_graph_id=$local_graph_id");
}

function get_data_source_title($local_data_id) {
	$data = db_fetch_row("select
		data_local.host_id,
		data_local.snmp_query_id,
		data_local.snmp_index,
		data_template_data.name
		from data_template_data,data_local
		where data_template_data.local_data_id=data_local.id
		and data_local.id=$local_data_id");
	
	if ((strstr($data["name"], "|")) && (!empty($data["host_id"]))) {
		return expand_title($data["host_id"], $data["snmp_query_id"], $data["snmp_index"], $data["name"]);
	}else{
		return $data["name"];
	}
}

function get_graph_title($local_graph_id) {
	$graph = db_fetch_row("select
		graph_local.host_id,
		graph_local.snmp_query_id,
		graph_local.snmp_index,
		graph_templates_graph.title
		from graph_templates_graph,graph_local
		where graph_templates_graph.local_graph_id=graph_local.id
		and graph_local.id=$local_graph_id");
	
	if ((strstr($graph["title"], "|")) && (!empty($graph["host_id"]))) {
		return expand_title($graph["host_id"], $graph["snmp_query_id"], $graph["snmp_index"], $graph["title"]);
	}else{
		return $graph["title"];
	}
}

function null_out_subsitions($string) {
	return eregi_replace("\|host_(hostname|description|management_ip|snmp_community|snmp_version|snmp_username|snmp_password)\|( - )?", "", $string);
}

function expand_title($host_id, $snmp_query_id, $snmp_index, $title) {
	if ((strstr($title, "|")) && (!empty($host_id))) {
		if (($snmp_query_id != "0") && ($snmp_index != "")) {
			return subsitute_snmp_query_data(null_out_subsitions(subsitute_host_data($title, "|", "|", $host_id)), "|", "|", $host_id, $snmp_query_id, $snmp_index);
		}else{
			return null_out_subsitions(subsitute_host_data($title, "|", "|", $host_id));
		}
	}else{
		return null_out_subsitions($title);
	}
}

function subsitute_data_query_path($path) {
	$path = str_replace("|path_cacti|", read_config_option("path_webroot") . read_config_option("path_webcacti"), $path);
	$path = str_replace("|path_php_binary|", read_config_option("path_php_binary"), $path);
	
	return $path;
}

function subsitute_host_data($string, $l_escape_string, $r_escape_string, $host_id) {
	if (isset($_SESSION["sess_host_cache_array"])) {
		$host_cache_array = unserialize($_SESSION["sess_host_cache_array"]);
	}
	
	if (!isset($host_cache_array[$host_id])) {
		$host = db_fetch_row("select description,hostname,management_ip,snmp_community,snmp_version,snmp_username,snmp_password from host where id=$host_id");
		$host_cache_array[$host_id] = $host;
		$_SESSION["sess_host_cache_array"] = serialize($host_cache_array);
	}
	
	$string = str_replace($l_escape_string . "host_hostname" . $r_escape_string, $host_cache_array[$host_id]["hostname"], $string);
	$string = str_replace($l_escape_string . "host_description" . $r_escape_string, $host_cache_array[$host_id]["description"], $string);
	$string = str_replace($l_escape_string . "host_management_ip" . $r_escape_string, $host_cache_array[$host_id]["management_ip"], $string);
	$string = str_replace($l_escape_string . "host_snmp_community" . $r_escape_string, $host_cache_array[$host_id]["snmp_community"], $string);
	$string = str_replace($l_escape_string . "host_snmp_version" . $r_escape_string, $host_cache_array[$host_id]["snmp_version"], $string);
	$string = str_replace($l_escape_string . "host_snmp_username" . $r_escape_string, $host_cache_array[$host_id]["snmp_username"], $string);
	$string = str_replace($l_escape_string . "host_snmp_password" . $r_escape_string, $host_cache_array[$host_id]["snmp_password"], $string);
	
	return $string;
}

function subsitute_snmp_query_data($string, $l_escape_string, $r_escape_string, $host_id, $snmp_query_id, $snmp_index) {
	$snmp_cache_data = db_fetch_assoc("select field_name,field_value from host_snmp_cache where host_id=$host_id and snmp_query_id=$snmp_query_id and snmp_index='$snmp_index'");
	
	if (sizeof($snmp_cache_data) > 0) {
	foreach ($snmp_cache_data as $data) {
		if ($data["field_value"] != "") {
			$string = stri_replace($l_escape_string . "query_" . $data["field_name"] . $r_escape_string, substr($data["field_value"],0,read_config_option("max_data_query_field_length")), $string);
		}
	}
	}
	
	return $string;
}

function data_query_index($index_type, $index_value, $host_id) {
	return db_fetch_row("select
		host_snmp_cache.snmp_query_id,
		host_snmp_cache.snmp_index
		from host_snmp_cache
		where host_snmp_cache.field_name='$index_type'
		and host_snmp_cache.field_value='$index_value'
		and host_snmp_cache.host_id=$host_id");
}

function data_query_field_list($data_template_data_id) {
	$field = db_fetch_assoc("select
		data_input_fields.type_code,
		data_input_data.value
		from data_input_fields,data_input_data
		where data_input_fields.id=data_input_data.data_input_field_id
		and data_input_data.data_template_data_id=$data_template_data_id
		and (data_input_fields.type_code='index_type' or data_input_fields.type_code='index_value' or data_input_fields.type_code='output_type')");
	$field = array_rekey($field, "type_code", "value");
	
	if ((!isset($field["index_type"])) || (!isset($field["index_value"])) || (!isset($field["output_type"]))) {
		return 0;
	}else{
		return $field;
	}
}


function generate_data_source_path($local_data_id) {
	$host_part = ""; $ds_part = "";
	
	/* try any prepend the name with the host description */
	$host_name = db_fetch_cell("select host.description from host,data_local where data_local.host_id=host.id and data_local.id=$local_data_id");
	
	if (!empty($host_name)) {
		$host_part = strtolower(clean_up_name($host_name)) . "_";
	}
	
	/* then try and use the internal DS name to identify it */
	$data_source_rrd_name = db_fetch_cell("select data_source_name from data_template_rrd where local_data_id=$local_data_id order by id");
	
	if (!empty($data_source_rrd_name)) {
		$ds_part = strtolower(clean_up_name($data_source_rrd_name));
	}else{
		$ds_part = "ds";
	}
	
	/* put it all together using the local_data_id at the end */
	$new_path = "<path_rra>/$host_part$ds_part" . "_" . "$local_data_id.rrd";
	
	/* update our changes to the db */
	db_execute("update data_template_data set data_source_path='$new_path' where local_data_id=$local_data_id");
	
	return $new_path;
}

function generate_graph_def_name($graph_item_id) {
	$lookup_table = array("a","b","c","d","e","f","g","h","i","j");
	
	$result = "";
	for($i=0; $i<strlen($graph_item_id); $i++) {
		$current_charcter = $graph_item_id[$i];
		$result .= $lookup_table[$current_charcter];
	}
	
	return $result;
}

function generate_data_input_field_sequences($string, $data_input_id, $inout) {
	global $config;
	
	include ($config["include_path"] . "/config_arrays.php");
	
	if (preg_match_all("/<([_a-zA-Z0-9]+)>/", $string, $matches)) {
		$j = 0;
		for ($i=0; ($i < count($matches[1])); $i++) {
			if (in_array($matches[1][$i], $registered_cacti_names) == false) {
				$j++; db_execute("update data_input_fields set sequence=$j where data_input_id=$data_input_id and input_output='$inout' and data_name='" . $matches[1][$i] . "'");
			}
		}
	}	
}

function move_graph_group($graph_template_item_id, $graph_group_array, $target_id, $direction) {
	$graph_item = db_fetch_row("select local_graph_id,graph_template_id from graph_templates_item where id=$graph_template_item_id");
	
	if (empty($graph_item["local_graph_id"])) {
		$sql_where = "graph_template_id = " . $graph_item["graph_template_id"] . " and local_graph_id=0";
	}else{
		$sql_where = "local_graph_id = " . $graph_item["local_graph_id"];
	}
	
	$graph_items = db_fetch_assoc("select id,sequence from graph_templates_item where $sql_where order by sequence");
	
	/* get a list of parent+children of our target group */
	$target_graph_group_array = get_graph_group($target_id);
	
	/* if this "parent" item has no children, then treat it like a regular gprint */
	if (sizeof($target_graph_group_array) == 0) {
		if ($direction == "next") {
			move_item_down("graph_templates_item", $graph_template_item_id, $sql_where);
		}elseif ($direction == "previous") {
			move_item_up("graph_templates_item", $graph_template_item_id, $sql_where);
		}
		
		return;
	}
	
	/* start the sequence at '1' */
	$sequence_counter = 1;
	
	if (sizeof($graph_items) > 0) {
	foreach ($graph_items as $item) {
		/* check to see if we are at the "target" spot in the loop; if we are, update the sequences and move on */
		if ($target_id == $item["id"]) {
			if ($direction == "next") {
				$group_array1 = $target_graph_group_array;
				$group_array2 = $graph_group_array;
			}elseif ($direction == "previous") {
				$group_array1 = $graph_group_array;
				$group_array2 = $target_graph_group_array;
			}
			
			while (list($sequence,$graph_template_item_id) = each($group_array1)) {
				db_execute("update graph_templates_item set sequence=$sequence_counter where id=$graph_template_item_id");
				
				/* propagate to ALL graphs using this template */
				if (empty($graph_item["local_graph_id"])) {
					db_execute("update graph_templates_item set sequence=$sequence_counter where local_graph_template_item_id=$graph_template_item_id");
				}
				
				$sequence_counter++;
			}
			
			while (list($sequence,$graph_template_item_id) = each($group_array2)) {
				db_execute("update graph_templates_item set sequence=$sequence_counter where id=$graph_template_item_id");
				
				/* propagate to ALL graphs using this template */
				if (empty($graph_item["local_graph_id"])) {
					db_execute("update graph_templates_item set sequence=$sequence_counter where local_graph_template_item_id=$graph_template_item_id");
				}
				
				$sequence_counter++;
			}
		}
		
		/* make sure to "ignore" the items that we handled above */
		if ((!isset($graph_group_array{$item["id"]})) && (!isset($target_graph_group_array{$item["id"]}))) {
			db_execute("update graph_templates_item set sequence=$sequence_counter where id=" . $item["id"]);
			$sequence_counter++;
		}
	}
	}
}

function get_graph_group($graph_template_item_id) {
	global $graph_item_types;
	
	$graph_item = db_fetch_row("select graph_type_id,sequence,local_graph_id,graph_template_id from graph_templates_item where id=$graph_template_item_id");
	
	if (empty($graph_item["local_graph_id"])) {
		$sql_where = "graph_template_id = " . $graph_item["graph_template_id"] . " and local_graph_id=0";
	}else{
		$sql_where = "local_graph_id = " . $graph_item["local_graph_id"];
	}
	
	/* a parent must NOT be the following graph item types */
	if (ereg("(GPRINT|VRULE|HRULE|COMMENT)", $graph_item_types{$graph_item["graph_type_id"]})) {
		return;
	}
	
	$graph_item_children_array = array();
	
	/* put the parent item in the array as well */
	$graph_item_children_array[$graph_template_item_id] = $graph_template_item_id;
	
	$graph_items = db_fetch_assoc("select id,graph_type_id from graph_templates_item where sequence > " . $graph_item["sequence"] . " and $sql_where order by sequence");
	
	if (sizeof($graph_items) > 0) {
	foreach ($graph_items as $item) {
		if ($graph_item_types{$item["graph_type_id"]} == "GPRINT") {
			/* a child must be a GPRINT */
			$graph_item_children_array{$item["id"]} = $item["id"];
		}else{
			/* if not a GPRINT then get out */
			return $graph_item_children_array;
		}
	}
	}
	
	return $graph_item_children_array;
}

function get_graph_parent($graph_template_item_id, $direction) {
	$graph_item = db_fetch_row("select sequence,local_graph_id,graph_template_id from graph_templates_item where id=$graph_template_item_id");
	
	if (empty($graph_item["local_graph_id"])) {
		$sql_where = "graph_template_id = " . $graph_item["graph_template_id"] . " and local_graph_id=0";
	}else{
		$sql_where = "local_graph_id = " . $graph_item["local_graph_id"];
	}
	
	if ($direction == "next") {
		$sql_operator = ">";
		$sql_order = "ASC";
	}elseif ($direction == "previous") {
		$sql_operator = "<";
		$sql_order = "DESC";
	}
	
	$next_parent_id = db_fetch_cell("select id from graph_templates_item where sequence $sql_operator " . $graph_item["sequence"] . " and graph_type_id != 9 and $sql_where order by sequence $sql_order limit 1");
	
	if (empty($next_parent_id)) {
		return 0;
	}else{
		return $next_parent_id;
	}
}

function get_item($tblname, $field, $startid, $lmt_query, $direction) {
	if ($direction == "next") {
		$sql_operator = ">";
		$sql_order = "ASC";
	}elseif ($direction == "previous") {
		$sql_operator = "<";
		$sql_order = "DESC";
	}
	
	$current_sequence = db_fetch_cell("select $field from $tblname where id=$startid");
	$new_item_id = db_fetch_cell("select id from $tblname where $field $sql_operator $current_sequence and $lmt_query order by $field $sql_order limit 1");
	
	if (empty($new_item_id)) {
		return $startid;
	}else{
		return $new_item_id;
	}
}

function get_sequence($id, $field, $table_name, $group_query) {
	if (empty($id)) {
		$data = db_fetch_row("select max($field)+1 as seq from $table_name where $group_query");
		
		if ($data["seq"] == "") {
			return 1;
		}else{
			return $data["seq"];
		}
	}else{
		$data = db_fetch_row("select $field from $table_name where id=$id");
		return $data[$field];
	}
}

function move_item_down($table_name, $current_id, $group_query) {
	$next_item = get_item($table_name, "sequence", $current_id, $group_query, "next");
	
	$sequence = db_fetch_cell("select sequence from $table_name where id=$current_id");
	$sequence_next = db_fetch_cell("select sequence from $table_name where id=$next_item");
	db_execute("update $table_name set sequence=$sequence_next where id=$current_id");
	db_execute("update $table_name set sequence=$sequence where id=$next_item");
}

function move_item_up($table_name, $current_id, $group_query) {
	$last_item = get_item($table_name, "sequence", $current_id, $group_query, "previous");
	
	$sequence = db_fetch_cell("select sequence from $table_name where id=$current_id");
	$sequence_last = db_fetch_cell("select sequence from $table_name where id=$last_item");
	db_execute("update $table_name set sequence=$sequence_last where id=$current_id");
	db_execute("update $table_name set sequence=$sequence where id=$last_item");
}

function exec_into_array($command_line) {
	exec($command_line,$out,$err);
	
	$command_array = array();
	
	for($i=0; list($key, $value) = each($out); $i++) {
		$command_array[$i] = $value;
	}
	
	return $command_array;
}

function get_web_browser() {
	if (stristr($_SERVER["HTTP_USER_AGENT"], "Mozilla") && (!(stristr($_SERVER["HTTP_USER_AGENT"], "compatible")))) {
		return "moz";
	}elseif (stristr($_SERVER["HTTP_USER_AGENT"], "MSIE")) {
		return "ie";
	}else{
		return "other";
	}
}

function hex2bin($data) {
	$len = strlen($data);
	
	for($i=0;$i<$len;$i+=2) {
		$newdata .=  pack("C",hexdec(substr($data,$i,2)));
	}
	
	return $newdata;
}

/* Converts the number of rra records into a time period */
function get_rra_timespan($rra_id) {
	$rra = db_fetch_row("select timespan from rra where id=$rra_id");
	$timespan = -($rra["timespan"]);
	
	return $timespan;
}

function reverse_lines($string) {
	$arr = split("\n", $string);
	
	rsort($arr);
	
	for ($i=0; ($i < 50); $i++) {
		$newstr .= $arr[$i] . "\n";
	}
	
	return $newstr;
}

function get_graph_permissions_sql($policy_graphs, $policy_hosts, $policy_graph_templates) {
	$sql = "";
	$sql_or = "";
	$sql_and = "";
	$sql_policy_or = "";
	$sql_policy_and = "";
	
	if ($policy_graphs == "1") {
		$sql_policy_and .= "$sql_and(user_auth_perms.type != 1 OR user_auth_perms.type is null)";
		$sql_and = " AND ";
		$sql_null = "is null";
	}elseif ($policy_graphs == "2") {
		$sql_policy_or .= "$sql_or(user_auth_perms.type = 1 OR user_auth_perms.type is not null)";
		$sql_or = " OR ";
		$sql_null = "is not null";
	}
	
	if ($policy_hosts == "1") {
		$sql_policy_and .= "$sql_and((user_auth_perms.type != 3) OR (user_auth_perms.type is null))";
		$sql_and = " AND ";
	}elseif ($policy_hosts == "2") {
		$sql_policy_or .= "$sql_or((user_auth_perms.type = 3) OR (user_auth_perms.type is not null))";
		$sql_or = " OR ";
	}
	
	if ($policy_graph_templates == "1") {
		$sql_policy_and .= "$sql_and((user_auth_perms.type != 4) OR (user_auth_perms.type is null))";
		$sql_and = " AND ";
	}elseif ($policy_graph_templates == "2") {
		$sql_policy_or .= "$sql_or((user_auth_perms.type = 4) OR (user_auth_perms.type is not null))";
		$sql_or = " OR ";
	}
	
	$sql_and = "";
	
	if (!empty($sql_policy_or)) {
		$sql_and = "AND ";
		$sql .= $sql_policy_or;
	}
	
	if (!empty($sql_policy_and)) {
		$sql .= "$sql_and$sql_policy_or";
	}
	
	if (empty($sql)) {
		return "";
	}else{
		return "(" . $sql . ")";
	}
}

function is_graph_allowed($local_graph_id) {
	$current_user = db_fetch_row("select policy_graphs,policy_hosts,policy_graph_templates from user_auth where id=" . $_SESSION["sess_user_id"]);
	
	/* get policy information for the sql where clause */
	$sql_where = get_graph_permissions_sql($current_user["policy_graphs"], $current_user["policy_hosts"], $current_user["policy_graph_templates"]);
	
	$graphs = db_fetch_assoc("select
		graph_templates_graph.local_graph_id
		from graph_templates_graph,graph_local
		left join host on host.id=graph_local.host_id
		left join graph_templates on graph_templates.id=graph_local.graph_template_id
		left join user_auth_perms on ((graph_templates_graph.local_graph_id=user_auth_perms.item_id and user_auth_perms.type=1) OR (host.id=user_auth_perms.item_id and user_auth_perms.type=3) OR (graph_templates.id=user_auth_perms.item_id and user_auth_perms.type=4) and user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ")
		where graph_templates_graph.local_graph_id=graph_local.id
		" . (empty($sql_where) ? "" : "and $sql_where") . "
		and graph_templates_graph.local_graph_id=$local_graph_id
		group by graph_templates_graph.local_graph_id");
	
	if (sizeof($graphs) > 0) {
		return true;
	}else{
		return false;
	}
}

function is_tree_allowed($tree_id) {
	$current_user = db_fetch_row("select policy_trees from user_auth where id=" . $_SESSION["sess_user_id"]);
	
	$trees = db_fetch_assoc("select
		user_id
		from user_auth_perms
		where user_id=" . $_SESSION["sess_user_id"] . "
		and type=2
		and item_id=$tree_id");
	
	/* policy == allow AND matches = DENY */
	if ((sizeof($trees) > 0) && ($current_user["policy_trees"] == "1")) {
		return false;
	/* policy == deny AND matches = ALLOW */
	}elseif ((sizeof($trees) > 0) && ($current_user["policy_trees"] == "2")) {
		return true;
	/* policy == allow AND no matches = ALLOW */
	}elseif ((sizeof($trees) == 0) && ($current_user["policy_trees"] == "1")) {
		return true;
	/* policy == deny AND no matches = DENY */
	}elseif ((sizeof($trees) == 0) && ($current_user["policy_trees"] == "2")) {
		return false;
	}
}

function get_graph_tree_array() {
	if (read_config_option("global_auth") == "on") {
		$current_user = db_fetch_row("select policy_trees from user_auth where id=" . $_SESSION["sess_user_id"]);
		
		if ($current_user["policy_trees"] == "1") {
			$sql_where = "where user_auth_perms.user_id is null";
		}elseif ($current_user["policy_trees"] == "2") {
			$sql_where = "where user_auth_perms.user_id is not null";
		}
		
		$tree_list = db_fetch_assoc("select
			graph_tree.id,
			graph_tree.name,
			user_auth_perms.user_id
			from graph_tree
			left join user_auth_perms on (graph_tree.id=user_auth_perms.item_id and user_auth_perms.type=2 and user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ")
			$sql_where
			order by graph_tree.name");
	}else{
		$tree_list = db_fetch_assoc("select * from graph_tree order by name");
	}
	
	return $tree_list;
}

function draw_navigation_text() {
	$nav_level_cache = isset($_SESSION["sess_nav_level_cache"]) ? unserialize($_SESSION["sess_nav_level_cache"]) : array();
	
	$nav = array(
		"graph_view.php:tree" => array("title" => "Tree Mode", "mapping" => "graph_view.php:", "url" => "graph_view.php?action=tree", "level" => "1"),
		"graph_view.php:list" => array("title" => "List Mode", "mapping" => "graph_view.php:", "url" => "graph_view.php?action=list", "level" => "1"),
		"graph_view.php:preview" => array("title" => "Preview Mode", "mapping" => "graph_view.php:", "url" => "graph_view.php?action=preview", "level" => "1"),
		"graph_view.php:" => array("title" => "Graphs", "mapping" => "", "url" => "graph_view.php", "level" => "0"),
		"graph.php:" => array("title" => "", "mapping" => "graph_view.php:,?", "level" => "2"),
		"graph_settings.php:" => array("title" => "Settings", "mapping" => "graph_view.php:", "url" => "graph_settings.php", "level" => "1"),
		"index.php:" => array("title" => "Console", "mapping" => "", "url" => "index.php", "level" => "0"),
		"graphs.php:" => array("title" => "Graph Management", "mapping" => "index.php:", "url" => "graphs.php", "level" => "1"),
		"graphs.php:graph_edit" => array("title" => "(Edit)", "mapping" => "index.php:,graphs.php:", "url" => "", "level" => "2"),
		"graphs.php:graph_diff" => array("title" => "Change Graph Template", "mapping" => "index.php:,graphs.php:,graphs.php:graph_edit", "url" => "", "level" => "3"),
		"graphs.php:actions" => array("title" => "Actions", "mapping" => "index.php:,graphs.php:", "url" => "", "level" => "2"),
		"graphs.php:item_edit" => array("title" => "Graph Items", "mapping" => "index.php:,graphs.php:,graphs.php:graph_edit", "url" => "", "level" => "3"),
		"gprint_presets.php:" => array("title" => "GPRINT Presets", "mapping" => "index.php:", "url" => "gprint_presets.php", "level" => "1"),
		"gprint_presets.php:edit" => array("title" => "(Edit)", "mapping" => "index.php:,gprint_presets.php:", "url" => "", "level" => "2"),
		"gprint_presets.php:remove" => array("title" => "(Remove)", "mapping" => "index.php:,gprint_presets.php:", "url" => "", "level" => "2"),
		"cdef.php:" => array("title" => "CDEF's", "mapping" => "index.php:", "url" => "cdef.php", "level" => "1"),
		"cdef.php:edit" => array("title" => "(Edit)", "mapping" => "index.php:,cdef.php:", "url" => "", "level" => "2"),
		"cdef.php:remove" => array("title" => "(Remove)", "mapping" => "index.php:,cdef.php:", "url" => "", "level" => "2"),
		"cdef.php:item_edit" => array("title" => "CDEF Items", "mapping" => "index.php:,cdef.php:,cdef.php:edit", "url" => "", "level" => "3"),
		"tree.php:" => array("title" => "Graph Trees", "mapping" => "index.php:", "url" => "tree.php", "level" => "1"),
		"tree.php:edit" => array("title" => "(Edit)", "mapping" => "index.php:,tree.php:", "url" => "", "level" => "2"),
		"tree.php:remove" => array("title" => "(Remove)", "mapping" => "index.php:,tree.php:", "url" => "", "level" => "2"),
		"tree.php:item_edit" => array("title" => "Graph Tree Items", "mapping" => "index.php:,tree.php:,tree.php:edit", "url" => "", "level" => "3"),
		"tree.php:item_remove" => array("title" => "(Remove Item)", "mapping" => "index.php:,tree.php:,tree.php:edit", "url" => "", "level" => "3"),
		"color.php:" => array("title" => "Colors", "mapping" => "index.php:", "url" => "color.php", "level" => "1"),
		"color.php:edit" => array("title" => "(Edit)", "mapping" => "index.php:,color.php:", "url" => "", "level" => "2"),
		"graph_templates.php:" => array("title" => "Graph Templates", "mapping" => "index.php:", "url" => "graph_templates.php", "level" => "1"),
		"graph_templates.php:template_edit" => array("title" => "(Edit)", "mapping" => "index.php:,graph_templates.php:", "url" => "", "level" => "2"),
		"graph_templates.php:actions" => array("title" => "Actions", "mapping" => "index.php:,graph_templates.php:", "url" => "", "level" => "2"),
		"graph_templates.php:item_edit" => array("title" => "Graph Template Items", "mapping" => "index.php:,graph_templates.php:,graph_templates.php:template_edit", "url" => "", "level" => "3"),
		"graph_templates.php:input_edit" => array("title" => "Graph Item Inputs", "mapping" => "index.php:,graph_templates.php:,graph_templates.php:template_edit", "url" => "", "level" => "3"),
		"host_templates.php:" => array("title" => "Host Templates", "mapping" => "index.php:", "url" => "host_templates.php", "level" => "1"),
		"host_templates.php:edit" => array("title" => "(Edit)", "mapping" => "host_templates.php:,host_templates.php:", "url" => "", "level" => "2"),
		"host_templates.php:remove" => array("title" => "(Remove)", "mapping" => "host_templates.php:,host_templates.php:", "url" => "", "level" => "2"),
		"data_templates.php:" => array("title" => "Data Templates", "mapping" => "index.php:", "url" => "data_templates.php", "level" => "1"),
		"data_templates.php:template_edit" => array("title" => "(Edit)", "mapping" => "index.php:,data_templates.php:", "url" => "", "level" => "2"),
		"data_templates.php:actions" => array("title" => "Actions", "mapping" => "index.php:,data_templates.php:", "url" => "", "level" => "2"),
		"data_sources.php:" => array("title" => "Data Sources", "mapping" => "index.php:", "url" => "data_sources.php", "level" => "1"),
		"data_sources.php:ds_edit" => array("title" => "(Edit)", "mapping" => "index.php:,data_sources.php:", "url" => "", "level" => "2"),
		"data_sources.php:actions" => array("title" => "Actions", "mapping" => "index.php:,data_sources.php:", "url" => "", "level" => "2"),
		"host.php:" => array("title" => "Polling Hosts", "mapping" => "index.php:", "url" => "host.php", "level" => "1"),
		"host.php:edit" => array("title" => "(Edit)", "mapping" => "index.php:,host.php:", "url" => "", "level" => "2"),
		"host.php:remove" => array("title" => "(Remove)", "mapping" => "index.php:,host.php:", "url" => "", "level" => "2"),
		"host.php:save" => array("title" => "Create Graphs from Data Query", "mapping" => "index.php:,host.php:,host.php:edit", "url" => "", "level" => "3"),
		"rra.php:" => array("title" => "Round Robin Archives", "mapping" => "index.php:", "url" => "rra.php", "level" => "1"),
		"rra.php:edit" => array("title" => "(Edit)", "mapping" => "index.php:,rra.php:", "url" => "", "level" => "2"),
		"rra.php:remove" => array("title" => "(Remove)", "mapping" => "index.php:,rra.php:", "url" => "", "level" => "2"),
		"data_input.php:" => array("title" => "Data Input Methods", "mapping" => "index.php:", "url" => "data_input.php", "level" => "1"),
		"data_input.php:edit" => array("title" => "(Edit)", "mapping" => "index.php:,data_input.php:", "url" => "", "level" => "2"),
		"data_input.php:remove" => array("title" => "(Remove)", "mapping" => "index.php:,data_input.php:", "url" => "", "level" => "2"),
		"data_input.php:field_edit" => array("title" => "Data Input Fields", "mapping" => "index.php:,data_input.php:,data_input.php:edit", "url" => "", "level" => "3"),
		"data_input.php:field_remove" => array("title" => "(Remove Item)", "mapping" => "index.php:,data_input.php:,data_input.php:edit", "url" => "", "level" => "3"),
		"snmp.php:" => array("title" => "Data Queries", "mapping" => "index.php:", "url" => "snmp.php", "level" => "1"),
		"snmp.php:edit" => array("title" => "(Edit)", "mapping" => "index.php:,snmp.php:", "url" => "", "level" => "2"),
		"snmp.php:remove" => array("title" => "(Remove)", "mapping" => "index.php:,snmp.php:", "url" => "", "level" => "2"),
		"snmp.php:item_edit" => array("title" => "Associated Graph Templates", "mapping" => "index.php:,snmp.php:,snmp.php:edit", "url" => "", "level" => "3"),
		"snmp.php:item_remove" => array("title" => "(Remove Item)", "mapping" => "index.php:,snmp.php:,snmp.php:edit", "url" => "", "level" => "3"),
		"utilities.php:" => array("title" => "Utilities", "mapping" => "index.php:", "url" => "utilities.php", "level" => "1"),
		"utilities.php:view_poller_cache" => array("title" => "View Poller Cache", "mapping" => "index.php:,utilities.php:", "url" => "utilities.php", "level" => "2"),
		"utilities.php:view_snmp_cache" => array("title" => "View SNMP Cache", "mapping" => "index.php:,utilities.php:", "url" => "utilities.php", "level" => "2"),
		"utilities.php:clear_poller_cache" => array("title" => "Clear Poller Cache", "mapping" => "index.php:,utilities.php:", "url" => "utilities.php", "level" => "2"),
		"settings.php:" => array("title" => "Cacti Settings", "mapping" => "index.php:", "url" => "settings.php", "level" => "1"),
		"user_admin.php:" => array("title" => "User Management", "mapping" => "index.php:", "url" => "user_admin.php", "level" => "1"),
		"user_admin.php:user_edit" => array("title" => "User Configuration", "mapping" => "index.php:,user_admin.php:", "url" => "", "level" => "2"),
		"user_admin.php:user_remove" => array("title" => "(Remove)", "mapping" => "index.php:,user_admin.php:", "url" => "", "level" => "2"),
		"user_admin.php:graph_perms_edit" => array("title" => "Graph Permissions", "mapping" => "index.php:,user_admin.php:", "url" => "", "level" => "2"),
		"about.php:" => array("title" => "About Cacti", "mapping" => "index.php:", "url" => "about.php", "level" => "1"),
		);
	
	$current_page = basename($_SERVER["PHP_SELF"]);
	$current_action = (isset($_REQUEST["action"]) ? $_REQUEST["action"] : "");
	
	/* find the current page in the big array */
	$current_array = $nav{$current_page . ":" . $current_action};
	$current_mappings = split(",", $current_array["mapping"]);
	$current_nav = "";
	
	/* resolve all mappings to build the navigation string */
	for ($i=0; ($i<count($current_mappings)); $i++) {
		if (empty($current_mappings[$i])) { continue; }
		
		if (!empty($nav_level_cache{$i}["url"])) {
			/* found a match in the url cache for this level */
			$url = $nav_level_cache{$i}["url"];
		}elseif (!empty($current_array["url"])) {
			/* found a default url in the above array */
			$url = $current_array["url"];
		}else{
			/* default to no url */
			$url = "";
		}
		
		if ($current_mappings[$i] == "?") {
			/* '?' tells us to pull title from the cache at this level */
			$current_nav .= (empty($url) ? "" : "<a href='$url'>") . $nav{$nav_level_cache{$i}["id"]}["title"] . (empty($url) ? "" : "</a>") . " -> ";
		}else{
			/* there is no '?' - pull from the above array */
			$current_nav .= (empty($url) ? "" : "<a href='$url'>") . $nav{$current_mappings[$i]}["title"] . (empty($url) ? "" : "</a>") . " -> ";
		}
	}
	
	/* put on the last entry (current) */
	if (empty($current_array["title"])) {
		/* if no title is specified, try to resolve one */
		$current_nav .= resolve_navigation_title($current_page . ":" . $current_action);
	}else{
		/* use the title specified in the above array */
		$current_nav .= $current_array["title"];
	}
	
	/* keep a cache for each level we encounter */
	$nav_level_cache{$current_array["level"]} = array("id" => $current_page . ":" . $current_action, "url" => $_SERVER["REQUEST_URI"]);
	$_SESSION["sess_nav_level_cache"] = serialize($nav_level_cache);
	
	print $current_nav;
}

function resolve_navigation_title($id) {
	switch ($id) {
	case 'graph.php:':
		return get_graph_title($_GET["local_graph_id"]);
		break;
	}
	
	return;
}

?>
