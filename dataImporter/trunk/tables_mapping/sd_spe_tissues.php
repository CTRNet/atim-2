<?php
$sd_spe_tissues["app_data"]["pkey"] = "tissue_id";
$sd_spe_tissues["app_data"]["file"] = "/Users/francois-michellheureux/Desktop/DataForMigration Lady Davies/Sardex_DrBasikData_tissue.csv";

$sd_spe_tissues["detail"]["sample_master_id"] = "";
$sd_spe_tissues["detail"]["tissue_source"] = "";
$sd_spe_tissues["detail"]["tissue_nature"] = "";
$sd_spe_tissues["detail"]["tissue_laterality"] = "";
$sd_spe_tissues["detail"]["pathology_reception_datetime"] = "";
$sd_spe_tissues["detail"]["tissue_size"] = "";
$sd_spe_tissues["detail"]["tissue_size_unit"] = "";

$sd_spe_tissues["master"]["sample_code"] = "tissue_id";//Required
$sd_spe_tissues["master"]["collection_id"] = "no_labo";
$sd_spe_tissues["master"]["sop_master_id"] = "";
$sd_spe_tissues["master"]["product_code"] = "";
$sd_spe_tissues["master"]["is_problematic"] = "";
$sd_spe_tissues["master"]["notes"] = "tissue_notes";


//do not modify this section
$sd_spe_tissues["detail"]["sample_master_id"] = "sample_master_id";

$sd_spe_tissues["master"]["sample_type"] = "@tissue";
$sd_spe_tissues["master"]["sample_control_id"] = "@3";
$sd_spe_tissues["master"]["sample_category"] = "@specimen";
//$sd_spe_tissues["master"]["initial_specimen_sample_id"];
$sd_spe_tissues["master"]["initial_specimen_sample_type"] = "@tissue";
$sd_spe_tissues["master"]["parent_id"] = ""; //NULL

$sd_spe_tissues["app_data"]['child'] = array();
$sd_spe_tissues["app_data"]['master_table_name'] = "sample_masters";
$sd_spe_tissues["app_data"]['detail_table_name'] = "sd_spe_tissues";
$sd_spe_tissues["app_data"]['detail_parent_key'] = "sample_master_id";
$sd_spe_tissues["app_data"]['parent_key'] = "collection_id";
$sd_spe_tissues["app_data"]['additional_queries'][] = "UPDATE sample_masters SET initial_specimen_sample_id=%%last_master_insert_id%% WHERE id=%%last_master_insert_id%%";
$tables['sd_spe_tissues'] = $sd_spe_tissues;
//-------------------------------
