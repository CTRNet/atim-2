<?php
$collections["app_data"]["pkey"] = "collection_id";
$collections["app_data"]["file"] = "/Users/francois-michellheureux/Documents/jewish/newData/collections_fake.csv";
$collections["app_data"]['csv_reference'] = "no_labo";

$collections["master"]["acquisition_label"] = "";
$collections["master"]["bank_id"] = "";
$collections["master"]["collection_site"] = "collection_site";
$collections["master"]["collection_datetime"] = "collection_datetime";
$collections["master"]["collection_datetime_accuracy"] = "";
$collections["master"]["sop_master_id"] = "";
$collections["master"]["collection_property"] = "";
$collections["master"]["collection_notes"] = "";

//do not modify this section
$collections["app_data"]['child'][] = "sd_spe_bloods";
$collections["app_data"]['child'][] = "sd_spe_tissues";
$collections["app_data"]['save_id'] = true;
$collections["app_data"]['master_table_name'] = "collections";
$tables['collections'] = $collections;
//-------------------------------