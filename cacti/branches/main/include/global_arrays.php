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

$actions_none = array(
	ACTION_NONE => __('None'),
);

$messages = array(
	1  => array(
		'message' => __('Save Successful.'),
		'type' => 'info'
	),
	2  => array(
		'message' => __('Save Failed.'),
		'type' => 'error'
	),
	3  => array(
		'message' => __('Save Failed: Field Input Error (Check Red Fields).'),
		'type' => 'error'
	),
	4  => array(
		'message' => __('Passwords do not match, please retype.'),
		'type' => 'error'
	),
	5  => array(
		'message' => __('You must select at least one field.'),
		'type' => 'error'
	),
	6  => array(
		'message' => __('You must have built in user authentication turned on to use this feature.'),
		'type' => 'error'
	),
	7  => array(
		'message' => __('XML parse error.'),
		'type' => 'error'
	),
	12 => array(
		'message' => __('Username already in use.'),
		'type' => 'error'
	),
	15 => array(
		'message' => __('XML: Cacti version does not exist.'),
		'type' => 'error'
	),
	16 => array(
		'message' => __('XML: Hash version does not exist.'),
		'type' => 'error'
	),
	17 => array(
		'message' => __('XML: Generated with a newer version of Cacti.'),
		'type' => 'error'
	),
	18 => array(
		'message' => __('XML: Cannot locate type code.'),
		'type' => 'error'
	),
	19 => array(
		'message' => __('Username already exists.'),
		'type' => 'error'
	),
	20 => array(
		'message' => __('Username change not permitted for designated template or guest user.'),
		'type' => 'error'
	),
	21 => array(
		'message' => __('User delete not permitted for designated template or guest user.'),
		'type' => 'error'
	),
	22 => array(
		'message' => __('User delete not permitted for designated graph export user.'),
		'type' => 'error'
	),
	23 => array(
		'message' => __('Data Template Includes Deleted Round Robin Archive.  Please run Database Repair Script to Identify and/or Correct.'),
		'type' => 'error'
	),
	24 => array(
		'message' => __('Graph Template Includes Deleted GPrint Prefix.  Please run Database Repair Script to Identify and/or Correct.'),
		'type' => 'error'
	),
	25 => array(
		'message' => __('Graph Template Includes Deleted CDEFs.  Please run Database Repair Script to Identify and/or Correct.'),
		'type' => 'error'
	),
	26 => array(
		'message' => __('Graph Template Includes Deleted Data Input Method.  Please run Database Repair Script to Identify.'),
		'type' => 'error'
	),
	27 => array(
		'message' => __('Data Template Not Found during Export.  Please run Database Repair Script to Identify.'),
		'type' => 'error'
	),
	28 => array(
		'message' => __('Device Template Not Found during Export.  Please run Database Repair Script to Identify.'),
		'type' => 'error'
	),
	29 => array(
		'message' => __('Data Query Not Found during Export.  Please run Database Repair Script to Identify.'),
		'type' => 'error'
	),
	30 => array(
		'message' => __('Graph Template Not Found during Export.  Please run Database Repair Script to Identify.'),
		'type' => 'error'
	),
);

$snmp_query_field_actions = array(1 =>
	__('SNMP Field Name (Dropdown)'),
	__('SNMP Field Value (From User)'),
	__('SNMP Output Type (Dropdown)'),
);

$banned_snmp_strings = array(
	'End of MIB',
	'No Such'
);

$logfile_options = array(1 =>
	__('Logfile Only'),
	__('Logfile and Syslog/Eventlog'),
	__('Syslog/Eventlog Only'),
);

$logfile_verbosity = array(
	POLLER_VERBOSITY_NONE 	=> __('NONE - Syslog Only if Selected'),
	POLLER_VERBOSITY_LOW 	=> __('LOW - Statistics and Errors'),
	POLLER_VERBOSITY_MEDIUM => __('MEDIUM - Statistics, Errors and Results'),
	POLLER_VERBOSITY_HIGH 	=> __('HIGH - Statistics, Errors, Results and Major I/O Events'),
	POLLER_VERBOSITY_DEBUG 	=> __('DEBUG - Statistics, Errors, Results, I/O and Program Flow'),
	POLLER_VERBOSITY_DEVDBG => __('DEVEL - Developer DEBUG Level'),
);

$poller_intervals = array(
	10 => __('Every %d Seconds', 10),
	15 => __('Every %d Seconds', 15),
	20 => __('Every %d Seconds', 20),
	30 => __('Every %d Seconds', 30),
	60 => __('Every Minute'),
	300 => __('Every %d Minutes', 5),
);

$cron_intervals = array(
	60 => __('Every Minute'),
	300 => __('Every %d Minutes', 5),
);

$registered_cacti_names = array(
	'path_cacti'
);

$snmp_implementations = array(
	'ucd-snmp' => __('UCD-SNMP 4.x'),
	'net-snmp' => __('NET-SNMP 5.x'),
);

$rrdtool_versions = array(
	RRD_VERSION_1_0 => 'RRDTool 1.0.x',
	RRD_VERSION_1_2 => 'RRDTool 1.2.x',
	RRD_VERSION_1_3 => 'RRDTool 1.3.x',
	RRD_VERSION_1_4 => 'RRDTool 1.4.x'
);


$i18n_modes = array(
	0 => __('Disabled'),
	1 => __('Enabled'),
	2 => __('Enabled (strict mode)'),
);

$menu = array(
	'management' => array(
		'description'	=> __('Management'),
		'items'			=> array(
			'tree.php'			=> __('Trees'),
			'sites.php'			=> __('Sites'),
			'devices.php'		=> __('Devices'),
			'graphs.php'		=> __('Graphs'),
			'data_sources.php'	=> __('Data Sources'),
		),
	),
	'data_collection' => array(
		'description'	=> __('Data Collection'),
		'items' 		=> array(
			'pollers.php'		=> __('Pollers'),
			'data_queries.php'	=> __('Data Queries'),
			'data_input.php'	=> __('Data Input Methods'),
		),
	),
	'templates' => array(
		'description'	=> __('Templates'),
		'items'			=> array(
			'device_templates.php'	=> __('Device'),
			'graph_templates.php'	=> __('Graph'),
			'data_templates.php'	=> __('Data Source'),
		),
	),
	'presets' => array(
		'description'	=> __('Presets'),
		'items'			=> array(
			'cdef.php'				=> __('CDEFs'),
			'vdef.php'				=> __('VDEFs'),
			'color.php'				=> __('Colors'),
			'gprint_presets.php'	=> __('GPRINT'),
			'xaxis.php'		=> __('X-Axis'),
			'rra.php'				=> __('RRAs'),
		),
	),
	'import_export' => array(
		'description'	=> __('Import/Export'),
		'items'			=> array(
			'templates_import.php' => __('Import Templates'),
			'templates_export.php' => __('Export Templates'),
		),
	),
	'configuration'  => array(
		'description'	=> __('Configuration'),
		'items'			=> array(
			'settings.php#ui-tabs-1'	=> __('Settings'),
			'plugins.php'				=> __('Plugin Management'),
		),
	),
	'utilities' => array(
		'description'	=> __('Utilities'),
		'items'			=> array(
			'utilities.php'		=> __('System Utilities'),
			'user_admin.php'	=> __('User Management'),
			'logout.php'		=> __('Logout User'),
		),
	),
);

$log_tail_lines = array(
	-1		=> __('All Lines'),
	10		=> __('%d Lines', 10),
	15		=> __('%d Lines', 15),
	20		=> __('%d Lines', 20),
	50		=> __('%d Lines', 50),
	100		=> __('%d Lines', 100),
	200		=> __('%d Lines', 200),
	500		=> __('%d Lines', 500),
	1000	=> __('%d Lines', 1000),
	2000	=> __('%d Lines', 2000),
	3000	=> __('%d Lines', 3000),
	5000	=> __('%d Lines', 5000),
	10000	=> __('%d Lines', 10000),
);

$item_rows = array(
	10   => __('%d Rows', 10),
	15   => __('%d Rows', 15),
	20   => __('%d Rows', 20),
	25   => __('%d Rows', 25),
	30   => __('%d Rows', 30),
	40   => __('%d Rows', 40),
	50   => __('%d Rows', 50),
	100  => __('%d Rows', 100),
	250  => __('%d Rows', 250),
	500  => __('%d Rows', 500),
	1000 => __('%d Rows', 1000),
	2000 => __('%d Rows', 2000),
	5000 => __('%d Rows', 5000),
);

$graphs_per_page = array(
	4    => __('%d Graphs', 4),
	6    => __('%d Graphs', 6),
	8    => __('%d Graphs', 8),
	10   => __('%d Graphs', 10),
	14   => __('%d Graphs', 14),
	20   => __('%d Graphs', 20),
	24   => __('%d Graphs', 24),
	30   => __('%d Graphs', 30),
	40   => __('%d Graphs', 40),
	50   => __('%d Graphs', 50),
);

$page_refresh_interval = array(
	5			=> __('%d Seconds', 5),
	10			=> __('%d Seconds', 10),
	20			=> __('%d Seconds', 20),
	30			=> __('%d Seconds', 30),
	60			=> __('1 Minute'),
	300			=> __('%d Minutes', 5),
	600			=> __('%d Minutes', 10),
	31536000	=> __('Never'),
);

$user_auth_realm_categories = array(
	'general'		=> array(__('General'), array(7,8)),
	'management'	=> array(__('Management'), array(4, 18, 5, 3)),
	'datacollect'	=> array(__('Data Collection'), array(19, 2, 13)),
	'templates'		=> array(__('Templates'), array(12, 10, 11)),
	'presets'		=> array(__('Presets'), array(14, 9)),
	'impexp'		=> array(__('Import/Export'), array(17,16)),
	'system'		=> array(__('System'), array(1, 15, 101)));

$user_auth_realms = array(
	7    => __('View Graphs'),
	8    => __('Console Access'),
	3    => __('Update Data Sources'),
	4    => __('Update System Trees'),
	18   => __('Update User Tees'),
	5    => __('Update Graphs'),
	19   => __('Pollers'),
	2    => __('Data Input'),
	13   => __('Data Queries'),
	12   => __('Update Device Templates'),
	10   => __('Update Graph Templates'),
	11   => __('Update Data Source Templates'),
	14   => __('Update CDEF\'s and VDEF\'s'),
	9    => __('Update Round Robin Archives'),
	16   => __('Export Data'),
	17   => __('Import Data'),
	1    => __('User Administration'),
	15   => __('Global Settings'),
	101  => __('Plugin Management')		//FIXME: verify, if 101 is correct (this should be the realm used for PIA in pre088 releases)
);

$user_auth_realm_filenames = array(
	'about.php'						=> 8,
	'cdef.php'						=> 14,
	'color.php'						=> 5,
	'data_input.php'				=> 2,
	'data_queries.php'				=> 13,
	'data_sources_items.php'		=> 3,
	'data_sources.php'				=> 3,
	'data_templates.php'			=> 11,
	'data_templates_items.php'		=> 11,
	'devices.php'					=> 3,
	'device_templates.php'			=> 12,
	'email_templates.php'			=> 8,
	'event_queue.php'				=> 8,
	'gprint_presets.php'			=> 5,
	'graph.php'						=> 7,
	'graph_image.php'				=> 7,
	'graph_xport.php'				=> 7,
	'graph_settings.php'			=> 7,
	'graph_templates.php'			=> 10,
	'graph_templates_inputs.php'	=> 10,
	'graph_templates_items.php'		=> 10,
	'graph_view.php'				=> 7,
	'graphs.php'					=> 5,
	'graphs_items.php'				=> 5,
	'graphs_new.php'				=> 5,
	'index.php'						=> 8,
	'logout.php'					=> 7,
	'plugins.php'					=> 101,
	'pollers.php'					=> 3,
	'rra.php'						=> 9,
	'settings.php'					=> 15,
	'sites.php'						=> 3,
	'smtp_servers.php'				=> 8,
	'smtp_queue.php'				=> 8,
	'templates_export.php'			=> 16,
	'templates_import.php'			=> 17,
	'tree.php'						=> 4,
	'user_admin.php'				=> 1,
	'utilities.php'					=> 15,
	'vdef.php'						=> 14,
	'xaxis.php'				=> 5,
	'layout.php'					=> 7
);

/* sequence of hash_type_codes defines the sequence of import ations
 * on template import */
$hash_type_codes = array(
	'round_robin_archive'		=> '15',
	'cdef'						=> '05',
	'cdef_item'					=> '14',
	'vdef'						=> '18',
	'vdef_item'					=> '19',
	'gprint_preset'				=> '06',
	'xaxis'						=> '16',
	'xaxis_item'				=> '17',
	'data_input_method'			=> '03',
	'data_input_field'			=> '07',
	'data_template'				=> '01',
	'data_template_item'		=> '08',
	'graph_template'			=> '00',
	'graph_template_item'		=> '10',
	'graph_template_input'		=> '09',
	'data_query'				=> '04',
	'data_query_graph'			=> '11',
	'data_query_sv_graph'		=> '12',
	'data_query_sv_data_source'	=> '13',
	'device_template'			=> '02',
);

$hash_version_codes = array(
	'0.8.4'  => '0000',
	'0.8.5'  => '0001',
	'0.8.5a' => '0002',
	'0.8.6'  => '0003',
	'0.8.6a' => '0004',
	'0.8.6b' => '0005',
	'0.8.6c' => '0006',
	'0.8.6d' => '0007',
	'0.8.6e' => '0008',
	'0.8.6f' => '0009',
	'0.8.6g' => '0010',
	'0.8.6h' => '0011',
	'0.8.6i' => '0012',
	'0.8.6j' => '0013',
	'0.8.7'  => '0014',
	'0.8.7a' => '0015',
	'0.8.7b' => '0016',
	'0.8.7c' => '0017',
	'0.8.7d' => '0018',
	'0.8.7e' => '0019',
	'0.8.7f' => '0020',
	'0.8.7g' => '0021',
	'0.8.7h' => '0022',
	"0.8.7i" => "0023",
	"0.8.8"  => "0024",
	"0.8.8a" => "0024",
	"0.8.9"  => "0024",
	'1.0.0'  => '0100'
);

$hash_type_names = array(
	'cdef'					=> 'CDEF',
	'cdef_item'				=> 'CDEF Item',
	'data_input_method'		=> 'Data Input Method',
	'data_input_field'		=> 'Data Input Field',
	'data_query'			=> 'Data Query',
	'data_template'			=> 'Data Source Template',
	'data_template_item'	=> 'Data Source Template Item',
	'device_template'		=> 'Device Template',
	'gprint_preset'			=> 'GPRINT Preset',
	'graph_template'		=> 'Graph Template',
	'graph_template_item'	=> 'Graph Template Item',
	'graph_template_input'	=> 'Graph Template Input',
	'round_robin_archive'	=> 'Round Robin Archive',
	'vdef'					=> 'VDEF',
	'vdef_item'				=> 'VDEF Item',
	'xaxis'					=> 'X-Axis Preset',
	'xaxis_item'			=> 'X-Axis Preset Item',
);

$i18n_months = array(
	'January'	=> __('__January_'),
	'February'	=> __('__February_'),
	'March'		=> __('__March_'),
	'Arpil'		=> __('__April_'),
	'May'		=> __('__May_'),
	'June'		=> __('__June_'),
	'July'		=> __('__July_'),
	'August'	=> __('__August_'),
	'September'	=> __('__September_'),
	'October'	=> __('__October_'),
	'November'	=> __('__November_'),
	'December'	=> __('__December_'),
);

$i18n_months_short = array(
	'Jan'	=> __('_Jan_'),
	'Feb'	=> __('_Feb_'),
	'Mar'	=> __('_Mar_'),
	'Arp'	=> __('_Apr_'),
	'May'	=> __('_May_'),
	'Jun'	=> __('_Jun_'),
	'Jul'	=> __('_Jul_'),
	'Aug'	=> __('_Aug_'),
	'Sep'	=> __('_Sep_'),
	'Oct'	=> __('_Oct_'),
	'Nov'	=> __('_Nov_'),
	'Dec'	=> __('_Dec_'),
);

$i18n_weekdays = array(
	'Sunday'	=> __('Sunday'),
	'Monday'	=> __('Monday'),
	'Tuesday'	=> __('Tuesday'),
	'Wednesday'	=> __('Wednesday'),
	'Thursday'	=> __('Thursday'),
	'Friday'	=> __('Friday'),
	'Saturday'	=> __('Saturday')
);

$i18n_weekdays_short = array(
	'Sun'	=> __('Sun'),
	'Mon'	=> __('Mon'),
	'Tue'	=> __('Tue'),
	'Wed'	=> __('Wed'),
	'Thu'	=> __('Thu'),
	'Fri'	=> __('Fri'),
	'Sat'	=> __('Sat')
);

/* as per http://php.net/manual/en/function.strftime.php */
$datetime_format = array(
	'%m/%d/%Y %I:%M:%S %p'  => __('Default (m/d/Y I:M:S p)'),
    '%m/%d/%Y %H:%M:%S'     => __('24 Hour (m/d/Y H:M:S)'),
    '%Y-%m-%d %H:%M:%S'     => __('24 Hour Alt (Y-m-d H:M:S)'),
    '%Y/%m/%d %H:%M:%S'     => __('24 Hour Alt2 (Y/m/d H:M:S)'),
    '%h %e %H:%M:%S'        => __('Syslog Style (h e H:M:S)')
);
