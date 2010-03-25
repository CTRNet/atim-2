<?php
$database['ip'] = "127.0.0.1";
$database['port'] = "8889";
$database['user'] = "root";
$database['pwd'] = "root";
$database['schema'] = "atim_lady";
$database['charset'] = "latin1";

$config['printQueries'] = false;

//-----addon querries------
//querries listed here will be run at the end of the process
//clinical_collection_links
$addonQueries[] = "INSERT INTO clinical_collection_links(`participant_id`, `collection_id`, `consent_master_id`, `created`, `created_by`, `modified`, `modified_by`) 
SELECT p.mysql_id, col.mysql_id, con.mysql_id, NOW(), '1', NOW(), '1' FROM `id_linking` AS p
LEFT JOIN id_linking AS con ON substr(p.csv_id, 2)=substr(con.csv_id, 6) AND con.model='consent_masters'
LEFT JOIN id_linking AS col ON p.csv_id=col.csv_reference AND col.model='collections'
WHERE p.model = 'participants'";
//-------------------------




global $created;
$created_id = 1;

