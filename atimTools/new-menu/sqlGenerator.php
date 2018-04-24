<?php

require_once("../common/myFunctions.php");
$json = $_POST['json'];
//$json = '[{"id" : "clin_CAN_1", "flag_active" : "false", "display_order" : "0" }]';
$json2 = stripslashes($json);
$json = json_decode($json2) or die("");
$query = "SELECT id, flag_active, display_order FROM menus WHERE id=?";
$stmt = $db->prepare($query) or die("prep 1 failed");
$row = bindRow($stmt);
$toDisable = array();
$toEnable = array();
$toSorted = array();
foreach ($json as $node) {
    $stmt->bind_param("s", $node->id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    if (!empty($row)) {
        if ($row['flag_active'] && $node->flag_active == "true") {
            $toDisable[] = $node->id;
        } else if (!$row['flag_active'] && $node->flag_active == "false") {
            $toEnable[] = $node->id;
        }
        if ($row['display_order'] != $node->display_order) {
            $toSorted[] = "UPDATE menus SET display_order=".$node->display_order." WHERE id = '".$node->id."';\n";
        }
    } else {
        echo "new node: ", $node->id, ", ", $node->parent, "\n";
        //new node
    }
}
if (count($toDisable)) {
    echo "UPDATE menus SET flag_active=false WHERE id IN('", implode("', '", $toDisable), "');\n";
}
if (count($toEnable)) {
    echo "UPDATE menus SET flag_active=true WHERE id IN('", implode("', '", $toEnable), "');\n";
}

if (count($toSorted)) {
    foreach($toSorted as $query){
       echo $query; 
    }
}

