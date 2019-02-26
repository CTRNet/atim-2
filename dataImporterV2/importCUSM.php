<?php

/**
 * 
 * @author     Marie G.
 * @version    
 * 
 * */

global $ids;
$ids = 1;
global $config;
$config = array(
    "warning" => "print",   // Options: print, save
    "error" => "print",     // Options: print, save
    "report" => "print",    // Options: print, save
    "idUserImport" => 1,
    "nowDatetime" => date("Y-m-d H:i:s"),
    "nowDate" => date("Y-m-d"),
    "headerLine" => 1,      // From top of the file: starts at 0
    "dataLine" => 2,        // From top of the file: starts at 0
    "identifier" => "Id Excel",
    "map" => array( "Id Excel" => "participants.id_temp", 
                    "Nom" => "{{participants.first_name}} {{participants.middle_name}}-{{participants.last_name}}",
                    "Status" => "participants.vital_status", 
                    "ID2" => "misc_identifiers.identifier_value",
                    "Diagnostic" => "diagnosis_masters.icd10_code",
                    "Infos" => "diagnosis_masters.previous_primary_code_system",
                    "Date" => "diagnosis_masters.dx_date"),
    "tables" => array("participants", "misc_identifiers", "diagnosis_masters", "diagnosis_controls", "event_masters", "event_controls"),
    "tablesLinks" => array("participants.id" => array("misc_identifiers" => "participant_id", "event_masters" => "participant_id", "diagnosis_masters" => "participant_id"),
                            "diagnosis_masters.id" => array("event_masters" => "diagnosis_master_id")),
    "rev" => array("participant", "misc_identifiers"),
);


//==============================================================================================
// Config
//==============================================================================================

$is_server = false;
// Database

$db_user 		= "root";
$db_pwd			= "";
$db_schema		= "atim_m";

if($is_server) {
    $db_user 		= "root";
    $db_pwd			= "";
    $db_schema		= "atim_m";
}

// File

$file_name = "ATiM.csv";

//$file_name = array("ATiM.csv","ATiM.csv","ATiM.csv");

$file_path = "C:\wamp64\www\scripts";

if($is_server) {
    $file_name = "ATiM.csv";
    $file_path = "/ch06chuma6134/";
}


// ==========================================================
//   ================== Import Parameters ==================
// ==========================================================



//==========================================================
//  ========= Database Connection & Reset tables =========
//==========================================================

$db_ip			= "localhost";
$db_port 		= "";
$db_charset		= "utf8";

global $db_connection;
$db_connection = @mysqli_connect(
	$db_ip.(!empty($db_port)? ":".$db_port : ''),
	$db_user,
	$db_pwd
) or importDie("DB connection: Could not connect to MySQL [".$db_ip.(!empty($db_port)? ":".$db_port : '')." / $db_user]", false);
if(!mysqli_set_charset($db_connection, $db_charset)){
	importDie("DB connection: Invalid charset", false);
}
@mysqli_select_db($db_connection, $db_schema) or importDie("DB connection: DB selection failed [$db_schema]", false);
mysqli_autocommit ($db_connection , false);


foreach($config["tables"] as $table){
    $query = mysqli_query($db_connection, "DROP TABLE IF EXISTS __temp_".$table);
    mysqli_commit($db_connection);

    $query = mysqli_query($db_connection, "CREATE TABLE __temp_".$table." AS (SELECT * FROM ".$table." WHERE 1=2)");
    mysqli_commit($db_connection);
        
}


//==========================================================
//   =============== Open and treat file ===============
//==========================================================
if (($handle = fopen($file_name, "r")) !== FALSE) {
   
    $header = NULL;
    $numLine = 0;
    $dataTemp = "";
    $infosRequete = array();
    $data = array();
    
    
    if (($handle = fopen($file_name, 'r')) !== FALSE)
    {
        
        // ==========================================================
        //   ============= Test separator for csv files ===========
        // ==========================================================
        $separator1 = ";";
        $separator2 = ",";
        
        $row = fgetcsv($handle, 1000, $separator1);
        if (sizeof($row) > 1){
            $separator = $separator1;
        } else {
            
            $row = fgetcsv($handle, 1000, $separator2);
            if (sizeof($row) > 1){
                $separator = $separator2;
            } else {
                messageToUser("error", "This file is not a CSV");
            }
        }
        // ========= /!\ Put the cursor back to the beginning of the file /!\ =========
        fseek($handle, 0);
        
        
        // ====================================================================
        //   ============= Get header and datas according to params =========
        // ====================================================================
        while (($row = fgetcsv($handle, 1000, $separator)) !== FALSE)
        {
            $separators = "";
            
            if($config["headerLine"] == $numLine){
                $header = $row;
                
                if(sizeof($config["map"]) != sizeof($header)){
                    messageToUser("error","There is a mismatch between the columns in the paramaters and those in the file to import. Did you make changes to your file format?<br />The columns listed in the parameters are: ".implode(", ", array_keys($config["map"])));
                }
            }
            else if ($numLine >= $config["dataLine"]){
            
                $dataTemp = array_combine($header, $row);  
                foreach($config["map"] as $key=>$field){
                    
                    if (!array_key_exists($key,$config["map"])){
                        messageToUser("error","The column \"".$key."\ was not defined in the parameters.");
                        continue;           // Skip to the next field
                    }
                    
                    // ==================== This field is not in the data extrated form the file ====================
                    if (!array_key_exists($key,$dataTemp)){
                        messageToUser("error","The value for the \"".$key."\" column is missing on the line with the \"".$config["identifier"]."\" equals to  \"".$dataTemp[$config["identifier"]]."\"");
                        continue;           // Skip to the next field
                    }
                    
                    if (preg_match_all("/{{(.*?)}}/", $field, $matches)){       // ------ Multiple fields in one ------
                        preg_match_all("/}}(.*?){{/", $field, $separators);
                        
                        $fields = $matches[1];
                        $separatorsList = $separators[1];
                        foreach($fields as $fieldDb){
                            $fieldTemp = explode(".",$fieldDb);
                            
                            if (sizeof($separatorsList)>0){            
                                if (strpos($dataTemp[$key], $separatorsList[0])){ 
                                    $requestTemp[$fieldTemp[0]][$fieldTemp[1]] = substr($dataTemp[$key], 0, strpos($dataTemp[$key], $separatorsList[0]));
                                    // ------ Remove used string form field ------
                                    $dataTemp[$key] = str_replace($requestTemp[$fieldTemp[0]][$fieldTemp[1]].$separatorsList[0], "", $dataTemp[$key]);
                                    // ------ Remove used separator ------
                                    unset($separatorsList[0]);
                                    $separatorsList = array_values($separatorsList);
                                } else {
                                  $requestTemp[$fieldTemp[0]][$fieldTemp[1]] = $dataTemp[$key];
                                }
                            } else {
                                  $requestTemp[$fieldTemp[0]][$fieldTemp[1]] = $dataTemp[$key];
                            }
                        }
                    } elseif (sizeof(explode(".",$field)) > 1) {                // ------ One field in one ------
                        $fieldTemp = explode(".",$field);
                        $requestTemp[$fieldTemp[0]][$fieldTemp[1]] = $dataTemp[$key];
                    } else {                                                    // ------ Fields temp waiting for treatment ------
                        $requestTemp[$field] = $dataTemp[$key];
                    }
                }
                $request[$dataTemp[$config["identifier"]]] = customizeBeforeInsert($requestTemp);
                insertEntry($request[$dataTemp[$config["identifier"]]]);
            }
            $numLine++;
        }
    }
    fclose($handle);
    
    mysqli_close($db_connection);
}




// ====================================================================================
//   ===================== Function to customize before insert =====================
// ====================================================================================
function customizeBeforeInsert($line){
    global $config;
    
    foreach($line as $key=>$field){
        
        switch ($key) {
            case "participants":
                $field["first_name"] = $field["first_name"]." ".$field["middle_name"];
                $field["middle_name_unknown"] = "y";
                $field["created_by"] = $config["idUserImport"];
                $field["modified_by"] = $config["idUserImport"];
                $field["last_modification"] = $config["nowDatetime"];

                unset($field["middle_name"]);           // Remove field when not needed
                unset($field["id_temp"]);           // Remove field when not needed
                break;
            case "misc_identifiers":
                $field["misc_identifier_control_id"] = 3;
                $field["created_by"] = $config["idUserImport"];
                $field["modified_by"] = $config["idUserImport"];
                break;
            case "diagnosis_masters":
                $field["created_by"] = $config["idUserImport"];
                $field["modified_by"] = $config["idUserImport"];
                break;
            } 

            // ========= /!\ Reasign the line to the array to return it  /!\ =========
            $line[$key] = $field;               
    }
    return $line;
}
 

// ====================================================================================
//   ===================== Function to treat messages to users =====================
// ====================================================================================
function messageToUser($type,$message){
    global $config;
    
    if ($config[$type] == "print"){
        echo $type.": ".$message."<br /><br />";
        
    } else {
        // Put message in db
        
    }
    
    
}


// ====================================================================================
//   ============== Function to validate before insertion in temp db ==============
// ====================================================================================
function validateStructureBeforeInsert($key,$field){
    global $config;
    global $db_connection;
    
    $r = mysqli_query($db_connection, "DESCRIBE `__temp_".$key."`");

    while($row = mysqli_fetch_array($r)) {
        
        // ========== Check if we have a value for the field in our datas ==========
        if (array_key_exists($row["Field"], $field)){
            
            // ========== Check size of value to insert ==========
            preg_match('/\((.*?)\)/', $row['Type'], $match);
            if (sizeof($match)>0){          
                $sizeMax = $match[1];
                if (strlen($field[$row["Field"]]) > $sizeMax){
                    messageToUser("error", "Field \"".$row["Field"]."\" is too long for \"".$key."\" table insert. <br /> -> Data: INSERT INTO __temp_".$key."(".implode(", ", array_keys($field)).") VALUES (\"".implode("\",\"", $field)."\")");
                }
            }
                
                
            if ($row["Type"]=="datetime"){
                echo $key." - ".$row["Field"]." - ".$row["Type"]."<br />";
                if (strlen($field[$row["Field"]]) == 4){
                    
                }
                
                
                if (strlen($field[$row["Field"]]) == 4){
                    
                }
                
            }

            if ($row["Type"]=="date"){
                echo $key." - ".$row["Field"]." - ".$row["Type"]."<br />";
            }
                
                
            
            //var_dump($row["Type"]);
            
        } elseif ($row["Null"] == "NO" && is_null($row["Default"])) {   // ========== Value is missing ==========
            messageToUser("error", "Field \"".$row["Field"]."\" is missing for \"".$key."\" table insert. <br /> -> Data: INSERT INTO __temp_".$key."(".implode(", ", array_keys($field)).") VALUES (\"".implode("\",\"", $field)."\")");
        }
    }
}



// ====================================================================================
//   ================ Function to insert an entry into the temp db  ================
// ====================================================================================
function insertEntry($line){
    global $config;
    global $db_connection;
    global $ids;
    
    $order = array();
    
    echo "<pre>";
    // ========== Add Id if none given ==========
     foreach($line as $key=>$field){
        
        if (!array_key_exists("id", $field)){
            $field["id"] = $ids++;
            $line[$key] = $field;
        } 
    } 
    
    // ========== Add foreign Ids to other tables ==========    
    foreach($line as $key=>$field){
        if (array_key_exists($key.".id",$config["tablesLinks"]) && sizeof($config["tablesLinks"][$key.".id"])>0){
            foreach($config["tablesLinks"][$key.".id"] as $tableForeignId=>$foreignId){
                if (array_key_exists($tableForeignId,$line)){
                    $line[$tableForeignId][$foreignId] = $field["id"];
                    //var_dump($line);
                }
            }
        }
    }

    foreach($line as $key=>$field){
        validateStructureBeforeInsert($key,$field);

        $query = "INSERT INTO __temp_".$key."(".implode(", ", array_keys($field)).") VALUES (\"".implode("\",\"", $field)."\")";
        //var_dump($query);
        mysqli_query($db_connection, $query);
    }
    echo "</pre>";
    
}
