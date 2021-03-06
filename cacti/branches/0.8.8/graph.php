<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2015 The Cacti Group                                 |
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

/* set default action */
if (!isset($_REQUEST['action'])) { $_REQUEST['action'] = 'view'; }
if (!isset($_REQUEST['view_type'])) { $_REQUEST['view_type'] = ''; }

$guest_account = true;
include('./include/auth.php');
include_once('./lib/rrd.php');

api_plugin_hook_function('graph');

include_once('./lib/html_tree.php');
top_graph_header();

/* ================= input validation ================= */
input_validate_input_regex(get_request_var_request('rra_id'), '^([0-9]+|all)$');
input_validate_input_number(get_request_var_request('local_graph_id'));
input_validate_input_number(get_request_var_request('graph_end'));
input_validate_input_number(get_request_var_request('graph_start'));
input_validate_input_regex(get_request_var_request('view_type'), '^([a-zA-Z0-9]+)$');
/* ==================================================== */

if (!isset($_REQUEST['rra_id'])) {
	$_REQUEST['rra_id'] = 'all';
}

if ($_REQUEST['rra_id'] == 'all') {
	$sql_where = ' WHERE id IS NOT NULL';
}else{
	$sql_where = ' WHERE id=' . $_REQUEST['rra_id'];
}

/* make sure the graph requested exists (sanity) */
if (!(db_fetch_cell_prepared('SELECT local_graph_id FROM graph_templates_graph WHERE local_graph_id = ?', array(get_request_var_request('local_graph_id'))))) {
	print "<strong><font class='txtErrorTextBox'>GRAPH DOES NOT EXIST</font></strong>"; 
	exit;
}

/* take graph permissions into account here */
if (!is_graph_allowed($_REQUEST['local_graph_id'])) {
	header('Location: permission_denied.php');
	exit;
}

$graph_title = get_graph_title($_REQUEST['local_graph_id']);

if ($_REQUEST['view_type'] == 'tree') {
	print "<table width='100%' style='background-color: #ffffff; border: 1px solid #ffffff;' align='center' cellspacing='0' cellpadding='3'>";
}else{
	print "<table width='100%' style='background-color: #f5f5f5; border: 1px solid #bbbbbb;' align='center' cellspacing='0' cellpadding='3'>";
}

$rras = get_associated_rras($_REQUEST['local_graph_id']);

switch ($_REQUEST['action']) {
case 'view':
	api_plugin_hook_function('page_buttons',
		array('lgid' => $_REQUEST['local_graph_id'],
			'leafid' => '',//$leaf_id,
			'mode' => 'mrtg',
			'rraid' => $_REQUEST['rra_id'])
		);
	?>
	<tr class='tableHeader'>
		<td colspan='3' class='textHeaderDark'>
			<strong>Viewing Graph</strong> '<?php print htmlspecialchars($graph_title);?>'
		<script type="text/javascript" >
		$(function() { 
			$('#navigation').show();
			$('#navigation_right').show();
		});
		</script>
		</td>
	</tr>
	<?php

	$i = 0;
	if (sizeof($rras) > 0) {
		$graph_end   = time();
		foreach ($rras as $rra) {
			$graph_start = $graph_end - db_fetch_cell_prepared('SELECT timespan FROM rra WHERE id = ?', array($rra['id']));
			?>
			<tr>
				<td align='center'>
					<table width='1' cellpadding='0'>
						<tr>
							<td>
								<img class='graphimage' id='graph_<?php print $_REQUEST['local_graph_id'] ?>' src='<?php print htmlspecialchars('graph_image.php?action=view&local_graph_id=' . $_REQUEST['local_graph_id'] . '&rra_id=' . $rra['id']);?>' border='0' alt='<?php print htmlspecialchars($graph_title);?>'>
							</td>
							<td valign='top' style='padding: 3px;' class='noprint'>
								<a href='<?php print htmlspecialchars('graph.php?action=zoom&local_graph_id=' . $_REQUEST['local_graph_id']. '&rra_id=' . $rra['id'] . '&view_type=' . $_REQUEST['view_type'] . '&graph_start=' . $graph_start . '&graph_end=' . $graph_end);?>'><img src='images/graph_zoom.gif' border='0' alt='Zoom Graph' title='Zoom Graph' style='padding: 3px;'></a><br>
								<a href='<?php print htmlspecialchars('graph_xport.php?local_graph_id=' . $_REQUEST['local_graph_id'] . '&rra_id=' . $rra['id'] . '&view_type=' . $_REQUEST['view_type'] .  '&graph_start=' . $graph_start . '&graph_end=' . $graph_end);?>'><img src='images/graph_query.png' border='0' alt='CSV Export' title='CSV Export' style='padding: 3px;'></a><br>
								<a href='<?php print htmlspecialchars('graph.php?action=properties&local_graph_id=' . $_REQUEST['local_graph_id'] . '&rra_id=' . $rra['id'] . '&view_type=' . $_REQUEST['view_type'] .  '&graph_start=' . $graph_start . '&graph_end=' . $graph_end);?>'><img src='images/graph_properties.gif' border='0' alt='Graph Source/Properties' title='Graph Source/Properties' style='padding: 3px;'></a>
								<?php if (read_config_option('realtime_enabled') == 'on') print "<a href='#' onclick=\"window.open('".$config['url_path']."graph_realtime.php?local_graph_id=" . $_REQUEST['local_graph_id'] . "', 'popup_" . $_REQUEST['local_graph_id'] . "', 'toolbar=no,menubar=no,resizable=yes,location=no,scrollbars=no,status=no,titlebar=no,width=650,height=300')\"><img src='".$config['url_path']."images/chart_curve_go.png' border='0' alt='Realtime' title='Realtime' style='padding: 3px;'></a><br/>\n";?>
								<?php api_plugin_hook('graph_buttons', array('hook' => 'view', 'local_graph_id' => $_REQUEST['local_graph_id'], 'rra' => $rra['id'], 'view_type' => $_REQUEST['view_type'])); ?>
								<a href='#page_top'><img src='<?php print $config['url_path']; ?>images/graph_page_top.gif' border='0' alt='Page Top' title='Page Top' style='padding: 3px;'></a><br>
							</td>
						</tr>
						<tr>
							<td colspan='2' align='center'>
								<strong><?php print htmlspecialchars($rra['name']);?></strong>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<?php
			$i++;
		}
		api_plugin_hook_function('tree_view_page_end');
	}

	break;

case 'zoom':
	/* find the maximum time span a graph can show */
	$max_timespan=1;
	if (sizeof($rras) > 0) {
		foreach ($rras as $rra) {
			if ($rra['steps'] * $rra['rows'] * $rra['rrd_step'] > $max_timespan) {
				$max_timespan = $rra['steps'] * $rra['rows'] * $rra['rrd_step'];
			}
		}
	}

	/* fetch information for the current RRA */
	if (isset($_REQUEST['rra_id']) && $_REQUEST['rra_id'] > 0) {
		$rra = db_fetch_row_prepared('SELECT id, timespan, steps, name FROM rra WHERE id = ?', array($_REQUEST['rra_id']));
	}else{
		$rra = db_fetch_row_prepared('SELECT id, timespan, steps, name FROM rra WHERE id = ?', array($rras[0]['id']));
	}

	/* define the time span, which decides which rra to use */
	$timespan = -($rra['timespan']);

	/* find the step and how often this graph is updated with new data */
	$ds_step = db_fetch_cell_prepared('SELECT
		data_template_data.rrd_step
		FROM (data_template_data, data_template_rrd, graph_templates_item)
		WHERE graph_templates_item.task_item_id = data_template_rrd.id
		AND data_template_rrd.local_data_id = data_template_data.local_data_id
		AND graph_templates_item.local_graph_id = ?
		LIMIT 0,1', array(get_request_var_request('local_graph_id')));
	$ds_step = empty($ds_step) ? 300 : $ds_step;
	$seconds_between_graph_updates = ($ds_step * $rra['steps']);

	$now = time();

	if (isset($_REQUEST['graph_end']) && ($_REQUEST['graph_end'] <= $now - $seconds_between_graph_updates)) {
		$graph_end = $_REQUEST['graph_end'];
	}else{
		$graph_end = $now - $seconds_between_graph_updates;
	}

	if (isset($_REQUEST['graph_start'])) {
		if (($graph_end - $_REQUEST['graph_start'])>$max_timespan) {
			$graph_start = $now - $max_timespan;
		}else {
			$graph_start = $_REQUEST['graph_start'];
		}
	}else{
		$graph_start = $now + $timespan;
	}

	/* required for zoom out function */
	if ($graph_start == $graph_end) {
		$graph_start--;
	}

	$graph = db_fetch_row_prepared('SELECT graph_templates_graph.height, graph_templates_graph.width
		FROM graph_templates_graph
		WHERE graph_templates_graph.local_graph_id = ?', array(get_request_var_request('local_graph_id')));

	$graph_height = $graph['height'];
	$graph_width  = $graph['width'];

	if (read_graph_config_option('custom_fonts') == 'on' & read_graph_config_option('title_size') != '') {
		$title_font_size = read_graph_config_option('title_size');
	}elseif (read_config_option('title_size') != '') {
		$title_font_size = read_config_option('title_size');
	}else {
	 	$title_font_size = 10;
	}

	?>
	<tr class='tableHeader'>
		<td colspan='3' class='textHeaderDark'>
			<strong>Zooming Graph</strong> '<?php print htmlspecialchars($graph_title);?>'
		</td>
	</tr>
	<tr>
		<td align='center'>
			<table width='1' cellpadding='0'>
				<tr>
					<td>
						<img id='zoomGraphImage' class="graphimage" src='<?php print htmlspecialchars('graph_image.php?action=zoom&local_graph_id=' . $_REQUEST['local_graph_id'] . '&rra_id=' . $_REQUEST['rra_id'] . '&view_type=' . $_REQUEST['view_type'] . '&graph_start=' . $graph_start . '&graph_end=' . $graph_end . '&graph_height=' . $graph_height . '&graph_width=' . $graph_width . '&title_font_size=' . $title_font_size);?>' border='0' alt='<?php print htmlspecialchars($graph_title);?>'>
					</td>
					<td valign='top' style='padding: 3px;' class='noprint'>
						<a href='<?php print htmlspecialchars('graph.php?action=properties&local_graph_id=' . $_REQUEST['local_graph_id'] . '&rra_id=' . $_REQUEST['rra_id'] . '&view_type=' . $_REQUEST['view_type'] . '&graph_start=' . $graph_start . '&graph_end=' . $graph_end);?>'><img src='images/graph_properties.gif' border='0' alt='Graph Source/Properties' title='Graph Source/Properties' style='padding: 3px;'></a>
						<a href='<?php print htmlspecialchars('graph_xport.php?local_graph_id=' . $_REQUEST['local_graph_id'] . '&rra_id=' . $_REQUEST['rra_id'] . '&view_type=' . $_REQUEST['view_type']);?>&graph_start=<?php print $graph_start;?>&graph_end=<?php print $graph_end;?>'><img src='images/graph_query.png' border='0' alt='CSV Export' title='CSV Export' style='padding: 3px;'></a><br>
						<?php api_plugin_hook('graph_buttons', array('hook' => 'zoom', 'local_graph_id' => $_REQUEST['local_graph_id'], 'rra' =>  $_REQUEST['rra_id'], 'view_type' => $_REQUEST['view_type'])); ?>
					</td>
				</tr>
				<tr>
					<td colspan='2' align='center'>
						<strong>Zooming</strong>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<script type="text/javascript" >
	$(function() { 
		$(".graphimage").zoom({serverTimeOffset:<?php print date('Z');?>}); 
		$('#navigation').show();
		$('#navigation_right').show();
	});
	</script>
	<?php

	break;

case 'properties':
	?>
	<tr class='tableHeader'>
		<td colspan='3' class='textHeaderDark'>
			<strong>Viewing Graph Properties </strong> '<?php print htmlspecialchars($graph_title);?>'
		</td>
	</tr>
	<tr>
		<td align='center'>
			<table width='1' cellpadding='0'>
				<tr>
					<td>
						<img src='<?php print htmlspecialchars('graph_image.php?action=properties&local_graph_id=' . $_REQUEST['local_graph_id'] . '&rra_id=' . $_REQUEST['rra_id'] . '&graph_start=' . (isset($_REQUEST['graph_start']) ? $_REQUEST['graph_start'] : '0') . '&graph_end=' . (isset($_REQUEST['graph_end']) ? $_REQUEST['graph_end'] : '0'));?>' border='0' alt='<?php print htmlspecialchars($graph_title);?>'>
					</td>
					<td valign='top' style='padding: 3px;'>
						<a href='<?php print htmlspecialchars('graph.php?action=zoom&local_graph_id=' . $_REQUEST['local_graph_id']. '&rra_id=' . $_REQUEST['rra_id'] . '&view_type=' . $_REQUEST['view_type'] . '&graph_start=' . get_request_var_request('graph_start') . '&graph_end=' . get_request_var_request('graph_end'));?>'><img src='images/graph_zoom.gif' border='0' alt='Zoom Graph' title='Zoom Graph' style='padding: 3px;'></a><br>
						<a href='<?php print htmlspecialchars('graph_xport.php?local_graph_id=' . $_REQUEST['local_graph_id'] . '&rra_id=' . $_REQUEST['rra_id'] . '&view_type=' . $_REQUEST['view_type']);?>'><img src='images/graph_query.png' border='0' alt='CSV Export' title='CSV Export' style='padding: 3px;'></a><br>
						<?php api_plugin_hook('graph_buttons', array('hook' => 'properties', 'local_graph_id' => $_REQUEST['local_graph_id'], 'rra' =>  $_REQUEST['rra_id'], 'view_type' => $_REQUEST['view_type'])); ?>
					</td>
				</tr>
				<tr>
					<td colspan='2' align='center'>
						<strong><?php print htmlspecialchars(db_fetch_cell_prepared('SELECT name FROM rra WHERE id = ?', array($_REQUEST['rra_id'])));?></strong>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<script type="text/javascript" >
	$(function() { 
		$('#navigation').show();
		$('#navigation_right').show();
	});
	</script>
	<?php

	break;
}

print '</table>';
print '<br><br>';

bottom_footer();

