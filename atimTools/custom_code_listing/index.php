<?php
/**
 * @author N Luc
 * @date 2016-01-04
 * @description: List all custom code
 */
require_once("../common/myFunctions.php");
global $config;
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Custom Code</title>
<script type="text/javascript" src="../common/js/jquery-1.4.4.min.js"></script>
<script type="text/javascript">
$(function(){
	$("select").change(function(){
		document.location = "?atim=" + $(this).val();
	});
});
</script>
<style type="text/css">
body{
	font-family: arial;
	font-size: 85%;
}
</style>
</head>
<body>
<div id="top">
	Select ATiM Directory: 
	<?php 
	$server_dir_path = $config['server_document_root'];
	$atim_dir_names = array();
	$selected_atim_dir_name = isset($_GET['atim'])? $_GET['atim'] : '';
	$error_msg = '';
	if(is_dir($server_dir_path)) {
		$server_dir_handle = opendir($server_dir_path);
		while (false !== ($new_server_sub_dir_name = readdir($server_dir_handle))) {
			$atim_path_to_test = $config['server_document_root'].'/'.$new_server_sub_dir_name;
			if(is_dir($atim_path_to_test) && is_dir($atim_path_to_test.'/app/plugin')) {
				$atim_dir_names[] = $new_server_sub_dir_name;
			}
		}
		if(empty($atim_dir_names)) {
			$error_msg = 'Your server document root does not contain atim source code.';
		}
	} else {
		$error_msg = 'Your server document root is not a directory. See config.php.';
	}
	if($error_msg) die("<FONT COLOR='red'>$error_msg</FONT><br>");
	?>
	<select id="dbSelect">
		<option></option>
		<?php 
		sort($atim_dir_names);
		foreach($atim_dir_names as $new_atim_dir_name){
			$selected = ($new_atim_dir_name == $selected_atim_dir_name ? ' selected="selected"' : "");
			echo("<option $selected>$new_atim_dir_name</option>");
		}
		if(!$selected_atim_dir_name) die();
		?>
	</select>
	<?php if(!in_array($selected_atim_dir_name, $atim_dir_names)) die("<br><FONT COLOR='red'>Your selected directory '$selected_atim_dir_name' is not an ATiM directory.</FONT><br>"); ?>
</div>
<table>
	<thead>
		<tr>
			<th>Plugin</th>
			<th>File Type</th>
			<th>File Name</th>
			<th>xxx</th>
		</tr>
	</thead>
	<tbody>
	<?php
		$last_plugin_and_file_type = '';
		$atim_plugins_path = $config['server_document_root'].'/'.$selected_atim_dir_name.'/app/plugin';
		$atim_plugins_handle = opendir($atim_plugins_path);
		while (false !== ($new_plugin_name = readdir($atim_plugins_handle))) {
			foreach(array('Controller', 'Model', 'View') as $plugin_object) {
				$plugin_objects = array($plugin_object);
				if($plugin_object == 'View') {
					$plugin_objects = array();
					if(is_dir("$atim_plugins_path/$new_plugin_name/View")) {
						$tmp_handle = opendir("$atim_plugins_path/$new_plugin_name/View");
						while (false !== ($new_view_name = readdir($tmp_handle))) {
							$plugin_objects[] = "$plugin_object/$new_view_name";
						}
					}
				}
				foreach($plugin_objects as $plugin_object) {
					foreach(array('Custom','Hook') as $custom_hook) {
						if(is_dir("$atim_plugins_path/$new_plugin_name/$plugin_object/$custom_hook")) {
							$tmp_handle = opendir("$atim_plugins_path/$new_plugin_name/$plugin_object/$custom_hook");
							while (false !== ($file_name = readdir($tmp_handle))) {
								if(preg_match('/\.((ctp)|(php))$/', $file_name)) {
								//	echo "$file_name<br>";
									$plugin_object_for_display = $plugin_object;
									if(preg_match('/View\/(.*)$/', $plugin_object, $matches)) $plugin_object_for_display = $matches[1].' View';
									$details = array();
									if($custom_hook == 'Custom') {
										$file_handle = fopen("$atim_plugins_path/$new_plugin_name/$plugin_object/$custom_hook/$file_name", "r");
										if ($file_handle) {
											while (($line = fgets($file_handle)) !== false) {
												if(preg_match('/function\ (.*)\(/iU', $line, $matches)) {
													$details[] = 'Function '.$matches[1].'()';
												} else if(preg_match('/static(.*)table_query/', $line)) {
													$details[] = ' Redefined $table_query';
												}
											}
											fclose($file_handle);
										}
									}	
									$details = implode('<br>', $details);							
									if("$new_plugin_name, $plugin_object_for_display.' - '.$custom_hook" != $last_plugin_and_file_type) {
										printf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', $new_plugin_name, $plugin_object_for_display.' - '.$custom_hook, $file_name, $details);
									} else {
										printf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', '', '', $file_name, $details);
									}
									$last_plugin_and_file_type = "$new_plugin_name, $plugin_object_for_display.' - '.$custom_hook";
								}
							}
						}
					}
				}
			}
		}
	?>
	</tbody>
</table>
</body>
</html>