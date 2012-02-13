<?php 
if(!isset($_SESSION)){
	session_start();
}
require_once("config.php");

if(isset($_GET['db'])){
	$_SESSION['db'] = $_GET['db']; 
}

global $db;
global $db_schema;
$db_schema = isset($_SESSION['db']) ? $_SESSION['db'] : NULL;
$db = getConnection();

function getConnection(){
	global $db_schema;
	global $config;
	$db = @new mysqli($config['mysql_host'], $config['mysql_user'], $config['mysql_pwd']);
	
	if ($db->connect_errno) {
	    die('Connect Error: ' . $db->connect_errno.": ".$db->connect_error."<br/>You need to configure the database connection in myFunctions.php");
	}
	if(!$db->set_charset("latin1")){
		die("We failed");
	}
	
	if($db_schema != NULL){
		if(!$db->select_db($db_schema)) {
			unset($_SESSION['db']);
			die($db->error);
		}
	}
	return $db;
}

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

function bindRow($stmt){
	$meta = $stmt->result_metadata();
	while ($field = $meta->fetch_field()){
		$params[] = &$row[$field->name];
	}
	call_user_func_array(array($stmt, 'bind_result'), $params); 
	return $row;
}


function getFieldsToUpdateQueryPart(array $key_values){
	$result = array();
	foreach($key_values as $key => $value){
		$result[] = '`'.$key.'`="'.$value.'"';
	}
	return implode(", ", $result);
}
?>