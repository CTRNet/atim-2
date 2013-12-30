<?php
/**
 * @author FM L'Heureux
 * @date 2009-12-02
 * @description: Reads a database and compares base tables with revs tables. Gives the difference between those and gives the list
 * of tables without revs. Also gives a list of strings without tranlsation.
 */
require_once("../common/myFunctions.php");

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Database validation</title>
<script type="text/javascript" src="../common/js/jquery-1.4.4.min.js"></script>
<script type="text/javascript">
$(function(){
	$("select").change(function(){
		document.location = "?db=" + $(this).val();
	});

	$("#tr_target").append($("#translations"));

	$(".table_field").click(function(){
		if($(this).attr("checked")){
			$("tr." + this.id).show();
		}else{
			$("tr." + this.id).hide();
		}
	});

	$("#where").click(function(){
		if($(this).attr("checked")){
			$("#mt tr").each(function(){
				$($(this).find("td, th")[1]).show();
			});
		}else{
			$("#mt tr").each(function(){
				$($(this).find("td, th")[1]).hide();
			});
		}
	});
});
</script>
<style type="text/css">
body{
	font-family: arial;
	font-size: 85%;
}
</style>
</head>
<body>
<div id="top">
	Current database: 
	<?php 
	$db2 = getConnection();
	$query = "SHOW databases";
	$result = $db->query($query) or die("show databases failed");
	?>
	<select id="dbSelect">
		<option></option>
		<?php 
		while($row = $result->fetch_row()){
			if($row[0] != "information_schema" && $row[0] != "mysql"){
				$selected = ($row[0] == $db_schema ? ' selected="selected"' : "");
				echo("<option".$selected.">".$row[0]."</option>");
			}
		}
		?>
	</select>
</div>
<?php 

// 1- GET TABLES

$result = $db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='".$_SESSION['db']."' ORDER BY TABLE_NAME") or die($db->error);
$tables = array();
while ($row = $result->fetch_assoc()) {
	foreach($row as $key => $value){
		$tables[$value] = "";
	}
}
$all_tables = array_merge($tables, array());
$result->free();
$control_tables = array();
$control_tables_to_ignore = array('datamart_browsing_controls', 'misc_identifier_controls', 'parent_to_derivative_sample_controls', 'realiquoting_controls', 'structure_permissible_values_custom_controls');
$control_tables_to_ignore = array_flip($control_tables_to_ignore);
$system_tables = array(
	'acos',
	'aliquot_controls',
	'aliquot_review_controls',
	'announcements',
	'aros',
	'aros_acos',
	'atim_information',
	'cake_sessions',
	'coding_icd10_ca',
	'coding_icd10_who',
	'coding_icd_o_2_morphology',
	'coding_icd_o_3_morphology',
	'coding_icd_o_3_topography',
	'configs',
	'consent_controls',
	'datamart_batch_ids',
	'datamart_batch_sets',
	'datamart_browsing_controls',
	'datamart_browsing_indexes',
	'datamart_browsing_results',
	'datamart_reports',
	'datamart_saved_browsing_indexes',
	'datamart_saved_browsing_steps',
	'datamart_structure_functions',
	'datamart_structures',
	'diagnosis_controls',
	'event_controls',
	'external_links',
	'groups',
	'i18n',
	'key_increments',
	'lab_book_controls',
	'langs',
	'menus',
	'misc_identifier_controls',
	'missing_translations',
	'pages',
	'parent_to_derivative_sample_controls',
	'protocol_controls',
	'protocol_extend_controls',
	'realiquoting_controls',
	'sample_controls',
	'sop_controls',
	'specimen_review_controls',
	'storage_controls',
	'structure_fields',
	'structure_formats',
	'structure_permissible_values',
	'structure_permissible_values_custom_controls',
	'structure_validations',
	'structure_value_domains',
	'structure_value_domains_permissible_values',
	'structures',
	'system_vars',
	'template_nodes',
	'templates',
	'treatment_controls',
	'treatment_extend_controls',
	'user_login_attempts',
	'user_logs',
	'users',
	'versions',
	'view_aliquot_uses',
	'view_aliquots',
	'view_collections',
	'view_samples',
	'view_storage_masters',
	'view_structure_formats_simplified');
$master_tables_from_controls = array();
$detail_tables_from_controls = array();
foreach($tables as $table => $foo){
	if(array_key_exists($table, $control_tables_to_ignore)){
		continue;
	}
	$pos = strpos($table, "_controls");
	if($pos !== false && $pos == strlen($table) - 9){
		$control_tables[] = $table;
		$master_tables_from_controls[str_replace('_controls', '_masters', $table)] = str_replace('_controls', '_control_id', $table);
		$result = $db->query("SELECT detail_tablename FROM ".$table." GROUP BY detail_tablename") or die($table." ".$db->error);
		while($row = $result->fetch_row()){
			$detail_tables_from_controls[$row[0]] = str_replace('_controls', '_master_id', $table);;
		}
		$result->free();
	}
}
$detail_tables_from_controls['specimen_details'] = 'sample_master_id';
$detail_tables_from_controls['derivative_details'] = 'sample_master_id';
$detail_tables_from_controls['ad_bags'] = 'aliquot_master_id';
$detail_tables_from_controls['ed_all_adverse_events_adverse_events'] = 'event_master_id';
$detail_tables_from_controls['ed_all_protocol_followups'] = 'event_master_id';
$detail_tables_from_controls['std_customs'] = 'storage_master_id';

// 2- Specific Tables Fields Properties

$all_system_fields = array(
		'id' => array(
			'properties' => array(
				'Type' => "int(11)",
				'Null' => "NO",
				'Key' => "PRI",
				'Default' => "",
				'Extra' => "auto_increment"),
			'sql' => "id int(11) NOT NULL AUTO_INCREMENT"),
		'created' => array(
			'properties' => array(
				'Type' => "datetime",
				'Null' => "YES",
				'Key' => "",
				'Default' => "",
				'Extra' => ""),
			'sql' => "created datetime DEFAULT NULL"),
		'created_by' => array(
			'properties' => array(
				'Type' => "int(10) unsigned",
				'Null' => "NO",
				'Key' => "",
				'Default' => "",
				'Extra' => ""),
			'sql' => "created_by int(10) unsigned NOT NULL"),
		'modified' => array(
			'properties' => array(
				'Type' => "datetime",
				'Null' => "YES",
				'Key' => "",
				'Default' => "",
				'Extra' => ""),
			'sql' => "modified datetime DEFAULT NULL"),
		'modified_by' => array(
			'properties' => array(
				'Type' => "int(10) unsigned",
				'Null' => "NO",
				'Key' => "",
				'Default' => "",
				'Extra' => ""),
			'sql' => "modified_by int(10) unsigned NOT NULL"),
		'deleted' => array(
			'properties' => array(
				'Type' => "tinyint(3) unsigned",
				'Null' => "NO",
				'Key' => "",
				'Default' => "0",
				'Extra' => ""),
			'sql' => "deleted tinyint(3) unsigned NOT NULL DEFAULT '0'"),
		'version_id' => array(
			'properties' => array(
				'Type' => "int(11)",
				'Null' => "NO",
				'Key' => "PRI",
				'Default' => "",
				'Extra' => "auto_increment"),
			'sql' => "version_id int(11) NOT NULL AUTO_INCREMENT"),
		'version_created' => array(
			'properties' => array(
				'Type' => "datetime",
				'Null' => "NO",
				'Key' => "",
				'Default' => "",
				'Extra' => ""),
			'sql' => "version_created datetime NOT NULL"));
$main_table_required_system_fields = array('id', 'created', 'created_by', 'modified', 'modified_by', 'deleted'); 	// _control_id field will be added dynamically (if required)
$main_revs_table_required_system_fields = array('id', 'modified_by', 'version_id', 'version_created');				// _control_id field will be added dynamically (if required)
$detail_table_required_system_fields = array();																		// _master_id field will be added dynamically
$detail_revs_table_required_system_fields = array('version_id', 'version_created');									// _master_id field will be added dynamically

// 3- Track Errors

$errors = array('Main Tables' => array(), 'Revs Tables' => array());
foreach($tables as $tname => $foo){
	if(in_array($tname, $system_tables)) {
		unset($tables[$tname]);
	} else {
		if(!preg_match('/_revs$/', $tname)) {
			unset($tables[$tname]);			
			//New Table To Review
			$all_system_fields_working_array = $all_system_fields;
			$table_required_system_fields_working_array = $main_table_required_system_fields;
			$revs_table_required_system_fields_working_array = $main_revs_table_required_system_fields;
			$master_control_foreign_key = null;
			$detail_table_foreign_key = null;
			if(array_key_exists($tname, $master_tables_from_controls)) {
				$master_control_foreign_key = $master_tables_from_controls[$tname];
				$control_table_name = str_replace('_control_id', '_controls', $master_control_foreign_key);
				$all_system_fields_working_array[$master_control_foreign_key] = array(
					'properties' => array(
						'Type' => "int(11)",
						'Null' => "NO",
						'Key' => "MUL",
						'Default' => "0",
						'Extra' => ""),
					'sql' => "$master_control_foreign_key int(11) NOT NULL DEFAULT '0'",
					'sql_fk' => "_$control_table_name FOREIGN KEY ($master_control_foreign_key) REFERENCES $control_table_name (id);");
				$table_required_system_fields_working_array = $main_table_required_system_fields;
				$table_required_system_fields_working_array[] = $master_control_foreign_key;
				$revs_table_required_system_fields_working_array = $main_revs_table_required_system_fields;
				$revs_table_required_system_fields_working_array[] = $master_control_foreign_key;
				if(array_key_exists($tname, $detail_tables_from_controls)) die('ERR_001');
			} else if(array_key_exists($tname, $detail_tables_from_controls)) {
				$detail_table_foreign_key = $detail_tables_from_controls[$tname];
				$master_table_name = str_replace('_master_id', '_masters', $detail_table_foreign_key);
				$all_system_fields_working_array[$detail_table_foreign_key] = array(
						'properties' => array(
							'Type' => "int(11)",
							'Null' => "NO",
							'Key' => "MUL",
							'Default' => "",
							'Extra' => ""),
						'sql' => "$detail_table_foreign_key int(11) NOT NULL",
						'sql_fk' => "_$master_table_name FOREIGN KEY ($detail_table_foreign_key) REFERENCES $master_table_name (id);");
				$table_required_system_fields_working_array = $detail_table_required_system_fields;
				$table_required_system_fields_working_array[] = $detail_table_foreign_key;	
				$revs_table_required_system_fields_working_array = $detail_revs_table_required_system_fields;	
				$revs_table_required_system_fields_working_array[] = $detail_table_foreign_key;		
			}
			$table_required_system_fields_working_array = array_flip($table_required_system_fields_working_array);
			$revs_table_required_system_fields_working_array = array_flip($revs_table_required_system_fields_working_array);
			//Get Table Properties
			$result = $db->query("DESCRIBE ".$tname) or die($db->error);
			$specific_table_fields = array();	
			while ($row = $result->fetch_assoc()) {		
				$field_name = $row['Field'];
				$field_properties = array('Type' => $row['Type'],'Null' => $row['Null'],'Key' => $row['Key'],'Default' => $row['Default'],'Extra' => $row['Extra']);	
				if(array_key_exists($field_name, $all_system_fields_working_array)) {
					//System Field
					if(!array_key_exists($field_name, $table_required_system_fields_working_array)) {
						$errors['Main Tables']['Un-required system field'][$tname]["Delete field [$field_name]"] = "ALTER TABLE $tname DROP COLUMN $field_name;";
					} else if(array_diff_assoc($field_properties, $all_system_fields_working_array[$field_name]['properties']) || array_diff_assoc($all_system_fields_working_array[$field_name]['properties'], $field_properties)) {					
						$msg = array();
						$sqls = array("ALTER TABLE $tname MODIFY ".$all_system_fields_working_array[$field_name]['sql'].";");
						foreach($all_system_fields_working_array[$field_name]['properties'] as $field_key => $expected_field_val) {
							if($field_properties[$field_key] != $expected_field_val) {
								$msg[] = "Change current '$field_key' value [".$field_properties[$field_key]."] to [$expected_field_val]";
								if($field_key == 'Key' && $field_properties[$field_key] == 'MUL') $msg[] = 'Drop foreign key if exits';
								if($field_key == 'Key') {
									if($field_properties[$field_key] == 'MUL') {
										$msg[] = 'Drop foreign key if exits';
										$sqls[] = "ALTER TABLE $tname DROP CONSTRAINT/DROP INDEX ... ON ***TODO***;";
									}
									if($field_properties[$field_key] == 'PRI') {
										$sqls[] = "ALTER TABLE $tname DROP PRIMARY KEY;";
										$msg[] = 'Drop primary key constraints if exits';
									}
									if($expected_field_val == 'MUL') {
										$ref_table_name = str_replace(array('_control_id', '_master_id'), array('_controls', '_masters'), $field_name);
										$sqls[] = "ALTER TABLE $tname ADD CONSTRAINT FK_".$tname."_".$ref_table_name." FOREIGN KEY ($field_name) REFERENCES $ref_table_name (id);";
										$msg[] = 'Create foreign key if required';
									}
								}
							}
						}
						$msg = empty($msg)? '' : implode(' + ', $msg);
						$errors['Main Tables']["Error on system field properties"][$tname][$field_name .': '. $msg] = implode("<br>", $sqls);
						unset($table_required_system_fields_working_array[$field_name]);
					} else {
						unset($table_required_system_fields_working_array[$field_name]);
					}
				} else {
					//Specific Field
					$specific_table_fields[$field_name] = $field_properties;
				}
			}	
			$result->free();
			foreach($table_required_system_fields_working_array as $missing_system_field => $foo_2) {
				$errors['Main Tables']['Missing system field'][$tname]["Create field $missing_system_field"] = "ALTER TABLE $tname ADD COLUMN ".$all_system_fields_working_array[$missing_system_field]['sql'].";";
				if(isset($all_system_fields_working_array[$missing_system_field]['sql_fk'])) $errors['Error on system field - to modify'][$tname][$missing_system_field] .= " ALTER TABLE $tname ADD CONSTRAINT FK_".$tname.$all_system_fields_working_array[$missing_system_field]['sql_fk'].";";	
			}
			if(!isset($tables[$tname."_revs"])){
				$errors['Revs Tables']['Missing revs table'][$tname."_revs"]['Create table '.$tname."_revs"] = 'CREATE TABLE '.$tname."_revs ... *** TODO ***;";
			} else {
				//Get Revs Table Properties
				unset($tables[$tname."_revs"]);
				$tname = $tname."_revs";
				$all_system_fields_working_array['id']['properties']['Key'] = "";
				$all_system_fields_working_array['id']['properties']['Extra'] = "";
				$all_system_fields_working_array['id']['sql'] = "id int(11) NOT NULL";
				if($master_control_foreign_key) $all_system_fields_working_array[$master_control_foreign_key]['properties']['Key'] = "";
				if($detail_table_foreign_key) $all_system_fields_working_array[$detail_table_foreign_key]['properties']['Key'] = "";
				$result = $db->query("DESCRIBE ".$tname) or die($db->error);
				while ($row = $result->fetch_assoc()) {
					$field_name = $row['Field'];				
					$field_properties = array('Type' => $row['Type'],'Null' => $row['Null'],'Key' => $row['Key'],'Default' => $row['Default'],'Extra' => $row['Extra']);
					if(array_key_exists($field_name, $all_system_fields_working_array)) {
						//System Field
						if(!array_key_exists($field_name, $revs_table_required_system_fields_working_array)) {
							$errors['Revs Tables']['Un-required system field'][$tname]["Delete field [$field_name]"] = "ALTER TABLE $tname DROP COLUMN $field_name;";
						} else {
							if($field_properties['Type'] != $all_system_fields_working_array[$field_name]['properties']['Type']) {
								$errors['Revs Tables']["Error on system field property"][$tname][$field_name .': '. "Change field 'Type' value [".$field_properties['Type']."] to [".$all_system_fields_working_array[$field_name]['properties']['Type']."]"] = "ALTER TABLE $tname MODIFY $field_name ... *** TODO ***;";
							}
							if($field_properties['Null'] != $all_system_fields_working_array[$field_name]['properties']['Null']) {
								$errors['Revs Tables']["Warning on system field property"][$tname][$field_name .': '. "Change field 'Null' value [".$field_properties['Null']."] to [".$all_system_fields_working_array[$field_name]['properties']['Null']."]"] = "ALTER TABLE $tname MODIFY $field_name ... *** TODO ***;";
							}
							if($field_name != 'version_id') {
								if($field_properties['Key'] == 'PRI') {
									$errors['Revs Tables']["Error on system field property"][$tname][$field_name .': '. "Delete field 'Key' value [".$field_properties['Key']."]"] = "ALTER TABLE $tname MODIFY $field_name ... *** TODO ***;";
								} else if($field_properties['Key']) {
									$errors['Revs Tables']["Message on system field property"][$tname][$field_name .': '. "Field 'Key' value [".$field_properties['Key']."] is not required"] = "ALTER TABLE $tname DROP CONSTRAINT/DROP INDEX ... ON ***TODO***;;";
								}
							} else {
								if($field_properties['Key'] != 'PRI') {
									$errors['Revs Tables']["Error on system field property"][$tname][$field_name .': '. "Add field 'Key' value [PRI]"] = "ALTER TABLE $tname MODIFY $field_name ... *** TODO ***;";
								}
							}
							if($field_properties['Default'] != $all_system_fields_working_array[$field_name]['properties']['Default']) {
								$errors['Revs Tables']["Warning on system field property"][$tname][$field_name .': '. "Change field 'Default' value [".$field_properties['Default']."] to [".$all_system_fields_working_array[$field_name]['properties']['Default']."]"] = "ALTER TABLE $tname MODIFY $field_name ... *** TODO ***;";
							}
							if($field_properties['Extra'] != $all_system_fields_working_array[$field_name]['properties']['Extra']) {
								$errors['Revs Tables']["Error on system field property"][$tname][$field_name .': '. "Change field 'Default' value [".$field_properties['Extra']."] to [".$all_system_fields_working_array[$field_name]['properties']['Extra']."]"] = "ALTER TABLE $tname MODIFY $field_name ... *** TODO ***;";
							}
							unset($revs_table_required_system_fields_working_array[$field_name]);
						}
					} else {
						if(!isset($specific_table_fields[$field_name])) {
							$errors['Revs Tables']['Field not existing in main table'][$tname]["Delete field [$field_name]"] = "ALTER TABLE $tname DROP COLUMN $field_name;";
						} else {
							if($field_properties['Type'] != $specific_table_fields[$field_name]['Type']) {
								$errors['Revs Tables']["Error on table field property"][$tname][$field_name .': '. "Change field 'Type' value [".$field_properties['Type']."] to [".$specific_table_fields[$field_name]['Type']."]"] = "ALTER TABLE $tname MODIFY $field_name ... *** TODO ***;";
							}
							if($field_properties['Null'] != $specific_table_fields[$field_name]['Null']) {
								$errors['Revs Tables']["Warning on table field property"][$tname][$field_name .': '. "Change field 'Null' value [".$field_properties['Null']."] to [".$specific_table_fields[$field_name]['Null']."]"] = "ALTER TABLE $tname MODIFY $field_name ... *** TODO ***;";
							}								
							if($field_properties['Key'] == 'PRI') {
								$errors['Revs Tables']["Error on table field property"][$tname][$field_name .': '. "Delete field 'Key' value [".$field_properties['Key']."]"] = "ALTER TABLE $tname MODIFY $field_name ... *** TODO ***;";
							} else if($field_properties['Key']) {
								$errors['Revs Tables']["Message on table field property"][$tname][$field_name .': '. "Field 'Key' value [".$field_properties['Key']."] is not required"] = "ALTER TABLE $tname DROP CONSTRAINT/DROP INDEX ... ON ***TODO***;;";
							}							
							if($field_properties['Default'] != $specific_table_fields[$field_name]['Default']) {
								$errors['Revs Tables']["Warning on table field property"][$tname][$field_name .': '. "Change field 'Default' value [".$field_properties['Default']."] to [".$specific_table_fields[$field_name]['Default']."]"] = "ALTER TABLE $tname MODIFY $field_name ... *** TODO ***;";
							}	
							if($field_properties['Extra']) {
								$errors['Revs Tables']["Error on table field property"][$tname][$field_name .': '. "Delete field 'Extra' value [".$field_properties['Extra']."]"] = "ALTER TABLE $tname MODIFY $field_name ... *** TODO ***;";
							}
							unset($specific_table_fields[$field_name]);
						}
					}
				}
				$result->free();
				foreach($specific_table_fields as $missing_field => $foo_2) {
					$errors['Revs Tables']['Missing main table field'][$tname]["Create field [$missing_field]"] = "ALTER TABLE $tname ADD COLUMN $missing_field ... *** TODO ***;";
				}				
				foreach($revs_table_required_system_fields_working_array as $missing_system_field => $foo_2) {
					$errors['Revs Tables']['Missing system field'][$tname]["Create field [$missing_system_field]"] = "ALTER TABLE $tname ADD COLUMN ".$all_system_fields_working_array[$missing_system_field]['sql'].";";
				}			
			}
		}
	}
}
foreach($tables as $alone_revs_table => $foo_3) $errors['Revs Tables']['Unlinked Revs table']["Delete revs table [$alone_revs_table]"]['-'] = "DROP TABLE $alone_revs_table;";
// Display Errors
foreach($errors as $tables_type => $errors_list) {
	echo "<h1>Corrections to do on $tables_type</h1>\n";
	if($errors_list) {
		foreach($errors_list as $errors_title => $tables_precisions_and_sqls) {
			echo "<h2>$errors_title</h2>\n";
			$all_sqls_msg = '';
			foreach($tables_precisions_and_sqls as $tables => $precisions_and_sqls) {
				echo "<b>table $tables</b><br/>\n";
				foreach($precisions_and_sqls as $precisions => $sql) {
					echo " - $precisions<br/>\n";
					$all_sqls_msg .= "$sql<br/>\n";
				}
			}
			echo "<br/>\n$all_sqls_msg</i>";
		}	
	} else {
		echo "None<br/>\n";
	}
}

?>
<h1>Controls tables pointing to invalid tables</h1>
<?php 
$query = "SHOW TABLES";
$result = $db->query($query) or die($db->error);
$non_control_tables = array();
$control_tables = array();
$ignore_list = array("datamart_browsing_controls", "misc_identifier_controls", "parent_to_derivative_sample_controls", "realiquoting_controls", 
	"sample_to_aliquot_controls", "structure_permissible_values_custom_controls");
while ($row = $result->fetch_row()){
	if(substr_compare($row[0], "_controls", -9) === 0){
		$control_tables[] = $row[0];
	}else{
		$non_control_tables[] = $row[0];
	}
}
$result->free();
$non_control_tables = array_flip($non_control_tables);
$control_tables = array_diff($control_tables, $ignore_list);
$missing_details = array();
$keys = array("detail_tablename", "extend_tablename");
foreach($control_tables as $control_table){
	$query = "SELECT * FROM ".$control_table;
	$result = $db->query($query) or die($db->error);
	$keys_to_look_for = array();
	if(($row = $result->fetch_assoc())){
		foreach($keys as $key){
			if(array_key_exists($key, $row)){
				$keys_to_look_for[] = $key;
			}
		}
		do{
			foreach($keys_to_look_for as $key){
				if($row[$key] != null && strlen($row[$key]) > 0 && !array_key_exists(trim($row[$key]), $non_control_tables)){
					$missing_details[] = $control_table." &rarr; ".$key." &rarr; ".$row[$key];
				}
			}
		}while ($row = $result->fetch_assoc());
	}
	$result->free();
}

if(empty($missing_details)){
	echo "All detail and extend tables were found";
}else{
	echo "<ul><li>",implode("</li><li>", $missing_details),"</li></ul>";
}
?>

<h1>Dates without accuracy</h1>
<?php
//cannot be done in a single query because of the permissions on information_schema 
$query = "SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS "
	."WHERE TABLE_SCHEMA='".$_SESSION['db']."' AND DATA_TYPE IN('date', 'datetime') AND COLUMN_NAME NOT IN('created', 'modified', 'version_created') AND TABLE_NAME NOT LIKE '%_revs'";
$result = $db->query($query) or die($db->error);
$date_fields = array();
while($row = $result->fetch_row()){
	$date_fields[$row[0].".".$row[1]] = null;
}
$result->free();
$query = "SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS "
	."WHERE TABLE_SCHEMA='".$_SESSION['db']."' AND DATA_TYPE IN('char', 'varchar') AND COLUMN_NAME LIKE '%_accuracy' AND TABLE_NAME NOT LIKE '%_revs'";

$result = $db->query($query) or die($db->error);
$bad_accuracy = array();
while($row = $result->fetch_row()){
	$key = str_replace("_accuracy", "", $row[0].".".$row[1]);
	if(array_key_exists($key, $date_fields)){
		unset($date_fields[$key]);
	}else{
		$bad_accuracy[] = $row[0].".".$row[1];
	}
}
?>
<ul>
	<li>Fields without accuracy
		<ul>
			<li>
				<?php 
					echo empty($date_fields) ? "All date fields have an accuracy field" : implode("</li>\n<li>", array_keys($date_fields));
				?>
			</li>
		</ul>
	</li>
	<li>Useless/non date accuracy fields
		<ul>
			<li>
				<?php 
				 echo empty($bad_accuracy) ? "No useless accuracy fields" : implode("</li>\n<li>", $bad_accuracy);
				?>
			</li>
		</ul>
	</li>
</ul>

<h1>Structures with duplicate fields</h1>
<?php 
$query = "SELECT COUNT(*) AS c, s.alias, sfi.plugin, sfi.tablename, sfi.field 
	FROM structure_formats AS sfo
	INNER JOIN structure_fields AS sfi ON sfi.id=sfo.structure_field_id 
	INNER JOIN structures AS s ON sfo.structure_id=s.id
    GROUP BY structure_field_id, structure_id HAVING c > 1";

$result = $db->query($query) or die("ERR AT LINE ".__LINE__.": ".$db->error);
if($row = $result->fetch_assoc()){
	echo "<table><thead><tr><th>Structure</th><th>Plugin</th><th>Tablename</th><th>field</th><th>Count</th></tr></thead><tbody>\n";
	$line = "<tr>".str_repeat("<td>%s</td>", 5)."</tr>\n";
	do{
		printf($line, $row['alias'], $row['plugin'], $row['tablename'], $row['field'], $row['c']);
	}while($row = $result->fetch_assoc());
	echo "</tbody></table>";
}else{
	echo "<p>No duplicate fields</p>";
}
$result->free();
?>

<h1>Grouped structures with duplicate fields</h1>
<table>
	<thead>
		<tr>
			<th>Structure(s)</th>
			<th>Field</th>
			<th>Count</th>
			<td>Sfo id(s)</th>
		</tr>
	</thead>
	<tbody>
<?php

$grouped_forms = array();
foreach($control_tables as $control_table){
	$query = "SELECT detail_form_alias FROM ".$control_table;
	$result = $db->query($query) or die("ERR AT LINE ".__LINE__.": ".$db->error." over table ".$control_table);
	while($row = $result->fetch_assoc()){
		$grouped_forms[] = $row['detail_form_alias'];
	}
	$result->free();
}
$grouped_forms = array_unique($grouped_forms);
foreach($grouped_forms as $grouped_form){
	$forms = explode(',', $grouped_form);
	array_filter($forms);
	$query = "SELECT COUNT(*) AS c, sfi.field, GROUP_CONCAT(DISTINCT s.alias SEPARATOR ', ') AS aliases, GROUP_CONCAT(DISTINCT sfo.id SEPARATOR ', ') AS sfo_ids 
		FROM structure_formats AS sfo
		INNER JOIN structure_fields AS sfi ON sfi.id=sfo.structure_field_id 
		INNER JOIN structures AS s ON sfo.structure_id=s.id
		WHERE s.alias IN('".implode("', '", $forms)."')
    	GROUP BY structure_field_id HAVING c > 1";
    $result = $db->query($query) or die("ERR AT LINE ".__LINE__.": ".$db->error);
	while($row = $result->fetch_assoc()){
		printf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', $row['aliases'], $row['field'], $row['c'], $row['sfo_ids']);
	}
	$result->free();

}
?>
	</tbody>
</table>

<h1>Fields referring to non existent tables</h1>
<table>
	<thead>
		<tr>
			<th>id</th>
			<th>Model</th>
			<th>Field</th>
			<th>invalid tablename</th>
		</tr>
	</thead>
	<tbody>
	<?php
	$query = "SELECT * FROM structure_fields WHERE tablename!='' AND tablename NOT IN('".implode("', '", array_keys($all_tables))."')";
	$result = $db->query($query) or die("ERR AT LINE ".__LINE__.": ".$db->error);
	while($row = $result->fetch_assoc()){
		printf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', $row['id'], $row['model'], $row['field'], $row['tablename']);
	}
	$result->free();
	?>
	</tbody>
</table>

<h1>Strings requiring translation</h1>
<div id="tr_target"></div>
<table id="mt"><tr>
<th>Missing translation</th><th>Where</th>
</tr>
<?php 
$query = "SELECT lang, place FROM(
		SELECT language_heading AS lang, 'sfo_language_heading' AS place FROM structure_formats
			LEFT JOIN i18n ON i18n.id=language_heading
			WHERE language_heading != '' AND i18n.id IS NULL
		UNION
			SELECT language_label AS lang, 'sfo_language_label' AS place FROM structure_formats
			LEFT JOIN i18n ON i18n.id=language_label
			WHERE language_label != '' AND i18n.id IS NULL
		UNION
			SELECT language_tag AS lang, 'sfo_language_tag' AS place FROM structure_formats
			LEFT JOIN i18n ON i18n.id=language_tag
			WHERE language_tag != '' AND i18n.id IS NULL
		UNION
			SELECT language_help AS lang, 'sfo_language_help' AS place FROM structure_formats
			LEFT JOIN i18n ON i18n.id=language_help
			WHERE language_help != '' AND i18n.id IS NULL
		UNION
			SELECT language_label AS lang, 'sfo_language_label' AS place FROM structure_formats
			LEFT JOIN i18n ON i18n.id=language_label
			WHERE language_label != '' AND i18n.id IS NULL	

		UNION
			SELECT language_label AS lang, 'sfi_language_label' AS place FROM structure_fields
			LEFT JOIN i18n ON i18n.id=language_label
			WHERE language_label != '' AND i18n.id IS NULL
		UNION
			SELECT language_tag AS lang, 'sfi_language_tag' AS place FROM structure_fields
			LEFT JOIN i18n ON i18n.id=language_tag
			WHERE language_tag != '' AND i18n.id IS NULL
			
		UNION
			SELECT language_alias AS lang, 'spv_language_alias' AS place FROM structure_permissible_values
			LEFT JOIN i18n ON i18n.id=language_alias
			WHERE language_alias != '' AND i18n.id IS NULL

		UNION
			SELECT language_message AS lang, 'sv_language_msg' AS place FROM structure_validations
			LEFT JOIN i18n ON i18n.id=language_message
			WHERE language_message != '' AND i18n.id IS NULL
		
		UNION
			SELECT id AS lang, 'missing_translations' AS place FROM missing_translations) AS tmp GROUP BY lang ORDER BY lang";

$result = $db->query($query) or die($db->error);
$tables = array();
while ($row = $result->fetch_assoc()) {
		if(!is_numeric($row['lang'])){
			echo("<tr class='".$row['place']."'><td>".$row['lang']."</td><td>".$row['place']."</td></tr>\n");
			if(!isset($tables[$row['place']])){
				$tables[$row['place']] = 1;
			}else{
				$tables[$row['place']] ++;
			}
		}
}
$result->free();

?>
</table>

<h1>Update on non strict fields</h1>
<?php 
$query = "SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE table_schema='".$_SESSION['db']."' AND column_default LIKE '0000-00-00%'";
if($result = $db->query($query)){
	?>
	<table>
		<thead>
			<tr><th colspan='2'>Fields with an invalid default date</th></tr>
			<tr><th>Table</th><th>Field</th></tr>
		</thead>
		<tbody>
		<?php
		$fmlh = array();
		if($row = $result->fetch_assoc()){
			do{
				printf ('<tr><td>%s</td><td>%s</td></tr>', $row['TABLE_NAME'], $row['COLUMN_NAME']);
				if($row['COLUMN_NAME'] == 'created' || $row['COLUMN_NAME'] == 'modified'){
					$fmlh[$row['TABLE_NAME']][] = "MODIFY COLUMN ".$row['COLUMN_NAME']." DATETIME DEFAULT NULL";
				}
			}while($row = $result->fetch_assoc());
		}else{
			echo "<tr><td colspan='2'>All fields are conform to strict standards.</td></tr>";
		}
		?>
		</tbody>
	</table>
	<?php
}else{
	echo "<p>You don't have sufficent privileges to run the quick query to detect non strict fields.<p/>";
}

?>
<h1>Dropdown values length exceeding database field capacity</h1>
<h2>Direct dropdown</h2>
<table>
	<thead>
		<tr>
			<th>Table</th>
			<th>Field</th>
			<th>Max length</th>
			<th>Exceeding values</th>
		</tr>
	</thead>
	<tbody>
<?php 
$query = "SELECT sf.tablename, sf.field, c.COLUMN_TYPE, svd.id, svd.domain_name FROM structure_fields AS sf 
INNER JOIN structure_value_domains AS svd ON sf.structure_value_domain=svd.id AND (svd.source IS NULL OR svd.source='')
INNER JOIN information_schema.columns AS c ON c.table_schema='".$_SESSION['db']."' AND c.table_name=sf.tablename AND c.column_name=sf.field AND c.COLUMN_TYPE LIKE '%char%'  
WHERE sf.type='select'";
if($results = $db->query($query)){
	$db2 = getConnection();
	$query = "SELECT value, LENGTH(value), language_alias FROM structure_value_domains_permissible_values AS svdpv
	INNER JOIN structure_permissible_values AS spv ON svdpv.structure_permissible_value_id=spv.id
	WHERE svdpv.structure_value_domain_id=? AND LENGTH(value) > ?";
	$stmt = $db2->prepare($query) or die("svd validation prep failed");
	$all_good = true;
	while($row = $results->fetch_assoc()){
		$match = array();
		if(preg_match('/[0-9]+/', $row['COLUMN_TYPE'], $match)){
			$stmt->bind_param('ii', $row['id'], $match[0]);
			$stmt->execute();
			$row2 = bindRow($stmt);
			if($stmt->fetch()){
				$all_good = false;
				printf('<tr><td>%s</td><td>%s</td><td>%d</td><td>', $row['tablename'], $row['field'], $match[0]);
				$str = array();
				do{
					$str[] = $row2['value'];
				}while($stmt->fetch());
				echo implode(', ', $str),'</td></tr>';
			}
		}else{
			die("No match for column type ".$row['COLUMN_TYPE']);
		}
	}
	if($all_good){
		echo '<tr><td colspan=4>All good!</td></tr>';
	}
	$results->free();
}else{
	echo $db->error;
}

?>
	<tbody>
</table>

<h2>Custom dropdowns</h2>
<table>
	<thead>
		<tr>
			<th>Custom control name</th>
			<th>Max length</th>
			<th>Exceeding values</th>
		</tr>
	</thead>
	<tbody>
	<?php
	$query = "SELECT name, values_max_length, GROUP_CONCAT(spvc.value) AS m_values FROM structure_permissible_values_custom_controls AS spvcc
	INNER JOIN structure_permissible_values_customs AS spvc ON spvcc.id=spvc.control_id AND LENGTH(spvc.value) > spvcc.values_max_length
	GROUP BY spvcc.id";
	$result = $db->query($query) or die("svd validation 2 failed");
	if($row = $result->fetch_assoc()){
		do{
			printf('<tr><td>%s</td><td>%d</td><td>%s</td></tr>', $row['name'], $row['values_max_length'], $row['m_values']);
		}while($row = $result->fetch_assoc());
	}else{
		echo '<tr><td colspan=3>All good</td></tr>';
	}
	$result->free();
	?>
	</tbody>
</table>

<ul id="translations" style="list-style: none;">
	<?php 
	foreach ($tables as $table => $count){
		echo("<li><input type='checkbox' id='".$table."' class='table_field' checked='true'/><label>".$table." (".$count.")</label></li>\n");
	}
	?>
	<li><input id="where" type="checkbox" checked="true"/><label>Show where</label></li>
</ul>

<h2>CodingICD validations</h2>
<?php 
//test validation on CodingIcd fields
$query = "SELECT sf.id, plugin, model, field, setting, rule FROM structure_fields AS sf LEFT JOIN structure_validations AS sv ON sv.structure_field_id=sf.id WHERE setting like '%coding%'";
$result = $db->query($query) or die("Invalid CodingICD validations failed");
if($row = $result->fetch_assoc()){
	?>
	<table>
		<thead>
			<tr>
				<th>id</th><th>plugin</th><th>model</th><th>field</th><th>setting</th><th>rule</th><th>status</th>
			</tr>
		</thead>
	<?php 
	do{
		$matches = array();
		$code = '';
		preg_match('/Coding(Icd[o]?[\d]+)s\/[\w]+\/([\w]+)$/', $row['setting'], $matches);
		$status = null;
		if($matches){
			if($row['rule']){
				$code = strtoupper($matches[1].$matches[2]);
				if(strpos(strtoupper($row['rule']), $code) == false){
					$status = 'error';
				}else{
					$status = 'ok';
				}
			}else{
				$status = 'validation missing';
			}
		}else{
			$status = '?';
		}
		
		printf('<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', $row['id'], $row['plugin'], $row['model'], $row['field'], $row['setting'], $row['rule'], $status);
	}while($row = $result->fetch_assoc());
	?>
	</table>
	<?php 
}else{
	echo 'No CodingIcd fields';
}
$result->free();
?>

</body>
</html>