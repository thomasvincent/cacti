<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004 Ian Berry                                            |
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

if (!defined("VALID_HOST_FIELDS")) {
	define("VALID_HOST_FIELDS", "(hostname|snmp_community|snmpv3_auth_username|snmpv3_auth_password|snmpv3_auth_protocol|snmpv3_priv_passphrase|snmpv3_priv_protocol|snmp_version|snmp_port|snmp_timeout)");
}

/* file: cdef.php, action: edit */
$fields_cdef_edit = array(
	"name" => array(
		"method" => "textbox",
		"friendly_name" => "Name",
		"description" => "A useful name for this CDEF.",
		"value" => "|arg1:name|",
		"max_length" => "255",
		),
	"id" => array(
		"method" => "hidden_zero",
		"value" => "|arg1:id|"
		),
	"save_component_cdef" => array(
		"method" => "hidden",
		"value" => "1"
		)
	);

/* file: color.php, action: edit */
$fields_color_edit = array(
	"hex" => array(
		"method" => "textbox",
		"friendly_name" => "Hex Value",
		"description" => "The hex value for this color; valid range: 000000-FFFFFF.",
		"value" => "|arg1:hex|",
		"max_length" => "6",
		),
	"id" => array(
		"method" => "hidden_zero",
		"value" => "|arg1:id|"
		),
	"save_component_color" => array(
		"method" => "hidden",
		"value" => "1"
		)
	);

/* file: data_pollers.php, action: edit */
$fields_data_poller_edit = array(
	"name" => array(
		"method" => "textbox",
		"friendly_name" => "Poller Name",
		"description" => "Enter a meaningful name for this poller.",
		"value" => "|arg1:name|",
		"max_length" => "255"
		),
	"hostname" => array(
		"method" => "textbox",
		"friendly_name" => "Hostname",
		"description" => "Enter the IP address or hostname of this poller.",
		"value" => "|arg1:hostname|",
		"max_length" => "255"
		),
	"active" => array(
		"method" => "checkbox",
		"friendly_name" => "Poller Active",
		"description" => "Whether or not this data poller is to be used.",
		"default" => "",
		"value" => "|arg1:active|",
		"form_id" => false
		),
	"id" => array(
		"method" => "hidden_zero",
		"value" => "|arg1:id|"
		),
	"save_component_data_poller" => array(
		"method" => "hidden",
		"value" => "1"
		)
	);

/* file: data_input.php, action: edit */
$fields_data_input_edit = array(
	"name" => array(
		"method" => "textbox",
		"friendly_name" => "Name",
		"description" => "Enter a meaningful name for this data input method.",
		"value" => "|arg1:name|",
		"max_length" => "255",
		),
	"type_id" => array(
		"method" => "drop_array",
		"friendly_name" => "Input Type",
		"description" => "Choose what type of data input method this is.",
		"value" => "|arg1:type_id|",
		"array" => $script_types,
		),
	"input_string" => array(
		"method" => "textbox",
		"friendly_name" => "Input String",
		"description" => "The data that is sent to the script, which includes the complete path to the script and input sources in &lt;&gt; brackets.",
		"value" => "|arg1:input_string|",
		"max_length" => "255",
		),
	"id" => array(
		"method" => "hidden_zero",
		"value" => "|arg1:id|"
		),
	"save_component_data_input" => array(
		"method" => "hidden",
		"value" => "1"
		)
	);

/* file: data_input.php, action: field_edit (dropdown) */
$fields_data_input_field_edit_1 = array(
	"data_name" => array(
		"method" => "drop_array",
		"friendly_name" => "Field [|arg1:|]",
		"description" => "Choose the associated field from the |arg1:| field.",
		"value" => "|arg3:data_name|",
		"array" => "|arg2:|",
		)
	);

/* file: data_input.php, action: field_edit (textbox) */
$fields_data_input_field_edit_2 = array(
	"data_name" => array(
		"method" => "textbox",
		"friendly_name" => "Field [|arg1:|]",
		"description" => "Enter a name for this |arg1:| field.",
		"value" => "|arg2:data_name|",
		"max_length" => "50",
		)
	);

/* file: data_input.php, action: field_edit */
$fields_data_input_field_edit = array(
	"name" => array(
		"method" => "textbox",
		"friendly_name" => "Friendly Name",
		"description" => "Enter a meaningful name for this data input method.",
		"value" => "|arg1:name|",
		"max_length" => "200",
		),
	"update_rra" => array(
		"method" => "checkbox",
		"friendly_name" => "Update RRD File",
		"description" => "Whether data from this output field is to be entered into the rrd file.",
		"value" => "|arg1:update_rra|",
		"default" => "on",
		"form_id" => "|arg1:id|"
		),
	"regexp_match" => array(
		"method" => "textbox",
		"friendly_name" => "Regular Expression Match",
		"description" => "If you want to require a certain regular expression to be matched againt input data, enter it here (ereg format).",
		"value" => "|arg1:regexp_match|",
		"max_length" => "200"
		),
	"allow_nulls" => array(
		"method" => "checkbox",
		"friendly_name" => "Allow Empty Input",
		"description" => "Check here if you want to allow NULL input in this field from the user.",
		"value" => "|arg1:allow_nulls|",
		"default" => "",
		"form_id" => false
		),
	"type_code" => array(
		"method" => "textbox",
		"friendly_name" => "Special Type Code",
		"description" => "If this field should be treated specially by host templates, indicate so here. Valid keywords for this field are 'hostname', 'snmp_community', 'snmpv3_auth_username', 'snmpv3_auth_password', 'snmpv3_auth_protocol', 'snmpv3_priv_passphrase', 'snmpv3_priv_protocol', 'snmp_port', 'snmp_timeout', and 'snmp_version'.",
		"value" => "|arg1:type_code|",
		"max_length" => "40"
		),
	"id" => array(
		"method" => "hidden_zero",
		"value" => "|arg1:id|"
		),
	"input_output" => array(
		"method" => "hidden",
		"value" => "|arg2:|"
		),
	"sequence" => array(
		"method" => "hidden_zero",
		"value" => "|arg1:sequence|"
		),
	"data_input_id" => array(
		"method" => "hidden_zero",
		"value" => "|arg3:data_input_id|"
		),
	"save_component_field" => array(
		"method" => "hidden",
		"value" => "1"
		)
	);

/* file: data_templates.php, action: template_edit */
$fields_data_template_template_edit = array(
	"template_name" => array(
		"method" => "textbox",
		"friendly_name" => "Name",
		"description" => "The name given to this data template.",
		"value" => "|arg1:name|",
		"max_length" => "150",
		),
	"data_template_id" => array(
		"method" => "hidden_zero",
		"value" => "|arg2:data_template_id|"
		),
	"data_template_data_id" => array(
		"method" => "hidden_zero",
		"value" => "|arg2:id|"
		),
	"current_rrd" => array(
		"method" => "hidden_zero",
		"value" => "|arg3:view_rrd|"
		),
	"save_component_template" => array(
		"method" => "hidden",
		"value" => "1"
		)
	);

/* file: (data_sources.php|data_templates.php), action: (ds|template)_edit */
$struct_data_source = array(
	"name" => array(
		"friendly_name" => "Name",
		"method" => "textbox",
		"max_length" => "250",
		"default" => "",
		"description" => "Choose a name for this data source.",
		"flags" => ""
		),
	"data_source_path" => array(
		"friendly_name" => "Data Source Path",
		"method" => "textbox",
		"max_length" => "255",
		"default" => "",
		"description" => "The full path to the RRD file.",
		"flags" => "NOTEMPLATE"
		),
	"data_input_id" => array(
		"friendly_name" => "Data Input Method",
		"method" => "drop_sql",
		"sql" => "select id,name from data_input order by name",
		"default" => "",
		"none_value" => "None",
		"description" => "The script/source used to gather data for this data source.",
		"flags" => "ALWAYSTEMPLATE"
		),
	"rra_id" => array(
		"method" => "drop_multi_rra",
		"friendly_name" => "Associated RRA's",
		"description" => "Which RRA's to use when entering data. (It is recommended that you select all of these values).",
		"form_id" => "|arg1:id|",
		"sql" => "select rra_id as id,data_template_data_id from data_template_data_rra where data_template_data_id=|arg1:id|",
		"sql_all" => "select rra.id from rra order by id",
		"sql_print" => "select rra.name from data_template_data_rra,rra where data_template_data_rra.rra_id=rra.id and data_template_data_rra.data_template_data_id=|arg1:id|",
		"flags" => "ALWAYSTEMPLATE"
		),
	"rrd_step" => array(
		"friendly_name" => "Step",
		"method" => "textbox",
		"max_length" => "10",
		"size" => "20",
		"default" => "300",
		"description" => "The amount of time in seconds between expected updates.",
		"flags" => ""
		),
	"active" => array(
		"friendly_name" => "Data Source Active",
		"method" => "checkbox",
		"default" => "on",
		"description" => "Whether Cacti should gather data for this data source or not.",
		"flags" => ""
		)
	);

/* file: (data_sources.php|data_templates.php), action: (ds|template)_edit */
$struct_data_source_item = array(
	"data_source_name" => array(
		"friendly_name" => "Internal Data Source Name",
		"method" => "textbox",
		"max_length" => "19",
		"default" => "",
		"description" => "Choose unique name to represent this piece of data inside of the rrd file."
		),
	"rrd_minimum" => array(
		"friendly_name" => "Minimum Value",
		"method" => "textbox",
		"max_length" => "20",
		"size" => "30",
		"default" => "0",
		"description" => "The minimum value of data that is allowed to be collected."
		),
	"rrd_maximum" => array(
		"friendly_name" => "Maximum Value",
		"method" => "textbox",
		"max_length" => "20",
		"size" => "30",
		"default" => "0",
		"description" => "The maximum value of data that is allowed to be collected."
		),
	"data_source_type_id" => array(
		"friendly_name" => "Data Source Type",
		"method" => "drop_array",
		"array" => $data_source_types,
		"default" => "",
		"description" => "How data is represented in the RRA."
		),
	"rrd_heartbeat" => array(
		"friendly_name" => "Heartbeat",
		"method" => "textbox",
		"max_length" => "20",
		"size" => "30",
		"default" => "600",
		"description" => "The maximum amount of time that can pass before data is entered as \"unknown\".
			(Usually 2x300=600)"
		),
	"data_input_field_id" => array(
		"friendly_name" => "Output Field",
		"method" => "drop_sql",
		"default" => "0",
		"description" => "When data is gathered, the data for this field will be put into this data source."
		)
	);

/* file: grprint_presets.php, action: edit */
$fields_grprint_presets_edit = array(
	"name" => array(
		"method" => "textbox",
		"friendly_name" => "Name",
		"description" => "Enter a name for this GPRINT preset, make sure it is something you recognize.",
		"value" => "|arg1:name|",
		"max_length" => "50",
		),
	"gprint_text" => array(
		"method" => "textbox",
		"friendly_name" => "GPRINT Text",
		"description" => "Enter the custom GPRINT string here.",
		"value" => "|arg1:gprint_text|",
		"max_length" => "50",
		),
	"id" => array(
		"method" => "hidden_zero",
		"value" => "|arg1:id|"
		),
	"save_component_gprint_presets" => array(
		"method" => "hidden",
		"value" => "1"
		)
	);

/* file: (graphs.php|graph_templates.php), action: (graph|template)_edit */
$struct_graph = array(
	"general_header" => array(
		"friendly_name" => "General Options",
		"method" => "spacer"
		),
	"title" => array(
		"friendly_name" => "Title",
		"method" => "textbox",
		"max_length" => "255",
		"default" => "",
		"description" => "The name that is printed on the graph."
		),
	"vertical_label" => array(
		"friendly_name" => "Vertical Label",
		"method" => "textbox",
		"max_length" => "255",
		"default" => "",
		"description" => "The label vertically printed to the left of the graph."
		),
	"image_format_id" => array(
		"friendly_name" => "Image Format",
		"method" => "drop_array",
		"array" => $image_types,
		"default" => "1",
		"description" => "The type of graph that is generated; GIF or PNG."
		),
	"export" => array(
		"friendly_name" => "Allow Graph Export",
		"method" => "checkbox",
		"default" => "on",
		"description" => "Choose whether this graph will be included in the static html/png export if you use
			cacti's export feature."
		),
	"base_value" => array(
		"friendly_name" => "Base Value",
		"method" => "textbox",
		"max_length" => "50",
		"default" => "1000",
		"description" => "Should be set to 1024 for memory and 1000 for traffic measurements."
		),
	"size_header" => array(
		"friendly_name" => "Image Size Options",
		"method" => "spacer"
		),
	"height" => array(
		"friendly_name" => "Height",
		"method" => "textbox",
		"max_length" => "50",
		"default" => "120",
		"description" => "The height (in pixels) that the graph is."
		),
	"width" => array(
		"friendly_name" => "Width",
		"method" => "textbox",
		"max_length" => "50",
		"default" => "500",
		"description" => "The width (in pixels) that the graph is."
		),
	"grid_header" => array(
		"friendly_name" => "Grid Options",
		"method" => "spacer"
		),
	"x_grid" => array(
		"friendly_name" => "X-Grid",
		"method" => "textbox",
		"max_length" => "100",
		"default" => "",
		"description" => "Controls the layout of the x-grid.  See the RRDTool manual for additional details."
		),
	"y_grid" => array(
		"friendly_name" => "Y-Grid",
		"method" => "textbox",
		"max_length" => "100",
		"default" => "",
		"description" => "Controls the layout of the y-grid.  See the RRDTool manual for additional details."
		),
	"y_grid_alt" => array(
		"friendly_name" => "Alternate Y-Grid",
		"method" => "checkbox",
		"default" => "",
		"description" => "Allows the dynamic placement of the y-grid based upon min and max values."
		),
	"no_minor" => array(
		"friendly_name" => "No Minor Grid Lines",
		"method" => "checkbox",
		"default" => "",
		"description" => "Removes minor grid lines.  Especially usefull on small graphs."
		),
	"ascale_header" => array(
		"friendly_name" => "Auto Scaling Options",
		"method" => "spacer"
		),
	"auto_scale" => array(
		"friendly_name" => "Utilize Auto Scale",
		"method" => "checkbox",
		"default" => "on",
		"description" => "Auto scale the y-axis instead of defining an upper and lower limit. Note: if this is check both the
			Upper and Lower limit will be ignored."
		),
	"auto_scale_opts" => array(
		"friendly_name" => "Standard Auto Scale Options",
		"method" => "radio",
		"default" => "2",
		"description" => "Use --alt-autoscale-max to scale to the maximum value, or --alt-autoscale to scale to the absolute
			minimum and maximum.",
		"items" => array(
			0 => array(
				"radio_value" => "1",
				"radio_caption" => "Use --alt-autoscale"
				),
			1 => array(
				"radio_value" => "2",
				"radio_caption" => "Use --alt-autoscale-max"
				)
			)
		),
	"auto_scale_log" => array(
		"friendly_name" => "Logarithmic Auto Scaling (--logarithmic)",
		"method" => "checkbox",
		"default" => "",
		"description" => "Use Logarithmic y-axis scaling"
		),
	"auto_scale_rigid" => array(
		"friendly_name" => "Rigid Boundaries Mode (--rigid)",
		"method" => "checkbox",
		"default" => "",
		"description" => "Do not expand the lower and upper limit if the graph contains a value outside the valid range."
		),
	"auto_padding" => array(
		"friendly_name" => "Auto Padding",
		"method" => "checkbox",
		"default" => "on",
		"description" => "Pad text so that legend and graph data always line up. Note: this could cause
			graphs to take longer to render because of the larger overhead. Also Auto Padding may not
			be accurate on all types of graphs, consistant labeling usually helps."
		),
	"rscale_header" => array(
		"friendly_name" => "Fixed Scaling Options",
		"method" => "spacer"
		),
	"upper_limit" => array(
		"friendly_name" => "Upper Limit",
		"method" => "textbox",
		"max_length" => "50",
		"default" => "100",
		"description" => "The maximum vertical value for the rrd graph."
		),
	"lower_limit" => array(
		"friendly_name" => "Lower Limit",
		"method" => "textbox",
		"max_length" => "255",
		"default" => "0",
		"description" => "The minimum vertical value for the rrd graph."
		),
	"unit_header" => array(
		"friendly_name" => "Units Display Options",
		"method" => "spacer"
		),
	"unit_value" => array(
		"friendly_name" => "Units Value",
		"method" => "textbox",
		"max_length" => "50",
		"default" => "",
		"description" => "(--unit) Sets the exponent value on the Y-axis for numbers. Note: This option was
			recently added in rrdtool 1.0.36."
		),
	"unit_length" => array(
		"friendly_name" => "Units Length (default 9)",
		"method" => "textbox",
		"max_length" => "50",
		"default" => "",
		"description" => "Sets the number of spaces for the units value to the left of the graph."
		),
	"unit_exponent_value" => array(
		"friendly_name" => "Units Exponent Value",
		"method" => "textbox",
		"max_length" => "50",
		"default" => "",
		"description" => "What unit cacti should use on the Y-axis. Use 3 to display everything in 'k' or -6
			to display everything in 'u' (micro)."
		)
	);

/* file: (graphs.php|graph_templates.php), action: item_edit */
$struct_graph_item = array(
	"task_item_id" => array(
		"friendly_name" => "Data Source",
		"method" => "drop_sql",
		"sql" => "select
			CONCAT_WS('',case when host.description is null then 'No Host' when host.description is not null then host.description end,' - ',data_template_data.name,' (',data_template_rrd.data_source_name,')') as name,
			data_template_rrd.id
			from data_template_data,data_template_rrd,data_local
			left join host on data_local.host_id=host.id
			where data_template_rrd.local_data_id=data_local.id
			and data_template_data.local_data_id=data_local.id
			order by name",
		"default" => "0",
		"none_value" => "None",
		"description" => "The data source to use for this graph item."
		),
	"color_id" => array(
		"friendly_name" => "Color",
		"method" => "drop_color",
		"default" => "0",
		"description" => "The color to use for the legend."
		),
	"graph_type_id" => array(
		"friendly_name" => "Graph Item Type",
		"method" => "drop_array",
		"array" => $graph_item_types,
		"default" => "0",
		"description" => "How data for this item is represented visually on the graph."
		),
	"consolidation_function_id" => array(
		"friendly_name" => "Consolidation Function",
		"method" => "drop_array",
		"array" => $consolidation_functions,
		"default" => "0",
		"description" => "How data for this item is represented statistically on the graph."
		),
	"cdef_id" => array(
		"friendly_name" => "CDEF Function",
		"method" => "drop_sql",
		"sql" => "select id,name from cdef order by name",
		"default" => "0",
		"none_value" => "None",
		"description" => "A CDEF (math) function to apply to this item on the graph."
		),
	"value" => array(
		"friendly_name" => "Value",
		"method" => "textbox",
		"max_length" => "50",
		"default" => "",
		"description" => "The value of an HRULE or VRULE graph item."
		),
	"gprint_id" => array(
		"friendly_name" => "GPRINT Type",
		"method" => "drop_sql",
		"sql" => "select id,name from graph_templates_gprint order by name",
		"default" => "2",
		"description" => "If this graph item is a GPRINT, you can optionally choose another format
			here. You can define additional types under \"GPRINT Presets\"."
		),
	"text_format" => array(
		"friendly_name" => "Text Format",
		"method" => "textbox",
		"max_length" => "255",
		"default" => "",
		"description" => "Text that will be displayed on the legend for this graph item."
		),
	"hard_return" => array(
		"friendly_name" => "Insert Hard Return",
		"method" => "checkbox",
		"default" => "",
		"description" => "Forces the legend to the next line after this item."
		),
	"sequence" => array(
		"friendly_name" => "Sequence",
		"method" => "view"
		)
	);

/* file: graph_templates.php, action: template_edit */
$fields_graph_template_template_edit = array(
	"name" => array(
		"method" => "textbox",
		"friendly_name" => "Name",
		"description" => "The name given to this graph template.",
		"value" => "|arg1:name|",
		"max_length" => "150",
		),
	"graph_template_id" => array(
		"method" => "hidden_zero",
		"value" => "|arg2:graph_template_id|"
		),
	"graph_template_graph_id" => array(
		"method" => "hidden_zero",
		"value" => "|arg2:id|"
		),
	"save_component_template" => array(
		"method" => "hidden",
		"value" => "1"
		)
	);

/* file: graph_templates.php, action: input_edit */
$fields_graph_template_input_edit = array(
	"name" => array(
		"method" => "textbox",
		"friendly_name" => "Name",
		"description" => "Enter a name for this graph item input, make sure it is something you recognize.",
		"value" => "|arg1:name|",
		"max_length" => "50"
		),
	"description" => array(
		"method" => "textarea",
		"friendly_name" => "Description",
		"description" => "Enter a description for this graph item input to describe what this input is used for.",
		"value" => "|arg1:description|",
		"textarea_rows" => "5",
		"textarea_cols" => "40"
		),
	"column_name" => array(
		"method" => "drop_array",
		"friendly_name" => "Field Type",
		"description" => "How data is to be represented on the graph.",
		"value" => "|arg1:column_name|",
		"array" => "|arg2:|",
		),
	"graph_template_id" => array(
		"method" => "hidden_zero",
		"value" => "|arg3:graph_template_id|"
		),
	"graph_template_input_id" => array(
		"method" => "hidden_zero",
		"value" => "|arg3:id|"
		),
	"save_component_input" => array(
		"method" => "hidden",
		"value" => "1"
		)
	);

/* file: host.php, action: edit */
$fields_host_edit = array(
	"spacer0" => array(
		"method" => "spacer",
		"friendly_name" => "General Options"
		),
	"description" => array(
		"method" => "textbox",
		"friendly_name" => "Description",
		"description" => "Give this host a meaningful description.",
		"value" => "|arg1:description|",
		"max_length" => "250"
		),
	"hostname" => array(
		"method" => "textbox",
		"friendly_name" => "Hostname",
		"description" => "Fill in the fully qualified hostname for this device.",
		"value" => "|arg1:hostname|",
		"max_length" => "250"
		),
	"host_template_id" => array(
		"method" => "drop_sql",
		"friendly_name" => "Host Template",
		"description" => "Choose what type of host, host template this is. The host template will govern what kinds of data should be gathered from this type of host.",
		"value" => "|arg1:host_template_id|",
		"none_value" => "None",
		"sql" => "select id,name from host_template order by name"
		),
	"poller_id" => array(
		"method" => "drop_sql",
		"friendly_name" => "Default Poller",
		"description" => "Choose the default poller to handle this hosts request.",
		"value" => "|arg1:poller_id|",
		"sql" => "select id,name from poller"
		),
	"disabled" => array(
		"method" => "checkbox",
		"friendly_name" => "Disable Host",
		"description" => "Check this box to disable all checks for this host.",
		"value" => "|arg1:disabled|",
		"default" => "",
		"form_id" => false
		),
	"spacer1" => array(
		"method" => "spacer",
		"friendly_name" => "Availability Detection"
		),
	"availability_method" => array(
		"method" => "drop_array",
		"friendly_name" => "Availability Method",
		"description" => "Choose the availability method to use for this host.",
		"value" => "|arg1:availability_method|",
		"default" => AVAIL_SNMP,
		"array" => $availability_options
		),
	"ping_method" => array(
		"friendly_name" => "Ping Type",
		"description" => "The type of ping packet to sent.  NOTE: ICMP requires that the Cacti Service ID have root privilages in Unix.",
		"value" => "|arg1:ping_method|",
		"method" => "drop_array",
		"default" => PING_UDP,
		"array" => $ping_methods
		),
	"spacer15" => array(
		"method" => "spacer",
		"friendly_name" => "SNMP Generic Options"
		),
	"snmp_port" => array(
		"method" => "textbox",
		"friendly_name" => "SNMP Port",
		"description" => "Enter the UDP port number to use for SNMP (default is 161).",
		"value" => "|arg1:snmp_port|",
		"max_length" => "5",
		"default" => read_config_option("snmp_port"),
		"size" => "15"
		),
	"snmp_timeout" => array(
		"method" => "textbox",
		"friendly_name" => "SNMP Timeout",
		"description" => "The maximum number of milliseconds Cacti will wait for an SNMP response (does not work with php-snmp support).",
		"value" => "|arg1:snmp_timeout|",
		"max_length" => "8",
		"default" => read_config_option("snmp_timeout"),
		"size" => "15"
		),
	"snmp_version" => array(
		"method" => "drop_array",
		"friendly_name" => "SNMP Version",
		"description" => "Choose the SNMP version for this host.",
		"value" => "|arg1:snmp_version|",
		"default" => read_config_option("snmp_ver"),
		"array" => $snmp_versions
		),
	"spacer2" => array(
		"method" => "spacer",
		"friendly_name" => "SNMP v1/v2c Options"
		),
	"snmp_community" => array(
		"method" => "textbox",
		"friendly_name" => "SNMP Community",
		"description" => "Fill in the SNMP read community for this device.",
		"value" => "|arg1:snmp_community|",
		"form_id" => "|arg1:id|",
		"default" => read_config_option("snmp_community"),
		"max_length" => "100"
		),
	"spacer3" => array(
		"method" => "spacer",
		"friendly_name" => "SNMP v3 Options"
		),
	"snmpv3_auth_username" => array(
		"method" => "textbox",
		"friendly_name" => "Username",
		"description" => "The default SNMP v3 username.",
		"value" => "|arg1:snmpv3_auth_username|",
		"default" => read_config_option("snmpv3_auth_username"),
		"max_length" => "100"
		),
	"snmpv3_auth_password" => array(
		"method" => "textbox_password",
		"friendly_name" => "Password",
		"description" => "The default SNMP v3 password.",
		"value" => "|arg1:snmpv3_auth_password|",
		"default" => read_config_option("snmpv3_auth_password"),
		"max_length" => "100"
		),
	"snmpv3_auth_protocol" => array(
		"method" => "drop_array",
		"friendly_name" => "Authentication Protocol",
		"description" => "Select the default SNMP v3 authentication protocol to use.",
		"value" => "|arg1:snmpv3_auth_protocol|",
		"default" => read_config_option("snmpv3_auth_protocol"),
		"array" => $snmpv3_auth_protocol
		),
	"snmpv3_priv_passphrase" => array(
		"method" => "textbox",
		"friendly_name" => "Privacy Passphrase",
		"description" => "The default SNMP v3 privacy passphrase.",
		"value" => "|arg1:snmpv3_priv_passphrase|",
		"default" => read_config_option("snmpv3_priv_passphrase"),
		"max_length" => "100"
		),
	"snmpv3_priv_protocol" => array(
		"method" => "drop_array",
		"friendly_name" => "Privacy Protocol",
		"description" => "Select the default SNMP v3 privacy protocol to use.",
		"value" => "|arg1:snmpv3_priv_protocol|",
		"default" => read_config_option("snmpv3_priv_protocol"),
		"array" => $snmpv3_priv_protocol
		),
	"id" => array(
		"method" => "hidden_zero",
		"value" => "|arg1:id|"
		),
	"_host_template_id" => array(
		"method" => "hidden_zero",
		"value" => "|arg1:host_template_id|"
		),
	"save_component_host" => array(
		"method" => "hidden",
		"value" => "1"
		)
	);

/* file: host_templates.php, action: edit */
$fields_host_template_edit = array(
	"name" => array(
		"method" => "textbox",
		"friendly_name" => "Name",
		"description" => "A useful name for this host template.",
		"value" => "|arg1:name|",
		"max_length" => "255",
		),
	"id" => array(
		"method" => "hidden_zero",
		"value" => "|arg1:id|"
		),
	"save_component_template" => array(
		"method" => "hidden",
		"value" => "1"
		)
	);

/* file: rra.php, action: edit */
$fields_rra_edit = array(
	"name" => array(
		"method" => "textbox",
		"friendly_name" => "Name",
		"description" => "How data is to be entered in RRA's.",
		"value" => "|arg1:name|",
		"max_length" => "100",
		),
	"consolidation_function_id" => array(
		"method" => "drop_multi",
		"friendly_name" => "Consolidation Functions",
		"description" => "How data is to be entered in RRA's.",
		"array" => $consolidation_functions,
		"sql" => "select consolidation_function_id as id,rra_id from rra_cf where rra_id=|arg1:id|",
		),
	"x_files_factor" => array(
		"method" => "textbox",
		"friendly_name" => "X-Files Factor",
		"description" => "The amount of unknown data that can still be regarded as known.",
		"value" => "|arg1:x_files_factor|",
		"max_length" => "10",
		),
	"steps" => array(
		"method" => "textbox",
		"friendly_name" => "Steps",
		"description" => "How many data points are needed to put data into the RRA.",
		"value" => "|arg1:steps|",
		"max_length" => "8",
		),
	"rows" => array(
		"method" => "textbox",
		"friendly_name" => "Rows",
		"description" => "How many generations data is kept in the RRA.",
		"value" => "|arg1:rows|",
		"max_length" => "8",
		),
	"timespan" => array(
		"method" => "textbox",
		"friendly_name" => "Timespan",
		"description" => "How many seconds to display in graph for this RRA.",
		"value" => "|arg1:timespan|",
		"max_length" => "8",
		),
	"id" => array(
		"method" => "hidden_zero",
		"value" => "|arg1:id|"
		),
	"save_component_rra" => array(
		"method" => "hidden",
		"value" => "1"
		)
	);

/* file: data_queries.php, action: edit */
$fields_data_query_edit = array(
	"name" => array(
		"method" => "textbox",
		"friendly_name" => "Name",
		"description" => "A name for this data query.",
		"value" => "|arg1:name|",
		"max_length" => "100",
		),
	"description" => array(
		"method" => "textbox",
		"friendly_name" => "Description",
		"description" => "A description for this data query.",
		"value" => "|arg1:description|",
		"max_length" => "255",
		),
	"xml_path" => array(
		"method" => "textbox",
		"friendly_name" => "XML Path",
		"description" => "The full path to the XML file containing definitions for this data query.",
		"value" => "|arg1:xml_path|",
		"default" => "<path_cacti>/resource/",
		"max_length" => "255",
		),
	"data_input_id" => array(
		"method" => "drop_sql",
		"friendly_name" => "Data Input Method",
		"description" => "Choose what type of host, host template this is. The host template will govern what kinds of data should be gathered from this type of host.",
		"value" => "|arg1:data_input_id|",
		"sql" => "select id,name from data_input where (type_id=3 or type_id=4 or type_id=5 or type_id=6) order by name",
		),
	"id" => array(
		"method" => "hidden_zero",
		"value" => "|arg1:id|",
		),
	"save_component_snmp_query" => array(
		"method" => "hidden",
		"value" => "1"
		)
	);

/* file: data_queries.php, action: item_edit */
$fields_data_query_item_edit = array(
	"name" => array(
		"method" => "textbox",
		"friendly_name" => "Name",
		"description" => "A name for this associated graph.",
		"value" => "|arg1:name|",
		"max_length" => "100",
		),
	"graph_template_id" => array(
		"method" => "drop_sql",
		"friendly_name" => "Graph Template",
		"description" => "Choose what type of host, host template this is. The host template will govern what kinds of data should be gathered from this type of host.",
		"value" => "|arg1:graph_template_id|",
		"sql" => "select id,name from graph_templates order by name",
		),
	"id" => array(
		"method" => "hidden_zero",
		"value" => "|arg1:id|"
		),
	"snmp_query_id" => array(
		"method" => "hidden_zero",
		"value" => "|arg2:snmp_query_id|"
		),
	"_graph_template_id" => array(
		"method" => "hidden_zero",
		"value" => "|arg1:graph_template_id|"
		),
	"save_component_snmp_query_item" => array(
		"method" => "hidden",
		"value" => "1"
		)
	);

/* file: tree.php, action: edit */
$fields_tree_edit = array(
	"name" => array(
		"method" => "textbox",
		"friendly_name" => "Name",
		"description" => "A useful name for this graph tree.",
		"value" => "|arg1:name|",
		"max_length" => "255",
		),
	"sort_type" => array(
		"method" => "drop_array",
		"friendly_name" => "Sorting Type",
		"description" => "Choose how items in this tree will be sorted.",
		"value" => "|arg1:sort_type|",
		"array" => $tree_sort_types,
		),
	"id" => array(
		"method" => "hidden_zero",
		"value" => "|arg1:id|"
		),
	"save_component_tree" => array(
		"method" => "hidden",
		"value" => "1"
		)
	);

?>
