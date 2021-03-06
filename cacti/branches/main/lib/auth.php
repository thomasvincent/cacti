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


/* user_copy - copies user account
   @param $template_user - username of the user account that should be used as the template
   @param $new_user - new username of the account to be created/overwritten
   @param $new_realm - new realm of the account to be created, overwrite not affected, but is used for lookup
   @param $overwrite - Allow overwrite of existing user, preserves username, fullname, password and realm
   @param $data_override - Array of user_auth field and values to override on the new user
   @return - True on copy, False on no copy */
function user_copy($template_user, $new_user, $template_realm = 0, $new_realm = 0, $overwrite = false, $data_override = array()) {

	/* ================= input validation ================= */
	input_validate_input_number($template_realm);
	input_validate_input_number($new_realm);
	/* ==================================================== */


	/* Check get template users array */
	$user_auth = db_fetch_row("SELECT * FROM user_auth WHERE username = '" . $template_user . "' AND realm = " . $template_realm);
	if (!isset($user_auth["id"])) {
		return false;
	}
	$template_id = $user_auth["id"];

	/* Create update/insert for new/existing user */
	$user_exist = db_fetch_row("SELECT * FROM user_auth WHERE username = '" . $new_user . "' AND realm = " . $new_realm);
	if (isset($user_exist["id"])) {
		if ($overwrite) {
			/* Overwrite existing user */
			$user_auth["id"] = $user_exist["id"];
			$user_auth["username"] = $user_exist["username"];
			$user_auth["password"] = $user_exist["password"];
			$user_auth["realm"] = $user_exist["realm"];
			$user_auth["full_name"] = $user_exist["full_name"];
			$user_auth["must_change_password"] = $user_exist["must_change_password"];
			$user_auth["enabled"] = $user_exist["enabled"];
		}else{
			/* User already exists, duplicate users are bad */
			raise_message(19);
			return false;
		}
	} else {
		/* new user */
		$user_auth["id"] = 0;
		$user_auth["username"] = $new_user;
		$user_auth["password"] = "!";
		$user_auth["realm"] = $new_realm;
	}

	/* Update data_override fields */
	if (is_array($data_override)) {
		foreach ($data_override as $field => $value) {
			if ((isset($user_auth[$field])) && ($field != "id") && ($field != "username")) {
				$user_auth[$field] = $value;
			}
		}
	}

	/* Save the user */
	$new_id = sql_save($user_auth, 'user_auth');

	/* Create/Update permissions and settings */
	if ((isset($user_exist)) && ($overwrite )) {
		db_execute("DELETE FROM user_auth_perms WHERE user_id = " . $user_exist["id"]);
		db_execute("DELETE FROM user_auth_realm WHERE user_id = " . $user_exist["id"]);
		db_execute("DELETE FROM settings_graphs WHERE user_id = " . $user_exist["id"]);
		db_execute("DELETE FROM settings_tree WHERE user_id = " . $user_exist["id"]);
	}

	$user_auth_perms = db_fetch_assoc("SELECT * FROM user_auth_perms WHERE user_id = " . $template_id);
	if (isset($user_auth_perms)) {
		foreach ($user_auth_perms as $row) {
			$row['user_id'] = $new_id;
			sql_save($row, 'user_auth_perms', array('user_id', 'item_id', 'type'), false);
		}
	}

	$user_auth_realm = db_fetch_assoc("SELECT * FROM user_auth_realm WHERE user_id = " . $template_id);
	if (isset($user_auth_realm)) {
		foreach ($user_auth_realm as $row) {
			$row['user_id'] = $new_id;
			sql_save($row, 'user_auth_realm', array('realm_id', 'user_id'), false);
		}
	}

	$settings_graphs = db_fetch_assoc("SELECT * FROM settings_graphs WHERE user_id = " . $template_id);
	if (isset($settings_graphs)) {
		foreach ($settings_graphs as $row) {
			$row['user_id'] = $new_id;
			sql_save($row, 'settings_graphs', array('user_id', 'name'), false);
		}
	}

	$settings_tree = db_fetch_assoc("SELECT * FROM settings_tree WHERE user_id = " . $template_id);
	if (isset($settings_tree)) {
		foreach ($settings_tree as $row) {
			$row['user_id'] = $new_id;
			sql_save($row, 'settings_tree', array('user_id', 'graph_tree_item_id'), false);
		}
	}

	return true;

}


/* user_remove - remove a user account
   @param $user_id - Id os the user account to remove */
function user_remove($user_id) {
	/* ================= input validation ================= */
	input_validate_input_number($user_id);
	/* ==================================================== */

	/* check for guest or template user */
	$username = db_fetch_cell("select username from user_auth where id = " . $user_id);
	if ($username != get_request_var_post("username")) {
		if ($username == read_config_option("user_template")) {
			raise_message(21);
			return;
		}

		if ($username == read_config_option("guest_user")) {
			raise_message(21);
			return;
		}
	}

	db_execute("delete from user_auth where id=" . $user_id);
	db_execute("delete from user_auth_realm where user_id=" . $user_id);
	db_execute("delete from user_auth_perms where user_id=" . $user_id);
	db_execute("delete from settings_graphs where user_id=" . $user_id);
	db_execute("delete from settings_tree where user_id=" . $user_id);

}


/* user_disable - disable a user account
   @param $user_id - Id of the user account to disable */
function user_disable($user_id) {
	/* ================= input validation ================= */
	input_validate_input_number($user_id);
	/* ==================================================== */

	db_execute("UPDATE user_auth SET enabled = '' where id=" . $user_id);

}


/* user_enable - enable a user account
   @param $user_id - Id of the user account to enable */
function user_enable($user_id) {
	/* ================= input validation ================= */
	input_validate_input_number($user_id);
	/* ==================================================== */

	db_execute("UPDATE user_auth SET enabled = 'on' where id=" . $user_id);

}


/* user_authorized - validate if a user has access to a realm
   @param int $realm - the realm to check
   @param int $user_id - the id of the user
   @returns boolean true or false */
function user_authorized($realm_id, $user_id = 0) {
	/* ================= input validation ================= */
	input_validate_input_number($user_id);
	input_validate_input_number($realm_id);
	/* ==================================================== */

	if ($user_id == 0) {
		$user_id = $_SESSION["sess_user_id"];
	}

	$authorized = db_fetch_cell("SELECT realm_id FROM user_auth_realm WHERE realm_id=$realm_id");

	if ($authorized == $realm_id) {
		return true;
	}else{
		return false;
	}
}


/* get_graph_permissions_sql - creates SQL that reprents the current graph, device and graph
     template policies
   @param $policy_graphs - (int) the current graph policy
   @param $policy_devices - (int) the current device policy
   @param $policy_graph_templates - (int) the current graph template policy
   @returns - an SQL "where" statement */
function get_graph_permissions_sql($policy_graphs, $policy_devices, $policy_graph_templates) {
	require_once(CACTI_INCLUDE_PATH . "/auth/auth_constants.php");

	$sql = "";
	$sql_or = "";
	$sql_and = "";
	$sql_policy_or = "";
	$sql_policy_and = "";

	if ($policy_graphs == AUTH_CONTROL_DATA_POLICY_ALLOW) {
		$sql_policy_and .= "$sql_and(user_auth_perms.type != " . PERM_GRAPHS . " OR user_auth_perms.type is null)";
		$sql_and = " AND ";
		$sql_null = "is null";
	}elseif ($policy_graphs == AUTH_CONTROL_DATA_POLICY_DENY) {
		$sql_policy_or .= "$sql_or(user_auth_perms.type = " . PERM_GRAPHS . " OR user_auth_perms.type is not null)";
		$sql_or = " OR ";
		$sql_null = "is not null";
	}

	if ($policy_devices == AUTH_CONTROL_DATA_POLICY_ALLOW) {
		$sql_policy_and .= "$sql_and((user_auth_perms.type != " . PERM_DEVICES . ") OR (user_auth_perms.type is null))";
		$sql_and = " AND ";
	}elseif ($policy_devices == AUTH_CONTROL_DATA_POLICY_DENY) {
		$sql_policy_or .= "$sql_or((user_auth_perms.type = " . PERM_DEVICES . ") OR (user_auth_perms.type is not null))";
		$sql_or = " OR ";
	}

	if ($policy_graph_templates == AUTH_CONTROL_DATA_POLICY_ALLOW) {
		$sql_policy_and .= "$sql_and((user_auth_perms.type != " . PERM_GRAPH_TEMPLATES . ") OR (user_auth_perms.type is null))";
		$sql_and = " AND ";
	}elseif ($policy_graph_templates == AUTH_CONTROL_DATA_POLICY_DENY) {
		$sql_policy_or .= "$sql_or((user_auth_perms.type = " . PERM_GRAPH_TEMPLATES . ") OR (user_auth_perms.type is not null))";
		$sql_or = " OR ";
	}

	$sql_and = "";

	if (!empty($sql_policy_or)) {
		$sql_and = "AND ";
		$sql .= $sql_policy_or;
	}

	if (!empty($sql_policy_and)) {
		$sql .= "$sql_and$sql_policy_and";
	}

	if (empty($sql)) {
		return "";
	}else{
		return "(" . $sql . ")";
	}
}

/* is_graph_allowed - determines whether the current user is allowed to view a certain graph
   @param $local_graph_id - (int) the ID of the graph to check permissions for
   @returns - (bool) whether the current user is allowed the view the specified graph or not */
function is_graph_allowed($local_graph_id) {
	require_once(CACTI_INCLUDE_PATH . "/auth/auth_constants.php");

	$current_user = db_fetch_row("select policy_graphs,policy_devices,policy_graph_templates from user_auth where id=" . $_SESSION["sess_user_id"]);

	/* get policy information for the sql where clause */
	$sql_where = get_graph_permissions_sql($current_user["policy_graphs"], $current_user["policy_devices"], $current_user["policy_graph_templates"]);

	$graphs = db_fetch_assoc("select
		graph_templates_graph.local_graph_id
		from (graph_templates_graph,graph_local)
		left join device on (device.id=graph_local.device_id)
		left join graph_templates on (graph_templates.id=graph_local.graph_template_id)
		left join user_auth_perms on ((graph_templates_graph.local_graph_id=user_auth_perms.item_id and user_auth_perms.type=" . PERM_GRAPHS . " and user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ") OR (device.id=user_auth_perms.item_id and user_auth_perms.type=" . PERM_DEVICES . " and user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ") OR (graph_templates.id=user_auth_perms.item_id and user_auth_perms.type=" . PERM_GRAPH_TEMPLATES . " and user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . "))
		where graph_templates_graph.local_graph_id=graph_local.id
		" . (empty($sql_where) ? "" : "and $sql_where") . "
		and graph_templates_graph.local_graph_id=$local_graph_id
		group by graph_templates_graph.local_graph_id");

	if (sizeof($graphs) > 0) {
		return true;
	}else{
		return false;
	}
}

/* is_tree_allowed - determines whether the current user is allowed to view a certain graph tree
   @param $tree_id - (int) the ID of the graph tree to check permissions for
   @returns - (bool) whether the current user is allowed the view the specified graph tree or not */
function is_tree_allowed($tree_id) {
	require_once(CACTI_INCLUDE_PATH . "/auth/auth_constants.php");

	$current_user = db_fetch_row("select policy_trees from user_auth where id=" . $_SESSION["sess_user_id"]);

	$trees = db_fetch_assoc("select
		user_id
		from user_auth_perms
		where user_id=" . $_SESSION["sess_user_id"] . "
		and type=" . PERM_TREES . "
		and item_id=$tree_id");

	/* policy == allow AND matches = DENY */
	if ((sizeof($trees) > 0) && ($current_user["policy_trees"] == AUTH_CONTROL_DATA_POLICY_ALLOW)) {
		return false;
	/* policy == deny AND matches = ALLOW */
	}elseif ((sizeof($trees) > 0) && ($current_user["policy_trees"] == AUTH_CONTROL_DATA_POLICY_DENY)) {
		return true;
	/* policy == allow AND no matches = ALLOW */
	}elseif ((sizeof($trees) == 0) && ($current_user["policy_trees"] == AUTH_CONTROL_DATA_POLICY_ALLOW)) {
		return true;
	/* policy == deny AND no matches = DENY */
	}elseif ((sizeof($trees) == 0) && ($current_user["policy_trees"] == AUTH_CONTROL_DATA_POLICY_DENY)) {
		return false;
	}
}

function &user_auth_form_list() {
	require(CACTI_INCLUDE_PATH . "/auth/auth_forms.php");

	return $fields_user_user_edit_device;
}