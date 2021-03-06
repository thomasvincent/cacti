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

include ("./include/auth.php");
include_once(CACTI_LIBRARY_PATH . "/utility.php");
include_once(CACTI_LIBRARY_PATH . "/graph.php");
include_once(CACTI_LIBRARY_PATH . "/data_source.php");
include_once(CACTI_LIBRARY_PATH . "/template.php");
include_once(CACTI_LIBRARY_PATH . "/html_form_template.php");
include_once(CACTI_LIBRARY_PATH . "/rrd.php");
include_once(CACTI_LIBRARY_PATH . "/data_query.php");

define("MAX_DISPLAY_PAGES", 21);

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

switch (get_request_var_request("action")) {
	case 'save':
		data_source_form_save();

		break;
	case 'actions':
		data_source_form_actions();

		break;
	case 'rrd_add':
		data_source_rrd_add();

		break;
	case 'item_remove':
		data_source_rrd_remove();

		break;
	case 'data_source_data_edit':
		include_once(CACTI_INCLUDE_PATH . "/top_header.php");

		data_source_data_edit();

		include_once(CACTI_INCLUDE_PATH . "/bottom_footer.php");
		break;
	case 'edit':
		data_source_edit();

		break;
	case 'data_source_toggle_status':
		data_source_toggle_status();

		break;
	case 'ajaxlist':
		data_source();

		break;
	case 'ajax_view':
		data_source();

		break;
	default:
		include_once(CACTI_INCLUDE_PATH . "/top_header.php");

		data_source();

		include_once(CACTI_INCLUDE_PATH . "/bottom_footer.php");
		break;
}

