<?php
$collections["app_data"]["pkey"] = "collection_pkey";
$collections["app_data"]["file"] = "/Users/francois-michellheureux/Desktop/DataForMigration Lady Davies/test/collections_fake.csv";

$collections["master"]["acquisition_label"] = "collection_pkey";
$collections["master"]["bank_id"] = "";
$collections["master"]["collection_site"] = "";
$collections["master"]["collection_datetime"] = "";
$collections["master"]["collection_datetime_accuracy"] = "";
$collections["master"]["sop_master_id"] = "";
$collections["master"]["collection_property"] = "collection_property";
$collections["master"]["collection_notes"] = "";

//do not modify this section
$collections["app_data"]['child'][] = "sd_spe_bloods";
$collections["app_data"]['child'][] = "sd_spe_tissues";
$collections["app_data"]['save_id'] = true;
$collections["app_data"]['master_table_name'] = "collections";
$tables['collections'] = $collections;
//-------------------------------