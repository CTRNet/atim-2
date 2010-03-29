<?php
require_once("myFunctions.php");
$mysqli->autocommit(false);
if(isset($_POST['sql'])){
	$mysqli->multi_query(get_magic_quotes_gpc() ? stripslashes($_POST['sql']) : $_POST['sql']) or die($mysqli->error);
    do {
        /* Stockage du premier rŽsultat */
        if ($result = $mysqli->store_result()) {
            while ($row = $result->fetch_row()) {
                printf("%s\n", $row[0]);
            }
            $result->free();
        }
        /* Affichage d'une sŽparation */
        if ($mysqli->more_results()) {
            printf("-----------------\n");
        }
    } while ($mysqli->next_result());
}
$mysqli->commit() or die($mysqli->error);
if ($mysqli->errno) {
  echo "Stopped while retrieving result : ".$mysqli->error;
}else{
	echo("WIN");
}
$mysqli->close();
?>