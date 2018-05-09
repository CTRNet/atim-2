<?php

require_once("../common/myFunctions.php");

$query = "SELECT id , model, display_name FROM `datamart_structures` ORDER BY id";
$db = getConnection();
$stmt = $db->prepare($query) or die("Query failed at line " . __LINE__ . " " . $query . " " . $db->error);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$response = array();
$dms = array();
if ($row) {
    while ($row) {
        $response[] = array('id' => $row['id'], 'model' => $row['model']);
        $dms[] = array('id' => $row['id'], 'name' => $row['display_name']);
        $row = $res->fetch_assoc();
    }
}


$query = "SELECT id1, id2 , flag_active_1_to_2 active FROM `datamart_browsing_controls` ORDER BY id1";
$db = getConnection();
$stmt = $db->prepare($query) or die("Query failed at line " . __LINE__ . " " . $query . " " . $db->error);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$dmbc = array();
if ($row) {
    while ($row) {
        $dmbc[] = array($row['id1'], $row['id2'], $row['active']);
        $row = $res->fetch_assoc();
    }
}


echo json_encode(array('idModel' => $response, 'dmbc' => $dmbc, 'dms' => $dms));
