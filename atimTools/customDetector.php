<?php
/**
 * Finds directories named either hooks or customs
 * If an argument is provided, it is used as the starting directory. If no argument is
 * provided, the starting directory is the one calling the script.
 */

$to_do = array();
$to_do[] = count($argv) > 1 ? $argv[1] : getcwd();

if(!is_dir($to_do[0])){
	die("Invalid directory\n");
}

echo "*** Program start ***\n";
echo "Parsing directory ",$to_do[0],"\n";
while($to_do){
	$parent_dir = array_pop($to_do);
	$d = dir($parent_dir);
	while(false !== ($current = $d->read())){
		if($current == '.' || $current == '..'){
			continue;
		}
		$val = $parent_dir.'/'.$current;
		if(is_dir($val)){
			$to_do[] = $val;
		}
		
		$lower = strtolower($current); 
		if($lower == 'hooks' || $lower == 'customs'){
			echo $val,"\n";
		}
	}
}
echo "*** Program terminated ***\n";