<?/* 
   +-------------------------------------------------------------------------+
   | Copyright (C) 2002 Ian Berry                                            |
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
   | cacti: the rrdtool frontend [php-auth, php-tree, php-form]              |
   +-------------------------------------------------------------------------+
   | This code is currently maintained and debugged by Ian Berry, any        |
   | questions or comments regarding this code should be directed to:        |
   | - iberry@raxnet.net                                                     |
   +-------------------------------------------------------------------------+
   | - raXnet - http://www.raxnet.net/                                       |
   +-------------------------------------------------------------------------+
   */?>
<?
$section = "Add/Edit Graphs"; include ('include/auth.php');

include_once ("include/functions.php");
include_once ("include/config_arrays.php");
include_once ('include/form.php');

switch ($_REQUEST["action"]) {
	case 'save':
		$redirect_location = form_save();
		
		header ("Location: $redirect_location"); exit;
		break;
	case 'remove':
		snmp_remove();
		
		header ("Location: snmp.php");
		break;
	case 'edit':
		include_once ("include/top_header.php");
		
		snmp_edit();
		
		include_once ("include/bottom_footer.php");
		break;
	default:
		include_once ("include/top_header.php");
		
		snmp();
		
		include_once ("include/bottom_footer.php");
		break;
}

/* --------------------------
    The Save Function
   -------------------------- */

function form_save() {
	if (isset($_POST["save_component_snmp_query"])) {
		$url = snmp_save();
		
		return $url;
	}
}
   
/* ---------------------
    SNMP Query Functions
   --------------------- */

function snmp_remove() {
	global $config;
	
	if ((read_config_option("remove_verification") == "on") && ($_GET["confirm"] != "yes")) {
		include ('include/top_header.php');
		DrawConfirmForm("Are You Sure?", "Are you sure you want to delete the CDEF <strong>'" . db_fetch_cell("select name from cdef where id=" . $_GET["id"]) . "'</strong>?", getenv("HTTP_REFERER"), "snmp.php?action=remove&id=" . $_GET["id"]);
		include ('include/bottom_footer.php');
		exit;
	}
	
	if ((read_config_option("remove_verification") == "") || ($_GET["confirm"] == "yes")) {
		db_execute("delete from cdef where id=" . $_GET["id"]);
		db_execute("delete from cdef_items where cdef_id=" . $_GET["id"]);
	}
}

function snmp_save() {
	$save["id"] = $_POST["id"];
	$save["name"] = form_input_validate($_POST["name"], "name", "", false, 3);
	$save["description"] = form_input_validate($_POST["description"], "description", "", true, 3);
	$save["xml_path"] = form_input_validate($_POST["xml_path"], "xml_path", "", false, 3);
	$save["graph_template_id"] = $_POST["graph_template_id"];
	$save["data_input_id"] = $_POST["data_input_id"];
	
	$snmp_query_id = sql_save($save, "snmp_query");
	
	db_execute ("delete from snmp_query_dt_field where snmp_query_id=$snmp_query_id");
	db_execute ("delete from snmp_query_dt_rrd where snmp_query_id=$snmp_query_id");
	
	while (list($var, $val) = each($_POST)) {
		if (eregi("^mdt_([0-9]+)_([0-9]+)_check", $var)) {
			$data_template_id = ereg_replace("^mdt_([0-9]+)_([0-9]+).+", "\\1", $var);
			$data_input_field_id = ereg_replace("^mdt_([0-9]+)_([0-9]+).+", "\\2", $var);
			
			//if (isset($_POST{"dt_" . $data_template_id})) {
				db_execute ("replace into snmp_query_dt_field (snmp_query_id,data_template_id,data_input_field_id,action_id) values($snmp_query_id,$data_template_id,$data_input_field_id," . $_POST{"mdt_" . $data_template_id . "_" . $data_input_field_id . "_action_id"} . ")");
			//}
		}elseif (eregi("^dsdt_([0-9]+)_([0-9]+)_check", $var)) {
			$data_template_id = ereg_replace("^dsdt_([0-9]+)_([0-9]+).+", "\\1", $var);
			$data_template_rrd_id = ereg_replace("^dsdt_([0-9]+)_([0-9]+).+", "\\2", $var);
			
			//if (isset($_POST{"dt_" . $data_template_id})) {
				db_execute ("replace into snmp_query_dt_rrd (snmp_query_id,data_template_id,data_template_rrd_id,snmp_field_name) values($snmp_query_id,$data_template_id,$data_template_rrd_id,'" . $_POST{"dsdt_" . $data_template_id . "_" . $data_template_rrd_id . "_snmp_field_output"} . "')");
			//}
		}
	}
	
	if (is_error_message()) {
		return $_SERVER["HTTP_REFERER"];
	}else{
		if (empty($_POST["id"])) {
			return "snmp.php?action=edit&id=$snmp_query_id";
		}else{
			return "snmp.php";
		}
	}
}

function snmp_edit() {
	include_once ("include/xml_functions.php");
	
	global $colors, $paths, $snmp_query_field_actions;
	
	display_output_messages();
	
	start_box("<strong>SNMP Queries</strong> [edit]", "98%", $colors["header"], "3", "center", "");
	
	if (isset($_GET["id"])) {
		$snmp_query = db_fetch_row("select * from snmp_query where id=" . $_GET["id"]);
	}else{
		unset($snmp_query);
	}
	
	?>
	<form method="post" action="snmp.php">
	
	<?DrawMatrixRowAlternateColorBegin($colors["form_alternate1"],$colors["form_alternate2"],0); ?>
		<td width="50%">
			<font class="textEditTitle">Name</font><br>
			A name for this SNMP query.
		</td>
		<?DrawFormItemTextBox("name",$snmp_query["name"],"","100", "40");?>
	</tr>
	
	<?DrawMatrixRowAlternateColorBegin($colors["form_alternate1"],$colors["form_alternate2"],1); ?>
		<td width="50%">
			<font class="textEditTitle">Description</font><br>
			A description for this SNMP query.
		</td>
		<?DrawFormItemTextBox("description",$snmp_query["description"],"","255", "40");?>
	</tr>
	
	<?DrawMatrixRowAlternateColorBegin($colors["form_alternate1"],$colors["form_alternate2"],0); ?>
		<td width="50%">
			<font class="textEditTitle">XML Path</font><br>
			The full path to the XML file containing definitions for this snmp query.
		</td>
		<?DrawFormItemTextBox("xml_path",$snmp_query["xml_path"],"<path_cacti>/resource/","255", "40");?>
	</tr>
	
	<?DrawMatrixRowAlternateColorBegin($colors["form_alternate1"],$colors["form_alternate2"],1); ?>
		<td width="50%">
			<font class="textEditTitle">Graph Template</font><br>
			Choose a graph template to associate with this SNMP query.
		</td>
		<?DrawFormItemDropdownFromSQL("graph_template_id",db_fetch_assoc("select id,name from graph_templates order by name"),"name","id",$snmp_query["graph_template_id"],"","");?>
	</tr>
	
	<?DrawMatrixRowAlternateColorBegin($colors["form_alternate1"],$colors["form_alternate2"],0); ?>
		<td width="50%">
			<font class="textEditTitle">Data Input Method</font><br>
			Select the data input method that will store/execute the data for this query.
		</td>
		<?DrawFormItemDropdownFromSQL("data_input_id",db_fetch_assoc("select id,name from data_input order by name"),"name","id",$snmp_query["data_input_id"],"","");?>
	</tr>
	
	<?
	DrawFormItemHiddenIDField("id",$_GET["id"]);
	end_box();
	
	if (isset($_GET["id"])) {
		start_box("", "98%", "aaaaaa", "3", "center", "");
		print "<tr bgcolor='#f5f5f5'><td>" . (file_exists(str_replace("<path_cacti>", $paths["cacti"], $snmp_query["xml_path"])) ? "<font color='#0d7c09'><strong>XML File Exists</strong></font>" : "<font color='#ff0000'><strong>XML File Does Not Exist</strong></font>") . "</td></tr>";
		end_box();
		
		$data_templates = db_fetch_assoc("select
			data_template.id,
			data_template.name
			from data_template, data_template_rrd, graph_templates_item
			where graph_templates_item.task_item_id=data_template_rrd.id
			and data_template_rrd.data_template_id=data_template.id
			and data_template_rrd.local_data_id=0
			and graph_templates_item.local_graph_id=0
			and graph_templates_item.graph_template_id=" . $snmp_query["graph_template_id"] . "
			group by data_template.id
			order by data_template.name");
		
		$i = 0;
		if (sizeof($data_templates) > 0) {
		foreach ($data_templates as $data_template) {
			start_box("<strong>Data Template</strong> [" . $data_template["name"] . "]", "98%", $colors["header"], "3", "center", "");
			
			print "	<tr bgcolor='#" . $colors["header_panel"] . "'>
					<td><span style='color: white; font-weight: bold;'>Data Input Field -> SNMP Field Action Mappings</span></td>
				</tr>";
			
			$fields = db_fetch_assoc("select
				data_input_fields.id,
				data_input_fields.name,
				snmp_query_dt_field.action_id,
				snmp_query_dt_field.snmp_query_id
				from data_input_fields
				left join snmp_query_dt_field on (snmp_query_dt_field.data_input_field_id=data_input_fields.id and snmp_query_dt_field.snmp_query_id=" . $_GET["id"] . " and snmp_query_dt_field.data_template_id=" . $data_template["id"] . ")
				where data_input_fields.data_input_id=" . $snmp_query["data_input_id"] . "
				order by data_input_fields.name");
			
			if (sizeof($fields) > 0) {
			foreach ($fields as $field) {
				if (empty($field["snmp_query_id"])) {
					$old_value = "";
				}else{
					$old_value = "on";
				}
			
			DrawMatrixRowAlternateColorBegin($colors["form_alternate1"],$colors["form_alternate2"],$i); $i++;
			?>
				<td colspan="3">
					<table cellspacing="0" cellpadding="0" border="0" width="100%">
						<tr>
							<td width="200">
								<strong>Data Input Field:</strong>
							</td>
							<td width="200">
								<?print $field["name"];?>
							</td>
							<td width="1">
								<?DrawStrippedFormItemDropdownFromSQL("mdt_" . $data_template["id"] . "_" . $field["id"] . "_action_id",$snmp_query_field_actions,"","",$field["action_id"],"","");?>
							</td>
							<td align="right">
								<?DrawStrippedFormItemCheckBox("mdt_" . $data_template["id"] . "_" . $field["id"] . "_check", $old_value, "", "",true);?>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<?
			}
			}
			
			print "	<tr bgcolor='#" . $colors["header_panel"] . "'>
					<td><span style='color: white; font-weight: bold;'>Data Source -> SNMP Output Field Mappings</span></td>
				</tr>";
			
			$data_template_rrds = db_fetch_assoc("select
				data_template_rrd.id,
				data_template_rrd.data_source_name,
				snmp_query_dt_rrd.snmp_field_name,
				snmp_query_dt_rrd.snmp_query_id
				from data_template_rrd
				left join snmp_query_dt_rrd on (snmp_query_dt_rrd.data_template_rrd_id=data_template_rrd.id and snmp_query_dt_rrd.snmp_query_id=" . $_GET["id"] . " and snmp_query_dt_rrd.data_template_id=" . $data_template["id"] . ")
				where data_template_rrd.data_template_id=" . $data_template["id"] . "
				and data_template_rrd.local_data_id=0
				order by data_template_rrd.data_source_name");
			
			$i = 0;
			if (sizeof($data_template_rrds) > 0) {
			foreach ($data_template_rrds as $data_template_rrd) {
				if (empty($data_template_rrd["snmp_query_id"])) {
					$old_value = "";
				}else{
					$old_value = "on";
				}
				
				DrawMatrixRowAlternateColorBegin($colors["form_alternate1"],$colors["form_alternate2"],$i); $i++;
				?>
					<td colspan="3">
						<table cellspacing="0" cellpadding="0" border="0" width="100%">
							<tr>
								<td width="200">
									<strong>Data Source:</strong>
								</td>
								<td width="200">
									<?print $data_template_rrd["data_source_name"];?>
								</td>
								<td width="1">
									<?
									$data = implode("",file(str_replace("<path_cacti>", $paths["cacti"], $snmp_query["xml_path"])));
									$snmp_queries = xml2array($data);
									$xml_outputs = array();
									
									while (list($field_name, $field_array) = each($snmp_queries["fields"][0])) {
										$field_array = $field_array[0];
										
										if ($field_array["direction"] == "output") {
											$xml_outputs[$field_name] = $field_name . " (" . $field_array["name"] . ")";;	
										}
									}
									
									DrawStrippedFormItemDropdownFromSQL("dsdt_" . $data_template["id"] . "_" . $data_template_rrd["id"] . "_snmp_field_output",$xml_outputs,"","",$data_template_rrd["snmp_field_name"],"","");?>
								</td>
								<td align="right">
									<?DrawStrippedFormItemCheckBox("dsdt_" . $data_template["id"] . "_" . $data_template_rrd["id"] . "_check", $old_value, "", "",true);?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<?
			}
			}
			
			end_box();
		}
		}
	}
	
	DrawFormItemHiddenTextBox("save_component_snmp_query","1","");
	
	start_box("", "98%", $colors["header"], "3", "center", "");
	?>
	<tr bgcolor="#FFFFFF">
		 <td colspan="2" align="right">
			<?DrawFormSaveButton("save", "snmp.php");?>
		</td>
	</tr>
	</form>
	<?
	end_box();	
}

function snmp() {
	global $colors;
	
	display_output_messages();
	
	start_box("<strong>SNMP Queries</strong>", "98%", $colors["header"], "3", "center", "snmp.php?action=edit");
	
	print "<tr bgcolor='#" . $colors["header_panel"] . "'>";
		DrawMatrixHeaderItem("Name",$colors["header_text"],1);
		DrawMatrixHeaderItem("&nbsp;",$colors["header_text"],1);
	print "</tr>";
    	
	$snmp_queries = db_fetch_assoc("select id,name from snmp_query order by name");
	
	if (sizeof($snmp_queries) > 0) {
	foreach ($snmp_queries as $snmp_query) {
		DrawMatrixRowAlternateColorBegin($colors["alternate"],$colors["light"],$i); $i++;
			?>
			<td>
				<a class="linkEditMain" href="snmp.php?action=edit&id=<?print $snmp_query["id"];?>"><?print $snmp_query["name"];?></a>
			</td>
			<td width="1%" align="right">
				<a href="snmp.php?action=remove&id=<?print $snmp_query["id"];?>"><img src="images/delete_icon.gif" width="10" height="10" border="0" alt="Delete"></a>&nbsp;
			</td>
		</tr>
	<?
	}
	}
	end_box();	
}
?>
