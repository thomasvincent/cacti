<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2012 The Cacti Group                                 |
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
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

/** update_data_source_title_cache_from_template - updates the title cache for all data sources
	that match a given data template
   @param int $data_template_id - (int) the ID of the data template to match */
function update_data_source_title_cache_from_template($data_template_id) {
	$data = db_fetch_assoc("select local_data_id from data_template_data where data_template_id=$data_template_id and local_data_id>0");

	if (sizeof($data) > 0) {
	foreach ($data as $item) {
		update_data_source_title_cache($item["local_data_id"]);
	}
	}
}

/** update_data_source_title_cache_from_query - updates the title cache for all data sources
	that match a given data query/index combination
   @param int $snmp_query_id 	- (int) the ID of the data query to match
   @param int $snmp_index 		- the index within the data query to match */
function update_data_source_title_cache_from_query($snmp_query_id, $snmp_index) {
	$data = db_fetch_assoc("select id from data_local where snmp_query_id=$snmp_query_id and snmp_index='$snmp_index'");

	if (sizeof($data) > 0) {
	foreach ($data as $item) {
		update_data_source_title_cache($item["id"]);
	}
	}
}

/** update_data_source_title_cache_from_device - updates the title cache for all data sources
	that match a given device
   @param int $device_id - (int) the ID of the device to match */
function update_data_source_title_cache_from_device($device_id) {
	$data = db_fetch_assoc("select id from data_local where device_id=$device_id");

	if (sizeof($data) > 0) {
	foreach ($data as $item) {
		update_data_source_title_cache($item["id"]);
	}
	}
}

/** update_data_source_title_cache - updates the title cache for a single data source
   @param int $local_data_id - (int) the ID of the data source to update the title cache for */
function update_data_source_title_cache($local_data_id) {
	db_execute("update data_template_data set name_cache='" . addslashes(get_data_source_title($local_data_id)) . "' where local_data_id=$local_data_id");
}

/** update_graph_title_cache_from_template - updates the title cache for all graphs
	that match a given graph template
   @param int $graph_template_id - (int) the ID of the graph template to match */
function update_graph_title_cache_from_template($graph_template_id) {
	$graphs = db_fetch_assoc("select local_graph_id from graph_templates_graph where graph_template_id=$graph_template_id and local_graph_id>0");

	if (sizeof($graphs) > 0) {
	foreach ($graphs as $item) {
		update_graph_title_cache($item["local_graph_id"]);
	}
	}
}

/** update_graph_title_cache_from_query - updates the title cache for all graphs
	that match a given data query/index combination
   @param int $snmp_query_id - (int) the ID of the data query to match
   @param int $snmp_index - the index within the data query to match */
function update_graph_title_cache_from_query($snmp_query_id, $snmp_index) {
	$graphs = db_fetch_assoc("select id from graph_local where snmp_query_id=$snmp_query_id and snmp_index='$snmp_index'");

	if (sizeof($graphs) > 0) {
	foreach ($graphs as $item) {
		update_graph_title_cache($item["id"]);
	}
	}
}

/** update_graph_title_cache_from_device - updates the title cache for all graphs
	that match a given device
   @param int $device_id - (int) the ID of the device to match */
function update_graph_title_cache_from_device($device_id) {
	$graphs = db_fetch_assoc("select id from graph_local where device_id=$device_id");

	if (sizeof($graphs) > 0) {
	foreach ($graphs as $item) {
		update_graph_title_cache($item["id"]);
	}
	}
}

/** update_graph_title_cache - updates the title cache for a single graph
   @param int $local_graph_id - (int) the ID of the graph to update the title cache for */
function update_graph_title_cache($local_graph_id) {
	db_execute("update graph_templates_graph set title_cache='" . addslashes(get_graph_title($local_graph_id)) . "' where local_graph_id=$local_graph_id");
}

/** null_out_substitutions 	- takes a string and cleans out any device variables that do not have values
   @param string $string 	- the string to clean out unsubstituted variables for
   @return string 			- the cleaned up string */
function null_out_substitutions($string) {
	global $regexps;

	return preg_replace("/\|device_" . VALID_HOST_FIELDS . "\|( - )?/i", "", $string);
}

/** expand_title - takes a string and substitutes all data query variables contained in it or cleans
	them out if no data query is in use
   @param int $device_id 		- (int) the device ID to match
   @param int $snmp_query_id 	- (int) the data query ID to match
   @param int $snmp_index 		- the data query index to match
   @param string $title 		- the original string that contains the data query variables
   @return string 				- the original string with all of the variable substitutions made */
function expand_title($device_id, $snmp_query_id, $snmp_index, $title) {
	if ((strstr($title, "|")) && (!empty($device_id))) {
		if (($snmp_query_id != "0") && ($snmp_index != "")) {
			return substitute_snmp_query_data(null_out_substitutions(substitute_device_data($title, "|", "|", $device_id, false)), $device_id, $snmp_query_id, $snmp_index, read_config_option("max_data_query_field_length"), false);
		}else{
			return null_out_substitutions(substitute_device_data($title, "|", "|", $device_id, false));
		}
	}else{
		return null_out_substitutions($title);
	}
}

/** substitute_script_query_path - takes a string and substitutes all path variables contained in it
   @param string $path 	- the string to make path variable substitutions on
   @return string 		- the original string with all of the variable substitutions made */
function substitute_script_query_path($path) {
	$path = clean_up_path(str_replace("|path_cacti|", CACTI_BASE_PATH, $path));
	$path = clean_up_path(str_replace("|path_php_binary|", cacti_escapeshellcmd(read_config_option("path_php_binary")), $path));
	$path = clean_up_path(str_replace("|path_perl_binary|", cacti_escapeshellcmd(read_config_option("path_perl_binary")), $path));
	$path = clean_up_path(str_replace("|path_sh_binary|", cacti_escapeshellcmd(read_config_option("path_sh_binary")), $path));

	return $path;
}

/** substitute_device_data - takes a string and substitutes all device variables contained in it
   @param string $string 			- the string to make device variable substitutions on
   @param string $l_escape_string 	- the character used to escape each variable on the left side
   @param string $r_escape_string 	- the character used to escape each variable on the right side
   @param int $device_id 			- the device ID to match
   @param bool $quote				- enclose substitutions in quotes
   @return string 					- the original string with all of the variable substitutions made */
function substitute_device_data($string, $l_escape_string, $r_escape_string, $device_id, $quote=true) {
	if (!empty($device_id)) {
		if (!isset($_SESSION["sess_device_cache_array"][$device_id])) {
			$device = db_fetch_row("SELECT * FROM device WHERE id=$device_id");
			if (array_key_exists("device_template_id", $device) && $device["device_template_id"] == 0) {
				$device["template"] = "None";
			} else {
				$device["template"] = db_fetch_cell("SELECT name FROM device_template WHERE id=" . $device["device_template_id"]);
			}
			$_SESSION["sess_device_cache_array"][$device_id] = $device;
		}

		# substitute all given device fields and escape specific shell characters
		foreach ($_SESSION["sess_device_cache_array"][$device_id] as $key => $value) {
			$string = str_replace($l_escape_string . "device_" . $key . $r_escape_string, cacti_escapeshellarg($value, $quote), $string);
		}

		$temp = plugin_hook_function('substitute_device_data', array('string' => $string, 'l_escape_string' => $l_escape_string, 'r_escape_string' => $r_escape_string, 'device_id' => $device_id));
		$string = $temp['string'];
	}

	return $string;
}

/** substitute_snmp_query_data 	- takes a string and substitutes all data query variables contained in it
   @param string $string 		- the original string that contains the data query variables
   @param int $device_id 		- (int) the device ID to match
   @param int $snmp_query_id 	- (int) the data query ID to match
   @param int $snmp_index 		- the data query index to match
   @param int $max_chars 		- the maximum number of characters to substitute
   @return string 				- the original string with all of the variable substitutions made */
function substitute_snmp_query_data($string, $device_id, $snmp_query_id, $snmp_index, $max_chars = 0, $quote=true) {
	$snmp_cache_data = db_fetch_assoc("select field_name,field_value from device_snmp_cache where device_id=$device_id and snmp_query_id=$snmp_query_id and snmp_index='$snmp_index'");

	if (sizeof($snmp_cache_data) > 0) {
		foreach ($snmp_cache_data as $data) {
			if ($data["field_value"] != "") {
				if ($max_chars > 0) {
					$data["field_value"] = substr($data["field_value"], 0, $max_chars);
				}

				$string = stri_replace("|query_" . $data["field_name"] . "|", cacti_escapeshellarg($data["field_value"], $quote), $string);
			}
		}
	}

	return $string;
}

/** substitute_data_input_data - takes a string and substitutes all data input variables contained in it
   @param string $string - the original string that contains the data input variables
   @param int $local_data_id - (int) the local data id to match
   @param int $max_chars - the maximum number of characters to substitute
   @returns int - the original string with all of the variable substitutions made */
function substitute_data_input_data($string, $graph, $local_data_id, $max_chars = 0) {
	if (empty($local_data_id)) {
		if (isset($graph['local_graph_id'])) {
			$local_data_ids = array_rekey(db_fetch_assoc("SELECT DISTINCT local_data_id
				FROM data_template_rrd
				INNER JOIN graph_templates_item
				ON data_template_rrd.id=graph_templates_item.task_item_id
				WHERE local_graph_id=" . $graph["local_graph_id"]), "local_data_id", "local_data_id");

			if (sizeof($local_data_ids)) {
				$data_template_data_id = db_fetch_cell("SELECT id FROM data_template_data WHERE local_data_id IN (" . implode(",", $local_data_ids) . ")");
			}else{
				$data_template_data_id = 0;
			}
		}else{
			$data_template_data_id = 0;
		}
	}else{
		$data_template_data_id = db_fetch_cell("SELECT id FROM data_template_data WHERE local_data_id=$local_data_id");
	}

	if (!empty($data_template_data_id)) {
		$data = db_fetch_assoc("SELECT
			dif.data_name, did.value
			FROM data_input_fields AS dif
			INNER JOIN data_input_data AS did
			ON dif.id=did.data_input_field_id
			WHERE data_template_data_id=$data_template_data_id
			AND input_output='in'");

		if (sizeof($data)) {
			foreach ($data as $item) {
				if ($item["value"] != "") {
					if ($max_chars > 0) {
						$item["value"] = substr($item["field_value"], 0, $max_chars);
					}

					$string = stri_replace("|input_" . $item["data_name"] . "|", $item["value"], $string);
				}
			}
		}
	}

	return $string;
}


function substitute_graph_data($graph_items, $graph, $graph_data_array, $rrdtool_version) {
	require_once(CACTI_LIBRARY_PATH . "/time.php");
	
	$graph_date = date_time_format();

	/* explicit start/end options given */
	if (strpos($graph_items, '|graph_start|') > 0 ||
		strpos($graph_items, '|graph_end|') > 0) {
			
		$start = date($graph_date, time()+$graph["graph_start"]);
		$end = date($graph_date, time()+$graph["graph_end"]);
		
		/* reformat depending on rrdtool version */
		if ($rrdtool_version != RRD_VERSION_1_0) {
			$start = str_replace(":", "\:", $start); 
			$end = str_replace(":", "\:", $end);
		}
		/* and finally replace */
		$graph_items = str_replace('|graph_start|', $start, $graph_items);
		$graph_items = str_replace('|graph_end|', $end, $graph_items);
		 
	}elseif ((isset($graph_data_array["graph_start"])) && (isset($graph_data_array["graph_end"]))) {
		if (($graph_data_array["graph_start"] < 0) && ($graph_data_array["graph_end"] < 0)) {
			$start = date($graph_date, time()+$graph_data_array["graph_start"]);
			$end = date($graph_date, time()+$graph_data_array["graph_end"]);
		} else {
			$start = date($graph_date, $graph_data_array["graph_start"]);
			$end = date($graph_date, $graph_data_array["graph_end"]);
		}
		/* reformat depending on rrdtool version */
		if ($rrdtool_version != RRD_VERSION_1_0) {
			$start = str_replace(":", "\:", $start); 
			$end = str_replace(":", "\:", $end);
		}
		/* and finally replace */
		$graph_items = "COMMENT:\"" . __("From %s To %s", $start, $end) . "\\c\"" . RRD_NL . "COMMENT:\"  \\n\"" . RRD_NL . $graph_items;
	}
	
	return $graph_items;
}
