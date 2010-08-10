<?php
if(isset($_GET['json'])){
	$json = json_decode(stripslashes($_GET['json'])) or die('[{"id" : "error", "text":"error"}]');
}else{
	die("no data");
}

require_once("../common/myFunctions.php");

if($json->fetching == "model"){
	$query = "SELECT model FROM structure_fields WHERE plugin LIKE '".(strlen($json->plugin) == 0 ? '%' : $json->plugin)."' AND model LIKE '%".$json->val."%'
			GROUP BY model ORDER BY model";
}else if($json->fetching == "plugin"){
	$query = "SELECT plugin FROM structure_fields WHERE plugin LIKE '%".$json->val."%' 
			GROUP BY plugin ORDER BY plugin";
}else if($json->fetching == "tablename"){
	$query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='".$db_schema."' AND TABLE_NAME LIKE '%".$json->val."%'  AND TABLE_NAME NOT LIKE '%_revs' LIMIT 15";
}
$result = $db->query($query) or die('[{"id" : "error", "text":"querry failed"}]');
echo("[");
if($row = $result->fetch_row()){
	echo('{"text" : "'.$row[0].'"}');
}
while($row = $result->fetch_row()){
	echo(',{"text" : "'.$row[0].'"}');
}
echo("]");
//echo('[{"id" : "toto", "text":"'.$json->val.'"}]');
//[{"id":"AD","text":"Andorra"},{"id":"AE","text":"United Arab Emirates"},{"id":"AF","text":"Afghanistan"},{"id":"AG","text":"Antigua and Barbuda"},{"id":"AI","text":"Anguilla"},{"id":"AL","text":"Albania"},{"id":"AM","text":"Armenia"},{"id":"AN","text":"Netherlands Antilles"},{"id":"AO","text":"Angola"},{"id":"AQ","text":"Antarctica"},{"id":"AR","text":"Argentina"},{"id":"AS","text":"American Samoa"},{"id":"AT","text":"Austria"},{"id":"AU","text":"Australia"},{"id":"AW","text":"Aruba"},{"id":"AX","text":"Aland Islands"},{"id":"AZ","text":"Azerbaijan"}]
?>