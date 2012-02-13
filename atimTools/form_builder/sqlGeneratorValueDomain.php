<?php 
require_once("../common/myFunctions.php");
require_once("sqlGeneratorFunctions.php");
error_reporting(E_ALL);

$query = 'SELECT * FROM structure_value_domains WHERE domain_name="'.$_POST['domain_name'].'"';
$result = $db->query($query) or die("Query failed at line ".__LINE__." ".$query." ".$db->error);
$values = $_POST['rows'];
unset($_POST['rows']);
if($row = $result->fetch_assoc()){
	//update if needed
	$is_new = true;
	$to_update = array();
	foreach($_POST as $k => $v){
		if($v != $row[$k]){
			$to_update[] = $k.'="'.$v.'"';
		}
	}
	if(!empty($_POST)){
		$part = "";
		echo 'UPDATE structure_value_domains SET '.implode(", ", $to_update).' WHERE domain_name="'.$_POST['domain_name'].'";';
	}
}else{
	//create the value domain
	$is_new = false;
	echo 'INSERT INTO structure_value_domains ('.implode(", ", array_keys($_POST)).') VALUES ("'.implode('", "', $_POST).'");';
}
$result->free();

foreach($values as $value){
	//is the id defined?
	if($value['id']){
		//is the value already linked to the domain?
		if(isLinkedToDomain($value, $_POST['domain_name'])){
			if(isUnchanged($value)){
				checkFlagActive($value, $_POST['domain_name']);
				continue;
			}else{
				//Branch B - Does a similar value exists?
				if(similarValueExists($value)){
					//use it
				
				}else{
					//create it
				}
				
				if(isUsedElsewhere($value, $_POST['domain_name'])){
					//delete it
				}
				
				continue;
			}
		}
	}
	
	//Branch A - Does a similar value exists?
	if(similarValueExists($value)){
		//use it
		
	}else{
		//create it
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

function isUsedElsewhere(array $value, $domain_name){
	global $db;
	$is_used_elsewhere = false;
	$query = "SELECT * FROM structure_value_domains AS svd
		INNER JOIN structure_value_domains_permissible_values AS svdpv ON svdpv.structure_value_domain_id=svd.id
		INNER JOIN structure_permissible_values AS spv ON spv.id=svdpv.structure_permissible_value_id
		WHERE svd.domain_name!='".$domain_name."' AND spv.id='".$value['id']."' LIMIT 1";
	$result = $db->query($query) or die("Query failed at line ".__LINE__." ".$query." ".$db->error);
	if($result->fetch_assoc()){
		$is_used_elsewhere = true;
	}
	$result->free();
	return $is_used_elsewhere;
}

function checkFlagActive(array $value, $domain_name){
	global $db;
	$query = "SELECT svdpv.flag_active FROM structure_value_domains AS svd
	INNER JOIN structure_value_domains_permissible_values AS svdpv ON svdpv.structure_value_domain_id=svd.id
	INNER JOIN structure_permissible_values AS spv ON spv.id=svdpv.structure_permissible_value_id
	WHERE svd.domain_name='".$domain_name."' AND spv.id='".$value['id']."' AND svdpv.flag_active!='".$value['flag_active']."' LIMIT 1";
	$result = $db->query($query) or die("Query failed at line ".__LINE__." ".$query." ".$db->error);
	if($result->fetch_assoc()){
		//TODO: WE'RE HERE!
		echo "UPDATE structure_value_domains AS svd
			INNER JOIN structure_value_domains_permissible_values AS svdpv ON svdpv.structure_value_domain_id=svd.id
			INNER JOIN structure_permissible_values AS spv ON spv.id=svdpv.structure_permissible_value_id
			SET flag_active='".$value['flag_active']."'
			WHERE svd.domain_name='".$domain_name."' AND spv.id='".$value['id']."'";
	}
	$result->free();
}