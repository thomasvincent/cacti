<?php
$no_http_headers = true;

/* display No errors */
error_reporting(E_ERROR);

include_once(dirname(__FILE__) . "/../lib/snmp.php");

if (!isset($called_by_script_server)) {
	include_once(dirname(__FILE__) . "/../include/config.php");

	array_shift($_SERVER["argv"]);

	print call_user_func_array("ss_host_disk", $_SERVER["argv"]);
}

function ss_host_disk($hostname, $host_id, $snmp_auth, $cmd, $arg1 = "", $arg2 = "") {
	$snmp = explode(":", $snmp_auth);
	$snmp_version = $snmp[0];
	$snmp_port = $snmp[1];
	$snmp_timeout = $snmp[2];

	$snmpv3_auth_username = "";
	$snmpv3_auth_password = "";
	$snmpv3_auth_protocol = "";
	$snmpv3_priv_passphrase = "";
	$snmpv3_priv_protocol = "";
	$snmp_community = "";

	if ($snmp_version == 3) {
		$snmpv3_auth_username = $snmp[4];
		$snmpv3_auth_password = $snmp[5];
		$snmpv3_auth_protocol = $snmp[6];
		$snmpv3_priv_passphrase = $snmp[7];
		$snmpv3_priv_protocol = $snmp[8];
	}else{
		$snmp_community = $snmp[3];
	}

	$oids = array(
		"total" => ".1.3.6.1.2.1.25.2.3.1.5",
		"used" => ".1.3.6.1.2.1.25.2.3.1.6",
		"failures" => ".1.3.6.1.2.1.25.2.3.1.7",
		"index" => ".1.3.6.1.2.1.25.2.3.1.1",
		"description" => ".1.3.6.1.2.1.25.2.3.1.3",
		"sau" => ".1.3.6.1.2.1.25.2.3.1.4"
		);

	if ($cmd == "index") {
		$return_arr = ss_host_disk_reindex(cacti_snmp_walk($hostname, $snmp_community, $oids["index"], $snmp_version, $snmpv3_auth_username, $snmpv3_auth_password, $snmp_port, $snmp_timeout, SNMP_POLLER));

		for ($i=0;($i<sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	}elseif ($cmd == "query") {
		$arg = $arg1;

		$arr_index = ss_host_disk_reindex(cacti_snmp_walk($hostname, $snmp_community, $oids["index"], $snmp_version, $snmpv3_auth_username, $snmpv3_auth_password, $snmp_port, $snmp_timeout, SNMP_POLLER));
		$arr = ss_host_disk_reindex(cacti_snmp_walk($hostname, $snmp_community, $oids[$arg], $snmp_version, $snmpv3_auth_username, $snmpv3_auth_password, $snmp_port, $snmp_timeout, SNMP_POLLER));

		for ($i=0;($i<sizeof($arr_index));$i++) {
			print $arr_index[$i] . "!" . $arr[$i] . "\n";
		}
	}elseif ($cmd == "get") {
		$arg = $arg1;
		$index = $arg2;

		if (($arg == "total") || ($arg == "used")) {
			/* get hrStorageAllocationUnits from the snmp cache since it is faster */
			$sau = eregi_replace("[^0-9]", "", db_fetch_cell("select field_value from host_snmp_cache where host_id=$host_id and field_name='hrStorageAllocationUnits' and snmp_index='$index'"));

			return cacti_snmp_get($hostname, $snmp_community, $oids[$arg] . ".$index", $snmp_version, $snmpv3_auth_username, $snmpv3_auth_password, $snmp_port, $snmp_timeout, SNMP_POLLER) * $sau;
		}else{
			return cacti_snmp_get($hostname, $snmp_community, $oids[$arg] . ".$index", $snmp_version, $snmpv3_auth_username, $snmpv3_auth_password, $snmp_port, $snmp_timeout, SNMP_POLLER);
		}
	}
}

function ss_host_disk_reindex($arr) {
	$return_arr = array();

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]["value"];
	}

	return $return_arr;
}

?>