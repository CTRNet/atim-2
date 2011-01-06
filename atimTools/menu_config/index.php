<?php 
require_once("../common/myFunctions.php");

class MenuConf{
	static $query_menus = "SELECT id, parent_id, is_root, display_order, language_title, language_description, use_link, flag_active 
			FROM menus WHERE parent_id=?";
	 
	
	static function printInner($id, $depth){
		$db = getConnection();
		$db2 = getConnection();
		//aliquot
		$stmt = $db->prepare(self::$query_menus) or die("printInner qry 2 failed");
		$stmt->bind_param("s", $id);
		$stmt->execute();
		$row = bindRow($stmt);
		if($stmt->fetch()){
			echo "<ul>\n";
			do{
				if($depth < 8){
					$disabled = $row['flag_active'] ? "" : " disabled";
					echo "<li id='",$row['id'],"' class='",$disabled,"'><div>",$row['language_title'],"</div>";
					self::printInner($row['id'], $depth + 1);
					echo "</li>\n";
				}
			}while($stmt->fetch());
			echo "</ul>\n";
		}
		$stmt->close();
	}
}

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="style.css"/>
<title>Menus Configuration</title>
<script src="../common/js/jquery-1.4.4.min.js"></script>
<script src="../common/js/common.js"></script>
<script src="default.js"></script>
<script>
$(function(){
	$("#dbSelect").change(function(){
		document.location = "index.php?db=" + $(this).val(); 
	});
});
</script>
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

<ul>
	<li id=0><div>Root</div><ul>
<?php 
$query = "SELECT id, parent_id, is_root, display_order, language_title, language_description, use_link, flag_active "
		."FROM menus WHERE is_root=true";
$result = $db->query($query) or die("Spe 1 qry failed ".$db->error);
$ids = array();
while($row = $result->fetch_assoc()){
	//filter fake roots
	$parent_ids[$row['id']] = null;
}
$result->data_seek(0);
while($row = $result->fetch_assoc()){
	if(!array_key_exists($row['parent_id'], $parent_ids)){
		$disabled = $row['flag_active'] ? "" : " disabled ";
		echo "<li id='",$row['id'],"' class='",$disabled,"'><div>",$row['language_title'], "</div>";
		MenuConf::printInner($row['id'], 1);
		echo "</li>";
	}
}
$result->close();
?>
	</ul></li>
</ul>

<fieldset>
<legend>Queries</legend>
<div id="out"></div>
</fieldset>
<pre id="debug"></pre>
</body>
</html>