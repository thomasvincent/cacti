#!/usr/bin/php -q
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

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0])) {
	die("<br><strong>This script is only meant to run at the command line.</strong>");
}

$start = date("Y-n-d H:i:s"); // for runtime measurement
$poller_update_time = date("Y-m-d H:i:s"); // for poller update time

ini_set("max_execution_time", "0");
ini_set("memory_limit", "32M");

$no_http_headers = true;

include(dirname(__FILE__) . "/include/config.php");
include_once($config["base_path"] . "/lib/snmp.php");
include_once($config["base_path"] . "/lib/poller.php");
include_once($config["base_path"] . "/lib/rrd.php");
include_once($config["base_path"] . "/lib/ping.php");

/* PHP Bug.  Not yet submitted */
if ($config["cacti_server_os"] == "win32") {
	$guess = substr(__FILE__,0,2);
	if ($guess == strtoupper($guess)) {
		$response = "ERROR: The PHP Script: CMD.PHP Must be started using the full path to the file and in lower case.  This is a PHP Bug!!!";
		print "\n";
		cacti_log($response,true);
		exit(-1);
	}
}

/* record start time */
list($micro,$seconds) = split(" ", microtime());
$start = $seconds + $micro;

if ( $_SERVER["argc"] == 1 ) {
	$polling_items = db_fetch_assoc("SELECT * from poller_item ORDER by host_id");
	$print_data_to_stdout = true;
	/* Get number of polling items from the database */
	$hosts = db_fetch_assoc("select * from host where disabled = '' order by id");
	$hosts = array_rekey($hosts,"id",$host_struc);
	$host_count = sizeof($hosts);
	$poller_id = 0;
}else{
	if ($_SERVER["argc"] == "4") {
		$print_data_to_stdout = true;
		$parms = $_SERVER["argv"];
		array_shift($parms);

		foreach($parms as $parameter) {
   	   switch (substr($parameter,0,2)) {
				case "-l":
					$last_host = substr($parameter,3);
					break;
				case "-f":
					$first_host = substr($parameter,3);
					break;
				case "-p":
					$poller_id = substr($parameter,3);
					break;
				default:
					cacti_log("ERROR: Invalid Calling Parameter in CMD.PHP",true,"CMDPHP");
			}
		}

		if ($first_host <= $last_host) {
			$hosts = db_fetch_assoc("select * from host where (disabled = '' and " .
					"id >= " .
					$first_host .
					" AND id <= " .
					$last_host . " AND poller_id = " . $poller_id . ") ORDER by id");
			$hosts = array_rekey($hosts,"id",$host_struc);
			$host_count = sizeof($hosts);

			$polling_items = db_fetch_assoc("SELECT * from poller_item " .
					"WHERE (host_id >= " .
					$first_host .
					" and host_id <= " .
					$last_host . " AND poller_id = " . $poller_id . ") ORDER by host_id");
		}else{
			print "ERROR: Invalid Arguments.  The first argument must be less than or equal to the first.\n";
			print "USAGE: CMD.PHP [-f=first_host -l=last_host -p=poller_id]\n";
			cacti_log("Poller[$poller_id] ERROR: Invalid Arguments.  CMD.PHP calling parameters invalid.",$print_data_to_stdout);
		}
	}else{
		print "ERROR: Invalid Arguments.  The first argument must be less than or equal to the first.\n";
		print "USAGE: CMD.PHP [-f=first_host -l=last_host -p=poller_id]\n";
		cacti_log("Poller[$poller_id] ERROR: Invalid Arguments.  CMD.PHP calling parameters invalid.",$print_data_to_stdout);
	}
}

if ((sizeof($polling_items) > 0) && (read_config_option("poller_enabled") == "on")) {
	$failure_type = "";
	$host_down = false;
	$new_host  = true;
	$last_host = ""; $current_host = "";

	// startup Cacti php polling server and include the include file for script processing
	$cactides = array(
		0 => array("pipe", "r"), // stdin is a pipe that the child will read from
		1 => array("pipe", "w"), // stdout is a pipe that the child will write to
		2 => array("pipe", "w")  // stderr is a pipe to write to
		);

	// create new ping socket for host pinging
	$ping = new Net_Ping;

	if (function_exists("proc_open")) {
		$cactiphp = proc_open(read_config_option("path_php_binary") . " " . $config["base_path"] . "/script_server.php cmd " . $poller_id, $cactides, $pipes);
		$output = fgets($pipes[1], 1024);
		if (substr_count($output, "Started") != 0) {
			if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_HIGH) {
				cacti_log("Poller[$poller_id] PHP Script Server Started Properly",$print_data_to_stdout);
			}
		}
		$using_proc_function = true;

	}else {
		$using_proc_function = false;
		if (read_config_option("log_verbosity") == POLLER_VERBOSITY_DEBUG) {
			cacti_log("Poller[$poller_id] WARNING: PHP version 4.3 or above is recommended for performance considerations.",$print_data_to_stdout);
		}
	}

	foreach ($polling_items as $item) {
		$current_host = $item["hostname"];

		if ($current_host != $last_host) {
			$new_host = true;
			$host_down = false;
		}

		$host_id = $item["host_id"];

		if (($new_host) && (!empty($host_id))) {
			$ping->host["hostname"]       = $item["hostname"];
			$ping->host["snmp_community"] = $item["snmp_community"];
			$ping->host["snmp_version"]   = $item["snmp_version"];
			$ping->host["snmpv3_auth_username"]  = $item["snmpv3_auth_username"];
			$ping->host["snmpv3_auth_password"]  = $item["snmpv3_auth_password"];
			$ping->host["snmpv3_auth_protocol"]  = $item["snmpv3_auth_protocol"];
			$ping->host["snmpv3_priv_passphrase"]  = $item["snmpv3_priv_passphrase"];
			$ping->host["snmpv3_priv_protocol"]  = $item["snmpv3_priv_protocol"];
			$ping->host["snmp_port"]      = $item["snmp_port"];
			$ping->host["snmp_timeout"]   = $item["snmp_timeout"];
			$ping->host["availability_method"] = $item["availability_method"];
			$ping->host["ping_method"] = $item["ping_method"];

			if ((!function_exists("socket_create")) || (phpversion() < "4.3")) {
				/* the ping test will fail under PHP < 4.3 without socket support */
				$ping_availability = AVAIL_SNMP;
			}else{
				$ping_availability = $item["availability_method"];
			}

			/* if we are only allowed to use an snmp check and this host does not support snnp, we
			must assume that this host is up */
			if ((($ping_availability == AVAIL_SNMP) && ($item["snmp_community"] == "")) || ($ping_availability == AVAIL_NONE)) {
				$host_down = false;
				update_host_status($poller_id, HOST_UP, $host_id, $hosts, $ping, $ping_availability, $print_data_to_stdout);

				if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM) {
					cacti_log("Poller[$poller_id] Host[$host_id] Availability Disabled for Host '" . $item["hostname"] . "'.", $print_data_to_stdout);
				}
			}else{
				if ($ping->ping($ping_availability, $item["ping_method"], read_config_option("ping_timeout"), read_config_option("ping_retries"))) {
					$host_down = false;
					update_host_status($poller_id, HOST_UP, $host_id, $hosts, $ping, $ping_availability, $print_data_to_stdout);
				}else{
					$host_down = true;
					update_host_status($poller_id, HOST_DOWN, $host_id, $hosts, $ping, $ping_availability, $print_data_to_stdout);
				}
			}

			if (!$host_down) {
				/* do the reindex check for this host */
				$reindex = db_fetch_assoc("select
					poller_reindex.data_query_id,
					poller_reindex.action,
					poller_reindex.op,
					poller_reindex.assert_value,
					poller_reindex.arg1
					from poller_reindex
					where poller_reindex.host_id=" . $item["host_id"]);

				if ((sizeof($reindex) > 0) && (!$host_down)) {
					if (read_config_option("log_verbosity") == POLLER_VERBOSITY_DEBUG) {
						cacti_log("Poller[$poller_id] Host[$host_id] RECACHE: Processing " . sizeof($reindex) . " items in the auto reindex cache for '" . $item["hostname"] . "'.",$print_data_to_stdout);
					}

					foreach ($reindex as $index_item) {
						$assert_fail = false;

						/* do the check */
						switch ($index_item["action"]) {
						case POLLER_ACTION_SNMP: /* snmp */
							$output = cacti_snmp_get($item["hostname"], $item["snmp_community"], $index_item["arg1"], $item["snmp_version"], $item["snmpv3_auth_username"], $item["snmpv3_auth_password"], $item["snmpv3_auth_protocol"], $item["snmpv3_priv_passphrase"], $item["snmpv3_priv_protocol"], $item["snmp_port"], $item["snmp_timeout"], SNMP_CMDPHP);
							break;
						case POLLER_ACTION_SCRIPT: /* script (popen) */
							$output = exec_poll($index_item["arg1"]);
							break;
						}

						/* assert the result with the expected value in the db; recache if the assert fails */
						if (($index_item["op"] == "=") && ($index_item["assert_value"] != trim($output))) {
							cacti_log("Poller[$poller_id] ASSERT: '" . $index_item["assert_value"] . "=" . trim($output) . "' failed. Recaching host '" . $item["hostname"] . "', data query #" . $index_item["data_query_id"] . ".\n", $print_data_to_stdout);
							db_execute("insert into poller_command (poller_id,time,action,command) values (0,NOW()," . POLLER_COMMAND_REINDEX . ",'" . $item["host_id"] . ":" . $index_item["data_query_id"] . "')");
							$assert_fail = true;
						}else if (($index_item["op"] == ">") && ($index_item["assert_value"] <= trim($output))) {
							cacti_log("Poller[$poller_id] ASSERT: '" . $index_item["assert_value"] . ">" . trim($output) . "' failed. Recaching host '" . $item["hostname"] . "', data query #" . $index_item["data_query_id"] . ".\n", $print_data_to_stdout);
							db_execute("insert into poller_command (poller_id,time,action,command) values (0,NOW()," . POLLER_COMMAND_REINDEX . ",'" . $item["host_id"] . ":" . $index_item["data_query_id"] . "')");
							$assert_fail = true;
						}else if (($index_item["op"] == "<") && ($index_item["assert_value"] >= trim($output))) {
							cacti_log("Poller[$poller_id] ASSERT: '" . $index_item["assert_value"] . "<" . trim($output) . "' failed. Recaching host '" . $item["hostname"] . "', data query #" . $index_item["data_query_id"] . ".\n", $print_data_to_stdout);
							db_execute("insert into poller_command (poller_id,time,action,command) values (0,NOW()," . POLLER_COMMAND_REINDEX . ",'" . $item["host_id"] . ":" . $index_item["data_query_id"] . "')");
							$assert_fail = true;
						}

						/* update 'poller_reindex' with the correct information if:
						 * 1) the assert fails
						 * 2) the OP code is > or < meaning the current value could have changed without causing
						 *     the assert to fail */
						if (($assert_fail == true) || ($index_item["op"] == ">") || ($index_item["op"] == "<")) {
							db_execute("update poller_reindex set assert_value='$output' where host_id='$host_id' and data_query_id='" . $index_item["data_query_id"] . "' and arg1='" . $index_item["arg1"] . "'");
						}
					}
				}
			}

			$new_host = false;
			$last_host = $current_host;
		}

		if (!$host_down) {
			switch ($item["action"]) {
			case POLLER_ACTION_SNMP: /* snmp */
				$output = cacti_snmp_get($item["hostname"], $item["snmp_community"], $item["arg1"], $item["snmp_version"], $item["snmpv3_auth_username"], $item["snmpv3_auth_password"], $item["snmp_port"], $item["snmp_timeout"], SNMP_CMDPHP);

				/* remove any quotes from string */
				$output = strip_quotes($output);

				if (!validate_result($output)) {
					if (strlen($output) > 20) {
						$strout = 20;
					} else {
						$strout = strlen($output);
					}

					cacti_log("Poller[$poller_id] Host[$host_id] WARNING: Result from SNMP not valid.  Partial Result: " . substr($output, 0, $strout), $print_data_to_stdout);
					$output = "U";
				}

				if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM) {
					cacti_log("Poller[$poller_id] Host[$host_id] SNMP: v" . $item["snmp_version"] . ": " . $item["hostname"] . ", dsname: " . $item["rrd_name"] . ", oid: " . $item["arg1"] . ", output: $output",$print_data_to_stdout);
				}

				break;
			case POLLER_ACTION_SCRIPT: /* script (popen) */
				$output = trim(exec_poll($item["arg1"]));

				/* remove any quotes from string */
				$output = strip_quotes($output);

				if (!validate_result($output)) {
					if (strlen($output) > 20) {
						$strout = 20;
					} else {
						$strout = strlen($output);
					}

					cacti_log("Poller[$poller_id] Host[$host_id] WARNING: Result from CMD not valid.  Partial Result: " . substr($output, 0, $strout), $print_data_to_stdout);
					$output = "U";
				}

				if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM) {
					cacti_log("Poller[$poller_id] Host[$host_id] CMD: " . $item["arg1"] . ", output: $output",$print_data_to_stdout);
				}

				break;
			case POLLER_ACTION_SCRIPT_PHP: /* script (php script server) */
				if ($using_proc_function == true) {
					$output = trim(str_replace("\n", "", exec_poll_php($item["arg1"], $using_proc_function, $pipes, $cactiphp)));

					/* remove any quotes from string */
					$output = strip_quotes($output);

					if (!validate_result($output)) {
						if (strlen($output) > 20) {
							$strout = 20;
						} else {
							$strout = strlen($output);
						}

						cacti_log("Poller[$poller_id] Host[$host_id] WARNING: Result from SERVER not valid.  Partial Result: " . substr($output, 0, $strout), $print_data_to_stdout);
						$output = "U";
					}

					if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM) {
						cacti_log("Poller[$poller_id] Host[$host_id] SERVER: " . $item["arg1"] . ", output: $output", $print_data_to_stdout);
					}
				}else{
					if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM) {
						cacti_log("Poller[$poller_id] Host[$host_id] *SKIPPING* SERVER: " . $item["arg1"] . " (PHP < 4.3)", $print_data_to_stdout);
					}

					$output = "U";
				}

				break;
			} /* End Switch */

			if (isset($output)) {
				db_execute("insert into poller_output (local_data_id,rrd_name,time,output) values (" . $item["local_data_id"] . ",'" . $item["rrd_name"] . "','$poller_update_time','" . addslashes($output) . "')");
			}
		} /* Next Cache Item */
	} /* End foreach */

	if ($using_proc_function == true) {
		// close php server process
		fwrite($pipes[0], "quit\r\n");
		fclose($pipes[0]);
		fclose($pipes[1]);
		fclose($pipes[2]);

		$return_value = proc_close($cactiphp);
	}

	if (($print_data_to_stdout) || (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM)) {
		/* take time and log performance data */
		list($micro,$seconds) = split(" ", microtime());
		$end = $seconds + $micro;

		cacti_log(sprintf("Poller[$poller_id] Time: %01.4f s, " .
			"Theads: N/A, " .
			"Hosts: %s",
			round($end-$start,4),
			$host_count),$print_data_to_stdout);
	}

}else{
	cacti_log("Poller[$poller_id] ERROR: Either there are no items in the cache or polling is disabled",$print_data_to_stdout);
}

/* Let the poller server know about cmd.php being finished */
db_execute("insert into poller_time (poller_id, start_time, end_time) values (" . $poller_id . ", NOW(), NOW())");

?>