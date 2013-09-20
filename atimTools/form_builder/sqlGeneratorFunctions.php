<?php
define("LS", "\n");

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
	global $STRUCTURE_FIELDS_FIELDS_WO_PLUGIN;
	global $STRUCTURE_FIELDS_FIELDS_LIGHT;
	$tmp = array();
	foreach($STRUCTURE_FIELDS_FIELDS_WO_PLUGIN AS $sf){
		if($sf == "structure_value_domain"){
			$tmp[] = "`structure_value_domain` ".castStructureValueDomain($field->structure_value_domain, true); 
		}else{
			$tmp[] = sprintf("`%s`='%s'", $sf, addslashes($field->{$sf}));
		}
	}
	$query = "FROM structure_fields WHERE ".implode(" AND ", $tmp);
	
	$query_id = "SELECT id ".$query;
	$query_all = "SELECT * ".$query;
	
	$tmp = array();
	foreach($STRUCTURE_FIELDS_FIELDS_LIGHT as $sf){
		if($sf == "structure_value_domain"){
			$tmp[] = "`structure_value_domain` ".castStructureValueDomain($field->structure_value_domain, true); 
		}else{
			$tmp[] = sprintf("`%s`='%s'", $sf, $field->{$sf});
		}
	}
	$query_id_light = "SELECT id FROM structure_fields WHERE ".implode(" AND ", $tmp);
	return array("query_id" => $query_id, "query_id_light" => $query_id_light, "data" => getDataFromQuery($query_all));
}

function getSelectSfiQuery($field){
	global $STRUCTURE_FIELDS_FIELDS_LIGHT;
	$tmp = array();
	foreach($STRUCTURE_FIELDS_FIELDS_LIGHT as $sf){
		$val = is_array($field) ? $field[$sf] : $field->{$sf};
		if($sf == "type"){
			continue;
		}
		if($sf == "structure_value_domain"){
			$tmp[] = "`structure_value_domain` ".castStructureValueDomain($val, true); 
		}else{
			$tmp[] = sprintf("`%s`='%s'", $sf, $val);
		}
	}
	return "SELECT %s FROM structure_fields WHERE ".implode(" AND ", $tmp);
}

function getSimilarSfi($field){
	$query = getSelectSfiQuery($field);
	return array("query_id" => sprintf($query, "id"), "data" => getDataFromQuery(sprintf($query, "*")));
}

function getSfo($field){
	global $STRUCTURE_FIELDS_FIELDS_LIGHT;
	$sfoData = getDataFromQuery("SELECT * FROM structure_formats WHERE id='".$field->sfo_id."'");
	if(empty($sfoData)){
		echo "-- WARNING: Bad sfo_id\n";
		return null;
	}
	$structureData = getDataFromQuery("SELECT * FROM structures WHERE id='".$sfoData['structure_id']."'");
	$structure_id_query = "SELECT id FROM structures WHERE alias='".$structureData['alias']."'";
	$sfiData = getDataFromQuery("SELECT * FROM structure_fields WHERE id='".$sfoData['structure_field_id']."'");
	assert(!empty($sfiData));
	$structure_field_id_query = sprintf(getSelectSfiQuery($sfiData), "id");
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
	global $STRUCTURE_FIELDS_FIELDS;
	$tmp = array();
	foreach($STRUCTURE_FIELDS_FIELDS as $sf){
		if($sf == "structure_value_domain"){
			$tmp[] = castStructureValueDomain($field->structure_value_domain, false); 
		}else{
			$tmp[] = "'".addslashes($field->{$sf})."'";
		}
	}
	return "(".implode(", ", $tmp)."), ";
}

function getInsertIntoSfo($field, $structure_id_query, $structure_field){
	global $OVERRIDES_NAMES;
	global $STRUCTURE_FORMATS_FLAGS;
	$query = "((".$structure_id_query."), (".$structure_field['query_id']."), ";//.$field->display_column."', '".$field->display_order."', '".$field->language_heading."', ";
	
	global $STRUCTURE_FORMATS_PRIMARY_FIELDS;
	foreach($STRUCTURE_FORMATS_PRIMARY_FIELDS as $sfpf){
	    $rp = new ReflectionProperty($field, $sfpf);
	    $query .=  "'".$rp->getValue($field)."', ";
	}
	
	//look to override properly
	foreach($OVERRIDES_NAMES as $override_name => $override_flag){
		if(!isset($structure_field['data'][$override_name]) || $structure_field['data'][$override_name] == $field->{$override_name}){
			$query .= "'0', '', ";
		}else{
			$query .= "'1', '".$field->{$override_name}."', ";
		}
	}
	$pieces = array();
	foreach($STRUCTURE_FORMATS_FLAGS as $sfo_flag){
		$pieces[] = "'".$field->{$sfo_flag}."'";
	}
	$query .= implode(", ", $pieces)."), ";
	return $query;
}

/**
 * Enter description here ...
 * @param unknown_type $field
 * @param unknown_type $sfi
 * @param unknown_type $sfo
 * @param unknown_type $ignore_sfo_sfi_id_update
 */
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
		}else if(!in_array($sfo_field, $OVERRIDES_NAMES)){
			//standard fields
			if($field->{$sfo_field} != $sfo['data'][$sfo_field]){
				$query .= "`".$sfo_field."`='".$field->{$sfo_field}."', ";
			}
		}
	}
	//TODO: compare structure id and update if necessary
	//compare structure_field_id and update if necessary
	if(($sfi != NULL && $field->sfi_id != $sfi['data']['id']) || $sfi == NULL && $field->sfi_id > 0){
		if(!$ignore_sfo_sfi_id_update){
			$query = "`structure_field_id`=(SELECT `id` FROM structure_fields WHERE `model`='".$field->model."' AND `tablename`='".$field->tablename."' AND `field`='".$field->field."' AND `type`='".$field->type."' AND `structure_value_domain`".castStructureValueDomain($field->structure_value_domain, true)."), ";
		}else if($sfi != null){
			$query .= "`structure_field_id`=(SELECT `id` FROM structure_fields WHERE `model`='".$sfi['data']['model']."' AND `tablename`='".$sfi['data']['tablename']."' AND `field`='".$sfi['data']['field']."' AND `type`='".$sfi['data']['type']."' AND `structure_value_domain`".castStructureValueDomain($sfi['data']['structure_value_domain'], true)."), ";
		}
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
	$query = "(SELECT (SELECT id FROM structure_fields WHERE model='".$field->model."' AND tablename='".$field->tablename."' AND field='".$field->field."' AND `type`='".$field->type."' AND structure_value_domain".castStructureValueDomain($field->structure_value_domain, true)."), `rule`, `on_action`, `language_message` FROM structure_validations "
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
