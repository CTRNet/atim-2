<?php
require_once("../common/myFunctions.php");
define("LS", "\n");
global $OVERRIDES_NAMES; 
$OVERRIDES_NAMES = array("language_label" => "flag_override_label", "language_tag" => "flag_override_tag", 
	"language_help" => "flag_override_help", "type" => "flag_override_type", "setting" => "flag_override_setting", 
	"default" => "flag_override_default");
global $STRUCTURE_FIELDS_FIELDS;
$STRUCTURE_FIELDS_FIELDS = array("plugin", "model", "tablename", "field", "language_label", "language_tag", "type", "setting", "default", "structure_value_domain", "language_help", "validation_control", "field_control");
global $STRUCTURE_FORMATS_FIELDS;
$STRUCTURE_FORMATS_FIELDS = array("display_column", "display_order", "language_heading", "language_label", "language_tag", "language_help", "type", "setting", "default", "flag_add", "flag_add_readonly", "flag_edit", "flag_edit_readonly", "flag_search", "flag_search_readonly", "flag_datagrid", "flag_datagrid_readonly", "flag_index", "flag_detail");

if(isset($_GET['json'])){
	$json = $_GET['json'];
}else if(isset($_POST['json'])){
	$json = $_POST['json'];
}else{
	error_reporting(E_ALL);
	ini_set('display_errors', '1');
	$json = '{"global" : { "alias" : "bob",  "language_title" : ""}, "fields" : [ {  "plugin" : "Clinicalannotation",  "model" : "EventDetail",  "tablename" : "groups",  "field" : "bank_id",  "language_label" : "toto &gt; tata",  "language_tag" : "",  "type" : "number",  "setting" : "",  "default" : "",  "structure_value_domain" : "NULL",  "language_help" : "",  "validation_control" : "",  "value_domain_controld" : "",  "field_control" : "",  "display_column" : "1",  "display_order" : "1",  "language_heading" : "",  "flag_add" : "0",  "flag_add_readonly" : "0",  "flag_edit" : "0",  "flag_edit_readonly" : "0",  "flag_search" : "0",  "flag_search_readonly" : "0",  "flag_datagrid" : "0",  "flag_datagrid_readonly" : "0",  "flag_index" : "0",  "flag_detail" : "0" } ] }';
	define(LS, "<br/>");
}

$json = json_decode(stripslashes($json)) or die("decode failed [".$json."]");
$insertIntoStructures = "INSERT INTO structures(`alias`, `language_title`, `language_help`, `flag_add_columns`, `flag_edit_columns`, `flag_search_columns`, `flag_detail_columns`) VALUES ('".$json->global->alias."', '".$json->global->language_title."', '', '1', '1', '1', '1');";
$insertIntoStructureFieldsHead = "INSERT INTO structure_fields(`public_identifier`, `plugin`, `model`, `tablename`, `field`, `language_label`, `language_tag`, `type`, `setting`, `default`, `structure_value_domain`, `language_help`, `validation_control`, `value_domain_control`, `field_control`) VALUES";
$insertIntoStructureFields = "";
$updateStructureField = "UPDATE structure_fields SET `public_identifier`=%s, `plugin`=%s, `model`=%s, `tablename`=%s, `field`=%s, `language_label`=%s, `language_tag`=%s, `type`=%s, `setting`=%s, `default`=%s, `structure_value_domain`=%s, `language_help`=%s, `validation_control`=%s, `value_domain_control`=%s, `field_control`=%s WHERE id=%s";
$insertIntoStructureFormatsHead = "INSERT INTO structure_formats(`structure_id`, `structure_field_id`, `display_column`, `display_order`, `language_heading`, `flag_override_label`, `language_label`, `flag_override_tag`, `language_tag`, `flag_override_help`, `language_help`, `flag_override_type`, `type`, `flag_override_setting`, `setting`, `flag_override_default`, `default`, `flag_add`, `flag_add_readonly`, `flag_edit`, `flag_edit_readonly`, `flag_search`, `flag_search_readonly`, `flag_datagrid`, `flag_datagrid_readonly`, `flag_index`, `flag_detail`) VALUES ";
$insertIntoStructureFormats = "";
$insertIntoStructureValidationsHead = "INSERT INTO structure_validations (`structure_field_id`, `rule`, `flag_empty`, `flag_required`, `on_action`, `language_message`) ";
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
	if(strlen($field->sfi_id) > 0){
		if($sameSfi['data']['id'] == $field->sfi_id){
			//no change to structure field
			if(strlen($field->sfo_id) > 0 && $sfo != NULL){
				//generate update sfo if necessary
				$str = getUpdateSfo($field, $sameSfi, $sfo);
				if(strlen($str) > 0){
					$updateStructureFormatsArray[] = $str;
				}
			}else{
				//new sfo
				$insertIntoStructureFormats .= getInsertIntoSfo($field, $structure_id_query, $sameSfi, $field).LS;
			}
		}else if(getFieldUsageCount($field->sfi_id) < 2){
			//we're alone
			//check if a target exists
			$tmp_similar_sfi = getSimilarSfi($field);
			if(count($tmp_similar_sfi) > 0){
				//target exists, update our sfo and scrap the old sfi
				$sfoDeleteIgnoreId[] = $field->sfi_id;
				$old_sfi = getStructureFieldById($field->sfi_id);
				$deleteFromStructureFieldArray[] = "DELETE FROM structure_fields WHERE model='".$old_sfi['model']."' AND tablename='".$old_sfi['tablename']."' AND field='".$old_sfi['field']."' AND `type`='".$old_sfi['type']."' AND structure_value_domain".castStructureValueDomain($old_sfi['structure_value_domain'], true).")";
				$str = getUpdateSfo($field, $tmp_similar_sfi, $sfo, false);//clear sfo overrides if needed
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
				$str = getUpdateSfo($field, $similarSfi, $sfo);
				if(strlen($str) > 0){
					$updateStructureFormatsArray[] = $str;
				}
			}else{
				//new sfo
				$insertIntoStructureFormats .= getInsertIntoSfo($field, $structure_id_query, $sameSfi, $field).LS;
			}
		}else{
			//no way to override, 
			if(!isset($sameSfi['data']['id'])){
				//recreate new sfi , update sfo, copy validations
				$insertIntoStructureFields .= getInsertIntoSfi($field).LS;
			}
			if($sfo != NULL){
				$str = getUpdateSfo($field, NULL, $sfo);
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
	$sfoDeleteIgnoreId[] = $field->sfi_id;
}
$query .= implode(", ", $sfoDeleteIgnoreId).");".LS;
$result = $db->query($query) or die("Query failed D ".$db->error);
$sfIds = array();
while($row = $result->fetch_assoc()){
	$sfIds[] = $row['id'];	
}
if(sizeof($sfIds) > 0){
	echo "-- delete structure_formats\n";
	$delete_query = "DELETE FROM structure_formats WHERE ";
	foreach($sfIds as $sfId){
		$result = $db->query("SELECT * FROM structure_formats WHERE id='".$sfId."'") or die("Query failed E");
		if($row = $result->fetch_assoc()){
			$where_part = "";
			foreach($row as $key => $val){
				//NULL values are not possible in that table
				if($key != 'id' && $key != 'structure_id' && $key != 'structure_field_id'){
					$where_part .= "`".$key."`='".$val."' AND ";
				}
			}
			echo($delete_query.substr($where_part, 0, -4).";\n");
		}
	}
}

if(count($deleteFromStructureFieldArray) > 0){
	echo("-- Delete obsolete structure fields\n");
	echo(implode("\n", $deleteFromStructureFieldArray)."\n");
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
		global $db;
		$result = $db->query("SELECT domain_name FROM structure_value_domains WHERE id='".$value."'") or die("castStructureValueDomain query failed");
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
		if(strlen($value) == 0){
			$value = "NULL";
		}
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

/**
 * Generates a query UPDATE part to bring row to data on the fields array
 * @param unknown_type $fields The fields to check
 * @param unknown_type $row The current row
 * @param unknown_type $data The targetted data
 */
function getUpdateQuery($fields, $row, $data){
//	print_r($data);
	$result = "";
	foreach($fields as $field){
		if($row[$field] != $data->{$field} && !($row[$field] == NULL && $data->{$field} == "NULL")){
			$result .= " `".$field."`=";
			if($field == "structure_value_domain"){
				$result .= castStructureValueDomain($data->structure_value_domain, false);
			}else if($data->{$field} == "NULL"){
				$result .= "NULL";
			}else{
				$result .= "'".$data->{$field}."'";
			}
			$result .=", ";
		}
	}
	return substr($result, 0, -2);
}

function getSameSfi($field){
	$query = "FROM structure_fields WHERE `model`='".$field->model."' AND `tablename`='".$field->tablename."' AND `field`='".$field->field."' AND `language_label`='".$field->language_label."' AND `language_tag`='".$field->language_tag."' AND `type`='".$field->type."' AND `setting`='".$field->setting."' AND `default`='".$field->default."' AND `structure_value_domain` ".castStructureValueDomain($field->structure_value_domain, true)." AND `language_help`='".$field->language_help."'";
	$query_id = "SELECT id ".$query;
	$query_all = "SELECT * ".$query;
	$query_id_light = "SELECT id FROM structure_fields WHERE `model`='".$field->model."' AND `tablename`='".$field->tablename."' AND `field`='".$field->field."' AND `type`='".$field->type."' AND `structure_value_domain` ".castStructureValueDomain($field->structure_value_domain, true);
	return array("query_id" => $query_id, "query_id_light" => $query_id_light, "data" => getDataFromQuery($query_all));
}

function getSimilarSfi($field){
	$query = "FROM structure_fields WHERE `model`='".$field->model."' AND `tablename`='".$field->tablename."' AND `field`='".$field->field."' AND `type`='".$field->type."' AND `structure_value_domain` ".castStructureValueDomain($field->structure_value_domain, true)." ";
	$query_id = "SELECT id ".$query;
	$query_all = "SELECT * ".$query;
	return array("query_id" => $query_id, "data" => getDataFromQuery($query_all));
}

function getSfo($field){
	$sfoData = getDataFromQuery("SELECT * FROM structure_formats WHERE id='".$field->sfo_id."'");
	$structureData = getDataFromQuery("SELECT * FROM structures WHERE id='".$sfoData['structure_id']."'");
	$structure_id_query = "SELECT id FROM structures WHERE alias='".$structureData['alias']."'";
	$sfiData =  getDataFromQuery("SELECT * FROM structure_fields WHERE id='".$sfoData['structure_field_id']."'");
	$structure_field_id_query = "SELECT id FROM structure_fields WHERE model='".$sfiData['model']."' AND tablename='".$sfiData['tablename']."' AND field='".$sfiData['field']."' AND type='".$sfiData['type']."' AND structure_value_domain ".castStructureValueDomain($sfiData['structure_value_domain'], true);
	return array("query_id" => "SELECT id FROM structure_formats WHERE structure_id=(".$structure_id_query.") AND structure_field_id=(".$structure_field_id_query.")", 
		'where' => " structure_id=(".$structure_id_query.") AND structure_field_id=(".$structure_field_id_query.") ", 
		'data' => $sfoData);
}

function getDataFromQuery($query){
	global $db;
	$result = $db->query($query) or die("Query failed getIdFromQuery  ".$db->error.LS."Query: ".$query);
	$data = NULL;
	if($row = $result->fetch_assoc()){
		$data = $row;
	}
	$result->close();
	return $data;
}

function getInsertIntoSfi($field){
	return "('', '".$field->plugin."', '".$field->model."', '".$field->tablename."', '".$field->field."', '".$field->language_label."', '".$field->language_tag."', '".$field->type."', '".$field->setting."', '".$field->default."', ".castStructureValueDomain($field->structure_value_domain, false).", '".$field->language_help."', 'open', 'open', 'open'), ";
}

function getInsertIntoSfo($field, $structure_id_query, $structure_field){
	global $OVERRIDES_NAMES;
	$query = "((".$structure_id_query."), (".$structure_field['query_id']."), '".$field->display_column."', '".$field->display_order."', '".$field->language_heading."', ";
	//look to override properly
	foreach($OVERRIDES_NAMES as $override_name => $override_flag){
		if(!isset($structure_field['data'][$override_name]) || $structure_field['data'][$override_name] == $field->{$override_name}){
			$query .= "'0', '', ";
		}else{
			$query .= "'1', '".$field->{$override_name}."', ";
		}
	}
	$query .= "'".$field->flag_add."', '".$field->flag_add_readonly."', '".$field->flag_edit."', '".$field->flag_edit_readonly."', '".$field->flag_search."', '".$field->flag_search_readonly."', '".$field->flag_datagrid."', '".$field->flag_datagrid_readonly."', '".$field->flag_index."', '".$field->flag_detail."'), ";
	return $query;
}

function getUpdateSfo($field, $sfi, $sfo, $ignore_sfo_sfi_id_update = false){
	global $STRUCTURE_FORMATS_FIELDS;
	global $OVERRIDES_NAMES;
	$query = "";
	foreach($STRUCTURE_FORMATS_FIELDS as $sfo_field){
		if(isset($OVERRIDES_NAMES[$sfo_field])){
			//overriden fields
			if($sfi == NULL || $field->{$sfo_field} == $sfi['data'][$sfo_field]){
				//same value, no need for override
				if($sfo['data'][$OVERRIDES_NAMES[$sfo_field]] == "1"){
					//cancel existing override
					$query .= "`".$OVERRIDES_NAMES[$sfo_field]."`='0', `".$sfo_field."`='', ";
				}
			}else{
				//different value, we need an override
				if($sfo['data'][$OVERRIDES_NAMES[$sfo_field]] != "1" || $sfo['data'][$sfo_field] != $field->{$sfo_field}){
					//override non existent, set it
					$query .= "`".$OVERRIDES_NAMES[$sfo_field]."`='1', `".$sfo_field."`='".$field->{$sfo_field}."', ";
				}
			}
		}else{
			//standard fields
			if($field->{$sfo_field} != $sfo['data'][$sfo_field]){
				$query .= "`".$sfo_field."`='".$field->{$sfo_field}."', ";
			}
		}
	}
	//TODO: compare structure id and update if necessary
	//compare structure_field_id and update if necessary
	if(!$ignore_sfo_sfi_id_update && (($sfi != NULL && $field->sfi_id != $sfi['data']['id']) || $sfi == NULL && $field->sfi_id > 0)){
		$query = "`structure_field_id`=(SELECT `id` FROM structure_fields WHERE `model`='".$field->model."' AND `tablename`='".$field->tablename."' AND `field`='".$field->field."' AND `type`='".$field->type."' AND `structure_value_domain`".castStructureValueDomain($field->structure_value_domain, true)."), ";
	}
	if(strlen($query) > 0){
		$query = "UPDATE structure_formats SET ".substr($query, 0, -2)." WHERE ".trim($sfo['where']); 
	}
	return $query;
}

function getFieldUsageCount($field_id){
	global $db;
	$query = "SELECT count(*) AS c FROM structure_formats WHERE structure_field_id='".$field_id."'";
	$result = $db->query($query) or die("exec getFieldUsageCount failed");
	$count = 0;
	if($row = $result->fetch_assoc()){
		$count = $row['c'];
	}
	$result->close();
	return $count;
}

function getUpdateSfi($field){
	global $STRUCTURE_FIELDS_FIELDS;
	$sfiData = getDataFromQuery("SELECT * FROM structure_fields WHERE id='".$field->sfi_id."'");
	return "UPDATE structure_fields SET ".getUpdateQuery($STRUCTURE_FIELDS_FIELDS, $sfiData, $field)
		." WHERE model='".$sfiData['model']."' AND tablename='".$sfiData['tablename']."' AND field='".$sfiData['field']."' AND `type`='".$sfiData['type']."' AND structure_value_domain ".castStructureValueDomain($sfiData['structure_value_domain'], true).";"; 
}

function getInsertStructureValidationsIfAny($field){
	global $db;
	$sfiData = getDataFromQuery("SELECT * FROM structure_fields WHERE id='".$field->sfi_id."'");
	$query = "(SELECT (SELECT id FROM structure_fields WHERE model='".$field->model."' AND tablename='".$field->tablename."' AND field='".$field->field."' AND `type`='".$field->type."' AND structure_value_domain".castStructureValueDomain($field->structure_value_domain, true)."), `rule`, `flag_empty`, `flag_required`, `on_action`, `language_message` FROM structure_validations "
		."WHERE structure_field_id=(SELECT id FROM structure_fields WHERE model='".$sfiData['model']."' AND tablename='".$sfiData['tablename']."' AND field='".$sfiData['field']."' AND `type`='".$sfiData['type']."' AND structure_value_domain ".castStructureValueDomain($sfiData['structure_value_domain'], true).")) ";
	$result = $db->query($query) or die("getInsertStructureValidationsIfAny query failed ");
	if($result->num_rows == 0){
		$query = null;
	}	
	return $query;
}

function getStructureFieldById($id){
	$query = "SELECT * FROM structure_fields WHERE id=".$id;
	return getDataFromQuery($query);
}
?>
