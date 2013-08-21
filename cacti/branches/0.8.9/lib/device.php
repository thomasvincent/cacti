<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2013 The Cacti Group                                 |
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

/**
 * fetch data via ajax callback
 * - template: fetch template information for given template id
 */
function device_ajax_actions() {
	/* ================= input validation ================= */
	$jaction = sanitize_search_string(get_request_var("jaction"));
	/* ================= input validation ================= */

	switch($jaction) {
	case "template":
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var("template"));
		/* ================= input validation ================= */

		$template = db_fetch_row("SELECT * FROM host_template WHERE id=" . get_request_var("template"));

		echo json_encode($template);

		break;
	}
}

/**
 *  The Save Functions
 */
function device_add_gt() {
	if (!empty($_POST["graph_template_id"])) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post("id"));
		input_validate_input_number(get_request_var_post("graph_template_id"));
		/* ==================================================== */

		db_execute("replace into host_graph (host_id,graph_template_id) values (" . get_request_var_post("id") . "," . get_request_var_post("graph_template_id") . ")");

//		exit;
	}
}

function device_add_dq() {
	if (!empty($_POST["snmp_query_id"])) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post("id"));
		input_validate_input_number(get_request_var_post("snmp_query_id"));
		input_validate_input_number(get_request_var_post("reindex_method"));
		/* ==================================================== */

		db_execute("replace into host_snmp_query (host_id,snmp_query_id,reindex_method) values (" . get_request_var_post("id") . "," . get_request_var_post("snmp_query_id") . "," . get_request_var_post("reindex_method") . ")");

		/* recache snmp data */
		run_data_query(get_request_var_post("id"), get_request_var_post("snmp_query_id"));

//		exit;
	}
}

function device_form_save() {
	/*
	 * loop for all possible changes of reindex_method
	 * post variable is build like this
	 * 		reindex_method_device_<host_id>_query_<snmp_query_id>_method_<old_reindex_method>
	 * if values of this variable differs from <old_reindex_method>, we will have to update
	 */
	$reindex_performed = false;
	while (list($var,$val) = each($_POST)) {
		if (preg_match("/^reindex_method_device_([0-9]+)_query_([0-9]+)_method_([0-9]+)$/", $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number(get_request_var_post("id"));
			input_validate_input_number($matches[1]); # device
			input_validate_input_number($matches[2]); # snmp_query_id
			input_validate_input_number($matches[3]); # old reindex method
			$reindex_method = $val;
			input_validate_input_number($reindex_method); # new reindex_method
			/* ==================================================== */

			# change reindex method of this very item
			if ( $reindex_method != $matches[3]) {
				db_execute("replace into host_snmp_query (host_id,snmp_query_id,reindex_method) values (" . $matches[1] . "," . $matches[2] . "," . $reindex_method . ")");

				/* recache snmp data */
				run_data_query($matches[1], $matches[2]);
				$reindex_performed = true;
			}
		}
	}

	/* save basic device information during first run, host_template should have been selected */
	if (isset($_POST["save_basic_host"])) {
		/* device template was given, so fetch defaults from it */
		$use_template = false;
		if ($_POST["host_template_id"] != 0) {
			$host_template = db_fetch_row("SELECT *
			FROM host_template
			WHERE id=" . $_POST["host_template_id"]);
		if (($host_template["override_defaults"] == CHECKED) &&
			(($host_template["override_permitted"] == CHECKED) &&
			($_POST["template_enabled"] == CHECKED)) || ($host_template["override_permitted"] != CHECKED)) {
			$use_template = true;
			$host_template["template_enabled"] = CHECKED;
		}
		}
		
		if (!$use_template) {
			$host_template["snmp_community"]        = get_request_var_post("snmp_community");
			$host_template["snmp_version"]          = get_request_var_post("snmp_version");
			$host_template["snmp_username"]         = get_request_var_post("snmp_username");
			$host_template["snmp_password"]         = get_request_var_post("snmp_password");
			$host_template["snmp_port"]             = get_request_var_post("snmp_port");
			$host_template["snmp_timeout"]          = get_request_var_post("snmp_timeout");
			$host_template["availability_method"]   = get_request_var_post("availability_method");
			$host_template["ping_method"]           = get_request_var_post("ping_method");
			$host_template["ping_port"]             = get_request_var_post("ping_port");
			$host_template["ping_timeout"]          = get_request_var_post("ping_timeout");
			$host_template["ping_retries"]          = get_request_var_post("ping_retries");
			$host_template["snmp_auth_protocol"]    = get_request_var_post("snmp_auth_protocol");
			$host_template["snmp_priv_passphrase"]  = get_request_var_post("snmp_priv_passphrase");
			$host_template["snmp_priv_protocol"]    = get_request_var_post("snmp_priv_protocol");
			$host_template["snmp_context"]          = get_request_var_post("snmp_context");
			$host_template["max_oids"]              = get_request_var_post("max_oids");
			$host_template["device_threads"]        = get_request_var_post("device_threads");
			$host_template["template_enabled"]      = "";
		}
		
		$host_template["notes"]    = ""; /* no support for notes in a host template */
		$host_template["disabled"] = ""; /* no support for disabling in a host template */
		$host_id = device_save($_POST["id"], $_POST["site_id"], $_POST["poller_id"], $_POST["host_template_id"], $_POST["description"],
			get_request_var_post("hostname"), $host_template["snmp_community"], $host_template["snmp_version"],
			$host_template["snmp_username"], $host_template["snmp_password"],
			$host_template["snmp_port"], $host_template["snmp_timeout"],
			$host_template["disabled"],
			$host_template["availability_method"], $host_template["ping_method"],
			$host_template["ping_port"], $host_template["ping_timeout"],
			$host_template["ping_retries"], $host_template["notes"],
			$host_template["snmp_auth_protocol"], $host_template["snmp_priv_passphrase"],
			$host_template["snmp_priv_protocol"], $host_template["snmp_context"], $host_template["max_oids"],
			$host_template["device_threads"], $host_template["template_enabled"]);

		
		header("Location: host.php?action=edit&id=" . (empty($host_id) ? $_POST["id"] : $host_id));
		exit;
	}

	if (isset($_POST["save_component_device"])) {
		if (get_request_var_post("snmp_version") == 3 && (get_request_var_post("snmp_password") != get_request_var_post("snmp_password_confirm"))) {
			raise_message(4);
		}else{
			$host_id = device_save($_POST["id"], $_POST["site_id"], $_POST["poller_id"], $_POST["host_template_id"], $_POST["description"],
				trim(get_request_var_post("hostname")), get_request_var_post("snmp_community"), get_request_var_post("snmp_version"),
				get_request_var_post("snmp_username"), get_request_var_post("snmp_password"),
				get_request_var_post("snmp_port"), get_request_var_post("snmp_timeout"),
				(isset($_POST["disabled"]) ? get_request_var_post("disabled") : ""),
				get_request_var_post("availability_method"), get_request_var_post("ping_method"),
				get_request_var_post("ping_port"), get_request_var_post("ping_timeout"),
				get_request_var_post("ping_retries"), get_request_var_post("notes"),
				get_request_var_post("snmp_auth_protocol"), get_request_var_post("snmp_priv_passphrase"),
				get_request_var_post("snmp_priv_protocol"), get_request_var_post("snmp_context"),
				get_request_var_post("max_oids"), get_request_var_post("device_threads"),
				(isset($_POST["template_enabled"]) ? get_request_var_post("template_enabled") : ""));
		}

		header("Location: host.php?action=edit&id=" . (empty($host_id) ? $_POST["id"] : $host_id));
		exit;
	}
}

/**
 * The "actions" function
 */
function device_form_actions() {
	require(CACTI_INCLUDE_PATH . "/device/device_arrays.php");
	require(CACTI_INCLUDE_PATH . "/graph_tree/graph_tree_arrays.php");
	require_once(CACTI_LIBRARY_PATH . "/data_source.php");
	require_once(CACTI_LIBRARY_PATH . "/graph.php");

	$fields_device_edit = device_form_list();
	$fields_device_edit_availability = device_availability_form_list();
	/* if we are to save this form, instead of display it */
	if (isset($_POST["selected_items"])) {
		$selected_items = unserialize(stripslashes($_POST["selected_items"]));

		if (get_request_var_post("drp_action") === DEVICE_ACTION_ENABLE) { /* Enable Selected Devices */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				db_execute("update host set disabled='' where id='" . $selected_items[$i] . "'");

				/* update poller cache */
				$data_sources = db_fetch_assoc("select id from data_local where host_id='" . $selected_items[$i] . "'");
				$poller_items = $local_data_ids = array();

				if (sizeof($data_sources) > 0) {
					foreach ($data_sources as $data_source) {
						$local_data_ids[] = $data_source["id"];
						$poller_items     = array_merge($poller_items, update_poller_cache($data_source["id"]));
					}
				}

				if (sizeof($local_data_ids)) {
					poller_update_poller_cache_from_buffer($local_data_ids, $poller_items);
				}
			}
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_DISABLE) { /* Disable Selected Devices */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				db_execute("update host set disabled='on' where id='" . $selected_items[$i] . "'");

				/* update poller cache */
				db_execute("delete from poller_item where host_id='" . $selected_items[$i] . "'");
				db_execute("delete from poller_reindex where host_id='" . $selected_items[$i] . "'");
			}
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_CHANGE_SNMP_OPTIONS) { /* change snmp options */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				reset($fields_device_edit);
				while (list($field_name, $field_array) = each($fields_device_edit)) {
					if (isset($_POST["t_$field_name"])) {
						db_execute("update host set $field_name = '" . $_POST[$field_name] . "' where id='" . $selected_items[$i] . "'");
					}
				}

				push_out_host($selected_items[$i]);
			}
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_CLEAR_STATISTICS) { /* Clear Statisitics for Selected Devices */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				db_execute("update host set min_time = '9.99999', max_time = '0', cur_time = '0',	avg_time = '0',
						total_polls = '0', failed_polls = '0',	availability = '100.00'
						where id = '" . $selected_items[$i] . "'");
			}
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_CHANGE_AVAILABILITY_OPTIONS) { /* change availability options */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				reset($fields_device_edit);
				while (list($field_name, $field_array) = each($fields_device_edit)) {
					if (isset($_POST["t_$field_name"])) {
						db_execute("update host set $field_name = '" . $_POST[$field_name] . "' where id='" . $selected_items[$i] . "'");
					}
				}

				push_out_host($selected_items[$i]);
			}
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_CHANGE_POLLER) { /* change poller */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				reset($fields_device_edit);
				while (list($field_name, $field_array) = each($fields_device_edit)) {
					if (isset($_POST["$field_name"])) {
						db_execute("update host set $field_name = '" . $_POST[$field_name] . "' where id='" . $selected_items[$i] . "'");
					}
				}

				push_out_host($selected_items[$i]);
			}
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_CHANGE_SITE) { /* change site */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				reset($fields_device_edit);
				while (list($field_name, $field_array) = each($fields_device_edit)) {
					if (isset($_POST["$field_name"])) {
						db_execute("update host set $field_name = '" . $_POST[$field_name] . "' where id='" . $selected_items[$i] . "'");
					}
				}

				push_out_host($selected_items[$i]);
			}
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_DELETE) { /* delete */
			if (!isset($_POST["delete_type"])) { $_POST["delete_type"] = 2; }

			$data_sources_to_act_on = array();
			$graphs_to_act_on       = array();
			$devices_to_act_on      = array();

			for ($i=0; $i<count($selected_items); $i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				$data_sources = db_fetch_assoc("select
					data_local.id as local_data_id
					from data_local
					where " . array_to_sql_or($selected_items, "data_local.host_id"));

				if (sizeof($data_sources) > 0) {
				foreach ($data_sources as $data_source) {
					$data_sources_to_act_on[] = $data_source["local_data_id"];
				}
				}

				if (get_request_var_post("delete_type") == 2) {
					$graphs = db_fetch_assoc("select
						graph_local.id as local_graph_id
						from graph_local
						where " . array_to_sql_or($selected_items, "graph_local.host_id"));

					if (sizeof($graphs) > 0) {
					foreach ($graphs as $graph) {
						$graphs_to_act_on[] = $graph["local_graph_id"];
					}
					}
				}

				$devices_to_act_on[] = $selected_items[$i];
			}

			switch (get_request_var_post("delete_type")) {
				case '1': /* leave graphs and data_sources in place, but disable the data sources */
					data_source_disable_multi($data_sources_to_act_on);

					break;
				case '2': /* delete graphs/data sources tied to this device */
					data_source_remove_multi($data_sources_to_act_on);

					graph_remove_multi($graphs_to_act_on);

					break;
			}

			device_remove_multi($devices_to_act_on);
		}elseif (preg_match("/^tr_([0-9]+)$/", get_request_var_post("drp_action"), $matches)) { /* place on tree */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				input_validate_input_number(get_request_var_post("tree_id"));
				input_validate_input_number(get_request_var_post("tree_item_id"));
				/* ==================================================== */

				tree_item_save(0, get_request_var_post("tree_id"), TREE_ITEM_TYPE_HOST, get_request_var_post("tree_item_id"), "", 0, read_graph_config_option("default_rra_id"), $selected_items[$i], 1, 1, false);
			}
		} else {
			plugin_hook_function('device_action_execute', get_request_var_post('drp_action'));
		}

		exit;
	}

	/* setup some variables */
	$device_list = ""; $device_array = array();

	/* loop through each of the device templates selected on the previous page and get more info about them */
	while (list($var,$val) = each($_POST)) {
		if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$device_list .= "<li>" . db_fetch_cell("select description from host where id=" . $matches[1]) . "</li>";
			$device_array[] = $matches[1];
		}
	}

	/* add a list of tree names to the actions dropdown */
	if (isset($device_actions)) {
		$device_actions = array_merge($device_actions, tree_add_tree_names_to_actions_array());
	}else{
		$device_actions = tree_add_tree_names_to_actions_array();
	}

	$device_actions[ACTION_NONE] = "None";

	print "<form id='device_edit_actions' method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='device_edit_actions'>\n";

	html_start_box("", "100", "3", "center", "");

	if (isset($device_array) && sizeof($device_array)) {
		if (get_request_var_post("drp_action") === ACTION_NONE) { /* NONE */
			print "	<tr>
						<td class='textArea'>
							<p>" . "You did not select a valid action. Please select 'Return' to return to the previous menu." . "</p>
						</td>
					</tr>\n";

			$title = "Selection Error";
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_ENABLE) { /* Enable Devices */
			print "	<tr>
					<td colspan='2' class='textArea'>
						<p>" . "When you click 'Continue', the following Device(s) will be enabled." . "</p>
						<div class='action_list'><ul>$device_list</ul></div>
					</td>
					</tr>";

			$title = "Enable Device(s)";
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_DISABLE) { /* Disable Devices */
			print "	<tr>
					<td colspan='2' class='textArea'>
						<p>" . "When you click 'Continue', the following Device(s) will be disabled." . "</p>
						<div class='action_list'><ul>$device_list</ul></div>
					</td>
					</tr>";

			$title = "Disable Device(s)";
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_CHANGE_SNMP_OPTIONS) { /* change snmp options */
			print "	<tr>
					<td colspan='2' class='textArea'>
						<p>" . "When you click 'Continue', the following Device(s) will have their SNMP settings changed.  Make sure you check the box next to the fields you want to update, and fill in the new values before continuing." . "</p>
						<div class='action_list'><ul>$device_list</ul></div>
					</td>
					</tr>";

			$form_array = array();
			while (list($field_name, $field_array) = each($fields_device_edit_availability)) {
				if (preg_match("/(^snmp_|max_oids)/", $field_name)) {
					$form_array += array($field_name => $fields_device_edit_availability[$field_name]);

					$form_array[$field_name]["value"] = "";
					$form_array[$field_name]["form_id"] = 0;
					$form_array[$field_name]["sub_checkbox"] = array(
						"name" => "t_" . $field_name,
						"friendly_name" => "Update this Field",
						"value" => ""
						);
				}
			}

			draw_edit_form(
				array(
					"config" => array("no_form_tag" => true),
					"fields" => $form_array
					)
				);

			$title = "Change Device(s) SNMP options";
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_CHANGE_AVAILABILITY_OPTIONS) { /* change availability options */
			print "	<tr>
					<td colspan='2' class='textArea'>
						<p>" . "When you click 'Continue', the following Device(s) Availability options will be changed.  Make sure you check the box next to the fields you want to update, and fill in the new values before continuing." . "</p>
						<div class='action_list'><ul>$device_list</ul></div>
					</td>
					</tr>";

			$form_array = array();
			while (list($field_name, $field_array) = each($fields_device_edit_availability)) {
				if (!preg_match("/(^snmp_|max_oids)/", $field_name)) {
					$form_array += array($field_name => $fields_device_edit_availability[$field_name]);

					$form_array[$field_name]["value"] = "";
					$form_array[$field_name]["form_id"] = 0;
					$form_array[$field_name]["sub_checkbox"] = array(
						"name" => "t_" . $field_name,
						"friendly_name" => "Update this Field",
						"value" => ""
						);
				}
			}

			draw_edit_form(
				array(
					"config" => array("no_form_tag" => true),
					"fields" => $form_array
					)
				);

			$title = "Change Device(s) Availability options";
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_CLEAR_STATISTICS) { /* Clear Statisitics for Selected Devices */
			print "	<tr>
					<td colspan='2' class='textArea'>
						<p>" . "When you click 'Continue', the following Device(s) statistics will be reset." . "</p>
						<div class='action_list'><ul>$device_list</ul></div>
					</td>
					</tr>";

			$title = "Clear Device(s) Statistics";
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_DELETE) { /* delete */
			print "	<tr>
					<td class='textArea'>
						<p>" . "When you click 'Continue', the following Device(s) will be deleted." . "</p>
						<div class='action_list'><ul>$device_list</ul></div>";
						form_radio_button("delete_type", "2", "1", "Leave all Graph(s) and Data Source(s) untouched.  Data Source(s) will be disabled however.", "1"); print "<br>";
						form_radio_button("delete_type", "2", "2", "Delete all associated <strong>Graph(s)</strong> and <strong>Data Source(s)</strong>.", "1"); print "<br>";
						print "</td></tr>
					</td>
				</tr>\n";

			$title = "Delete Device(s)";
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_CHANGE_POLLER) { /* Change Poller */
			print "	<tr>
					<td colspan='2' class='textArea'>
						<p>" . "When you click 'Continue', the following Device(s) will be re-associated with the Poller below." . "</p>
						<div class='action_list'><ul>$device_list</ul></div>
					</td>
					</tr>\n";

			$form_array = array();
			$field_name = "poller_id";
			$form_array += array($field_name => $fields_device_edit["poller_id"]);
			$form_array[$field_name]["description"] = "Please select the new Poller for the selected Device(s).";

			draw_edit_form(
				array(
					"config" => array("no_form_tag" => true),
					"fields" => $form_array
					)
				);

			$title = "Change Device(s) Poller";
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_CHANGE_SITE) { /* Change Site */
			print "	<tr>
					<td colspan='2' class='textArea'>
						<p>" . "When you click 'Continue', the following Device(s) will be re-associated with the Site below." . "</p>
						<div class='action_list'><ul>$device_list</ul></div>
					</td>
					</tr>\n";

			$form_array = array();
			$field_name = "site_id";
			$form_array += array($field_name => $fields_device_edit["site_id"]);
			$form_array[$field_name]["description"] = "Please select the new Site for the selected Device(s).";

			draw_edit_form(
				array(
					"config" => array("no_form_tag" => true),
					"fields" => $form_array
					)
				);

			$title = "Change Device(s) Site";
		}elseif (preg_match("/^tr_([0-9]+)$/", get_request_var_post("drp_action"), $matches)) { /* place on tree */
			print "	<tr>
					<td class='textArea'>
						<p>" . "When you click 'Continue', the following Device(s) will be placed under the Tree Branch selected below." . "</p>
						<div class='action_list'><ul>$device_list</ul></div>
						<p><strong>" . "Destination Branch: " . "</strong><br>"; grow_dropdown_tree($matches[1], "tree_item_id", "0"); print "</p>
					</td>
				</tr>\n
				<input type='hidden' name='tree_id' value='" . $matches[1] . "'>\n ";

			$title = "Place Device(s) on a Tree";
		} else {
			$save['drp_action'] = $_POST['drp_action'];
			$save['device_list'] = $device_list;
			$save['device_array'] = (isset($device_array)? $device_array : array());
			$save['title'] = '';
			plugin_hook_function('device_action_prepare', $save);

			if (strlen($save['title'])) {
				$title = $save['title'];
			}else{
				$title = '';
			}
		}
	} else {
		print "	<tr>
				<td class='textArea'>
					<p>" . "You must first select a Device.  Please select 'Return' to return to the previous menu." . "</p>
				</td>
			</tr>\n";

		$title = "Selection Error";
	}

	form_hidden_box("tab", "devices", "");

	if (!isset($device_array) || get_request_var_post("drp_action") === ACTION_NONE) {
		form_return_button($title);
	}else{
		form_continue(serialize($device_array), get_request_var_post("drp_action"), $title, "device_edit_actions");
	}

	html_end_box();
}

/**
 * reload data query for given data query id and device
 */
function device_reload_query() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("host_id"));
	/* ==================================================== */

	run_data_query(get_request_var("host_id"), get_request_var("id"));

#	if ((isset($_GET["action"]) && $_GET["action"] == "query_verbose") && (isset($_SESSION["debug_log"]["data_query"]))) {
#		print "<div style='overflow:auto;height:300px;'>\n";
#		print "<span class=\"log\">" . debug_log_return("data_query") . "</span>";
#		print "</div>";
#		print "<div align='right'><input type='button' onClick='javascript:$(\"#cdialog\").dialog(\"close\");(\"#cdialog\").empty();' value='Close'></div>\n";
#	}
#
#	debug_log_clear("data_query");
}

/**
 * remove a data query from a device
 */
function device_remove_query() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("host_id"));
	/* ==================================================== */

	device_dq_remove(get_request_var("host_id"), get_request_var("id"));
}

/**
 * remove a graph template from a device
 */
function device_remove_gt() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("host_id"));
	/* ==================================================== */

	device_gt_remove(get_request_var("host_id"), get_request_var("id"));
}

/**
 * Device Sub-Tabs
 */
function html_devices_draw_tabs() {
	$parent_id = get_request_var_request("parent_id");

	$device_tabs = array(
		"general" 			=> "General",
		"graph_template" 	=> "Associated Graph Templates",
		"data_query" 		=> "Associated Data Queries",
		"graphs_new" 		=> "New Graphs",
		"graphs" 			=> "Existing Graphs",
		"data_sources" 		=> "Existing Data Sources"
	);

	$device_tabs = plugin_hook_function('device_tabs_init', $device_tabs);

	/* draw the categories tabs on the top of the page */
	print "<table width='100%' cellspacing='0' cellpadding='0' align='center'><tr>";
	print "<td><div id='tabs_device'>";

	if (sizeof($device_tabs) > 0) {
		print "<ul>";
		foreach (array_keys($device_tabs) as $tab_short_name) {
			switch ($tab_short_name) {
			case 'general':
				print "<li><a href='" . htmlspecialchars("host.php?action=ajax_edit" . (isset($_REQUEST['parent_id']) ? "&id=" . $_REQUEST['parent_id']: "") . (isset($_REQUEST['id']) ? "&id=" . $_REQUEST['id']: "") . "&tab=$tab_short_name") . "'>" . $device_tabs[$tab_short_name] . "</a></li>";
				break;
			case 'graph_template':
				print "<li><a href='" . htmlspecialchars("host.php?action=ajax_edit_graph_template" . (isset($_REQUEST['parent_id']) ? "&id=" . $_REQUEST['parent_id']: "") . (isset($_REQUEST['id']) ? "&id=" . $_REQUEST['id']: "") . "&tab=$tab_short_name") . "'>" . $device_tabs[$tab_short_name] . "</a></li>";
				break;
			case 'data_query':
				print "<li><a href='" . htmlspecialchars("host.php?action=ajax_edit_data_query" . (isset($_REQUEST['parent_id']) ? "&id=" . $_REQUEST['parent_id']: "") . (isset($_REQUEST['id']) ? "&id=" . $_REQUEST['id']: "") . "&tab=$tab_short_name") . "'>" . $device_tabs[$tab_short_name] . "</a></li>";
				break;
			case 'graphs_new':
				print "<li><a href='" . htmlspecialchars("graphs_new.php?action=ajax_view" . (isset($_REQUEST['id']) ? "&id=" . $_REQUEST['id'] . "&parent=devices&parent_id=" . $_REQUEST['id'] . "&host_id=" . $_REQUEST['id']: "") . "&tab=$tab_short_name") . "'>" . $device_tabs[$tab_short_name] . "</a></li>";
				break;
			case 'graphs':
				print "<li><a href='" . htmlspecialchars("graphs.php?action=ajax_view&parent=devices&parent_id=" . $_REQUEST['id'] . "&host_id=" . $_REQUEST['id'] . "&tab=$tab_short_name") . "'>" . $device_tabs[$tab_short_name] . "</a></li>";
				break;
			case 'data_sources':
				print "<li><a href='" . htmlspecialchars("data_sources.php?action=ajax_view&parent=devices&parent_id=" . $_REQUEST['id'] . "&host_id=" . $_REQUEST['id'] . "&tab=$tab_short_name") . "'>" . $device_tabs[$tab_short_name] . "</a></li>";
				break;
			default:
				plugin_hook_function('device_tabs_display', $tab_short_name);
				break;
			}

			if (!isset($_REQUEST["id"])) break;
		}
		print "</ul>";
	}

	print "</div></td></tr></table>
	<script type='text/javascript'>
		$().ready(function() {
			// call jQueryUI tabs
			$('div #tabs_device').tabs({ cookie: { expires: 30 } });
			});
	</script>\n";
}

/**
 * Edit a device
 */
function device_edit($tab = false) {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("host_id"));
	/* ==================================================== */

	if ($tab) {
		html_devices_draw_tabs();
	}elseif(isset($_REQUEST["parent"])) {
		// TODO: html_draw_tabs will in turn call html_devices_draw_tabs, so why using this way?
		html_draw_tabs();
	}else{
		if (!empty($_REQUEST["id"])) {
			$device         = db_fetch_row("select * from host where id=" . $_REQUEST["id"]);
			$device_text    = $device["description"] . " [" . $device["hostname"] . "]";
		}elseif (!empty($_GET["host_id"])) {
			$_REQUEST["id"]   = $_REQUEST["host_id"];
			$device         = db_fetch_row("select * from host where id=" . $_REQUEST["id"]);
			$device_text    = $device["description"] . " [" . $device["hostname"] . "]";
		}else{
			$device         = "";
			$device_text    = "New Device";
		}

		/* TODO: is it a good move to again switch on "action", as we already did in host.php? 
		 * or should we prefer to pass a parameter to this function to switch on */
		switch (get_request_var_request('action')) {
			case 'edit':
			case 'ajax_edit':
				device_display_general($device, $device_text);
				break;
			case 'ajax_edit_graph_template':
				device_display_graph_template($device, $device_text);	
				break;
			case 'ajax_edit_data_query':
				device_display_data_query($device, $device_text);
				break;
		}		
	}
}

/**
 * display general device tab that shows configuration data for that device
 */
function device_display_general($device, $device_text) {
	require(CACTI_INCLUDE_PATH . "/data_query/data_query_arrays.php");
	require(CACTI_INCLUDE_PATH . "/device/device_arrays.php");

	if (isset($device["id"])) {

		$dd_menu_options = 'cacti_dd_menu=device_options&host_id=' . $device["id"];

		html_start_box($device_text, "100", "3", "center", (isset($_GET["id"]) ? "menu::" . "Device Options" . ":device_options:html_start_box:" . $dd_menu_options : ""), true);

		?>
			<tr class="rowAlternate3">
				<?php if (($device["availability_method"] == AVAIL_SNMP) ||
					($device["availability_method"] == AVAIL_SNMP_GET_SYSDESC) ||
					($device["availability_method"] == AVAIL_SNMP_GET_NEXT) ||
					($device["availability_method"] == AVAIL_SNMP_AND_PING) ||
					($device["availability_method"] == AVAIL_SNMP_OR_PING)) { ?>
				<td class="textInfo">
					<?php print "SNMP Information";?><br>
					<span class="normal">
					<?php
					if ((($device["snmp_community"] == "") && ($device["snmp_username"] == "")) ||
						($device["snmp_version"] == 0)) {
						print "<span class=\"info\">SNMP not in use</span>\n";
					}else{
						$snmp_system = cacti_snmp_get($device["hostname"], $device["snmp_community"], ".1.3.6.1.2.1.1.1.0", $device["snmp_version"],
							$device["snmp_username"], $device["snmp_password"],
							$device["snmp_auth_protocol"], $device["snmp_priv_passphrase"], $device["snmp_priv_protocol"],
							$device["snmp_context"], $device["snmp_port"], $device["snmp_timeout"], read_config_option("snmp_retries"),SNMP_WEBUI);

						/* modify for some system descriptions */
						/* 0000937: System output in host.php poor for Alcatel */
						if (substr_count($snmp_system, "00:")) {
							$snmp_system = str_replace("00:", "", $snmp_system);
							$snmp_system = str_replace(":", " ", $snmp_system);
						}

						if ($snmp_system == "") {
							print "<span class=\"warning\">SNMP error</span>\n";
						}else{
							$snmp_uptime   = cacti_snmp_get($device["hostname"], $device["snmp_community"], ".1.3.6.1.2.1.1.3.0", $device["snmp_version"],
								$device["snmp_username"], $device["snmp_password"],
								$device["snmp_auth_protocol"], $device["snmp_priv_passphrase"], $device["snmp_priv_protocol"],
								$device["snmp_context"], $device["snmp_port"], $device["snmp_timeout"], read_config_option("snmp_retries"), SNMP_WEBUI);

							$snmp_hostname = cacti_snmp_get($device["hostname"], $device["snmp_community"], ".1.3.6.1.2.1.1.5.0", $device["snmp_version"],
								$device["snmp_username"], $device["snmp_password"],
								$device["snmp_auth_protocol"], $device["snmp_priv_passphrase"], $device["snmp_priv_protocol"],
								$device["snmp_context"], $device["snmp_port"], $device["snmp_timeout"], read_config_option("snmp_retries"), SNMP_WEBUI);

							$snmp_location = cacti_snmp_get($device["hostname"], $device["snmp_community"], ".1.3.6.1.2.1.1.6.0", $device["snmp_version"],
								$device["snmp_username"], $device["snmp_password"],
								$device["snmp_auth_protocol"], $device["snmp_priv_passphrase"], $device["snmp_priv_protocol"],
								$device["snmp_context"], $device["snmp_port"], $device["snmp_timeout"], read_config_option("snmp_retries"), SNMP_WEBUI);

							$snmp_contact  = cacti_snmp_get($device["hostname"], $device["snmp_community"], ".1.3.6.1.2.1.1.4.0", $device["snmp_version"],
								$device["snmp_username"], $device["snmp_password"],
								$device["snmp_auth_protocol"], $device["snmp_priv_passphrase"], $device["snmp_priv_protocol"],
								$device["snmp_context"], $device["snmp_port"], $device["snmp_timeout"], read_config_option("snmp_retries"), SNMP_WEBUI);

							print "<strong>System:</strong> " . html_split_string($snmp_system,200) . "<br>\n";
							$days      = intval($snmp_uptime / (60*60*24*100));
							$remainder = $snmp_uptime % (60*60*24*100);
							$hours     = intval($remainder / (60*60*100));
							$remainder = $remainder % (60*60*100);
							$minutes   = intval($remainder / (60*100));
							print "<strong>" . "Uptime:"   . " </strong> $snmp_uptime";
							print "&nbsp;($days days, $hours hours, $minutes minutes)<br>\n";
							print "<strong>" . "Hostname:" . " </strong> $snmp_hostname<br>\n";
							print "<strong>" . "Location:" . " </strong> $snmp_location<br>\n";
							print "<strong>" . "Contact:"  . " </strong> $snmp_contact<br>\n";
						}
					}
					?>
					</span>
				</td>
				<?php }
				if (($device["availability_method"] == AVAIL_PING) ||
					($device["availability_method"] == AVAIL_SNMP_AND_PING) ||
					($device["availability_method"] == AVAIL_SNMP_OR_PING)) {
					/* create new ping socket for device pinging */
					$ping = new Net_Ping;

					$ping->device = $device;
					$ping->port = $device["ping_port"];

					/* perform the appropriate ping check of the device */
					if ($ping->ping($device["availability_method"], $device["ping_method"],
						$device["ping_timeout"], $device["ping_retries"])) {
						$device_down = false;
						$ping_class = "ping";
						}else{
						$device_down = true;
						$ping_class = "ping_warning";
						}

				?>
				<td class="textInfo" style="vertical-align:top;">
					<?php print "Ping Results";?><br>
					<span class="<?php $ping_class ?>">
					<?php print $ping->ping_response; ?>
					</span>
				</td>
				<?php }else if ($device["availability_method"] == AVAIL_NONE) { ?>
				<td class="textInfo">
					<?php print "No Availability Check In Use";?><br>
				</td>
				<?php } ?>
			</tr>
		<?php
	}else{
		html_start_box($device_text, "100", "3", "center", "", false);
	}

	html_end_box(FALSE);

	print "<form id='device_edit_settings' method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='device_edit_settings'>\n";
	html_start_box("General Settings", "100", 0, "center", "", true);

	/* preserve the device template id if passed in via a GET variable */
	$fields_device_edit = device_form_list();
	if (!empty($_GET["template_id"])) {
//		$fields_device_edit["host_template_id"]["value"] = $_GET["template_id"];
//		$fields_device_edit["host_template_id"]["method"] = "hidden";
	}

	/* if we are creating a device and have changed templates set that value */
	if (!isset($device["id"])) {
		if (!empty($_GET["template_id"])) {
			$device["host_template_id"] = $_GET["template_id"];
		}
	}

	/* draw basic fields only on first run for a new device */
	draw_edit_form(array(
		"config" => array("form_name" => "chk", "no_form_tag" => true),
		"fields" => inject_form_variables($fields_device_edit, (is_array($device) ? $device : array()))
		));

	/* if the device is new, check/set the $device array with some template values */
	$override_permitted  = true;
	$propagation_allowed = false;
	if (!isset($device["id"]) && isset($_REQUEST["template_id"])) {
		$template_settings = db_fetch_row("SELECT * FROM host_template WHERE id=" . $_REQUEST["template_id"]);
		if (sizeof($template_settings)) {
		foreach($template_settings as $key => $value) {
			switch($key) {
				case "id":
				case "name":
				case "description":
				case "hash":
				case "image":
					unset($template_settings[$key]);
					break;
				case "override_defaults":
					if ($value == CHECKED) {
						$propagation_allowed = true;
					}
					unset($template_settings[$key]);
					break;
				case "override_permitted":
					if ($value != CHECKED) {
						$override_permitted = false;
					}
					break;
				default:
					break;
			}
		}
		}
	}else{
		if (isset($device["host_template_id"]) && db_fetch_cell("SELECT override_defaults FROM host_template WHERE id=" . $device["host_template_id"]) == CHECKED) {
			$propagation_allowed = true;
		}
	}

	/* for a given device, display all availability options as well */
	draw_edit_form(array(
		"config" => array("form_name" => "chk", "no_form_tag" => true),
		"fields" => inject_form_variables(device_availability_form_list(), (isset($template_settings) ? $template_settings : $device))
		));

	form_hidden_box("id", (isset($device["id"]) ? $device["id"] : "0"), "");
	form_hidden_box("hidden_host_template_id", (isset($device["host_template_id"]) ? $device["host_template_id"] : "0"), "");
	form_hidden_box("save_basic_host", "1", "");
	form_hidden_box("save_component_device", "1", "");
	form_hidden_box("tab", "devices", "");
	form_hidden_box("override_permitted", ($override_permitted ? "true":"false"), "");
	form_hidden_box("propagation_allowed", ($propagation_allowed ? "true":"false"), "");

	html_end_box(!isset($device["id"]));

	/* javascript relates to availability options, so include it only for existing devices */
	?>
	<script type="text/javascript">
	<!--


	/* set the visibility of the SNMP options available
	   depending on the SNMP version currently defined */
	function setSNMPVisibility(snmp_version) {
		//alert("setSNMPVisibility SNMP Version is '" + snmp_version + "'");

		switch(snmp_version) {
		case "<?php print SNMP_VERSION_NONE;?>": // SNMP none
//			$('#snmp_version').attr("disabled","disabled");
			$('#row_snmp_version').css('display', 'none');
			$('#row_snmp_username').css('display', 'none');
			$('#row_snmp_password').css('display', 'none');
			$('#row_snmp_community').css('display', 'none');
			$('#row_snmp_auth_protocol').css('display', 'none');
			$('#row_snmp_priv_passphrase').css('display', 'none');
			$('#row_snmp_priv_protocol').css('display', 'none');
			$('#row_snmp_context').css('display', 'none');
			$('#row_snmp_port').css('display', 'none');
			$('#row_snmp_timeout').css('display', 'none');
			$('#row_max_oids').css('display', 'none');

			break;
		case "<?php print SNMP_VERSION_1;?>": // SNMP V1
		case "<?php print SNMP_VERSION_2;?>": // SNMP V2
//			$('#snmp_version').removeAttr("disabled");
			$('#row_snmp_version').css('display', '');
			$('#row_snmp_username').css('display', 'none');
			$('#row_snmp_password').css('display', 'none');
			$('#row_snmp_community').css('display', '');
			$('#row_snmp_auth_protocol').css('display', 'none');
			$('#row_snmp_priv_passphrase').css('display', 'none');
			$('#row_snmp_priv_protocol').css('display', 'none');
			$('#row_snmp_context').css('display', 'none');
			$('#row_snmp_port').css('display', '');
			$('#row_snmp_timeout').css('display', '');
			$('#row_max_oids').css('display', '');

			break;
		case "<?php print SNMP_VERSION_3;?>": // SNMP V3
//			$('#snmp_version').removeAttr("disabled");
			$('#row_snmp_version').css('display', '');
			$('#row_snmp_username').css('display', '');
			$('#row_snmp_password').css('display', '');
			$('#row_snmp_community').css('display', 'none');
			$('#row_snmp_auth_protocol').css('display', '');
			$('#row_snmp_priv_passphrase').css('display', '');
			$('#row_snmp_priv_protocol').css('display', '');
			$('#row_snmp_context').css('display', '');
			$('#row_snmp_port').css('display', '');
			$('#row_snmp_timeout').css('display', '');
			$('#row_max_oids').css('display', '');

			break;
		}
	}



	/* set the visibility of the ping_port
	   in case we have an ICMP ping, you can't set a port
	 */
	function setPingPortVisibility(ping_method) {
		//alert("setPingPortVisibility Ping Method is '" + ping_method + "'");
		
		switch(ping_method) {
		case "<?php print PING_NONE;?>": // ping nothing
			/* deactivate all PING options */
			$('#row_ping_method').css('display', 'none');
			$('#row_ping_port').css('display', 'none');
			$('#row_ping_timeout').css('display', 'none');
			$('#row_ping_retries').css('display', 'none');

			break;
		case "<?php print PING_ICMP;?>": // ping icmp
			/* ICMP ping does not take a port */
			$('#row_ping_method').css('display', '');
			$('#row_ping_port').css('display', 'none');
			$('#row_ping_timeout').css('display', '');
			$('#row_ping_retries').css('display', '');

			break;
		case "<?php print PING_UDP;?>": // ping udp
		case "<?php print PING_TCP;?>": // ping tcp
			$('#row_ping_method').css('display', '');
			$('#row_ping_port').css('display', '');
			$('#row_ping_timeout').css('display', '');
			$('#row_ping_retries').css('display', '');

			break;
		}
	}

	/* this function is called when
	   - availibility options changes
	   - ping method changes
	   - SNMP version changes
	   - and on page load
	   it will cover the required changes by calling appropriate functions
	   that are responsible for each specific change
	 */
	function changeHostForm() {
		ping_method         = $('#ping_method').val();
		//alert("changeHostForm Ping Method is '" + ping_method + "'");
		snmp_version        = $('#snmp_version').val();
		//alert("changeHostForm SNMP Version is '" + snmp_version + "'");		
		availability        = $('#availability_method').val();
		//alert("changeHostForm Availability is '" + availability + "'");


		switch(availability) {
		case "<?php print AVAIL_NONE;?>": // availability none
			/* deactivate PING */
			setPingPortVisibility("<?php print PING_NONE;?>")
			/* deactivate SNMP */
			setSNMPVisibility("<?php print SNMP_VERSION_NONE;?>")

			break;
		case "<?php print AVAIL_PING;?>": // ping
			/* set PING */
			setPingPortVisibility(ping_method)
			/* deactivate SNMP */
			setSNMPVisibility("<?php print SNMP_VERSION_NONE;?>")

			break;
		case "<?php print AVAIL_SNMP;?>": // snmp
		case "<?php print AVAIL_SNMP_GET_NEXT;?>": // snmp
		case "<?php print AVAIL_SNMP_GET_SYSDESC;?>": // snmp
			/* deactivate PING */
			setPingPortVisibility("<?php print PING_NONE;?>")
			/* set SNMP, take care when previous SNMP version was SNMP_VERSION_NONE */
			if (snmp_version == <?php print SNMP_VERSION_NONE;?>) {
				/* this at least allows for displaying the SNMP versions 
				   and thus the user may change to values required */
				snmp_version = <?php print '"' . SNMP_VERSION_1 . '"';?>;
				$('#snmp_version').val(snmp_version);
			}
			setSNMPVisibility(snmp_version)

			break;
		case "<?php print AVAIL_SNMP_AND_PING;?>": // ping and snmp
		case "<?php print AVAIL_SNMP_OR_PING;?>": // ping or snmp
			/* set PING */
			setPingPortVisibility(ping_method)
			/* set SNMP, take care when previous SNMP version was SNMP_VERSION_NONE */
			if (snmp_version == <?php print SNMP_VERSION_NONE;?>) {
				/* this at least allows for displaying the SNMP versions 
				   and thus the user may change to values required */
				snmp_version = <?php print '"' . SNMP_VERSION_1 . '"';?>;
				$('#snmp_version').val(snmp_version);
			}
			setSNMPVisibility(snmp_version)

			break;
		}
	}

	/* enable/disable setting of 
	   - availability options
	   - ping options
	   - SNMP options
	   - threading
	   as a result of templating being enabled or disabled
	 */
	function toggleAvailabilityAndSnmp(template_enabled){
		//alert("toggleAvailabilityAndSnmp called");
	
		/* in case templating is disabled and override is allowed
		   => allow for editing those options on device level
		      by removing the "disabled" attribute
		 */ 
		if (!template_enabled && $('#override_permitted').val() == 'true') {
			$('#override_permitted').removeAttr("disabled");
			$('#availability_method').removeAttr("disabled");
			$('#ping_method').removeAttr("disabled");
			$('#ping_port').removeAttr("disabled");
			$('#ping_timeout').removeAttr("disabled");
			$('#ping_retries').removeAttr("disabled");
			$('#snmp_version').removeAttr("disabled");
			$('#snmp_username').removeAttr("disabled");
			$('#snmp_password').removeAttr("disabled");
			$('#snmp_password_confirm').removeAttr("disabled");
			$('#snmp_community').removeAttr("disabled");
			$('#snmp_auth_protocol').removeAttr("disabled");
			$('#snmp_priv_passphrase').removeAttr("disabled");
			$('#snmp_priv_protocol').removeAttr("disabled");
			$('#snmp_context').removeAttr("disabled");
			$('#snmp_port').removeAttr("disabled");
			$('#snmp_timeout').removeAttr("disabled");
			$('#max_oids').removeAttr("disabled");
			$('#device_threads').removeAttr("disabled");
		}else{
			/* in all other cases
			   => disallow editing those options on device level
			      by setting the "disabled" attribute
		 	 */ 
			$('#override_permitted').attr("disabled","disabled");
			$('#availability_method').attr("disabled","disabled");
			$('#ping_method').attr("disabled","disabled");
			$('#ping_port').attr("disabled","disabled");
			$('#ping_timeout').attr("disabled","disabled");
			$('#ping_retries').attr("disabled","disabled");
			$('#snmp_version').attr("disabled","disabled");
			$('#snmp_username').attr("disabled","disabled");
			$('#snmp_password').attr("disabled","disabled");
			$('#snmp_password_confirm').attr("disabled","disabled");
			$('#snmp_community').attr("disabled","disabled");
			$('#snmp_auth_protocol').attr("disabled","disabled");
			$('#snmp_priv_passphrase').attr("disabled","disabled");
			$('#snmp_priv_protocol').attr("disabled","disabled");
			$('#snmp_context').attr("disabled","disabled");
			$('#snmp_port').attr("disabled","disabled");
			$('#snmp_timeout').attr("disabled","disabled");
			$('#max_oids').attr("disabled","disabled");
			$('#device_threads').attr("disabled","disabled");
		}

		changeHostForm();

		if ($('#override_permitted').val() == 'false') {
			$('#template_enabled').attr("checked","checked");
			$('#template_enabled').attr("disabled","disabled");
		}

		if ($('#propagation_allowed').val() == 'false') {
			$('#row_template_enabled').hide();
		}else{
			$('#row_template_enabled').show();
		}
	}

	$().ready(function() {
		//alert('ready function firing');
		toggleAvailabilityAndSnmp($('#template_enabled').attr('checked'));

		/* Hide options when override is turned off */
		$("#template_enabled").change(function() {
			toggleAvailabilityAndSnmp(this.checked);
		});

		/* if this is a new device */
		if ($('#id').val() == 0) {
			/* react to any change of the host_template */
			$('#host_template_id').change(function() {
				/* and fetch data from the host_template */
				$.get("host.php?action=ajax&jaction=template&template="+this.value, function(data) {
					if (data != "null") {
						data = $.parseJSON(data);
						$('#availability_method').val(data.availability_method);
						$('#ping_method').val(data.ping_method);
						$('#ping_port').val(data.ping_port);
						$('#ping_timeout').val(data.ping_timeout);
						$('#ping_retries').val(data.ping_retries);
						$('#snmp_version').val(data.snmp_version);
						$('#snmp_username').val(data.snmp_username);
						$('#snmp_password').val(data.snmp_password);
						$('#snmp_password_confirm').val(data.snmp_password);
						$('#snmp_community').val(data.snmp_community);
						$('#snmp_auth_protocol').val(data.snmp_auth_protocol);
						$('#snmp_priv_passphrase').val(data.snmp_priv_passphrase);
						$('#snmp_priv_protocol').val(data.snmp_priv_protocol);
						$('#snmp_context').val(data.snmp_port);
						$('#snmp_port').val(data.snmp_port);
						$('#snmp_timeout').val(data.snmp_timeout);
						$('#max_oids').val(data.max_oids);
						$('#device_threads').val(data.device_threads);

						if (data.override_defaults=="" || data.override_permitted=="on") {
							toggleAvailabilityAndSnmp(false);
						}else{
							toggleAvailabilityAndSnmp(true);
						}
					}else{
						toggleAvailabilityAndSnmp(false);
					}
				});
			});
		}
	});

	-->
	</script>
	<?php

	form_save_button("host.php", "return");
}

/**
 * display general device tab that shows configuration data for that device
 */
function device_display_graph_template($device, $device_text) {
	require(CACTI_INCLUDE_PATH . "/data_query/data_query_arrays.php");
	require(CACTI_INCLUDE_PATH . "/device/device_arrays.php");


	print "<form id='device_edit_graph_template' name='device_edit_graph_template' method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "'>\n";
	if (isset($device["id"])) {
		html_start_box("Associated Graph Templates", "100", 0, "center", "", true);
		print "<tr><td>";
		html_header(array(array("name" => "Graph Template Name"), array("name" => "Status")), 2);

		$selected_graph_templates = db_fetch_assoc("select
			graph_templates.id,
			graph_templates.name
			from (graph_templates,host_graph)
			where graph_templates.id=host_graph.graph_template_id
			and host_graph.host_id=" . $_GET["id"] . "
			order by graph_templates.name");

		$available_graph_templates = db_fetch_assoc("SELECT
			graph_templates.id, graph_templates.name
			FROM snmp_query_graph RIGHT JOIN graph_templates
			ON (snmp_query_graph.graph_template_id = graph_templates.id)
			WHERE (((snmp_query_graph.name) Is Null)) ORDER BY graph_templates.name");

		/* omit those graph_templates, that have already been associated */
		$keeper = array();
		foreach ($available_graph_templates as $item) {
			if (sizeof(db_fetch_assoc("SELECT graph_template_id FROM host_graph " .
					" WHERE ((host_id=" . $_GET["id"] . ")" .
					" AND (graph_template_id=" . $item["id"] ."))")) > 0) {
				/* do nothing */
			} else {
				array_push($keeper, $item);
			}
		}

		$available_graph_templates = $keeper;

		$i = 0;
		if (sizeof($selected_graph_templates) > 0) {
		foreach ($selected_graph_templates as $item) {
			$i++;
			form_alternate_row_color("graph_template" . $i);

			/* get status information for this graph template */
			$is_being_graphed = (sizeof(db_fetch_assoc("select id from graph_local where graph_template_id=" . $item["id"] . " and host_id=" . $_GET["id"])) > 0) ? true : false;

			?>
				<td style="padding: 4px;">
					<?php print $i;?>) <?php print $item["name"];?>
				</td>
				<td>
					<?php print (($is_being_graphed == true) ? "<span class=\"success\">" . "Is Being Graphed" . "</span> (<a href='" . htmlspecialchars("graphs.php?action=edit&id=" . db_fetch_cell("select id from graph_local where graph_template_id=" . $item["id"] . " and host_id=" . get_request_var("id") . " limit 0,1")) . "'>" . "Edit" . "</a>)" : "<span class=\"unknown\">" . "Not Being Graphed" . "</span>");?>
				</td>
				<td align='right' nowrap>
					<img id='<?php print $_GET["id"] . "_" . $item["id"];?>' align='middle' class='buttonSmall img_filter gremove' src='images/delete_icon_large.gif' title='<?php print "Delete Graph Template Association";?>' alt='<?php print "Delete";?>'>
				</td>
			<?php
			form_end_row();
		}
		}else{
			print "<tr><td><em>" . "No Associated Graph Templates." . "</em></td></tr>";
		}

		form_alternate_row_color("gt_device" . $device["id"]);
		?>
			<td colspan="4">
				<table cellspacing="0" cellpadding="1" width="100%">
					<tr>
					<td><?php print "Add Graph Template:";?>&nbsp;
						<?php form_dropdown("graph_template_id",$available_graph_templates,"name","id","","","");?>
					</td>
					<td align="right" style="text-align:right;padding:0px;">
						&nbsp;<input id="add_gt_y" type="button" value="<?php print "Add";?>" name="add_gt_y" align="middle">
					</td>
					</tr>
				</table>
			</td>
		<?php
		form_end_row();
		print "</table></td></tr>";		/* end of html_header */
		form_hidden_box("id", (isset($device["id"]) ? $device["id"] : "0"), "");
		form_hidden_box("tab", "devices", "");
		html_end_box(FALSE);
	}

	print "</form>";		/* end of html_header */

	?>
	<script type='text/javascript'>
	$().ready(function() {
		$('.gremove').click(function(data) {
			id=$(this).attr('id').split('_');
			$.get('host.php?action=gt_remove&id='+id[1]+'&host_id='+id[0], function(data) {
				//re-call same tab by $.get-ting appropriate edit function
				$.get('host.php?action=ajax_edit_graph_template&id='+id[0], function(data) {
					//reload the form provided by this function, thus refreshing the form
					$('#device_edit_graph_template').html(data);
				});
			});
		}).css('cursor', 'pointer');
		$('#add_gt_y').click(function(data) {
			$('#action').attr('value', 'add_gt');
			id=$('#id').attr('value');
			//pass all form parameters using "serialize" to the add function
			$.post('host.php?action=add_gt', $('#device_edit_graph_template').serialize(), function(data) {
				//re-call same tab by $.get-ting appropriate edit function
				$.get('host.php?action=ajax_edit_graph_template&id='+id, function(data) {
					//reload the form provided by this function, thus refreshing the form
					$('#device_edit_graph_template').html(data);
				});
			});
		}); 
	});
	<?php
}

/**
 * display general device tab that shows configuration data for that device
 */
function device_display_data_query($device, $device_text) {
	require(CACTI_INCLUDE_PATH . "/data_query/data_query_arrays.php");
	require(CACTI_INCLUDE_PATH . "/device/device_arrays.php");


	print "<form id='device_edit_data_query' name='device_edit_data_query' method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "'>\n";
	if (isset($device["id"])) {
		html_start_box("Associated Data Queries", "100", 0, "center", "", true);
		print "<tr><td>";
		html_header(array(array("name" => "Data Query Name"), array("name" => "Debugging"), array("name" => "Re-Index Method"), array("name" =>"Status")), 2);

		$selected_data_queries = db_fetch_assoc("select
			snmp_query.id,
			snmp_query.name,
			host_snmp_query.reindex_method
			from (snmp_query,host_snmp_query)
			where snmp_query.id=host_snmp_query.snmp_query_id
			and host_snmp_query.host_id=" . $_GET["id"] . "
			order by snmp_query.name");

		$available_data_queries = db_fetch_assoc("select
			snmp_query.id,
			snmp_query.name
			from snmp_query
			order by snmp_query.name");

		$keeper = array();
		foreach ($available_data_queries as $item) {
			if (sizeof(db_fetch_assoc("SELECT snmp_query_id FROM host_snmp_query " .
					" WHERE ((host_id=" . $_GET["id"] . ")" .
					" and (snmp_query_id=" . $item["id"] ."))")) > 0) {
				/* do nothing */
			} else {
				array_push($keeper, $item);
			}
		}

		$available_data_queries = $keeper;

		$i = 0;
		if (sizeof($selected_data_queries) > 0) {
			foreach ($selected_data_queries as $item) {
				$i++;
				form_alternate_row_color("selected_data_queries" . $i);

				/* get status information for this data query */
				$num_dq_items = sizeof(db_fetch_assoc("select snmp_index from host_snmp_cache where host_id=" . $_GET["id"] . " and snmp_query_id=" . $item["id"]));
				$num_dq_rows = sizeof(db_fetch_assoc("select snmp_index from host_snmp_cache where host_id=" . $_GET["id"] . " and snmp_query_id=" . $item["id"] . " group by snmp_index"));

				$status = "success";

				?>
					<td style="padding: 4px;">
						<?php print $i;?>) <?php print $item["name"];?>
					</td>
					<td>
						(<span id="<?php print $_GET["id"] . "_" . $item["id"];?>" class="verbose"><?php print "Verbose Query";?></span>)
					</td>
					<td>
						<?php form_dropdown("reindex_method_device_".get_request_var("id")."_query_".$item["id"]."_method_".$item["reindex_method"],$reindex_types,"","",$item["reindex_method"],"","","","");?>
					</td>
					<td>
						<?php print (($status == "success") ? "<span class=\"success\">" . "Success" . "</span>" : "<span class=\"fail\">" . "Fail" . "</span>");?> [<?php print $num_dq_items;?> <?php print __("Item", $num_dq_items);?>, <?php print $num_dq_rows;?> <?php print __("Row", $num_dq_rows);?>]
					</td>
					<td align='right' nowrap>
						<img id='<?php print $_GET["id"] . "_" . $item["id"];?>' class='reload buttonSmall img_filter' align='middle' src='images/reload_icon_small.gif' title='<?php print "Reload Data Query";?>' alt='<?php print "Reload";?>'>&nbsp;
						<img id='<?php print $_GET["id"] . "_" . $item["id"];?>' align='middle' class='img_filter buttonSmall qremove' src='images/delete_icon_large.gif' title='<?php print "Delete Data Query Association";?>' alt='<?php print "Delete";?>'>
					</td>
				<?php
				form_end_row();
			}
		}else{
			print "<tr><td><em>". "No associated data queries." . "</em></td></tr>";
		}

		form_alternate_row_color("dq_device" . $device["id"]);

		?>
			<td colspan="5">
				<table cellspacing="0" cellpadding="1" width="100%">
					<tr>
						<td><?php print "Add Data Query:";?>&nbsp;
							<?php form_dropdown("snmp_query_id",$available_data_queries,"name","id","","","");?>
						</td>
						<td><?php print "Re-Index Method:";?>&nbsp;
							<?php form_dropdown("reindex_method",$reindex_types,"","","1","","");?>
						</td>
						<td align="right" style="text-align:right;padding:0px;">
							&nbsp;<input id="add_dq_y" type="button" value="<?php print "Add";?>" name="add_dq_y" align="middle">
						</td>
					</tr>
				</table>
			</td>
		<?php
		form_end_row();
		print "</table></td></tr>";		/* end of html_header */
		form_hidden_box("id", (isset($device["id"]) ? $device["id"] : "0"), "");
		form_hidden_box("tab", "devices", "");
		html_end_box(FALSE);
	}

	print "</form>";		/* end of html_header */

	?>
	<script type='text/javascript'>
	$().ready(function() {
		$('.verbose').click(function(data) {
			id=$(this).attr('id').split('_');
			$.get('host.php?action=query_verbose&id='+id[1]+'&host_id='+id[0], function(data) {
				$('#cdialog').html(data);
				$('#cdialog').dialog({ title: "<?php print "Data Query Debug Log";?>", minHeight: 80, minWidth: 700, resizable: false });
			});
		}).css('cursor', 'pointer');
		$('.reload').click(function(data) {
			id=$(this).attr('id').split('_');
			$(this).attr('src', 'images/indicator.gif');
			$.get('host.php?action=query_reload&id='+id[1]+'&host_id='+id[0], function(data) {
				//re-call same tab by $.get-ting appropriate edit function
				$.get('host.php?action=ajax_edit_data_query&id='+id[0], function(data) {
					//reload the form provided by this function, thus refreshing the form
					$('#device_edit_data_query').html(data);
				});
			});
		}).css('cursor', 'pointer');
		$('.qremove').click(function(data) {
			id=$(this).attr('id').split('_');
			$.get('host.php?action=query_remove&id='+id[1]+'&host_id='+id[0], function(data) {
				//re-call same tab by $.get-ting appropriate edit function
				$.get('host.php?action=ajax_edit_data_query&id='+id[0], function(data) {
					//reload the form provided by this function, thus refreshing the form
					$('#device_edit_data_query').html(data);
				});
			});
		}).css('cursor', 'pointer');
		$('#add_dq_y').click(function(data) {
			$('#action').attr('value', 'add_dq');
			id=$('#id').attr('value');
			//pass all form parameters using "serialize" to the add function
			$.post('host.php?action=add_dq', $('#device_edit_data_query').serialize(), function(data) {
				//re-call same tab by $.get-ting appropriate edit function
				$.get('host.php?action=ajax_edit_data_query&id='+id, function(data) {
					//reload the form provided by this function, thus refreshing the form
					$('#device_edit_data_query').html(data);
				});
			});
		}); 
	});
	<?php
}

/**
 * show device filter options for device list
 */
function device_filter() {
	global $item_rows;

	html_start_box("Devices", "100", "3", "center", "host.php?action=edit&template_id=" . html_get_page_variable("template_id") . "&status=" . html_get_page_variable("status", true));
	?>
	<tr class='rowAlternate3'>
		<td>
			<form id="form_devices" action="host.php" name="form_devices" method="post">
			<table cellpadding="0" cellspacing="3">
				<tr>
					<td class="w1">
						<?php print "Search:";?>
					</td>
					<td class="w1">
						<input type="text" name="filter" size="30" value="<?php print html_get_page_variable("filter");?>">
					</td>
					<td class="w1">
						<?php print "Rows:";?>
					</td>
					<td class="w1">
						<select name="rows" onChange="applyDeviceFilterChange(document.form_devices)">
							<option value="-1"<?php if (html_get_page_variable("rows") == "-1") {?> selected<?php }?>><?php print "Default";?></option>
							<?php
							if (sizeof($item_rows) > 0) {
							foreach ($item_rows as $key => $value) {
								print "<option value='" . $key . "'"; if (html_get_page_variable("rows") == $key) { print " selected"; } print ">" . $value . "</option>\n";
							}
							}
							?>
						</select>
					</td>
					<td>
					</td>
					<td>
					</td>
					<td class="w1">
						<input type="submit" Value="<?php print "Go";?>" name="go" align="middle">
					</td>
					<td class="w1">
						<input type="button" Value="<?php print "Clear";?>" name="clear" align="middle" onClick="clearDeviceFilterChange(document.form_devices)">
					</td>
				</tr>
				<tr>
					<td class="w1">
						<?php print "Type:";?>
					</td>
					<td class="w1">
						<select name="template_id" onChange="applyDeviceFilterChange(document.form_devices)">
							<option value="-1"<?php if (html_get_page_variable("template_id") == "-1") {?> selected<?php }?>><?php print "Any";?></option>
							<option value="0"<?php if (html_get_page_variable("template_id") == "0") {?> selected<?php }?>><?php print "None";?></option>
							<?php
							$host_templates = db_fetch_assoc("select id,name from host_template order by name");

							if (sizeof($host_templates) > 0) {
							foreach ($host_templates as $host_template) {
								print "<option value='" . $host_template["id"] . "'"; if (html_get_page_variable("template_id") == $host_template["id"]) { print " selected"; } print ">" . $host_template["name"] . "</option>\n";
							}
							}
							?>
						</select>
					</td>
					<td class="w1">
						<?php print "Status:";?>
					</td>
					<td class="w1">
						<select name="status" onChange="applyDeviceFilterChange(document.form_devices)">
							<option value="-1"<?php if (html_get_page_variable("status") == "-1") {?> selected<?php }?>><?php print "Any";?></option>
							<option value="-3"<?php if (html_get_page_variable("status") == "-3") {?> selected<?php }?>><?php print "Enabled";?></option>
							<option value="-2"<?php if (html_get_page_variable("status") == "-2") {?> selected<?php }?>><?php print "Disabled";?></option>
							<option value="-4"<?php if (html_get_page_variable("status") == "-4") {?> selected<?php }?>><?php print "Not Up";?></option>
							<option value="3"<?php  if (html_get_page_variable("status") == "3") {?> selected<?php }?>><?php print "Up";?></option>
							<option value="1"<?php  if (html_get_page_variable("status") == "1") {?> selected<?php }?>><?php print "Down";?></option>
							<option value="2"<?php  if (html_get_page_variable("status") == "2") {?> selected<?php }?>><?php print "Recovering";?></option>
							<option value="0"<?php  if (html_get_page_variable("status") == "0") {?> selected<?php }?>><?php print "Unknown";?></option>
						</select>
					</td>
					<td class="w1">
						<?php print "Site:";?>
					</td>
					<td class="w1">
						<select name="site" onChange="applyDeviceFilterChange(document.form_devices)">
							<option value="-1"<?php if (html_get_page_variable("site") == "-1") {?> selected<?php }?>><?php print "All";?></option>
							<option value="0"<?php if (html_get_page_variable("site") == "0") {?> selected<?php }?>><?php print "Not Defined";?></option>
							<?php
							$sites = db_fetch_assoc("select id,name from sites order by name");

							if (sizeof($sites)) {
							foreach ($sites as $site) {
								print "<option value='" . $site["id"] . "'"; if (html_get_page_variable("site") == $site["id"]) { print " selected"; } print ">" . $site["name"] . "</option>\n";
							}
							}
							?>
						</select>
					</td>
					<td class="w1">
						<?php print "Poller:";?>
					</td>
					<td class="w1">
						<select name="poller" onChange="applyDeviceFilterChange(document.form_devices)">
							<option value="-1"<?php if (html_get_page_variable("poller") == "-1") {?> selected<?php }?>><?php print "All";?></option>
							<option value="0"<?php if (html_get_page_variable("poller") == "0") {?> selected<?php }?>><?php print "System Default";?></option>
							<?php
							$pollers = db_fetch_assoc("select id,description AS name from poller order by description");

							if (sizeof($pollers)) {
							foreach ($pollers as $poller) {
								print "<option value='" . $poller["id"] . "'"; if (html_get_page_variable("poller") == $poller["id"]) { print " selected"; } print ">" . $poller["name"] . "</option>\n";
							}
							}
							?>
						</select>
					</td>
				</tr>
			</table>
			<div><input type='hidden' name='page' value='1'></div>
			</form>
		</td>
	</tr>
	<?php
	html_end_box(false);
	?>
	<script type="text/javascript">
	<!--
	function clearDeviceFilterChange(objForm) {
		<?php print (isset($_REQUEST["tab"]) ? "strURL = '?template_id=" . html_get_page_variable("template_id") . "&id=" . html_get_page_variable("template_id") . "&action=edit&tab=" . html_get_page_variable("tab") . "';\n" : "strURL = '?template_id=-1';");?>
		strURL = strURL + '&filter=';
		strURL = strURL + '&rows=-1';
		document.location = strURL;
	}

	function applyDeviceFilterChange(objForm) {
		if (objForm.template_id.value) {
			strURL = '?template_id=' + objForm.template_id.value;
			strURL = strURL + '&filter=' + objForm.filter.value;
		}else{
			strURL = '?filter=' + objForm.filter.value;
		}
		strURL = strURL + '&status=' + objForm.status.value;
		strURL = strURL + '&rows=' + objForm.rows.value;
		strURL = strURL + '&poller=' + objForm.poller.value;
		strURL = strURL + '&site=' + objForm.site.value;
		<?php print (isset($_REQUEST["tab"]) ? "strURL = strURL + '&id=' + objForm.template_id.value + '&action=edit&tab=" . html_get_page_variable("tab") . "';\n" : "");?>
		document.location = strURL;
	}
	-->
	</script>
	<?php
}

/**
 * get device records for device list, respecting filter options
 */
function get_device_records(&$total_rows, &$rowspp) {
	/* form the 'where' clause for our main sql query */
	if (strlen(html_get_page_variable("filter"))) {
		$sql_where = "WHERE (host.hostname like '%%" . html_get_page_variable("filter") . "%%' OR host.description like '%%" . html_get_page_variable("filter") . "%%')";
	}else{
		$sql_where = "";
	}

	if (html_get_page_variable("status") == "-1") {
		/* Show all items */
	}elseif (html_get_page_variable("status") == "-2") {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " host.disabled='on'";
	}elseif (html_get_page_variable("status") == "-3") {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " host.disabled=''";
	}elseif (html_get_page_variable("status") == "-4") {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " (host.status!='3' or host.disabled='on')";
	}else {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " (host.status=" . html_get_page_variable("status") . " AND host.disabled = '')";
	}

	if (html_get_page_variable("template_id") == "-1") {
		/* Show all items */
	}elseif (html_get_page_variable("template_id") == "0") {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " host.host_template_id=0";
	}elseif (strlen(html_get_page_variable("template_id"))) {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " host.host_template_id=" . html_get_page_variable("template_id");
	}

	if (html_get_page_variable("poller") == "-1") {
		/* Show all items */
	}elseif (html_get_page_variable("poller") == "0") {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " host.poller_id=0";
	}elseif (strlen(html_get_page_variable("poller"))) {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " host.poller_id=" . html_get_page_variable("poller");
	}

	if (html_get_page_variable("site") == "-1") {
		/* Show all items */
	}elseif (html_get_page_variable("site") == "0") {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " host.site_id=0";
	}elseif (strlen(html_get_page_variable("site"))) {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " host.site_id=" . html_get_page_variable("site");
	}

	if (html_get_page_variable("rows") == "-1") {
		$rowspp = read_config_option("num_rows_device");
	}else{
		$rowspp = html_get_page_variable("rows");
	}

	$sortby = html_get_page_variable("sort_column");
	if ($sortby=="hostname") {
		$sortby = "INET_ATON(hostname)";
	}

	$total_rows = db_fetch_cell("select
		COUNT(host.id)
		from host
		$sql_where");

	$sql_query = "SELECT device.*, poller.description AS poller, sites.name AS site,
		(SELECT count(*) FROM graph_local WHERE host.id=graph_local.host_id) AS total_graphs,
		(SELECT count(*) FROM data_local WHERE host.id=data_local.host_id) AS total_datasources
		FROM host
		LEFT JOIN poller
		ON host.poller_id=poller.id
		LEFT JOIN sites
		ON host.site_id=sites.id
		$sql_where
		ORDER BY " . $sortby . " " . html_get_page_variable("sort_direction") . "
		LIMIT " . ($rowspp*(html_get_page_variable("page")-1)) . "," . $rowspp;

	return db_fetch_assoc($sql_query);
}

/**
 * display general device list
 */
function device($refresh = true) {
	global $item_rows;
	require(CACTI_INCLUDE_PATH . "/device/device_arrays.php");

	$table = New html_table;

	$table->page_variables = array(
		"page"           => array("type" => "numeric", "method" => "request", "default" => "1"),
		"rows"           => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"filter"         => array("type" => "string",  "method" => "request", "default" => ""),
		"tab"            => array("type" => "string",  "method" => "request", "default" => "", "nosession" => true),
		"poller"         => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"status"         => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"site"           => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"template_id"    => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"sort_column"    => array("type" => "string",  "method" => "request", "default" => "description"),
		"sort_direction" => array("type" => "string",  "method" => "request", "default" => "ASC")
	);

	$table->table_format = array(
		"description" => array(
			"name" => "Description",
			"link" => true,
			"filter" => true,
			"order" => "ASC",
			"href_suffix" => "#ui-tabs-1",	# always jump to "general" tab from list
		),
		"device.hostname" => array(
			"name" => "Hostname",
			"filter" => true,
			"order" => "ASC"
		),
		"total_graphs" => array(
			"name" => "Graphs",
			"order" => "ASC",
			"align" => "right"
		),
		"total_datasources" => array(
			"name" => "Data Sources",
			"order" => "ASC",
			"align" => "right"
		),
		"status" => array(
			"name" => "Status",
			"function" => "get_colored_device_status",
			"align" => "center",
			"params" => array("disabled", "status"),
			"order" => "ASC"
		),
		"status_event_count" => array(
			"name" => "Time in State",
			"order" => "ASC",
			"function" => "display_device_down_time",
			"params" => array("id", "status_event_count"),
			"align" => "right"
		),
		"cur_time" => array(
			"name" => "Current (ms)",
			"order" => "DESC",
			"format" => "round,2",
			"align" => "right"
		),
		"avg_time" => array(
			"name" => "Average (ms)",
			"order" => "DESC",
			"format" => "round,2",
			"align" => "right"
		),
		"availability" => array(
			"name" => "Availability",
			"order" => "ASC",
			"format" => "round,2",
			"align" => "right"
		),
		"polling_time" => array(
			"name" => "Poll Time",
			"order" => "DESC",
			"format" => "round,2",
			"align" => "right"
		),
		"id" => array(
			"name" => "ID",
			"align" => "right",
			"order" => "ASC"
		)
	);

	/* initialize page behavior */
	$table->href           = "host.php";
	$table->session_prefix = "sess_devices";
	$table->filter_func    = "device_filter";
	$table->refresh        = $refresh;
	$table->resizable      = true;
	$table->checkbox       = true;
	$table->sortable       = true;
	$table->actions        = array_merge($device_actions, tree_add_tree_names_to_actions_array());
	$table->table_id       = "devices";
	if (isset($_REQUEST['parent'])) {
		$table->parent    = $_REQUEST['parent'];
		$table->parent_id = get_request_var_request('id');
	}

	/* we must validate table variables */
	$table->process_page_variables();

	/* get the records */
	$table->rows = get_device_records($table->total_rows, $table->rows_per_page);

	/* display the table */
	$table->draw_table();
}

/**
 * return device field list for form engine
 */
function &device_form_list() {
	require(CACTI_INCLUDE_PATH . "/device/device_forms.php");

	return $fields_host_edit;
}

/**
 * return device availability options list for form engine
 */
function &device_availability_form_list() {
	require(CACTI_INCLUDE_PATH . "/device/device_forms.php");

	return $fields_host_edit_availability;
}

/** 
 * device_remove - removes a device
 * @param $host_id - the id of the device to remove */
function device_remove($host_id) {
	require_once(CACTI_INCLUDE_PATH . "/auth/auth_constants.php");

	db_execute("delete from host           where id=$host_id");
	db_execute("delete from host_graph       where host_id=$host_id");
	db_execute("delete from host_snmp_query  where host_id=$host_id");
	db_execute("delete from host_snmp_cache  where host_id=$host_id");
	db_execute("delete from poller_item      where host_id=$host_id");
	db_execute("delete from poller_reindex   where host_id=$host_id");
	db_execute("delete from poller_command   where command like '$host_id:%'");
	db_execute("delete from graph_tree_items where host_id=$host_id");
	db_execute("delete from user_auth_perms  where item_id=$host_id and type=" . PERM_DEVICES);

	db_execute("update data_local  set host_id=0 where host_id=$host_id");
	db_execute("update graph_local set host_id=0 where host_id=$host_id");
}

/** 
 * device_remove_multi - removes multiple devices in one call
 * @param $host_ids - an array of device id's to remove */
function device_remove_multi($host_ids) {
	require_once(CACTI_INCLUDE_PATH . "/auth/auth_constants.php");

	$devices_to_delete = "";
	$i = 0;

	if (sizeof($host_ids)) {
		/* build the list */
		foreach($host_ids as $host_id) {
			if ($i == 0) {
				$devices_to_delete .= $host_id;
			}else{
				$devices_to_delete .= ", " . $host_id;
			}

			/* poller commands go one at a time due to trashy logic */
			db_execute("DELETE FROM poller_item      WHERE host_id=$host_id");
			db_execute("DELETE FROM poller_reindex   WHERE host_id=$host_id");
			db_execute("DELETE FROM poller_command   WHERE command like '$host_id:%'");

			$i++;
		}

		db_execute("DELETE FROM host             WHERE id IN ($devices_to_delete)");
		db_execute("DELETE FROM host_graph       WHERE host_id IN ($devices_to_delete)");
		db_execute("DELETE FROM host_snmp_query  WHERE host_id IN ($devices_to_delete)");
		db_execute("DELETE FROM host_snmp_cache  WHERE host_id IN ($devices_to_delete)");

		db_execute("DELETE FROM graph_tree_items WHERE host_id IN ($devices_to_delete)");
		db_execute("DELETE FROM user_auth_perms  WHERE item_id IN ($devices_to_delete) and type=" . PERM_DEVICES);

		/* for people who choose to leave data sources around */
		db_execute("UPDATE data_local  SET host_id=0 WHERE host_id IN ($devices_to_delete)");
		db_execute("UPDATE graph_local SET host_id=0 WHERE host_id IN ($devices_to_delete)");

	}
}

/** device_dq_remove - removes a device->data query mapping
   @param $host_id - the id of the device which contains the mapping
   @param $data_query_id - the id of the data query to remove the mapping for */
function device_dq_remove($host_id, $data_query_id) {
	db_execute("delete from host_snmp_cache where snmp_query_id=$data_query_id and host_id=$host_id");
	db_execute("delete from host_snmp_query where snmp_query_id=$data_query_id and host_id=$host_id");
	db_execute("delete from poller_reindex where data_query_id=$data_query_id and host_id=$host_id");
}

/** device_gt_remove - removes a device->graph template mapping
   @param $host_id - the id of the device which contains the mapping
   @param $graph_template_id - the id of the graph template to remove the mapping for */
function device_gt_remove($host_id, $graph_template_id) {
	db_execute("delete from host_graph where graph_template_id=$graph_template_id and host_id=$host_id");
}

/** device_save - save a device to the database
 *
 * @param int $id
 * @param int $host_template_id
 * @param string $description
 * @param string $hostname
 * @param string $snmp_community
 * @param int $snmp_version
 * @param string $snmp_username
 * @param string $snmp_password
 * @param int $snmp_port
 * @param int $snmp_timeout
 * @param string $disabled
 * @param int $availability_method
 * @param int $ping_method
 * @param int $ping_port
 * @param int $ping_timeout
 * @param int $ping_retries
 * @param string $notes
 * @param string $snmp_auth_protocol
 * @param string $snmp_priv_passphrase
 * @param string $snmp_priv_protocol
 * @param string $snmp_context
 * @param int $max_oids
 * @param int $device_threads
 * @param int $site_id
 * @param int $poller_id
 * @param string $template_enabled
 * @return unknown_type
 */
function device_save($id, $host_template_id, $description, $hostname, $snmp_community, $snmp_version,
	$snmp_username, $snmp_password, $snmp_port, $snmp_timeout, $disabled, $availability_method, $ping_method, $ping_port,
	$ping_timeout, $ping_retries, $notes, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context,
	$max_oids, $device_threads, $site_id=0, $poller_id=0, $template_enabled='') {
	require_once(CACTI_LIBRARY_PATH . "/utility.php");
	require_once(CACTI_LIBRARY_PATH . "/variables.php");
	require_once(CACTI_LIBRARY_PATH . "/data_query.php");

	/* fetch some cache variables */
	if (empty($id)) {
		$_host_template_id = 0;
	}else{
		$_host_template_id = db_fetch_cell("select host_template_id from host where id=$id");
	}

	$save["id"] 			  = form_input_validate($id, "id", "^[0-9]+$", false, 3); 
# TODO: deactivated, until site and poller present
#	$save["site_id"]          = form_input_validate($site_id, "site_id", "^[0-9]+$", false, 3);
#	$save["poller_id"]        = form_input_validate($poller_id, "poller_id", "^[0-9]+$", false, 3);
	$save["host_template_id"] = form_input_validate($host_template_id, "host_template_id", "^[0-9]+$", false, 3);
	$save["description"]      = form_input_validate($description, "description", "", false, 3);
	$save["hostname"]         = form_input_validate(trim($hostname), "hostname", "", false, 3);
	$save["notes"]            = form_input_validate($notes, "notes", "", true, 3);
	$save["disabled"]         = form_input_validate($disabled, "disabled", "", true, 3);
	$save["template_enabled"] = form_input_validate($template_enabled, "template_enabled", "", true, 3);

	$save["snmp_version"]     = form_input_validate($snmp_version, "snmp_version", "", true, 3);
	$save["snmp_community"]   = form_input_validate($snmp_community, "snmp_community", "", true, 3);

	if ($save["snmp_version"] == 3) {
		$save["snmp_username"]        = form_input_validate($snmp_username, "snmp_username", "", true, 3);
		$save["snmp_password"]        = form_input_validate($snmp_password, "snmp_password", "", true, 3);
		$save["snmp_auth_protocol"]   = form_input_validate($snmp_auth_protocol, "snmp_auth_protocol", "^\[None\]|MD5|SHA$", true, 3);
		$save["snmp_priv_passphrase"] = form_input_validate($snmp_priv_passphrase, "snmp_priv_passphrase", "", true, 3);
		$save["snmp_priv_protocol"]   = form_input_validate($snmp_priv_protocol, "snmp_priv_protocol", "^\[None\]|DES|AES128$", true, 3);
		$save["snmp_context"]         = form_input_validate($snmp_context, "snmp_context", "", true, 3);
	} else {
		$save["snmp_username"]        = "";
		$save["snmp_password"]        = "";
		$save["snmp_auth_protocol"]   = "";
		$save["snmp_priv_passphrase"] = "";
		$save["snmp_priv_protocol"]   = "";
		$save["snmp_context"]         = "";
	}

	$save["snmp_port"]           = form_input_validate($snmp_port, "snmp_port", "^[0-9]+$", false, 3);
	$save["snmp_timeout"]        = form_input_validate($snmp_timeout, "snmp_timeout", "^[0-9]+$", false, 3);

	$save["availability_method"] = form_input_validate($availability_method, "availability_method", "^[0-9]+$", false, 3);
	$save["ping_method"]         = form_input_validate($ping_method, "ping_method", "^[0-9]+$", false, 3);
	$save["ping_port"]           = form_input_validate($ping_port, "ping_port", "^[0-9]+$", true, 3);
	$save["ping_timeout"]        = form_input_validate($ping_timeout, "ping_timeout", "^[0-9]+$", true, 3);
	$save["ping_retries"]        = form_input_validate($ping_retries, "ping_retries", "^[0-9]+$", true, 3);
	$save["max_oids"]            = form_input_validate($max_oids, "max_oids", "^[0-9]+$", true, 3);
	$save["device_threads"]      = form_input_validate($device_threads, "device_threads", "^[0-9]+$", true, 3);

	$save = plugin_hook_function('device_save', $save);

	$host_id = 0;

	if (!is_error_message()) {
		$host_id = sql_save($save, "host");

		if ($host_id) {
			raise_message(1);

			/* push out relavant fields to data sources using this device */
			push_out_host($host_id, 0);

			/* the device substitution cache is now stale; purge it */
			kill_session_var("sess_device_cache_array");

			/* update title cache for graph and data source */
			update_data_source_title_cache_from_host($host_id);
			update_graph_title_cache_from_host($host_id);
		}else{
			raise_message(2);
		}

		/* recache in case any snmp information was changed */
		if (!empty($id)) { /* a valid device was already existing */
			/* detect SNMP change, if current snmp parameters cannot be found in device table */
			$snmp_changed = ($id != db_fetch_cell("SELECT " .
					"id " .
					"FROM host " .
					"WHERE id=$id " .
					"AND snmp_version='$snmp_version' " .
					"AND snmp_community='$snmp_community' " .
					"AND snmp_username='$snmp_username' " .
					"AND snmp_password='$snmp_password' " .
					"AND snmp_auth_protocol='$snmp_auth_protocol' " .
					"AND snmp_priv_passphrase='$snmp_priv_passphrase' " .
					"AND snmp_priv_protocol='$snmp_priv_protocol' " .
					"AND snmp_context='$snmp_context' " .
					"AND snmp_port='$snmp_port' " .
					"AND snmp_timeout='$snmp_timeout' "));

			if ($snmp_changed) {
				/* fecth all existing snmp queries */
				$snmp_queries = db_fetch_assoc("SELECT " .
						"snmp_query_id, " .
						"reindex_method " .
						"FROM host_snmp_query " .
						"WHERE host_id=$id");

				if (sizeof($snmp_queries) > 0) {
					foreach ($snmp_queries as $snmp_query) {
						/* recache all existing snmp queries */
						run_data_query($id, $snmp_query["snmp_query_id"]);
					}
				}
			}
		}

		/* if the user changes the device template, add each snmp query associated with it */
		if (($host_template_id != $_host_template_id) && (!empty($host_template_id))) {
			$snmp_queries = db_fetch_assoc("select snmp_query_id, reindex_method from host_template_snmp_query where host_template_id=$host_template_id");

			if (sizeof($snmp_queries) > 0) {
			foreach ($snmp_queries as $snmp_query) {
				db_execute("replace into host_snmp_query (host_id,snmp_query_id,reindex_method) values ($host_id," . $snmp_query["snmp_query_id"] . "," . $snmp_query["reindex_method"] . ")");

				/* recache snmp data */
				run_data_query($host_id, $snmp_query["snmp_query_id"]);
			}
			}

			$graph_templates = db_fetch_assoc("select graph_template_id from host_template_graph where host_template_id=$host_template_id");

			if (sizeof($graph_templates) > 0) {
			foreach ($graph_templates as $graph_template) {
				db_execute("replace into host_graph (host_id,graph_template_id) values ($host_id," . $graph_template["graph_template_id"] . ")");
				plugin_hook_function('add_graph_template_to_device', array("host_id" => $host_id, "graph_template_id" => $graph_template["graph_template_id"]));
			}
			}
		}
	}

	return $host_id;
}
