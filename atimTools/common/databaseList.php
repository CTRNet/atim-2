<?php
global $db;
global $db_schema;
require_once("../common/myFunctions.php");

$to_return = Array("selected" => $db_schema, "options" => Array());

$query = "SHOW databases";
$result = $db->query($query) or die("show databases failed");
while($row = $result->fetch_row()){
	if($row[0] != "information_schema" && $row[0] != "mysql"){
	    array_push($to_return["options"], $row[0]);
	}
}

echo json_encode($to_return);