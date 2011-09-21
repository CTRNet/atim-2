<?php
require_once("../common/myFunctions.php");
$json = $_POST['json'];
//$json = '[{ "id" : "core_CAN_42", "parent" : "0", "state" : "false" }]';
$json2 = stripslashes($json);
$json = json_decode($json2) or die("decode failed [".$json2."]");

$query = "SELECT flag_active FROM menus WHERE id=?";
$stmt = $db->prepare($query) or die("prep 1 failed");
$row = bindRow($stmt);
$to_disable = array();
$to_enable = array();
foreach($json as $node){
	$stmt->bind_param("s", $node->id);
	$stmt->execute();
	if($stmt->fetch()){
		if($row['flag_active'] && $node->disabled == "true"){
			$to_disable[] = $node->id;
		}else if(!$row['flag_active'] && $node->disabled == "false"){
			$to_enable[] = $node->id;
		}
	}else{
		echo "new node: ", $node->id,", ", $node->parent,"<br/>";
		//new node
	}
}

if(count($to_disable)){
	echo "UPDATE menus SET flag_active=false WHERE id IN('", implode("', '", $to_disable), "');<br/>";
}
if(count($to_enable)){
	echo "UPDATE menus SET flag_active=true WHERE id IN('", implode("', '", $to_enable), "');<br/>";
}