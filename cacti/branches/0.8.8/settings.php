<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2014 The Cacti Group                                 |
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

include('./include/auth.php');

/* set default action */
if (!isset($_REQUEST['action'])) { $_REQUEST['action'] = ''; }

switch ($_REQUEST['action']) {
case 'save':
	while (list($field_name, $field_array) = each($settings{$_POST['tab']})) {
		if (($field_array['method'] == 'header') || ($field_array['method'] == 'spacer' )){
			/* do nothing */
		}elseif ($field_array['method'] == 'checkbox') {
			if (isset($_POST[$field_name])) {
				db_execute_prepared("REPLACE INTO settings (name, value) VALUES (?, 'on')", array($field_name));
			}else{
				db_execute_prepared("REPLACE INTO settings (name, value) VALUES (?, '')", array($field_name));
			}
		}elseif ($field_array['method'] == 'checkbox_group') {
			while (list($sub_field_name, $sub_field_array) = each($field_array['items'])) {
				if (isset($_POST[$sub_field_name])) {
					db_execute_prepared("REPLACE INTO settings (name, value) VALUES (?, 'on')", array($sub_field_name));
				}else{
					db_execute_prepared("REPLACE INTO settings (name, value) VALUES (?, '')", array($sub_field_name));
				}
			}
		}elseif ($field_array['method'] == 'textbox_password') {
			if ($_POST[$field_name] != $_POST[$field_name.'_confirm']) {
				raise_message(4);
				break;
			}elseif (isset($_POST[$field_name])) {
				db_execute_prepared('REPLACE INTO settings (name, value) VALUES (?, ?)', array($field_name, get_request_var_post($field_name)));
			}
		}elseif ((isset($field_array['items'])) && (is_array($field_array['items']))) {
			while (list($sub_field_name, $sub_field_array) = each($field_array['items'])) {
				if (isset($_POST[$sub_field_name])) {
					db_execute_prepared('REPLACE INTO settings (name, value) VALUES (?, ?)', array($sub_field_name, get_request_var_post($sub_field_name)));
				}
			}
		}elseif (isset($_POST[$field_name])) {
			db_execute_prepared('REPLACE INTO settings (name, value) VALUES (?, ?)', array($field_name, get_request_var_post($field_name)));
		}
	}
	api_plugin_hook_function('global_settings_update');
	raise_message(1);

	/* reset local settings cache so the user sees the new settings */
	kill_session_var('sess_config_array');

	if (isset($_REQUEST['header']) && $_REQUEST['header'] == 'false') {
		header('Location: settings.php?header=false&tab=' . $_POST['tab']);
	}else{
		header('Location: settings.php?tab=' . $_POST['tab']);
	}

	break;
case 'send_test':
	email_test();
	break;
default:
	top_header();

	/* set the default settings category */
	if (!isset($_GET['tab'])) {
		/* there is no selected tab; select the first one */
		if (isset($_SESSION['sess_settings_tab'])) {
			$current_tab = $_SESSION['sess_settings_tab'];
		}else{
			$current_tab = array_keys($tabs);
			$current_tab = $current_tab[0];
		}
	}else{
		$current_tab = $_GET['tab'];
	}
	$_SESSION['sess_settings_tab'] = $current_tab;

	/* draw the categories tabs on the top of the page */
	print "<table cellpadding='0' cellspacing='0' border='0'><tr><td>\n";
	print "<div class='tabs' style='float:left;'><nav><ul>\n";

	if (sizeof($tabs) > 0) {
	foreach (array_keys($tabs) as $tab_short_name) {
		print "<li class='subTab'><a " . (($tab_short_name == $current_tab) ? "class='selected'" : "class=''") . " href='" . htmlspecialchars("settings.php?tab=$tab_short_name") . "'>$tabs[$tab_short_name]</a></li>\n";
	}
	}

	print "</ul></nav></div>\n";
	print "</tr></table><table width='100%' cellpadding='0' cellspacing='0' border='0'><tr><td>\n";

	html_start_box('<strong>Cacti Settings (' . $tabs[$current_tab] . ')</strong>', '100%', '', '3', 'center', '');

	$form_array = array();

	while (list($field_name, $field_array) = each($settings[$current_tab])) {
		$form_array += array($field_name => $field_array);

		if ((isset($field_array['items'])) && (is_array($field_array['items']))) {
			while (list($sub_field_name, $sub_field_array) = each($field_array['items'])) {
				if (config_value_exists($sub_field_name)) {
					$form_array[$field_name]['items'][$sub_field_name]['form_id'] = 1;
				}

				$form_array[$field_name]['items'][$sub_field_name]['value'] = db_fetch_cell_prepared('SELECT value FROM settings WHERE name = ?', array($sub_field_name));
			}
		}else{
			if (config_value_exists($field_name)) {
				$form_array[$field_name]['form_id'] = 1;
			}

			$form_array[$field_name]['value'] = db_fetch_cell_prepared('SELECT value FROM settings WHERE name = ?', array($field_name));
		}
	}

	draw_edit_form(
		array(
			'config' => array(),
			'fields' => $form_array)
			);

	html_end_box();

	form_hidden_box('tab', $current_tab, '');

	form_save_button('', 'save');

	print "</td></tr></table>\n";

	?>
	<script type='text/javascript'>

	var themeChanged = false;
	var currentTheme = '';

	$(function() {
		$('.subTab').find('a').click(function(event) {
			event.preventDefault();
			href = $(this).attr('href');
			href = href+ (href.indexOf('?') > 0 ? '&':'?') + 'header=false';
			$.get(href, function(data) {
				$('#main').html(data);
				applySkin();
			});
		});

		$('input[value='Save']').click(function(event) {
			event.preventDefault();
			if (themeChanged != true) {
				$.post('settings.php?tab='+$('#tab').val()+'&header=false', $('input, select, textarea').serialize()).done(function(data) {
					$('#main').html(data);
					applySkin();
				});
			}else{
				$.post('settings.php?tab='+$('#tab').val()+'&header=false', $('input, select, textarea').serialize()).done(function(data) {
					document.location = 'settings.php?tab='+$('#tab').val();
				});
			}
		});

		if ($('#row_settings_email_header')) {
			$('#emailtest').click(function() {
				var $div = $('<div />').appendTo('body');
				$div.attr('id', 'testmail');
				$('#testmail').prop('title', 'Test E-Mail Results');
				$('#testmail').dialog({
					autoOpen: false,
					modal: true,
					minHeight: 300,
					maxHeight: 600,
					height: 450,
					width: 500,
					show: {
						effect: 'appear',
						duration: 100
					},
					hide: {
						effect: 'appear',
						duratin: 100
					}
				});
				$.get('settings.php?action=send_test', function(data) {
					$('#testmail').html(data);
					$('#testmail').dialog('open');
				});
			});
		}
	});

	if ($('#row_font_method')) {
		currentTheme = $('#selected_theme').val();

		initFonts();

		$('#font_method').change(function() {
			initFonts();
		});

		$('#selected_theme').change(function() {
			themeChanger();
		});
	}

	if ($('#row_snmp_ver')) {
		initSNMP();
		$('#snmp_ver').change(function() {
			initSNMP();
		});
	}

	if ($('#row_availability_method')) {
		initAvail();
		$('#availability_method').change(function() {
			initAvail();
		});
	}

	if ($('#row_export_type')) {
		initFTPExport();
		initPresentation();
		initTiming();
		$('#export_type').change(function() {
			initFTPExport();
		});
		$('#export_presentation').change(function() {
			initPresentation();
		});
		$('#export_timing').change(function() {
			initTiming();
		});
	}

	if ($('#row_auth_method')) {
		initAuth();
		initSearch();
		initGroupMember();
		$('#auth_method').change(function() {
			initAuth();
		});
		$('#ldap_mode').change(function() {
			initSearch();
		});
		$('#ldap_group_require').change(function() {
			initGroupMember();
		});
	}

	if ($('#rrd_autoclean')) {
		initRRDClean();

		$('#rrd_autoclean').change(function() {
			initRRDClean();
		});

		$('#rrd_autoclean_method').change(function() {
			initRRDClean();
		});
	}

	if ($('#boost_rrd_update_enable')) {
		initBoostOD();
		initBoostServer();
		initBoostCache();

		$('#boost_rrd_update_enable').change(function() {
			initBoostOD();
		});

		$('#boost_server_enable').change(function() {
			initBoostServer();
		});

		$('#boost_png_cache_enable').change(function() {
			initBoostCache();
		});
	}

	function initBoostCache() {
		if ($('#boost_png_cache_enable').is(':checked')){
			$('#row_boost_png_cache_directory').show();
		}else{
			$('#row_boost_png_cache_directory').hide();
		}
	}

	function initBoostOD() {
		if ($('#boost_rrd_update_enable').is(':checked')){
			$('#row_boost_rrd_update_interval').show();
			$('#row_boost_rrd_update_max_records').show();
			$('#row_boost_rrd_update_max_records_per_select').show();
			$('#row_boost_rrd_update_string_length').show();
			$('#row_boost_poller_mem_limit').show();
			$('#row_boost_rrd_update_max_runtime').show();
			$('#row_boost_redirect').show();
		}else{
			$('#row_boost_rrd_update_interval').hide();
			$('#row_boost_rrd_update_max_records').hide();
			$('#row_boost_rrd_update_max_records_per_select').hide();
			$('#row_boost_rrd_update_string_length').hide();
			$('#row_boost_poller_mem_limit').hide();
			$('#row_boost_rrd_update_max_runtime').hide();
			$('#row_boost_redirect').hide();
		}
	}

	function initBoostServer() {
		if ($('#boost_server_enable').is(':checked')){
			$('#row_boost_server_effective_user').show();
			$('#row_boost_server_multiprocess').show();
			$('#row_boost_path_rrdupdate').show();
			$('#row_boost_server_hostname').show();
			$('#row_boost_server_listen_port').show();
			$('#row_boost_server_timeout').show();
			$('#row_boost_server_clients').show();
		}else{
			$('#row_boost_server_effective_user').hide();
			$('#row_boost_server_multiprocess').hide();
			$('#row_boost_path_rrdupdate').hide();
			$('#row_boost_server_hostname').hide();
			$('#row_boost_server_listen_port').hide();
			$('#row_boost_server_timeout').hide();
			$('#row_boost_server_clients').hide();
		}
	}

	function themeChanger() {
		if ($('#selected_theme').val() != currentTheme) {
			themeChanged = true;
		}else{
			themeChanged = false;
		}
	}

	function initFonts() {
		if ($('#font_method').val() == 1) {
			$('#row_title_size').hide();
			$('#row_title_font').hide();
			$('#row_legend_size').hide();
			$('#row_legend_font').hide();
			$('#row_axis_size').hide();
			$('#row_axis_font').hide();
			$('#row_unit_size').hide();
			$('#row_unit_font').hide();
		}else{
			$('#row_title_size').show();
			$('#row_title_font').show();
			$('#row_legend_size').show();
			$('#row_legend_font').show();
			$('#row_axis_size').show();
			$('#row_axis_font').show();
			$('#row_unit_size').show();
			$('#row_unit_font').show();
		}
	}

	function initRRDClean() {
		if ($('#rrd_autoclean').is(':checked')) {
			$('#row_rrd_autoclean_method').show();
			if ($('#rrd_autoclean_method').val() == '3') {
				$('#row_rrd_archive').show();
			}else{
				$('#row_rrd_archive').hide();
			}
		}else{
			$('#row_rrd_autoclean_method').hide();
			$('#row_rrd_archive').hide();
		}
	}

	function initSearch() {
		switch($('#ldap_mode').val()) {
		case "0":
			$('#row_ldap_search_base_header').hide();
			$('#row_ldap_search_base').hide();
			$('#row_ldap_search_filter').hide();
			$('#row_ldap_specific_dn').hide();
			$('#row_ldap_specific_password').hide();
			break;
		case "1":
			$('#row_ldap_search_base_header').show();
			$('#row_ldap_search_base').show();
			$('#row_ldap_search_filter').show();
			$('#row_ldap_specific_dn').hide();
			$('#row_ldap_specific_password').hide();
			break;
		case "2":
			$('#row_ldap_search_base_header').show();
			$('#row_ldap_search_base').show();
			$('#row_ldap_search_filter').show();
			$('#row_ldap_specific_dn').show();
			$('#row_ldap_specific_password').show();
			break;
		}
	}

	function initGroupMember() {
		if ($('#ldap_group_require').is(':checked')) {
			$('#row_ldap_group_header').show();
			$('#row_ldap_group_dn').show();
			$('#row_ldap_group_attrib').show();
			$('#row_ldap_group_member_type').show();
		}else{
			$('#row_ldap_group_header').hide();
			$('#row_ldap_group_dn').hide();
			$('#row_ldap_group_attrib').hide();
			$('#row_ldap_group_member_type').hide();
		}
	}

	function initAuth() {
		switch($('#auth_method').val()) {
		case "0":
			$('#row_special_users_header').hide();
			$('#row_guest_user').hide();
			$('#row_user_template').hide();
			$('#row_ldap_general_header').hide();
			$('#row_ldap_server').hide();
			$('#row_ldap_port').hide();
			$('#row_ldap_port_ssl').hide();
			$('#row_ldap_version').hide();
			$('#row_ldap_encryption').hide();
			$('#row_ldap_referrals').hide();
			$('#row_ldap_mode').hide();
			$('#row_ldap_dn').hide();
			$('#row_ldap_group_require').hide();
			$('#row_ldap_attrib').hide();
			$('#row_ldap_member_type').hide();
			$('#row_ldap_group_header').hide();
			$('#row_ldap_group_dn').hide();
			$('#row_ldap_group_attrib').hide();
			$('#row_ldap_group_member_type').hide();
			$('#row_ldap_search_base_header').hide();
			$('#row_ldap_search_base').hide();
			$('#row_ldap_search_filter').hide();
			$('#row_ldap_specific_dn').hide();
			$('#row_ldap_specific_password').hide();
			break;
		case "1":
		case "2":
		case "4":
			$('#row_special_users_header').show();
			$('#row_guest_user').show();
			$('#row_user_template').show();
			$('#row_ldap_general_header').hide();
			$('#row_ldap_server').hide();
			$('#row_ldap_port').hide();
			$('#row_ldap_port_ssl').hide();
			$('#row_ldap_version').hide();
			$('#row_ldap_encryption').hide();
			$('#row_ldap_referrals').hide();
			$('#row_ldap_mode').hide();
			$('#row_ldap_dn').hide();
			$('#row_ldap_group_require').hide();
			$('#row_ldap_attrib').hide();
			$('#row_ldap_member_type').hide();
			$('#row_ldap_group_header').hide();
			$('#row_ldap_group_dn').hide();
			$('#row_ldap_group_attrib').hide();
			$('#row_ldap_group_member_type').hide();
			$('#row_ldap_search_base_header').hide();
			$('#row_ldap_search_base').hide();
			$('#row_ldap_search_filter').hide();
			$('#row_ldap_specific_dn').hide();
			$('#row_ldap_specific_password').hide();
			break;
		case "3":
			$('#row_special_users_header').show();
			$('#row_guest_user').show();
			$('#row_user_template').show();
			$('#row_ldap_general_header').show();
			$('#row_ldap_server').show();
			$('#row_ldap_port').show();
			$('#row_ldap_port_ssl').show();
			$('#row_ldap_version').show();
			$('#row_ldap_encryption').show();
			$('#row_ldap_referrals').show();
			$('#row_ldap_mode').show();
			$('#row_ldap_dn').show();
			$('#row_ldap_group_require').show();
			$('#row_ldap_attrib').show();
			$('#row_ldap_member_type').show();
			$('#row_ldap_group_header').show();
			$('#row_ldap_group_dn').show();
			$('#row_ldap_group_attrib').show();
			$('#row_ldap_group_member_type').show();
			$('#row_ldap_search_base_header').show();
			$('#row_ldap_search_base').show();
			$('#row_ldap_search_filter').show();
			$('#row_ldap_specific_dn').show();
			$('#row_ldap_specific_password').show();
			initSearch();
			initGroupMember();
			break;
		default:
			$('#row_special_users_header').show();
			$('#row_guest_user').show();
			$('#row_user_template').show();
			$('#row_ldap_general_header').hide();
			$('#row_ldap_server').hide();
			$('#row_ldap_port').hide();
			$('#row_ldap_port_ssl').hide();
			$('#row_ldap_version').hide();
			$('#row_ldap_encryption').hide();
			$('#row_ldap_referrals').hide();
			$('#row_ldap_mode').hide();
			$('#row_ldap_dn').hide();
			$('#row_ldap_group_require').hide();
			$('#row_ldap_attrib').hide();
			$('#row_ldap_member_type').hide();
			$('#row_ldap_group_header').hide();
			$('#row_ldap_group_dn').hide();
			$('#row_ldap_group_attrib').hide();
			$('#row_ldap_group_member_type').hide();
			$('#row_ldap_search_base_header').hide();
			$('#row_ldap_search_base').hide();
			$('#row_ldap_search_filter').hide();
			$('#row_ldap_specific_dn').hide();
			$('#row_ldap_specific_password').hide();
			break;
		}
	}

	function initAvail() {
		switch($('#availability_method').val()) {
		case "0":
			$('#row_ping_method').hide();
			$('#row_ping_port').hide();
			$('#row_ping_timeout').hide();
			$('#row_ping_retries').hide();
			break;
		case "1":
		case "4":
			$('#row_ping_method').show();
			$('#row_ping_port').show();
			$('#row_ping_timeout').show();
			$('#row_ping_retries').show();
			break;
		case "3":
			$('#row_ping_method').show();
			$('#row_ping_port').show();
			$('#row_ping_timeout').show();
			$('#row_ping_retries').show();
			break;
		case "2":
		case "5":
		case "6":
			$('#row_ping_method').hide();
			$('#row_ping_port').hide();
			$('#row_ping_timeout').show();
			$('#row_ping_retries').show();
			break;
		}
	}

	function initSNMP() {
		switch($('#snmp_ver').val()) {
		case "0":
			$('#row_snmp_community').hide();
			$('#row_snmp_username').hide();
			$('#row_snmp_password').hide();
			$('#row_snmp_auth_protocol').hide();
			$('#row_snmp_priv_passphrase').hide();
			$('#row_snmp_priv_protocol').hide();
			$('#row_snmp_timeout').hide();
			$('#row_snmp_port').hide();
			$('#row_snmp_retries').hide();
			break;
		case "1":
		case "2":
			$('#row_snmp_community').show();
			$('#row_snmp_username').hide();
			$('#row_snmp_password').hide();
			$('#row_snmp_auth_protocol').hide();
			$('#row_snmp_priv_passphrase').hide();
			$('#row_snmp_priv_protocol').hide();
			$('#row_snmp_timeout').show();
			$('#row_snmp_port').show();
			$('#row_snmp_retries').show();
			break;
		case "3":
			$('#row_snmp_community').hide();
			$('#row_snmp_username').show();
			$('#row_snmp_password').show();
			$('#row_snmp_auth_protocol').show();
			$('#row_snmp_priv_passphrase').show();
			$('#row_snmp_priv_protocol').show();
			$('#row_snmp_timeout').show();
			$('#row_snmp_port').show();
			$('#row_snmp_retries').show();
			break;
		}
	}

	function initFTPExport() {
		switch($('#export_type').val()) {
		case "disabled":
		case "local":
			$('#row_export_hdr_ftp').hide();
			$('#row_export_ftp_sanitize').hide();
			$('#row_export_ftp_host').hide();
			$('#row_export_ftp_port').hide();
			$('#row_export_ftp_passive').hide();
			$('#row_export_ftp_user').hide();
			$('#row_export_ftp_password').hide();
			break;
		case "ftp_php":
		case "ftp_ncftpput":
		case "sftp_php":
			$('#row_export_hdr_ftp').hide();
			$('#row_export_ftp_sanitize').hide();
			$('#row_export_ftp_host').hide();
			$('#row_export_ftp_port').hide();
			$('#row_export_ftp_passive').hide();
			$('#row_export_ftp_user').hide();
			$('#row_export_ftp_password').hide();
			break;
		}
	}

	function initPresentation() {
		switch($('#export_presentation').val()) {
		case "classical":
			$('#row_export_tree_options').hide();
			$('#row_export_tree_isolation').hide();
			$('#row_export_user_id').hide();
			$('#row_export_tree_expand_hosts').hide();
			break;
		case "tree":
			$('#row_export_tree_options').show();
			$('#row_export_tree_isolation').show();
			$('#row_export_user_id').show();
			$('#row_export_tree_expand_hosts').show();
			break;
		}
	}

	function initTiming() {
		switch($('#export_timing').val()) {
		case "disabled":
			$('#row_path_html_export_skip').hide();
			$('#row_export_hourly').hide();
			$('#row_export_daily').hide();
			break;
		case "classic":
			$('#row_path_html_export_skip').show();
			$('#row_export_hourly').hide();
			$('#row_export_daily').hide();
			break;
		case "export_hourly":
			$('#row_path_html_export_skip').hide();
			$('#row_export_hourly').show();
			$('#row_export_daily').hide();
			break;
		case "export_daily":
			$('#row_path_html_export_skip').hide();
			$('#row_export_hourly').hide();
			$('#row_export_daily').show();
			break;
		}
	}

	</script>
	<?php

	bottom_footer();

	break;
}

