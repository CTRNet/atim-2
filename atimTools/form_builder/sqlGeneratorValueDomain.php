<?php 
require_once("../common/myFunctions.php");
require_once("sqlGeneratorFunctions.php");

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
