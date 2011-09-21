<?php
/**
 * @author FM L'Heureux
 * Generates the queries to run to merge 2 collections. Simply put the "from"
 * and "to" acquisition label in the $from_to variable bellow, like in the
 * example.
 */
//format: from;to\n
//$example_from_to = 
//"acquisition_label_from; acquisition_label_to
//acquisition_label_from2; acquisition_label_to2
//acquisition_label_fromX; acquisition_label_toX";

$from_to = "";//posts will override this

function explodeWalk(&$line){
	$exploded = explode(";", $line);
	$line = count($exploded) == 2 ? array('from' => trim($exploded[0]), 'to' => trim($exploded[1])) : null;
}
function printUpdateQueries($line){
	printf("UPDATE aliquot_masters SET collection_id=(SELECT id FROM collections WHERE acquisition_label='%s') WHERE collection_id=(SELECT id FROM collections WHERE acquisition_label='%s');\n", $line['to'], $line['from']);
	printf("UPDATE aliquot_masters_revs SET collection_id=(SELECT id FROM collections WHERE acquisition_label='%s') WHERE collection_id=(SELECT id FROM collections WHERE acquisition_label='%s');\n", $line['to'], $line['from']);
	
	printf("UPDATE sample_masters SET collection_id=(SELECT id FROM collections WHERE acquisition_label='%s') WHERE collection_id=(SELECT id FROM collections WHERE acquisition_label='%s');\n", $line['to'], $line['from']);
	printf("UPDATE sample_masters_revs SET collection_id=(SELECT id FROM collections WHERE acquisition_label='%s') WHERE collection_id=(SELECT id FROM collections WHERE acquisition_label='%s');\n", $line['to'], $line['from']);
	
	printf("UPDATE specimen_review_masters SET collection_id=(SELECT id FROM collections WHERE acquisition_label='%s') WHERE collection_id=(SELECT id FROM collections WHERE acquisition_label='%s');\n", $line['to'], $line['from']);
	printf("UPDATE specimen_review_masters_revs SET collection_id=(SELECT id FROM collections WHERE acquisition_label='%s') WHERE collection_id=(SELECT id FROM collections WHERE acquisition_label='%s');\n\n", $line['to'], $line['from']);
}
function keepFrom($line){
	return $line['from'];
}

function myTrim(&$line){
	$line= trim($line, " \t	");
}

if(!empty($_POST)){
	$from_to = $_POST['data'];	
}

//return the result
$from_to = explode("\n", $from_to);
array_walk($from_to, 'myTrim');
array_walk($from_to, 'explodeWalk');
$from_to = array_filter($from_to);

if(!empty($from_to)){
	array_walk($from_to, 'printUpdateQueries');
	
	$from = array_map('keepFrom', $from_to);
	$imploded_from = "'".implode("', '", $from)."'";
	
	printf("DELETE FROM clinical_collection_links WHERE collection_id IN (SELECT id FROM collections WHERE acquisition_label IN(%s));\n", $imploded_from);
	printf("DELETE FROM clinical_collection_links_revs WHERE collection_id IN (SELECT id FROM collections WHERE acquisition_label IN(%s));\n", $imploded_from);
	printf("DELETE FROM collections WHERE acquisition_label IN(%s);\n", $imploded_from);
	printf("DELETE FROM collections_revs WHERE acquisition_label IN(%s);\n\n", $imploded_from);
}
?>
