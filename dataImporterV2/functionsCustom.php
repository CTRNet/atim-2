<?php
// ====================================================================================
//   ===================== Function to customize before insert =====================
// ====================================================================================
function customizeBeforeInsert($line){
    global $config; // you have access to all the defined variables of the params files. Ex: $config["general"]["idUserImport"], $config["general"]["nowDatetime"] and $config["general"]["nowDate"] 
    global $dbConnection; // you have access to all the defined variables of the params files
        
    $diagnosisControls = array();
    $query = "SELECT * FROM diagnosis_controls WHERE flag_active=1";
    $result = mysqli_query($dbConnection,$query) or die(mysqli_error($dbConnection));
    while ($row = mysqli_fetch_assoc($result)){
        $diagnosisControls[$row['category'].'-'.$row['controls_type']] = $row;
    }

    $miscIdentifierControls = array();
    $query = "SELECT * FROM misc_identifier_controls WHERE flag_active=1";
    $result = mysqli_query($dbConnection,$query) or die(mysqli_error($dbConnection));
    while ($row = mysqli_fetch_assoc($result)){
        $miscIdentifierControls[$row['misc_identifier_name']] = $row;
    }

    foreach($line as $key=>$field){


        $oldKey = $key;
        preg_match_all("/\[(.*?)\]/", $key, $nameTable);
        if (sizeof($nameTable[0])>0){
            $key = str_replace($nameTable[0][0], "", $key);
        }

        switch ($key) {
            case "participants":
                unset($field["id_temp"]);           // Remove field when not needed
                break;
            case "misc_identifiers":
                $miscIdentifierName = '';
                $identifierValue = $field['identifier_value'];
                if(preg_match('/^[A-Z]{4}[0-9]+$/',$identifierValue)) {
                    $miscIdentifierName = 'ramq nbr';
                } elseif(preg_match('/^[0-9]+MGH$/',$identifierValue)) {
                    $miscIdentifierName = 'MGH-MRN';
                } elseif(preg_match('/^[0-9]+RVH$/',$identifierValue)) {
                    $miscIdentifierName = 'RVC-MRN';
                }
                if($miscIdentifierName) {
                    $field["misc_identifier_control_id"] = $miscIdentifierControls[$miscIdentifierName]['id'];
                    $field["flag_unique"] = $miscIdentifierControls[$miscIdentifierName]['flag_unique'];
                } else {
                    unset($line[$key]);
                }
                break;
            case "diagnosis_masters":
                $field["diagnosis_control_id"] = $diagnosisControls['primary-tumor registry']['id'];
                break;

        }
        // ========= /!\ Reasign the line to the array to return it  /!\ =========
        $line[$oldKey] = $field;
    }
    return $line;
}
 
?>