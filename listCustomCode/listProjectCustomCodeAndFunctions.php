<?php

$path = $argv[1];
if(!is_dir($path)) die("\nWrong path [$path]\n");
$Iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
$i=1;
$j=1;
$k=1;
$output="";
foreach($Iterator as $file){
    if(substr($file,-4) == '.php' && (preg_match('/.*Plugin.+((Controller)|(Model)|(View)).+Hook.*/', $file) || preg_match('/.*Plugin.+((Controller)|(Model)|(View)).+Custom.*/', $file))) {
        $file_name = str_replace($path, '', $file);
        if(!preg_match('/((Customize.Controller.CustomizeAppController.php)|(app.Plugin.Customize.Model.CustomizeAppModel.php))/', $file_name)) {
            echo "$file_name\n";
            if (preg_match_all('/\n\s+function\s&?([a-zA-Z_]+[a-zA-Z0-9_]*)\((.*)\)/', file_get_contents($file), $matches)) {
                foreach($matches[1] as $key => $function) {
                    echo "\t - function $function(".(isset($matches[1][$key])? $matches[1][$key]: '').")\n";
                }
            }
            echo "\n";
        }
	}
}
$i--;
echo "\nTotal Custom files: ".$i."\n";