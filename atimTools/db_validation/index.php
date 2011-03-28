<?php
/**
 * @author FM L'Heureux
 * @date 2009-12-02
 * @description: Reads a database and compares base tables with revs tables. Gives the difference between those and gives the list
 * of tables without revs. Also gives a list of strings without tranlsation.
 */
require_once("../common/myFunctions.php");

$tables_wo_revs = array("acos", "aliquot_controls", "aros", "aros_acos", "atim_information", "coding_icd10", "coding_icdo_3", "configs", 
	"consent_controls", "diagnosis_controls", "event_controls", "groups", "i18n", "key_increments", "langs", "menus", "missing_translations",
	"pages", "parent_to_derivative_sample_controls", "sample_to_aliquot_controls", 
	"misc_identifier_controls", "protocol_controls", "realiquoting_controls", "sample_controls", "sop_controls" ,"storage_controls", 
	"structures", "structure_fields", "structure_formats", "structure_permissible_values", "structure_permissible_values_custom_controls", 
	"structure_validations", "structure_value_domains", "structure_value_domains_permissible_values", "tx_controls", "users", "user_logs",
	"versions", "view_aliquots", "view_collections", "view_samples", "datamart_browsing_controls", 
	"aliquot_review_controls", "coding_icd_o_3_topography", "datamart_adhoc", "specimen_review_controls",
	"user_login_attempts", "view_structures");

$tables_wo_revs = array_flip($tables_wo_revs);
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Database validation</title>
<script type="text/javascript" src="../common/js/jquery-1.4.4.min.js"></script>
<script type="text/javascript">
$(function(){
	$("select").change(function(){
		document.location = "?db=" + $(this).val();
	});

	$("#tr_target").append($("#translations"));

	$(".table_field").click(function(){
		if($(this).attr("checked")){
			$("tr." + this.id).show();
		}else{
			$("tr." + this.id).hide();
		}
	});

	$("#where").click(function(){
		if($(this).attr("checked")){
			$("#mt tr").each(function(){
				$($(this).find("td, th")[1]).show();
			});
		}else{
			$("#mt tr").each(function(){
				$($(this).find("td, th")[1]).hide();
			});
		}
	});
});
</script>
<style type="text/css">
body{
	font-family: arial;
	font-size: 85%;
}
</style>
</head>
<body>
<div id="top">
	Current database: 
	<?php 
	$db2 = getConnection();
	$query = "SHOW databases";
	$result = $db->query($query) or die("show databases failed");
	?>
	<select id="dbSelect">
		<option></option>
		<?php 
		while($row = $result->fetch_row()){
			if($row[0] != "information_schema" && $row[0] != "mysql"){
				$selected = ($row[0] == $db_schema ? ' selected="selected"' : "");
				echo("<option".$selected.">".$row[0]."</option>");
			}
		}
		?>
	</select>
</div>
<?php 
$result = $db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='".$_SESSION['db']."' ORDER BY TABLE_NAME") or die($db->error);
$tables = array();
while ($row = $result->fetch_assoc()) {
	foreach($row as $key => $value){
		$tables[$value] = "";
	}
}
$result->free();
echo("<h1>Corrections to do</h1>\n");
$correction = false;
foreach($tables as $tname => $foo){
	if(isset($tables[$tname."_revs"])){
		$result = $db->query("DESCRIBE ".$tname) or die($db->error);
		$primary_table = array();
		while ($row = $result->fetch_assoc()) {
			$primary_table[$row['Field']]['Type'] = $row['Type'];
			$primary_table[$row['Field']]['Null'] = $row['Null'];
			$primary_table[$row['Field']]['Key'] = $row['Key'];
			$primary_table[$row['Field']]['Default'] = $row['Default'];
			$primary_table[$row['Field']]['Extra'] = $row['Extra'];
		}
		$primary_table['version_id']['Type'] = "int(11)";
		$primary_table['version_id']['Null'] = "NO";
		$primary_table['version_id']['Key'] = "";
		$primary_table['version_id']['Default'] = "";
		$primary_table['version_id']['Extra'] = "auto_increment";
		$primary_table['version_created']['Type'] = "datetime";
		$primary_table['version_created']['Null'] = "NO";
		$primary_table['version_created']['Key'] = "";
		$primary_table['version_created']['Default'] = "";
		$primary_table['version_created']['Extra'] = "";
		$result->free();

		$result = $db->query("DESCRIBE ".$tname."_revs") or die($db->error);
		while ($row = $result->fetch_assoc()) {
			if(!isset($primary_table[$row['Field']])){
				echo("- ".$tname."_revs.".$row['Field']."<br/>");
				$correction = true;
			}elseif ($primary_table[$row['Field']]['Type'] != $row['Type']
				|| $primary_table[$row['Field']]['Null'] != $row['Null']
//				|| $primary_table[$row['Field']]['Key'] != $row['Key']
				|| $primary_table[$row['Field']]['Default'] != $row['Default']
//				|| $primary_table[$row['Field']]['Extra'] != $row['Extra']
				){
					$correction = true;
					echo("c ".$tname.".".$row['Field']."<br/>\n");
				}
				unset($primary_table[$row['Field']]);
		}
		foreach($primary_table as $field => $foo){
			$correction = true;
			echo("+ ".$tname."_revs.".$field."<br/>\n");
		}
		$result->free();
		unset($tables[$tname]);
		unset($tables[$tname."_revs"]);
	}
}
if(!$correction){
	echo("None.\n");
}

echo("<h1>Tables without revs</h1>\n");
if(count($tables) > 0){
	echo("<ol>\n");
	foreach($tables as $tname => $foo){
		if(!isset($tables_wo_revs[$tname])){
			echo("<li>".$tname."</li>\n");
		}else{
			unset($tables_wo_revs[$tname]);
		}
	}
	echo("</ol>\n");
}else{
	echo("None.\n");
}

?>
<h1>Controls tables pointing to invalid tables</h1>
<?php 
$query = "SHOW TABLES";
$result = $db->query($query) or die($db->error);
$non_control_tables = array();
$control_tables = array();
$ignore_list = array("datamart_browsing_controls", "misc_identifier_controls", "parent_to_derivative_sample_controls", "realiquoting_controls", 
	"sample_to_aliquot_controls", "structure_permissible_values_custom_controls");
while ($row = $result->fetch_row()){
	if(substr_compare($row[0], "_controls", -9) === 0){
		$control_tables[] = $row[0];
	}else{
		$non_control_tables[] = $row[0];
	}
}
$result->free();
$non_control_tables = array_flip($non_control_tables);
$control_tables = array_diff($control_tables, $ignore_list);
$missing_details = array();
$keys = array("detail_tablename", "extend_tablename");
foreach($control_tables as $control_table){
	$query = "SELECT * FROM ".$control_table;
	$result = $db->query($query) or die($db->error);
	$keys_to_look_for = array();
	if(($row = $result->fetch_assoc())){
		foreach($keys as $key){
			if(array_key_exists($key, $row)){
				$keys_to_look_for[] = $key;
			}
		}
		do{
			foreach($keys_to_look_for as $key){
				if($row[$key] != null && strlen($row[$key]) > 0 && !array_key_exists($row[$key], $non_control_tables)){
					$missing_details[] = $control_table." &rarr; ".$key." &rarr; ".$row[$key];
				}
			}
		}while ($row = $result->fetch_assoc());
	}
	$result->free();
}

if(empty($missing_details)){
	echo "All detail and extend tables were found";
}else{
	echo "<ul><li>",implode("</li><li>", $missing_details),"</li></ul>";
}
?>

<h1>Strings requiring translation</h1>
<div id="tr_target"></div>
<table id="mt"><tr>
<th>Missing translation</th><th>Where</th>
</tr>
<?php 
$query = "SELECT lang, place FROM(
		SELECT language_heading AS lang, 'sfo_language_heading' AS place FROM structure_formats
			LEFT JOIN i18n ON i18n.id=language_heading
			WHERE language_heading != '' AND i18n.id IS NULL
		UNION
			SELECT language_label AS lang, 'sfo_language_label' AS place FROM structure_formats
			LEFT JOIN i18n ON i18n.id=language_label
			WHERE language_label != '' AND i18n.id IS NULL
		UNION
			SELECT language_tag AS lang, 'sfo_language_tag' AS place FROM structure_formats
			LEFT JOIN i18n ON i18n.id=language_tag
			WHERE language_tag != '' AND i18n.id IS NULL
		UNION
			SELECT language_help AS lang, 'sfo_language_help' AS place FROM structure_formats
			LEFT JOIN i18n ON i18n.id=language_help
			WHERE language_help != '' AND i18n.id IS NULL
		UNION
			SELECT language_label AS lang, 'sfo_language_label' AS place FROM structure_formats
			LEFT JOIN i18n ON i18n.id=language_label
			WHERE language_label != '' AND i18n.id IS NULL	

		UNION
			SELECT language_label AS lang, 'sfi_language_label' AS place FROM structure_fields
			LEFT JOIN i18n ON i18n.id=language_label
			WHERE language_label != '' AND i18n.id IS NULL
		UNION
			SELECT language_tag AS lang, 'sfi_language_tag' AS place FROM structure_fields
			LEFT JOIN i18n ON i18n.id=language_tag
			WHERE language_tag != '' AND i18n.id IS NULL
			
		UNION
			SELECT language_alias AS lang, 'spv_language_alias' AS place FROM structure_permissible_values
			LEFT JOIN i18n ON i18n.id=language_alias
			WHERE language_alias != '' AND i18n.id IS NULL

		UNION
			SELECT language_message AS lang, 'sv_language_msg' AS place FROM structure_validations
			LEFT JOIN i18n ON i18n.id=language_message
			WHERE language_message != '' AND i18n.id IS NULL
		
		UNION
			SELECT id AS lang, 'missing_translations' AS place FROM missing_translations) AS tmp GROUP BY lang ORDER BY lang";

$result = $db->query($query) or die($db->error);
$tables = array();
while ($row = $result->fetch_assoc()) {
		if(!is_numeric($row['lang'])){
			echo("<tr class='".$row['place']."'><td>".$row['lang']."</td><td>".$row['place']."</td></tr>\n");
			if(!isset($tables[$row['place']])){
				$tables[$row['place']] = 1;
			}else{
				$tables[$row['place']] ++;
			}
		}
}
$result->free();

?>
</table>
<ul id="translations" style="list-style: none;">
	<?php 
	foreach ($tables as $table => $count){
		echo("<li><input type='checkbox' id='".$table."' class='table_field' checked='true'/><label>".$table." (".$count.")</label></li>\n");
	}
	?>
	<li><input id="where" type="checkbox" checked="true"/><label>Show where</label></li>
</ul>

</body>
</html>