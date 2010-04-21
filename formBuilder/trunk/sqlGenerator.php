<?php
//TODO: do not return the id for structure_value_domain
require_once("myFunctions.php");
$lineSeparator = "\n";
$structureFieldsFields = array("public_identifier", "old_id", "plugin", "model", "tablename", "field", "language_label", "language_tag", "type", "setting", "default", "structure_value_domain", "language_help", "validation_control", "value_domain_control", "field_control");
$structureFormatsFieds = array("old_id", "structure_id", "structure_old_id", "structure_field_id", "structure_field_old_id", "display_column", "display_order", "language_heading", "flag_override_label", "language_label", "flag_override_tag", "language_tag", "flag_override_help", "language_help", "flag_override_type", "type", "flag_override_setting", "setting", "flag_override_default", "default", "flag_add", "flag_add_readonly", "flag_edit", "flag_edit_readonly", "flag_search", "flag_search_readonly", "flag_datagrid", "flag_datagrid_readonly", "flag_index", "flag_detail");


if(isset($_GET['json'])){
	$json = $_GET['json'];
}else if(isset($_POST['json'])){
	$json = $_POST['json'];
}else{
	error_reporting(E_ALL);
	ini_set('display_errors', '1');
	$json = '{"global" : { "alias" : "bob",  "language_title" : ""}, "fields" : [ {  "plugin" : "Clinicalannotation",  "model" : "EventDetail",  "tablename" : "groups",  "field" : "bank_id",  "language_label" : "toto &gt; tata",  "language_tag" : "",  "type" : "number",  "setting" : "",  "default" : "",  "structure_value_domain" : "NULL",  "language_help" : "",  "validation_control" : "",  "value_domain_controld" : "",  "field_control" : "",  "display_column" : "1",  "display_order" : "1",  "language_heading" : "",  "flag_add" : "0",  "flag_add_readonly" : "0",  "flag_edit" : "0",  "flag_edit_readonly" : "0",  "flag_search" : "0",  "flag_search_readonly" : "0",  "flag_datagrid" : "0",  "flag_datagrid_readonly" : "0",  "flag_index" : "0",  "flag_detail" : "0" } ] }';
	$lineSeparator = "<br/>";
}

$json = json_decode(stripslashes($json)) or die("decode failed ".$json);
$insertIntoStructures = "INSERT INTO structures(`alias`, `language_title`, `language_help`, `flag_add_columns`, `flag_edit_columns`, `flag_search_columns`, `flag_detail_columns`) VALUES ('".$json->global->alias."', '".$json->global->language_title."', '', '1', '1', '1', '1');";
$insertIntoStructureFields = "INSERT INTO structure_fields(`public_identifier`, `plugin`, `model`, `tablename`, `field`, `language_label`, `language_tag`, `type`, `setting`, `default`, `structure_value_domain`, `language_help`, `validation_control`, `value_domain_control`, `field_control`) VALUES";
$insertIntoStructureFormatsHead = "INSERT INTO structure_formats(`structure_id`, `structure_field_id`, `display_column`, `display_order`, `language_heading`, `flag_override_label`, `language_label`, `flag_override_tag`, `language_tag`, `flag_override_help`, `language_help`, `flag_override_type`, `type`, `flag_override_setting`, `setting`, `flag_override_default`, `default`, `flag_add`, `flag_add_readonly`, `flag_edit`, `flag_edit_readonly`, `flag_search`, `flag_search_readonly`, `flag_datagrid`, `flag_datagrid_readonly`, `flag_index`, `flag_detail`) VALUES ";
$structureOldId = $json->global->startingOldId;
$insertIntoStructureFormatsArray = array();
$sfOldIds = array();

$newFields = false;

$structure_id_query = "SELECT id FROM structures WHERE alias='".$json->global->alias."'";
$result = $mysqli->query($structure_id_query) or die("Query failed A ".$mysqli->error);
if($row = $result->fetch_assoc()){
	//structure already exists. This is an update.
	$insertIntoStructures = "";
	$structureId = $row['id'];
}

//Tactics
//#1: See if that strutures field exists precisely as we need it
//	->Yes: Use it and use format right on it
//#2: Otherwise see if there is a similar one
//	-> Yes: Use it and use format with overrides
//#3: Otherwise create it and use format right on it.
//#4: Delete all structure_formats of the current structures that have not been created/updated here 
//FTW comrad! 
foreach($json->fields as $field){
	//building associations
	//check if a proper structure_field exists
	$structure_field_id_query = "SELECT id FROM structure_fields WHERE `model`='".$field->model."' AND `tablename`='".$field->tablename."' AND `field`='".$field->field."' AND `language_label`='".$field->language_label."' AND `language_tag`='".$field->language_tag."' AND `type`='".$field->type."' AND `setting`='".$field->setting."' AND `default`='".$field->default."' AND `structure_value_domain` ".castStructureValueDomain($field->structure_value_domain, true)." AND `language_help`='".$field->language_help."' "; 
	$id = "";
	$override = false;
	$insertStructureFormat = true;
	$result = $mysqli->query($structure_field_id_query) or die("Query failed B  ".$mysqli->error.$lineSeparator."Query: ".$structure_field_id_query); 
	if($row = $result->fetch_assoc()){
		//identical
		$id = $row['id'];
		$query = "SELECT '1' FROM structure_formats WHERE structure_id='".$structureId."' AND structure_field_id='".$id."' AND display_column='".$field->display_column."' AND display_order='".$field->display_order."' AND language_heading='".$field->language_heading."' AND flag_override_label='0' AND language_label='' AND flag_override_tag='0' AND language_tag='' AND flag_override_help='0' AND language_help='' AND flag_override_type='0' AND type='' AND flag_override_setting='0' AND setting='' AND flag_override_default='0' AND `default`='' AND flag_add='".$field->flag_add."' AND flag_add_readonly='".$field->flag_add_readonly."' AND flag_edit='".$field->flag_edit."' AND flag_edit_readonly='".$field->flag_edit_readonly."' AND flag_search='".$field->flag_search."' AND flag_search_readonly='".$field->flag_search_readonly."' AND flag_datagrid='".$field->flag_datagrid."' AND flag_datagrid_readonly='".$field->flag_datagrid_readonly."' AND flag_index='".$field->flag_index."' AND flag_detail='".$field->flag_detail."'";
		$result = $mysqli->query($query) or die("Query failed B.1a  ".$mysqli->error.$lineSeparator."Query: ".$query);
		if($result->fetch_assoc()){
			//no need to change it
			$insertStructureFormat = false;
		}
	}else{
		$structure_field_id_query = "SELECT id FROM structure_fields WHERE `model`='".$field->model."' AND `tablename`='".$field->tablename."' AND `field`='".$field->field."' AND `structure_value_domain` ".castStructureValueDomain($field->structure_value_domain, true)." ";
		$result = $mysqli->query($structure_field_id_query) or die("Query failed C ".$mysqli->error);
		if($row = $result->fetch_assoc()){
			//override
			$id = $row['id'];
			$override = true;
		}else{
			//doesn't exist at all
			$insertIntoStructureFields .= "('', '".$field->plugin."', '".$field->model."', '".$field->tablename."', '".$field->field."', '".$field->language_label."', '".$field->language_tag."', '".$field->type."', '".$field->setting."', '".$field->default."', ".castStructureValueDomain($field->structure_value_domain, false).", '".$field->language_help."', 'open', 'open', 'open'), ";
			$newFields = true;
		}
	}
	$query = "SELECT '1' FROM structure_formats WHERE structure_id='".$structureId."' AND structure_field_id='".$id."'";
	$result = $mysqli->query($query) or die("Query failed B.1b  ".$mysqli->error.$lineSeparator."Query: ".$query);
	$update = ($result->fetch_assoc() ? true : false);
	$insertIntoStructureFormats = $insertIntoStructureFormatsHead."((".$structure_id_query."), (".$structure_field_id_query."), '".$field->display_column."', '".$field->display_order."', '".$field->language_heading."', ";
	$duplicatePart = "";
	$sfIds[] = $structureId."_".$id;
	if($override){
		$insertIntoStructureFormats .= "1, '".$field->language_label."', 1, '".$field->language_tag."', 1, '".$field->language_help."', 1, '".$field->type."', 1, '".$field->setting."', 1, '".$field->default."', ";
		$duplicatePart = "`flag_override_label`='1', `language_label`='".$field->language_label."', `flag_override_tag`='1', `language_tag`='".$field->language_tag."', `flag_override_help`='1', `language_help`='".$field->language_help."', `flag_override_type`='1', `type`='".$field->type."', `flag_override_setting`='1', `setting`='".$field->setting."', `flag_override_default`='1', `default`='".$field->default."' ";
	}else{
		$insertIntoStructureFormats .= "'0', '', '0', '', '0', '', '0', '', '0', '', '0', '', ";
		$duplicatePart = "`flag_override_label`='0', `language_label`='', `flag_override_tag`='0', `language_tag`='', `flag_override_help`='0', `language_help`='', `flag_override_type`='0', `type`='', `flag_override_setting`='0', `setting`='', `flag_override_default`='0', `default`='' ";
	}
	$insertIntoStructureFormats .= "'".$field->flag_add."', '".$field->flag_add_readonly."', '".$field->flag_edit."', '".$field->flag_edit_readonly."', '".$field->flag_search."', '".$field->flag_search_readonly."', '".$field->flag_datagrid."', '".$field->flag_datagrid_readonly."', '".$field->flag_index."', '".$field->flag_detail."') ";	
		//."ON DUPLICATE KEY UPDATE 
	$duplicatePart = "display_column='".$field->display_column."', display_order='".$field->display_order."', language_heading='".$field->language_heading."', "
		."`flag_add`='".$field->flag_add."', `flag_add_readonly`='".$field->flag_add_readonly."', `flag_edit`='".$field->flag_edit."', `flag_edit_readonly`='".$field->flag_edit_readonly."', `flag_search`='".$field->flag_search."', `flag_search_readonly`='".$field->flag_search_readonly."', `flag_datagrid`='".$field->flag_datagrid."', `flag_datagrid_readonly`='".$field->flag_datagrid_readonly."', `flag_index`='".$field->flag_index."', `flag_detail`='".$field->flag_detail."', "
		.$duplicatePart;
	if($update){
		$query = "SELECT '1' FROM structure_formats WHERE ".str_replace("', ", "' AND ", $duplicatePart)." AND structure_id=(".$structure_id_query.") AND structure_field_id=(".$structure_field_id_query.")";
		$result = $mysqli->query($query);
		echo $query."\n\n";
		if(!$result->fetch_assoc()){
			$insertIntoStructureFormatsArray[] = "UPDATE structure_formats SET ".$duplicatePart." WHERE structure_id=(".$structure_id_query.") AND structure_field_id=(".$structure_field_id_query.")";
		}
	}else{
		$insertIntoStructureFormatsArray[] = $insertIntoStructureFormats; 
	}
}
echo $insertIntoStructures.$lineSeparator.$lineSeparator;
if($newFields){
	echo substr($insertIntoStructureFields, 0, strlen($insertIntoStructureFields) - 2).";".$lineSeparator.$lineSeparator;
}
foreach($insertIntoStructureFormatsArray as $query){
	echo $query.";".$lineSeparator.$lineSeparator;
}

$query = "SELECT id FROM structure_formats WHERE structure_id='".$structureId."' AND structure_field_id NOT IN(";
foreach($sfIds as $sfId){
	$tmp = explode("_", $sfId);
	$query .= "'".$tmp[1]."', ";
}
$query = substr($query, 0, strlen($query) - 2).");".$lineSeparator;
$result = $mysqli->query($query) or die("Query failed D ".$mysqli->error);
$sfIds = array();
while($row = $result->fetch_assoc()){
	$sfIds[] = $row['id'];	
}
if(sizeof($sfIds) > 0){
	echo "-- delete structure_formats\n";
	$delete_query = "DELETE FROM structure_formats WHERE ";
	foreach($sfIds as $sfId){
		$result = $mysqli->query("SELECT * FROM structure_fields WHERE id='".$sfId."'") or die("Query failed E");
		if($row = $result->fetch_assoc()){
			echo $where_part = "";
			foreach($row as $key => $val){
				//NULL values are not possible in that table
				if($key != 'id'){
					$where_part .= $key."='".$val."' AND ";
				}
			}
			echo($delete_query.substr($where_part, 0, -4).";\n");
		}
	}
}

function formatField($field){
	if(strtoupper($field) == "NULL" || strpos(strtoupper($field), 'SELECT') > -1){
		return $field;
	}
	return "'".$field."'";
}

function castStructureValueDomain($value, $where){
	$q_result = "";
	if(is_numeric($value)){
		global $mysqli;
		$result = $mysqli->query("SELECT domain_name FROM structure_value_domains WHERE id='".$value."'") or die("castStructureValueDomain query failed");
		if($row = $result->fetch_assoc()){
			if($where){
				$q_result = "=";
			}
			$q_result .= "(SELECT id FROM structure_value_domains WHERE domain_name='".$row['domain_name']."')";
		}else{
			//invalid! DIE!!!
			die("Invalid structure_value_domain_id [".$value."]\n");
		}
		$result->close();
	}else{
		$q_result = valueToQueryWherePart($value, $where);
	}
	return $q_result;
}

function valueToQueryWherePart($value, $where = true){
	$q_result = "";
	if(strtoupper($value) == "NULL"){
		if($where){
			$q_result = " IS NULL ";
		}else{
			$q_result = " NULL ";
		}	
	}else{
		if($where){
			$q_result = "=";
		}	
		if(strpos(strtoupper($value), "SELECT") > 0){
			$q_result .= $value." ";
		}else{
			$q_result .= "(SELECT id FROM structure_value_domains WHERE domain_name='".$value."') ";
		}
	}
	return $q_result;
}
?>