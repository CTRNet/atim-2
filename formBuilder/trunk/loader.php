<?php
require_once("myFunctions.php");
$json = json_decode(stripslashes($_GET['json'])) or die("decode failed");
$query = "";
if($json->type == 'structures'){
	
	
	if(is_numeric($json->val)){
		$query = "SELECT alias FROM structures WHERE id='".$json->val."'";
		$result = $mysqli->query($query) or die("Query failed ".$mysqli->error);
		if($row = $result->fetch_assoc()){
			echo("<h3>Structure ".$row['alias']."</h3>\n");
		}
		$query = "SELECT * "
				."FROM `structure_formats` AS sfo "
				."INNER JOIN structure_fields AS sfi ON sfi.id = sfo.structure_field_id "
				."WHERE sfo.structure_id = '".$json->val."'";
	}else{
		$query = "SELECT alias FROM structures WHERE old_id='".$json->val."'";
		$result = $mysqli->query($query) or die("Query failed ".$mysqli->error);
		if($row = $result->fetch_assoc()){
			echo("<h3>Structure ".$row['alias']."</h3>\n");
		}
		$query = "SELECT * "
				."FROM `structure_formats` AS sfo "
				."INNER JOIN structure_fields AS sfi ON sfi.id = sfo.structure_field_id "
				."WHERE sfo.structure_old_id = '".$json->val."'";
	}
}else if($json->type == 'tables'){
	echo ("<h3>Table: <span id='tablename'>".$json->val."</span></h3>");
	echo ("Autotype: <input id='table_autotype' type='checkbox' checked='checked'/>");
	$query = "DESC ".$json->val;
}else if($json->type == 'fields'){
	$query = "SELECT * FROM structure_fields WHERE model = '".$json->val."'";
}else if($json->type == 'structure_permissible_values'){
	$query = "SELECT * FROM structure_permissible_values ORDER BY language_alias"; 
}else if($json->type == 'value_domains'){
	$query = "SELECT * FROM structure_value_domains AS svd
				INNER JOIN structure_value_domains_permissible_values AS svdpv ON svd.id=svdpv.structure_value_domain_id
				INNER JOIN structure_permissible_values AS spv ON svdpv.structure_permissible_value_id=spv.id				
				WHERE domain_name = '".$json->val."'"; 
}else{
	$query = "SELECT 'error'";
}
$result = $mysqli->query($query) or die("Query failed ".$mysqli->error);
echo('<table class="ui-widget ui-widget-content">');
if($row = $result->fetch_assoc()){
	echo("<thead><tr class='ui-widget-header'>");
	foreach($row as $k => $v){
		echo("<th>".$k."</td>");
	}
	echo("</tr></thead>");
	echo("<tbody>");
	foreach($row as $k => $v){
		echo("<td class='".$k."'>".$v."</td>");
	}
	echo("</tr>");
}
while($row = $result->fetch_assoc()){
	echo("<tr>");
	foreach($row as $k => $v){
		echo("<td class='".$k."'>".$v."</td>");
	}
	echo("</tr>");
}
echo("<tdoby></table>");
?>