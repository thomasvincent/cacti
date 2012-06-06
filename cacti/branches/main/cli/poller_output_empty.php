#!/usr/bin/php -q
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

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die("<br><strong>This script is only meant to run at the command line.</strong>");
}

/* we are not talking to the browser */
$no_http_headers = true;

/* start initialization section */
include(dirname(__FILE__) . "/../include/global.php");
include_once(CACTI_LIBRARY_PATH . "/poller.php");
include_once(CACTI_LIBRARY_PATH . "/data_query.php");
include_once(CACTI_LIBRARY_PATH . "/graph_export.php");
include_once(CACTI_LIBRARY_PATH . "/rrd.php");

/* process calling arguments */
$parms = $_SERVER["argv"];
$me = array_shift($parms);

foreach($parms as $parameter) {
	@list($arg, $value) = @explode("=", $parameter);

	switch ($arg) {
	case "-h":
		display_help($me);
		exit;
	case "-v":
		display_help($me);
		exit;
	case "--version":
		display_help($me);
		exit;
	case "--help":
		display_help($me);
		exit;
	default:
		printf(__("ERROR: Invalid Parameter %s\n\n"), $parameter);
		display_help($me);
		exit;
	}
}

/* record the start time */
list($micro,$seconds) = explode(" ", microtime());
$start = $seconds + $micro;

/* open a pipe to rrdtool for writing */
$rrdtool_pipe = rrd_init();

$rrds_processed = 0;

while (db_fetch_cell("SELECT count(*) FROM poller_output") > 0) {
	$rrds_processed = $rrds_processed + process_poller_output($rrdtool_pipe, FALSE);
}

printf(__("There were %d rrds_processed, RRD updates made this pass\n"), $rrds_processed);

rrd_close($rrdtool_pipe);

/*	display_help - displays the usage of the function */
function display_help($me) {
	echo "Cacti Empty Poller Output Table Script 1.0" . ", " . __("Copyright 2004-2011 - The Cacti Group") . "\n";
	echo __("usage: ") . $me . " [-h] [--help] [-v] [--version]\n\n";
	echo "   -v --version  " . __("Display this help message") . "\n";
	echo "   -h --help     " . __("Display this help message") . "\n";
}
