<?php 
require_once("../common/myFunctions.php");

class InvConf{
	static $queryAliquot = "SELECT * FROM sample_to_aliquot_controls AS s 
		INNER JOIN aliquot_controls AS a ON s.aliquot_control_id=a.id 
		WHERE sample_control_id=? ORDER BY aliquot_type";
	static $querySample = "SELECT assoc.parent_sample_control_id AS parent_sample_control_id,
		assoc.derivative_sample_control_id AS derivative_sample_control_id,
		assoc.id AS control_id,
		assoc.flag_active AS flag_active,
		s.sample_type AS sample_type,
		s.form_alias AS form_alias
 		FROM parent_to_derivative_sample_controls AS assoc  
		INNER JOIN sample_controls AS s ON assoc.derivative_sample_control_id=s.id 
		WHERE assoc.parent_sample_control_id=?
		ORDER BY s.sample_type ";
	static $printedSamples = array(); 
	
	static function printInner($id, $depth){
		$db = getConnection();
		
		//aliquot
		$stmt = $db->prepare(InvConf::$queryAliquot) or die("printInner qry 2 failed");
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$row = bindRow($stmt);
		if($stmt->fetch()){
			echo("<ul class='aliquots'>");
			do{
				$disabled = $row['flag_active'] ? "" : " disabled ";
				$json = '{ "id" : "'.$row['id'].'" }';
				echo("<li class='".$disabled." ".$json."'>".$row['aliquot_type']."<br/><span class='small'>".$row['form_alias']."</span></li>");
			}while($stmt->fetch());
			echo("</ul>");
		}
		$stmt->close();
		echo("</div>");
		
		//child	
		$stmt = $db->prepare(InvConf::$querySample) or die("printInner qry 1 failed ");
		$stmt->bind_param("i", $id);
		$row = bindRow($stmt);
		$stmt->execute();
		if($stmt->fetch()){
			echo("<ul class='samples' style='vertical-align: middle;'>");
			do{
				if($row['derivative_sample_control_id'] != $id && !in_array($row['derivative_sample_control_id'], InvConf::$printedSamples) && $depth < 4){
					$disabled = $row['flag_active'] ? "" : " disabled";
					$json = '{ "id" : "'.$row['control_id'].'"}';
					echo("<li class='sample".$disabled." ".$json."'><div class='sample_node'><div class='sample_cell'>".$row['sample_type']." (".$row['derivative_sample_control_id'].")<br/><span class='small'>".$row['form_alias']."</span></div>");
					InvConf::printInner($row['derivative_sample_control_id'], $depth + 1);
					echo("</li>");
				}
			}while($stmt->fetch());
			echo("</ul>");
		}
		$stmt->close();
	}
}

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="style.css" />
<title>Inventory Configuration</title>
<script type="text/javascript" src="../common/js/jquery-1.4.2.min.js"></script>
<script type="text/javascript" src="../common/js/wz_jsgraphics.js"></script>
<script type="text/javascript" src="../common/js/common.js"></script>
<script type="text/javascript" src="default.js"></script>
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
	<?php 
	$query = "SELECT * FROM sample_controls WHERE sample_category='specimen' ORDER BY sample_type";
	
	$result = $db->query($query) or die("Spe 1 qry failed ".$db->error);
	$stmt2 = $db2->prepare(InvConf::$queryAliquot) or die($db2->error." ".InvConf::$queryAliquot);
	$row2 = bindRow($stmt2);		
	while($row = $result->fetch_assoc()){
		$disabled = $row['flag_active'] ? "" : " disabled ";
		$json = '{ "id" : "'.$row['id'].'" }';
		echo("<li class='sample ".$disabled." ".$json."'><div class='sample_node'><div class='sample_cell'>".$row['sample_type']." (".$row['id'].")<br/><span class='small'>".$row['form_alias']."</span></div>");
		InvConf::printInner($row['id'], 1);
		echo("</li>");
	}
	$result->close();
	?>
	</ul>
	<pre id="debug"></pre>
</body>
</html>