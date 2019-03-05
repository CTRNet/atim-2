<?php
// ====================================================================================
//   ===================== Function to customize before insert =====================
// ====================================================================================
function customizeBeforeInsert($line){
    global $config; // you have access to all the defined variables of the params files
    
    foreach($line as $key=>$field){
        
        switch ($key) {
            case "participants":
                $field["first_name"] = $field["first_name"]." ".$field["middle_name"];
                $field["middle_name_unknown"] = "y";
                $field["created_by"] = $config["general"]["idUserImport"];
                $field["modified_by"] = $config["general"]["idUserImport"];
                $field["last_modification"] = $config["general"]["nowDatetime"];

                unset($field["middle_name"]);           // Remove field when not needed
                unset($field["id_temp"]);           // Remove field when not needed
                break;
            case "misc_identifiers":
                $field["misc_identifier_control_id"] = 3;
                $field["created_by"] = $config["general"]["idUserImport"];
                $field["modified_by"] = $config["general"]["idUserImport"];
                break;
            case "diagnosis_masters":
                $field["created_by"] = $config["general"]["idUserImport"];
                $field["modified_by"] = $config["general"]["idUserImport"];
                break;
            } 

            // ========= /!\ Reasign the line to the array to return it  /!\ =========
            $line[$key] = $field;               
    }
    return $line;
}
 
?>