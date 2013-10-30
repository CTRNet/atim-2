<?php
global $db;
require_once("../common/myFunctions.php");
$to_return = array("names" => array(), "links" => array());

$query = "SELECT id, display_name from datamart_structures";
$result = $db->query($query);

while($row = $result->fetch_assoc()){
    $to_return["names"][$row['id']] = $row['display_name'];
}
$result->close();

$query = "SELECT id1, id2, flag_active_1_to_2, flag_active_2_to_1 
        FROM datamart_browsing_controls";
$result = $db->query($query);
while($row = $result->fetch_assoc()){
    array_push($to_return['links'], array(
            $row['id1'],
            $row['id2'],
            $row['flag_active_1_to_2'] && $row['flag_active_2_to_1']));
}
echo json_encode($to_return);