<?php 
require_once('config.php');
$matches = array();
$is_old_ie = null;
preg_match('/MSIE\ [0-9]\.[0-9]/', $_SERVER['HTTP_USER_AGENT'], $matches);
if(!empty($matches)){
	$is_ie = true;
	$version = substr($matches[0], 5) + 0;
	$is_old_ie = $version < 8; 
}else{
	$is_old_ie = false;
}
?>


<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<link rel="shortcut icon" href="favicon.ico"/>
		<link rel="stylesheet" type="text/css" href="style.css" />		
		<title>Portail ATiM</title>
	</head>
	<body>
		<div class='outer'>
			<div class='inner'>
				<?php 
				$template = '<img src="%s" alt="%s"/>';
				foreach($main_logos as $main_logo){
					printf($template, $main_logo['src'], $main_logo['alt']);
				} 
				?>
				<h1>Portail ATiM</h1>
				<?php 
				if($is_old_ie){
					$template = '<a href="%s">%s</a>';
					$line_parts = array();
					foreach($install_links as $name => $url){
						$line_parts[] = sprintf($template, $url, $name);
					}
					echo "<p>",implode(" | ", $line_parts),"</p>";
				}else{
				?>
				<ul class="installLinks">
					<?php 
					$template = '<li><a href="%s">%s</a></li>';
					foreach($install_links as $name => $url){
						printf($template, $url, $name);
					}
					?>
				</ul>
				<?php 
				}
				?>
				<img src="ctrnet_logo.png" alt="CTRNet logo" class="ctrnetLogo"/>
				<div class="usefullLinks">
					<h2>Liens utiles</h2>
					<ul>
					<?php 
					$template = '<li><a href="%s">%s</a></li>';
					foreach($usefull_links as $name => $url){
						if(is_array($url)){
							echo "<li><h3>",$name,"</h3><ul>";
							foreach($url as $inner_name => $inner_url){
								printf($template, $inner_url, $inner_name);
							}
							echo "</ul></li>";
						}else{
							printf($template, $url, $name);
						}
					}	
					?>
					</ul>
				</div>
				<?php 
				if($is_old_ie){
					?>
					<div class="oldIe">
					ATTENTION: Votre version d'Internet Explorer est inférieure à 8 et risque de ne pas bien fonctionner avec ATiM.
					</div>
					<?php 
				}
				?>
			</div>
		</div>
	</body>
</html>