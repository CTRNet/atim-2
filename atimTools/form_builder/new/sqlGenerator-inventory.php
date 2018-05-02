<?php
require_once("../../common/myFunctions.php");
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
            $toEnable[] = $node->id;
        } else if ($row['flag_active'] && $node->flag_active == false) {
            $toDisable[] = $node->id;
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
            $toEnable[] = $node->id;
        } else if ($row['flag_active'] && $node->flag_active == false) {
            $toDisable[] = $node->id;
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
            $toEnable[] = $node->id;
        } else if ($row['flag_active'] && $node->flag_active == false) {
            $toDisable[] = $node->id;
        }
    }
}
if (count($toDisable)) {
    echo "UPDATE realiquoting_controls SET flag_active=false WHERE id IN('", implode("', '", $toDisable), "');\n";
}
if (count($toEnable)) {
    echo "UPDATE realiquoting_controls SET flag_active=true WHERE id IN('", implode("', '", $toEnable), "');\n";
}
