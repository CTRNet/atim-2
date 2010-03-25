<?php 
session_start();
if(isset($_GET['db'])){
	$_SESSION['db'] = $_GET['db']; 
}

$db_schema = isset($_SESSION['db']) ? $_SESSION['db'] : 'atim_lady';
global $mysqli;
$mysqli = @new mysqli('localhost', 'root', 'root', $db_schema);

if ($mysqli->connect_errno) {
    die('Connect Error: ' . $mysqli->connect_errno);
}
if(!$mysqli->set_charset("latin1")){
	die("We failed");
}
//mysqli_autocommit($connection, false);

function getMyPostedVariable2($variableName){
	return isset($_POST[$variableName]) ? protectUserVariable2($_POST[$variableName]) : "";
}

function getMyGetedVariable2($variableName){
	global $_GET;
	return isset($_GET[$variableName]) ? protectUserVariable2($_GET[$variableName]) : "";
}

function protectUserVariable2($var){
	$returnValue = "";
	global $mysqli;
	if (get_magic_quotes_gpc()){
		$returnValue = $mysqli->real_escape_string(htmlspecialchars(stripslashes(rtrim(ltrim($var)))));
	} else{
		$returnValue = $mysqli->real_escape_string(htmlspecialchars(rtrim(ltrim($var))));
	}
	return $returnValue;
}

?>