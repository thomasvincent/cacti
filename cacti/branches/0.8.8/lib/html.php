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

/* html_start_box - draws the start of an HTML box with an optional title
   @arg $title - the title of this box ("" for no title)
   @arg $width - the width of the box in pixels or percent
   @arg $background_color - deprecated
   @arg $cell_padding - the amount of cell padding to use inside of the box
   @arg $align - the HTML alignment to use for the box (center, left, or right)
   @arg $add_text - the url to use when the user clicks 'Add' in the upper-right
     corner of the box ("" for no 'Add' link) */
function html_start_box($title, $width, $background_color, $cell_padding, $align, $add_text, $add_label = 'Add') {
	static $table_suffix = 1;

	$table_prefix = basename($_SERVER['PHP_SELF'], '.php');;
	if (isset($_REQUEST['report']) && $_REQUEST['report'] != '') {
		$table_prefix .= '_' . $_REQUEST['report'];
	} elseif (isset($_REQUEST['tab']) && $_REQUEST['tab'] != '') {
		$table_prefix .= '_' . $_REQUEST['tab'];
	} elseif (isset($_REQUEST['action']) && $_REQUEST['action'] != '') {
		$table_prefix .= '_' . $_REQUEST['action'];
	}
	$table_id = $table_prefix . $table_suffix;

	?>
	<table id='<?php print $table_id;?>' align="<?php print $align;?>" width="<?php print $width;?>" cellpadding=0 cellspacing=0 border=0 class="cactiTable">
		<tr>
			<td>
				<?php if ($title != "") {?>
				<table width='100%' class='cactiTableTitle' cellpadding='<?php print $cell_padding;?>' cellspacing='0' border='0'>
					<tr>
						<td class='textHeaderDark'><?php print $title;?></td>
						<?php if ($add_text!= "") {?>
						<td class='textHeaderDark' align='right'><a class="linkOverDark" href="<?php print htmlspecialchars($add_text);?>"><?php print $add_label;?></a>&nbsp;</td><?php }?>
					</tr>
				</table>
				<?php }?>
				<table class='cactiTable' cellpadding='<?php print $cell_padding;?>' cellspacing='0' border='0' width="100%">
	<?php

	$table_suffix++;
}

/* html_end_box - draws the end of an HTML box
   @arg $trailing_br (bool) - whether to draw a trailing <br> tag after ending
     the box */
function html_end_box($trailing_br = true) { ?>
				</table>
			</td>
		</tr>
	</table>
	<?php if ($trailing_br == true) { print "<div class='break'></div>"; } ?>
<?php }

/* html_graph_start_box - draws the start of an HTML graph view box
   @arg $cellpadding - the table cell padding for the box
   @arg $leading_br (bool) - whether to draw a leader <br> tag before the start of the table */
function html_graph_start_box($cellpadding = 3, $leading_br = true) {
	if ($leading_br == true) {
		print "<br>\n";
	}

	print "<table width='100%' style='cactiTable' align='center' cellpadding='$cellpadding'>\n";
}

/* html_graph_end_box - draws the end of an HTML graph view box */
function html_graph_end_box() {
	print "</table>";
}

/* html_graph_area - draws an area the contains full sized graphs
   @arg $graph_array - the array to contains graph information. for each graph in the
     array, the following two keys must exist
     $arr[0]["local_graph_id"] // graph id
     $arr[0]["title_cache"] // graph title
   @arg $no_graphs_message - display this message if no graphs are found in $graph_array
   @arg $extra_url_args - extra arguments to append to the url
   @arg $header - html to use as a header
   @arg $columns - the number of columns to present */
function html_graph_area(&$graph_array, $no_graphs_message = "", $extra_url_args = "", $header = "", $columns = 0) {
	global $config;
	$i = 0; $k = 0; $j = 0;

	$num_graphs = sizeof($graph_array);

	if ($columns == 0) {
		$columns = read_graph_config_option('num_columns');
	}

	if ($num_graphs > 0) {
		if ($header != "") {
			print $header;
		}

		$start = true;
		foreach ($graph_array as $graph) {
			if (isset($graph["graph_template_name"])) {
				if (isset($prev_graph_template_name)) {
					if ($prev_graph_template_name != $graph["graph_template_name"]) {
						$print  = true;
						$prev_graph_template_name = $graph["graph_template_name"];
					}else{
						$print = false;
					}
				}else{
					$print  = true;
					$prev_graph_template_name = $graph["graph_template_name"];
				}

				if ($print) {
					print "<tr class='templateHeader'>
						<td colspan='3' class='textHeaderDark'>
							<strong>Graph Template:</strong> " . htmlspecialchars($graph["graph_template_name"]) . "
						</td>
					</tr>";
				}
			}elseif (isset($graph["data_query_name"])) {
				if (isset($prev_data_query_name)) {
					if ($prev_data_query_name != $graph["data_query_name"]) {
						$print  = true;
						$prev_data_query_name = $graph["data_query_name"];
					}else{
						$print = false;
					}
				}else{
					$print  = true;
					$prev_data_query_name = $graph["data_query_name"];
				}

				if ($print) {
					if (!$start) {
						while(($i % $columns) != 0) {
							print "<td align='center' width='" . ceil(100 / $columns) . "%'></td>";
							$i++;
						}

						print "</tr>";
					}

					print "<tr class='tableHeader'>
							<td colspan='$columns' class='graphSubHeaderColumn textHeaderDark'><strong>Data Query:</strong> " . $graph["data_query_name"] . "</td>
						</tr>";
					$i = 0;
				}

				if (!isset($prev_sort_field_value) || $prev_sort_field_value != $graph["sort_field_value"]){
					$prev_sort_field_value = $graph["sort_field_value"];
					print "<tr class='templateHeader'>
						<td colspan='$columns' class='textHeaderDark'>
							" . $graph["sort_field_value"] . "
						</td>
					</tr>";
					$i = 0;
					$j = 0;
				}
			}

			if ($i == 0) {
				form_alternate_row();
				$start = false;
			}

			?>
			<td align='center' width='<?php print ceil(100 / $columns);?>%'>
				<table align='center' cellpadding='0'>
					<tr>
						<td align='center'>
							<div style="min-height: <?php echo (1.6 * $graph["height"]) . "px"?>;"><img class='graphimage' id='graph_<?php print $graph["local_graph_id"] ?>' src='<?php print htmlspecialchars($config['url_path'] . "graph_image.php?local_graph_id=" . $graph["local_graph_id"] . "&rra_id=0&graph_height=" . $graph["height"] . "&graph_width=" . $graph["width"] . "&title_font_size=" . ((read_graph_config_option("custom_fonts") == "on") ? read_graph_config_option("title_size") : read_config_option("title_size")) . (($extra_url_args == "") ? "" : "&$extra_url_args"));?>' border='0' alt='<?php print htmlspecialchars($graph["title_cache"]);?>'></div>
							<?php print (read_graph_config_option("show_graph_title") == "on" ? "<span align='center'><strong>" . htmlspecialchars($graph["title_cache"]) . "</strong></span>" : "");?>
						</td>
						<td valign='top' style='align: left; padding: 3px;' class='noprint'>
							<?php graph_drilldown_icons($graph['local_graph_id']);?>
						</td>
					</tr>
				</table>
			</td>
			<?php

			$i++;
			$k++;

			if (($i % $columns) == 0 && ($k < $num_graphs)) {
				$i=0;
				$j++;
				print "</tr>";
				$start = true;
			}
		}

		if (!$start) {
			while(($i % $columns) != 0) {
				print "<td align='center' width='" . ceil(100 / $columns) . "%'></td>";
				$i++;
			}

			print "</tr>";
		}
	}else{
		if ($no_graphs_message != "") {
			print "<td><em>$no_graphs_message</em></td>";
		}
	}
}

/* html_graph_thumbnail_area - draws an area the contains thumbnail sized graphs
   @arg $graph_array - the array to contains graph information. for each graph in the
     array, the following two keys must exist
     $arr[0]["local_graph_id"] // graph id
     $arr[0]["title_cache"] // graph title
   @arg $no_graphs_message - display this message if no graphs are found in $graph_array
   @arg $extra_url_args - extra arguments to append to the url
   @arg $header - html to use as a header
   @arg $columns - the number of columns to present */
function html_graph_thumbnail_area(&$graph_array, $no_graphs_message = "", $extra_url_args = "", $header = "", $columns = 0) {
	global $config;
	$i = 0; $k = 0; $j = 0;

	$num_graphs = sizeof($graph_array);

	if ($columns == 0) {
		$columns = read_graph_config_option('num_columns');
	}

	if ($num_graphs > 0) {
		if ($header != "") {
			print $header;
		}

		$start = true;
		foreach ($graph_array as $graph) {
			if (isset($graph["graph_template_name"])) {
				if (isset($prev_graph_template_name)) {
					if ($prev_graph_template_name != $graph["graph_template_name"]) {
						$prev_graph_template_name = $graph["graph_template_name"];
					}
				}else{
					$prev_graph_template_name = $graph["graph_template_name"];
				}
			}elseif (isset($graph["data_query_name"])) {
				if (isset($prev_data_query_name)) {
					if ($prev_data_query_name != $graph["data_query_name"]) {
						$print  = true;
						$prev_data_query_name = $graph["data_query_name"];
					}else{
						$print = false;
					}
				}else{
					$print  = true;
					$prev_data_query_name = $graph["data_query_name"];
				}

				if ($print) {
					if (!$start) {
						while(($i % $columns) != 0) {
							print "<td align='center' width='" . ceil(100 / $columns) . "%'></td>";
							$i++;
						}

						print "</tr>";
					}

					print "<tr class='tableHeader'>
							<td class='graphSubHeaderColumn textHeaderDark' colspan='$columns'><strong>Data Query:</strong> " . $graph["data_query_name"] . "</td>
						</tr>";
					$i = 0;
				}
			}

			if ($i == 0) {
				form_alternate_row();
				$start = false;
			}

			?>
			<td align='center' width='<?php print ceil(100 / $columns);?>%'>
				<table align='center' cellpadding='0'>
					<tr>
						<td align='center'>
							<div style="min-height: <?php echo (1.6 * read_graph_config_option("default_height")) . "px"?>;"><img class='graphimage' id='graph_<?php print $graph["local_graph_id"] ?>' src='<?php print htmlspecialchars($config['url_path'] . "graph_image.php?local_graph_id=" . $graph["local_graph_id"] . "&rra_id=0&graph_height=" . read_graph_config_option("default_height") . "&graph_width=" . read_graph_config_option("default_width") . "&graph_nolegend=true&title_font_size=" . ((read_graph_config_option("custom_fonts") == "on") ? read_graph_config_option("title_size") : read_config_option("title_size")) . (($extra_url_args == "") ? "" : "&$extra_url_args"));?>' border='0' alt='<?php print htmlspecialchars($graph["title_cache"]);?>'></div>
							<?php print (read_graph_config_option("show_graph_title") == "on" ? "<span align='center'><strong>" . htmlspecialchars($graph["title_cache"]) . "</strong></span>" : "");?>
						</td>
						<td valign='top' align='center' style='align: center'>
							<?php print graph_drilldown_icons($graph['local_graph_id'], 'graph_buttons_thumbnails');?>
						</td>
					</tr>
				</table>
			</td>
			<?php

			$i++;
			$k++;

			if (($i % $columns) == 0 && ($k < $num_graphs)) {
				$i=0;
				$j++;
				print "</tr>";
				$start = true;
			}
		}

		if (!$start) {
			while(($i % $columns) != 0) {
				print "<td align='center' width='" . ceil(100 / $columns) . "%'></td>";
				$i++;
			}

			print "</tr>";
		}
	}else{
		if ($no_graphs_message != "") {
			print "<td><em>$no_graphs_message</em></td>";
		}
	}
}

function graph_drilldown_icons($local_graph_id, $type = 'graph_buttons') {
	global $config;

	print "<span class='hyperLink zooming' id='graph_" . $local_graph_id . "_util'><img class='drillDown' src='" . $config['url_path'] . "images/cog.png' border='0' alt='' title='Graph Details, Zooming and Debugging Utilities'></span><br>\n";
	print "<span class='hyperLink csvexport' id='graph_" . $local_graph_id . "_csv'><img class='drillDown' src='" . $config['url_path'] . "images/table_go.png' border='0' alt='' title='CSV Export of Graph Data'></span><br>\n";
	print "<span class='hyperLink csvexport' id='graph_" . $local_graph_id . "_mrtg'><img class='drillDown' src='" . $config['url_path'] . "images/mrtg.png' border='0' alt='' title='MRTG Graph View'></span><br>\n";
	if (read_config_option('realtime_enabled') == 'on') {
		print "<span class='hyperLink realtime' id='graph_" . $local_graph_id . "_realtime'><img class='drillDown' src='" . $config['url_path'] . "images/chart_curve_go.png' border='0' alt='' title='Click to view just this Graph in Realtime'></span><br/>\n";
	}
	api_plugin_hook($type, array('hook' => 'graphs_thumbnails', 'local_graph_id' => $local_graph_id, 'rra' =>  0, 'view_type' => ''));
}

/* html_nav_bar - draws a navigation bar which includes previous/next links as well as current
     page information
   @arg $base_url - the base URL will all filter options except page#
   @arg $max_pages - the maximum number of pages to display
   @arg $current_page - the current page in the navigation system
   @arg $rows_per_page - the number of rows that are displayed on a single page
   @arg $total_rows - the total number of rows in the navigation system
   @arg $object - the object types that is being displayed
   @arg $page_var - the object types that is being displayed
   @arg $return_to - paint the resulting page into this dom object */
function html_nav_bar($base_url, $max_pages, $current_page, $rows_per_page, $total_rows, $colspan=30, $object = "Rows", $page_var = "page", $return_to = "") {
	if ($total_rows > $rows_per_page) {
		if (substr_count($base_url, '?') == 0) {
			$base_url = trim($base_url) . '?';
		}else{
			$base_url = trim($base_url) . '&';
		}

		$url_page_select = get_page_list($current_page, $max_pages, $rows_per_page, $total_rows, $base_url, $page_var, $return_to);

		$nav = "<tr class='cactiNavBarTop'>
			<td colspan='$colspan'>
				<table width='100%' cellspacing='0' cellpadding='0' border='0'>
					<tr>
						<td style='width:10%;' align='left' class='textHeaderDark'><div style='display:block;'>
							" . (($current_page > 1) ? "<div class='navBarNavigation navBarNavigationPrevious' onClick='gotoPage(" . ($current_page-1) . ")'><i class='fa fa-angle-double-left previous'></i>Previous</div>":"") . "
						</div></td>
						<td style='width:80%;' align='center' class='textHeaderDark'>
							Showing $object " . (($rows_per_page*($current_page-1))+1) . " to " . (($total_rows < $rows_per_page) || ($total_rows < ($rows_per_page*$current_page)) ? $total_rows : $rows_per_page*$current_page) . " of $total_rows [$url_page_select]
						</td>
						<td style='width:10%;' align='right' class='textHeaderDark'><div style='display:block;'>
							" . (($current_page*$rows_per_page) < $total_rows ? "<div class='navBarNavigation navBarNavigationNext' onClick='gotoPage(" . ($current_page+1) . ")'>Next<i class='fa fa-angle-double-right next'></i></div>":"") . "
						</div></td>
					</tr>
				</table>
			</td>
			</tr>\n";
	}elseif ($total_rows > 0) {
		$nav = "<tr class='cactiNavBarTop'>
			<td colspan='$colspan'>
				<table width='100%' cellspacing='0' cellpadding='0' border='0'>
					<tr>
						<td align='center' class='textHeaderDark'>
							Showing All $object
						</td>
					</tr>
				</table>
			</td>
			</tr>\n";

	}else{
		$nav = "<tr class='cactiNavBarTop'>
			<td colspan='$colspan'>
				<table width='100%' cellspacing='0' cellpadding='0' border='0'>
					<tr>
						<td align='center' class='textHeaderDark'>
							No $object Found
						</td>
					</tr>
				</table>
			</td>
			</tr>\n";
	}

	return $nav;
}

/* html_header_sort - draws a header row suitable for display inside of a box element.  When
     a user selects a column header, the collback function "filename" will be called to handle
     the sort the column and display the altered results.
   @arg $header_items - an array containing a list of column items to display.  The
        format is similar to the html_header, with the exception that it has three
        dimensions associated with each element (db_column => display_text, default_sort_order)
		alternatively (db_column => array('display' = 'blah', 'align' = 'blah', 'sort' = 'blah'))
   @arg $sort_column - the value of current sort column.
   @arg $sort_direction - the value the current sort direction.  The actual sort direction
        will be opposite this direction if the user selects the same named column.
   @arg $last_item_colspan - the TD 'colspan' to apply to the last cell in the row */
function html_header_sort($header_items, $sort_column, $sort_direction, $last_item_colspan = 1) {
	/* reverse the sort direction */
	if ($sort_direction == "ASC") {
		$new_sort_direction = "DESC";
	}else{
		$new_sort_direction = "ASC";
	}

	print "<tr class='tableHeader'>\n";

	$i = 1;
	foreach ($header_items as $db_column => $display_array) {
		if (array_key_exists('display', $display_array)) {
			$display_text = $display_array['display'];
			if ($sort_column == $db_column) {
				$icon = $sort_direction;
				$direction = $new_sort_direction;
			}else{
				$icon = '';
				if (isset($display_array['sort'])) {
					$direction = $display_array['sort'];
				}else{
					$direction = 'ASC';
				}
			}

			if (isset($display_array['align'])) {
				$align = $display_array['align'];
			}else{
				$align = 'left';
			}

			if (isset($display_array['tip'])) {
				$tip = $display_array['tip'];
			}else{
				$tip = '';
			}
		}else{
			/* by default, you will always sort ascending, with the exception of an already sorted column */
			if ($sort_column == $db_column) {
				$icon = $sort_direction;
				$direction = $new_sort_direction;
				$display_text = $display_array[0];
			}else{
				$icon = '';
				$display_text = $display_array[0];
				$direction = $display_array[1];
			}

			$align = 'left';
			$tip   = '';
		}

		if (strtolower($icon) == 'asc') {
			$icon = 'fa fa-sort-asc';
		}elseif (strtolower($icon) == 'desc') {
			$icon = 'fa fa-sort-desc';
		}else{
			$icon = 'fa fa-unsorted';
		}

		if (($db_column == "") || (substr_count($db_column, "nosort"))) {
			print "<th " . ($tip != '' ? "title='" . htmlspecialchars($tip) . "'":"") . " style='text-align:$align;' " . ((($i+1) == count($header_items)) ? "colspan='$last_item_colspan' " : "") . ">" . $display_text . "</th>\n";
		}else{
			print "<th " . ($tip != '' ? "title='" . htmlspecialchars($tip) . "'":"") . " class='sortable' style='text-align:$align;'>";
			print "<div class='sortinfo' sort-page='" . htmlspecialchars($_SERVER['PHP_SELF']) . "' sort-column='$db_column' sort-direction='$direction'><div class='textSubHeaderDark'>" . $display_text . "<i class='$icon'></i></div></div></th>\n";
		}

		$i++;
	}

	print "</tr>\n";
}

/* html_header_sort_checkbox - draws a header row with a 'select all' checkbox in the last cell
     suitable for display inside of a box element.  When a user selects a column header,
     the collback function "filename" will be called to handle the sort the column and display
     the altered results.
   @arg $header_items - an array containing a list of column items to display.  The
        format is similar to the html_header, with the exception that it has three
        dimensions associated with each element (db_column => display_text, default_sort_order)
		alternatively (db_column => array('display' = 'blah', 'align' = 'blah', 'sort' = 'blah'))
   @arg $sort_column - the value of current sort column.
   @arg $sort_direction - the value the current sort direction.  The actual sort direction
        will be opposite this direction if the user selects the same named column.
   @arg $form_action - the url to post the 'select all' form to */
function html_header_sort_checkbox($header_items, $sort_column, $sort_direction, $include_form = true, $form_action = "") {
	static $page = 0;

	/* reverse the sort direction */
	if ($sort_direction == "ASC") {
		$new_sort_direction = "DESC";
	}else{
		$new_sort_direction = "ASC";
	}

	/* default to the 'current' file */
	if ($form_action == "") { $form_action = basename($_SERVER["PHP_SELF"]); }

	print "<tr class='tableHeader'>\n";

	foreach($header_items as $db_column => $display_array) {
		$icon = '';
		if (array_key_exists('display', $display_array)) {
			$display_text = $display_array['display'];
			if ($sort_column == $db_column) {
				$icon = $sort_direction;
				$direction = $new_sort_direction;
			}else{
				$icon = '';

				if (isset($display_array['sort'])) {
					$direction = $display_array['sort'];
				}else{
					$direction = 'ASC';
				}
			}

			if (isset($display_array['align'])) {
				$align = $display_array['align'];
			}else{
				$align = 'left';
			}

			if (isset($display_array['tip'])) {
				$tip = $display_array['tip'];
			}else{
				$tip = '';
			}
		}else{
			/* by default, you will always sort ascending, with the exception of an already sorted column */
			if ($sort_column == $db_column) {
				$icon = $sort_direction;
				$direction = $new_sort_direction;
				$display_text = $display_array[0];
			}else{
				$icon = '';
				$display_text = $display_array[0];
				$direction = $display_array[1];
			}

			$align = 'left';
			$tip   = '';
		}

		if (strtolower($icon) == 'asc') {
			$icon = 'fa fa-sort-asc';
		}elseif (strtolower($icon) == 'desc') {
			$icon = 'fa fa-sort-desc';
		}else{
			$icon = 'fa fa-unsorted';
		}

		if (($db_column == "") || (substr_count($db_column, "nosort"))) {
			print "<th " . ($tip != '' ? "title='" . htmlspecialchars($tip) . "'":"") . " style='text-align:$align;'>" . $display_text . "</th>\n";
		}else{
			print "<th " . ($tip != '' ? "title='" . htmlspecialchars($tip) . "'":"") . " class='sortable' style='text-align:$align;'>";
			print "<div class='sortinfo' sort-page='" . htmlspecialchars($_SERVER['PHP_SELF']) . "' sort-column='$db_column' sort-direction='$direction'><div class='textSubHeaderDark'>" . $display_text . "<i class='$icon'></i></div></div></th>\n";
		}
	}

	print "<th width='1%' class='tableSubHeaderCheckbox' align='right' style='" . get_checkbox_style() . "'><input type='checkbox' style='margin: 0px;' name='all' title='Select All Rows' onClick='SelectAll(\"chk_\",this.checked)'></th>" . ($include_form ? "<th style='display:none;'><form name='chk' method='post' action='$form_action'></th>\n":"");
	print "</tr>\n";

	$page++;
}

/* html_header - draws a header row suitable for display inside of a box element
   @arg $header_items - an array containing a list of items to be included in the header
		alternatively and array of header names and alignment array('display' = 'blah', 'align' = 'blah')
   @arg $last_item_colspan - the TD 'colspan' to apply to the last cell in the row */
function html_header($header_items, $last_item_colspan = 1) {
	print "<tr class='tableHeader'>\n";

	for ($i=0; $i<count($header_items); $i++) {
		if (is_array($header_items[$i])) {
			print "<th style='text-align:" . $header_items[$i]['align'] . ";'" . ((($i+1) == count($header_items)) ? "colspan='$last_item_colspan' " : "") . ">" . $header_items[$i] . "</th>\n";
		}else{
			print "<th style='text-align:left;'" . ((($i+1) == count($header_items)) ? "colspan='$last_item_colspan' " : "") . ">" . $header_items[$i] . "</th>\n";
		}
	}

	print "</tr>\n";
}

/* html_header_checkbox - draws a header row with a 'select all' checkbox in the last cell
     suitable for display inside of a box element
   @arg $header_items - an array containing a list of items to be included in the header
		alternatively and array of header names and alignment array('display' = 'blah', 'align' = 'blah')
   @arg $form_action - the url to post the 'select all' form to */
function html_header_checkbox($header_items, $include_form = true, $form_action = "") {
	/* default to the 'current' file */
	if ($form_action == "") { $form_action = basename($_SERVER["PHP_SELF"]); }

	print "<tr class='tableHeader'>\n";

	for ($i=0; $i<count($header_items); $i++) {
		if (is_array($header_items[$i])) {
			print "<th style='text-align:" . $header_items[$i]['align'] . ";'>" . $header_items[$i]['display'] . "</td>";
		}else{
			print "<th style='text-align:left;'>" . $header_items[$i] . "</th>\n";
		}
	}

	print "<th width='1%' class='tableSubHeaderCheckbox' align='right' style='" . get_checkbox_style() . "'><input type='checkbox' style='margin: 0px;' name='all' title='Select All Rows' onClick='SelectAll(\"chk_\",this.checked)'></th>\n" . ($include_form ? "<th style='display:none;'><form name='chk' method='post' action='$form_action'></th>\n":"");
	print "</tr>\n";
}

/* html_create_list - draws the items for an html dropdown given an array of data
   @arg $form_data - an array containing data for this dropdown. it can be formatted
     in one of two ways:
     $array["id"] = "value";
     -- or --
     $array[0]["id"] = 43;
     $array[0]["name"] = "Red";
   @arg $column_display - used to indentify the key to be used for display data. this
     is only applicable if the array is formatted using the second method above
   @arg $column_id - used to indentify the key to be used for id data. this
     is only applicable if the array is formatted using the second method above
   @arg $form_previous_value - the current value of this form element */
function html_create_list($form_data, $column_display, $column_id, $form_previous_value) {
	if (empty($column_display)) {
		foreach (array_keys($form_data) as $id) {
			print '<option value="' . htmlspecialchars($id) . '"';

			if ($form_previous_value == $id) {
			print " selected";
			}

			print ">" . title_trim(null_out_substitutions(htmlspecialchars($form_data[$id])), 75) . "</option>\n";
		}
	}else{
		if (sizeof($form_data) > 0) {
			foreach ($form_data as $row) {
				print "<option value='" . htmlspecialchars($row[$column_id]) . "'";

				if ($form_previous_value == $row[$column_id]) {
					print " selected";
				}

				if (isset($row["host_id"])) {
					print ">" . title_trim(htmlspecialchars($row[$column_display]), 75) . "</option>\n";
				}else{
					print ">" . title_trim(null_out_substitutions(htmlspecialchars($row[$column_display])), 75) . "</option>\n";
				}
			}
		}
	}
}

/* html_split_string - takes a string and breaks it into a number of <br> separated segments
   @arg $string - string to be modified and returned
   @arg $length - the maximal string length to split to
   @arg $forgiveness - the maximum number of characters to walk back from to determine
         the correct break location.
   @returns $new_string - the modified string to be returned. */
function html_split_string($string, $length = 70, $forgiveness = 10) {
	$new_string = "";
	$j    = 0;
	$done = false;

	while (!$done) {
		if (strlen($string) > $length) {
			for($i = 0; $i < $forgiveness; $i++) {
				if (substr($string, $length-$i, 1) == " ") {
					$new_string .= substr($string, 0, $length-$i) . "<br>";

					break;
				}
			}

			$string = substr($string, $length-$i);
		}else{
			$new_string .= $string;
			$done        = true;
		}

		$j++;
		if ($j > 4) break;
	}

	return $new_string;
}

/* draw_graph_items_list - draws a nicely formatted list of graph items for display
     on an edit form
   @arg $item_list - an array representing the list of graph items. this array should
     come directly from the output of db_fetch_assoc()
   @arg $filename - the filename to use when referencing any external url
   @arg $url_data - any extra GET url information to pass on when referencing any
     external url
   @arg $disable_controls - whether to hide all edit/delete functionality on this form */
function draw_graph_items_list($item_list, $filename, $url_data, $disable_controls) {
	global $colors, $config;

	include($config["include_path"] . "/global_arrays.php");

	print "<tr class='tableHeader'>";
		DrawMatrixHeaderItem("Graph Item","",1);
		DrawMatrixHeaderItem("Data Source","",1);
		DrawMatrixHeaderItem("Graph Item Type","",1);
		DrawMatrixHeaderItem("CF Type","",1);
		DrawMatrixHeaderItem("Item Color","",4);
	print "</tr>";

	$group_counter = 0; $_graph_type_name = ""; $i = 0;
	$alternate_color_1 = $colors["alternate"]; $alternate_color_2 = $colors["alternate"];

	if (sizeof($item_list) > 0) {
	foreach ($item_list as $item) {
		/* graph grouping display logic */
		$this_row_style = ""; $use_custom_row_color = false; $hard_return = "";

		if ($graph_item_types{$item["graph_type_id"]} != "GPRINT") {
			$this_row_style = "font-weight: bold;"; $use_custom_row_color = true;

			if ($group_counter % 2 == 0) {
				$alternate_color_1 = "EEEEEE";
				$alternate_color_2 = "EEEEEE";
				$custom_row_color = "D5D5D5";
			}else{
				$alternate_color_1 = $colors["alternate"];
				$alternate_color_2 = $colors["alternate"];
				$custom_row_color = "D2D6E7";
			}

			$group_counter++;
		}

		$_graph_type_name = $graph_item_types{$item["graph_type_id"]};

		/* alternating row color */
		if ($use_custom_row_color == false) {
			form_alternate_row();
		}else{
			print "<tr bgcolor='#$custom_row_color'>";
		}

		print "<td>";
		if ($disable_controls == false) { print "<a href='" . htmlspecialchars("$filename?action=item_edit&id=" . $item["id"] . "&$url_data") . "'>"; }
		print "<strong>Item # " . ($i+1) . "</strong>";
		if ($disable_controls == false) { print "</a>"; }
		print "</td>\n";

		if (empty($item["data_source_name"])) { $item["data_source_name"] = "No Task"; }

		switch (true) {
		case preg_match("/(AREA|STACK|GPRINT|LINE[123])/", $_graph_type_name):
			$matrix_title = "(" . $item["data_source_name"] . "): " . $item["text_format"];
			break;
		case preg_match("/(HRULE)/", $_graph_type_name):
			$matrix_title = "HRULE: " . $item["value"];
			break;
		case preg_match("/(VRULE)/", $_graph_type_name):
			$matrix_title = "VRULE: " . $item["value"];
			break;
		case preg_match("/(COMMENT)/", $_graph_type_name):
			$matrix_title = "COMMENT: " . $item["text_format"];
			break;
		}

		if ($item["hard_return"] == "on") {
			$hard_return = "<strong><font color=\"#FF0000\">&lt;HR&gt;</font></strong>";
		}

		print "<td style='$this_row_style'>" . htmlspecialchars($matrix_title) . $hard_return . "</td>\n";
		print "<td style='$this_row_style'>" . $graph_item_types{$item["graph_type_id"]} . "</td>\n";
		print "<td style='$this_row_style'>" . $consolidation_functions{$item["consolidation_function_id"]} . "</td>\n";
		print "<td" . ((!empty($item["hex"])) ? " bgcolor='#" . $item["hex"] . "'" : "") . " width='1%'>&nbsp;</td>\n";
		print "<td style='$this_row_style'>" . $item["hex"] . "</td>\n";

		if ($disable_controls == false) {
			print "<td><a href='" . htmlspecialchars("$filename?action=item_movedown&id=" . $item["id"] . "&$url_data") . "'><img src='" . $config['url_path'] . "images/move_down.gif' border='0' alt='Move Down'></a>
					<a href='" . htmlspecialchars("$filename?action=item_moveup&id=" . $item["id"] . "&$url_data") . "'><img src='" . $config['url_path'] . "images/move_up.gif' border='0' alt='Move Up'></a></td>\n";
			print "<td align='right'><a href='" . htmlspecialchars("$filename?action=item_remove&id=" . $item["id"] . "&$url_data") . "'><img src='" . $config['url_path'] . "images/delete_icon.gif' style='height:10px;width:10px;' border='0' alt='Delete'></a></td>\n";
		}

		print "</tr>";

		$i++;
	}
	}else{
		print "<tr class='tableRow'><td colspan='7'><em>No Items</em></td></tr>";
	}
}

/* draw_menu - draws the cacti menu for display in the console */
function draw_menu($user_menu = "") {
	global $config, $user_auth_realm_filenames, $menu;

	if (strlen($user_menu == 0)) {
		$user_menu = $menu;
	}

	?>

	<tr>
	<td>
	<table width='100%' cellpadding='0' cellspacing='0' border='0'>
	<tr><td>
	<div id='menu'>
	<ul id='nav'>

	<?php

	/* loop through each header */
	foreach ($user_menu as $header_name => $header_array) {
		/* pass 1: see if we are allowed to view any children */
		$show_header_items = false;
		foreach ($header_array as $item_url => $item_title) {
			$current_realm_id = (isset($user_auth_realm_filenames{basename($item_url)}) ? $user_auth_realm_filenames{basename($item_url)} : 0);

			if (is_realm_allowed($current_realm_id)) {
				$show_header_items = true;
			}
		}

		if ($show_header_items == true) {
			print "<li><a class='active' href='#'>$header_name</a>\n";
			print "<ul style='display:block;'>\n";

			/* pass 2: loop through each top level item and render it */
			foreach ($header_array as $item_url => $item_title) {
				$current_realm_id = (isset($user_auth_realm_filenames{basename($item_url)}) ? $user_auth_realm_filenames{basename($item_url)} : 0);

				/* if this item is an array, then it contains sub-items. if not, is just
				the title string and needs to be displayed */
				if (is_array($item_title)) {
					$i = 0;

					if ($current_realm_id == -1 || is_realm_allowed($current_realm_id) || !isset($user_auth_realm_filenames{basename($item_url)})) {
						/* if the current page exists in the sub-items array, draw each sub-item */
						if (array_key_exists(basename($_SERVER["PHP_SELF"]), $item_title) == true) {
							$draw_sub_items = true;
						}else{
							$draw_sub_items = false;
						}

						while (list($item_sub_url, $item_sub_title) = each($item_title)) {
							$item_sub_url = $config['url_path'] . $item_sub_url;

							/* always draw the first item (parent), only draw the children if we are viewing a page
							that is contained in the sub-items array */
							if (($i == 0) || ($draw_sub_items)) {
								if (basename($_SERVER["PHP_SELF"]) == basename($item_sub_url)) {
									print "<li><a class='pick selected' href='" . htmlspecialchars($item_sub_url) . "'>$item_sub_title</a></li>\n";
								}else{
									print "<li><a class='pick' href='" . htmlspecialchars($item_sub_url) . "'>$item_sub_title</a></li>\n";
								}
							}

							$i++;
						}
					}
				}else{
					if ($current_realm_id == -1 || is_realm_allowed($current_realm_id) || !isset($user_auth_realm_filenames{basename($item_url)})) {
						/* draw normal (non sub-item) menu item */
						$item_url = $config['url_path'] . $item_url;
						if (basename($_SERVER["PHP_SELF"]) == basename($item_url)) {
							print "<li><a class='pic selected' href='" . htmlspecialchars($item_url) . "'>$item_title</a></li>\n";
						}else{
							print "<li><a class='pic' href='" . htmlspecialchars($item_url) . "'>$item_title</a></li>\n";
						}
					}
				}
			}

			print "</ul></li>\n";
		}
	}

	?>

	</ul>
	</div>
	<script type='text/javascript'>
	$(function () {
	<?php if (read_config_option('selected_theme') != 'classic') {?>

		// Initialize the navigation settings
		$('#nav > li > a').each(function() {
			active = $.cookie($(this).text());
			if (active != null) {
				if (active == 'active') {
					$(this).next().show();
				}else{
					$(this).next().hide();
				}
			}
		});

		// Functon to give life to the Navigation pane
		$('#nav li:has(ul) a.active').click(function() {
			if ($(this).next().is(':visible')){
				$(this).next().slideUp( { duration: 200, easing: 'swing' } );
				$.cookie($(this).text(), 'collapsed', { expires: 31, path: '/cacti/' } );
			} else {
				$(this).next().slideToggle( { duration: 200, easing: 'swing' } );
				if ($(this).next().is(':visible')) {
					$.cookie($(this).text(), 'active', { expires: 31, path: '/cacti/' } );
				}else{
			        $.cookie($(this).text(), 'collapsed', { expires: 31, path: '/cacti/'} );
				}
			}
		});
	<?php }?>
	});
	</script>
	</td>
	</tr>
	</table></td></tr>
	<?php
}

/* draw_actions_dropdown - draws a table the allows the user to select an action to perform
     on one or more data elements
   @arg $actions_array - an array that contains a list of possible actions. this array should
     be compatible with the form_dropdown() function */
function draw_actions_dropdown($actions_array) {
	global $config;
	?>
	<table align='center' width='100%'>
		<tr>
			<td width='100%' valign='top'>
				<img src='<?php echo $config['url_path']; ?>images/arrow.gif' alt='' align='middle'>&nbsp;
			</td>
			<td align='right' style='white-space:nowrap;'>
				Choose an action:
			</td>
			<td align='right'>
				<?php form_dropdown("drp_action",$actions_array,"","","1","","");?>
			</td>
			<td width='1' align='right'>
				<input type='submit' value='Go' title='Execute Action'>
			</td>
		</tr>
	</table>

	<input type='hidden' name='action' value='actions'>
	<?php
}

/*
 * Deprecated functions
 */

function DrawMatrixHeaderItem($matrix_name, $matrix_text_color, $column_span = 1) { 
	?>
	<th style='height:1px;' colspan='<?php print $column_span;?>'>
		<div class='textSubHeaderDark'><?php print $matrix_name;?></div>
	</th>
	<?php 
}

function form_area($text) { ?>
	<tr>
		<td class="textArea">
			<?php print $text;?>
		</td>
	</tr>
<?php }

function is_console_page($url) {
	global $menu;

	if (basename($url) == 'index.php') {
		return true;
	}

	if (basename($url) == 'rrdcleaner.php') {
		return true;
	}

	if (api_plugin_hook_function('is_console_page', $url) != $url) {
		return true;
	}

	if (sizeof($menu)) {
	foreach($menu as $section => $children) {
		if (sizeof($children)) {
		foreach($children as $page => $name) {
			if (basename($page) == basename($url)) {
				return true;
			}
		}
		}
	}
	}

	return false;
}

function html_show_tabs_left($show_console_tab) {
	global $config, $tabs_left;

	if (read_config_option("selected_theme") == 'classic') {
		if ($show_console_tab == true) {
			?><a href="<?php echo $config['url_path']; ?>index.php"><img src="<?php echo $config['url_path']; ?>images/tab_console<?php print (is_console_page(basename($_SERVER['PHP_SELF'])) ? '_down':'');?>.gif" alt="Console" align="absmiddle" border="0"></a><?php
		}

		if (is_realm_allowed(7)) {
			?><a href="<?php echo $config['url_path']; ?>graph_view.php"><img src="<?php echo $config['url_path']; ?>images/tab_graphs<?php
			$file = basename($_SERVER['PHP_SELF']);
			if ($file == "graph_view.php" || $file == "graph_settings.php" || $file == "graph.php") {
				print "_down";
			} 
			print ".gif";?>" alt="Graphs" align="absmiddle" border="0"></a><?php
		}

		if (is_realm_allowed(21) || is_realm_allowed(22)) {
			if (substr_count($_SERVER["REQUEST_URI"], "reports_")) {
				print '<a href="' . $config['url_path'] . (is_realm_allowed(22) ? 'reports_admin.php':'reports_user.php') . '"><img src="' . $config['url_path'] . 'images/tab_nectar_down.gif" alt="Reporting" align="absmiddle" border="0"></a>';
			}else{
				print '<a href="' . $config['url_path'] . (is_realm_allowed(22) ? 'reports_admin.php':'reports_user.php') . '"><img src="' . $config['url_path'] . 'images/tab_nectar.gif" alt="Reporting" align="absmiddle" border="0"></a>';
			}
		}

		if (is_realm_allowed(18) || is_realm_allowed(19)) {
			if (substr_count($_SERVER["REQUEST_URI"], "clog")) {
				print '<a href="' . $config['url_path'] . (is_realm_allowed(18) ? 'clog.php':'clog_user.php') . '"><img src="' . $config['url_path'] . 'images/tab_clog_down.png" alt="Cacti Log" align="absmiddle" border="0"></a>';
			}else{
				print '<a href="' . $config['url_path'] . (is_realm_allowed(18) ? 'clog.php':'clog_user.php') . '"><img src="' . $config['url_path'] . 'images/tab_clog.png" alt="Cacti Log" align="absmiddle" border="0"></a>';
			}
		}

		api_plugin_hook('top_graph_header_tabs');
	}else{
		if ($show_console_tab) {
			$tabs_left[] =
			array(
				'title' => 'Console',
				'image' => '',
				'url'   => $config['url_path'] . 'index.php',
			);
		}

		$tabs_left[] =
			array(
				'title' => 'Graphs',
				'image' => '',
				'url'   => $config['url_path'] . 'graph_view.php',
			);

		$tabs_left[] =
			array(
				'title' => 'Reporting',
				'image' => '',
				'url'   => $config['url_path'] . (is_realm_allowed(22) ? 'reports_admin.php':'reports_user.php'),
			);

		$tabs_left[] =
			array(
				'title' => 'Cacti Log',
				'image' => '',
				'url'   => $config['url_path'] . (is_realm_allowed(18) ? 'clog.php':'clog_user.php'),
			);

		// Get Plugin Text Out of Band
		ob_start();
		api_plugin_hook('top_graph_header_tabs');

		$tab_text = trim(ob_get_clean());
		$tab_text = str_replace('<a', '', $tab_text);
		$tab_text = str_replace('</a>', '|', $tab_text);
		$tab_text = str_replace('<img', '', $tab_text);
		$tab_text = str_replace('<', '', $tab_text);
		$tab_text = str_replace('"', "'", $tab_text);
		$tab_text = str_replace('>', '', $tab_text);
		$elements = explode('|', $tab_text);

		foreach($elements as $p) {
			$p = trim($p);

			if ($p == '') continue;

			$altpos  = strpos($p, 'alt=');
			$hrefpos = strpos($p, 'href=');

			if ($altpos >= 0) {
				$alt = substr($p, $altpos+4);
				$parts = explode("'", $alt);
				if ($parts[0] == '') {
					$alt = $parts[1];
				}else{
					$alt = $parts[0];
				}
			}else{
				$alt = 'Title';
			}

			if ($hrefpos >= 0) {
				$href = substr($p, $hrefpos+5);
				$parts = explode("'", $href);
				if ($parts[0] == '') {
					$href = $parts[1];
				}else{
					$href = $parts[0];
				}
			}else{
				$href = 'unknown';
			}

			$tabs_left[] = array('title' => ucwords($alt), 'url' => $href);
		}

		$i = 0;
		$me_base = basename($_SERVER['PHP_SELF']);
		foreach($tabs_left as $tab) {
			$tab_base = basename($tab['url']);
			if ($tab_base == 'graph_view.php' && ($me_base == 'graph_view.php' || $me_base == 'graph.php')) {
				$tabs_left[$i]['selected'] = true;
			}elseif ($tab_base == 'index.php' && is_console_page($me_base)) {
				$tabs_left[$i]['selected'] = true;
			}elseif ($tab_base == $me_base) {
				$tabs_left[$i]['selected'] = true;
			}
			$i++;
		}

		print "<div class='maintabs'><nav><ul>\n";
		foreach($tabs_left as $tab) {
			print "<li><a class='" . (isset($tab['selected']) ? 'selected':'') . "' href='" . $tab['url'] . "'>" . $tab['title'] . "</a></li>\n";
		}
		print "</ul></nav></div>\n";
	}
}

function html_graph_tabs_right($current_user) {
	global $config, $tabs_right;

	if (read_config_option("selected_theme") == 'classic') {
		if (is_view_allowed('graph_settings')) {
			print '<a href="' . $config['url_path'] . 'graph_settings.php"><img src="' . $config['url_path'] . 'images/tab_settings';
			if (basename($_SERVER["PHP_SELF"]) == "graph_settings.php") {
				print "_down";
			}
			print '.gif" border="0" alt="Settings" align="absmiddle"></a>';
		}?>&nbsp;&nbsp;<?php

		if (is_view_allowed('show_tree')) {
			?><a href="<?php print htmlspecialchars($config['url_path'] . "graph_view.php?action=tree");?>"><img src="<?php echo $config['url_path']; ?>images/tab_mode_tree<?php
			if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "tree") {
				print "_down";
			}?>.gif" border="0" title="Tree View" alt="Tree View" align="absmiddle"></a><?php
		}?><?php

		if (is_view_allowed('show_list')) {
			?><a href="<?php print htmlspecialchars($config['url_path'] . "graph_view.php?action=list");?>"><img src="<?php echo $config['url_path']; ?>images/tab_mode_list<?php
			if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "list") {
				print "_down";
			}?>.gif" border="0" title="List View" alt="List View" align="absmiddle"></a><?php
		}?><?php

		if (is_view_allowed('show_preview')) {
			?><a href="<?php print htmlspecialchars($config['url_path'] . "graph_view.php?action=preview");?>"><img src="<?php echo $config['url_path']; ?>images/tab_mode_preview<?php
			if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "preview") {
				print "_down";
			}?>.gif" border="0" title="Preview View" alt="Preview View" align="absmiddle"></a><?php
		}?>&nbsp;<br>
		<?php
	}else{
		$tabs_right = array(
			array(
				'title' => 'Settings',
				'image' => '',
				'id'    => 'settings',
				'url'   => 'graph_settings.php',
			),
			array(
				'title' => 'Tree View',
				'image' => 'images/tab_tree.gif',
				'id'    => 'tree',
				'url'   => 'graph_view.php?action=tree',
			),
			array(
				'title' => 'List View',
				'image' => 'images/tab_list.gif',
				'id'    => 'list',
				'url'   => 'graph_view.php?action=list',
			),
			array(
				'title' => 'Preview',
				'image' => 'images/tab_preview.gif',
				'id'    => 'preview',
				'url'   => 'graph_view.php?action=preview',
			),
		);

		$i = 0;
		foreach($tabs_right as $tab) {
			if ($tab['url'] == 'graph_settings.php' && (basename($_SERVER['PHP_SELF']) == 'graph_settings.php')) {
				$tabs_right[$i]['selected'] = true;
			}elseif ($tab['id'] == 'tree') {
				if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'tree') {
					$tabs_right[$i]['selected'] = true;
				}
			}elseif ($tab['id'] == 'list') {
				if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'list') {
					$tabs_right[$i]['selected'] = true;
				}
			}elseif ($tab['id'] == 'preview') {
				if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'preview') {
					$tabs_right[$i]['selected'] = true;
				}
			}elseif (strstr($_SERVER['PHP_SELF'], $tab['url'])) {
				$tabs_right[$i]['selected'] = true;
			}

			$i++;
		}

		print "<div class='tabs' style='float:right;'><nav><ul>\n";
		foreach($tabs_right as $tab) {
			switch($tab['id']) {
			case 'settings':
				if (is_view_allowed('graph_settings') == true) {
					if (isset($tab['image']) && $tab['image'] != '') {
						print "<li><a title='" . $tab['title'] . "' class='" . (isset($tab['selected']) ? 'selected':'') . "' href='" . $tab['url'] . "'><img src='" . $config['url_path'] . $tab['image'] . "' border='0' align='bottom'></a></li>\n";
					}else{
						print "<li><a title='" . $tab['title'] . "' class='" . (isset($tab['selected']) ? 'selected':'') . "' href='" . $tab['url'] . "'>" . $tab['title'] . "</a></li>\n";
					}
					break;
				}

				break;
			case 'tree':
				if (is_view_allowed('show_tree')) {
					if (isset($tab['image']) && $tab['image'] != '') {
						print "<li><a title='" . $tab['title'] . "' class='" . (isset($tab['selected']) ? 'selected':'') . "' href='" . $tab['url'] . "'><img src='" . $config['url_path'] . $tab['image'] . "' border='0' align='bottom'></a></li>\n";
					}else{
						print "<li><a title='" . $tab['title'] . "' class='" . (isset($tab['selected']) ? 'selected':'') . "' href='" . $tab['url'] . "'>" . $tab['title'] . "</a></li>\n";
					}
					break;
				}

				break;
			case 'list':
				if (is_view_allowed('show_list')) {
					if (isset($tab['image']) && $tab['image'] != '') {
						print "<li><a title='" . $tab['title'] . "' class='" . (isset($tab['selected']) ? 'selected':'') . "' href='" . $tab['url'] . "'><img src='" . $config['url_path'] . $tab['image'] . "' border='0' align='bottom'></a></li>\n";
					}else{
						print "<li><a title='" . $tab['title'] . "' class='" . (isset($tab['selected']) ? 'selected':'') . "' href='" . $tab['url'] . "'>" . $tab['title'] . "</a></li>\n";
					}
					break;
				}

				break;
			case 'preview':
				if (is_view_allowed('show_preview')) {
					if (isset($tab['image']) && $tab['image'] != '') {
						print "<li><a title='" . $tab['title'] . "' class='" . (isset($tab['selected']) ? 'selected':'') . "' href='" . $tab['url'] . "'><img src='" . $config['url_path'] . $tab['image'] . "' border='0' align='bottom'></a></li>\n";
					}else{
						print "<li><a title='" . $tab['title'] . "' class='" . (isset($tab['selected']) ? 'selected':'') . "' href='" . $tab['url'] . "'>" . $tab['title'] . "</a></li>\n";
					}
					break;
				}

				break;
			}
		}
		print "</ul></nav></div>\n";
	}
}

function html_host_filter($host_id) {
	$theme = read_config_option('selected_theme');

	if ($theme == 'classic') {
		?>
		<td width='50'>
			Device
		</td>
		<td width='1'>
			<select id='host_id' name='host_id' onChange='applyFilter()'>
				<option value='-1'<?php if (get_request_var_request('host_id') == '-1') {?> selected<?php }?>>Any</option>
				<option value='0'<?php if (get_request_var_request('host_id') == '0') {?> selected<?php }?>>None</option>
				<?php
				$hosts = db_fetch_assoc("SELECT id, CONCAT_WS('',description,' (',hostname,')') AS name FROM host ORDER BY description, hostname");

				if (sizeof($hosts) > 0) {
					foreach ($hosts as $host) {
						print "<option value='" . $host['id'] . "'"; if (get_request_var_request('host_id') == $host['id']) { print ' selected'; } print '>' . title_trim(htmlspecialchars($host['name']), 40) . "</option>\n";
					}
				}
				?>
			</select>
		</td>
		<?php
	}else{
		if ($host_id > 0) {
			$hostname = db_fetch_cell_prepared("SELECT description FROM host WHERE id = ?", array($host_id));
		}elseif ($host_id == 0) {
			$hostname = 'None';
		}else{
			$hostname = 'Any';
		}

		?>
		<td width='50'>
			Device
		</td>
		<td width='1'>
			<span id='host_wrapper' style='width:200px;' class='ui-selectmenu-button ui-widget ui-state-default ui-corner-all'>
				<span id='host_click' class='ui-icon ui-icon-triangle-1-s'></span>
				<input size='28' id='host' value='<?php print $hostname;?>'>
			</span>
			<input type='hidden' id='host_id' name='host_id' value='<?php print $host_id;?>'>
		</td>
	<?php
	}
}

