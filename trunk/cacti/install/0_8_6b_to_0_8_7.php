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

function upgrade_to_0_8_7() {
	// ALTER TABLE `data_input_fields` DROP `sequence`;
	db_install_execute("0.8.7", "ALTER TABLE `host` CHANGE `min_time` `min_time` decimal(9,5) default '99999.99', CHANGE `max_time` `max_time` decimal(9,5) default '0.00000', CHANGE `cur_time` `cur_time` decimal(9,5) default '0.00000', CHANGE `avg_time` `avg_time` decimal(9,5) default '0.00000';");
	db_install_execute("0.8.7", "ALTER TABLE `host` ADD `poller_id` smallint(5) unsigned NOT NULL default '0' AFTER `id`;");
	db_install_execute("0.8.7", "ALTER TABLE `host` ADD `availability_method` smallint(5) unsigned NOT NULL default '1' AFTER `snmp_timeout`;");
	db_install_execute("0.8.7", "ALTER TABLE `host` ADD `ping_method` smallint(5) unsigned default '' AFTER `availability_method`;");
	db_install_execute("0.8.7", "ALTER TABLE `host` CHANGE `snmp_username` `snmpv3_auth_username` varchar(50), CHANGE `snmp_password` `snmpv3_auth_password` varchar(50);");
	db_install_execute("0.8.7", "ALTER TABLE `host` ADD `snmpv3_auth_protocol` varchar(5) AFTER `snmpv3_auth_password`, ADD `snmpv3_priv_passphrase` varchar(200) AFTER `snmpv3_auth_protocol`, ADD `snmpv3_priv_protocol` varchar(5) AFTER `snmpv3_priv_passphrase`;");
	db_install_execute("0.8.7", "ALTER TABLE `data_input` ADD `reserved` tinyint unsigned NOT NULL default '0' AFTER `id`;");
	db_install_execute("0.8.7", "UPDATE data_input set reserved = '1' where hash = '3eb92bb845b9660a7445cf9740726522' or hash = 'bf566c869ac6443b0c75d1c32b5a350e' or hash = '80e9e4c4191a5da189ae26d0e237f015' or hash = '332111d8b54ac8ce939af87a7eac0c06';");
	db_install_execute("0.8.7", "ALTER TABLE `poller` ADD `active` varchar(5) default 'On' AFTER `id`;");
	db_install_execute("0.8.7", "ALTER TABLE `poller` CHANGE `ip_address` `name` varchar(150);");
	db_install_execute("0.8.7", "ALTER TABLE `user_auth` ADD column `enabled` tinyint(1) unsigned NOT NULL default '1';");
	db_install_execute("0.8.7", "ALTER TABLE `poller_item` ADD `availability_method` smallint(5) unsigned NOT NULL default '1' AFTER `snmp_timeout`;");
	db_install_execute("0.8.7", "ALTER TABLE `poller_item` ADD `ping_method` smallint(5) unsigned default '' AFTER `availability_method`;");
	db_install_execute("0.8.7", "ALTER TABLE `poller_item` CHANGE `snmp_username` `snmpv3_auth_username` varchar(50), CHANGE `snmp_password` `snmpv3_auth_password` varchar(50);");
	db_install_execute("0.8.7", "ALTER TABLE `poller_item` ADD `snmpv3_auth_protocol` varchar(5) AFTER `snmpv3_auth_password`, ADD `snmpv3_priv_passphrase` varchar(200) AFTER `snmpv3_auth_protocol`, ADD `snmpv3_priv_protocol` varchar(5) AFTER `snmpv3_priv_passphrase`;");
	db_install_execute("0.8.7", "ALTER TABLE `user_auth` ADD `password_expire_length` int(4) unsigned NOT NULL default '0', ADD `password_change_last` datetime NOT NULL;");
}
?>
