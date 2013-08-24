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

include("./include/auth.php");
include_once(CACTI_LIBRARY_PATH . "/template.php");

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

switch ($_REQUEST["action"]) {
	case 'save':
		form_save();

		break;
	case 'item_remove':
		item_remove();

		header("Location: graph_templates.php?action=template_edit&id=" . $_GET["graph_template_id"]);
		break;
	case 'item_movedown':
		item_movedown();

		header("Location: graph_templates.php?action=template_edit&id=" . $_GET["graph_template_id"]);
		break;
	case 'item_moveup':
		item_moveup();

		header("Location: graph_templates.php?action=template_edit&id=" . $_GET["graph_template_id"]);
		break;
	case 'item_edit':
		include_once("./include/top_header.php");

		item_edit();

		include_once("./include/bottom_footer.php");
		break;
	case 'item':
		include_once("./include/top_header.php");

		item();

		include_once ("./include/bottom_footer.php");
		break;
}

/* --------------------------
    The Save Function
   -------------------------- */

function form_save() {
	if (isset($_POST["save_component_item"])) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post("graph_template_id"));
		input_validate_input_number(get_request_var_post("task_item_id"));
		/* ==================================================== */

		global $graph_item_types;

		$items[0] = array();

		if ($graph_item_types{$_POST["graph_type_id"]} == "LEGEND") {
			/* this can be a major time saver when creating lots of graphs with the typical
			GPRINT LAST/AVERAGE/MAX legends */
			$items = array(
				0 => array(
					"color_id" => "0",
					"graph_type_id" => "9",
					"consolidation_function_id" => "4",
					"text_format" => "Current:",
					"hard_return" => ""
					),
				1 => array(
					"color_id" => "0",
					"graph_type_id" => "9",
					"consolidation_function_id" => "1",
					"text_format" => "Average:",
					"hard_return" => ""
					),
				2 => array(
					"color_id" => "0",
					"graph_type_id" => "9",
					"consolidation_function_id" => "3",
					"text_format" => "Maximum:",
					"hard_return" => "on"
					));
		}

		foreach ($items as $item) {

			$save["id"] 				= form_input_validate($_POST["graph_template_item_id"], "graph_template_item_id", "^[0-9]+$", false, 3);
			$save["hash"] 				= get_hash_graph_template($_POST["graph_template_item_id"], "graph_template_item");
			$save["graph_template_id"] 	= form_input_validate($_POST["graph_template_id"], "graph_template_id", "^[0-9]+$", false, 3);
			$save["local_graph_id"] 	= 0;
			$save["task_item_id"] 		= form_input_validate(((isset($item["task_item_id"]) ? $item["task_item_id"] : (isset($_POST["task_item_id"]) ? $_POST["task_item_id"] : 0))), "task_item_id", "^[0-9]+$", true, 3);
			$save["color_id"] 			= form_input_validate(((isset($item["color_id"]) ? $item["color_id"] : (isset($_POST["color_id"]) ? $_POST["color_id"] : 0))), "color_id", "^[0-9]+$", true, 3);
			/* if alpha is disabled, use invisible_alpha instead */
			if (!isset($_POST["alpha"])) {$_POST["alpha"] = $_POST["invisible_alpha"];}
			$save["alpha"] 				= form_input_validate(((isset($item["alpha"]) ? $item["alpha"] : (isset($_POST["alpha"]) ? $_POST["alpha"] : "FF"))), "alpha", "^[a-fA-F0-9]+$", true, 3);
			$save["graph_type_id"]		= form_input_validate(((isset($item["graph_type_id"]) ? $item["graph_type_id"] : (isset($_POST["graph_type_id"]) ? $_POST["graph_type_id"] : 0))), "graph_type_id", "^[0-9]+$", true, 3);
			if (isset($_POST["line_width"]) || isset($item["line_width"])) {
				$save["line_width"] 	= form_input_validate((isset($item["line_width"]) ? $item["line_width"] : $_POST["line_width"]), "line_width", "^[0-9]+[\.,]+[0-9]+$", true, 3);
			}else { # make sure to transfer old LINEx style into line_width on save
				switch ($save["graph_type_id"]) {
					case GRAPH_ITEM_TYPE_LINE1:
						$save["line_width"] = 1;
						break;
					case GRAPH_ITEM_TYPE_LINE2:
						$save["line_width"] = 2;
						break;
					case GRAPH_ITEM_TYPE_LINE3:
						$save["line_width"] = 3;
						break;
					default:
						$save["line_width"] = 0;
				}
			}
			# color must be filled for graphable content
			switch ($save["graph_type_id"]) {
				case GRAPH_ITEM_TYPE_HRULE:
				case GRAPH_ITEM_TYPE_VRULE:
				case GRAPH_ITEM_TYPE_LINE1:
				case GRAPH_ITEM_TYPE_LINE2:
				case GRAPH_ITEM_TYPE_LINE3:
				case GRAPH_ITEM_TYPE_AREA:
				case GRAPH_ITEM_TYPE_STACK:
				case GRAPH_ITEM_TYPE_LINESTACK:
				case GRAPH_ITEM_TYPE_TICK:
					if ($save["color_id"] == 0) {
						# no color set, but required
						# so force a validation issue on this field
						form_input_validate("", "color_id", "^[0-9]+$", false, 3);
					}
					break;
				default:
					# everything is fine
			}
			$save["dashes"] 			= form_input_validate((isset($_POST["dashes"]) ? $_POST["dashes"] : ""), "dashes", "^[0-9]+[,0-9]*$", true, 3);
			$save["dash_offset"] 		= form_input_validate((isset($_POST["dash_offset"]) ? $_POST["dash_offset"] : ""), "dash_offset", "^[0-9]+$", true, 3);
			$save["cdef_id"] 			= form_input_validate(((isset($item["cdef_id"]) ? $item["cdef_id"] : (isset($_POST["cdef_id"]) ? $_POST["cdef_id"] : 0))), "cdef_id", "^[0-9]+$", true, 3);
			$save["vdef_id"] 			= form_input_validate(((isset($item["vdef_id"]) ? $item["vdef_id"] : (isset($_POST["vdef_id"]) ? $_POST["vdef_id"] : 0))), "vdef_id", "^[0-9]+$", true, 3);
			$save["shift"] 				= form_input_validate((isset($_POST["shift"]) ? $_POST["shift"] : ""), "shift", "^((on)|)$", true, 3);
			$save["consolidation_function_id"] = form_input_validate(((isset($item["consolidation_function_id"]) ? $item["consolidation_function_id"] : (isset($_POST["consolidation_function_id"]) ? $_POST["consolidation_function_id"] : 0))), "consolidation_function_id", "^[0-9]+$", true, 3);
			$save["textalign"] 			= form_input_validate((isset($_POST["textalign"]) ? $_POST["textalign"] : ""), "textalign", "^[a-z]+$", true, 3);
			$save["text_format"] 		= form_input_validate(((isset($item["text_format"]) ? $item["text_format"] : (isset($_POST["text_format"]) ? $_POST["text_format"] : ""))), "text_format", "", true, 3);
			$save["value"] 				= form_input_validate((isset($_POST["value"]) ? $_POST["value"] : ""), "value", "^[0-9.]+$", true, 3);
			$save["hard_return"] 		= form_input_validate(((isset($item["hard_return"]) ? $item["hard_return"] : (isset($_POST["hard_return"]) ? $_POST["hard_return"] : ""))), "hard_return", "", true, 3);
			$save["gprint_id"] 			= form_input_validate(((isset($item["gprint_id"]) ? $item["gprint_id"] : (isset($_POST["gprint_id"]) ? $_POST["gprint_id"] : 0))), "gprint_id", "^[0-9]+$", true, 3);
			/* generate a new sequence if needed */
			if (empty($_POST["sequence"])) {
				$_POST["sequence"] 		= get_sequence($_POST["sequence"], "sequence", "graph_templates_item", "graph_template_id=" . $_POST["graph_template_id"] . " and local_graph_id=0");
			}
			$save["sequence"] 			= form_input_validate($_POST["sequence"], "sequence", "^[0-9]+$", false, 3);

			if (!is_error_message()) {
				/* Before we save the item, let's get a look at task_item_id <-> input associations */
				$orig_data_source_graph_inputs = db_fetch_assoc("select
					graph_template_input.id,
					graph_template_input.name,
					graph_templates_item.task_item_id
					from (graph_template_input,graph_template_input_defs,graph_templates_item)
					where graph_template_input.id=graph_template_input_defs.graph_template_input_id
					and graph_template_input_defs.graph_template_item_id=graph_templates_item.id
					and graph_template_input.graph_template_id=" . $save["graph_template_id"] . "
					and graph_template_input.column_name='task_item_id'
					group by graph_templates_item.task_item_id");

				$orig_data_source_to_input = array_rekey($orig_data_source_graph_inputs, "task_item_id", "id");

				$graph_template_item_id = sql_save($save, "graph_templates_item");

				if ($graph_template_item_id) {
					raise_message(1);

					if (!empty($save["task_item_id"])) {
						/* old item clean-up.  Don't delete anything if the item <-> task_item_id association remains the same. */
						if ($_POST["_task_item_id"] != $_POST["task_item_id"]) {
							/* It changed.  Delete any old associations */
							db_execute("delete from graph_template_input_defs where graph_template_item_id=$graph_template_item_id");

							/* Input for current data source exists and has changed.  Update the association */
							if (isset($orig_data_source_to_input{$save["task_item_id"]})) {
								db_execute("replace into graph_template_input_defs (graph_template_input_id,
								graph_template_item_id) values (" . $orig_data_source_to_input{$save["task_item_id"]}
								. ",$graph_template_item_id)");
							}
						}

						/* an input for the current data source does NOT currently exist, let's create one */
						if (!isset($orig_data_source_to_input{$save["task_item_id"]})) {
							$ds_name = db_fetch_cell("select data_source_name from data_template_rrd where id=" . $_POST["task_item_id"]);

							db_execute("replace into graph_template_input (hash,graph_template_id,name,column_name) values (
								'" . get_hash_graph_template(0, "graph_template_input") . "'," . $save["graph_template_id"] . ",
								'Data Source [$ds_name]','task_item_id')");

							$graph_template_input_id = db_fetch_insert_id();

							$graph_items = db_fetch_assoc("select id from graph_templates_item where graph_template_id=" . $save["graph_template_id"] . " and task_item_id=" . $_POST["task_item_id"]);

							if (sizeof($graph_items) > 0) {
							foreach ($graph_items as $graph_item) {
								db_execute("replace into graph_template_input_defs (graph_template_input_id,graph_template_item_id) values ($graph_template_input_id," . $graph_item["id"] . ")");
							}
							}
						}
					}

					push_out_graph_item($graph_template_item_id);

					if (isset($orig_data_source_to_input{$_POST["task_item_id"]})) {
						/* make sure all current graphs using this graph input are aware of this change */
						push_out_graph_input($orig_data_source_to_input{$_POST["task_item_id"]}, $graph_template_item_id, array($graph_template_item_id => $graph_template_item_id));
					}
				}else{
					raise_message(2);
				}
			}

			$_POST["sequence"] = 0;
		}

		if (is_error_message()) {
			header("Location: graph_templates_items.php?action=item_edit&graph_template_item_id=" . (empty($graph_template_item_id) ? $_POST["graph_template_item_id"] : $graph_template_item_id) . "&id=" . $_POST["graph_template_id"]);
			exit;
		}else{
			header("Location: graph_templates.php?action=template_edit&id=" . $_POST["graph_template_id"]);
			exit;
		}
	}
}

/* -----------------------
    item - Graph Items
   ----------------------- */

function item_movedown() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("graph_template_id"));
	/* ==================================================== */

	global $graph_item_types;

	$arr = get_graph_group($_GET["id"]);
	$next_id = get_graph_parent($_GET["id"], "next");

	if ((!empty($next_id)) && (isset($arr{$_GET["id"]}))) {
		move_graph_group($_GET["id"], $arr, $next_id, "next");
	}elseif (preg_match("/(GPRINT|VRULE|HRULE|COMMENT)/", $graph_item_types{db_fetch_cell("select graph_type_id from graph_templates_item where id=" . $_GET["id"])})) {
		/* this is so we know the "other" graph item to propagate the changes to */
		$next_item = get_item("graph_templates_item", "sequence", $_GET["id"], "graph_template_id=" . $_GET["graph_template_id"] . " and local_graph_id=0", "next");

		move_item_down("graph_templates_item", $_GET["id"], "graph_template_id=" . $_GET["graph_template_id"] . " and local_graph_id=0");

		db_execute("update graph_templates_item set sequence=" . db_fetch_cell("select sequence from graph_templates_item where id=" . $_GET["id"]) . " where local_graph_template_item_id=" . $_GET["id"]);
		db_execute("update graph_templates_item set sequence=" . db_fetch_cell("select sequence from graph_templates_item where id=" . $next_item). " where local_graph_template_item_id=" . $next_item);
	}
}

function item_moveup() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("graph_template_id"));
	/* ==================================================== */

	global $graph_item_types;

	$arr = get_graph_group($_GET["id"]);
	$next_id = get_graph_parent($_GET["id"], "previous");

	if ((!empty($next_id)) && (isset($arr{$_GET["id"]}))) {
		move_graph_group($_GET["id"], $arr, $next_id, "previous");
	}elseif (preg_match("/(GPRINT|VRULE|HRULE|COMMENT)/", $graph_item_types{db_fetch_cell("select graph_type_id from graph_templates_item where id=" . $_GET["id"])})) {
		/* this is so we know the "other" graph item to propagate the changes to */
		$last_item = get_item("graph_templates_item", "sequence", $_GET["id"], "graph_template_id=" . $_GET["graph_template_id"] . " and local_graph_id=0", "previous");

		move_item_up("graph_templates_item", $_GET["id"], "graph_template_id=" . $_GET["graph_template_id"] . " and local_graph_id=0");

		db_execute("update graph_templates_item set sequence=" . db_fetch_cell("select sequence from graph_templates_item where id=" . $_GET["id"]) . " where local_graph_template_item_id=" . $_GET["id"]);
		db_execute("update graph_templates_item set sequence=" . db_fetch_cell("select sequence from graph_templates_item where id=" . $last_item). " where local_graph_template_item_id=" . $last_item);
	}
}

function item_remove() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("graph_template_id"));
	/* ==================================================== */

	db_execute("delete from graph_templates_item where id=" . $_GET["id"]);
	db_execute("delete from graph_templates_item where local_graph_template_item_id=" . $_GET["id"]);

	/* delete the graph item input if it is empty */
	$graph_item_inputs = db_fetch_assoc("select
		graph_template_input.id
		from (graph_template_input,graph_template_input_defs)
		where graph_template_input.id=graph_template_input_defs.graph_template_input_id
		and graph_template_input.graph_template_id=" . $_GET["graph_template_id"] . "
		and graph_template_input_defs.graph_template_item_id=" . $_GET["id"] . "
		group by graph_template_input.id");

	if (sizeof($graph_item_inputs) > 0) {
	foreach ($graph_item_inputs as $graph_item_input) {
		if (sizeof(db_fetch_assoc("select graph_template_input_id from graph_template_input_defs where graph_template_input_id=" . $graph_item_input["id"])) == 1) {
			db_execute("delete from graph_template_input where id=" . $graph_item_input["id"]);
		}
	}
	}

	db_execute("delete from graph_template_input_defs where graph_template_item_id=" . $_GET["id"]);
}

function item_edit() {
	global $colors, $graph_item_types, $consolidation_functions;
	require(CACTI_LIBRARY_PATH . "/graph.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("graph_template_id"));
	/* ==================================================== */
	$_id = get_request_var("id");
	$_template_id = get_request_var("graph_template_id");

	$header_label = "[edit graph: " . db_fetch_cell("select name from graph_templates where id=" . $_template_id) . "]";

	html_start_box("<strong>Graph Template Items</strong> " . htmlspecialchars($header_label), "100%", $colors["header"], "3", "center", "");

	if ($_id != '') {
		$template_item = db_fetch_row("select * from graph_templates_item where id=" . $_id);
	}

	/* by default, select the LAST DS chosen to make everyone's lives easier */
	$struct_graph_item = graph_item_form_list();
	if ($_template_id != '') {
		$default = db_fetch_row("select task_item_id from graph_templates_item where graph_template_id=" . $_template_id . " and local_graph_id=0 order by sequence DESC");

		if (sizeof($default) > 0) {
			$struct_graph_item["task_item_id"]["default"] = $default["task_item_id"];
		}else{
			$struct_graph_item["task_item_id"]["default"] = 0;
		}
	}

	/* modifications to the default graph items array */
	$struct_graph_item["task_item_id"]["sql"] = "select
		CONCAT_WS('',data_template.name,' - ',' (',data_template_rrd.data_source_name,')') as name,
		data_template_rrd.id
		from (data_template_data,data_template_rrd,data_template)
		where data_template_rrd.data_template_id=data_template.id
		and data_template_data.data_template_id=data_template.id
		and data_template_data.local_data_id=0
		and data_template_rrd.local_data_id=0
		order by data_template.name,data_template_rrd.data_source_name";

	$form_array = array();

	while (list($field_name, $field_array) = each($struct_graph_item)) {
		$form_array += array($field_name => $struct_graph_item[$field_name]);

		$form_array[$field_name]["value"] = (isset($template_item) ? $template_item[$field_name] : "");
		$form_array[$field_name]["form_id"] = (isset($template_item) ? $template_item["id"] : "0");

	}

	if ($_id != '') {
		/* we want to mark the fields that are associated with a graph item input */
		$graph_item_input_fields = db_fetch_assoc("select
			graph_template_input.id,
			graph_template_input.column_name
			from (graph_template_input,graph_template_input_defs)
			where graph_template_input.id=graph_template_input_defs.graph_template_input_id
			and graph_template_input.graph_template_id=" . $_template_id . "
			and graph_template_input_defs.graph_template_item_id=" . $_id . "
			group by graph_template_input.column_name");

		if (sizeof($graph_item_input_fields) > 0) {
		foreach ($graph_item_input_fields as $field) {
			$form_array{$field["column_name"]}["friendly_name"] .= " [<a href='" . htmlspecialchars("graph_templates_inputs.php?action=input_edit&id=" . $field["id"] . "&graph_template_id=" . $_template_id) . "'>Field Not Templated</a>]";
		}
		}
	}

	draw_edit_form(
		array(
			"config" => array(
				),
			"fields" => $form_array
			)
		);

	html_end_box();

	form_hidden_box("graph_template_item_id", (isset($template_item) ? $template_item["id"] : "0"), "");
	form_hidden_box("graph_template_id", $_template_id, "0");
	form_hidden_box("sequence", (isset($template_item) ? $template_item["sequence"] : "0"), "");
	form_hidden_box("_graph_type_id", (isset($template_item) ? $template_item["graph_type_id"] : "0"), "");
	form_hidden_box("_task_item_id", (isset($template_item) ? $template_item["task_item_id"] : "0"), "");
	form_hidden_box("save_component_item", "1", "");
	form_hidden_box("invisible_alpha", $form_array["alpha"]["value"], "FF");
	form_hidden_box("rrdtool_version", read_config_option("rrdtool_version"), "");

	form_save_button("graph_templates.php?action=template_edit&id=" . $_template_id);

	include_once(CACTI_BASE_PATH . "/access/js/graph_item_dependencies.js");	# this one modifies attr("disabled")
	include_once(CACTI_BASE_PATH . "/access/js/line_width.js");
	include_once(CACTI_BASE_PATH . "/access/js/rrdtool_version.js");			# this one sets attr("disabled) and comes last!
}
