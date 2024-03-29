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
$toDelete = array();
$toinsert = array();

foreach ($json->sample as $node) {
    if ($node->id!=-1){
        $stmt->bind_param("s", $node->id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        if (!empty($row)) {
            if ($node->flag_active == 2){
                $toDelete[] = $node->id;
            }elseif (!$row['flag_active'] && $node->flag_active == 1) {
                $toEnable[] = $node->id;
            } elseif ($row['flag_active'] && $node->flag_active == 0) {
                $toDisable[] = $node->id;
            }
        }
    }else{
        $toinsert[] = 
                "INSERT INTO `parent_to_derivative_sample_controls` " .
                "(`id`, `parent_sample_control_id`, `derivative_sample_control_id`, `flag_active`) " . 
                "VALUES (NULL, '".$node->parent_id."', '".$node->children_id."', '".$node->flag_active."')";
    }
}
if (count($toDisable)) {
    echo "UPDATE parent_to_derivative_sample_controls SET flag_active=false WHERE id IN('", implode("', '", $toDisable), "');\n";
}
if (count($toEnable)) {
    echo "UPDATE parent_to_derivative_sample_controls SET flag_active=true WHERE id IN('", implode("', '", $toEnable), "');\n";
}

if (count($toDelete)) {
    echo "DELETE FROM parent_to_derivative_sample_controls WHERE id IN('", implode("', '", $toDelete), "');\n";
}

if (count($toinsert)) {
    echo implode(";\n", $toinsert), ";\n";
}


$query = "SELECT * FROM  aliquot_controls WHERE id=?";
$stmt = $db->prepare($query) or die("prep 1 failed");
$row = bindRow($stmt);
$toDisable = array();
$toEnable = array();
$toChangeVolumeUnit = array();

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
        if ($node->volume_unit !=$row['volume_unit']){
            $toChangeVolumeUnit[] = "UPDATE aliquot_controls SET volume_unit = '" . $node->volume_unit."' WHERE id = ".$node->id;
        }
    }
}
if (count($toDisable)) {
    echo "UPDATE aliquot_controls SET flag_active=false WHERE id IN('", implode("', '", $toDisable), "');\n";
}
if (count($toEnable)) {
    echo "UPDATE aliquot_controls SET flag_active=true WHERE id IN('", implode("', '", $toEnable), "');\n";
}
if (count($toChangeVolumeUnit)) {
    echo implode(";\n", $toChangeVolumeUnit), ";\n";
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


$query = "SELECT * FROM  sample_controls WHERE id=?";
$stmt = $db->prepare($query) or die("prep 1 failed");
$row = bindRow($stmt);
$toChange = array();

foreach ($json->sampleControls as $node) {
    $stmt->bind_param("s", $node->id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    if (!empty($row)) {
        if ($row['detail_form_alias'] != $node->detail_form_alias) {
            $toChange[] = "UPDATE sample_controls SET detail_form_alias = '".$node->detail_form_alias."' WHERE id = ".$node->id.";\n";
        } 
    }
}
if (count($toChange)) {
    echo implode(";\n", $toChange);
}

$returnEcho = ob_get_clean();
if (strlen($returnEcho)>0){
    $returnEcho ="start transaction;\n\n".$returnEcho."\ncommit;\n";
}
echo $returnEcho;

