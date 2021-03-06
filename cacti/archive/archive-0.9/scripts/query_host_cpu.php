<?php

$no_http_headers = true;
include(dirname(__FILE__) . "/../include/global.php");
include(dirname(__FILE__) . "/../lib/snmp.php");

$oids = array(
	"index" => ".1.3.6.1.2.1.25.3.3.1",
	"usage" => ".1.3.6.1.2.1.25.3.3.1"
	);

$hostname = $_SERVER["argv"][1];
$snmp_community = $_SERVER["argv"][2];
$snmp_version = $_SERVER["argv"][3];
$snmpv3_auth_username = $_SERVER["argv"][4];
$snmpv3_auth_password = $_SERVER["argv"][5];
$snmpv3_auth_protocol = $_SERVER["argv"][6];
$snmpv3_priv_passphrase = $_SERVER["argv"][7];
$snmpv3_priv_protocol = $_SERVER["argv"][8];
$cmd = $_SERVER["argv"][9];

if ($cmd == "index") {
	$arr_index = get_indexes($hostname, $snmp_community, $snmp_version);

	for ($i=0;($i<sizeof($arr_index));$i++) {
		print $arr_index[$i] . "\n";
	}
}elseif ($cmd == "query") {
	$arg = $_SERVER["argv"][10];

	$arr_index = get_indexes($hostname, $snmp_community, $snmp_version, $snmpv3_auth_username, $snmpv3_auth_password, $snmpv3_auth_protocol, $snmpv3_priv_passphrase, $snmpv3_priv_protocol);
	$arr = get_cpu_usage($hostname, $snmp_community, $snmp_version, $snmpv3_auth_username, $snmpv3_auth_password, $snmpv3_auth_protocol, $snmpv3_priv_passphrase, $snmpv3_priv_protocol);

	for ($i=0;($i<sizeof($arr_index));$i++) {
		if ($arg == "usage") {
			print $arr_index[$i] . "!" . $arr[$i] . "\n";
		}elseif ($arg == "index") {
			print $arr_index[$i] . "!" . $arr_index[$i] . "\n";
		}
	}
}elseif ($cmd == "get") {
	$arg = $_SERVER["argv"][10];
	$index = $_SERVER["argv"][11];

	$arr_index = get_indexes($hostname, $snmp_community, $snmp_version, $snmpv3_auth_username, $snmpv3_auth_password, $snmpv3_auth_protocol, $snmpv3_priv_passphrase, $snmpv3_priv_protocol);
	$arr = get_cpu_usage($hostname, $snmp_community, $snmp_version, $snmpv3_auth_username, $snmpv3_auth_password, $snmpv3_auth_protocol, $snmpv3_priv_passphrase, $snmpv3_priv_protocol);

	if (isset($arr_index[$index])) {
		print $arr[$index];
	}
}

function get_cpu_usage($hostname, $snmp_community, $snmp_version, $snmpv3_auth_username, $snmpv3_auth_password, $snmpv3_auth_protocol, $snmpv3_priv_passphrase, $snmpv3_priv_protocol) {
	$arr = reindex(cacti_snmp_walk($hostname, $snmp_community, ".1.3.6.1.2.1.25.3.3.1", $snmp_version, $snmpv3_auth_username, $snmpv3_auth_password, $snmpv3_auth_protocol, $snmpv3_priv_passphrase, $snmpv3_priv_protocol, 161, 1000));
	$return_arr = array();

	$j = 0;

	for ($i=0;($i<sizeof($arr));$i++) {
		if (is_numeric($arr[$i])) {
			$return_arr[$j] = $arr[$i];
			$j++;
		}
	}

	return $return_arr;
}

function get_indexes($hostname, $snmp_community, $snmp_version, $snmpv3_auth_username, $snmpv3_auth_password, $snmpv3_auth_protocol, $snmpv3_priv_passphrase, $snmpv3_priv_protocol) {
	$arr = reindex(cacti_snmp_walk($hostname, $snmp_community, ".1.3.6.1.2.1.25.3.3.1", $snmp_version, $snmpv3_auth_username, $snmpv3_auth_password, $snmpv3_auth_protocol, $snmpv3_priv_passphrase, $snmpv3_priv_protocol, 161, 1000));
	$return_arr = array();

	$j = 0;

	for ($i=0;($i<sizeof($arr));$i++) {
		if (is_numeric($arr[$i])) {
			$return_arr[$j] = $j;
			$j++;
		}
	}

	return $return_arr;
}

function reindex($arr) {
	$return_arr = array();

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]["value"];
	}

	return $return_arr;
}

?>
