<?php 
require_once("../common/myFunctions.php");
require_once("sqlGeneratorFunctions.php");
error_reporting(E_ALL);

define("NL", "\n");

$query = 'SELECT * FROM structure_value_domains WHERE domain_name="'.$_POST['domain_name'].'"';
$result = $db->query($query) or die("Query failed at line ".__LINE__." ".$query." ".$db->error);
$values = isset($_POST['rows']) ? $_POST['rows'] : array();
unset($_POST['rows']);
if($row = $result->fetch_assoc()){
	//update if needed
	$is_new = true;
	$to_update = array();
	foreach($_POST as $k => $v){
		if($v != $row[$k]){
			$to_update[$k] = $v;
		}
	}
	if(!empty($to_update)){
		$part = "";
		echo 'UPDATE structure_value_domains SET '.getFieldsToUpdateQueryPart($to_update).' WHERE domain_name="'.$_POST['domain_name'].'";'.NL;
	}
}else{
	//create the value domain
	$is_new = false;
	$source = $_POST['source'];
	unset($_POST['source']);
	echo 'INSERT INTO structure_value_domains ('.implode(", ", array_keys($_POST)).', source) VALUES ("'.implode('", "', $_POST).'", '.($source == 'NULL' || strlen($source) == 0 ? 'NULL' : '"'.$source.'"').');'.NL;
}
$result->free();

$used_id_pre = array();
$still_used_id = array();

$query = "SELECT structure_permissible_value_id FROM structure_value_domains_permissible_values WHERE structure_value_domain_id=(".getSvdIdQuery($_POST['domain_name']).")";
$result = $db->query($query) or die("Query failed at line ".__LINE__." ".$query." ".$db->error);
while($row = $result->fetch_assoc()){
	$used_id_pre[] = $row['structure_permissible_value_id'];
}
$result->free();

foreach($values as $value){
	//is the id defined?
	if($value['id']){
		//is the value already linked to the domain?
		if(isLinkedToDomain($value, $_POST['domain_name'])){
			if(isUnchanged($value)){
				checkFlagActiveAndOrder($value, $_POST['domain_name']);
				$still_used_id[] = $value['id'];
				continue;
			}else{
				//Branch B - Does a similar value exists?
				if($similar_id = similarValueExists($value)){
					//use it
					echoInsertIntoSvdpv($value, $_POST['domain_name']);
				}else{
					//create it
					echoInsertSpvQuery($value);
					echoInsertIntoSvdpv($value, $_POST['domain_name']);
				}
				
				continue;
			}
		}
	}
	
	//Branch A - Does a similar value exists?
	if(similarValueExists($value)){
		//use it
		echoInsertIntoSvdpv($value, $_POST['domain_name']);
	}else{
		//create it
		echoInsertSpvQuery($value);
		echoInsertIntoSvdpv($value, $_POST['domain_name']);
	}
}

//delete now unused values
$to_delete = array_diff($used_id_pre, $still_used_id);
if($to_delete){
	$query = "SELECT * FROM structure_permissible_values WHERE id IN(".implode(", ", $to_delete).")";
	$result = $db->query($query) or die("Query failed at line ".__LINE__." ".$query." ".$db->error);
	
	$to_delete = array();
	while($row = $result->fetch_assoc()){
		echo 'DELETE svdpv FROM structure_value_domains_permissible_values AS svdpv '
			.'INNER JOIN structure_permissible_values AS spv ON svdpv.structure_permissible_value_id=spv.id '
			.'WHERE spv.value="'.$row['value'].'" AND spv.language_alias="'.$row['language_alias'].'";'.NL;
		$to_delete[] = $row;
	}
	$result->free();
	
	foreach($to_delete as $value){
		if(isNotUsedElsewhere($value, $_POST['domain_name'])){
			echoDeleteSpv($value);
		}
	}
}

function isLinkedToDomain(array $value, $domain_name){
	global $db;
	$is_linked_to_domain_name = false;
	$query = "SELECT * FROM structure_value_domains AS svd
		INNER JOIN structure_value_domains_permissible_values AS svdpv ON svdpv.structure_value_domain_id=svd.id
		INNER JOIN structure_permissible_values AS spv ON spv.id=svdpv.structure_permissible_value_id
		WHERE svd.domain_name='".$domain_name."' AND spv.id='".$value['id']."'";
	$result = $db->query($query) or die("Query failed at line ".__LINE__." ".$query." ".$db->error);
	
	if($row = $result->fetch_assoc()){
		$is_linked_to_domain_name = true;
	}
	$result->free();
	return $is_linked_to_domain_name;
}

function isUnchanged(array $value){
	global $db;
	$unchanged = true;
	$query = "SELECT * FROM structure_permissible_values WHERE id='".$value['id']."'";
	$result = $db->query($query) or die("Query failed at line ".__LINE__." ".$query." ".$db->error);
	if($row = $result->fetch_assoc()){
		foreach(array('value', 'language_alias') as $key){
			if($row[$key] != $value[$key]){
				$unchanged = false;
				break;
			}
		}
	}else{
		print_r($value);
		die("ERROR: Value not found\n");
	}
	$result->free();
	return $unchanged;
}

function similarValueExists(array $value){
	global $db;
	$similar_val_id = null;
	$query = "SELECT id FROM structure_permissible_values WHERE value='".$value['value']."' AND language_alias='".$value['language_alias']."'";
	$result = $db->query($query) or die("Query failed at line ".__LINE__." ".$query." ".$db->error);
	if($row = $result->fetch_assoc()){
		$similar_val_id = $row['id'];
	}
	$result->free();
	return $similar_val_id;
	
}

function isNotUsedElsewhere(array $value, $domain_name){
	global $db;
	$is_not_used_elsewhere = true;
	$query = "SELECT * FROM structure_value_domains AS svd
		INNER JOIN structure_value_domains_permissible_values AS svdpv ON svdpv.structure_value_domain_id=svd.id
		INNER JOIN structure_permissible_values AS spv ON spv.id=svdpv.structure_permissible_value_id
		WHERE ".($domain_name ? "svd.domain_name!='".$domain_name."' AND " : "")."spv.id='".$value['id']."' LIMIT 1";
	$result = $db->query($query) or die("Query failed at line ".__LINE__." ".$query." ".$db->error);
	if($result->fetch_assoc()){
		$is_not_used_elsewhere = false;
	}
	$result->free();
	return $is_not_used_elsewhere;
}

function checkFlagActiveAndOrder(array $value, $domain_name){
	global $db;
	$query = "SELECT svdpv.flag_active, svdpv.display_order FROM structure_value_domains AS svd
	INNER JOIN structure_value_domains_permissible_values AS svdpv ON svdpv.structure_value_domain_id=svd.id
	INNER JOIN structure_permissible_values AS spv ON spv.id=svdpv.structure_permissible_value_id
	WHERE svd.domain_name='".$domain_name."' AND spv.id='".$value['id']."' LIMIT 1";
	$result = $db->query($query) or die("Query failed at line ".__LINE__." ".$query." ".$db->error);
	if($row = $result->fetch_assoc()){
		$to_update = array();
		if($row['flag_active'] != $value['flag_active']){
			$to_update['flag_active'] = $value['flag_active']; 
		}
		if($row['display_order'] != $value['display_order']){
			$to_update['display_order'] = $value['display_order'];
		}
		if($to_update){
			echo "UPDATE structure_value_domains AS svd "
				."INNER JOIN structure_value_domains_permissible_values AS svdpv ON svdpv.structure_value_domain_id=svd.id "
				."INNER JOIN structure_permissible_values AS spv ON spv.id=svdpv.structure_permissible_value_id "
				."SET ".getFieldsToUpdateQueryPart($to_update)." "
				."WHERE svd.domain_name='".$domain_name."' AND spv.id=(".getSpvIdQuery($value).");".NL;
		}
	}
	$result->free();
}

function echoInsertIntoSvdpv(array $value, $domain_name){
	printf(
		'INSERT INTO structure_value_domains_permissible_values (structure_value_domain_id, structure_permissible_value_id, display_order, flag_active) VALUES ((%s), (%s), "%s", "%s");'.NL,
		getSvdIdQuery($domain_name),
		getSpvIdQuery($value),
		$value['display_order'],
		$value['flag_active']
	);
}

function getSvdIdQuery($domain_name){
	return 'SELECT id FROM structure_value_domains WHERE domain_name="'.$domain_name.'"';
}

function getSpvIdQuery(array $value){
	return 'SELECT id FROM structure_permissible_values WHERE value="'.$value['value'].'" AND language_alias="'.$value['language_alias'].'"';
}

function echoInsertSpvQuery(array $value){
	echo 'INSERT INTO structure_permissible_values (value, language_alias) VALUES("'.$value['value'].'", "'.$value['language_alias'].'");'.NL;
}

function echoDeleteSpv(array $value){
	global $db;
	$query = "SELECT * FROM structure_permissible_values WHERE id='".$value['id']."'";
	$result = $db->query($query) or die("Query failed at line ".__LINE__." ".$query." ".$db->error);
	if($row = $result->fetch_assoc()){
		echo 'DELETE FROM structure_permissible_values WHERE value="'.$value['value'].'" AND language_alias="'.$value['language_alias'].'";'.NL;
	}else{
		print_r($value);
		die('ERROR: Failed to delete based on id');
	}
	$result->free();
}



