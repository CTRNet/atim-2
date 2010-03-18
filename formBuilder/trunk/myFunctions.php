<?php 
$db_schema = 'atim_hepato';

$mysqli = @new mysqli('localhost', 'root', 'root', $db_schema);

if ($mysqli->connect_errno) {
    die('Connect Error: ' . $mysqli->connect_errno);
}
if(!$mysqli->set_charset("latin1")){
	die("We failed");
}
//mysqli_autocommit($connection, false);
?>