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
include_once('./lib/cdef.php');

define('MAX_DISPLAY_PAGES', 21);

$cdef_actions = array(
	1 => 'Delete',
	2 => 'Duplicate'
	);

/* set default action */
if (!isset($_REQUEST['action'])) { $_REQUEST['action'] = ''; }

switch ($_REQUEST['action']) {
	case 'save':
		form_save();

		break;
	case 'actions':
		form_actions();

		break;
	case 'item_movedown':
		item_movedown();

		header('Location: cdef.php?action=edit&id=' . $_REQUEST['cdef_id']);
		break;
	case 'item_moveup':
		item_moveup();

		header('Location: cdef.php?action=edit&id=' . $_REQUEST['cdef_id']);
		break;
	case 'item_remove':
		item_remove();

		header('Location: cdef.php?action=edit&id=' . $_REQUEST['cdef_id']);
		break;
	case 'item_edit':
		top_header();

		item_edit();

		bottom_footer();
		break;
	case 'edit':
		top_header();

		cdef_edit();

		bottom_footer();
		break;
	default:
		top_header();

		cdef();

		bottom_footer();
		break;
}

/* --------------------------
    Global Form Functions
   -------------------------- */

function draw_cdef_preview($cdef_id) {
	?>
	<tr class='even'>
		<td>
			<pre>cdef=<?php print get_cdef($cdef_id, true);?></pre>
		</td>
	</tr>
<?php }


/* --------------------------
    The Save Function
   -------------------------- */

function form_save() {
	if (isset($_POST['save_component_cdef'])) {
		$save['id']   = form_input_validate($_POST['id'], 'id', '^[0-9]+$', false, 3);
		$save['hash'] = get_hash_cdef($_POST['id']);
		$save['name'] = form_input_validate($_POST['name'], 'name', '', false, 3);

		if (!is_error_message()) {
			$cdef_id = sql_save($save, 'cdef');

			if ($cdef_id) {
				raise_message(1);
			}else{
				raise_message(2);
			}
		}

		header('Location: cdef.php?action=edit&id=' . (empty($cdef_id) ? $_POST['id'] : $cdef_id));
	}elseif (isset($_POST['save_component_item'])) {
		$sequence = get_sequence($_POST['id'], 'sequence', 'cdef_items', 'cdef_id=' . $_POST['cdef_id']);

		$save['id']       = form_input_validate($_POST['id'], 'id', '^[0-9]+$', false, 3);
		$save['hash']     = get_hash_cdef($_POST['id'], 'cdef_item');
		$save['cdef_id']  = form_input_validate($_POST['cdef_id'], 'cdef_id', '^[0-9]+$', false, 3);
		$save['sequence'] = $sequence;
		$save['type']     = form_input_validate($_POST['type'], 'type', '^[0-9]+$', false, 3);
		$save['value']    = form_input_validate($_POST['value'], 'value', '', false, 3);

		if (!is_error_message()) {
			$cdef_item_id = sql_save($save, 'cdef_items');

			if ($cdef_item_id) {
				raise_message(1);
			}else{
				raise_message(2);
			}
		}

		if (is_error_message()) {
			header('Location: cdef.php?action=item_edit&cdef_id=' . $_POST['cdef_id'] . '&id=' . (empty($cdef_item_id) ? $_POST['id'] : $cdef_item_id));
		}else{
			header('Location: cdef.php?action=edit&id=' . $_POST['cdef_id']);
		}
	}
}

/* ------------------------
    The 'actions' function
   ------------------------ */

function form_actions() {
	global $cdef_actions;

	/* ================= input validation ================= */
	input_validate_input_regex(get_request_var_post('drp_action'), '^([a-zA-Z0-9_]+)$');
	/* ==================================================== */
	
	/* if we are to save this form, instead of display it */
	if (isset($_POST['selected_items'])) {
		$selected_items = unserialize(stripslashes($_POST['selected_items']));

		if ($_POST['drp_action'] == '1') { /* delete */
			db_execute('delete from cdef where ' . array_to_sql_or($selected_items, 'id'));
			db_execute('delete from cdef_items where ' . array_to_sql_or($selected_items, 'cdef_id'));

		}elseif ($_POST['drp_action'] == '2') { /* duplicate */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				duplicate_cdef($selected_items[$i], $_POST['title_format']);
			}
		}

		header('Location: cdef.php');
		exit;
	}

	/* setup some variables */
	$cdef_list = ''; $i = 0;

	/* loop through each of the graphs selected on the previous page and get more info about them */
	while (list($var,$val) = each($_POST)) {
		if (preg_match('/^chk_([0-9]+)$/', $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$cdef_list .= '<li>' . htmlspecialchars(db_fetch_cell('select name from cdef where id=' . $matches[1])) . '</li>';
			$cdef_array[$i] = $matches[1];

			$i++;
		}
	}

	top_header();

	print "<form action='cdef.php' method='post'>\n";

	html_start_box('<strong>' . $cdef_actions{$_POST['drp_action']} . '</strong>', '60%', '', '3', 'center', '');

	if (isset($cdef_array) && sizeof($cdef_array)) {
		if ($_POST['drp_action'] == '1') { /* delete */
			print "	<tr>
					<td class='textArea' class='odd'>
						<p>When you click \"Continue\", the folling CDEF(s) will be deleted.</p>
						<p><ul>$cdef_list</ul></p>
					</td>
				</tr>\n";

			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Delete CDEF(s)'>";
		}elseif ($_POST['drp_action'] == '2') { /* duplicate */
			print "	<tr>
					<td class='textArea' class='odd'>
						<p>When you click \"Continue\", the following CDEFs will be duplicated. You can
						optionally change the title format for the new CDEFs.</p>
						<p><ul>$cdef_list</ul></p>
						<p><strong>Title Format:</strong><br>"; form_text_box("title_format", "<cdef_title> (1)", "", "255", "30", "text"); print "</p>
					</td>
				</tr>\n";

			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Duplicate CDEF(s)'>";
		}
	}else{
		print "<tr><td class='odd'><span class='textError'>You must select at least one CDEF.</span></td></tr>\n";
		$save_html = "<input type='button' value='Return' onClick='window.history.back()'>";
	}

	print "	<tr>
			<td align='right' class='saveRow'>
				<input type='hidden' name='action' value='actions'>
				<input type='hidden' name='selected_items' value='" . (isset($cdef_array) ? serialize($cdef_array) : '') . "'>
				<input type='hidden' name='drp_action' value='" . $_POST['drp_action'] . "'>
				$save_html
			</td>
		</tr>\n";

	html_end_box();

	bottom_footer();
}

/* --------------------------
    CDEF Item Functions
   -------------------------- */

function item_movedown() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request('id'));
	input_validate_input_number(get_request_var_request('cdef_id'));
	/* ==================================================== */

	move_item_down('cdef_items', $_REQUEST['id'], 'cdef_id=' . $_REQUEST['cdef_id']);
}

function item_moveup() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request('id'));
	input_validate_input_number(get_request_var_request('cdef_id'));
	/* ==================================================== */

	move_item_up('cdef_items', $_REQUEST['id'], 'cdef_id=' . $_REQUEST['cdef_id']);
}

function item_remove() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request('id'));
	input_validate_input_number(get_request_var_request('cdef_id'));
	/* ==================================================== */

	db_execute('delete from cdef_items where id=' . $_REQUEST['id']);
}

function item_edit() {
	global $cdef_item_types, $cdef_functions, $cdef_operators, $custom_data_source_types;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request('id'));
	input_validate_input_number(get_request_var_request('cdef_id'));
	/* ==================================================== */

	if (!empty($_REQUEST['id'])) {
		$cdef = db_fetch_row('select * from cdef_items where id=' . $_REQUEST['id']);
		$current_type = $cdef['type'];
		$values[$current_type] = $cdef['value'];
	}

	html_start_box('', '100%', '', '3', 'center', '');
	draw_cdef_preview($_REQUEST['cdef_id']);
	html_end_box();

	print "<form method='post' action='cdef.php' name='form_cdef'>\n";

	html_start_box('<strong>CDEF Items</strong> [edit: ' . htmlspecialchars(db_fetch_cell('SELECT name FROM cdef WHERE id=' . $_REQUEST['cdef_id'])) . ']', '100%', '', '3', 'center', '');

	if (isset($_REQUEST['type_select'])) {
		$current_type = $_REQUEST['type_select'];
	}elseif (isset($cdef['type'])) {
		$current_type = $cdef['type'];
	}else{
		$current_type = '1';
	}

	form_alternate_row();?>
		<td width='50%'>
			<font class='textEditTitle'>CDEF Item Type</font><br>
			Choose what type of CDEF item this is.
		</td>
		<td>
			<select name='type_select' onChange='window.location=document.form_cdef.type_select.options[document.form_cdef.type_select.selectedIndex].value'>
				<?php
				while (list($var, $val) = each($cdef_item_types)) {
					print "<option value='cdef.php?action=item_edit" . (isset($_REQUEST['id']) ? '&id=' . $_REQUEST['id'] : '') . '&cdef_id=' . $_REQUEST['cdef_id'] . "&type_select=$var'"; if ($var == $current_type) { print ' selected'; } print ">$val</option>\n";
				}
				?>
			</select>
		</td>
	</tr>
	form_alternate_row();?>
		<td width='50%'>
			<font class='textEditTitle'>CDEF Item Value</font><br>
			Enter a value for this CDEF item.
		</td>
		<td>
			<?php
			switch ($current_type) {
			case '1':
				form_dropdown('value', $cdef_functions, '', '', (isset($cdef['value']) ? $cdef['value'] : ''), '', '');
				break;
			case '2':
				form_dropdown('value', $cdef_operators, '', '', (isset($cdef['value']) ? $cdef['value'] : ''), '', '');
				break;
			case '4':
				form_dropdown('value', $custom_data_source_types, '', '', (isset($cdef['value']) ? $cdef['value'] : ''), '', '');
				break;
			case '5':
				form_dropdown('value', db_fetch_assoc('select name,id from cdef order by name'), 'name', 'id', (isset($cdef['value']) ? $cdef['value'] : ''), '', '');
				break;
			case '6':
				form_text_box('value', (isset($cdef['value']) ? $cdef['value'] : ''), '', '255', 30, 'text', (isset($_REQUEST['id']) ? $_REQUEST['id'] : '0'));
				break;
			}
			?>
		</td>
	</tr>
	<?php

	form_hidden_box('id', (isset($_REQUEST['id']) ? $_REQUEST['id'] : '0'), '');
	form_hidden_box('type', $current_type, '');
	form_hidden_box('cdef_id', $_REQUEST['cdef_id'], '');
	form_hidden_box('save_component_item', '1', '');

	html_end_box();

	form_save_button('cdef.php?action=edit&id=' . $_REQUEST['cdef_id']);
}

/* ---------------------
    CDEF Functions
   --------------------- */

function cdef_edit() {
	global $cdef_item_types, $fields_cdef_edit;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request('id'));
	/* ==================================================== */

	if (!empty($_REQUEST['id'])) {
		$cdef = db_fetch_row('select * from cdef where id=' . $_REQUEST['id']);
		$header_label = '[edit: ' . htmlspecialchars($cdef['name']) . ']';
	}else{
		$header_label = '[new]';
	}

	html_start_box("<strong>CDEF's</strong> $header_label", '100%', '', '3', 'center', '');

	draw_edit_form(array(
		'config' => array(),
		'fields' => inject_form_variables($fields_cdef_edit, (isset($cdef) ? $cdef : array()))
		));

	html_end_box();

	if (!empty($_REQUEST['id'])) {
		html_start_box('', '100%', '', '3', 'center', '');
		draw_cdef_preview($_REQUEST['id']);
		html_end_box();

		html_start_box('<strong>CDEF Items</strong>', '100%', '', '3', 'center', 'cdef.php?action=item_edit&cdef_id=' . $cdef['id']);

		print "<tr class='tableHeader'>";
			DrawMatrixHeaderItem('Item', '', 1);
			DrawMatrixHeaderItem('Position', '', 1);
			DrawMatrixHeaderItem('Item Value', '', 2);
		print '</tr>';

		$cdef_items = db_fetch_assoc('SELECT * FROM cdef_items WHERE cdef_id=' . $_REQUEST['id'] . ' ORDER BY sequence');

		$i = 0;
		if (sizeof($cdef_items) > 0) {
			foreach ($cdef_items as $cdef_item) {
				form_alternate_row();$i++;?>
					<td>
						<a class='linkEditMain' href='<?php print htmlspecialchars('cdef.php?action=item_edit&id=' . $cdef_item['id'] . '&cdef_id=' . $cdef['id']);?>'>Item #<?php print htmlspecialchars($i);?></a>
					</td>
					<td align='left' width='50'>
						<a href='<?php print htmlspecialchars('cdef.php?action=item_movedown&id=' . $cdef_item['id'] . '&cdef_id=' . $cdef['id']);?>'><img src='images/move_down.gif' border='0' alt='Move Down'></a>
						<a href='<?php print htmlspecialchars('cdef.php?action=item_moveup&id=' . $cdef_item['id'] . '&cdef_id=' . $cdef['id']);?>'><img src='images/move_up.gif' border='0' alt='Move Up'></a>
					</td>
					<td>
						<em><?php $cdef_item_type = $cdef_item['type']; print $cdef_item_types[$cdef_item_type];?></em>: <strong><?php print htmlspecialchars(get_cdef_item_name($cdef_item['id']));?></strong>
					</td>
					<td align='right'>
						<a href='<?php print htmlspecialchars('cdef.php?action=item_remove&id=' . $cdef_item['id'] . '&cdef_id=' . $cdef['id']);?>'><img src='images/delete_icon.gif' style='height:10px;width:10px;' border='0' alt='Delete'></a>
					</td>
				</tr>
			<?php
			}
		}
		html_end_box();
	}

	form_save_button('cdef.php', 'return');
}

function cdef() {
	global $cdef_actions, $item_rows;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request('page'));
	input_validate_input_number(get_request_var_request('rows'));
	/* ==================================================== */

	/* clean up search string */
	if (isset($_REQUEST['filter'])) {
		$_REQUEST['filter'] = sanitize_search_string(get_request_var_request('filter'));
	}

	/* clean up has_graphs string */
	if (isset($_REQUEST['has_graphs'])) {
		$_REQUEST['has_graphs'] = sanitize_search_string(get_request_var_request('has_graphs'));
	}

	/* clean up sort_column string */
	if (isset($_REQUEST['sort_column'])) {
		$_REQUEST['sort_column'] = sanitize_search_string(get_request_var_request('sort_column'));
	}

	/* clean up sort_direction string */
	if (isset($_REQUEST['sort_direction'])) {
		$_REQUEST['sort_direction'] = sanitize_search_string(get_request_var_request('sort_direction'));
	}

	/* if the user pushed the 'clear' button */
	if (isset($_REQUEST['clear_x'])) {
		kill_session_var('sess_cdef_current_page');
		kill_session_var('sess_cdef_filter');
		kill_session_var('sess_cdef_has_graphs');
		kill_session_var('sess_default_rows');
		kill_session_var('sess_cdef_sort_column');
		kill_session_var('sess_cdef_sort_direction');

		unset($_REQUEST['page']);
		unset($_REQUEST['filter']);
		unset($_REQUEST['has_graphs']);
		unset($_REQUEST['rows']);
		unset($_REQUEST['sort_column']);
		unset($_REQUEST['sort_direction']);

	}

	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value('page', 'sess_cdef_current_page', '1');
	load_current_session_value('filter', 'sess_cdef_filter', '');
	load_current_session_value('has_graphs', 'sess_cdef_has_graphs', 'true');
	load_current_session_value('sort_column', 'sess_cdef_sort_column', 'name');
	load_current_session_value('sort_direction', 'sess_cdef_sort_direction', 'ASC');
	load_current_session_value('rows', 'sess_default_rows', read_config_option('num_rows_table'));

	html_start_box("<strong>CDEF's</strong>", '100%', '', '3', 'center', 'cdef.php?action=edit');

	?>
	<tr class='even'>
		<td>
			<form id='form_cdef' action='cdef.php'>
			<table cellpadding='2' cellspacing='0'>
				<tr>
					<td width='50'>
						Search:
					</td>
					<td width='1'>
						<input id='filter' type='text' name='filter' size='25' value='<?php print htmlspecialchars(get_request_var_request('filter'));?>'>
					</td>
					<td>
						CDEFs:
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
					<td>
						<input type="checkbox" id='has_graphs' <?php print ($_REQUEST['has_graphs'] == 'true' ? 'checked':'');?>>
					</td>
					<td>
						<label for='has_graphs' style='white-space:nowrap;'>Has Graphs</label>
					</td>
					<td>
						<input type='button' id='refresh' value='Go' title='Set/Refresh Filters'>
					</td>
					<td>
						<input type='button' id='clear' name='clear_x' value='Clear' title='Clear Filters'>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' name='page' value='<?php print $_REQUEST['page'];?>'>
			</form>
			<script type='text/javascript'>
			function applyFilter() {
				strURL = 'cdef.php?filter='+$('#filter').val()+'&rows='+$('#rows').val()+'&page='+$('#page').val()+'&has_graphs='+$('#has_graphs').is(':checked')+'&header=false';
				$.get(strURL, function(data) {
					$('#main').html(data);
					applySkin();
				});
			}

			function clearFilter() {
				strURL = 'cdef.php?clear_x=1&header=false';
				$.get(strURL, function(data) {
					$('#main').html(data);
					applySkin();
				});
			}

			$(function() {
				$('#refresh').click(function() {
					applyFilter();
				});

				$('#has_graphs').click(function() {
					applyFilter();
				});

				$('#clear').click(function() {
					clearFilter();
				});

				$('#form_cdef').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});
			});
			</script>
		</td>
	</tr>
	<?php

	html_end_box();

	/* form the 'where' clause for our main sql query */
	if ($_REQUEST['filter'] != '') {
		$sql_where = "WHERE (name LIKE '%" . get_request_var_request('filter') . "%')";
	}else{
		$sql_where = '';
	}

	if ($_REQUEST['has_graphs'] == 'true') {
		$sql_having = 'HAVING graphs>0';
	}else{
		$sql_having = '';
	}

	/* print checkbox form for validation */
	print "<form name='chk' method='post' action='cdef.php'>\n";

	html_start_box('', '100%', '', '3', 'center', '');

	$total_rows = db_fetch_cell("SELECT
		COUNT(rows)
		FROM (
			SELECT cd.id AS rows,
			SUM(CASE WHEN local_graph_id>0 THEN 1 ELSE 0 END) AS graphs
			FROM cdef AS cd
			LEFT JOIN graph_templates_item AS gti
			ON gti.cdef_id=cd.id
			$sql_where
			GROUP BY cd.id
			$sql_having
		) AS rs");

	$cdef_list = db_fetch_assoc("SELECT rs.*,
		SUM(CASE WHEN local_graph_id=0 THEN 1 ELSE 0 END) AS templates,
		SUM(CASE WHEN local_graph_id>0 THEN 1 ELSE 0 END) AS graphs
		FROM (
			SELECT cd.*, gti.local_graph_id
			FROM cdef AS cd
			LEFT JOIN graph_templates_item AS gti
			ON gti.cdef_id=cd.id
			GROUP BY cd.id, gti.graph_template_id, gti.local_graph_id
		) AS rs
		$sql_where
		GROUP BY rs.id
		$sql_having
		ORDER BY " . get_request_var_request('sort_column') . ' ' . get_request_var_request('sort_direction') .
		' LIMIT ' . (get_request_var_request('rows')*(get_request_var_request('page')-1)) . ',' . get_request_var_request('rows'));

	$nav = html_nav_bar('cdef.php?filter=' . get_request_var_request('filter'), MAX_DISPLAY_PAGES, get_request_var_request('page'), get_request_var_request('rows'), $total_rows, 4, 'CDEFs', 'page', 'main');

	print $nav;

	$display_text = array(
		'name' => array('CDEF Title', 'ASC'),
		'graphs' => array('display' => 'Graphs Using', 'align' => 'right', 'sort' => 'DESC'),
		'templates' => array('display' => 'Templates Using', 'align' => 'right', 'sort' => 'DESC'));

	html_header_sort_checkbox($display_text, get_request_var_request('sort_column'), get_request_var_request('sort_direction'), false);

	$i = 0;
	if (sizeof($cdef_list) > 0) {
		foreach ($cdef_list as $cdef) {
			if ($cdef['graphs'] == 0 && $cdef['templates'] == 0) {
				$disabled = false;
			}else{
				$disabled = true;
			}

			form_alternate_row('line' . $cdef['id'], false, $disabled);
			form_selectable_cell("<a class='linkEditMain' href='" . htmlspecialchars('cdef.php?action=edit&id=' . $cdef['id']) . "'>" . (strlen(get_request_var_request('filter')) ? preg_replace('/(' . preg_quote(get_request_var_request('filter'), '/') . ')/i', "<span class='filteredValue'>\\1</span>", htmlspecialchars($cdef['name'])) : htmlspecialchars($cdef['name'])) . '</a>', $cdef['id']);
			form_selectable_cell($cdef['graphs'], $cdef['id'], '', 'text-align:right');
			form_selectable_cell($cdef['templates'], $cdef['id'], '', 'text-align:right');
			form_checkbox_cell($cdef['name'], $cdef['id'], $disabled);
			form_end_row();
		}
		print $nav;
	}else{
		print "<tr class='tableRow'><td colspan='4'><em>No CDEFs</em></td></tr>\n";
	}
	html_end_box(false);

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($cdef_actions);

	print "</form>\n";
}
?>

