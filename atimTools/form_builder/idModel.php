<?php

require_once("../common/myFunctions.php");

$query = "SELECT id , model, display_name, plugin FROM `datamart_structures` ORDER BY id";
$db = getConnection();
$stmt = $db->prepare($query) or die("Query failed at line " . __LINE__ . " " . $query . " " . $db->error);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$response = array();
$dms = array();
if ($row) {
    while ($row) {
        $response[] = array('id' => $row['id'], 'plugin' => $row['plugin'], 'model' => $row['model'], 'name' => $row['display_name']);
        $row = $res->fetch_assoc();
    }
}


$query = "SELECT id1, id2 , flag_active_1_to_2 active , ".
            "DS1.model model1, DS1.plugin plugin1, ".
            "DS2.model model2, DS2.plugin plugin2 ".
        "FROM `datamart_browsing_controls` DBC ".
        "JOIN `datamart_structures`  DS1 ON DS1.id = DBC.id1 ".
        "JOIN `datamart_structures`  DS2 ON DS2.id = DBC.id2 ".
        "ORDER BY id1";
$db = getConnection();
$stmt = $db->prepare($query) or die("Query failed at line " . __LINE__ . " " . $query . " " . $db->error);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$dmbc = array();
if ($row) {
    while ($row) {
        $q1 = "(SELECT id FROM `datamart_structures` where model = '".$row['model1']."' and plugin = '".$row['plugin1']."')";
        $q2 = "(SELECT id FROM `datamart_structures` where model = '".$row['model2']."' and plugin = '".$row['plugin2']."')";
        $dmbc[] = array($row['id1'], $row['id2'], $row['active'], $q1, $q2);
        $row = $res->fetch_assoc();
    }
}


echo json_encode(array('idModel' => $response, 'dmbc' => $dmbc));
