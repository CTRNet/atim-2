<?php
$sd_der_pbmcs["app_data"]["pkey"] = "buffy_group_id";
$sd_der_pbmcs["app_data"]["file"] = "/Users/francois-michellheureux/Documents/jewish/newData/bloodsOut.csv";

$sd_der_pbmcs["master"]["sample_code"] = "@buffy_tmp";//Required
$sd_der_pbmcs["master"]["sop_master_id"] = "";
$sd_der_pbmcs["master"]["product_code"] = "";
$sd_der_pbmcs["master"]["is_problematic"] = "";
$sd_der_pbmcs["master"]["notes"] = "";
$sd_der_pbmcs["master"]["parent_id"] = "blood_id";


//do not modify this section
$sd_der_pbmcs["detail"][0]["sample_master_id"] = "sample_master_id";
$sd_der_pbmcs["detail"][1]["sample_master_id"] = "sample_master_id";

$sd_der_pbmcs["master"]["sample_type"] = "@pbmc";
$sd_der_pbmcs["master"]["sample_control_id"] = "@8";
$sd_der_pbmcs["master"]["sample_category"] = "@derivative";
$sd_der_pbmcs["master"]["initial_specimen_sample_type"] = "@blood";
$sd_der_pbmcs["master"]["collection_id"] = "collection_id";
$sd_der_pbmcs["master"]["initial_specimen_sample_id"] = "initial_specimen_sample_id";

$sd_der_pbmcs["app_data"]['child'][] = "ad_tubes_pbmc";
$sd_der_pbmcs["app_data"]['master_table_name'] = "sample_masters";
$sd_der_pbmcs["app_data"]['detail_table_name'][0] = "sd_der_pbmcs";
$sd_der_pbmcs["app_data"]['detail_table_name'][1] = "derivative_details";
$sd_der_pbmcs["app_data"]['detail_parent_key'] = "sample_master_id";
$sd_der_pbmcs["app_data"]['parent_key'] = "parent_id";
$sd_der_pbmcs["app_data"]['ask_parent']["collection_id"] = "collection_id";
$sd_der_pbmcs["app_data"]['ask_parent']["id"] = "initial_specimen_sample_id";
$sd_der_pbmcs["app_data"]['additional_queries'][] = "UPDATE sample_masters SET sample_code='PBMC - %%last_master_insert_id%%' WHERE id=%%last_master_insert_id%%";
$tables['sd_der_pbmcs'] = $sd_der_pbmcs;
//-------------------------------
?>