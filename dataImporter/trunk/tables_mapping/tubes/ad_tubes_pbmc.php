<?php
$ad_tubes_pbmc["app_data"]["pkey"] = "aliquot_buffy_coat";
$ad_tubes_pbmc["app_data"]["file"] = "/Users/francois-michellheureux/Desktop/DataForMigration Lady Davies/Sardex_DrBasikData_blood_out.csv";

$ad_tubes_pbmc["master"]["barcode"] = "buffy coat_barcodes";
$ad_tubes_pbmc["master"]["collection_id"] = "";
$ad_tubes_pbmc["master"]["sample_master_id"] = "aliquot_buffy_coat";
$ad_tubes_pbmc["master"]["sop_master_id"] = "";
$ad_tubes_pbmc["master"]["initial_volume"] = "";
$ad_tubes_pbmc["master"]["current_volume"] = "";
$ad_tubes_pbmc["master"]["aliquot_volume_unit"] = "";
$ad_tubes_pbmc["master"]["in_stock"] = "buffy coat_status";
$ad_tubes_pbmc["master"]["in_stock_detail"] = "";
$ad_tubes_pbmc["master"]["study_summary_id"] = "";
$ad_tubes_pbmc["master"]["storage_datetime"] = "";
$ad_tubes_pbmc["master"]["storage_master_id"] = "";
$ad_tubes_pbmc["master"]["storage_coord_x"] = "";
$ad_tubes_pbmc["master"]["coord_x_order"] = "";
$ad_tubes_pbmc["master"]["storage_coord_y"] = "";
$ad_tubes_pbmc["master"]["coord_y_order"] = "";
$ad_tubes_pbmc["master"]["product_code"] = "";
$ad_tubes_pbmc["master"]["notes"] = "";


$ad_tubes_pbmc["detail"]["lot_number"] = "";
$ad_tubes_pbmc["detail"]["concentration"] = "";
$ad_tubes_pbmc["detail"]["concentration_unit"] = "";
$ad_tubes_pbmc["detail"]["cell_count"] = "";
$ad_tubes_pbmc["detail"]["cell_count_unit"] = "";



//DO NOT MODIFY-----------------
$ad_tubes_pbmc["master"]["aliquot_type"] = "@tube";
$ad_tubes_pbmc["master"]["aliquot_control_id"] = "@15";
$ad_tubes_pbmc["master"]["collection_id"] = "collection_id";

$ad_tubes_pbmc["detail"]["aliquot_master_id"] = "aliquot_master_id";

$ad_tubes_pbmc["app_data"]['child'] = array();
$ad_tubes_pbmc["app_data"]['master_table_name'] = "aliquot_masters";
$ad_tubes_pbmc["app_data"]['detail_table_name'] = "ad_tubes";
$ad_tubes_pbmc["app_data"]['detail_parent_key'] = "aliquot_master_id";
$ad_tubes_pbmc["app_data"]['parent_key'] = "sample_master_id";
$ad_tubes_pbmc["app_data"]['ask_parent']["collection_id"] = "collection_id";
$ad_tubes_pbmc["app_data"]['additional_queries'][] = "UPDATE aliquot_masters SET barcode='autogen %%last_master_insert_id%%' WHERE id=%%last_master_insert_id%% AND (barcode IS NULL OR barcode='')";
$tables['ad_tubes_pbmc'] = $ad_tubes_pbmc;
//-------------------------------
?>