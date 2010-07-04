<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2010 The Cacti Group                                 |
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

/* --------------------------
    The Save Function
   -------------------------- */

function api_device_form_save() {
	/*
	 * loop for all possible changes of reindex_method
	 * post variable is build like this
	 * 		reindex_method_device_<device_id>_query_<snmp_query_id>_method_<old_reindex_method>
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
				db_execute("replace into device_snmp_query (device_id,snmp_query_id,reindex_method) values (" . $matches[1] . "," . $matches[2] . "," . $reindex_method . ")");

				/* recache snmp data */
				run_data_query($matches[1], $matches[2]);
				$reindex_performed = true;
			}
		}
	}

	if ((!empty($_POST["add_dq_y"])) && (!empty($_POST["snmp_query_id"]))) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post("id"));
		input_validate_input_number(get_request_var_post("snmp_query_id"));
		input_validate_input_number(get_request_var_post("reindex_method"));
		/* ==================================================== */

		db_execute("replace into device_snmp_query (device_id,snmp_query_id,reindex_method) values (" . get_request_var_post("id") . "," . get_request_var_post("snmp_query_id") . "," . get_request_var_post("reindex_method") . ")");

		/* recache snmp data */
		run_data_query(get_request_var_post("id"), get_request_var_post("snmp_query_id"));

		header("Location: devices.php?action=edit&id=" . $_POST["id"]);
		exit;
	}

	if ((!empty($_POST["add_gt_y"])) && (!empty($_POST["graph_template_id"]))) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post("id"));
		input_validate_input_number(get_request_var_post("graph_template_id"));
		/* ==================================================== */

		db_execute("replace into device_graph (device_id,graph_template_id) values (" . get_request_var_post("id") . "," . get_request_var_post("graph_template_id") . ")");

		header("Location: devices.php?action=edit&id=" . $_POST["id"]);
		exit;
	}

	/* save basic device information during first run, device_template should have bee selected */
	if (isset($_POST["save_basic_device"])) {
		/* device template was given, so fetch defaults from it */
		$use_template = false;
		if ($_POST["device_template_id"] != 0) {
			$device_template = db_fetch_row("SELECT *
				FROM device_template
				WHERE id=" . $_POST["device_template_id"]);
			if (($device_template["override_defaults"] == CHECKED) &&
				(($device_template["override_permitted"] == CHECKED) &&
				($_POST["template_enabled"] == CHECKED)) || ($device_template["override_permitted"] != CHECKED)) {
				$use_template = true;
				$device_template["template_enabled"] = CHECKED;
			}
		}

		if (!$use_template) {
			$device_template["snmp_community"]        = get_request_var_post("snmp_community");
			$device_template["snmp_version"]          = get_request_var_post("snmp_version");
			$device_template["snmp_username"]         = get_request_var_post("snmp_username");
			$device_template["snmp_password"]         = get_request_var_post("snmp_password");
			$device_template["snmp_port"]             = get_request_var_post("snmp_port");
			$device_template["snmp_timeout"]          = get_request_var_post("snmp_timeout");
			$device_template["availability_method"]   = get_request_var_post("availability_method");
			$device_template["ping_method"]           = get_request_var_post("ping_method");
			$device_template["ping_port"]             = get_request_var_post("ping_port");
			$device_template["ping_timeout"]          = get_request_var_post("ping_timeout");
			$device_template["ping_retries"]          = get_request_var_post("ping_retries");
			$device_template["snmp_auth_protocol"]    = get_request_var_post("snmp_auth_protocol");
			$device_template["snmp_priv_passphrase"]  = get_request_var_post("snmp_priv_passphrase");
			$device_template["snmp_priv_protocol"]    = get_request_var_post("snmp_priv_protocol");
			$device_template["snmp_context"]          = get_request_var_post("snmp_context");
			$device_template["max_oids"]              = get_request_var_post("max_oids");
			$device_template["device_threads"]        = get_request_var_post("device_threads");
			$device_template["template_enabled"]      = "";
		}

		$device_template["notes"]    = ""; /* no support for notes in a device template */
		$device_template["disabled"] = ""; /* no support for disabling in a device template */
		$device_id = api_device_save($_POST["id"], $_POST["site_id"], $_POST["poller_id"], $_POST["device_template_id"], $_POST["description"],
			get_request_var_post("hostname"), $device_template["snmp_community"], $device_template["snmp_version"],
			$device_template["snmp_username"], $device_template["snmp_password"],
			$device_template["snmp_port"], $device_template["snmp_timeout"],
			$device_template["disabled"],
			$device_template["availability_method"], $device_template["ping_method"],
			$device_template["ping_port"], $device_template["ping_timeout"],
			$device_template["ping_retries"], $device_template["notes"],
			$device_template["snmp_auth_protocol"], $device_template["snmp_priv_passphrase"],
			$device_template["snmp_priv_protocol"], $device_template["snmp_context"], $device_template["max_oids"],
			$device_template["device_threads"], $device_template["template_enabled"]);

		header("Location: devices.php?action=edit&id=" . (empty($device_id) ? $_POST["id"] : $device_id));
		exit;
	}

	if ((isset($_POST["save_component_device"])) && (empty($_POST["add_dq_y"]))) {
		if (get_request_var_post("snmp_version") == 3 && (get_request_var_post("snmp_password") != get_request_var_post("snmp_password_confirm"))) {
			raise_message(4);
		}else{
			$device_id = api_device_save($_POST["id"], $_POST["site_id"], $_POST["poller_id"], $_POST["device_template_id"], $_POST["description"],
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

		header("Location: devices.php?action=edit&id=" . (empty($device_id) ? $_POST["id"] : $device_id));
		exit;
	}
}

/* ------------------------
    The "actions" function
   ------------------------ */

function api_device_form_actions() {
	require_once(CACTI_BASE_PATH . "/lib/device/device_info.php");
	require(CACTI_BASE_PATH . "/include/graph_tree/graph_tree_arrays.php");

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

				db_execute("update device set disabled='' where id='" . $selected_items[$i] . "'");

				/* update poller cache */
				$data_sources = db_fetch_assoc("select id from data_local where device_id='" . $selected_items[$i] . "'");
				$poller_items = array();

				if (sizeof($data_sources) > 0) {
					foreach ($data_sources as $data_source) {
						$local_data_ids[] = $data_source["id"];
						$poller_items     = array_merge($poller_items, update_poller_cache($data_source["id"]));
					}
				}

				poller_update_poller_cache_from_buffer($local_data_ids, $poller_items);
			}
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_DISABLE) { /* Disable Selected Devices */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				db_execute("update device set disabled='on' where id='" . $selected_items[$i] . "'");

				/* update poller cache */
				db_execute("delete from poller_item where device_id='" . $selected_items[$i] . "'");
				db_execute("delete from poller_reindex where device_id='" . $selected_items[$i] . "'");
			}
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_CHANGE_SNMP_OPTIONS) { /* change snmp options */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				reset($fields_device_edit);
				while (list($field_name, $field_array) = each($fields_device_edit)) {
					if (isset($_POST["t_$field_name"])) {
						db_execute("update device set $field_name = '" . $_POST[$field_name] . "' where id='" . $selected_items[$i] . "'");
					}
				}

				push_out_device($selected_items[$i]);
			}
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_CLEAR_STATISTICS) { /* Clear Statisitics for Selected Devices */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				db_execute("update device set min_time = '9.99999', max_time = '0', cur_time = '0',	avg_time = '0',
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
						db_execute("update device set $field_name = '" . $_POST[$field_name] . "' where id='" . $selected_items[$i] . "'");
					}
				}

				push_out_device($selected_items[$i]);
			}
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_CHANGE_POLLER) { /* change poller */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				reset($fields_device_edit);
				while (list($field_name, $field_array) = each($fields_device_edit)) {
					if (isset($_POST["$field_name"])) {
						db_execute("update device set $field_name = '" . $_POST[$field_name] . "' where id='" . $selected_items[$i] . "'");
					}
				}

				push_out_device($selected_items[$i]);
			}
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_CHANGE_SITE) { /* change site */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				reset($fields_device_edit);
				while (list($field_name, $field_array) = each($fields_device_edit)) {
					if (isset($_POST["$field_name"])) {
						db_execute("update device set $field_name = '" . $_POST[$field_name] . "' where id='" . $selected_items[$i] . "'");
					}
				}

				push_out_device($selected_items[$i]);
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
					where " . array_to_sql_or($selected_items, "data_local.device_id"));

				if (sizeof($data_sources) > 0) {
				foreach ($data_sources as $data_source) {
					$data_sources_to_act_on[] = $data_source["local_data_id"];
				}
				}

				if (get_request_var_post("delete_type") == 2) {
					$graphs = db_fetch_assoc("select
						graph_local.id as local_graph_id
						from graph_local
						where " . array_to_sql_or($selected_items, "graph_local.device_id"));

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
					api_data_source_disable_multi($data_sources_to_act_on);

					break;
				case '2': /* delete graphs/data sources tied to this device */
					api_data_source_remove_multi($data_sources_to_act_on);

					api_graph_remove_multi($graphs_to_act_on);

					break;
			}

			api_device_remove_multi($devices_to_act_on);
		}elseif (preg_match("/^tr_([0-9]+)$/", get_request_var_post("drp_action"), $matches)) { /* place on tree */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				input_validate_input_number(get_request_var_post("tree_id"));
				input_validate_input_number(get_request_var_post("tree_item_id"));
				/* ==================================================== */

				api_tree_item_save(0, get_request_var_post("tree_id"), TREE_ITEM_TYPE_DEVICE, get_request_var_post("tree_item_id"), "", 0, read_graph_config_option("default_rra_id"), $selected_items[$i], 1, 1, false);
			}
		} else {
			api_plugin_hook_function('device_action_execute', get_request_var_post('drp_action'));
		}

		header("Location: devices.php");
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

			$device_list .= "<li>" . db_fetch_cell("select description from device where id=" . $matches[1]) . "</li>";
			$device_array[] = $matches[1];
		}
	}

	include_once(CACTI_BASE_PATH . "/include/top_header.php");

	/* add a list of tree names to the actions dropdown */
	if (isset($device_actions)) {
		$device_actions = array_merge($device_actions, api_tree_add_tree_names_to_actions_array());
	}else{
		$device_actions = api_tree_add_tree_names_to_actions_array();
	}

	$device_actions[ACTION_NONE] = __("None");

	print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='device_edit_actions'>\n";
	html_start_box("<strong>" . $device_actions{get_request_var_post("drp_action")} . "</strong>", "60", "3", "center", "");

	if (isset($device_array) && sizeof($device_array)) {
		if (get_request_var_post("drp_action") === ACTION_NONE) { /* NONE */
			print "	<tr>
						<td class='textArea'>
							<p>" . __("You did not select a valid action. Please select 'Return' to return to the previous menu.") . "</p>
						</td>
					</tr>\n";
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_ENABLE) { /* Enable Devices */
			print "	<tr>
					<td colspan='2' class='textArea'>
						<p>" . __("When you click 'Continue', the following Device(s) will be enabled.") . "</p>
						<p><ul>$device_list</ul></p>
					</td>
					</tr>";

			$title = __("Enable Device(s)");
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_DISABLE) { /* Disable Devices */
			print "	<tr>
					<td colspan='2' class='textArea'>
						<p>" . __("When you click 'Continue', the following Device(s) will be disabled.") . "</p>
						<p><ul>$device_list</ul></p>
					</td>
					</tr>";

			$title = __("Disable Device(s)");
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_CHANGE_SNMP_OPTIONS) { /* change snmp options */
			print "	<tr>
					<td colspan='2' class='textArea'>
						<p>" . __("When you click 'Continue', the following Device(s) will have their SNMP settings changed.  Make sure you check the box next to the fields you want to update, and fill in the new values before continuing.") . "</p>
						<p><ul>$device_list</ul></p>
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
						"friendly_name" => __("Update this Field"),
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

			$title = __("Change Device(s) SNMP options");
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_CHANGE_AVAILABILITY_OPTIONS) { /* change availability options */
			print "	<tr>
					<td colspan='2' class='textArea'>
						<p>" . __("When you click 'Continue', the following Device(s) Availability options will be changed.  Make sure you check the box next to the fields you want to update, and fill in the new values before continuing.") . "</p>
						<p><ul>$device_list</ul></p>
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
						"friendly_name" => __("Update this Field"),
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

			$title = __("Change Device(s) Availability options");
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_CLEAR_STATISTICS) { /* Clear Statisitics for Selected Devices */
			print "	<tr>
					<td colspan='2' class='textArea'>
						<p>" . __("When you click 'Continue', the following Device(s) statistics will be reset.") . "</p>
						<p><ul>$device_list</ul></p>
					</td>
					</tr>";

			$title = __("Clear Device(s) Statistics");
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_DELETE) { /* delete */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Device(s) will be deleted.") . "</p>
						<p><ul>$device_list</ul></p>";
						form_radio_button("delete_type", "2", "1", __("Leave all Graph(s) and Data Source(s) untouched.  Data Source(s) will be disabled however."), "1"); print "<br>";
						form_radio_button("delete_type", "2", "2", __("Delete all associated <strong>Graph(s)</strong> and <strong>Data Source(s)</strong>."), "1"); print "<br>";
						print "</td></tr>
					</td>
				</tr>\n";

			$title = __("Delete Device(s)");
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_CHANGE_POLLER) { /* Change Poller */
			print "	<tr>
					<td colspan='2' class='textArea'>
						<p>" . __("When you click 'Continue', the following Device(s) will be re-associated with the Poller below.") . "</p>
						<p><ul>$device_list</ul></p>
					</td>
					</tr>";

			$form_array = array();
			$field_name = "poller_id";
			$form_array += array($field_name => $fields_device_edit["poller_id"]);
			$form_array[$field_name]["description"] = __("Please select the new Poller for the selected Device(s).");

			draw_edit_form(
				array(
					"config" => array("no_form_tag" => true),
					"fields" => $form_array
					)
				);

			$title = __("Change Device(s) Poller");
		}elseif (get_request_var_post("drp_action") === DEVICE_ACTION_CHANGE_SITE) { /* Change Site */
			print "	<tr>
					<td colspan='2' class='textArea'>
						<p>" . __("When you click 'Continue', the following Device(s) will be re-associated with the Site below.") . "</p>
						<p><ul>$device_list</ul></p>
					</td>
					</tr>";

			$form_array = array();
			$field_name = "site_id";
			$form_array += array($field_name => $fields_device_edit["site_id"]);
			$form_array[$field_name]["description"] = __("Please select the new Site for the selected Device(s).");

			draw_edit_form(
				array(
					"config" => array("no_form_tag" => true),
					"fields" => $form_array
					)
				);


			$title = __("Change Device(s) Site");
		}elseif (preg_match("/^tr_([0-9]+)$/", get_request_var_post("drp_action"), $matches)) { /* place on tree */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Device(s) will be placed under the Tree Branch selected below.") . "</p>
						<p><ul>$device_list</ul></p>
						<p><strong>" . __("Destination Branch:") . "</strong><br>"; grow_dropdown_tree($matches[1], "tree_item_id", "0"); print "</p>
					</td>
				</tr>\n
				<input type='hidden' name='tree_id' value='" . $matches[1] . "'>\n
				";

			$title = __("Place Device(s) on a Tree");
		} else {
			$save['drp_action'] = $_POST['drp_action'];
			$save['device_list'] = $device_list;
			$save['device_array'] = (isset($device_array)? $device_array : array());
			$save['title'] = '';
			api_plugin_hook_function('device_action_prepare', $save);

			if (strlen($save['title'])) {
				$title = $save['title'];
			}else{
				$title = '';
			}
		}
	} else {
		print "	<tr>
				<td class='textArea'>
					<p>" . __("You must first select a Device.  Please select 'Return' to return to the previous menu.") . "</p>
				</td>
			</tr>\n";
	}

	if (!isset($device_array) || get_request_var_post("drp_action") === ACTION_NONE) {
		form_return_button();
	}else{
		form_continue(serialize($device_array), get_request_var_post("drp_action"));
	}

	html_end_box();

	include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
}

/* -------------------
    Data Query Functions
   ------------------- */

function device_reload_query() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("device_id"));
	/* ==================================================== */

	run_data_query(get_request_var("device_id"), get_request_var("id"));
}

function device_remove_query() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("device_id"));
	/* ==================================================== */

	api_device_dq_remove(get_request_var("device_id"), get_request_var("id"));
}

function device_remove_gt() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("device_id"));
	/* ==================================================== */

	api_device_gt_remove(get_request_var("device_id"), get_request_var("id"));
}

/* ---------------------
    Host Functions
   --------------------- */

function device_remove() {
	global $config;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	if ((read_config_option("deletion_verification") == CHECKED) && (!isset($_GET["confirm"]))) {
		include(CACTI_BASE_PATH . "/include/top_header.php");
		form_confirm(__("Are You Sure?"), __("Are you sure you want to delete the device") . " <strong>'" . db_fetch_cell("select description from device where id=" . $_GET["id"]) . "'</strong>?", "devices.php", "devices.php?action=remove&id=" . $_GET["id"]);
		include(CACTI_BASE_PATH . "/include/bottom_footer.php");
		exit;
	}

	if ((read_config_option("deletion_verification") == "") || (isset($_GET["confirm"]))) {
		api_device_remove(get_request_var("id"));
	}
}

function device_edit() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("device_id"));
	/* ==================================================== */

	$device_tabs = array(
		"general" => __("General"),
		"newgraphs" => __("New Graphs"),
		"graphs" => __("Graphs"),
		"datasources" => __("Data Sources")
	);

	if (!empty($_REQUEST["id"])) {
		$device         = db_fetch_row("select * from device where id=" . $_REQUEST["id"]);
		$device_text    = "<strong>" . $device["description"] . "(" . $device["hostname"] . ")</strong>";
		$header_label = __("[edit: ") . $device["description"] . "]";
	}elseif (!empty($_GET["device_id"])) {
		$_REQUEST["id"]   = $_REQUEST["device_id"];
		$device         = db_fetch_row("select * from device where id=" . $_REQUEST["id"]);
		$device_text    = "<strong>" . $device["description"] . "(" . $device["hostname"] . ")</strong>";
		$header_label = __("[edit: ") . $device["description"] . "]";
	}else{
		$header_label = __("[new]");
		$device_text    = __("New Device");
		$device         = "";
	}

	/* set the default settings category */
	if (!isset($_REQUEST["tab"])) {
		/* there is no selected tab; select the first one */
		$current_tab = array_keys($device_tabs);
		$current_tab = $current_tab[0];
	}else{
		$current_tab = $_REQUEST["tab"];
	}

	/* draw the categories tabs on the top of the page */
	print "<table width='100%' cellspacing='0' cellpadding='0' align='center'><tr>";
	print "<td><div class='tabs'>";

	if (sizeof($device_tabs) > 0) {
	foreach (array_keys($device_tabs) as $tab_short_name) {
		print "<div class='tabDefault'><a " . (($tab_short_name == $current_tab) ? "class='tabSelected'" : "class='tabDefault'") . " href='" . htmlspecialchars("devices.php?action=edit" . (isset($_REQUEST['id']) ? "&id=" . $_REQUEST['id'] . "&device_id=" . $_REQUEST['id']: "") . "&tab=$tab_short_name") . "'>" . $device_tabs[$tab_short_name] . "</a></div>";

		if (!isset($_REQUEST["id"])) break;
	}
	}
	print "</div></td></tr></table>";

	if (!isset($_REQUEST["tab"])) {
		$_REQUEST["tab"] = "general";
	}

	switch (get_request_var_request("tab")) {
		case "newgraphs":
			include_once(CACTI_BASE_PATH . "/lib/graph/graphs_new_form.php");
			include_once(CACTI_BASE_PATH . "/lib/data_query.php");
			include_once(CACTI_BASE_PATH . "/lib/utility.php");
			include_once(CACTI_BASE_PATH . "/lib/sort.php");
			include_once(CACTI_BASE_PATH . "/lib/html_form_template.php");
			include_once(CACTI_BASE_PATH . "/lib/template.php");

			graphs_new();

			break;
		case "datasources":
			include_once(CACTI_BASE_PATH . "/lib/data_source/data_source_form.php");
			include_once(CACTI_BASE_PATH . "/lib/utility.php");
			include_once(CACTI_BASE_PATH . "/lib/api_graph.php");
			include_once(CACTI_BASE_PATH . "/lib/api_data_source.php");
			include_once(CACTI_BASE_PATH . "/lib/template.php");
			include_once(CACTI_BASE_PATH . "/lib/html_form_template.php");
			include_once(CACTI_BASE_PATH . "/lib/rrd.php");
			include_once(CACTI_BASE_PATH . "/lib/data_query.php");

			data_source();

			break;
		case "graphs":
			include_once(CACTI_BASE_PATH . "/lib/graph/graphs_form.php");
			include_once(CACTI_BASE_PATH . "/lib/utility.php");
			include_once(CACTI_BASE_PATH . "/lib/api_graph.php");
			include_once(CACTI_BASE_PATH . "/lib/api_tree.php");
			include_once(CACTI_BASE_PATH . "/lib/api_data_source.php");
			include_once(CACTI_BASE_PATH . "/lib/template.php");
			include_once(CACTI_BASE_PATH . "/lib/html_tree.php");
			include_once(CACTI_BASE_PATH . "/lib/html_form_template.php");
			include_once(CACTI_BASE_PATH . "/lib/rrd.php");
			include_once(CACTI_BASE_PATH . "/lib/data_query.php");

			graph();

			break;
		default:
			device_display_general($device, $device_text);

			break;
	}
}

function device_display_general($device, $device_text) {
	require(CACTI_BASE_PATH . "/include/data_query/data_query_arrays.php");
	require_once(CACTI_BASE_PATH . "/lib/device/device_info.php");

	if (isset($device["id"])) {
		html_start_box($device_text, "100", "3", "center", "", true);
		?>
			<tr class="rowAlternate2">
				<?php if (($device["availability_method"] == AVAIL_SNMP) ||
					($device["availability_method"] == AVAIL_SNMP_AND_PING) ||
					($device["availability_method"] == AVAIL_SNMP_OR_PING)) { ?>
				<td class="textInfo">
					<?php print __("SNMP Information");?><br>
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
						/* 0000937: System output in devices.php poor for Alcatel */
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
							print "<strong>" . __("Uptime:")   . " </strong> $snmp_uptime";
							print "&nbsp;($days days, $hours hours, $minutes minutes)<br>\n";
							print "<strong>" . __("Hostname:") . " </strong> $snmp_hostname<br>\n";
							print "<strong>" . __("Location:") . " </strong> $snmp_location<br>\n";
							print "<strong>" . __("Contact:")  . " </strong> $snmp_contact<br>\n";
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
					<?php print __("Ping Results");?><br>
					<span class="<?php $ping_class ?>">
					<?php print $ping->ping_response; ?>
					</span>
				</td>
				<?php }else if ($device["availability_method"] == AVAIL_NONE) { ?>
				<td class="textInfo">
					<?php print __("No Availability Check In Use");?><br>
				</td>
				<?php } ?>
			</tr>
		<?php
	}else{
		html_start_box($device_text, "100", "3", "center", "", false);
	}

	html_end_box(FALSE);

	print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='device_edit_settings'>\n";
	html_start_box("<strong>" . __("General Settings") . "</strong>", "100", 0, "center", "", true);
	$header_items = array(
		array("name" => __("Field")),
		array("name" => __("Value"))
	);
	print "<tr><td>";
	html_header($header_items, 1, false, 'device');

	/* preserve the device template id if passed in via a GET variable */
	$fields_device_edit = device_form_list();
	if (!empty($_GET["template_id"])) {
//		$fields_device_edit["device_template_id"]["value"] = $_GET["template_id"];
//		$fields_device_edit["device_template_id"]["method"] = "hidden";
	}

	/* if we are creating a device and have changed templates set that value */
	if (!isset($device["id"])) {
		if (!empty($_GET["template_id"])) {
			$device["device_template_id"] = $_GET["template_id"];
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
	if (!isset($device["id"])) {
		$template_settings = db_fetch_row("SELECT * FROM device_template WHERE id=" . $_REQUEST["template_id"]);
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
		if (db_fetch_cell("SELECT override_defaults FROM device_template WHERE id=" . $device["device_template_id"]) == CHECKED) {
			$propagation_allowed = true;
		}
	}

	/* for a given device, display all availability options as well */
	draw_edit_form(array(
		"config" => array("form_name" => "chk", "no_form_tag" => true),
		"fields" => inject_form_variables(device_availability_form_list(), (isset($template_settings) ? $template_settings : $device))
		));

	print "</table></td></tr>";		/* end of html_header */
	html_end_box(!isset($device["id"]));
	form_hidden_box("id", (isset($device["id"]) ? $device["id"] : "0"), "");
	form_hidden_box("hidden_device_template_id", (isset($device["device_template_id"]) ? $device["device_template_id"] : "0"), "");
	form_hidden_box("save_basic_device", "1", "");
	form_hidden_box("save_component_device", "1", "");
	form_hidden_box("override_permitted", ($override_permitted ? "true":"false"), "");
	form_hidden_box("propagation_allowed", ($propagation_allowed ? "true":"false"), "");

	/* javascript relates to availability options, so include it only for existing devices */
	?>
	<script type="text/javascript">
	<!--

	// default snmp information
	var snmp_community       = document.getElementById('snmp_community').value;
	var snmp_username        = document.getElementById('snmp_username').value;
	var snmp_password        = document.getElementById('snmp_password').value;
	var snmp_auth_protocol   = document.getElementById('snmp_auth_protocol').value;
	var snmp_priv_passphrase = document.getElementById('snmp_priv_passphrase').value;
	var snmp_priv_protocol   = document.getElementById('snmp_priv_protocol').value;
	var snmp_context         = document.getElementById('snmp_context').value;
	var snmp_port            = document.getElementById('snmp_port').value;
	var snmp_timeout         = document.getElementById('snmp_timeout').value;
	var max_oids             = document.getElementById('max_oids').value;

	// default ping methods
	var ping_method    = document.getElementById('ping_method').value;
	var ping_port      = document.getElementById('ping_port').value;
	var ping_timeout   = document.getElementById('ping_timeout').value;
	var ping_retries   = document.getElementById('ping_retries').value;

	var availability_methods = document.getElementById('availability_method').options;
	var num_methods          = document.getElementById('availability_method').length;
	var selectedIndex        = document.getElementById('availability_method').selectedIndex;

	var agent = navigator.userAgent;
	agent = agent.match("MSIE");

	function setPingVisibility() {
		availability_method = document.getElementById('availability_method').value;
		ping_method         = document.getElementById('ping_method').value;

		/* debugging, uncomment as required */
		//alert("The availability method is '" + availability_method + "'");
		//alert("The ping method is '" + ping_method + "'");

		switch(availability_method) {
		case "0": // none
			document.getElementById('row_ping_method').style.display  = "none";
			document.getElementById('row_ping_port').style.display    = "none";
			document.getElementById('row_ping_timeout').style.display = "none";
			document.getElementById('row_ping_retries').style.display = "none";

			break;
		case "2": // snmp
			document.getElementById('row_ping_method').style.display  = "none";
			document.getElementById('row_ping_port').style.display    = "none";
			document.getElementById('row_ping_timeout').style.display = "";
			document.getElementById('row_ping_retries').style.display = "";

			break;
		default: // ping ok
			switch(ping_method) {
			case "1": // ping icmp
				document.getElementById('row_ping_method').style.display  = "";
				document.getElementById('row_ping_port').style.display    = "none";
				document.getElementById('row_ping_timeout').style.display = "";
				document.getElementById('row_ping_retries').style.display = "";

				break;
			case "2": // ping udp
			case "3": // ping tcp
				document.getElementById('row_ping_method').style.display  = "";
				document.getElementById('row_ping_port').style.display    = "";
				document.getElementById('row_ping_timeout').style.display = "";
				document.getElementById('row_ping_retries').style.display = "";

				break;
			}

			break;
		}
	}

	function addSelectItem(item, formObj) {
		if (agent != "MSIE") {
			formObj.add(item,null); // standards compliant
		}else{
			formObj.add(item);      // IE only
		}
	}

	function setAvailability(type) {
		/* get the availability structure */
		var am=document.getElementById('availability_method');

		/* get current selectedIndex */
		selectedIndex = document.getElementById('availability_method').selectedIndex;

		/* debugging uncomment as required */
		//alert("The selectedIndex is '" + selectedIndex + "'");
		//alert("The array length is '" + am.length + "'");

		switch(type) {
		case "NoSNMP":
			/* remove snmp options */
			if (am.length == 4) {
				am.remove(1);
				am.remove(1);
				am.remove(1);
			}

			/* set the index to something valid, like "ping" */
			if (selectedIndex > 1) {
				am.selectedIndex=1;
			}

			break;
		case "All":
			/* restore all options */
			if (am.length == 2) {
				am.remove(0);
				am.remove(0);

				var a=document.createElement('option');
				var b=document.createElement('option');
				var c=document.createElement('option');
				var d=document.createElement('option');
				var e=document.createElement('option');

				a.value="0";
				a.text="None";
				addSelectItem(a,am);

				b.value="1";
				b.text="Ping and SNMP";
				addSelectItem(b,am);

				e.value="4";
				e.text="Ping or SNMP";
				addSelectItem(e,am);

				c.value="2";
				c.text="SNMP";
				addSelectItem(c,am);

				d.value="3";
				d.text="Ping";
				addSelectItem(d,am);

				/* restore the correct index number */
				if (selectedIndex == 0) {
					am.selectedIndex = 0;
				}else{
					am.selectedIndex = 3;
				}
			}

			break;
		}

		setAvailabilityVisibility(type, am.selectedIndex);
		setPingVisibility();
	}

	function setAvailabilityVisibility(type, selectedIndex) {
		switch(type) {
		case "NoSNMP":
			switch(selectedIndex) {
			case "0": // availability none
				document.getElementById('row_ping_method').style.display="none";
				document.getElementById('ping_method').value=0;

				break;
			case "1": // ping
				document.getElementById('row_ping_method').style.display="";
				document.getElementById('ping_method').value=ping_method;

				break;
			}
		case "All":
			switch(selectedIndex) {
			case "0": // availability none
				document.getElementById('row_ping_method').style.display="none";
				document.getElementById('ping_method').value=0;

				break;
			case "1": // ping and snmp
			case "3": // ping
			case "4": // ping or snmp
				if ((document.getElementById('row_ping_method').style.display == "none") ||
					(document.getElementById('row_ping_method').style.display == undefined)) {
					document.getElementById('ping_method').value=ping_method;
					document.getElementById('row_ping_method').style.display="";
				}

				break;
			case "2": // snmp
				document.getElementById('row_ping_method').style.display="none";
				document.getElementById('ping_method').value="0";

				break;
			}
		}
	}

	function changeHostForm() {
		snmp_version        = document.getElementById('snmp_version').value;

		switch(snmp_version) {
		case "0":
			setAvailability("NoSNMP");
			setSNMP("None");

			break;
		case "1":
		case "2":
			setAvailability("All");
			setSNMP("v1v2");

			break;
		case "3":
			setAvailability("All");
			setSNMP("v3");

			break;
		}
	}

	function setSNMP(snmp_type) {
		switch(snmp_type) {
		case "None":
			document.getElementById('row_snmp_username').style.display        = "none";
			document.getElementById('row_snmp_password').style.display        = "none";
			document.getElementById('row_snmp_community').style.display       = "none";
			document.getElementById('row_snmp_auth_protocol').style.display   = "none";
			document.getElementById('row_snmp_priv_passphrase').style.display = "none";
			document.getElementById('row_snmp_priv_protocol').style.display   = "none";
			document.getElementById('row_snmp_context').style.display         = "none";
			document.getElementById('row_snmp_port').style.display            = "none";
			document.getElementById('row_snmp_timeout').style.display         = "none";
			document.getElementById('row_max_oids').style.display             = "none";

			break;
		case "v1v2":
			document.getElementById('row_snmp_username').style.display        = "none";
			document.getElementById('row_snmp_password').style.display        = "none";
			document.getElementById('row_snmp_community').style.display       = "";
			document.getElementById('row_snmp_auth_protocol').style.display   = "none";
			document.getElementById('row_snmp_priv_passphrase').style.display = "none";
			document.getElementById('row_snmp_priv_protocol').style.display   = "none";
			document.getElementById('row_snmp_context').style.display         = "none";
			document.getElementById('row_snmp_port').style.display            = "";
			document.getElementById('row_snmp_timeout').style.display         = "";
			document.getElementById('row_max_oids').style.display             = "";

			break;
		case "v3":
			document.getElementById('row_snmp_username').style.display        = "";
			document.getElementById('row_snmp_password').style.display        = "";
			document.getElementById('row_snmp_community').style.display       = "none";
			document.getElementById('row_snmp_auth_protocol').style.display   = "";
			document.getElementById('row_snmp_priv_passphrase').style.display = "";
			document.getElementById('row_snmp_priv_protocol').style.display   = "";
			document.getElementById('row_snmp_context').style.display         = "";
			document.getElementById('row_snmp_port').style.display            = "";
			document.getElementById('row_snmp_timeout').style.display         = "";
			document.getElementById('row_max_oids').style.display             = "";

			break;
		}
	}

	function toggleAvailabilityAndSnmp(template_enabled) {
		if (!template_enabled && $('#override_permitted').val() == 'true') {
			$('#override_permitted').removeAttr("disabled");
			$('#availability_header').removeAttr("disabled");
			$('#availability_method').removeAttr("disabled");
			$('#ping_method').removeAttr("disabled");
			$('#ping_port').removeAttr("disabled");
			$('#ping_timeout').removeAttr("disabled");
			$('#ping_retries').removeAttr("disabled");
			$('#snmp_spacer').removeAttr("disabled");
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
			$('#override_permitted').attr("disabled","disabled");
			$('#availability_header').attr("disabled","disabled");
			$('#availability_method').attr("disabled","disabled");
			$('#ping_method').attr("disabled","disabled");
			$('#ping_port').attr("disabled","disabled");
			$('#ping_timeout').attr("disabled","disabled");
			$('#ping_retries').attr("disabled","disabled");
			$('#snmp_spacer').attr("disabled","disabled");
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
		toggleAvailabilityAndSnmp(document.getElementById('template_enabled').checked);

		/* Hide options when override is turned off */
		$("#template_enabled").change(function () {
			toggleAvailabilityAndSnmp(this.checked);
		});

		if ($('#id').val() == 0) {
			$('#device_template_id').change(function() {
				document.location='devices.php?action=edit&template_id='+this.value+'&status=-1'
			});
		}
	});

	-->
	</script>
	<?php

	if ((isset($_GET["display_dq_details"])) && (isset($_SESSION["debug_log"]["data_query"]))) {
		html_start_box("<strong>" . __("Data Query Debug Information") . "</strong>", "100", "3", "center", "", true);

		print "<tr><td><span class=\"log\">" . debug_log_return("data_query") . "</span></td></tr>";

		html_end_box(false);
	}

	if (isset($device["id"])) {
		html_start_box("<strong>". __("Associated Graph Templates") . "</strong>", "100", 0, "center", "", true);
		print "<tr><td>";
		html_header(array(array("name" => __("Graph Template Name")), array("name" => __("Status"))), 2);

		$selected_graph_templates = db_fetch_assoc("select
			graph_templates.id,
			graph_templates.name
			from (graph_templates,device_graph)
			where graph_templates.id=device_graph.graph_template_id
			and device_graph.device_id=" . $_GET["id"] . "
			order by graph_templates.name");

		$available_graph_templates = db_fetch_assoc("SELECT
			graph_templates.id, graph_templates.name
			FROM snmp_query_graph RIGHT JOIN graph_templates
			ON (snmp_query_graph.graph_template_id = graph_templates.id)
			WHERE (((snmp_query_graph.name) Is Null)) ORDER BY graph_templates.name");

		/* omit those graph_templates, that have already been associated */
		$keeper = array();
		foreach ($available_graph_templates as $item) {
			if (sizeof(db_fetch_assoc("SELECT graph_template_id FROM device_graph " .
					" WHERE ((device_id=" . $_GET["id"] . ")" .
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
			$is_being_graphed = (sizeof(db_fetch_assoc("select id from graph_local where graph_template_id=" . $item["id"] . " and device_id=" . $_GET["id"])) > 0) ? true : false;

			?>
				<td style="padding: 4px;">
					<strong><?php print $i;?>)</strong> <?php print $item["name"];?>
				</td>
				<td>
					<?php print (($is_being_graphed == true) ? "<span class=\"success\">" . __("Is Being Graphed") . "</span> (<a href='" . htmlspecialchars("graphs.php?action=edit&id=" . db_fetch_cell("select id from graph_local where graph_template_id=" . $item["id"] . " and device_id=" . get_request_var("id") . " limit 0,1")) . "'>" . __("Edit") . "</a>)" : "<span class=\"unknown\">" . __("Not Being Graphed") . "</span>");?>
				</td>
				<td align='right' nowrap>
					<a href='devices.php?action=gt_remove&amp;id=<?php print $item["id"];?>&amp;device_id=<?php print $_GET["id"];?>'><img align='middle' class='buttonSmall' src='images/delete_icon_large.gif' title='<?php print __("Delete Graph Template Association");?>' alt='<?php print __("Delete");?>'></a>
				</td>
			<?php
			form_end_row();
		}
		}else{
			print "<tr><td><em>" . __("No Associated Graph Templates.") . "</em></td></tr>";
		}

		form_alternate_row_color("gt_device" . $device["id"]);
		?>
			<td colspan="4">
				<table cellspacing="0" cellpadding="1" width="100%">
					<tr>
					<td nowrap><?php print __("Add Graph Template:");?>&nbsp;
						<?php form_dropdown("graph_template_id",$available_graph_templates,"name","id","","","");?>
					</td>
					<td align="right">
						&nbsp;<input type="submit" value="<?php print __("Add");?>" name="add_gt_y" align="middle">
					</td>
					</tr>
				</table>
			</td>
		<?php
		form_end_row();
		print "</table></td></tr>";		/* end of html_header */
		html_end_box(FALSE);

		html_start_box("<strong>" . __("Associated Data Queries") . "</strong>", "100", 0, "center", "", true);
		print "<tr><td>";
		html_header(array(array("name" => __("Data Query Name")), array("name" => __("Debugging")), array("name" => __("Re-Index Method")), array("name" =>__("Status"))), 2);

		$selected_data_queries = db_fetch_assoc("select
			snmp_query.id,
			snmp_query.name,
			device_snmp_query.reindex_method
			from (snmp_query,device_snmp_query)
			where snmp_query.id=device_snmp_query.snmp_query_id
			and device_snmp_query.device_id=" . $_GET["id"] . "
			order by snmp_query.name");

		$available_data_queries = db_fetch_assoc("select
			snmp_query.id,
			snmp_query.name
			from snmp_query
			order by snmp_query.name");

		$keeper = array();
		foreach ($available_data_queries as $item) {
			if (sizeof(db_fetch_assoc("SELECT snmp_query_id FROM device_snmp_query " .
					" WHERE ((device_id=" . $_GET["id"] . ")" .
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
				$num_dq_items = sizeof(db_fetch_assoc("select snmp_index from device_snmp_cache where device_id=" . $_GET["id"] . " and snmp_query_id=" . $item["id"]));
				$num_dq_rows = sizeof(db_fetch_assoc("select snmp_index from device_snmp_cache where device_id=" . $_GET["id"] . " and snmp_query_id=" . $item["id"] . " group by snmp_index"));

				$status = "success";

				?>
					<td style="padding: 4px;">
						<strong><?php print $i;?>)</strong> <?php print $item["name"];?>
					</td>
					<td>
						(<a href="devices.php?action=query_verbose&amp;id=<?php print $item["id"];?>&amp;device_id=<?php print $_GET["id"];?>"><?php print __("Verbose Query");?></a>)
					</td>
					<td>
						<?php form_dropdown("reindex_method_device_".get_request_var("id")."_query_".$item["id"]."_method_".$item["reindex_method"],$reindex_types,"","",$item["reindex_method"],"","","","");?>
					</td>
					<td>
						<?php print (($status == "success") ? "<span class=\"success\">" . __("Success") . "</span>" : "<span class=\"fail\">" . __("Fail") . "</span>");?> [<?php print $num_dq_items;?> <?php print __("Item", $num_dq_items);?>, <?php print $num_dq_rows;?> <?php print __("Row", $num_dq_rows);?>]
					</td>
					<td align='right' nowrap>
						<a href='devices.php?action=query_reload&amp;id=<?php print $item["id"];?>&amp;device_id=<?php print $_GET["id"];?>'><img align='middle' class='buttonSmall' src='images/reload_icon_small.gif' title='<?php print __("Reload Data Query");?>' alt='<?php print __("Reload");?>'></a>&nbsp;
						<a href='devices.php?action=query_remove&amp;id=<?php print $item["id"];?>&amp;device_id=<?php print $_GET["id"];?>'><img align='middle' class='buttonSmall' src='images/delete_icon_large.gif' title='<?php print __("Delete Data Query Association");?>' alt='<?php print __("Delete");?>'></a>
					</td>
				<?php
				form_end_row();
			}
		}else{
			print "<tr><td><em>". __("No associated data queries.") . "</em></td></tr>";
		}

		form_alternate_row_color("dq_device" . $device["id"]);

		?>
			<td colspan="5">
				<table cellspacing="0" cellpadding="1" width="100%">
					<tr>
					<td nowrap><?php print __("Add Data Query:");?>&nbsp;
						<?php form_dropdown("snmp_query_id",$available_data_queries,"name","id","","","");?>
					</td>
					<td nowrap><?php print __("Re-Index Method:");?>&nbsp;
						<?php form_dropdown("reindex_method",$reindex_types,"","","1","","");?>
					</td>
					<td align="right">
						&nbsp;<input type="submit" value="<?php print __("Add");?>" name="add_dq_y" align="middle">
					</td>
					</tr>
				</table>
			</td>
		<?php
		form_end_row();
		print "</table></td></tr>";		/* end of html_header */
		html_end_box();
	}

	form_save_button("devices.php", "return");
}

function filter() {
	global $item_rows;

	html_start_box("<strong>" . __("Devices") . "</strong>", "100", "3", "center", "devices.php?action=edit&template_id=" . html_get_page_variable("template_id") . "&status=" . html_get_page_variable("status"), true);
	?>
	<tr class='rowAlternate2'>
		<td>
			<form action="devices.php" name="form_devices" method="post">
			<table cellpadding="0" cellspacing="3">
				<tr>
					<td class="nw50">
						&nbsp;<?php print __("Type:");?>&nbsp;
					</td>
					<td class="w1">
						<select name="template_id" onChange="applyDeviceFilterChange(document.form_devices)">
							<option value="-1"<?php if (html_get_page_variable("template_id") == "-1") {?> selected<?php }?>><?php print __("Any");?></option>
							<option value="0"<?php if (html_get_page_variable("template_id") == "0") {?> selected<?php }?>><?php print __("None");?></option>
							<?php
							$device_templates = db_fetch_assoc("select id,name from device_template order by name");

							if (sizeof($device_templates) > 0) {
							foreach ($device_templates as $device_template) {
								print "<option value='" . $device_template["id"] . "'"; if (html_get_page_variable("template_id") == $device_template["id"]) { print " selected"; } print ">" . $device_template["name"] . "</option>\n";
							}
							}
							?>
						</select>
					</td>
					<td class="nw50">
						&nbsp;<?php print __("Status:");?>&nbsp;
					</td>
					<td class="w1">
						<select name="status" onChange="applyDeviceFilterChange(document.form_devices)">
							<option value="-1"<?php if (html_get_page_variable("status") == "-1") {?> selected<?php }?>><?php print __("Any");?></option>
							<option value="-3"<?php if (html_get_page_variable("status") == "-3") {?> selected<?php }?>><?php print __("Enabled");?></option>
							<option value="-2"<?php if (html_get_page_variable("status") == "-2") {?> selected<?php }?>><?php print __("Disabled");?></option>
							<option value="-4"<?php if (html_get_page_variable("status") == "-4") {?> selected<?php }?>><?php print __("Not Up");?></option>
							<option value="3"<?php  if (html_get_page_variable("status") == "3") {?> selected<?php }?>><?php print __("Up");?></option>
							<option value="1"<?php  if (html_get_page_variable("status") == "1") {?> selected<?php }?>><?php print __("Down");?></option>
							<option value="2"<?php  if (html_get_page_variable("status") == "2") {?> selected<?php }?>><?php print __("Recovering");?></option>
							<option value="0"<?php  if (html_get_page_variable("status") == "0") {?> selected<?php }?>><?php print __("Unknown");?></option>
						</select>
					</td>
					<td class="nw50">
						&nbsp;<?php print __("Rows:");?>&nbsp;
					</td>
					<td class="w1">
						<select name="rows" onChange="applyDeviceFilterChange(document.form_devices)">
							<option value="-1"<?php if (html_get_page_variable("rows") == "-1") {?> selected<?php }?>><?php print __("Default");?></option>
							<?php
							if (sizeof($item_rows) > 0) {
							foreach ($item_rows as $key => $value) {
								print "<option value='" . $key . "'"; if (html_get_page_variable("rows") == $key) { print " selected"; } print ">" . $value . "</option>\n";
							}
							}
							?>
						</select>
					</td>
				</tr>
			</table>
			<table cellpadding="0" cellspacing="3">
				<tr>
					<td class="nw50">
						&nbsp;<?php print __("Site:");?>&nbsp;
					</td>
					<td class="w1">
						<select name="site" onChange="applyDeviceFilterChange(document.form_devices)">
							<option value="-1"<?php if (html_get_page_variable("site") == "-1") {?> selected<?php }?>><?php print __("All");?></option>
							<option value="0"<?php if (html_get_page_variable("site") == "0") {?> selected<?php }?>><?php print __("Not Defined");?></option>
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
					<td class="nw50">
						&nbsp;<?php print __("Poller:");?>&nbsp;
					</td>
					<td class="w1">
						<select name="poller" onChange="applyDeviceFilterChange(document.form_devices)">
							<option value="-1"<?php if (html_get_page_variable("poller") == "-1") {?> selected<?php }?>><?php print __("All");?></option>
							<option value="0"<?php if (html_get_page_variable("poller") == "0") {?> selected<?php }?>><?php print __("System Default");?></option>
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
					<td class="nw50">
						&nbsp;<?php print __("Search:");?>&nbsp;
					</td>
					<td class="w1">
						<input type="text" name="filter" size="20" value="<?php print html_get_page_variable("filter");?>">
					</td>
					<td class="nw120">
						&nbsp;<input type="submit" Value="<?php print __("Go");?>" name="go" align="middle">
						<input type="button" Value="<?php print __("Clear");?>" name="clear" align="middle" onClick="clearDeviceFilterChange(document.form_devices)">
					</td>
				</tr>
			</table>
			<div><input type='hidden' name='page' value='1'></div>
			</form>
		</td>
	</tr>
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
		<?php print (isset($_REQUEST["tab"]) ? "strURL = strURL + '&id=' + objForm.template_id.value + '&action=edit&action=edit&tab=" . html_get_page_variable("tab") . "';\n" : "");?>
		document.location = strURL;
	}
	-->
	</script>
	<?php
	html_end_box(false);
}

function get_records(&$total_rows, &$rowspp) {
	/* form the 'where' clause for our main sql query */
	if (strlen(html_get_page_variable("filter"))) {
		$sql_where = "WHERE (device.hostname like '%%" . html_get_page_variable("filter") . "%%' OR device.description like '%%" . html_get_page_variable("filter") . "%%')";
	}else{
		$sql_where = "";
	}

	if (html_get_page_variable("status") == "-1") {
		/* Show all items */
	}elseif (html_get_page_variable("status") == "-2") {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " device.disabled='on'";
	}elseif (html_get_page_variable("status") == "-3") {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " device.disabled=''";
	}elseif (html_get_page_variable("status") == "-4") {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " (device.status!='3' or device.disabled='on')";
	}else {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " (device.status=" . html_get_page_variable("status") . " AND device.disabled = '')";
	}

	if (get_request_var_request("template_id") == "-1") {
		/* Show all items */
	}elseif (get_request_var_request("template_id") == "0") {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " device.device_template_id=0";
	}elseif (!empty($_REQUEST["template_id"])) {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " device.device_template_id=" . html_get_page_variable("template_id");
	}

	if (get_request_var_request("poller") == "-1") {
		/* Show all items */
	}elseif (get_request_var_request("poller") == "0") {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " device.poller_id=0";
	}elseif (!empty($_REQUEST["poller"])) {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " device.poller_id=" . html_get_page_variable("poller");
	}

	if (get_request_var_request("site") == "-1") {
		/* Show all items */
	}elseif (get_request_var_request("site") == "0") {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " device.site_id=0";
	}elseif (!empty($_REQUEST["site"])) {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " device.site_id=" . html_get_page_variable("site");
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
		COUNT(device.id)
		from device
		$sql_where");

	$sql_query = "SELECT device.*, poller.description AS poller, sites.name AS site,
		(SELECT count(*) FROM graph_local WHERE device.id=graph_local.device_id) AS total_graphs,
		(SELECT count(*) FROM data_local WHERE device.id=data_local.device_id) AS total_datasources
		FROM device
		LEFT JOIN poller
		ON device.poller_id=poller.id
		LEFT JOIN sites
		ON device.site_id=sites.id
		$sql_where
		ORDER BY " . $sortby . " " . html_get_page_variable("sort_direction") . "
		LIMIT " . ($rowspp*(html_get_page_variable("page")-1)) . "," . $rowspp;

	return db_fetch_assoc($sql_query);
}

function device($refresh = true) {
	global $item_rows;
	require(CACTI_BASE_PATH . "/include/device/device_arrays.php");

	$table = New html_table;

	$table->page_variables = array(
		"page"           => array("type" => "numeric", "method" => "request", "default" => "1"),
		"rows"           => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"filter"         => array("type" => "string",  "method" => "request", "default" => ""),
		"tab"            => array("type" => "string",  "method" => "request", "default" => ""),
		"poller"         => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"status"         => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"site"           => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"template_id"    => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"sort_column"    => array("type" => "string",  "method" => "request", "default" => "description"),
		"sort_direction" => array("type" => "string",  "method" => "request", "default" => "ASC")
	);

	$table->table_format = array(
		"description" => array(
			"name" => __("Description"),
			"link" => true,
			"filter" => true,
			"order" => "ASC"
		),
		"device.hostname" => array(
			"name" => __("Hostname"),
			"filter" => true,
			"order" => "ASC"
		),
		"id" => array(
			"name" => __("ID"),
			"align" => "right",
			"order" => "ASC"
		),
		"total_graphs" => array(
			"name" => __("Graphs"),
			"order" => "ASC",
			"align" => "right"
		),
		"total_datasources" => array(
			"name" => __("Data Sources"),
			"order" => "ASC",
			"align" => "right"
		),
		"status" => array(
			"name" => __("Status"),
			"function" => "get_colored_device_status",
			"align" => "center",
			"params" => array("disabled", "status"),
			"order" => "ASC"
		),
		"status_event_count" => array(
			"name" => __("Time in State"),
			"order" => "ASC",
			"function" => "display_device_down_time",
			"params" => array("id", "status_event_count"),
			"align" => "right"
		),
		"cur_time" => array(
			"name" => __("Current (ms)"),
			"order" => "DESC",
			"format" => "round,2",
			"align" => "right"
		),
		"avg_time" => array(
			"name" => __("Average (ms)"),
			"order" => "DESC",
			"format" => "round,2",
			"align" => "right"
		),
		"availability" => array(
			"name" => __("Availability"),
			"order" => "ASC",
			"format" => "round,2",
			"align" => "right"
		),
		"polling_time" => array(
			"name" => __("Poll Time"),
			"order" => "DESC",
			"format" => "round,2",
			"align" => "right"
		)
	);

	/* initialize page behavior */
	$table->href           = "devices.php";
	$table->session_prefix = "sess_devices";
	$table->filter_func    = "filter";
	$table->refresh        = $refresh;
	$table->resizable      = true;
	$table->checkbox       = true;
	$table->sortable       = true;
	$table->actions        = array_merge($device_actions, api_tree_add_tree_names_to_actions_array());

	/* we must validate table variables */
	$table->process_page_variables();

	/* get the records */
	$table->rows = get_records($table->total_rows, $table->rows_per_page);

	/* display the table */
	$table->draw_table();
}

