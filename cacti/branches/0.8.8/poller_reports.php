#!/usr/bin/php -q
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

/* tick use required as of PHP 4.3.0 to accomodate signal handling */
declare(ticks = 1);

/** display_help - generic help screen for utilities
 * @return		 - null */
function display_help () {
	$version = db_fetch_cell("SELECT cacti FROM version");
	print "Reporting Poller Version " . $version . ", " .  COPYRIGHT_YEARS . "\n\n";
	print "usage: poller_reports.php [-d | --debug] [-h | -H | --help] [-v | -V | --version]\n\n";
	print "-f | --force     - Force all Reports to be sent\n";
	print "-d | --debug     - Display verbose output during execution\n";
	print "-v -V --version  - Display this help message\n";
	print "-h -H --help     - display this help message\n";
}


/** sig_handler - provides a generic means to catch exceptions to the Cacti log.
 * @arg $signo 	- (int) the signal that was thrown by the interface.
 * @return 		- null */
function sig_handler($signo) {
	switch ($signo) {
		case SIGTERM:
		case SIGINT:
			reports_log("WARNING: Reports Poller terminated by user", false, "REPORTS TRACE", POLLER_VERBOSITY_LOW);

			exit;
			break;
		default:
			/* ignore all other signals */
	}
}


/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die("<br><strong>This script is only meant to run at the command line.</strong>");
}

/* We are not talking to the browser */
$no_http_headers = TRUE;

$dir = dirname(__FILE__);
chdir($dir);

/* include important functions */
include_once("./include/global.php");
include_once($config["base_path"] . "/lib/poller.php");
include_once($config["base_path"] . "/lib/rrd.php");
include_once($config["base_path"] . "/lib/reports.php");

global $current_user;

/* process calling arguments */
$parms = $_SERVER["argv"];
array_shift($parms);

$debug          = FALSE;
$force          = FALSE;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode("=", $parameter);

	switch ($arg) {
	case "-f":
	case "--force":
		$force = TRUE;
		break;
	case "-d":
	case "--debug":
		$debug = TRUE;
		break;
	case "-v":
	case "--version":
	case "-V":
	case "--help":
	case "-h":
	case "-H":
		display_help();
		exit;
	default:
		print "ERROR: Invalid Parameter " . $parameter . "\n\n";
		display_help();
		exit;
	}
}

/* install signal handlers for UNIX only */
if (function_exists("pcntl_signal")) {
	pcntl_signal(SIGTERM, "sig_handler");
	pcntl_signal(SIGINT, "sig_handler");
}

/* take time and log performance data */
list($micro,$seconds) = explode(" ", microtime());
$start = $seconds + $micro;

/* let's give this script lot of time to run for ever */
ini_set("max_execution_time", "0");

$t = time();
$number_sent = 0;

# fetch all enabled reports that have a stratime in the past
if (!$force) {
	$reports = db_fetch_assoc("SELECT * FROM reports WHERE mailtime<$t AND enabled='on'");
}else{
	$reports = db_fetch_assoc("SELECT * FROM reports WHERE enabled='on'");
}
reports_log("Cacti Reports reports found: " . sizeof($reports), true, "REPORTS", POLLER_VERBOSITY_MEDIUM);

# execute each of those reports
if (sizeof($reports)) {
	foreach ($reports as $report) {
		reports_log("Reports processing report: " . $report["name"], true, "REPORTS", POLLER_VERBOSITY_MEDIUM);
		$current_user = db_fetch_row("SELECT * FROM user_auth WHERE id=" . $report["user_id"]);
		if (isset($report['email'])) {
			generate_report($report, false, "poller");
			$number_sent++;
		}
	}

	/* record the end time */
	list($micro,$seconds) = explode(" ", microtime());
	$end = $seconds + $micro;

	/* log statistics */
	$reports_stats = sprintf("Time:%01.4f Reports:%s", $end - $start, $number_sent);
	reports_log('Reports STATS: ' . $reports_stats, true, "REPORTS", POLLER_VERBOSITY_LOW);
	db_execute("REPLACE INTO settings (name, value) VALUES ('stats_reports', '$reports_stats')");
}

?>
