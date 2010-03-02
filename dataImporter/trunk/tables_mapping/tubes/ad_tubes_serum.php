<?php
$ad_tubes_serum["app_data"]["pkey"] = "aliquot_serum";
$ad_tubes_serum["app_data"]["file"] = "/Users/francois-michellheureux/Desktop/DataForMigration Lady Davies/Sardex_DrBasikData_blood_out.csv";

$ad_tubes_serum["master"]["barcode"] = "serum_barcodes";
$ad_tubes_serum["master"]["collection_id"] = "";
$ad_tubes_serum["master"]["sample_master_id"] = "aliquot_serum";
$ad_tubes_serum["master"]["sop_master_id"] = "";
$ad_tubes_serum["master"]["initial_volume"] = "";
$ad_tubes_serum["master"]["current_volume"] = "";
$ad_tubes_serum["master"]["aliquot_volume_unit"] = "";
$ad_tubes_serum["master"]["in_stock"] = "serum_status";
$ad_tubes_serum["master"]["in_stock_detail"] = "";
$ad_tubes_serum["master"]["study_summary_id"] = "";
$ad_tubes_serum["master"]["storage_datetime"] = "";
$ad_tubes_serum["master"]["storage_master_id"] = "";
$ad_tubes_serum["master"]["storage_coord_x"] = "";
$ad_tubes_serum["master"]["coord_x_order"] = "";
$ad_tubes_serum["master"]["storage_coord_y"] = "";
$ad_tubes_serum["master"]["coord_y_order"] = "";
$ad_tubes_serum["master"]["product_code"] = "";
$ad_tubes_serum["master"]["notes"] = "";


$ad_tubes_serum["detail"]["lot_number"] = "";
$ad_tubes_serum["detail"]["concentration"] = "";
$ad_tubes_serum["detail"]["concentration_unit"] = "";
$ad_tubes_serum["detail"]["cell_count"] = "";
$ad_tubes_serum["detail"]["cell_count_unit"] = "";



//DO NOT MODIFY-----------------
$ad_tubes_serum["master"]["aliquot_type"] = "@tube";
$ad_tubes_serum["master"]["aliquot_control_id"] = "@8";
$ad_tubes_serum["master"]["collection_id"] = "collection_id";

$ad_tubes_serum["detail"]["aliquot_master_id"] = "aliquot_master_id";

$ad_tubes_serum["app_data"]['child'] = array();
$ad_tubes_serum["app_data"]['master_table_name'] = "aliquot_masters";
$ad_tubes_serum["app_data"]['detail_table_name'] = "ad_tubes";
$ad_tubes_serum["app_data"]['detail_parent_key'] = "aliquot_master_id";
$ad_tubes_serum["app_data"]['parent_key'] = "sample_master_id";
$ad_tubes_serum["app_data"]['ask_parent']["collection_id"] = "collection_id";
$ad_tubes_serum["app_data"]['additional_queries'][] = "UPDATE aliquot_masters SET barcode='autogen %%last_master_insert_id%%' WHERE id=%%last_master_insert_id%% AND (barcode IS NULL OR barcode='')";
$tables['ad_tubes_serum'] = $ad_tubes_serum;
//-------------------------------
?>