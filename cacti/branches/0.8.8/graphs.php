<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2014 The Cacti Group                                 |
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

include('./include/auth.php');
include_once('./lib/utility.php');
include_once('./lib/api_graph.php');
include_once('./lib/api_tree.php');
include_once('./lib/api_data_source.php');
include_once('./lib/template.php');
include_once('./lib/html_tree.php');
include_once('./lib/html_form_template.php');
include_once('./lib/rrd.php');
include_once('./lib/data_query.php');

define('MAX_DISPLAY_PAGES', 21);

$graph_actions = array(
	1 => 'Delete',
	2 => 'Change Graph Template',
	5 => 'Change Device',
	6 => 'Reapply Suggested Names',
	7 => 'Resize Graphs',
	3 => 'Duplicate',
	4 => 'Convert to Graph Template'
	);

$graph_actions = api_plugin_hook_function('graphs_action_array', $graph_actions);

/* set default action */
if (!isset($_REQUEST['action'])) { $_REQUEST['action'] = ''; }

switch ($_REQUEST['action']) {
	case 'save':
		form_save();

		break;
	case 'actions':
		form_actions();

		break;
	case 'graph_diff':
		top_header();

		graph_diff();

		bottom_footer();
		break;
	case 'item':
		top_header();

		item();

		bottom_footer();
		break;
	case 'graph_remove':
		graph_remove();

		header('Location: graphs.php');
		break;
	case 'graph_edit':
		top_header();

		graph_edit();

		bottom_footer();
		break;
	default:
		top_header();

		graph();

		bottom_footer();
		break;
}

/* --------------------------
    Global Form Functions
   -------------------------- */

function add_tree_names_to_actions_array() {
	global $graph_actions;

	/* add a list of tree names to the actions dropdown */
	$trees = db_fetch_assoc('SELECT id, name FROM graph_tree ORDER BY name');

	if (sizeof($trees) > 0) {
	foreach ($trees as $tree) {
		$graph_actions{'tr_' . $tree['id']} = 'Place on a Tree (' . $tree['name'] . ')';
	}
	}
}

/* --------------------------
    The Save Function
   -------------------------- */

function form_save() {
	if ((isset($_POST['save_component_graph_new'])) && (!empty($_POST['graph_template_id']))) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post('graph_template_id'));
		/* ==================================================== */

		$save['id'] = $_POST['local_graph_id'];
		$save['graph_template_id'] = $_POST['graph_template_id'];
		$save['host_id'] = $_POST['host_id'];

		$local_graph_id = sql_save($save, 'graph_local');

		change_graph_template($local_graph_id, $_POST['graph_template_id'], true);

		/* update the title cache */
		update_graph_title_cache($local_graph_id);
	}

	if (isset($_POST['save_component_graph'])) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post('graph_template_id'));
		input_validate_input_number(get_request_var_post('_graph_template_id'));
		/* ==================================================== */

		$save1['id'] = $_POST['local_graph_id'];
		$save1['host_id'] = $_POST['host_id'];
		$save1['graph_template_id'] = $_POST['graph_template_id'];

		$save2['id'] = $_POST['graph_template_graph_id'];
		$save2['local_graph_template_graph_id'] = $_POST['local_graph_template_graph_id'];
		$save2['graph_template_id'] = $_POST['graph_template_id'];
		$save2['image_format_id'] = form_input_validate($_POST['image_format_id'], 'image_format_id', '', true, 3);
		$save2['title'] = form_input_validate($_POST['title'], 'title', '', false, 3);
		$save2['height'] = form_input_validate($_POST['height'], 'height', '^[0-9]+$', false, 3);
		$save2['width'] = form_input_validate($_POST['width'], 'width', '^[0-9]+$', false, 3);
		$save2['upper_limit'] = form_input_validate($_POST['upper_limit'], 'upper_limit', "^(-?([0-9]+(\.[0-9]*)?|[0-9]*\.[0-9]+)([eE][+\-]?[0-9]+)?)|U$", ((strlen($_POST['upper_limit']) === 0) ? true : false), 3);
		$save2['lower_limit'] = form_input_validate($_POST['lower_limit'], 'lower_limit', "^(-?([0-9]+(\.[0-9]*)?|[0-9]*\.[0-9]+)([eE][+\-]?[0-9]+)?)|U$", ((strlen($_POST['lower_limit']) === 0) ? true : false), 3);
		$save2['vertical_label'] = form_input_validate($_POST['vertical_label'], 'vertical_label', '', true, 3);
		$save2['slope_mode'] = form_input_validate((isset($_POST['slope_mode']) ? $_POST['slope_mode'] : ''), 'slope_mode', '', true, 3);
		$save2['auto_scale'] = form_input_validate((isset($_POST['auto_scale']) ? $_POST['auto_scale'] : ''), 'auto_scale', '', true, 3);
		$save2['auto_scale_opts'] = form_input_validate($_POST['auto_scale_opts'], 'auto_scale_opts', '', true, 3);
		$save2['auto_scale_log'] = form_input_validate((isset($_POST['auto_scale_log']) ? $_POST['auto_scale_log'] : ''), 'auto_scale_log', '', true, 3);
		$save2['scale_log_units'] = form_input_validate((isset($_POST['scale_log_units']) ? $_POST['scale_log_units'] : ''), 'scale_log_units', '', true, 3);
		$save2['auto_scale_rigid'] = form_input_validate((isset($_POST['auto_scale_rigid']) ? $_POST['auto_scale_rigid'] : ''), 'auto_scale_rigid', '', true, 3);
		$save2['auto_padding'] = form_input_validate((isset($_POST['auto_padding']) ? $_POST['auto_padding'] : ''), 'auto_padding', '', true, 3);
		$save2['base_value'] = form_input_validate($_POST['base_value'], 'base_value', '^[0-9]+$', false, 3);
		$save2['export'] = form_input_validate((isset($_POST['export']) ? $_POST['export'] : ''), 'export', '', true, 3);
		$save2['unit_value'] = form_input_validate($_POST['unit_value'], 'unit_value', '', true, 3);
		$save2['unit_exponent_value'] = form_input_validate($_POST['unit_exponent_value'], 'unit_exponent_value', '^-?[0-9]+$', true, 3);

		if (!is_error_message()) {
			$local_graph_id = sql_save($save1, 'graph_local');
		}

		if (!is_error_message()) {
			$save2['local_graph_id'] = $local_graph_id;
			$graph_templates_graph_id = sql_save($save2, 'graph_templates_graph');

			if ($graph_templates_graph_id) {
				raise_message(1);

				/* if template information chanegd, update all necessary template information */
				if ($_POST['graph_template_id'] != $_POST['_graph_template_id']) {
					/* check to see if the number of graph items differs, if it does; we need user input */
					if ((!empty($_POST['graph_template_id'])) && (!empty($_POST['local_graph_id'])) && (sizeof(db_fetch_assoc_prepared('SELECT id FROM graph_templates_item WHERE local_graph_id = ?', array($local_graph_id))) != sizeof(db_fetch_assoc_prepared('SELECT id from graph_templates_item WHERE local_graph_id = 0 AND graph_template_id = ?', array($_POST['graph_template_id']))))) {
						/* set the template back, since the user may choose not to go through with the change
						at this point */
						db_execute_prepared('UPDATE graph_local SET graph_template_id = ? WHERE id = ?', array($_POST['_graph_template_id'], $local_graph_id));
						db_execute_prepared('UPDATE graph_templates_graph SET graph_template_id = ? WHERE local_graph_id = ?', array($_POST['_graph_template_id'], $local_graph_id));

						header('Location: graphs.php?action=graph_diff&id=$local_graph_id&graph_template_id=' . $_POST['graph_template_id']);
						exit;
					}
				}
			}else{
				raise_message(2);
			}

			/* update the title cache */
			update_graph_title_cache($local_graph_id);
		}

		if ((!is_error_message()) && ($_POST['graph_template_id'] != $_POST['_graph_template_id'])) {
			change_graph_template($local_graph_id, $_POST['graph_template_id'], true);
		}elseif (!empty($_POST['graph_template_id'])) {
			update_graph_data_query_cache($local_graph_id);
		}
	}

	if (isset($_POST['save_component_input'])) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post('local_graph_id'));
		/* ==================================================== */

		/* first; get the current graph template id */
		$graph_template_id = db_fetch_cell_prepared('SELECT graph_template_id FROM graph_local WHERE id = ?', array($_POST['local_graph_id']));

		/* get all inputs that go along with this graph template, if templated */
		if ($graph_template_id > 0) {
			$input_list = db_fetch_assoc_prepared('SELECT id, column_name FROM graph_template_input WHERE graph_template_id = ?', array($graph_template_id));
			
			if (sizeof($input_list) > 0) {
				foreach ($input_list as $input) {
					/* we need to find out which graph items will be affected by saving this particular item */
					$item_list = db_fetch_assoc_prepared('SELECT
						graph_templates_item.id
						FROM (graph_template_input_defs, graph_templates_item)
						WHERE graph_template_input_defs.graph_template_item_id = graph_templates_item.local_graph_template_item_id
						AND graph_templates_item.local_graph_id = ?
						AND graph_template_input_defs.graph_template_input_id = ?', array($_POST['local_graph_id'], $input['id']));
					
					/* loop through each item affected and update column data */
					if (sizeof($item_list) > 0) {
						foreach ($item_list as $item) {
							/* if we are changing templates, the POST vars we are searching for here will not exist.
							 this is because the db and form are out of sync here, but it is ok to just skip over saving
							 the inputs in this case. */
							if (isset($_POST{$input['column_name'] . '_' . $input['id']})) {
								db_execute_prepared('UPDATE graph_templates_item SET ' . $input['column_name'] . ' = ? WHERE id = ?', array($_POST{$input['column_name'] . '_' . $input['id']}, $item['id']));
							}
						}
					}
				}
			}
		}
	}

	if (isset($_POST['save_component_graph_diff'])) {
		if ($_POST['type'] == '1') {
			$intrusive = true;
		}elseif ($_POST['type'] == '2') {
			$intrusive = false;
		}

		change_graph_template($_POST['local_graph_id'], $_POST['graph_template_id'], $intrusive);
	}

	if ((isset($_POST['save_component_graph_new'])) && (empty($_POST['graph_template_id']))) {
		header('Location: graphs.php?action=graph_edit&host_id=' . $_POST['host_id'] . '&new=1');
	}elseif ((is_error_message()) || (empty($_POST['local_graph_id'])) || (isset($_POST['save_component_graph_diff'])) || ($_POST['graph_template_id'] != $_POST['_graph_template_id']) || ($_POST['host_id'] != $_POST['_host_id'])) {
		header('Location: graphs.php?action=graph_edit&id=' . (empty($local_graph_id) ? $_POST['local_graph_id'] : $local_graph_id) . (isset($_POST['host_id']) ? '&host_id=' . $_POST['host_id'] : ''));
	}else{
		header('Location: graphs.php');
	}
}

/* ------------------------
    The "actions" function
   ------------------------ */

function form_actions() {
	global $graph_actions;

	/* ================= input validation ================= */
	input_validate_input_regex(get_request_var_post('drp_action'), '^([a-zA-Z0-9_]+)$');
	/* ==================================================== */

	/* if we are to save this form, instead of display it */
	if (isset($_POST['selected_items'])) {
		$selected_items = unserialize(stripslashes($_POST['selected_items']));

		if ($_POST['drp_action'] == '1') { /* delete */
			if (!isset($_POST['delete_type'])) { $_POST['delete_type'] = 1; }

			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */
			}

			switch ($_POST['delete_type']) {
				case '2': /* delete all data sources referenced by this graph */
					$data_sources = array_rekey(db_fetch_assoc('SELECT data_template_data.local_data_id
						FROM (data_template_rrd, data_template_data, graph_templates_item)
						WHERE graph_templates_item.task_item_id=data_template_rrd.id
						AND data_template_rrd.local_data_id=data_template_data.local_data_id
						AND ' . array_to_sql_or($selected_items, 'graph_templates_item.local_graph_id') . '
						AND data_template_data.local_data_id > 0'), 'local_data_id', 'local_data_id');

					if (sizeof($data_sources)) {
						api_data_source_remove_multi($data_sources);
						api_plugin_hook_function('data_source_remove', $data_sources);
					}

					break;
			}

			api_graph_remove_multi($selected_items);

			api_plugin_hook_function('graphs_remove', $selected_items);
		}elseif ($_POST['drp_action'] == '2') { /* change graph template */
			input_validate_input_number(get_request_var_post('graph_template_id'));
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				change_graph_template($selected_items[$i], $_POST['graph_template_id'], true);
			}
		}elseif ($_POST['drp_action'] == '3') { /* duplicate */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				duplicate_graph($selected_items[$i], 0, $_POST['title_format']);
			}
		}elseif ($_POST['drp_action'] == '4') { /* graph -> graph template */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				graph_to_graph_template($selected_items[$i], $_POST['title_format']);
			}
		}elseif (preg_match('/^tr_([0-9]+)$/', $_POST['drp_action'], $matches)) { /* place on tree */
			input_validate_input_number(get_request_var_post('tree_id'));
			input_validate_input_number(get_request_var_post('tree_item_id'));
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				api_tree_item_save(0, $_POST['tree_id'], TREE_ITEM_TYPE_GRAPH, $_POST['tree_item_id'], '', $selected_items[$i], read_graph_config_option('default_rra_id'), 0, 0, 0, false);
			}
		}elseif ($_POST['drp_action'] == '5') { /* change host */
			input_validate_input_number(get_request_var_post('host_id'));
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				db_execute_prepared('UPDATE graph_local SET host_id = ? WHERE id = ?', array($_POST['host_id'], $selected_items[$i]));
				update_graph_title_cache($selected_items[$i]);
			}
		}elseif ($_POST['drp_action'] == '6') { /* reapply suggested naming */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				api_reapply_suggested_graph_title($selected_items[$i]);
				update_graph_title_cache($selected_items[$i]);
			}
		}elseif ($_POST['drp_action'] == '7') { /* resize graphs */
			input_validate_input_number(get_request_var_post('graph_width'));
			input_validate_input_number(get_request_var_post('graph_height'));
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				api_resize_graphs($selected_items[$i], $_POST['graph_width'], $_POST['graph_height']);
			}
		} else {
			api_plugin_hook_function('graphs_action_execute', $_POST['drp_action']);
		}
		api_plugin_hook_function('graphs_action_bottom', array($_POST['drp_action'], $selected_items));

		header('Location: graphs.php');
		exit;
	}

	/* setup some variables */
	$graph_list = ''; $i = 0;

	/* loop through each of the graphs selected on the previous page and get more info about them */
	while (list($var,$val) = each($_POST)) {
		if (preg_match('/^chk_([0-9]+)$/', $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$graph_list .= '<li>' . htmlspecialchars(get_graph_title($matches[1])) . '</li>';
			$graph_array[$i] = $matches[1];

			$i++;
		}
	}

	top_header();

	/* add a list of tree names to the actions dropdown */
	add_tree_names_to_actions_array();

	html_start_box('<strong>' . $graph_actions{$_POST['drp_action']} . '</strong>', '60%', '', '3', 'center', '');

	print "<form action='graphs.php' method='post'>\n";

	if (isset($graph_array) && sizeof($graph_array)) {
		if ($_POST['drp_action'] == '1') { /* delete */
			$graphs = array();

			/* find out which (if any) data sources are being used by this graph, so we can tell the user */
			if (isset($graph_array) && sizeof($graph_array)) {
				$data_sources = db_fetch_assoc('select
					data_template_data.local_data_id,
					data_template_data.name_cache
					from (data_template_rrd,data_template_data,graph_templates_item)
					where graph_templates_item.task_item_id=data_template_rrd.id
					and data_template_rrd.local_data_id=data_template_data.local_data_id
					and ' . array_to_sql_or($graph_array, 'graph_templates_item.local_graph_id') . '
					and data_template_data.local_data_id > 0
					group by data_template_data.local_data_id
					order by data_template_data.name_cache');
			}

			print "	<tr>
					<td class='textArea'>
						<p>When you click \"Continue\", the following Graph(s) will be deleted.  Please note, Data Source(s) should be deleted only if they are only used by these Graph(s)
						and not others.</p>
						<p><ul>$graph_list</ul></p>";

						if (isset($data_sources) && sizeof($data_sources)) {
							print "<tr><td class='textArea'><p>The following Data Source(s) are in use by these Graph(s):</p>\n";

							print '<ul>';
							foreach ($data_sources as $data_source) {
								print '<li><strong>' . $data_source['name_cache'] . "</strong></li>\n";
							}
							print '</ul>';

							print '<br>';
							form_radio_button('delete_type', '1', '2', "Leave the Data Source(s) untouched.  Not applicable for Graphs created under 'New Graphs' or WHERE the Graphs were created automatically.", '2'); print '<br>';
							form_radio_button('delete_type', '2', '2', 'Delete all <strong>Data Source(s)</strong> referenced by these Graph(s).', '2'); print '<br>';
							print '</td></tr>';
						}
					print "
					</td>
				</tr>\n
				";
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Delete Graph(s)'>";
		}elseif ($_POST['drp_action'] == '2') { /* change graph template */
			print "	<tr>
					<td class='textArea'>
						<p>Choose a Graph Template and click \"Continue\" to change the Graph Template for
						the following Graph(s). Be aware that all warnings will be suppressed during the
						conversion, so Graph data loss is possible.</p>
						<p><ul>$graph_list</ul></p>
						<p><strong>New Graph Template:</strong><br>"; form_dropdown('graph_template_id',db_fetch_assoc('SELECT graph_templates.id,graph_templates.name FROM graph_templates ORDER BY name'),'name','id','','','0'); print "</p>
					</td>
				</tr>\n
				";
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Change Graph Template'>";
		}elseif ($_POST['drp_action'] == '3') { /* duplicate */
			print "	<tr>
					<td class='textArea'>
						<p>When you click \"Continue\", the following Graph(s) will be duplicated. You can
						optionally change the title format for the new Graph(s).</p>
						<p><ul>$graph_list</ul></p>
						<p><strong>Title Format:</strong><br>"; form_text_box('title_format', '<graph_title> (1)', '', '255', '30', 'text'); print "</p>
					</td>
				</tr>\n
				";
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Duplicate Graph(s)'>";
		}elseif ($_POST['drp_action'] == '4') { /* graph -> graph template */
			print "	<tr>
					<td class='textArea'>
						<p>When you click \"Continue\", the following Graph(s) will be converted into Graph Template(s).
						You can optionally change the title format for the new Graph Template(s).</p>
						<p><ul>$graph_list</ul></p>
						<p><strong>Title Format:</strong><br>"; form_text_box('title_format', '<graph_title> Template', '', '255', '30', 'text'); print "</p>
					</td>
				</tr>\n
				";
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Convert to Graph Template'>";
		}elseif (preg_match('/^tr_([0-9]+)$/', $_POST['drp_action'], $matches)) { /* place on tree */
			print "	<tr>
					<td class='textArea'>
						<p>When you click \"Continue\", the following Graph(s) will be placed under the Tree Branch selected below.</p>
						<p><ul>$graph_list</ul></p>
						<p><strong>Destination Branch:</strong><br>"; grow_dropdown_tree($matches[1], '0', 'tree_item_id', '0'); print "</p>
					</td>
				</tr>\n
				<input type='hidden' name='tree_id' value='" . $matches[1] . "'>\n
				";
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Place Graph(s) on Tree'>";
		}elseif ($_POST['drp_action'] == '5') { /* change host */
			print "	<tr>
					<td class='textArea'>
						<p>Choose a new Device for these Graph(s) and click \"Continue\"</p>
						<p><ul>$graph_list</ul></p>
						<p><strong>New Device:</strong><br>"; form_dropdown('host_id',db_fetch_assoc("SELECT id,CONCAT_WS('',description,' (',hostname,')') as name FROM host ORDER BY description,hostname"),'name','id','','','0'); print "</p>
					</td>
				</tr>\n
				";
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Change Graph(s) Associated Device'>";
		}elseif ($_POST['drp_action'] == '6') { /* reapply suggested naming to host */
			print "	<tr>
					<td class='textArea'>
						<p>When you click \"Continue\", the following Graph(s) will have thier suggested naming convensions
						recalculated and applied to the Graph(s).</p>
						<p><ul>$graph_list</ul></p>
					</td>
				</tr>\n
				";
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Reapply Suggested Naming to Graph(s)'>";
		}elseif ($_POST['drp_action'] == '7') { /* resize graphs */
			print "	<tr>
					<td class='textArea'>
						<p>When you click \"Continue\", the following Graph(s) will be resized per your specifications.</p>
						<p><ul>$graph_list</ul></p>
						<p><strong>Graph Height:</strong><br>"; form_text_box('graph_height', '', '', '255', '30', 'text'); print '</p>
						<p><strong>Graph Width:</strong><br>'; form_text_box('graph_width', '', '', '255', '30', 'text'); print "</p>
					</td>
				</tr>\n
				";

			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Resize Selected Graph(s)'>";
		} else {
			$save['drp_action'] = $_POST['drp_action'];
			$save['graph_list'] = $graph_list;
			$save['graph_array'] = (isset($graph_array) ? $graph_array : array());
			api_plugin_hook_function('graphs_action_prepare', $save);
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue'>";
		}
	}else{
		print "<tr><td class='even'><span class='textError'>You must select at least one graph.</span></td></tr>\n";
		$save_html = "<input type='button' value='Return' onClick='window.history.back()'>";
	}

	print "	<tr>
			<td align='right' class='saveRow'>
				<input type='hidden' name='action' value='actions'>
				<input type='hidden' name='selected_items' value='" . (isset($graph_array) ? serialize($graph_array) : '') . "'>
				<input type='hidden' name='drp_action' value='" . $_POST['drp_action'] . "'>
				$save_html
			</td>
		</tr>
		";

	html_end_box();

	bottom_footer();
}

/* -----------------------
    item - Graph Items
   ----------------------- */

function item() {
	global $consolidation_functions, $graph_item_types, $struct_graph_item;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var('id'));
	/* ==================================================== */

	if (empty($_GET['id'])) {
		$template_item_list = array();

		$header_label = '[new]';
	}else{
		$template_item_list = db_fetch_assoc_prepared('SELECT
			graph_templates_item.id,
			graph_templates_item.text_format,
			graph_templates_item.value,
			graph_templates_item.hard_return,
			graph_templates_item.graph_type_id,
			graph_templates_item.consolidation_function_id,
			data_template_rrd.data_source_name,
			cdef.name AS cdef_name,
			colors.hex
			FROM graph_templates_item
			LEFT JOIN data_template_rrd ON (graph_templates_item.task_item_id = data_template_rrd.id)
			LEFT JOIN data_local ON (data_template_rrd.local_data_id = data_local.id)
			LEFT JOIN data_template_data ON (data_local.id = data_template_data.local_data_id)
			LEFT JOIN cdef ON (cdef_id = cdef.id)
			LEFT JOIN colors ON (color_id = colors.id)
			WHERE graph_templates_item.local_graph_id = ?
			ORDER BY graph_templates_item.sequence', array($_GET['id']));

		$host_id = db_fetch_cell_prepared('SELECT host_id FROM graph_local WHERE id = ?', array($_GET['id']));
		$header_label = '[edit: ' . htmlspecialchars(get_graph_title($_GET['id'])) . ']';
	}

	$graph_template_id = db_fetch_cell_prepared('SELECT graph_template_id FROM graph_local WHERE id = ?', array($_GET['id']));

	if (empty($graph_template_id)) {
		$add_text = 'graphs_items.php?action=item_edit&local_graph_id=' . $_GET['id'] . "&host_id=$host_id";
	}else{
		$add_text = '';
	}

	html_start_box("<strong>Graph Items</strong> $header_label", '100%', '', '3', 'center', $add_text);
	draw_graph_items_list($template_item_list, 'graphs_items.php', 'local_graph_id=' . $_GET['id'], (empty($graph_template_id) ? false : true));
	html_end_box();
}

/* ------------------------------------
    graph - Graphs
   ------------------------------------ */

function graph_diff() {
	global  $struct_graph_item, $graph_item_types, $consolidation_functions;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var('id'));
	input_validate_input_number(get_request_var('graph_template_id'));
	/* ==================================================== */

	$template_query = "SELECT
		graph_templates_item.id,
		graph_templates_item.text_format,
		graph_templates_item.value,
		graph_templates_item.hard_return,
		graph_templates_item.consolidation_function_id,
		graph_templates_item.graph_type_id,
		CONCAT_WS(' - ', data_template_data.name, data_template_rrd.data_source_name) AS task_item_id,
		cdef.name AS cdef_id,
		colors.hex AS color_id
		FROM graph_templates_item
		LEFT JOIN data_template_rrd ON (graph_templates_item.task_item_id = data_template_rrd.id)
		LEFT JOIN data_local ON (data_template_rrd.local_data_id = data_local.id)
		LEFT JOIN data_template_data ON (data_local.id = data_template_data.local_data_id)
		LEFT JOIN cdef ON (cdef_id = cdef.id)
		LEFT JOIN colors ON (color_id = colors.id)";

	/* first, get information about the graph template as that's what we're going to model this
	graph after */
	$graph_template_items = db_fetch_assoc_prepared("
		$template_query
		WHERE graph_templates_item.graph_template_id = ?
		AND graph_templates_item.local_graph_id = 0
		ORDER BY graph_templates_item.sequence", array($_GET['graph_template_id']));

	/* next, get information about the current graph so we can make the appropriate comparisons */
	$graph_items = db_fetch_assoc_prepared("
		$template_query
		WHERE graph_templates_item.local_graph_id = ?
		ORDER BY graph_templates_item.sequence", array($_GET['id']));

	$graph_template_inputs = db_fetch_assoc_prepared('SELECT
		graph_template_input.column_name,
		graph_template_input_defs.graph_template_item_id
		FROM (graph_template_input, graph_template_input_defs)
		WHERE graph_template_input.id = graph_template_input_defs.graph_template_input_id
		AND graph_template_input.graph_template_id = ?', array($_GET['graph_template_id']));

	/* ok, we want to loop through the array with the GREATEST number of items so we don't have to worry
	about tacking items on the end */
	if (sizeof($graph_template_items) > sizeof($graph_items)) {
		$items = $graph_template_items;
	}else{
		$items = $graph_items;
	}

	?>
	<table class="tableConfirmation" width="100%" align="center">
		<tr>
			<td class="textArea">
				The template you have selected requires some changes to be made to the structure of
				your graph. Below is a preview of your graph along with changes that need to be completed
				as shown in the left-hand column.
			</td>
		</tr>
	</table>
	<br>
	<?php

	html_start_box('<strong>Graph Preview</strong>', '100%', '', '3', 'center', '');

	$graph_item_actions = array('normal' => '', 'add' => '+', 'delete' => '-');

	$group_counter = 0; $i = 0; $mode = 'normal'; $_graph_type_name = '';

	if (sizeof($items) > 0) {
	foreach ($items as $item) {
		reset($struct_graph_item);

		/* graph grouping display logic */
		$bold_this_row = false; $use_custom_row_color = false; $action_css = ''; unset($graph_preview_item_values);

		if ((sizeof($graph_template_items) > sizeof($graph_items)) && ($i >= sizeof($graph_items))) {
			$mode = 'add';
			$user_message = "When you click save, the items marked with a '<strong>+</strong>' will be added <strong>(Recommended)</strong>.";
		}elseif ((sizeof($graph_template_items) < sizeof($graph_items)) && ($i >= sizeof($graph_template_items))) {
			$mode = 'delete';
			$user_message = "When you click save, the items marked with a '<strong>-</strong>' will be removed <strong>(Recommended)</strong>.";
		}

		/* here is the fun meshing part. first we check the graph template to see if there is an input
		for each field of this row. if there is, we revert to the value stored in the graph, if not
		we revert to the value stored in the template. got that? ;) */
		for ($j=0; ($j < count($graph_template_inputs)); $j++) {
			if ($graph_template_inputs[$j]['graph_template_item_id'] == (isset($graph_template_items[$i]['id']) ? $graph_template_items[$i]['id'] : '')) {
				/* if we find out that there is an "input" covering this field/item, use the
				value FROM the graph, not the template */
				$graph_item_field_name = (isset($graph_template_inputs[$j]['column_name']) ? $graph_template_inputs[$j]['column_name'] : '');
				$graph_preview_item_values[$graph_item_field_name] = (isset($graph_items[$i][$graph_item_field_name]) ? $graph_items[$i][$graph_item_field_name] : '');
			}
		}

		/* go back through each graph field and find out which ones haven't been covered by the
		"inputs" above. for each one, use the value FROM the template */
		while (list($field_name, $field_array) = each($struct_graph_item)) {
			if ($mode == 'delete') {
				$graph_preview_item_values[$field_name] = (isset($graph_items[$i][$field_name]) ? $graph_items[$i][$field_name] : '');
			}elseif (!isset($graph_preview_item_values[$field_name])) {
				$graph_preview_item_values[$field_name] = (isset($graph_template_items[$i][$field_name]) ? $graph_template_items[$i][$field_name] : '');
			}
		}

		/* "prepare" array values */
		$consolidation_function_id = $graph_preview_item_values['consolidation_function_id'];
		$graph_type_id = $graph_preview_item_values['graph_type_id'];

		/* color logic */
		if (($graph_item_types[$graph_type_id] != 'GPRINT') && ($graph_item_types[$graph_type_id] != $_graph_type_name)) {
			$bold_this_row = true; $use_custom_row_color = true; $hard_return = '';

			if ($group_counter % 2 == 0) {
				$alternate_color_1 = 'graphItemGr1Alt1';
				$alternate_color_2 = 'graphItemGr1Alt2';
				$custom_row_color  = 'graphItemGr1Cust';
			}else{
				$alternate_color_1 = 'graphItemGr2Alt1';
				$alternate_color_2 = 'graphItemGr2Alt2';
				$custom_row_color  = 'graphItemGr2Cust';
			}

			$group_counter++;
		}

		$_graph_type_name = $graph_item_types[$graph_type_id];

		/* alternating row colors */
		if ($use_custom_row_color == false) {
			if ($i % 2 == 0) {
				$action_column_color = $alternate_color_1;
			}else{
				$action_column_color = $alternate_color_2;
			}
		}else{
			$action_column_color = $custom_row_color;
		}

		print "<tr class='#$action_column_color'>"; $i++;

		/* make the left-hand column blue or red depending on if "add"/"remove" mode is set */
		if ($mode == 'add') {
			$action_column_color = 'graphItemAdd';
			$action_css = '';
		}elseif ($mode == 'delete') {
			$action_column_color = 'graphItemDel';
			$action_css = 'text-decoration: line-through;';
		}

		if ($bold_this_row == true) {
			$action_css .= ' font-weight:bold;';
		}

		/* draw the TD that shows the user whether we are going to: KEEP, ADD, or DROP the item */
		print "<td width='1%' class='#$action_column_color' style='font-weight: bold; color: white;'>" . $graph_item_actions[$mode] . '</td>';
		print "<td style='$action_css'><strong>Item # " . $i . "</strong></td>\n";

		if (empty($graph_preview_item_values['task_item_id'])) { $graph_preview_item_values['task_item_id'] = 'No Task'; }

		switch (true) {
		case preg_match('/(AREA|STACK|GPRINT|LINE[123])/', $_graph_type_name):
			$matrix_title = '(' . $graph_preview_item_values['task_item_id'] . '): ' . $graph_preview_item_values['text_format'];
			break;
		case preg_match('/(HRULE|VRULE)/', $_graph_type_name):
			$matrix_title = 'HRULE: ' . $graph_preview_item_values['value'];
			break;
		case preg_match('/(COMMENT)/', $_graph_type_name):
			$matrix_title = 'COMMENT: ' . $graph_preview_item_values['text_format'];
			break;
		}

		/* use the cdef name (if in use) if all else fails */
		if ($matrix_title == '') {
			if ($graph_preview_item_values['cdef_id'] != '') {
				$matrix_title .= 'CDEF: ' . $graph_preview_item_values['cdef_id'];
			}
		}

		if ($graph_preview_item_values['hard_return'] == 'on') {
			$hard_return = "<strong><font class='graphItemHR'>&lt;HR&gt;</font></strong>";
		}

		print "<td style='$action_css'>" . htmlspecialchars($matrix_title) . $hard_return . "</td>\n";
		print "<td style='$action_css'>" . $graph_item_types{$graph_preview_item_values['graph_type_id']} . "</td>\n";
		print "<td style='$action_css'>" . $consolidation_functions{$graph_preview_item_values['consolidation_function_id']} . "</td>\n";
		print '<td' . ((!empty($graph_preview_item_values['color_id'])) ? " bgcolor='#" . $graph_preview_item_values['color_id'] . "'" : '') . " width='1%'>&nbsp;</td>\n";
		print "<td style='$action_css'>" . $graph_preview_item_values['color_id'] . "</td>\n";

		print '</tr>';
	}
	}else{
		print "<tr class='tableRow'><td colspan='7'><em>No Items</em></td></tr>\n";
	}
	html_end_box();

	?>
	<form action="graphs.php" method="post">
	<table class='tableConfirmation' width="100%" align="center">
		<tr>
			<td class="textArea">
				<input type='radio' name='type' value='1' checked>&nbsp;<?php print $user_message;?><br>
				<input type='radio' name='type' value='2'>&nbsp;When you click save, the graph items will remain untouched (could cause inconsistencies).
			</td>
		</tr>
	</table>
	<br>
	<input type="hidden" name="action" value="save">
	<input type="hidden" name="save_component_graph_diff" value="1">
	<input type="hidden" name="local_graph_id" value="<?php print $_GET['id'];?>">
	<input type="hidden" name="graph_template_id" value="<?php print $_GET['graph_template_id'];?>">
	<?php

	form_save_button('graphs.php?action=graph_edit&id=' . $_GET['id']);
}

function graph_edit() {
	global $struct_graph, $image_types, $consolidation_functions, $graph_item_types, $struct_graph_item;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var('id'));
	/* ==================================================== */

	$use_graph_template = true;

	if (!empty($_GET['id'])) {
		$local_graph_template_graph_id = db_fetch_cell_prepared('SELECT local_graph_template_graph_id FROM graph_templates_graph WHERE local_graph_id = ?', array($_GET['id']));

		$graphs = db_fetch_row_prepared('SELECT * FROM graph_templates_graph WHERE local_graph_id = ?', array($_GET['id']));
		$graphs_template = db_fetch_row_prepared('SELECT * FROM graph_templates_graph WHERE id = ?', array($local_graph_template_graph_id));

		$host_id = db_fetch_cell_prepared('SELECT host_id FROM graph_local WHERE id = ?', array($_GET['id']));
		$header_label = '[edit: ' . htmlspecialchars(get_graph_title($_GET['id'])) . ']';

		if ($graphs['graph_template_id'] == '0') {
			$use_graph_template = false;
		}
	}else{
		$header_label = '[new]';
		$use_graph_template = false;
	}

	/* handle debug mode */
	if (isset($_GET['debug'])) {
		if ($_GET['debug'] == '0') {
			kill_session_var('graph_debug_mode');
		}elseif ($_GET['debug'] == '1') {
			$_SESSION['graph_debug_mode'] = true;
		}
	}

	if (!empty($_GET['id'])) {
		?>
		<table width="100%" align="center">
			<tr>
				<td class="textInfo" colspan="2" valign="top">
					<?php print htmlspecialchars(get_graph_title($_GET['id']));?>
				</td>
				<td class="textInfo" align="right" valign="top">
					<span class="linkMarker">*<a href='<?php print htmlspecialchars('graphs.php?action=graph_edit&id=' . (isset($_GET['id']) ? $_GET['id'] : '0') . '&debug=' . (isset($_SESSION['graph_debug_mode']) ? '0' : '1'));?>'>Turn <strong><?php print (isset($_SESSION['graph_debug_mode']) ? 'Off' : 'On');?></strong> Graph Debug Mode.</a></span><br>
					<?php
						if (!empty($graphs['graph_template_id'])) {
							?><span class="linkMarker">*<a href='<?php print htmlspecialchars('graph_templates.php?action=template_edit&id=' . (isset($graphs['graph_template_id']) ? $graphs['graph_template_id'] : '0'));?>'>Edit Graph Template.</a></span><br><?php
						}
						if (!empty($_GET['host_id']) || !empty($host_id)) {
							?><span class="linkMarker">*<a href='<?php print htmlspecialchars('host.php?action=edit&id=' . (isset($_GET['host_id']) ? $_GET['host_id'] : $host_id));?>'>Edit Device.</a></span><br><?php
						}
					?>
				</td>
			</tr>
		</table>
		<br>
		<?php
	}

	html_start_box("<strong>Graph Template Selection</strong> $header_label", '100%', '', '3', 'center', '');

	$form_array = array(
		'graph_template_id' => array(
			'method' => 'drop_sql',
			'friendly_name' => 'Selected Graph Template',
			'description' => 'Choose a graph template to apply to this graph. Please note that graph data may be lost if you change the graph template after one is already applied.',
			'value' => (isset($graphs) ? $graphs['graph_template_id'] : '0'),
			'none_value' => 'None',
			'sql' => 'SELECT graph_templates.id,graph_templates.name FROM graph_templates ORDER BY name'
			),
		'host_id' => array(
			'method' => 'drop_sql',
			'friendly_name' => 'Device',
			'description' => 'Choose the host that this graph belongs to.',
			'value' => (isset($_GET['host_id']) ? $_GET['host_id'] : $host_id),
			'none_value' => 'None',
			'sql' => "SELECT id,CONCAT_WS('',description,' (',hostname,')') as name FROM host ORDER BY description,hostname"
			),
		'graph_template_graph_id' => array(
			'method' => 'hidden',
			'value' => (isset($graphs) ? $graphs['id'] : '0')
			),
		'local_graph_id' => array(
			'method' => 'hidden',
			'value' => (isset($graphs) ? $graphs['local_graph_id'] : '0')
			),
		'local_graph_template_graph_id' => array(
			'method' => 'hidden',
			'value' => (isset($graphs) ? $graphs['local_graph_template_graph_id'] : '0')
			),
		'_graph_template_id' => array(
			'method' => 'hidden',
			'value' => (isset($graphs) ? $graphs['graph_template_id'] : '0')
			),
		'_host_id' => array(
			'method' => 'hidden',
			'value' => (isset($host_id) ? $host_id : '0')
			)
		);

	draw_edit_form(
		array(
			'config' => array(),
			'fields' => $form_array
			)
		);

	html_end_box();

	/* only display the "inputs" area if we are using a graph template for this graph */
	if (!empty($graphs['graph_template_id'])) {
		html_start_box('<strong>Supplemental Graph Template Data</strong>', '100%', '', '3', 'center', '');

		draw_nontemplated_fields_graph($graphs['graph_template_id'], $graphs, '|field|', '<strong>Graph Fields</strong>', true, true, 0);
		draw_nontemplated_fields_graph_item($graphs['graph_template_id'], $_GET['id'], '|field|_|id|', '<strong>Graph Item Fields</strong>', true);

		html_end_box();
	}

	/* graph item list goes here */
	if ((!empty($_GET['id'])) && (empty($graphs['graph_template_id']))) {
		item();
	}

	if (!empty($_GET['id'])) {
		?>
		<table width="100%" align="center">
			<tr>
				<td align="center" class="textInfo" colspan="2">
					<img src="<?php print htmlspecialchars('graph_image.php?action=edit&local_graph_id=' . $_GET['id'] . '&rra_id=' . read_graph_config_option('default_rra_id'));?>" alt="">
				</td>
				<?php
				if ((isset($_SESSION['graph_debug_mode'])) && (isset($_GET['id']))) {
					$graph_data_array['output_flag'] = RRDTOOL_OUTPUT_STDERR;
					$graph_data_array['print_source'] = 1;
					?>
					<td>
						<span class="textInfo">RRDTool Command:</span><br>
						<pre><?php print @rrdtool_function_graph($_GET['id'], 1, $graph_data_array);?></pre>
						<span class="textInfo">RRDTool Says:</span><br>
						<?php unset($graph_data_array['print_source']);?>
						<pre><?php print @rrdtool_function_graph($_GET['id'], 1, $graph_data_array);?></pre>
					</td>
					<?php
				}
				?>
			</tr>
		</table>
		<br>
		<?php
	}

	if (((isset($_GET['id'])) || (isset($_GET['new']))) && (empty($graphs['graph_template_id']))) {
		html_start_box('<strong>Graph Configuration</strong>', '100%', '', '3', 'center', '');

		$form_array = array();

		while (list($field_name, $field_array) = each($struct_graph)) {
			$form_array += array($field_name => $struct_graph[$field_name]);

			$form_array[$field_name]['value'] = (isset($graphs) ? $graphs[$field_name] : '');
			$form_array[$field_name]['form_id'] = (isset($graphs) ? $graphs['id'] : '0');

			if (!(($use_graph_template == false) || ($graphs_template{'t_' . $field_name} == 'on'))) {
				$form_array[$field_name]['method'] = 'template_' . $form_array[$field_name]['method'];
				$form_array[$field_name]['description'] = '';
			}
		}

		draw_edit_form(
			array(
				'config' => array(
					'no_form_tag' => true
					),
				'fields' => $form_array
				)
			);

		html_end_box();
	}

	if ((isset($_GET['id'])) || (isset($_GET['new']))) {
		form_hidden_box('save_component_graph','1','');
		form_hidden_box('save_component_input','1','');
	}else{
		form_hidden_box('save_component_graph_new','1','');
	}

	form_hidden_box('rrdtool_version', read_config_option('rrdtool_version'), '');
	form_save_button('graphs.php');

	//Now we need some javascript to make it dynamic
	?>
	<script type='text/javascript'>

	dynamic();

	function dynamic() {
		if ($('#scale_log_units').is(':checked')) {
			$('#scale_log_units').prop('disabled', true);
			if ($('#auto_scale_log').is(':checked')) {
				$('#scale_log_units').prop('disabled', false);
			}
		}
	}

	function changeScaleLog() {
		if ($('#scale_log_units').is(':checked')) {
			$('#scale_log_units').prop('disabled', true);
			if ($('#auto_scale_log').is(':checked')) {
				$('#scale_log_units').prop('disabled', false);
			}
		}
	}
	</script>
	<?php
}

function graph() {
	global $colors, $graph_actions, $item_rows;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request('host_id'));
	input_validate_input_number(get_request_var_request('rows'));
	input_validate_input_number(get_request_var_request('template_id'));
	input_validate_input_number(get_request_var_request('page'));
	/* ==================================================== */

	/* clean up search string */
	if (isset($_REQUEST['filter'])) {
		$_REQUEST['filter'] = sanitize_search_string(get_request_var('filter'));
	}

	/* clean up sort_column string */
	if (isset($_REQUEST['sort_column'])) {
		$_REQUEST['sort_column'] = sanitize_search_string(get_request_var('sort_column'));
	}

	/* clean up sort_direction string */
	if (isset($_REQUEST['sort_direction'])) {
		$_REQUEST['sort_direction'] = sanitize_search_string(get_request_var('sort_direction'));
	}

	/* if the user pushed the 'clear' button */
	if (isset($_REQUEST['clear_x'])) {
		kill_session_var('sess_graph_current_page');
		kill_session_var('sess_graph_filter');
		kill_session_var('sess_graph_sort_column');
		kill_session_var('sess_graph_sort_direction');
		kill_session_var('sess_graph_host_id');
		kill_session_var('sess_default_rows');
		kill_session_var('sess_graph_template_id');

		unset($_REQUEST['page']);
		unset($_REQUEST['filter']);
		unset($_REQUEST['sort_column']);
		unset($_REQUEST['sort_direction']);
		unset($_REQUEST['host_id']);
		unset($_REQUEST['rows']);
		unset($_REQUEST['template_id']);
	}

	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value('page', 'sess_graph_current_page', '1');
	load_current_session_value('filter', 'sess_graph_filter', '');
	load_current_session_value('sort_column', 'sess_graph_sort_column', 'title_cache');
	load_current_session_value('sort_direction', 'sess_graph_sort_direction', 'ASC');
	load_current_session_value('host_id', 'sess_graph_host_id', '-1');
	load_current_session_value('rows', 'sess_default_rows', read_config_option('num_rows_table'));
	load_current_session_value('template_id', 'sess_graph_template_id', '-1');

	/* if the number of rows is -1, set it to the default */
	if (get_request_var_request('rows') == -1) {
		$_REQUEST['rows'] = read_config_option('num_rows_table');
	}

	?>
	<script type="text/javascript">
	<!--

	function applyFilter() {
		strURL = 'graphs.php?host_id=' + $('#host_id').val();
		strURL = strURL + '&rows=' + $('#rows').val();
		strURL = strURL + '&page=' + $('#page').val();
		strURL = strURL + '&filter=' + $('#filter').val();
		strURL = strURL + '&template_id=' + $('#template_id').val();
		strURL = strURL + '&header=false';
		$.get(strURL, function(data) {
			$('#main').html(data);
			applySkin();
		});
	}

	function clearFilter() {
		strURL = 'graphs.php?clear_x=1&header=false';
		$.get(strURL, function(data) {
			$('#main').html(data);
			applySkin();
		});
	}


	$(function() {
		$('#refresh').click(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});

		$('#form_graphs').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});
	});

	-->
	</script>
	<?php

	if (read_config_option('grds_creation_method') == 1) {
		$add_url = htmlspecialchars('graphs.php?action=graph_edit&host_id=' . get_request_var_request('host_id'));
	}else{
		$add_url = '';
	}

	html_start_box('<strong>Graph Management</strong>', '100%', '', '3', 'center', $add_url);

	?>
	<tr class='even noprint'>
		<td>
			<form id='form_graphs' name="form_graphs" action="graphs.php">
			<table cellpadding="2" cellspacing="0" border="0">
				<tr>
					<td width="50">
						Device
					</td>
					<td>
						<select id='host_id' name='host_id' onChange="applyFilter()">
							<option value="-1"<?php if (get_request_var_request('host_id') == '-1') {?> selected<?php }?>>Any</option>
							<option value="0"<?php if (get_request_var_request('host_id') == '0') {?> selected<?php }?>>None</option>
							<?php
							$hosts = get_allowed_devices();
							if (sizeof($hosts) > 0) {
								foreach ($hosts as $host) {
									print "<option value='" . $host['id'] . "'"; if (get_request_var_request('host_id') == $host['id']) { print ' selected'; } print '>' . title_trim(htmlspecialchars($host['description']), 40) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
					<td>
						Template
					</td>
					<td>
						<select id='template_id' name='template_id' onChange='applyFilter()'>
							<option value="-1"<?php if (get_request_var_request('template_id') == '-1') {?> selected<?php }?>>Any</option>
							<option value="0"<?php if (get_request_var_request('template_id') == '0') {?> selected<?php }?>>None</option>
							<?php
							$templates = get_allowed_graph_templates();
							if (sizeof($templates) > 0) {
								foreach ($templates as $template) {
									print "<option value='" . $template['id'] . "'"; if (get_request_var_request('template_id') == $template['id']) { print ' selected'; } print '>' . title_trim(htmlspecialchars($template['name']), 40) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
					<td>
						<input type="button" id='refresh' value="Go" title="Set/Refresh Filters">
					</td>
					<td>
						<input type="button" id='clear' name="clear_x" value="Clear" title="Clear Filters">
					</td>
				</tr>
			</table>
			<table cellpadding="2" cellspacing="0" border="0">
				<tr>
					<td width="50">
						Search
					</td>
					<td>
						<input id='filter' type="text" name="filter" size="25" value="<?php print htmlspecialchars(get_request_var_request('filter'));?>">
					</td>
					<td>
						Graphs
					</td>
					<td>
						<select id='rows' name='rows' onChange='applyFilter()'>
							<?php
							if (sizeof($item_rows) > 0) {
								foreach ($item_rows as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var_request('rows') == $key) { print ' selected'; } print '>' . htmlspecialchars($value) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' name='page' value='<?php print $_REQUEST['page'];?>'>
			</form>
		</td>
	</tr>
	<?php

	html_end_box();

	/* form the 'where' clause for our main sql query */
	if (strlen(get_request_var_request('filter'))) {
		$sql_where = " AND (graph_templates_graph.title_cache like '%%" . get_request_var_request('filter') . "%%'" .
			" OR graph_templates.name like '%%" . get_request_var_request('filter') . "%%')";
	}else{
		$sql_where = '';
	}

	if (get_request_var_request('host_id') == '-1') {
		/* Show all items */
	}elseif (get_request_var_request('host_id') == '0') {
		$sql_where .= ' AND graph_local.host_id=0';
	}elseif (!empty($_REQUEST['host_id'])) {
		$sql_where .= ' AND graph_local.host_id=' . get_request_var_request('host_id');
	}

	if (get_request_var_request('template_id') == '-1') {
		/* Show all items */
	}elseif (get_request_var_request('template_id') == '0') {
		$sql_where .= ' AND graph_templates_graph.graph_template_id=0';
	}elseif (!empty($_REQUEST['template_id'])) {
		$sql_where .= ' AND graph_templates_graph.graph_template_id=' . get_request_var_request('template_id');
	}

	/* allow plugins to modify sql_where */
	$sql_where .= api_plugin_hook_function('graphs_sql_where', $sql_where);

	/* print checkbox form for validation */
	print "<form name='chk' method='post' action='graphs.php'>\n";

	html_start_box('', '100%', '', '3', 'center', '');

	$total_rows = db_fetch_cell("SELECT
		COUNT(graph_templates_graph.id)
		FROM (graph_local, graph_templates_graph)
		LEFT JOIN graph_templates ON (graph_local.graph_template_id = graph_templates.id)
		WHERE graph_local.id = graph_templates_graph.local_graph_id
		$sql_where");

	$graph_list = db_fetch_assoc("SELECT
		graph_templates_graph.id,
		graph_templates_graph.local_graph_id,
		graph_templates_graph.height,
		graph_templates_graph.width,
		graph_templates_graph.title_cache,
		graph_templates.name,
		graph_local.host_id
		FROM (graph_local, graph_templates_graph)
		LEFT JOIN graph_templates ON (graph_local.graph_template_id = graph_templates.id)
		WHERE graph_local.id = graph_templates_graph.local_graph_id
		$sql_where
		ORDER BY " . $_REQUEST['sort_column'] . ' ' . get_request_var_request('sort_direction') .
		' LIMIT ' . (get_request_var_request('rows')*(get_request_var_request('page')-1)) . ',' . get_request_var_request('rows'));

	$nav = html_nav_bar('graphs.php?filter=' . get_request_var_request('filter') . '&host_id=' . get_request_var_request('host_id'), MAX_DISPLAY_PAGES, get_request_var_request('page'), get_request_var_request('rows'), $total_rows, 5, 'Graphs', 'page', 'main');

	print $nav;

	$display_text = array(
		'title_cache' => array('Graph Title', 'ASC'),
		'local_graph_id' => array('display' => 'ID', 'align' => 'right', 'sort' => 'ASC'),
		'name' => array('Template Name', 'ASC'),
		'height' => array('Size', 'ASC'));

	html_header_sort_checkbox($display_text, get_request_var_request('sort_column'), get_request_var_request('sort_direction'), false);

	$i = 0;
	if (sizeof($graph_list) > 0) {
		foreach ($graph_list as $graph) {
			/* we're escaping strings here, so no need to escape them on form_selectable_cell */
			$template_name = ((empty($graph['name'])) ? '<em>None</em>' : htmlspecialchars($graph['name']));
			form_alternate_row('line' . $graph['local_graph_id'], true);
			form_selectable_cell("<a class='linkEditMain' href='" . htmlspecialchars('graphs.php?action=graph_edit&id=' . $graph['local_graph_id']) . "' title='" . htmlspecialchars($graph['title_cache']) . "'>" . ((get_request_var_request('filter') != '') ? preg_replace('/(' . preg_quote(get_request_var_request('filter'), '/') . ')/i', "<span class='filteredValue'>\\1</span>", title_trim(htmlspecialchars($graph['title_cache']), read_config_option('max_title_length'))) : title_trim(htmlspecialchars($graph['title_cache']), read_config_option('max_title_length'))) . '</a>', $graph['local_graph_id']);
			form_selectable_cell($graph['local_graph_id'], $graph['local_graph_id'], '', 'text-align:right');
			form_selectable_cell(((get_request_var_request('filter') != '') ? preg_replace('/(' . preg_quote(get_request_var_request('filter'), '/') . ')/i', "<span class='filteredValue'>\\1</span>", $template_name) : $template_name), $graph['local_graph_id']);
			form_selectable_cell($graph['height'] . 'x' . $graph['width'], $graph['local_graph_id']);
			form_checkbox_cell($graph['title_cache'], $graph['local_graph_id']);
			form_end_row();
		}

		/* put the nav bar on the bottom as well */
		print $nav;
	}else{
		print "<tr class='tableRow'><td colspan='5'><em>No Graphs Found</em></td></tr>";
	}

	html_end_box(false);

	/* add a list of tree names to the actions dropdown */
	add_tree_names_to_actions_array();

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($graph_actions);

	print "</form>\n";
}

