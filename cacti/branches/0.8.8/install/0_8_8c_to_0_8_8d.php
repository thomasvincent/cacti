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

function upgrade_to_0_8_8d() {
	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `user_auth_group` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`name` varchar(20) NOT NULL,
		`description` varchar(255) NOT NULL default '',
		`graph_settings` varchar(2) DEFAULT NULL,
		`login_opts` tinyint(1) NOT NULL DEFAULT '1',
		`show_tree` varchar(2) DEFAULT 'on',
		`show_list` varchar(2) DEFAULT 'on',
		`show_preview` varchar(2) NOT NULL DEFAULT 'on',
		`policy_graphs` tinyint(1) unsigned NOT NULL DEFAULT '1',
		`policy_trees` tinyint(1) unsigned NOT NULL DEFAULT '1',
		`policy_hosts` tinyint(1) unsigned NOT NULL DEFAULT '1',
		`policy_graph_templates` tinyint(1) unsigned NOT NULL DEFAULT '1',
		`enabled` char(2) NOT NULL DEFAULT 'on',
		PRIMARY KEY (`id`))
		ENGINE=MyISAM
		COMMENT='Table that Contains User Groups';");

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `user_auth_group_perms` (
		`group_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
		`item_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
		`type` tinyint(2) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`group_id`,`item_id`,`type`),
		KEY `group_id` (`group_id`,`type`))
		ENGINE=MyISAM
		COMMENT='Table that Contains User Group Permissions';");

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `user_auth_group_realm` (
		`group_id` int(10) unsigned NOT NULL,
		`realm_id` int(10) unsigned NOT NULL,
		PRIMARY KEY  (`group_id`, `realm_id`),
		KEY `group_id` (`group_id`),
		KEY `realm_id` (`realm_id`))
		ENGINE=MyISAM
		COMMENT='Table that Contains User Group Realm Permissions';");

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `user_auth_group_members` (
		`group_id` int(10) unsigned NOT NULL,
		`user_id` int(10) unsigned NOT NULL,
		PRIMARY KEY  (`group_id`, `user_id`),
		KEY `group_id` (`group_id`),
		KEY `realm_id` (`user_id`))
		ENGINE=MyISAM
		COMMENT='Table that Contains User Group Members';");

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `settings_graphs_group` (
		`group_id` smallint(8) unsigned NOT NULL DEFAULT '0',
		`name` varchar(50) NOT NULL DEFAULT '',
		`value` varchar(255) NOT NULL DEFAULT '',
		PRIMARY KEY (`group_id`,`name`))
		ENGINE=MyISAM
		COMMENT='Stores the Default User Group Graph Settings';");

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `data_source_stats_daily` (
		`local_data_id` mediumint(8) unsigned NOT NULL,
		`rrd_name` varchar(19) NOT NULL,
		`average` DOUBLE DEFAULT NULL,
		`peak` DOUBLE DEFAULT NULL,
		PRIMARY KEY  (`local_data_id`,`rrd_name`)
		) ENGINE=MyISAM;");

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `data_source_stats_hourly` (
		`local_data_id` mediumint(8) unsigned NOT NULL,
		`rrd_name` varchar(19) NOT NULL,
		`average` DOUBLE DEFAULT NULL,
		`peak` DOUBLE DEFAULT NULL,
		PRIMARY KEY  (`local_data_id`,`rrd_name`)
		) ENGINE=MyISAM;");

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `data_source_stats_hourly_cache` (
		`local_data_id` mediumint(8) unsigned NOT NULL,
		`rrd_name` varchar(19) NOT NULL,
		`time` timestamp NOT NULL default '0000-00-00 00:00:00',
		`value` DOUBLE DEFAULT NULL,
		PRIMARY KEY  (`local_data_id`,`time`,`rrd_name`),
		KEY `time` USING BTREE (`time`)
		) ENGINE=MEMORY;");

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `data_source_stats_hourly_last` (
		`local_data_id` mediumint(8) unsigned NOT NULL,
		`rrd_name` varchar(19) NOT NULL,
		`value` DOUBLE DEFAULT NULL,
		`calculated` DOUBLE DEFAULT NULL,
		PRIMARY KEY  (`local_data_id`,`rrd_name`)
		) ENGINE=MEMORY;");

	if (!sizeof(db_fetch_row("SHOW COLUMNS from data_source_stats_hourly_last where Field='calculated'"))) {
		db_install_execute('0.8.8d', "ALTER TABLE data_source_stats_hourly_last ADD calculated DOUBLE DEFAULT NULL AFTER `value`");
	};

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `data_source_stats_monthly` (
		`local_data_id` mediumint(8) unsigned NOT NULL,
		`rrd_name` varchar(19) NOT NULL,
		`average` DOUBLE DEFAULT NULL,
		`peak` DOUBLE DEFAULT NULL,
		PRIMARY KEY  (`local_data_id`,`rrd_name`)
		) ENGINE=MyISAM;"
	);

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `data_source_stats_weekly` (
		`local_data_id` mediumint(8) unsigned NOT NULL,
		`rrd_name` varchar(19) NOT NULL,
		`average` DOUBLE DEFAULT NULL,
		`peak` DOUBLE DEFAULT NULL,
		PRIMARY KEY  (`local_data_id`,`rrd_name`)
		) ENGINE=MyISAM;"
	);

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `data_source_stats_yearly` (
		`local_data_id` mediumint(8) unsigned NOT NULL,
		`rrd_name` varchar(19) NOT NULL,
		`average` DOUBLE DEFAULT NULL,
		`peak` DOUBLE DEFAULT NULL,
		PRIMARY KEY  (`local_data_id`,`rrd_name`)
		) ENGINE=MyISAM;"
	);

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `poller_output_boost` (
		`local_data_id` mediumint(8) unsigned NOT NULL default '0',
		`rrd_name` varchar(19) NOT NULL default '',
		`time` datetime NOT NULL default '0000-00-00 00:00:00',
		`output` varchar(512) NOT NULL,
		PRIMARY KEY USING BTREE (`local_data_id`,`time`,`rrd_name`))
		ENGINE=MEMORY;");

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `poller_output_boost_processes` (
		`sock_int_value` bigint(20) unsigned NOT NULL auto_increment,
		`status` varchar(255) default NULL,
		PRIMARY KEY (`sock_int_value`))
		ENGINE=MEMORY;");

	if (db_table_exists('plugin_domains')) {
		db_install_execute('0.8.8d', 'RENAME TABLE plugin_domains TO user_domains');
	}

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `user_domains` (
		`domain_id` int(10) unsigned NOT NULL auto_increment,
		`domain_name` varchar(20) NOT NULL,
		`type` int(10) UNSIGNED NOT NULL DEFAULT '0',
		`enabled` char(2) NOT NULL DEFAULT 'on',
		`defdomain` tinyint(3) NOT NULL DEFAULT '0',
		`user_id` int(10) unsigned NOT NULL default '0',
		PRIMARY KEY  (`domain_id`))
		ENGINE=MyISAM
		COMMENT='Table to Hold Login Domains';");

	if (db_table_exists('plugin_domains_ldsp')) {
		db_install_execute('0.8.8d', 'RENAME TABLE plugin_domains_ldsp TO user_domains_ldap');
	}

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `user_domains_ldap` (
		`domain_id` int(10) unsigned NOT NULL,
		`server` varchar(128) NOT NULL,
		`port` int(10) unsigned NOT NULL,
		`port_ssl` int(10) unsigned NOT NULL,
		`proto_version` tinyint(3) unsigned NOT NULL,
		`encryption` tinyint(3) unsigned NOT NULL,
		`referrals` tinyint(3) unsigned NOT NULL,
		`mode` tinyint(3) unsigned NOT NULL,
		`dn` varchar(128) NOT NULL,
		`group_require` char(2) NOT NULL,
		`group_dn` varchar(128) NOT NULL,
		`group_attrib` varchar(128) NOT NULL,
		`group_member_type` tinyint(3) unsigned NOT NULL,
		`search_base` varchar(128) NOT NULL,
		`search_filter` varchar(128) NOT NULL,
		`specific_dn` varchar(128) NOT NULL,
		`specific_password` varchar(128) NOT NULL,
		PRIMARY KEY  (`domain_id`))
		ENGINE=MyISAM
		COMMENT='Table to Hold Login Domains for LDAP';");
	if (db_table_exists('plugin_snmpagent_cache')) {
		db_install_execute('0.8.8d', 'RENAME TABLE plugin_snmpagent_cache TO snmpagent_cache');
	}

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `snmpagent_cache` (
		`oid` varchar(255) NOT NULL,
		`name` varchar(255) NOT NULL,
		`mib` varchar(255) NOT NULL,
		`type` varchar(255) NOT NULL DEFAULT '',
		`otype` varchar(255) NOT NULL DEFAULT '',
		`kind` varchar(255) NOT NULL DEFAULT '',
		`max-access` varchar(255) NOT NULL DEFAULT 'not-accessible',
		`value` varchar(255) NOT NULL DEFAULT '',
		`description` varchar(5000) NOT NULL DEFAULT '',
		PRIMARY KEY (`oid`),
		KEY `name` (`name`),
		KEY `mib` (`mib`))
		ENGINE=MyISAM
		COMMENT='SNMP MIB CACHE';");

	if (db_table_exists('plugin_snmpagent_mibs')) {
		db_install_execute('0.8.8d', 'RENAME TABLE plugin_snmpagent_mibs TO snmpagent_mibs');
	}

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `snmpagent_mibs` (
		`id` int(8) NOT NULL AUTO_INCREMENT,
		`name` varchar(32) NOT NULL DEFAULT '',
		`file` varchar(255) NOT NULL DEFAULT '',
		PRIMARY KEY (`id`))
		ENGINE=MyISAM
		COMMENT='Registered MIB files';");

	if (db_table_exists('plugin_snmpagent_cache_notifications')) {
		db_install_execute('0.8.8d', 'RENAME TABLE plugin_snmpagent_cache_notifications TO snmpagent_cache_notifications');
	}
	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `snmpagent_cache_notifications` (
		`name` varchar(255) NOT NULL,
		`mib` varchar(255) NOT NULL,
		`attribute` varchar(255) NOT NULL,
		`sequence_id` smallint(6) NOT NULL,
		KEY `name` (`name`))
		ENGINE=MyISAM
		COMMENT='Notifcations and related attributes';");

	if (db_table_exists('plugin_snmpagent_cache_textual_conventions')) {
		db_install_execute('0.8.8d', 'RENAME TABLE plugin_snmpagent_cache_textual_conventions TO snmpagent_cache_textual_conventions');
	}

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `snmpagent_cache_textual_conventions` (
		`name` varchar(255) NOT NULL,
		`mib` varchar(255) NOT NULL,
		`type` varchar(255) NOT NULL DEFAULT '',
		`description` varchar(5000) NOT NULL DEFAULT '',
		KEY `name` (`name`),
		KEY `mib` (`mib`))
		ENGINE=MyISAM
		COMMENT='Textual conventions';");

	if (db_table_exists('plugin_snmpagent_managers')) {
		db_install_execute('0.8.8d', 'RENAME TABLE plugin_snmpagent_managers TO snmpagent_managers');
	}

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `snmpagent_managers` (
		`id` int(8) NOT NULL AUTO_INCREMENT,
		`hostname` varchar(255) NOT NULL,
		`description` varchar(255) NOT NULL,
		`disabled` char(2) DEFAULT NULL,
		`max_log_size` tinyint(1) NOT NULL,
		`snmp_version` varchar(255) NOT NULL,
		`snmp_community` varchar(255) NOT NULL,
		`snmp_username` varchar(255) NOT NULL,
		`snmp_auth_password` varchar(255) NOT NULL,
		`snmp_auth_protocol` varchar(255) NOT NULL,
		`snmp_priv_password` varchar(255) NOT NULL,
		`snmp_priv_protocol` varchar(255) NOT NULL,
		`snmp_port` varchar(255) NOT NULL,
		`snmp_message_type` tinyint(1) NOT NULL,
		`notes` text,
		PRIMARY KEY (`id`),
		KEY `hostname` (`hostname`))
		ENGINE=MyISAM
		COMMENT='snmp notification receivers';");

	if (db_table_exists('plugin_snmpagent_managers_notifications')) {
		db_install_execute('0.8.8d', 'RENAME TABLE plugin_snmpagent_managers_notifications TO snmpagent_managers_notifications');
	}

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `snmpagent_managers_notifications` (
		`manager_id` int(8) NOT NULL,
		`notification` varchar(255) NOT NULL,
		`mib` varchar(255) NOT NULL,
		KEY `mib` (`mib`),
		KEY `manager_id` (`manager_id`),
		KEY `manager_id2` (`manager_id`,`notification`))
		ENGINE=MyISAM
		COMMENT='snmp notifications to receivers';");

	if (db_table_exists('plugin_snmpagent_notifications_log')) {
		db_install_execute('0.8.8d', 'RENAME TABLE plugin_snmpagent_notifications_log TO snmpagent_notifications_log');
	}

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `snmpagent_notifications_log` (
		`id` int(12) NOT NULL AUTO_INCREMENT,
		`time` int(24) NOT NULL,
		`severity` tinyint(1) NOT NULL,
		`manager_id` int(8) NOT NULL,
		`notification` varchar(255) NOT NULL,
		`mib` varchar(255) NOT NULL,
		`varbinds` varchar(5000) NOT NULL,
		PRIMARY KEY (`id`),
		KEY `time` (`time`),
		KEY `severity` (`severity`),
		KEY `manager_id` (`manager_id`),
		KEY `manager_id2` (`manager_id`,`notification`))
		ENGINE=MyISAM
		COMMENT='logs snmp notifications to receivers';");
		
	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `data_source_purge_temp` (
		`id` integer UNSIGNED auto_increment,
		`name_cache` varchar(255) NOT NULL default '',
		`local_data_id` mediumint(8) unsigned NOT NULL default '0',
		`name` varchar(128) NOT NULL default '',
		`size` integer UNSIGNED NOT NULL default '0',
		`last_mod` TIMESTAMP NOT NULL default '0000-00-00 00:00:00',
		`in_cacti` tinyint NOT NULL default '0',
		`data_template_id` mediumint(8) unsigned NOT NULL default '0',
		PRIMARY KEY (`id`),
		UNIQUE KEY name (`name`), 
		KEY local_data_id (`local_data_id`), 
		KEY in_cacti (`in_cacti`), 
		KEY data_template_id (`data_template_id`)) 
		ENGINE=MyISAM 
		COMMENT='RRD Cleaner File Repository';");

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `data_source_purge_action` (
		`id` integer UNSIGNED auto_increment,
		`name` varchar(128) NOT NULL default '',
		`local_data_id` mediumint(8) unsigned NOT NULL default '0',
		`action` tinyint(2) NOT NULL default 0,
		PRIMARY KEY (`id`),
		UNIQUE KEY name (`name`))
		ENGINE=MyISAM 
		COMMENT='RRD Cleaner File Actions';");

	db_install_execute('0.8.8d', "ALTER TABLE graph_tree 
		ADD COLUMN enabled char(2) DEFAULT 'on' AFTER id,
		ADD COLUMN locked TINYINT default '0' AFTER enabled, 
		ADD COLUMN locked_date TIMESTAMP default '0000-00-00' AFTER locked, 
		ADD COLUMN last_modified TIMESTAMP default '0000-00-00' AFTER name, 
		ADD COLUMN user_id INT UNSIGNED default '1' AFTER name, 
		ADD COLUMN modified_by INT UNSIGNED default '1'");

	db_install_execute('0.8.8d', "ALTER TABLE graph_tree_items 
		MODIFY COLUMN id BIGINT UNSIGNED NOT NULL auto_increment, 
		ADD COLUMN parent BIGINT UNSIGNED default NULL AFTER id, 
		ADD COLUMN position int UNSIGNED default NULL AFTER parent,
		ADD INDEX parent (parent)");

	db_install_execute('0.8.8d', "CREATE TABLE IF NOT EXISTS `user_auth_cache` (
		`user_id` int(10) unsigned NOT NULL DEFAULT '0',
		`hostname` varchar(64) NOT NULL DEFAULT '',
		`last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`token` varchar(1024) NOT NULL DEFAULT '') 
		ENGINE=MyISAM 
		COMMENT='Caches Remember Me Details'");

	// Add secpass fields
	db_add_column ('0.8.8d', 'user_auth', array('name' => 'lastchange', 'type' => 'int(12)', 'NULL' => false, 'default' => '-1'));
	db_add_column ('0.8.8d', 'user_auth', array('name' => 'lastlogin', 'type' => 'int(12)', 'NULL' => false, 'default' => '-1'));
	db_add_column ('0.8.8d', 'user_auth', array('name' => 'password_history', 'type' => 'text', 'NULL' => false, 'default' => ''));
	db_add_column ('0.8.8d', 'user_auth', array('name' => 'locked', 'type' => 'varchar(3)', 'NULL' => false, 'default' => ''));
	db_add_column ('0.8.8d', 'user_auth', array('name' => 'failed_attempts', 'type' => 'int(5)', 'NULL' => false, 'default' => '0'));
	db_add_column ('0.8.8d', 'user_auth', array('name' => 'lastfail', 'type' => 'int(12)', 'NULL' => false, 'default' => '0'));

	// Convert all trees to new format, but never run more than once
	$columns = array_rekey(db_fetch_assoc("SHOW COLUMNS FROM graph_tree_items"), "Field", array("Type", "Null", "Key", "Default", "Extra"));

	if (isset($columns['order_key'])) {
		define('CHARS_PER_TIER', 3);

		$trees = db_fetch_assoc("SELECT id FROM graph_tree ORDER BY id");

		if (sizeof($trees)) {
		foreach($trees as $t) {
			$tree_items = db_fetch_assoc("SELECT * 
				FROM graph_tree_items 
				WHERE graph_tree_id=" . $t['id'] . " 
				AND order_key NOT LIKE '___000%' 
				ORDER BY order_key");

			/* reset the position variable in case we run more than once */
			db_execute("UPDATE graph_tree_items SET position=0 WHERE graph_tree_id=" . $t['id']);

			$prev_parent = 0;
			$prev_id     = 0;
			$position    = 0;

			if (sizeof($tree_items)) {
				foreach($tree_items AS $item) {
					$translated_key = rtrim($item["order_key"], "0\r\n");
					$missing_len    = strlen($translated_key) % CHARS_PER_TIER;
					if ($missing_len > 0) {
						$translated_key .= substr("000", 0, $missing_len);
					}
					$parent_key_len = strlen($translated_key) - CHARS_PER_TIER;
					$parent_key     = substr($translated_key, 0, $parent_key_len);
					$parent_id      = db_fetch_cell("SELECT id FROM graph_tree_items WHERE graph_tree_id=" . $item["graph_tree_id"] . " AND order_key LIKE '" . $parent_key . "000%'");
	
					if (!empty($parent_id)) {
						/* get order */
						if ($parent_id != $prev_parent) {
							$position = 0;
						}

						$position = db_fetch_cell("SELECT MAX(position) 
							FROM graph_tree_items 
							WHERE graph_tree_id=" . $item['graph_tree_id'] . " 
							AND parent=" . $parent_id) + 1;

						db_execute("UPDATE graph_tree_items SET parent=$parent_id, position=$position WHERE id=" . $item["id"]);
					}else{
						db_execute("UPDATE graph_tree_items SET parent=0, position=$position WHERE id=" . $item["id"]);
					}

					$prev_parent = $parent_id;
				}
			}

			/* get base tree items and set position */
			$tree_items = db_fetch_assoc("SELECT * 
				FROM graph_tree_items
				WHERE graph_tree_id=" . $t['id'] . " 
				AND order_key LIKE '___000%' 
				ORDER BY order_key");

			$position = 0;
			if (sizeof($tree_items)) {
				foreach($tree_items as $item) {
					db_execute("UPDATE graph_tree_items SET parent=0, position=$position WHERE id=" . $item['id']);
					$position++;
				}
			}
		}
		}

		db_install_execute('0.8.8d', "ALTER TABLE graph_tree_items DROP COLUMN order_key");
	}

	/* merge of clog */
	/* clog user = 19 */
	/* dlog admin = 18 */
	$realms = db_fetch_assoc("SELECT * FROM plugin_realms WHERE plugins='clog'");
	if (sizeof($realms)) {
	foreach($realms as $r) {
		if ($r['file'] == 'clog.php') {
			db_execute("UPDATE user_auth_realm SET realm_id=18 WHERE realm_id=" . ($r['id']+100));
		}elseif ($r['file'] == 'clog_user.php') {
			db_execute("UPDATE user_auth_realm SET realm_id=19 WHERE realm_id=" . ($r['id']+100));
		}
	}
	}

	db_install_execute('0.8.8d', "DELETE FROM plugin_realms WHERE file LIKE 'clog%'");
	db_install_execute('0.8.8d', "DELETE FROM plugin_config WHERE directory='clog'");
	db_install_execute('0.8.8d', "DELETE FROM plugin_hooks WHERE name='clog'");

	snmpagent_cache_install();

	// Adding email column for future user
	db_add_column ('0.8.8d', 'user_auth', array('name' => 'email_address', 'type' => 'varchar(128)', 'NULL' => true, 'after' => 'full_name'));

	db_install_execute('0.8.8d', 'DROP TABLE IF EXISTS poller_output_realtime');
	db_install_execute('0.8.8d', "CREATE TABLE poller_output_realtime (
		local_data_id mediumint(8) unsigned NOT NULL default '0',
		rrd_name varchar(19) NOT NULL default '',
		time timestamp NOT NULL default '0000-00-00 00:00:00',
		output text NOT NULL,
		poller_id varchar(30) NOT NULL default '',
		PRIMARY KEY  (local_data_id,rrd_name,`time`),
		KEY poller_id(poller_id)) 
		ENGINE=MyISAM");

	db_install_execute('0.8.8d', 'DROP TABLE IF EXISTS poller_output_rt');

	db_install_execute('0.8.8d', "DELETE FROM plugin_realms WHERE file LIKE '%graph_image_rt%'");
	db_install_execute('0.8.8d', "DELETE FROM plugin_config WHERE directory='realtime'");
	db_install_execute('0.8.8d', "DELETE FROM plugin_hooks WHERE name='realtime'");

	// If we have never install Nectar before, we can simply install
	if (!sizeof(db_fetch_row("SHOW TABLES LIKE '%plugin_nectar%'"))) {
		db_install_execute('0.8.8d', "CREATE TABLE `reports` (
			`id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
			`user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
			`name` varchar(100) NOT NULL DEFAULT '',
			`cformat` char(2) NOT NULL DEFAULT '',
			`format_file` varchar(255) NOT NULL DEFAULT '',
			`font_size` smallint(2) unsigned NOT NULL DEFAULT '0',
			`alignment` smallint(2) unsigned NOT NULL DEFAULT '0',
			`graph_linked` char(2) NOT NULL DEFAULT '',
			`intrvl` smallint(2) unsigned NOT NULL DEFAULT '0',
			`count` smallint(2) unsigned NOT NULL DEFAULT '0',
			`offset` int(12) unsigned NOT NULL DEFAULT '0',
			`mailtime` bigint(20) unsigned NOT NULL DEFAULT '0',
			`subject` varchar(64) NOT NULL DEFAULT '',
			`from_name` varchar(40) NOT NULL,
			`from_email` text NOT NULL,
			`email` text NOT NULL,
			`bcc` text NOT NULL,
			`attachment_type` smallint(2) unsigned NOT NULL DEFAULT '1',
			`graph_height` smallint(2) unsigned NOT NULL DEFAULT '0',
			`graph_width` smallint(2) unsigned NOT NULL DEFAULT '0',
			`graph_columns` smallint(2) unsigned NOT NULL DEFAULT '0',
			`thumbnails` char(2) NOT NULL DEFAULT '',
			`lastsent` bigint(20) unsigned NOT NULL DEFAULT '0',
			`enabled` char(2) DEFAULT '',
			PRIMARY KEY (`id`),
			KEY `mailtime` (`mailtime`)) 
			ENGINE=MyISAM 
			COMMENT='Cacri Reporting Reports'");
	
		db_install_execute('0.8.8d', "CREATE TABLE `reports_items` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`report_id` int(10) unsigned NOT NULL DEFAULT '0',
			`item_type` tinyint(1) unsigned NOT NULL DEFAULT '1',
			`tree_id` int(10) unsigned NOT NULL DEFAULT '0',
			`branch_id` int(10) unsigned NOT NULL DEFAULT '0',
			`tree_cascade` char(2) NOT NULL DEFAULT '',
			`graph_name_regexp` varchar(128) NOT NULL DEFAULT '',
			`host_template_id` int(10) unsigned NOT NULL DEFAULT '0',
			`host_id` int(10) unsigned NOT NULL DEFAULT '0',
			`graph_template_id` int(10) unsigned NOT NULL DEFAULT '0',
			`local_graph_id` int(10) unsigned NOT NULL DEFAULT '0',
			`timespan` int(10) unsigned NOT NULL DEFAULT '0',
			`align` tinyint(1) unsigned NOT NULL DEFAULT '1',
			`item_text` text NOT NULL,
			`font_size` smallint(2) unsigned NOT NULL DEFAULT '10',
			`sequence` smallint(5) unsigned NOT NULL DEFAULT '0',
			PRIMARY KEY (`id`),
			KEY `report_id` (`report_id`)) 
			ENGINE=MyISAM 
			COMMENT='Cacti Reporting Items'");
	}else{
		db_install_execute('0.8.8d', 'RENAME TABLE plugin_nectar TO reports');
		db_install_execute('0.8.8d', 'RENAME TABLE plugin_nectar_items TO reports_items');
		db_install_execute('0.8.8d', "UPDATE settings SET name=REPLACE(name, 'nectar','reports') WHERE name LIKE '%nectar%'");

		db_add_column ('0.8.8d', 'reports', array('name' => 'bcc',           'type' => 'TEXT', 'after' => 'email'));
		db_add_column ('0.8.8d', 'reports', array('name' => 'from_name',     'type' => 'VARCHAR(40)',  'NULL' => false, 'default' => '', 'after' => 'mailtime'));
		db_add_column ('0.8.8d', 'reports', array('name' => 'user_id',       'type' => 'mediumint(8)', 'unsigned' => true, 'NULL' => false, 'default' => '0', 'after' => 'id'));
		db_add_column ('0.8.8d', 'reports', array('name' => 'graph_width',   'type' => 'smallint(2)',  'unsigned' => true, 'NULL' => false, 'default' => '0', 'after' => 'attachment_type'));
		db_add_column ('0.8.8d', 'reports', array('name' => 'graph_height',  'type' => 'smallint(2)',  'unsigned' => true, 'NULL' => false, 'default' => '0', 'after' => 'graph_width'));
		db_add_column ('0.8.8d', 'reports', array('name' => 'graph_columns', 'type' => 'smallint(2)',  'unsigned' => true, 'NULL' => false, 'default' => '0', 'after' => 'graph_height'));
		db_add_column ('0.8.8d', 'reports', array('name' => 'thumbnails',    'type' => 'char(2)',      'NULL' => false, 'default' => '', 'after' => 'graph_columns'));
		db_add_column ('0.8.8d', 'reports', array('name' => 'font_size',     'type' => 'smallint(2)',  'NULL' => false, 'default' => '16', 'after' => 'name'));
		db_add_column ('0.8.8d', 'reports', array('name' => 'alignment',     'type' => 'smallint(2)',  'NULL' => false, 'default' => '0', 'after' => 'font_size'));
		db_add_column ('0.8.8d', 'reports', array('name' => 'cformat',       'type' => 'char(2)',      'NULL' => false, 'default' => '', 'after' => 'name'));
		db_add_column ('0.8.8d', 'reports', array('name' => 'format_file',   'type' => 'varchar(255)', 'NULL' => false, 'default' => '', 'after' => 'cformat'));
		db_add_column ('0.8.8d', 'reports', array('name' => 'graph_linked',  'type' => 'char(2)',      'NULL' => false, 'default' => '', 'after' => 'alignment'));
		db_add_column ('0.8.8d', 'reports', array('name' => 'subject',       'type' => 'varchar(64)',  'NULL' => false, 'default' => '', 'after' => 'mailtime'));

		/* plugin_reports_items upgrade */
		db_add_column ('0.8.8d', 'reports_items', array('name' => 'host_template_id',  'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0', 'after' => 'item_type'));
		db_add_column ('0.8.8d', 'reports_items', array('name' => 'graph_template_id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0', 'after' => 'host_id'));
		db_add_column ('0.8.8d', 'reports_items', array('name' => 'tree_id',           'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0', 'after' => 'item_type'));
		db_add_column ('0.8.8d', 'reports_items', array('name' => 'branch_id',         'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0', 'after' => 'tree_id'));
		db_add_column ('0.8.8d', 'reports_items', array('name' => 'tree_cascade',      'type' => 'char(2)', 'NULL' => false, 'default' => '', 'after' => 'branch_id'));
		db_add_column ('0.8.8d', 'reports_items', array('name' => 'graph_name_regexp', 'type' => 'varchar(128)', 'NULL' => false, 'default' => '', 'after' => 'tree_cascade'));


		/* fix host templates and graph template ids */
		$items = db_fetch_assoc("SELECT * FROM reports_items WHERE item_type=1");
		if (sizeof($items)) {
		foreach ($items as $row) {
			$host = db_fetch_row("SELECT host.* 
				FROM graph_local 
				LEFT JOIN host 
				ON (graph_local.host_id=host.id) 
				WHERE graph_local.id=" . $row["local_graph_id"]);

			$graph_template = db_fetch_cell("SELECT graph_template_id 
				FROM graph_local 
				WHERE id=" . $row["local_graph_id"]);

			db_execute("UPDATE reports_items SET " .
					" host_id='" . $host["id"] . "', " .
					" host_template_id='" . $host["host_template_id"] . "', " .
					" graph_template_id='" . $graph_template . "' " .
					" WHERE id=" . $row["id"]);
		}
		}
	}

	db_add_column ('0.8.8d', 'host', array('name' => 'snmp_sysDescr',          'type' => 'varchar(300)', 'NULL' => false, 'default' => '',  'after' => 'snmp_timeout'));
	db_add_column ('0.8.8d', 'host', array('name' => 'snmp_sysObjectID',       'type' => 'varchar(64)',  'NULL' => false, 'default' => '',  'after' => 'snmp_sysDescr'));
	db_add_column ('0.8.8d', 'host', array('name' => 'snmp_sysUpTimeInstance', 'type' => 'int',          'NULL' => false, 'default' => '0', 'after' => 'snmp_sysObjectID', 'unsigned' => true));
	db_add_column ('0.8.8d', 'host', array('name' => 'snmp_sysContact',        'type' => 'varchar(300)', 'NULL' => false, 'default' => '',  'after' => 'snmp_sysUpTimeInstance'));
	db_add_column ('0.8.8d', 'host', array('name' => 'snmp_sysName',           'type' => 'varchar(300)', 'NULL' => false, 'default' => '',  'after' => 'snmp_sysContact'));
	db_add_column ('0.8.8d', 'host', array('name' => 'snmp_sysLocation',       'type' => 'varchar(300)', 'NULL' => false, 'default' => '',  'after' => 'snmp_sysName'));
	db_add_column ('0.8.8d', 'host', array('name' => 'polling_time',           'type' => 'DOUBLE',                        'default' => '0', 'after' => 'avg_time'));

}
