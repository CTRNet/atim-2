<?php
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
	$json = '{\"global\" : { \"startingOldId\" : \"toto-0001\",  \"alias\" : \"finch_power\",  \"language_title\" : \"\"}, \"fields\" : [ {  \"plugin\" : \"Clinicalannotation\",  \"model\" : \"MiscIdentifier\",  \"tablename\" : \"misc_identifier\",  \"field\" : \"effective_date\",  \"language_label\" : \"effective_date 2\",  \"language_tag\" : \"\",  \"type\" : \"date\",  \"setting\" : \"\",  \"default\" : \"\",  \"structure_value_domain\" : \"NULL\",  \"language_help\" : \"help_effective date\",  \"validation_control\" : \"\",  \"value_domain_controld\" : \"\",  \"field_control\" : \"\",  \"display_column\" : \"0\",  \"display_order\" : \"0\",  \"language_heading\" : \"\",  \"flag_add\" : \"\",  \"flag_add_readonly\" : \"\",  \"flag_edit\" : \"\",  \"flag_edit_readonly\" : \"\",  \"flag_search\" : \"\",  \"flag_search_readonly\" : \"\",  \"flag_datagrid\" : \"\",  \"flag_datagrid_readonly\" : \"\",  \"flag_index\" : \"\",  \"flag_detail\" : \"\" } ] }';
	$lineSeparator = "<br/>";
}
$json = json_decode(stripslashes($json)) or die("decode failed");

$insertIntoStructures = "INSERT INTO structures(`old_id`, `alias`, `language_title`, `language_help`, `flag_add_columns`, `flag_edit_columns`, `flag_search_columns`, `flag_detail_columns`) VALUES ('".$json->global->startingOldId."', '".$json->global->alias."', '".$json->global->language_title."', '', '1', '1', '1', '1');";
$insertIntoStructureFields = "INSERT INTO structure_fields(`public_identifier`, `old_id`, `plugin`, `model`, `tablename`, `field`, `language_label`, `language_tag`, `type`, `setting`, `default`, `structure_value_domain`, `language_help`, `validation_control`, `value_domain_control`, `field_control`) VALUES";
$insertIntoStructureFormatsHead = "INSERT INTO structure_formats(`old_id`, `structure_id`, `structure_old_id`, `structure_field_id`, `structure_field_old_id`, `display_column`, `display_order`, `language_heading`, `flag_override_label`, `language_label`, `flag_override_tag`, `language_tag`, `flag_override_help`, `language_help`, `flag_override_type`, `type`, `flag_override_setting`, `setting`, `flag_override_default`, `default`, `flag_add`, `flag_add_readonly`, `flag_edit`, `flag_edit_readonly`, `flag_search`, `flag_search_readonly`, `flag_datagrid`, `flag_datagrid_readonly`, `flag_index`, `flag_detail`) VALUES ";
$structureOldId = $json->global->startingOldId;
$insertIntoStructureFormatsArray = array();
$sfOldIds = array();

$newFields = false;

$query = "SELECT old_id FROM structures WHERE alias='".$json->global->alias."'";
$result = $mysqli->query($query) or die("Query failed A ".$mysqli->error);
if($row = $result->fetch_assoc()){
	//structure already exists. This is an update.
	$insertIntoStructures = "";
	$structureOldId = $row['old_id'];
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
//	$associationFormatsWhere = array();
//	$associationFormatsUpdate = array();
//	$associationFieldsWhere = array();
//	$associationFieldsUpdate = array();
//	foreach($field as $k => $v){
//		if(isset($structureFormatsFields[$k])){
//			$associationFormatsWhere[] = "`".$k."`=".valueToQueryWherePart($value, false);
//			$associationFormatsUpdate[] = "`".$k."`=".valueToQueryWherePart($value, true);
//		}
//		if(isset($structureFieldsFields[$k])){
//			$associationFieldsWhere[] = "`".$k."`=".valueToQueryWherePart($value, false);
//			$associationFieldsUpdate[] = "`".$k."`=".valueToQueryWherePart($value, true);
//		}
//	}
	//check if a proper structure_field exists
	$query = "SELECT old_id FROM structure_fields WHERE `model`='".$field->model."' AND `tablename`='".$field->tablename."' AND `field`='".$field->field."' AND `language_label`='".$field->language_label."' AND `language_tag`='".$field->language_tag."' AND `type`='".$field->type."' AND `setting`='".$field->setting."' AND `default`='".$field->default."' AND `structure_value_domain` ".valueToQueryWherePart($field->structure_value_domain)." AND `language_help`='".$field->language_help."' ";
	$oldId = "";
	$override = false;
	$insertStructureFormat = true;
	$result = $mysqli->query($query) or die("Query failed B  ".$mysqli->error.$lineSeparator."Query: ".$query); 
	if($row = $result->fetch_assoc()){
		//identical
		$oldId = $row['old_id'];
		$query = "SELECT '1' FROM structure_formats WHERE old_id='".$structureOldId."_".$oldId."' AND structure_id=(SELECT id FROM structures WHERE old_id='".$structureOldId."') AND structure_old_id='".$structureOldId."' AND structure_field_id=(SELECT id FROM structure_fields WHERE old_id='".$oldId."') AND structure_field_old_id='".$oldId."' AND display_column='".$field->display_column."' AND display_order='".$field->display_order."' AND language_heading='".$field->language_heading."' AND flag_override_label='0' AND language_label='' AND flag_override_tag='0' AND language_tag='' AND flag_override_help='0' AND language_help='' AND flag_override_type='0' AND type='' AND flag_override_setting='0' AND setting='' AND flag_override_default='0' AND `default`='' AND flag_add='".$field->flag_add."' AND flag_add_readonly='".$field->flag_add_readonly."' AND flag_edit='".$field->flag_edit."' AND flag_edit_readonly='".$field->flag_edit_readonly."' AND flag_search='".$field->flag_search."' AND flag_search_readonly='".$field->flag_search_readonly."' AND flag_datagrid='".$field->flag_datagrid."' AND flag_datagrid_readonly='".$field->flag_datagrid_readonly."' AND flag_index='".$field->flag_index."' AND flag_detail='".$field->flag_detail."'";
		$result = $mysqli->query($query) or die("Query failed B.1  ".$mysqli->error.$lineSeparator."Query: ".$query);
		if($result->fetch_assoc()){
			//no need to change it
			$insertStructureFormat = false;
		}
	}else{
		$query = "SELECT old_id FROM structure_fields WHERE `model`='".$field->model."' AND `tablename`='".$field->tablename."' AND `field`='".$field->field."' AND `structure_value_domain` ".valueToQueryWherePart($field->structure_value_domain)." ";
		$result = $mysqli->query($query) or die("Query failed C ".$mysqli->error);
		if($row = $result->fetch_assoc()){
			//override
			$oldId = $row['old_id'];
			$override = true;
		}else{
			//doesn't exist at all
			$insertIntoStructureFields .= "('', '".incrementOldId($json->global->startingOldId)."', '".$field->plugin."', '".$field->model."', '".$field->tablename."', '".$field->field."', '".$field->language_label."', '".$field->language_tag."', '".$field->type."', '".$field->setting."', '".$field->default."', ".formatField($field->structure_value_domain).", '".$field->language_help."', 'open', 'open', 'open'), ";
			$oldId = $json->global->startingOldId;
			$newFields = true;
		}
	}
	$query = "SELECT '1' FROM structure_formats WHERE old_id='".$structureOldId."_".$oldId."' AND structure_id=(SELECT id FROM structures WHERE old_id='".$structureOldId."') AND structure_old_id='".$structureOldId."' AND structure_field_id=(SELECT id FROM structure_fields WHERE old_id='".$oldId."') AND structure_field_old_id='".$oldId."'";
	$result = $mysqli->query($query) or die("Query failed B.1  ".$mysqli->error.$lineSeparator."Query: ".$query);
	$update = ($result->fetch_assoc() ? true : false);
	$insertIntoStructureFormats = $insertIntoStructureFormatsHead."('".$structureOldId."_".$oldId."', (SELECT id FROM structures WHERE old_id='".$structureOldId."'), '".$structureOldId."', (SELECT id FROM structure_fields WHERE old_id='".$oldId."'), '".$oldId."', '".$field->display_column."', '".$field->display_order."', '".$field->language_heading."', ";
	$duplicatePart = "";
	$sfOldIds[] = $structureOldId."_".$oldId;
	if($override){
		$insertIntoStructureFormats .= "1, '".$field->language_label."', 1, '".$field->language_tag."', 1, '".$field->language_help."', 1, '".$field->type."', 1, '".$field->setting."', 1, '".$field->default."', ";
		$duplicatePart = "`flag_override_label`='1', `language_label`='".$field->language_label."', `flag_override_tag`='1', `language_tag`='".$field->language_tag."', `flag_override_help`='1', `language_help`='".$field->language_help."', `flag_override_type`='1', `type`='".$field->type."', `flag_override_setting`='1', `setting`='".$field->setting."', `flag_override_default`='1', `default`='".$field->default."' ";
	}else{
		$insertIntoStructureFormats .= "0, '', 0, '', 0, '', 0, '', 0, '', 0, '', ";
		$duplicatePart = "`flag_override_label`='0', `language_label`='', `flag_override_tag`='0', `language_tag`='', `flag_override_help`='0', `language_help`='', `flag_override_type`='0', `type`='', `flag_override_setting`='0', `setting`='', `flag_override_default`='0', `default`='' ";
	}
	$insertIntoStructureFormats .= "'".$field->flag_add."', '".$field->flag_add_readonly."', '".$field->flag_edit."', '".$field->flag_edit_readonly."', '".$field->flag_search."', '".$field->flag_search_readonly."', '".$field->flag_datagrid."', '".$field->flag_datagrid_readonly."', '".$field->flag_index."', '".$field->flag_detail."') ";	
		//."ON DUPLICATE KEY UPDATE 
	$duplicatePart = "display_column='".$field->display_column."', display_order='".$field->display_order."', language_heading='".$field->language_heading."', "
		."`flag_add`='".$field->flag_add."', `flag_add_readonly`='".$field->flag_add_readonly."', `flag_edit`='".$field->flag_edit."', `flag_edit_readonly`='".$field->flag_edit_readonly."', `flag_search`='".$field->flag_search."', `flag_search_readonly`='".$field->flag_search_readonly."', `flag_datagrid`='".$field->flag_datagrid."', `flag_datagrid_readonly`='".$field->flag_datagrid_readonly."', `flag_index`='".$field->flag_index."', `flag_detail`='".$field->flag_detail."', "
		.$duplicatePart;
	if($update){
		$query = "SELECT '1' FROM structure_formats WHERE ".str_replace("', ", "' AND ", $duplicatePart)." AND old_id='".$structureOldId."_".$oldId."' AND structure_id=(SELECT id FROM structures WHERE old_id='".$structureOldId."') AND structure_old_id='".$structureOldId."' AND structure_field_id=(SELECT id FROM structure_fields WHERE old_id='".$oldId."') AND structure_field_old_id='".$oldId."'";
		$result = $mysqli->query($query);
		if(!$result->fetch_assoc()){
			$insertIntoStructureFormatsArray[] = "UPDATE structure_formats SET ".$duplicatePart." WHERE old_id='".$structureOldId."_".$oldId."' AND structure_id=(SELECT id FROM structures WHERE old_id='".$structureOldId."') AND structure_old_id='".$structureOldId."' AND structure_field_id=(SELECT id FROM structure_fields WHERE old_id='".$oldId."') AND structure_field_old_id='".$oldId."'";
		}
	}else{
		$insertIntoStructureFormatsArray[] = $insertIntoStructureFormats; 
	}
//"INSERT INTO structure_formats(`old_id`, `structure_id`, `structure_old_id`, `structure_field_id`, `structure_field_old_id`, `display_column`, `display_order`, `language_heading`, 
//`flag_override_label`, `language_label`, `flag_override_tag`, `language_tag`, `flag_override_help`, `language_help`, `flag_override_type`, `type`, `flag_override_setting`, `setting`, `flag_override_default`, `default`, 
//`flag_add`, `flag_add_readonly`, `flag_edit`, `flag_edit_readonly`, `flag_search`, `flag_search_readonly`, `flag_datagrid`, `flag_datagrid_readonly`, `flag_index`, `flag_detail`) VALUES ";
}
echo $insertIntoStructures.$lineSeparator.$lineSeparator;
if($newFields){
	echo substr($insertIntoStructureFields, 0, strlen($insertIntoStructureFields) - 2).";".$lineSeparator.$lineSeparator;
}
foreach($insertIntoStructureFormatsArray as $query){
	echo $query.";".$lineSeparator.$lineSeparator;
}

$query = "SELECT old_id FROM structure_formats WHERE structure_id=(SELECT id FROM structures WHERE old_id='".$structureOldId."') AND old_id NOT IN(";
foreach($sfOldIds as $sfOldId){
	$query .= "'".$sfOldId."', ";
}
$query = substr($query, 0, strlen($query) - 2).");".$lineSeparator;
$result = $mysqli->query($query) or die("Query failed D ".$mysqli->error);
$sfOldIds = array();
if($row = $result->fetch_assoc()){
	$sfOldIds[] = $row['old_id'];	
}

if(sizeof($sfOldIds) > 0){
	$query = "DELETE FROM structure_formats WHERE old_id IN(";
	foreach($sfOldIds as $sfOldId){
		$query .= "'".$sfOldId."', ";
	}
	$query = substr($query, 0, strlen($query) - 2).");".$lineSeparator.$lineSeparator;
	echo $query;
}






function incrementOldId(&$oldId){
	if(is_numeric($oldId)){
		return ++ $oldId;
	}else if(strrpos($oldId, "-") > -1){
		$prefix = substr($oldId, 0, strrpos($oldId, "-") + 1);
		$oldId = substr($oldId, strrpos($oldId, "-") + 1);
		$valLength = strlen($oldId);
		$oldId += 1;
		while(strlen($oldId) < $valLength){
			$oldId = "0".$oldId;
		}
		$oldId = $prefix.$oldId;
		return $oldId;
	}
	return "ERROR";	
}

function formatField($field){
	if(strtoupper($field) == "NULL" || strpos(strtoupper($field), 'SELECT') > -1){
		return $field;
	}
	return "'".$field."'";
}

function valueToQueryWherePart($value, $update = false){
	$result = "";
	if(strtoupper($value) == "NULL"){
		if($update){
			$result = " NULL ";
		}else{
			$result = " IS NULL ";
		}	
	}else if(strpos(strtoupper($value), "SELECT") > 0){
		$result = "=".$value." ";	
	}else{
		$result = "='".$value."' ";
	}
	return $result;
}
?>