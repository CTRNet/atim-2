<?php
require_once("../common/myFunctions.php");
require_once("sqlGeneratorFunctions.php");

global $OVERRIDES_NAMES; 
$OVERRIDES_NAMES = array("language_label" => "flag_override_label", "language_tag" => "flag_override_tag", 
	"language_help" => "flag_override_help", "type" => "flag_override_type", "setting" => "flag_override_setting", 
	"default" => "flag_override_default");
global $STRUCTURE_FIELDS_FIELDS;
$STRUCTURE_FIELDS_FIELDS = array("plugin", "model", "tablename", "field", "type", "structure_value_domain", "flag_confidential", "setting", "default", "language_help", "language_label", "language_tag");
global $STRUCTURE_FIELDS_FIELDS_WO_PLUGIN;
$STRUCTURE_FIELDS_FIELDS_WO_PLUGIN = $STRUCTURE_FIELDS_FIELDS;//all but plugin
array_shift($STRUCTURE_FIELDS_FIELDS_WO_PLUGIN);
global $STRUCTURE_FIELDS_FIELDS_LIGHT;
$STRUCTURE_FIELDS_FIELDS_LIGHT = array_slice($STRUCTURE_FIELDS_FIELDS, 1, 6, false);

global $STRUCTURE_FORMATS_FLAGS;
$STRUCTURE_FORMATS_FLAGS = array("flag_add", "flag_add_readonly", "flag_edit", "flag_edit_readonly", "flag_search", "flag_search_readonly", "flag_addgrid", "flag_addgrid_readonly", "flag_editgrid", "flag_editgrid_readonly", "flag_batchedit", "flag_batchedit_readonly", "flag_index", "flag_detail", "flag_summary", 'flag_float');

$tmp = array();
foreach($OVERRIDES_NAMES as $k => $v){
	$tmp[] = $v;
	$tmp[] = $k;
}
global $STRUCTURE_FORMATS_PRIMARY_FIELDS;
$STRUCTURE_FORMATS_PRIMARY_FIELDS = array("display_column", "display_order", "language_heading", "margin");
global $STRUCTURE_FORMATS_FIELDS;
$STRUCTURE_FORMATS_FIELDS = array_merge($STRUCTURE_FORMATS_PRIMARY_FIELDS, $tmp, $STRUCTURE_FORMATS_FLAGS);

if(isset($_GET['json'])){
	$json = $_GET['json'];
}else if(isset($_POST['json'])){
	$json = $_POST['json'];
}else{
	error_reporting(E_ALL);
	ini_set('display_errors', '1');
	$json = '{"global" : { "alias" : "bob",  "language_title" : ""}, "fields" : [ {  "plugin" : "Clinicalannotation",  "model" : "EventDetail",  "tablename" : "groups",  "field" : "bank_id",  "language_label" : "toto &gt; tata",  "language_tag" : "",  "type" : "number",  "setting" : "",  "default" : "",  "structure_value_domain" : "NULL",  "language_help" : "",  "validation_control" : "",  "value_domain_controld" : "",  "field_control" : "",  "display_column" : "1",  "display_order" : "1",  "language_heading" : "",  "flag_add" : "0",  "flag_add_readonly" : "0",  "flag_edit" : "0",  "flag_edit_readonly" : "0",  "flag_search" : "0",  "flag_search_readonly" : "0", "flag_addgrid" : "0",  "flag_addgrid_readonly" : "0", "flag_editgrid" : "0",  "flag_editgrid_readonly" : "0",  "flag_index" : "0",  "flag_detail" : "0" } ] }';
	define(LS, "<br/>");
}

$json = json_decode(stripslashes($json)) or die("decode failed [".$json."]");
$insertIntoStructures = "INSERT INTO structures(`alias`) VALUES ('".$json->global->alias."');";
$insertIntoStructureFieldsHead = "INSERT INTO structure_fields(`".implode("`, `", $STRUCTURE_FIELDS_FIELDS)."`) VALUES";
$insertIntoStructureFields = "";
$updateStructureField = "UPDATE structure_fields SET `".implode("`=%s, `" , $STRUCTURE_FIELDS_FIELDS)."` WHERE id=%s";
$insertIntoStructureFormatsHead = "INSERT INTO structure_formats(`structure_id`, `structure_field_id`, `".implode($STRUCTURE_FORMATS_FIELDS, "`, `")."`) VALUES ";
$insertIntoStructureFormats = "";
$insertIntoStructureValidationsHead = "INSERT INTO structure_validations (`structure_field_id`, `rule`, `on_action`, `language_message`) ";
$deleteFromStructureFieldArray = array();
$insertIntoStructureValidationsArray = array();
$updateStructureFieldsArray = array();
$updateStructureFormatsArray = array();
$sfoDeleteIgnoreId = array();
$sfOldIds = array();

$structure_id_query = "SELECT id FROM structures WHERE alias='".$json->global->alias."'";
$result = $db->query($structure_id_query) or die("Query failed A ".$db->error);
if($row = $result->fetch_assoc()){
	//structure already exists. This is an update.
	$insertIntoStructures = "";
	$structureId = $row['id'];
}else{
	$structureId = "";
}

foreach($json->fields as $field){
	$sameSfi = getSameSfi($field);
	$similarSfi = getSimilarSfi($field);
	$sfo = (strlen($structureId) > 0 ? getSfo($field) : NULL);
	if($sfo != NULL && $sfo['data']['structure_id'] != $structureId){
		$sfo = NULL;
	}
	if(strlen($field->sfi_id) > 0){
		if($sameSfi['data']['id'] == $field->sfi_id){
			//no change to structure field
			if(strlen($field->sfo_id) > 0 && $sfo != NULL){
				//generate update sfo if necessary
				$str = getUpdateSfo($field, $sameSfi, $sfo, false);
				if(strlen($str) > 0){
					$updateStructureFormatsArray[] = $str;
				}
			}else{
				//new sfo
				$insertIntoStructureFormats .= getInsertIntoSfo($field, $structure_id_query, $sameSfi, $field).LS;
			}
		}else if($sfo != NULL && getFieldUsageCount($field->sfi_id) < 2){
			//we're alone
			//check if a target exists
			$tmp_similar_sfi = getSimilarSfi($field);
			if(count($tmp_similar_sfi['data']) > 0 && $tmp_similar_sfi['data']['id'] != $field->sfi_id){
				//target exists, update our sfo and scrap the old sfi
				$sfoDeleteIgnoreId[] = $field->sfi_id;
				$old_sfi = getStructureFieldById($field->sfi_id);
				$deleteFromStructureFieldArray[] = "model='".$old_sfi['model']."' AND tablename='".$old_sfi['tablename']."' AND field='".$old_sfi['field']."' AND `type`='".$old_sfi['type']."' AND structure_value_domain".castStructureValueDomain($old_sfi['structure_value_domain'], true);
				$str = getUpdateSfo($field, $tmp_similar_sfi, $sfo, true);//update sfo overrides if needed
				if(strlen($str) > 0){
					$updateStructureFormatsArray[] = $str;
				}
				$field->sfi_id = $tmp_similar_sfi['data']['id'];
			}else{
				$updateStructureFieldsArray[] = getUpdateSfi($field);
				if($sfo != NULL){
					$str = getUpdateSfo($field, NULL, $sfo, true);//clear sfo overrides if needed
					if(strlen($str) > 0){
						$updateStructureFormatsArray[] = $str;
					}
				}else{
					//new sfo
					$insertIntoStructureFormats .= getInsertIntoSfo($field, $structure_id_query, $sameSfi, $field).LS;
				}
			}
		}else if($similarSfi['data']['id'] == $field->sfi_id){
			//override is possible
			if($sfo != NULL){
				$str = getUpdateSfo($field, $similarSfi, $sfo, false);
				if(strlen($str) > 0){
					$updateStructureFormatsArray[] = $str;
				}
			}else{
				//new sfo
				$insertIntoStructureFormats .= getInsertIntoSfo($field, $structure_id_query, $similarSfi, $field).LS;
			}
		}else{
			//no way to override, 
			if(!isset($sameSfi['data']['id'])){
				//recreate new sfi , update sfo, copy validations
				$insertIntoStructureFields .= getInsertIntoSfi($field).LS;
			}
			if($sfo != NULL){
				$str = getUpdateSfo($field, NULL, $sfo, false);
				if(strlen($str) > 0){
					$updateStructureFormatsArray[] = $str;
				}
				$tmp = getInsertStructureValidationsIfAny($field);
				if($tmp != null){
					$insertIntoStructureValidationsArray[] = $tmp;
				} 
			}else{
				//new sfo
				$insertIntoStructureFormats .= getInsertIntoSfo($field, $structure_id_query, $sameSfi, $field).LS;
			}
		}
	}else if($sameSfi['data']['id'] != NULL){
			//create sfo without overrides
			$insertIntoStructureFormats .= getInsertIntoSfo($field, $structure_id_query, $sameSfi, $field).LS;
	}else if($similarSfi['data']['id'] != NULL){
			//create sfo with proper overrides
			$insertIntoStructureFormats .= getInsertIntoSfo($field, $structure_id_query, $similarSfi, $field).LS;
	}else{
		//create new sfi + sfo without overrides
		$insertIntoStructureFields .= getInsertIntoSfi($field).LS;
		$insertIntoStructureFormats .= getInsertIntoSfo($field, $structure_id_query, $sameSfi, $field).LS;
	}
}

if(strlen($insertIntoStructures) > 0){
	echo $insertIntoStructures.LS.LS;
}
if(strlen($insertIntoStructureFields) > 0){
	echo substr($insertIntoStructureFieldsHead.LS.$insertIntoStructureFields, 0, -3).";".LS;
}

if(strlen($insertIntoStructureFormats) > 0){
	echo($insertIntoStructureFormatsHead.LS.substr($insertIntoStructureFormats, 0, -3).";".LS);
}

if(count($insertIntoStructureValidationsArray) > 0){
	echo($insertIntoStructureValidationsHead.LS.implode(", ".LS, $insertIntoStructureValidationsArray).";".LS);
}

foreach($updateStructureFieldsArray as $query){
	echo $query."".LS;
}

foreach($updateStructureFormatsArray as $query){
	echo $query.";".LS;
}

$query = "SELECT id FROM structure_formats WHERE structure_id='".$structureId."' AND structure_field_id NOT IN(";
$ids = array();
foreach($json->fields as $field){
	if(is_numeric($field->sfi_id)){
		$sfoDeleteIgnoreId[] = $field->sfi_id;
	}
}
if(count($sfoDeleteIgnoreId) > 0){
	$query .= implode(", ", $sfoDeleteIgnoreId).");".LS;
	$result = $db->query($query) or die("Query failed D ".$db->error. "[".$query."]");
	$sfIds = array();
	while($row = $result->fetch_assoc()){
		$sfIds[] = $row['id'];	
	}
	if(sizeof($sfIds) > 0){
		echo "-- delete structure_formats\n";
		$delete_query = "DELETE FROM structure_formats WHERE ";
		$ignore_keys = array("id", "structure_id", "structure_field_id", "structure_value_domain", "created", "created_by", "modified", "modified_by");//TODO: use a global ignore list
		foreach($sfIds as $sfId){
			$result = $db->query("SELECT structure_id, structure_field_id FROM structure_formats WHERE id='".$sfId."'") or die("Query failed E");
			if($row = $result->fetch_assoc()){
				$s_id = $row['structure_id'];
				$sfi_id = $row['structure_field_id'];
				$result->close();
				
				$result = $db->query("SELECT alias FROM structures WHERE id='".$s_id."'") or die("Query failed E2");
				assert($row = $result->fetch_assoc());
				$delete_query_structure_id = "structure_id=(SELECT id FROM structures WHERE alias='".$row['alias']."')";
				$result->close();
				$result = $db->query("SELECT * FROM structure_fields WHERE id='".$sfi_id."'") or die("Query failed E3");
				if($row = $result->fetch_assoc()){
					$where_part = array();
					foreach($row as $key => $val){
						//NULL values are not possible in that table
						if(!in_array($key, $ignore_keys)){
							$where_part[] = "`".$key."`='".$val."'";
						}else if($key == "structure_value_domain"){
							$where_part[] = "`".$key."`".castStructureValueDomain($val, true);
						}
					}
					echo $delete_query, $delete_query_structure_id, " AND structure_field_id=(SELECT id FROM structure_fields WHERE ", implode(" AND ", $where_part), ");\n";
					if(getFieldUsageCount($sfi_id) == 1){
						//delete from sfi
						$deleteFromStructureFieldArray[] = "". implode(" AND ", $where_part);
					}
				}
				
			}
		}
	}
}

if(count($deleteFromStructureFieldArray) > 0){
	echo "-- Delete obsolete structure fields and validations\n";
	echo "DELETE FROM structure_validations WHERE structure_field_id IN (SELECT id FROM structure_fields WHERE (".implode(") OR (\n", $deleteFromStructureFieldArray)."));\n";
	echo "DELETE FROM structure_fields WHERE (".implode(") OR (\n", $deleteFromStructureFieldArray).");\n";
}


?>
