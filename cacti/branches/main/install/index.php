<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2010 The Cacti Group                                 |
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

include("../include/global.php");

/* allow the upgrade script to run for as long as it needs to */
ini_set("max_execution_time", "0");

/* let's not repopulate the poller cache unless we have to */
$repopulate = false;

/* verify all required php extensions */
if (!verify_php_extensions()) {exit;}

$cacti_versions = array("0.8", "0.8.1", "0.8.2", "0.8.2a", "0.8.3", "0.8.3a", "0.8.4", "0.8.5", "0.8.5a", "0.8.6", "0.8.6a", "0.8.6b", "0.8.6c", "0.8.6d", "0.8.6e", "0.8.6f", "0.8.6g", "0.8.6h", "0.8.6i", "0.8.6j", "0.8.6k", "0.8.7", "0.8.7a", "0.8.7b", "0.8.7c", "0.8.7d", "0.8.7e", "0.8.8");

if(!$database_empty) {
	$old_cacti_version = db_fetch_cell("select cacti from version");
} else {
	$old_cacti_version = "";
}

/* try to find current (old) version in the array */
$old_version_index = array_search($old_cacti_version, $cacti_versions);

/* do a version check */
if ($old_cacti_version == CACTI_VERSION) {
	print "	<p style='font-family: Verdana, Arial; font-size: 16px; font-weight: bold; color: red;'>Error</p>
		<p style='font-family: Verdana, Arial; font-size: 12px;'>This installation is already up-to-date. Click <a href='../index.php'>here</a> to use cacti.</p>";
	exit;
}elseif (preg_match("/^0\.6/", $old_cacti_version)) {
	print "	<p style='font-family: Verdana, Arial; font-size: 16px; font-weight: bold; color: red;'>Error</p>
		<p style='font-family: Verdana, Arial; font-size: 12px;'>You are attempting to install cacti " . CACTI_VERSION . "
		onto a 0.6.x database. To continue, you must create a new database, import 'cacti.sql' into it, and
		update 'include/config.php' to point to the new database.</p>";
	exit;
}elseif (empty($old_cacti_version)) {
	print "	<p style='font-family: Verdana, Arial; font-size: 16px; font-weight: bold; color: red;'>Error</p>
		<p style='font-family: Verdana, Arial; font-size: 12px;'>You have created a new database, but have not yet imported
		the 'cacti.sql' file. At the command line, execute the following to continue:</p>
		<p><pre>mysql -u $database_username -p $database_default < cacti.sql</pre></p>
		<p>This error may also be generated if the cacti database user does not have correct permissions on the cacti database.
		Please ensure that the cacti database user has the ability to SELECT, INSERT, DELETE, UPDATE, CREATE, ALTER, DROP, INDEX
		on the cacti database.</p>";
	exit;
}

function verify_php_extensions() {
	$extensions = array("session", "sockets", "mysql", "xml", "pcre");
	$ok = true;
	$missing_extension = "	<p style='font-family: Verdana, Arial; font-size: 16px; font-weight: bold; color: red;'>Error</p>
							<p style='font-family: Verdana, Arial; font-size: 12px;'>The following PHP extensions are missing:</p><ul>";
	foreach ($extensions as $extension) {
		if (!extension_loaded($extension)){
			$ok = false;
			$missing_extension .= "<li style='font-family: Verdana, Arial; font-size: 12px;'>$extension</li>";
		}
	}
	if (!$ok) {
		print $missing_extension . "</ul><p style='font-family: Verdana, Arial; font-size: 12px;'>Please install those PHP extensions and retry</p>";
	}
	return $ok;
}

function db_install_execute($cacti_version, $sql) {
	global $cnn_id;

	$sql_install_cache = (isset($_SESSION["sess_sql_install_cache"]) ? $_SESSION["sess_sql_install_cache"] : array());

	if (db_execute($sql)) {
		$sql_install_cache{sizeof($sql_install_cache)}[$cacti_version][TRUE] = $sql;
	}else{
		$sql_install_cache{sizeof($sql_install_cache)}[$cacti_version][FALSE] = array($sql, $cnn_id->ErrorMsg());
//		$sql_install_cache{sizeof($sql_install_cache)}[$cacti_version][2] = $cnn_id->ErrorMsg();
	}

	$_SESSION["sess_sql_install_cache"] = $sql_install_cache;
}

function find_best_path($binary_name) {
	global $config;
	if (CACTI_SERVER_OS == "win32") {
		$pgf = getenv("ProgramFiles");
		$pgf64 = getenv("ProgramW6432");

		if (strlen($pgf64)) {
			$search_paths[] = $pgf64 . "/php";
			$search_paths[] = $pgf64 . "/rrdtool";
			$search_paths[] = $pgf64 . "/net-snmp/bin";
		}
		$search_paths[] = $pgf . "/php";
		$search_paths[] = $pgf . "/rrdtool";
		$search_paths[] = $pgf . "/net-snmp/bin";
		$search_paths[] = "c:/php";
		$search_paths[] = "c:/cacti";
		$search_paths[] = "c:/spine";
		$search_paths[] = "c:/usr/bin";
		$search_paths[] = "c:/usr/net-snmp/bin";
		$search_paths[] = "c:/rrdtool";
		$search_paths[] = "d:/php";
		$search_paths[] = "d:/cacti";
		$search_paths[] = "d:/spine";
		$search_paths[] = "d:/usr/bin";
		$search_paths[] = "d:/usr/net-snmp/bin";
		$search_paths[] = "d:/rrdtool";

		//$search_paths = array("c:/usr/bin", "c:/cacti", "c:/rrdtool", "c:/spine", "c:/php",
		//	"c:/progra~1/php", "c:/progra~2/php", "c:/net-snmp/bin", "c:/progra~1/net-snmp/bin",
		//	"c:/progra~2/net-snmp/bin", "d:/usr/bin", "d:/net-snmp/bin",
		//	"d:/progra~1/net-snmp/bin", "d:/cacti", "d:/rrdtool",
		//	"d:/spine", "d:/php", "d:/progra~1/php");
	}else{
		$search_paths = array("/bin", "/sbin", "/usr/bin", "/usr/sbin", "/usr/local/bin", "/usr/local/sbin");
	}

	for ($i=0; $i<count($search_paths); $i++) {
		if (CACTI_SERVER_OS == "win32") {
			$path = dosPath($search_paths[$i]);
		}else{
			$path = $search_paths[$i];
		}

		if ((file_exists($path . "/" . $binary_name)) && (is_readable($path . "/" . $binary_name))) {

			return $path . "/" . $binary_name;
		}
	}
}

/* Here, we define each name, default value, type, and path check for each value
we want the user to input. The "name" field must exist in the 'settings' table for
this to work. Cacti also uses different default values depending on what OS it is
running on. */

/* RRDTool Binary Path */
$input = array();
$input["path_rrdtool"] = $settings["path"]["path_rrdtool"];

if (CACTI_SERVER_OS == "unix") {
	$which_rrdtool = find_best_path("rrdtool");

	if (config_value_exists("path_rrdtool")) {
		$input["path_rrdtool"]["default"] = read_config_option("path_rrdtool");
	}else if (!empty($which_rrdtool)) {
		$input["path_rrdtool"]["default"] = $which_rrdtool;
	}else{
		$input["path_rrdtool"]["default"] = "/usr/local/bin/rrdtool";
	}
}elseif (CACTI_SERVER_OS == "win32") {
	$which_rrdtool = find_best_path("rrdtool.exe");

	if (config_value_exists("path_rrdtool")) {
		$input["path_rrdtool"]["default"] = read_config_option("path_rrdtool");
	}else if (!empty($which_rrdtool)) {
		$input["path_rrdtool"]["default"] = $which_rrdtool;
	}else{
		$input["path_rrdtool"]["default"] = "c:/rrdtool/rrdtool.exe";
	}
}

/* PHP Binary Path */
$input["path_php_binary"] = $settings["path"]["path_php_binary"];

if (CACTI_SERVER_OS == "unix") {
	$which_php = find_best_path("php");

	if (config_value_exists("path_php_binary")) {
		$input["path_php_binary"]["default"] = read_config_option("path_php_binary");
	}else if (!empty($which_php)) {
		$input["path_php_binary"]["default"] = $which_php;
	}else{
		$input["path_php_binary"]["default"] = "/usr/bin/php";
	}
}elseif (CACTI_SERVER_OS == "win32") {
	$which_php = find_best_path("php.exe");

	if (config_value_exists("path_php_binary")) {
		$input["path_php_binary"]["default"] = read_config_option("path_php_binary");
	}else if (!empty($which_php)) {
		$input["path_php_binary"]["default"] = $which_php;
	}else{
		$input["path_php_binary"]["default"] = "c:/php/php.exe";
	}
}

/* snmpwalk Binary Path */
$input["path_snmpwalk"] = $settings["path"]["path_snmpwalk"];

if (CACTI_SERVER_OS == "unix") {
	$which_snmpwalk = find_best_path("snmpwalk");

	if (config_value_exists("path_snmpwalk")) {
		$input["path_snmpwalk"]["default"] = read_config_option("path_snmpwalk");
	}else if (!empty($which_snmpwalk)) {
		$input["path_snmpwalk"]["default"] = $which_snmpwalk;
	}else{
		$input["path_snmpwalk"]["default"] = "/usr/local/bin/snmpwalk";
	}
}elseif (CACTI_SERVER_OS == "win32") {
	$which_snmpwalk = find_best_path("snmpwalk.exe");

	if (config_value_exists("path_snmpwalk")) {
		$input["path_snmpwalk"]["default"] = read_config_option("path_snmpwalk");
	}else if (!empty($which_snmpwalk)) {
		$input["path_snmpwalk"]["default"] = $which_snmpwalk;
	}else{
		$input["path_snmpwalk"]["default"] = "c:/net-snmp/bin/snmpwalk.exe";
	}
}

/* snmpget Binary Path */
$input["path_snmpget"] = $settings["path"]["path_snmpget"];

if (CACTI_SERVER_OS == "unix") {
	$which_snmpget = find_best_path("snmpget");

	if (config_value_exists("path_snmpget")) {
		$input["path_snmpget"]["default"] = read_config_option("path_snmpget");
	}else if (!empty($which_snmpget)) {
		$input["path_snmpget"]["default"] = $which_snmpget;
	}else{
		$input["path_snmpget"]["default"] = "/usr/local/bin/snmpget";
	}
}elseif (CACTI_SERVER_OS == "win32") {
	$which_snmpget = find_best_path("snmpget.exe");

	if (config_value_exists("path_snmpget")) {
		$input["path_snmpget"]["default"] = read_config_option("path_snmpget");
	}else if (!empty($which_snmpget)) {
		$input["path_snmpget"]["default"] = $which_snmpget;
	}else{
		$input["path_snmpget"]["default"] = "c:/net-snmp/bin/snmpget.exe";
	}
}

/* snmpbulkwalk Binary Path */
$input["path_snmpbulkwalk"] = $settings["path"]["path_snmpbulkwalk"];

if (CACTI_SERVER_OS == "unix") {
	$which_snmpbulkwalk = find_best_path("snmpbulkwalk");

	if (config_value_exists("path_snmpbulkwalk")) {
		$input["path_snmpbulkwalk"]["default"] = read_config_option("path_snmpbulkwalk");
	}else if (!empty($which_snmpbulkwalk)) {
		$input["path_snmpbulkwalk"]["default"] = $which_snmpbulkwalk;
	}else{
		$input["path_snmpbulkwalk"]["default"] = "/usr/local/bin/snmpbulkwalk";
	}
}elseif (CACTI_SERVER_OS == "win32") {
	$which_snmpbulkwalk = find_best_path("snmpbulkwalk.exe");

	if (config_value_exists("path_snmpbulkwalk")) {
		$input["path_snmpbulkwalk"]["default"] = read_config_option("path_snmpbulkwalk");
	}else if (!empty($which_snmpbulkwalk)) {
		$input["path_snmpbulkwalk"]["default"] = $which_snmpbulkwalk;
	}else{
		$input["path_snmpbulkwalk"]["default"] = "c:/net-snmp/bin/snmpbulkwalk.exe";
	}
}

/* snmpgetnext Binary Path */
$input["path_snmpgetnext"] = $settings["path"]["path_snmpgetnext"];

if (CACTI_SERVER_OS == "unix") {
	$which_snmpgetnext = find_best_path("snmpgetnext");

	if (config_value_exists("path_snmpgetnext")) {
		$input["path_snmpgetnext"]["default"] = read_config_option("path_snmpgetnext");
	}else if (!empty($which_snmpgetnext)) {
		$input["path_snmpgetnext"]["default"] = $which_snmpgetnext;
	}else{
		$input["path_snmpgetnext"]["default"] = "/usr/local/bin/snmpgetnext";
	}
}elseif (CACTI_SERVER_OS == "win32") {
	$which_snmpgetnext = find_best_path("snmpgetnext.exe");

	if (config_value_exists("path_snmpgetnext")) {
		$input["path_snmpgetnext"]["default"] = read_config_option("path_snmpgetnext");
	}else if (!empty($which_snmpgetnext)) {
		$input["path_snmpgetnext"]["default"] = $which_snmpgetnext;
	}else{
		$input["path_snmpgetnext"]["default"] = "c:/net-snmp/bin/snmpgetnext.exe";
	}
}

/* log file path */
$input["path_cactilog"] = $settings["path"]["path_cactilog"];
$input["path_cactilog"]["description"] = "The path to your Cacti log file.";
if (config_value_exists("path_cactilog")) {
	$input["path_cactilog"]["default"] = read_config_option("path_cactilog");
} else {
	$input["path_cactilog"]["default"] = CACTI_BASE_PATH . "/log/cacti.log";
}

/* spine Binary Path */
$input["path_spine"] = $settings["path"]["path_spine"];

if (CACTI_SERVER_OS == "unix") {
	$which_spine = find_best_path("spine");

	if (config_value_exists("path_spine")) {
		$input["path_spine"]["default"] = read_config_option("path_spine");
	}else if (!empty($which_spine)) {
		$input["path_spine"]["default"] = $which_spine;
	}else{
		$input["path_spine"]["default"] = "/usr/local/bin/spine";
	}
}elseif (CACTI_SERVER_OS == "win32") {
	$which_spine = find_best_path("spine.exe");

	if (config_value_exists("path_spine")) {
		$input["path_spine"]["default"] = read_config_option("path_spine");
	}else if (!empty($which_spine)) {
		$input["path_spine"]["default"] = $which_spine;
	}else{
		$input["path_spine"]["default"] = "c:/spine/spine.exe";
	}
}

/* SNMP Version */
if (CACTI_SERVER_OS == "unix") {
	$input["snmp_version"] = $settings["general"]["snmp_version"];
	$input["snmp_version"]["default"] = "net-snmp";
}

/* RRDTool Version */
if ((file_exists($input["path_rrdtool"]["default"])) && ((CACTI_SERVER_OS == "win32") || (is_executable($input["path_rrdtool"]["default"]))) ) {
	$input["rrdtool_version"] = $settings["general"]["rrdtool_version"];

	$out_array = array();

	exec($input["path_rrdtool"]["default"], $out_array);

	if (sizeof($out_array) > 0) {
		if (preg_match("/^RRDtool 1\.4/", $out_array[0])) {
			$input["rrdtool_version"]["default"] = RRD_VERSION_1_4;
		}else if (preg_match("/^RRDtool 1\.3/", $out_array[0])) {
			$input["rrdtool_version"]["default"] = RRD_VERSION_1_3;
		}else if (preg_match("/^RRDtool 1\.2\./", $out_array[0])) {
			$input["rrdtool_version"]["default"] = RRD_VERSION_1_2;
		}else if (preg_match("/^RRDtool 1\.0\./", $out_array[0])) {
			$input["rrdtool_version"]["default"] = RRD_VERSION_1_0;
		}
	}
}

/* default value for this variable */
if (!isset($_REQUEST["install_type"])) {
	$_REQUEST["install_type"] = 0;
}

/* defaults for the install type dropdown */
if ($old_cacti_version == "new_install") {
	$default_install_type = "1";
}else{
	$default_install_type = "3";
}

/* pre-processing that needs to be done for each step */
if (empty($_REQUEST["step"])) {
	$_REQUEST["step"] = 1;
}else{
	if (get_request_var_request("step") == "1") {
		$_REQUEST["step"] = "2";
	}elseif ((get_request_var_request("step") == "2") && (get_request_var_request("install_type") == "1")) {
		$_REQUEST["step"] = "3";
	}elseif ((get_request_var_request("step") == "2") && (get_request_var_request("install_type") == "3")) {
		$_REQUEST["step"] = "8";
	}elseif ((get_request_var_request("step") == "8") && ($old_version_index <= array_search("0.8.5a", $cacti_versions))) {
		$_REQUEST["step"] = "9";
	}elseif (get_request_var_request("step") == "8") {
		$_REQUEST["step"] = "3";
	}elseif (get_request_var_request("step") == "9") {
		$_REQUEST["step"] = "3";
	}elseif (get_request_var_request("step") == "3") {
		$_REQUEST["step"] = "4";
	}
}

if (get_request_var_request("step") == "4") {
	global $repopulate;

	include_once(CACTI_BASE_PATH . "/lib/data_query.php");
	include_once(CACTI_BASE_PATH . "/lib/utility.php");

	$i = 0;

	/* get all items on the form and write values for them  */
	while (list($name, $array) = each($input)) {
		if (isset($_POST[$name])) {
			db_execute("replace into settings (name,value) values ('$name','" . get_request_var_post($name) . "')");
		}
	}

	setcookie(session_name(),"",time() - 3600,"/");

	kill_session_var("sess_config_array");
	kill_session_var("sess_device_cache_array");

	/* just in case we have hard drive graphs to deal with */
	$device_id = db_fetch_cell("select id from device where hostname='127.0.0.1'");

	if (!empty($device_id)) {
		run_data_query($device_id, 6);
	}

	/* it's not always a good idea to re-populate the poller cache to make sure everything is
	refreshed and up-to-date */
	if ($repopulate) {
		repopulate_poller_cache();
	}

	db_execute("delete from version");
	db_execute("insert into version (cacti) values ('" . CACTI_VERSION . "')");

	header ("Location: ../index.php");
	exit;
}elseif ((get_request_var_request("step") == "8") && (get_request_var_request("install_type") == "3")) {
	/* if the version is not found, die */
	if (!is_int($old_version_index)) {
		print "	<p style='font-family: Verdana, Arial; font-size: 16px; font-weight: bold; color: red;'>Error</p>
			<p style='font-family: Verdana, Arial; font-size: 12px;'>Invalid Cacti version
			<strong>$old_cacti_version</strong>, cannot upgrade to <strong>" . CACTI_VERSION . "
			</strong></p>";
		exit;
	}

	/* loop from the old version to the current, performing updates for each version in between */
	for ($i=($old_version_index+1); $i<count($cacti_versions); $i++) {
		if ($cacti_versions[$i] == "0.8.1") {
			include ("0_8_to_0_8_1.php");
			upgrade_to_0_8_1();
		}elseif ($cacti_versions[$i] == "0.8.2") {
			include ("0_8_1_to_0_8_2.php");
			upgrade_to_0_8_2();
		}elseif ($cacti_versions[$i] == "0.8.2a") {
			include ("0_8_2_to_0_8_2a.php");
			upgrade_to_0_8_2a();
		}elseif ($cacti_versions[$i] == "0.8.3") {
			include ("0_8_2a_to_0_8_3.php");
			include_once("../lib/utility.php");
			upgrade_to_0_8_3();
		}elseif ($cacti_versions[$i] == "0.8.4") {
			include ("0_8_3_to_0_8_4.php");
			upgrade_to_0_8_4();
		}elseif ($cacti_versions[$i] == "0.8.5") {
			include ("0_8_4_to_0_8_5.php");
			upgrade_to_0_8_5();
		}elseif ($cacti_versions[$i] == "0.8.6") {
			include ("0_8_5a_to_0_8_6.php");
			upgrade_to_0_8_6();
		}elseif ($cacti_versions[$i] == "0.8.6a") {
			include ("0_8_6_to_0_8_6a.php");
			upgrade_to_0_8_6a();
		}elseif ($cacti_versions[$i] == "0.8.6d") {
			include ("0_8_6c_to_0_8_6d.php");
			upgrade_to_0_8_6d();
		}elseif ($cacti_versions[$i] == "0.8.6e") {
			include ("0_8_6d_to_0_8_6e.php");
			upgrade_to_0_8_6e();
		}elseif ($cacti_versions[$i] == "0.8.6g") {
			include ("0_8_6f_to_0_8_6g.php");
			upgrade_to_0_8_6g();
		}elseif ($cacti_versions[$i] == "0.8.6h") {
			include ("0_8_6g_to_0_8_6h.php");
			upgrade_to_0_8_6h();
		}elseif ($cacti_versions[$i] == "0.8.6i") {
			include ("0_8_6h_to_0_8_6i.php");
			upgrade_to_0_8_6i();
		}elseif ($cacti_versions[$i] == "0.8.7") {
			include ("0_8_6j_to_0_8_7.php");
			upgrade_to_0_8_7();
		}elseif ($cacti_versions[$i] == "0.8.7a") {
			include ("0_8_7_to_0_8_7a.php");
			upgrade_to_0_8_7a();
		}elseif ($cacti_versions[$i] == "0.8.7b") {
			include ("0_8_7a_to_0_8_7b.php");
			upgrade_to_0_8_7b();
		}elseif ($cacti_versions[$i] == "0.8.7c") {
			include ("0_8_7b_to_0_8_7c.php");
			upgrade_to_0_8_7c();
		}elseif ($cacti_versions[$i] == "0.8.7d") {
			include ("0_8_7c_to_0_8_7d.php");
			upgrade_to_0_8_7d();
		}elseif ($cacti_versions[$i] == "0.8.7e") {
			include ("0_8_7d_to_0_8_7e.php");
			upgrade_to_0_8_7e();
		}elseif ($cacti_versions[$i] == "0.8.8") {
			include ("0_8_7e_to_0_8_8.php");
			upgrade_to_0_8_8();
		}
	}
}

?>
<html>
<head>
	<title>cacti</title>
    <link type="text/css" href="../include/main.css" rel="stylesheet">
</head>

<body>

<form method="post" action="index.php">

<div style='align:center;margin:20px 100px 20px 100px;'>
<table align="center" cellpadding="1" cellspacing="0" border="0">
	<tr>
		<td width="100%">
			<table cellpadding="3" cellspacing="0" border="0" style="border:1px solid #104075;" bgcolor="#F0F0F0" width="100%">
				<tr class="rowHeader">
					<td class="textHeaderDark"><strong>Cacti Installation Guide</strong></td>
				</tr>
				<tr>
					<td width="100%" style="font-size: 12px;">
						<?php if (get_request_var_request("step") == "1") { ?>

						<p>Thanks for taking the time to download and install cacti, the complete graphing
						solution for your network. Before you can start making cool graphs, there are a few
						pieces of data that cacti needs to know.</p>

						<p>Make sure you have read and followed the required steps needed to install cacti
						before continuing. Install information can be found for
						<a href="../docs/html/install_unix.html">Unix</a> and <a href="../docs/html/install_windows.html">Win32</a>-based operating systems.</p>

						<p>Also, if this is an upgrade, be sure to reading the <a href="../docs/html/upgrade.html">Upgrade</a> information file.</p>

						<p>Cacti is licensed under the GNU General Public License, you must agree
						to its provisions before continuing:</p>

						<p class="code">This program is free software; you can redistribute it and/or
						modify it under the terms of the GNU General Public License
						as published by the Free Software Foundation; either version 2
						of the License, or (at your option) any later version.</p>

						<p class="code">This program is distributed in the hope that it will be useful,
						but WITHOUT ANY WARRANTY; without even the implied warranty of
						MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
						GNU General Public License for more details.</p>

						<?php }elseif (get_request_var_request("step") == "2") { ?>

						<p>Please select the type of installation</p>

						<p>
						<select name="install_type">
							<option value="1"<?php print ($default_install_type == "1") ? " selected" : "";?>>New Install</option>
							<option value="3"<?php print ($default_install_type == "3") ? " selected" : "";?>>Upgrade from cacti 0.8.x</option>
						</select>
						</p>

						<p>The following information has been determined from Cacti's configuration file.
						If it is not correct, please edit 'include/config.php' before continuing.</p>

						<p class="code">
						<?php	print "Database User: $database_username<br>";
							print "Database Hostname: $database_hostname<br>";
							print "Database: $database_default<br>";
							print "Server Operating System Type: " . CACTI_SERVER_OS . "<br>"; ?>
						</p>

						<?php }elseif (get_request_var_request("step") == "3") { ?>

						<p>Make sure all of these values are correct before continuing.</p>
						<?php
						$i = 0;
						/* find the appropriate value for each 'config name' above by config.php, database,
						or a default for fall back */
						while (list($name, $array) = each($input)) {
							if (isset($input[$name])) {
								$current_value = $array["default"];

								/* run a check on the path specified only if specified above, then fill a string with
								the results ('FOUND' or 'NOT FOUND') so they can be displayed on the form */
								$form_check_string = "";

								if (($array["method"] == "textbox") ||
									($array["method"] == "filepath")) {
									if (@file_exists($current_value)) {
										$form_check_string = "<font color='#008000'>[FOUND]</font> ";
									}else{
										$form_check_string = "<font color='#FF0000'>[NOT FOUND]</font> ";
									}
								}

								/* draw the acual header and textbox on the form */
								print "<p><strong>" . $form_check_string . $array["friendly_name"] . "</strong>";

								if (!empty($array["friendly_name"])) {
									print ": " . $array["description"];
								}else{
									print "<strong>" . $array["description"] . "</strong>";
								}

								print "<br>";

								switch ($array["method"]) {
								case 'textbox':
									form_text_box($name, $current_value, "", "", "40", "text");
									break;
								case 'filepath':
									form_filepath_box($name, $current_value, "", "", "40", "text");
									break;
								case 'drop_array':
									form_dropdown($name, $array["array"], "", "", $current_value, "", "");
									break;
								}

								print "<br></p>";
							}

							$i++;
						}?>

						<p><strong><font color="#FF0000">NOTE:</font></strong> Once you click "Finish",
						all of your settings will be saved and your database will be upgraded if this
						is an upgrade. You can change any of the settings on this screen at a later
						time by going to "Cacti Settings" from within Cacti.</p>

						<?php }elseif (get_request_var_request("step") == "8") { ?>

						<p>Upgrade results:</p>

						<?php
						$current_version  = "";
						$upgrade_results = "";
						$failed_sql_query = false;

						$fail_text    = "<span class=\"warning\">[Fail]</span>&nbsp;";
						$success_text = "<span class=\"success\">[Success]</span>&nbsp;";
						$fail_message = "<span class=\"warning\">[Message]</span>&nbsp;";

						if (isset($_SESSION["sess_sql_install_cache"])) {
							while (list($index, $arr1) = each($_SESSION["sess_sql_install_cache"])) {
								while (list($version, $arr2) = each($arr1)) {
									while (list($status, $sql) = each($arr2)) {
										if ($current_version != $version) {
											$version_index = array_search($version, $cacti_versions);
											$upgrade_results .= "<p><strong>" . $cacti_versions{$version_index-1}  . " -> " . $cacti_versions{$version_index} . "</strong></p>\n";
										}

										$upgrade_results .= "<p class='code'>" . (($status == FALSE) ? $fail_text . $sql[0] . "<br>" . $fail_message . $sql[1] : $success_text . $sql) . "</p>\n";

										/* if there are one or more failures, make a note because we are going to print
										out a warning to the user later on */
										if ($status == 0) {
											$failed_sql_query = true;
										}

										$current_version = $version;
									}
								}
							}

							kill_session_var("sess_sql_install_cache");
						}else{
							print "<em>No SQL queries have been executed.</em>";
						}

						if ($failed_sql_query == true) {
							print "<p><strong><font color='#FF0000'>WARNING:</font></strong> One or more of the SQL queries needed to
								upgraded your Cacti installation has failed. Please see below for more details. Your
								Cacti MySQL user must have <strong>SELECT, INSERT, UPDATE, DELETE, ALTER, CREATE, and DROP</strong>
								permissions. For each query that failed, you should evaluate the error message returned and take 
								appropriate action.</p>\n";
						}

						print $upgrade_results;
						?>

						<?php }elseif (get_request_var_request("step") == "9") { ?>

						<p style='font-size: 16px; font-weight: bold; color: red;'>Important Upgrade Notice</p>

						<p>Before you continue with the installation, you <strong>must</strong> update your <tt>/etc/crontab</tt> file to point to <tt>poller.php</tt> instead of <tt>cmd.php</tt>.</p>

						<p>See the sample crontab entry below with the change made in red. Your crontab line will look slightly different based upon your setup.</p>

						<p><tt>*/5 * * * * cactiuser php /var/www/html/cacti/<span class="warning">poller.php</span> &gt; /dev/null 2&gt;&amp;1</tt></p>

						<p>Once you have made this change, please click Next to continue.</p>

						<?php }?>

						<p align="right"><input type="submit" value="<?php if (get_request_var_request("step") == "3") {?>Finish<?php }else{?>Next<?php }?>" title="<?php if (get_request_var_request("step") == "3"){?>Finish Install<?php }else{?>Proceed to Next Step<?php }?>"></p>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</div>

<input type="hidden" name="step" value="<?php print $_REQUEST["step"];?>">

</form>

</body>
</html>
