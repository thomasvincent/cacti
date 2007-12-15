<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2007 The Cacti Group                                 |
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
include_once("./lib/utility.php");

define("MAX_DISPLAY_PAGES", 21);

$host_actions = array(
	1 => "Delete",
	2 => "Duplicate"
	);

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

form_cancel_action_validate();

switch ($_REQUEST["action"]) {
	case 'save':
		form_save();

		break;
	case 'actions':
		form_actions();

		break;
	case 'item_remove_gt':
		template_item_remove_gt();

		header("Location: host_templates.php?action=edit&id=" . $_GET["host_template_id"]);
		break;
	case 'item_remove_dq':
		template_item_remove_dq();

		header("Location: host_templates.php?action=edit&id=" . $_GET["host_template_id"]);
		break;
	case 'edit':
		include_once("./include/top_header.php");

		template_edit();

		include_once("./include/bottom_footer.php");
		break;
	default:
		include_once("./include/top_header.php");

		template();

		include_once("./include/bottom_footer.php");
		break;
}

/* --------------------------
    The Save Function
   -------------------------- */

function form_save() {
	/* required for "run_data_query" */
	include_once("./lib/data_query.php");

	if (isset($_POST["save_component_template"])) {
		$redirect_back = false;

		$save["id"] = $_POST["id"];
		$save["hash"] = get_hash_host_template($_POST["id"]);
		$save["name"] = form_input_validate($_POST["name"], "name", "", false, 3);

		if (!is_error_message()) {
			$host_template_id = sql_save($save, "host_template");

			if ($host_template_id) {
				raise_message(1);

				if (isset($_POST["add_gt_y"])) {
					db_execute("replace into host_template_graph (host_template_id,graph_template_id) values($host_template_id," . $_POST["graph_template_id"] . ")");
					/* associate this new Graph Template with all hosts that are using the current Host Template
					   but leave those hosts that have this template already */
					$new_gt_host_entries = db_fetch_assoc("
								SELECT 	host.id AS host_id,
										host.description AS description,
										host.hostname AS hostname
								FROM 	host,
									 	host_template_graph
								WHERE	host.host_template_id 					= host_template_graph.host_template_id
								AND		host_template_graph.graph_template_id 	= " . $_POST["graph_template_id"] . "
								AND		host.id NOT
								IN (
									SELECT host_graph.host_id
									FROM   host_graph
									WHERE  host_graph.graph_template_id = " . $_POST["graph_template_id"] . "
									)"
								);
					if (sizeof($new_gt_host_entries) > 0) {
						/* notify the user of changes to hosts */
						debug_log_clear("host_template");
						$template_name = db_fetch_cell("SELECT name FROM graph_templates WHERE id = " . $_POST["graph_template_id"]);
						debug_log_insert("host_template", "Adding Graph Template: " . $template_name . " to ");

						foreach($new_gt_host_entries as $entry) {
							/* add the Graph Template */
							db_execute("REPLACE INTO host_graph ( host_id, graph_template_id )
									VALUES (" . $entry["host_id"] . ","
											  . $_POST["graph_template_id"] . "
											)"
									);
							debug_log_insert("host_template", $entry["hostname"] . ", " . $entry["description"]);
						}
					}
					$redirect_back = true;
				}elseif (isset($_POST["add_dq_y"])) {
					db_execute("replace into host_template_snmp_query (host_template_id,snmp_query_id) values($host_template_id," . $_POST["snmp_query_id"] . ")");
					/* associate this new Data Query with all hosts that are using the current Host Template
					   but leave those hosts that have this Data Query already.
					   Reindex all those Hosts */
					$new_dq_host_entries = db_fetch_assoc("
								SELECT 	host.id AS host_id,
										host.description AS description,
										host.hostname AS hostname
								FROM  	host,
										host_template_snmp_query
								WHERE	host.host_template_id					= host_template_snmp_query.host_template_id
								AND		host_template_snmp_query.snmp_query_id	= " . $_POST["snmp_query_id"] . "
								AND		host.id NOT
								IN (
									SELECT host_snmp_query.host_id
									FROM   host_snmp_query
									WHERE  host_snmp_query.snmp_query_id = " . $_POST["snmp_query_id"] . "
									)"
								);
					if (sizeof($new_dq_host_entries) > 0) {
						/* notify the user of changes to hosts */
						debug_log_clear("host_template");
						$template_name = db_fetch_cell("SELECT name FROM snmp_query WHERE id = " . $_POST["snmp_query_id"]);
						debug_log_insert("host_template", "Adding Data Query: " . $template_name . " to ");

						foreach($new_dq_host_entries as $entry) {
							/* add the Data Query */
							db_execute("REPLACE INTO host_snmp_query (host_id,snmp_query_id,reindex_method)
										VALUES (". $entry["host_id"] . ","
												 . $_POST["snmp_query_id"] . ","
												 . DATA_QUERY_AUTOINDEX_BACKWARDS_UPTIME . "
												)"
										);
							/* recache snmp data */
							run_data_query($entry["host_id"], $_POST["snmp_query_id"]);
							debug_log_insert("host_template", $entry["hostname"] . ", " . $entry["description"]);
						}
					}
					$redirect_back = true;
				}
			}else{
				raise_message(2);
			}
		}

		if ((is_error_message()) || (empty($_POST["id"])) || ($redirect_back == true)) {
			header("Location: host_templates.php?action=edit&id=" . (empty($host_template_id) ? $_POST["id"] : $host_template_id));
		}else{
			header("Location: host_templates.php");
		}
	}
}

/* ------------------------
    The "actions" function
   ------------------------ */

function form_actions() {
	global $colors, $host_actions;

	/* if we are to save this form, instead of display it */
	if (isset($_POST["selected_items"])) {
		$selected_items = unserialize(stripslashes($_POST["selected_items"]));

		if ($_POST["drp_action"] == "1") { /* delete */
			db_execute("delete from host_template where " . array_to_sql_or($selected_items, "id"));
			db_execute("delete from host_template_snmp_query where " . array_to_sql_or($selected_items, "host_template_id"));
			db_execute("delete from host_template_graph where " . array_to_sql_or($selected_items, "host_template_id"));

			/* "undo" any device that is currently using this template */
			db_execute("update host set host_template_id=0 where " . array_to_sql_or($selected_items, "host_template_id"));
		}elseif ($_POST["drp_action"] == "2") { /* duplicate */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				duplicate_host_template($selected_items[$i], $_POST["title_format"]);
			}
		}

		header("Location: host_templates.php");
		exit;
	}

	/* setup some variables */
	$host_list = ""; $i = 0;

	/* loop through each of the host templates selected on the previous page and get more info about them */
	while (list($var,$val) = each($_POST)) {
		if (ereg("^chk_([0-9]+)$", $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$host_list .= "<li>" . db_fetch_cell("select name from host_template where id=" . $matches[1]) . "<br>";
			$host_array[$i] = $matches[1];
		}

		$i++;
	}

	include_once("./include/top_header.php");

	html_start_box("<strong>" . $host_actions{$_POST["drp_action"]} . "</strong>", "60%", $colors["header_panel"], "3", "center", "");

	print "<form action='host_templates.php' method='post'>\n";

	if (sizeof($host_array)) {
		if ($_POST["drp_action"] == "1") { /* delete */
			print "	<tr>
					<td class='textArea' bgcolor='#" . $colors["form_alternate1"]. "'>
						<p>Are you sure you want to delete the following host templates? All devices currently attached
						this these host templates will lose their template assocation.</p>
						<p>$host_list</p>
					</td>
				</tr>\n
				";
		}elseif ($_POST["drp_action"] == "2") { /* duplicate */
			print "	<tr>
					<td class='textArea' bgcolor='#" . $colors["form_alternate1"]. "'>
						<p>When you click save, the following host templates will be duplicated. You can
						optionally change the title format for the new host templates.</p>
						<p>$host_list</p>
						<p><strong>Title Format:</strong><br>"; form_text_box("title_format", "<template_title> (1)", "", "255", "30", "text"); print "</p>
					</td>
				</tr>\n
				";
		}
	} else {
		print "	<tr>
				<td class='textArea' bgcolor='#" . $colors["form_alternate1"]. "'>
					<p>You must first select a Device Template.  Please select 'Return' to return to the previous menu.</p>
				</td>
			</tr>\n";
	}

	if (!isset($host_array)) {
		form_return_button_alt();
	}else{
		form_yesno_button_alt(serialize($host_array), $_POST["drp_action"]);
	}

	html_end_box();

	include_once("./include/bottom_footer.php");
}

/* ---------------------
    Template Functions
   --------------------- */

function template_item_remove_gt() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("host_template_id"));
	/* ==================================================== */

	db_execute("delete from host_template_graph where graph_template_id=" . $_GET["id"] . " and host_template_id=" . $_GET["host_template_id"]);
}

function template_item_remove_dq() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("host_template_id"));
	/* ==================================================== */

	db_execute("delete from host_template_snmp_query where snmp_query_id=" . $_GET["id"] . " and host_template_id=" . $_GET["host_template_id"]);
}

function template_edit() {
	global $colors, $fields_host_template_edit;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	display_output_messages();
	/* remember if there's something we want to show to the user */
	$debug_log = debug_log_return("host_template");

	if (!empty($debug_log)) {
		debug_log_clear("host_template");
		?>
		<table width='100%' style='background-color: #f5f5f5; border: 1px solid #bbbbbb;' align='center'>
			<tr bgcolor="<?php print $colors["light"];?>">
				<td style="padding: 3px; font-family: monospace;">
					<?php print $debug_log;?>
				</td>
			</tr>
		</table>
		<br>
		<?php
	}

	if (!empty($_GET["id"])) {
		$host_template = db_fetch_row("select * from host_template where id=" . $_GET["id"]);
		$header_label = "[edit: " . $host_template["name"] . "]";
	}else{
		$header_label = "[new]";
		$_GET["id"] = 0;
	}

	html_start_box("<strong>Host Templates</strong> $header_label", "100%", $colors["header"], "3", "center", "");

	draw_edit_form(array(
		"config" => array(),
		"fields" => inject_form_variables($fields_host_template_edit, (isset($host_template) ? $host_template : array()))
		));

	html_end_box();

	if (!empty($_GET["id"])) {
		html_start_box("<strong>Associated Graph Templates</strong>", "100%", $colors["header"], "3", "center", "");

		$selected_graph_templates = db_fetch_assoc("select
			graph_templates.id,
			graph_templates.name
			from (graph_templates,host_template_graph)
			where graph_templates.id=host_template_graph.graph_template_id
			and host_template_graph.host_template_id=" . $_GET["id"] . "
			order by graph_templates.name");

		$i = 0;
		if (sizeof($selected_graph_templates) > 0) {
		foreach ($selected_graph_templates as $item) {
			$i++;
			?>
			<tr>
				<td style="padding: 4px;">
					<strong><?php print $i;?>)</strong> <?php print $item["name"];?>
				</td>
				<td align="right">
					<a href='host_templates.php?action=item_remove_gt&id=<?php print $item["id"];?>&host_template_id=<?php print $_GET["id"];?>'><img src='images/delete_icon.gif' width='10' height='10' border='0' alt='Delete'></a>
				</td>
			</tr>
			<?php
		}
		}else{ print "<tr><td><em>No associated graph templates.</em></td></tr>"; }

		?>
		<tr bgcolor="#<?php print $colors["form_alternate1"];?>">
			<td colspan="2">
				<table cellspacing="0" cellpadding="1" width="100%">
					<td nowrap>Add Graph Template:&nbsp;
						<?php form_dropdown("graph_template_id",db_fetch_assoc("select
							graph_templates.id,
							graph_templates.name
							from graph_templates left join host_template_graph
							on (graph_templates.id=host_template_graph.graph_template_id and host_template_graph.host_template_id=" . $_GET["id"] . ")
							where host_template_graph.host_template_id is null
							order by graph_templates.name"),"name","id","","","");?>
					</td>
					<td align="right">
						&nbsp;<input type="submit" Value="Add" name="add_gt_y" align="absmiddle">
					</td>
				</table>
			</td>
		</tr>

		<?php
		html_end_box();

		html_start_box("<strong>Associated Data Queries</strong>", "100%", $colors["header"], "3", "center", "");

		$selected_data_queries = db_fetch_assoc("select
			snmp_query.id,
			snmp_query.name
			from (snmp_query,host_template_snmp_query)
			where snmp_query.id=host_template_snmp_query.snmp_query_id
			and host_template_snmp_query.host_template_id=" . $_GET["id"] . "
			order by snmp_query.name");

		$i = 0;
		if (sizeof($selected_data_queries) > 0) {
		foreach ($selected_data_queries as $item) {
			$i++;
			?>
			<tr>
				<td style="padding: 4px;">
					<strong><?php print $i;?>)</strong> <?php print $item["name"];?>
				</td>
				<td align='right'>
					<a href='host_templates.php?action=item_remove_dq&id=<?php print $item["id"];?>&host_template_id=<?php print $_GET["id"];?>'><img src='images/delete_icon.gif' width='10' height='10' border='0' alt='Delete'></a>
				</td>
			</tr>
			<?php
		}
		}else{ print "<tr><td><em>No associated data queries.</em></td></tr>"; }

		?>
		<tr bgcolor="#<?php print $colors["form_alternate1"];?>">
			<td colspan="2">
				<table cellspacing="0" cellpadding="1" width="100%">
					<td nowrap>Add Data Query:&nbsp;
						<?php form_dropdown("snmp_query_id",db_fetch_assoc("select
							snmp_query.id,
							snmp_query.name
							from snmp_query left join host_template_snmp_query
							on (snmp_query.id=host_template_snmp_query.snmp_query_id and host_template_snmp_query.host_template_id=" . $_GET["id"] . ")
							where host_template_snmp_query.host_template_id is null
							order by snmp_query.name"),"name","id","","","");?>
					</td>
					<td align="right">
						&nbsp;<input type="submit" value="Add" name="add_dq_y" align="absmiddle">
					</td>
				</table>
			</td>
		</tr>

		<?php
		html_end_box();
	}

	form_save_button_alt();
}

function template() {
	global $colors, $host_actions;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("page"));
	/* ==================================================== */

	/* clean up search string */
	if (isset($_REQUEST["filter"])) {
		$_REQUEST["filter"] = sanitize_search_string(get_request_var("filter"));
	}

	/* clean up sort_column */
	if (isset($_REQUEST["sort_column"])) {
		$_REQUEST["sort_column"] = sanitize_search_string(get_request_var("sort_column"));
	}

	/* clean up sort_direction string */
	if (isset($_REQUEST["sort_direction"])) {
		$_REQUEST["sort_direction"] = sanitize_search_string(get_request_var("sort_direction"));
	}

	/* if the user pushed the 'clear' button */
	if (isset($_REQUEST["clear_x"])) {
		kill_session_var("sess_host_template_current_page");
		kill_session_var("sess_host_template_filter");
		kill_session_var("sess_host_template_sort_column");
		kill_session_var("sess_host_template_sort_direction");

		unset($_REQUEST["page"]);
		unset($_REQUEST["filter"]);
		unset($_REQUEST["sort_column"]);
		unset($_REQUEST["sort_direction"]);
	}

	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value("page", "sess_host_template_current_page", "1");
	load_current_session_value("filter", "sess_host_template_filter", "");
	load_current_session_value("sort_column", "sess_host_template_sort_column", "name");
	load_current_session_value("sort_direction", "sess_host_template_sort_direction", "ASC");

	display_output_messages();

	html_start_box("<strong>Host Templates</strong>", "100%", $colors["header"], "3", "center", "host_templates.php?action=edit", true);

	include("./include/html/inc_graph_template_filter_table.php");

	html_end_box();

	/* form the 'where' clause for our main sql query */
	$sql_where = "WHERE (host_template.name LIKE '%%" . $_REQUEST["filter"] . "%%')";

	html_start_box("", "100%", $colors["header"], "3", "center", "");

	$total_rows = db_fetch_cell("SELECT
		COUNT(host_template.id)
		FROM host_template
		$sql_where");

	$template_list = db_fetch_assoc("SELECT
		host_template.id,host_template.name
		FROM host_template
		$sql_where
		ORDER BY " . $_REQUEST['sort_column'] . " " . $_REQUEST['sort_direction'] .
		" LIMIT " . (read_config_option("num_rows_device")*($_REQUEST["page"]-1)) . "," . read_config_option("num_rows_device"));

	/* generate page list navigation */
	$nav = html_create_nav($_REQUEST["page"], MAX_DISPLAY_PAGES, read_config_option("num_rows_device"), $total_rows, 7, "host_templates.php");

	print $nav;

	$display_text = array(
		"name" => array("Template Title", "ASC"));

	html_header_sort_checkbox($display_text, $_REQUEST["sort_column"], $_REQUEST["sort_direction"]);

	if (sizeof($template_list) > 0) {
		foreach ($template_list as $template) {
			form_alternate_row_color('line' . $template["id"]);
			form_selectable_cell("<a class='linkEditMain' href='host_templates.php?action=edit&id=" . $template["id"] . "'>" . (strlen($_REQUEST["filter"]) ? eregi_replace("(" . preg_quote($_REQUEST["filter"]) . ")", "<span style='background-color: #F8D93D;'>\\1</span>", $template["name"]) : $template["name"]) . "</a>", $template["id"]);
			form_checkbox_cell($template["name"], $template["id"]);
			form_end_row();
		}
		/* put the nav bar on the bottom as well */
		print $nav;
	}else{
		print "<tr><td><em>No Host Templates</em></td></tr>\n";
	}
	html_end_box(false);

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($host_actions);

	print "</form>\n";
}
?>