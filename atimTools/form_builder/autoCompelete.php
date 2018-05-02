<?php

require_once("../common/myFunctions.php");
$data = json_decode($_POST["data"]);

$query = "SELECT " . $data->field . " value FROM " . $data->table . " ORDER BY " . $data->order;
$db = getConnection();
$stmt = $db->prepare($query) or die("Select a database;");
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$response = array();
if ($row) {
    while ($row) {
        $response[]=$row['value'];
        $row = $res->fetch_assoc();
    }
}
echo json_encode($response);