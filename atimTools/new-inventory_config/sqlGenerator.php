<?php
require_once("../common/myFunctions.php");
$json = $_POST['json'];
//$json = '[{'id': 169, 'parent_id': 121, 'children_id': 119, 'flag_active': false}]';

$json2 = stripslashes($json);
$json = json_decode($json2) or die("decode failed.");

$query = "SELECT * FROM parent_to_derivative_sample_controls WHERE id=?";
$stmt = $db->prepare($query) or die("prep 1 failed");
$row = bindRow($stmt);
$toDisable = array();
$toEnable = array();

foreach ($json->sample as $node) {
    if ($node->id!=-1){
        $stmt->bind_param("s", $node->id);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    if (!empty($row)) {
        if (!$row['flag_active'] && $node->flag_active == true) {
            $toDisable[] = $node->id;
        } else if ($row['flag_active'] && $node->flag_active == false) {
            $toEnable[] = $node->id;
        }
    }
}
if (count($toDisable)) {
    echo "UPDATE parent_to_derivative_sample_controls SET flag_active=false WHERE id IN('", implode("', '", $toDisable), "');\n";
}
if (count($toEnable)) {
    echo "UPDATE parent_to_derivative_sample_controls SET flag_active=true WHERE id IN('", implode("', '", $toEnable), "');\n";
}


$query = "SELECT * FROM  aliquot_controls WHERE id=?";
$stmt = $db->prepare($query) or die("prep 1 failed");
$row = bindRow($stmt);
$toDisable = array();
$toEnable = array();

foreach ($json->aliquot as $node) {
    if ($node->id!=-1){
        $stmt->bind_param("s", $node->id);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    if (!empty($row)) {
        if (!$row['flag_active'] && $node->flag_active == true) {
            $toDisable[] = $node->id;
        } else if ($row['flag_active'] && $node->flag_active == false) {
            $toEnable[] = $node->id;
        }
    }
}
if (count($toDisable)) {
    echo "UPDATE aliquot_controls SET flag_active=false WHERE id IN('", implode("', '", $toDisable), "');\n";
}
if (count($toEnable)) {
    echo "UPDATE aliquot_controls SET flag_active=true WHERE id IN('", implode("', '", $toEnable), "');\n";
}


$query = "SELECT * FROM realiquoting_controls WHERE id=?";
$stmt = $db->prepare($query) or die("prep 1 failed");
$row = bindRow($stmt);
$toDisable = array();
$toEnable = array();

foreach ($json->realiquot as $node) {
    if ($node->id!=-1){
        $stmt->bind_param("s", $node->id);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    if (!empty($row)) {
        if (!$row['flag_active'] && $node->flag_active == true) {
            $toDisable[] = $node->id;
        } else if ($row['flag_active'] && $node->flag_active == false) {
            $toEnable[] = $node->id;
        }
    }
}
if (count($toDisable)) {
    echo "UPDATE realiquoting_controls SET flag_active=false WHERE id IN('", implode("', '", $toDisable), "');\n";
}
if (count($toEnable)) {
    echo "UPDATE realiquoting_controls SET flag_active=true WHERE id IN('", implode("', '", $toEnable), "');\n";
}




//$sample_qry = "SELECT * FROM parent_to_derivative_sample_controls WHERE id=?";
//$aliquot_qry = "SELECT flag_active FROM aliquot_controls WHERE id=?";
//$realiquot_qry = "SELECT flag_active FROM realiquoting_controls WHERE id=?";
//
//$stmt = $db->prepare($sample_qry) or die("sqlGenerator prep1 failed");
//$row = bindRow($stmt);
//$samples_activate = array();
//$samples_deactivate = array();
//foreach($json->samples AS $sample){
//	$stmt->bind_param("i", $sample->id);
//	$stmt->execute();
//	if($stmt->fetch()){
//		if($row['flag_active'] == $sample->disabled){
//			if($sample->disabled){
//				$samples_deactivate[] = $sample->id;
//			}else{
//				$samples_activate[] = $sample->id;
//			}
//		}
//	}
//}
//$stmt->close();
//
//$stmt = $db->prepare($aliquot_qry) or die("sqlGenerator prep2 failed");
//$row = bindRow($stmt);
//$aliquots_activate = array();
//$aliquots_deactivate = array();
//foreach($json->aliquots AS $aliquot){
//	$stmt->bind_param("i", $aliquot->id);
//	$stmt->execute();
//	if($stmt->fetch()){
//		if($row['flag_active'] == $aliquot->disabled){
//			if($aliquot->disabled){
//				$aliquots_deactivate[] = $aliquot->id;
//			}else{
//				$aliquots_activate[] = $aliquot->id;
//			}
//		}
//	}
//}
//$stmt->close();
//
//$stmt = $db->prepare($realiquot_qry);
//$row = bindRow($stmt);
//$realiquots_activate = array();
//$realiquots_deactivate = array();
//foreach($json->realiquots AS $realiquot){
//	$stmt->bind_param("i", $realiquot->id);
//	$stmt->execute();
//	if($stmt->fetch()){
//	if($row['flag_active'] == $realiquot->disabled){
//			if($realiquot->disabled){
//				$realiquots_deactivate[] = $realiquot->id;
//			}else{
//				$realiquots_activate[] = $realiquot->id;
//			}
//		}
//	}
//}
//if(count($samples_activate) > 0){
//	$samples_activate = array_unique($samples_activate, SORT_NUMERIC);
//	echo("UPDATE parent_to_derivative_sample_controls SET flag_active=true WHERE id IN(".implode(", ", $samples_activate).");<br/>");
//}
//if(count($samples_deactivate) > 0){
//	$samples_deactivate = array_unique($samples_deactivate, SORT_NUMERIC);
//	echo("UPDATE parent_to_derivative_sample_controls SET flag_active=false WHERE id IN(".implode(", ", $samples_deactivate).");<br/>");
//}
//if(count($aliquots_activate) > 0){
//	$aliquots_activate = array_unique($aliquots_activate, SORT_NUMERIC);
//	echo("UPDATE aliquot_controls SET flag_active=true WHERE id IN(".implode(", ", $aliquots_activate).");<br/>");
//}
//if(count($aliquots_deactivate) > 0){
//	$aliquots_deactivate = array_unique($aliquots_deactivate, SORT_NUMERIC);
//	echo("UPDATE aliquot_controls SET flag_active=false WHERE id IN(".implode(", ", $aliquots_deactivate).");<br/>");
//}
//if(count($realiquots_activate) > 0){
//	$realiquots_activate = array_unique($realiquots_activate, SORT_NUMERIC);
//	echo("UPDATE realiquoting_controls SET flag_active=true WHERE id IN(".implode(", ", $realiquots_activate).");<br/>");	
//}
//if(count($realiquots_deactivate) > 0){
//	$realiquots_deactivate = array_unique($realiquots_deactivate, SORT_NUMERIC);
//	echo("UPDATE realiquoting_controls SET flag_active=false WHERE id IN(".implode(", ", $realiquots_deactivate).");<br/>");	
//}