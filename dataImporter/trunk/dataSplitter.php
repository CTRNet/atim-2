<?php
require_once("commonFunctions.php");

$split_on[] = array('use_key' => 'sample_code',
					'group_key' => 'sample_code_plasma',
					'split_key' => 'aliquot_plasma',
					'split' => array("plasma_barcodes" => ""),
					'copy' => array("plasma_status" => "", "plasma_status_reason" => ""));
$split_on[] = array('use_key' => 'sample_code',
					'group_key' => 'sample_code_serum',
					'split_key' => 'aliquot_serum',
					'split' => array("serum_barcodes" => "", "serum_pwet" => ""),
					'copy' => array("serum_creation_date" => "", "serum_notes" => "", "serum_status" => "", "serum_status_reason" => ""));
$split_on[] = array('use_key' => 'sample_code',
					'group_key' => 'sample_code_buffy_coat',
					'split_key' => 'aliquot_buffy_coat',
					'split' => array("buffy coat_barcodes" => ""),
					'copy' => array("buffy coat_creation_date" => "", "buffy coat_notes" => "", "buffy coat_status" => "", "buffy coat_status_reason" => ""));


$file_name = "/Users/francois-michellheureux/Desktop/DataForMigration Lady Davies/Sardex_DrBasikData_blood.csv";
$file_out = "/Users/francois-michellheureux/Desktop/DataForMigration Lady Davies/Sardex_DrBasikData_blood_out.csv";
$fh = fopen($file_name, 'r');
global $fh_out;
$fh_out = fopen($file_out, 'w');
if(!fh){
	die("fopen failed on ".$file_name);
}

$keys = lineToArray(fgets($fh, 4096));
foreach($split_on as $split_unit){
	$keys[] = $split_unit['group_key'];
	$keys[] = $split_unit['split_key'];
}


$result = "";
foreach($keys as $key){
	$result .= '"'.$key.'";';
}
echo(substr($result, 0, strlen($result) - 1)."\n");
fwrite($fh_out, substr($result, 0, strlen($result) - 1)."\n");

while(!feof($fh)){
	$values = lineToArray(fgets($fh, 4096));
	associate($keys, $values);
	printLine($values, $split_on);
}
flush();
fclose($fh);
fclose($fh_out);

function printLine($values, $split_on){
	global $fh_out;
	$max_size = 0;
	foreach($split_on as &$split_unit){
		foreach($split_unit['split'] as $split_col_name => $splitted_arr){
			$split_unit['split'][$split_col_name] = getSplit($values[$split_col_name]);
			$max_size = max($max_size, sizeOf($split_unit['split'][$split_col_name]));
//			echo($split_col_name."\n");
//			print_r($split_unit['split'][$split_col_name]);
			//replace splitted values for the first line and add key
			if(sizeOf($split_unit['split'][$split_col_name]) > 0){
				$values[$split_col_name] = $split_unit['split'][$split_col_name][0];
				$split_unit['using_key'] = $values[$split_unit['use_key']];
				$values[$split_unit['group_key']] = $split_unit['using_key'];
				$values[$split_unit['split_key']] = $split_unit['using_key'];
			}
		}
		foreach($split_unit['copy'] as $key => $empty){
			$split_unit['copy'][$key] = $values[$key];
		}
	}
	
	$result = "";
	foreach($values as $key => &$value){
		if(!is_numeric($key)){
			$result .= '"'.$value.'";';
		}
		//clear all values
		$value = "";
	}
	
	//print first line
	echo (strlen($result) > 0 ? substr($result, 0, strlen($result) - 1) : "")."\n";
	fwrite($fh_out, (strlen($result) > 0 ? substr($result, 0, strlen($result) - 1) : "")."\n");
	
	//print additional lines
	for($i = 1; $i < $max_size; ++ $i){
		//build the value array
		foreach($split_on as &$split_unit){
			$splitted = false;
			foreach($split_unit['split'] as $split_col_name => $splitted_arr){
				if(sizeOf($split_unit['split'][$split_col_name]) > $i){
					$splitted = true;
					$values[$split_col_name] = $split_unit['split'][$split_col_name][$i];
					$values[$split_unit['split_key']] = $split_unit['using_key'];
				}
			}
			if($splitted){
				foreach($split_unit['copy'] as $key => $val){
					$values[$key] = $val;
				}
			}
		}		
		
		//make the line
		$result = "";
		foreach($values as $key => &$value){
			if(!is_numeric($key)){
				$result .= '"'.$value.'";';
			}
			//clear all values
			$value = "";
		}

		//print the line
		echo (strlen($result) > 0 ? substr($result, 0, strlen($result) - 1) : "")."\n";
		fwrite($fh_out, (strlen($result) > 0 ? substr($result, 0, strlen($result) - 1) : "")."\n");
	}
}

function getSplit($value_to_split){
	$result = array();
	while(($index = strpos($value_to_split, "[")) !== false){
		$result[] = substr($value_to_split, $index + 1, strpos($value_to_split, "]") - $index - 1);
		$value_to_split = substr($value_to_split, strpos($value_to_split, "]") + 1);
	}	
	return $result;
}
