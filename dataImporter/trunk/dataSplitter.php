<?php
require_once("commonFunctions.php");
/*
 * use_key = primary specimen key
 * group key = group key
 * split key = key generated by the split
 * split = the split will be based on that column
 * copy = columns to copy by the split operation
 */

//bloods
//$file_name = "/Users/francois-michellheureux/Documents/jewish/newData/bloods.csv";
//$file_out = "/Users/francois-michellheureux/Documents/jewish/newData/bloodsOut.csv";
//$split_on[] = array('use_key' => 'blood_id',
//					'group_key' => 'plasma_group_id',
//					'split_key' => 'plasma_parent_id',
//					'split' => array("plasma_barcodes" => ""),
//					'copy' => array("plasma_status" => "", "plasma_status_reason" => "", "plasma_notes" => "", "plasma_creation_date" => ""));
//$split_on[] = array('use_key' => 'blood_id',
//					'group_key' => 'serum_group_id',
//					'split_key' => 'serum_parent_id',
//					'split' => array("serum_barcodes" => ""),
//					'copy' => array("serum_creation_date" => "", "serum_notes" => "", "serum_status" => "", "serum_status_reason" => ""));
//$split_on[] = array('use_key' => 'blood_id',
//					'group_key' => 'buffy_group_id',
//					'split_key' => 'buffy_parent_id',
//					'split' => array("buffy coat_barcodes" => ""),
//					'copy' => array("buffy coat_creation_date" => "", "buffy coat_notes" => "", "buffy coat_status" => "", "buffy coat_status_reason" => ""));

//$file_name = "/Users/francois-michellheureux/Documents/jewish/newData/tissues.csv";
//$file_out = "/Users/francois-michellheureux/Documents/jewish/newData/tissuesOut.csv";
//$split_on[] = array('use_key' => 'tissue_id',
//					'group_key' => 'tube_group_id',
//					'split_key' => 'tube_parent_id',
//					'split' => array("tissue_tube_barcodes" => ""),
//					'copy' => array("tissue_tube_status" => "",	"tissue_tube_status_reason" => "",	"tissue_tube_notes" => ""));
$file_name = "/Users/francois-michellheureux/Documents/Jen/import.csv";
$file_out = "/Users/francois-michellheureux/Documents/Jen/bloods.csv";
$split_on[] = array('use_key' => 'sample_id',
					'group_key' => 'plasma_group_id',
					'split_key' => 'plasma_parent_id',
					'split' => array("Position plasma" => ""),
					'copy' => array("Volume de chaque aliquot" => "",	"Unit" => "",	"Entreposage plasma" => "", "study_id" => "", "acquisition_label" => ""));


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
echo(substr($result, 0, strlen($result) - 1).Config::$line_break_tag);
fwrite($fh_out, substr($result, 0, strlen($result) - 1).Config::$line_break_tag);

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
//			echo($split_col_name.Config::$line_break_tag);
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
	echo (strlen($result) > 0 ? substr($result, 0, strlen($result) - 1) : "").Config::$line_break_tag;
	fwrite($fh_out, (strlen($result) > 0 ? substr($result, 0, strlen($result) - 1) : "").Config::$line_break_tag);
	
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
		echo (strlen($result) > 0 ? substr($result, 0, strlen($result) - 1) : "").Config::$line_break_tag;
		fwrite($fh_out, (strlen($result) > 0 ? substr($result, 0, strlen($result) - 1) : "").Config::$line_break_tag);
	}
}

function getSplit($value_to_split){
	$result = array();
	if(strpos($value_to_split, "[") !== false){
		//separate every [value1][value2]
		while(($index = strpos($value_to_split, "[")) !== false){
			$result[] = substr($value_to_split, $index + 1, strpos($value_to_split, "]") - $index - 1);
			$value_to_split = substr($value_to_split, strpos($value_to_split, "]") + 1);
		}	
	}else if(strpos($value_to_split, "-") !== false){
		//it's a range, will work with numerical values
		list($min, $max) = explode("-", $value_to_split);
		$result = range($min, $max);
	}else if(strpos($value_to_split, ",")){
		$result = explode(",", $value_to_split);
	}
	return $result;
}
