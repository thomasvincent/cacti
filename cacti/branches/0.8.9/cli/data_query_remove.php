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

/* We are not talking to the browser */
$no_http_headers = true;

include(dirname(__FILE__)."/../include/global.php");
require_once(CACTI_INCLUDE_PATH . "/device/device_constants.php");
include_once(CACTI_LIBRARY_PATH . "/api_automation_tools.php");
include_once(CACTI_LIBRARY_PATH . "/device.php");
#include_once(CACTI_LIBRARY_PATH . "/data_query.php");

/* process calling arguments */
$parms = $_SERVER["argv"];
$me = array_shift($parms);
$debug		= FALSE;	# no debug mode
$quietMode 	= FALSE;	# be verbose by default
$device 	= array();
$dq			= array();
$error		= '';

if (sizeof($parms)) {
	foreach($parms as $parameter) {
		@list($arg, $value) = @explode("=", $parameter);

		switch ($arg) {
			case "-d":
			case "--debug":			$debug 							= TRUE; 		break;
			case "--device-id":		$device["id"] 					= trim($value);	break;
			case "--site-id":		$device["site_id"] 				= trim($value);	break;
			case "--poller-id":		$device["poller_id"]			= trim($value);	break;
			case "--description":	$device["description"] 			= trim($value);	break;
			case "--ip":			$device["hostname"] 			= trim($value);	break;
			case "--template":		$device["host_template_id"]	 	= trim($value);	break;
			case "--community":		$device["snmp_community"] 		= trim($value);	break;
			case "--version":		$device["snmp_version"] 		= trim($value);	break;
			case "--notes":			$device["notes"] 				= trim($value);	break;
			case "--disabled":		$device["disabled"] 			= trim($value);	break;
			case "--username":		$device["snmp_username"] 		= trim($value);	break;
			case "--password":		$device["snmp_password"] 		= trim($value);	break;
			case "--authproto":		$device["snmp_auth_protocol"]	= trim($value);	break;
			case "--privproto":		$device["snmp_priv_protocol"] 	= trim($value);	break;
			case "--privpass":		$device["snmp_priv_passphrase"] = trim($value);	break;
			case "--context":		$device["snmp_context"] 		= trim($value);	break;
			case "--port":			$device["snmp_port"] 			= trim($value);	break;
			case "--timeout":		$device["snmp_timeout"] 		= trim($value);	break;
			case "--avail":			$device["availability_method"] 	= trim($value);	break;
			case "--ping-method":	$device["ping_method"] 			= trim($value);	break;
			case "--ping-port":		$device["ping_port"] 			= trim($value);	break;
			case "--ping-retries":	$device["ping_retries"] 		= trim($value);	break;
			case "--ping-timeout":	$device["ping_timeout"] 		= trim($value);	break;
			case "--max-oids":		$device["max_oids"] 			= trim($value);	break;
			case "--device-threads":$device["device_threads"] 		= trim($value);	break;
			case "--data-query-id":	$dq["snmp_query_id"] 			= trim($value);	break;
			#case "--reindex-method":$dq["reindex_method"] 			= trim($value);	break;
			case "-V":
			case "-H":
			case "--help":
			case "--version":		display_help($me);								exit(0);
			case "--quiet":			$quietMode = TRUE;								break;
			default:				echo "ERROR: Invalid Argument: ($arg)" . "\n\n"; display_help($me); exit(1);
		}
	}


	if (sizeof($device)) {
		# verify the parameters given
		$verify = verifyDevice($device, true);
		if (isset($verify["err_msg"])) {
			print $verify["err_msg"] . "\n\n";
			display_help($me);
			exit(1);
		}
	}

	if (sizeof($dq)) {
		# verify the parameters given
		$verify = verifyDataQuery($dq, true);
		if (isset($verify["err_msg"])) {
			print $verify["err_msg"] . "\n\n";
			display_help($me);
			exit(1);
		}
	}

	if (!isset($dq["snmp_query_id"])) {
		print "ERROR: No matching Data Query found\n";
		print "Try php -q data_query_list.php" . "\n";
		exit(1);
	}

	/* get devices matching criteria */
	$devices = getDevices($device);

	if (!sizeof($devices)) {
		echo "ERROR: No matching Devices found" . "\n";
		echo "Try php -q device_list.php" . "\n";
		exit(1);
	}

	if (isset($dq["snmp_query_id"])) {
		foreach ($devices as $device) {
			device_dq_remove($device["id"], $dq["snmp_query_id"]);
			echo "Data Query (" . $dq["snmp_query_id"] .") removed from Device (" . $device["id"] . ": " . $device["hostname"] .")" . "\n";
		}
	}
}else{
	display_help($me);
	exit(1);
}

function display_help($me) {
	echo "Remove Data Query Script 1.0" . ", " . "Copyright 2004-2011 - The Cacti Group" . "\n";
	echo "A simple command line utility to remove data queries from devices in Cacti" . "\n\n";
	echo "usage: " . $me . " [--data-query-id=] [--device-id=] [--site-id=] [--poller-id=]\n";
	echo "       [--description=] [--ip=] [--template=] [--notes=\"[]\"] [--disabled]\n";
	echo "       [--avail=[pingsnmp]] [--ping-method=[tcp] --ping-port=[N/A, 1-65534]] --ping-retries=[2] --ping-timeout=[500]\n";
	echo "       [--version=1] [--community=] [--port=161] [--timeout=500]\n";
	echo "       [--username= --password=] [--authproto=] [--privpass= --privproto=] [--context=]\n";
	echo "       [--quiet] [-d]\n\n";
	echo "Optional:" . "\n";
	echo "   --data-query-id  " . "the numerical ID of the data_query to be listed" . "\n";
	#echo "   --reindex-method " . "the reindex method to be used for that data query" . "\n";
	#echo "          0|none  " . "no reindexing" . "\n";
	#echo "          1|uptime" . "Uptime goes Backwards" . "\n";
	#echo "          2|index " . "Index Count Changed" . "\n";
	#echo "          3|fields" . "Verify all Fields" . "\n";
	#echo "          4|value " . "Re-Index Value Changed" . "\n";
	echo "   --device-id                 " . "the numerical ID of the device" . "\n";
	echo "   --site-id                   " . "the numerical ID of the site" . "\n";
	echo "   --poller-id                 " . "the numerical ID of the poller" . "\n";
	echo "   --description               " . "the name that will be displayed by Cacti in the graphs" . "\n";
	echo "   --ip                        " . "self explanatory (can also be a FQDN)" . "\n";
	echo "   --template                  " . "denotes the device template to be used" . "\n";
	echo "                               " . "In case a device template is given, all values are fetched from this one." . "\n";
	echo "                               " . "For a device template=0 (NONE), Cacti default settings are used." . "\n";
	echo "                               " . "Optionally overwrite by any of the following:" . "\n";
	echo "   --notes                     " . "General information about this device. Must be enclosed using double quotes." . "\n";
	echo "   --disable                   " . "to add this device but to disable checks and 0 to enable it" . " [0|1]\n";
	echo "   --avail                     " . "device availability check" . " [ping][none, snmp, pingsnmp]\n";
	echo "     --ping-method             " . "if ping selected" . " [icmp|tcp|udp]\n";
	echo "     --ping-port               " . "port used for tcp|udp pings" . " [1-65534]\n";
	echo "     --ping-retries            " . "the number of time to attempt to communicate with a device" . "\n";
	echo "     --ping-timeout            " . "ping timeout" . "\n";
	echo "   --version                   " . "snmp version" . " [1|2|3]\n";
	echo "   --community                 " . "snmp community string for snmpv1 and snmpv2. Leave blank for no community" . "\n";
	echo "   --port                      " . "snmp port" . "\n";
	echo "   --timeout                   " . "snmp timeout" . "\n";
	echo "   --username                  " . "snmp username for snmpv3" . "\n";
	echo "   --password                  " . "snmp password for snmpv3" . "\n";
	echo "   --authproto                 " . "snmp authentication protocol for snmpv3" . " [".SNMP_AUTH_PROTOCOL_MD5."|".SNMP_AUTH_PROTOCOL_SHA."]\n";
	echo "   --privpass                  " . "snmp privacy passphrase for snmpv3" . "\n";
	echo "   --privproto                 " . "snmp privacy protocol for snmpv3" . " [".SNMP_PRIV_PROTOCOL_DES."|".SNMP_PRIV_PROTOCOL_AES128."]\n";
	echo "   --context                   " . "snmp context for snmpv3" . "\n";
	echo "   --max-oids                  " . "the number of OID's that can be obtained in a single SNMP Get request" . " [1-60]\n";
	#echo "   -d                          " . "Debug Mode, no updates made, but printing the SQL for updates" . "\n";
	echo "   --quiet                     " . "batch mode value return" . "\n\n";
	echo "Examples:" . "\n";
	echo "   php -q " . $me . "  --data-query-id=3 --device-id=5\n";
	echo "   " . "  removes data query id 3 from device id 5" . "\n";
	echo "   php -q " . $me . "  --data-query-id=3 --template=8\n";
	echo "   " . "  same as above, but performed for all devices associated with device template id 8" . "\n";
}
