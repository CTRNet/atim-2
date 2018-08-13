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
		<link rel="shortcut icon" href="./images/favicon.ico"/>
		<link rel="stylesheet" type="text/css" href="style.css" />		
		<link rel="stylesheet" type="text/css" href="lib/bootstrap.min.css" />		
		<link rel="stylesheet" type="text/css" href="lib/bootstrap-theme.min.css"/>		
		<script src="lib/jquery.min.js"></script>
		<script src="lib/bootstrap.min.js"></script>
		<title>Portail ATiM</title>
	</head>
	<body>
		<div class='outer'>
			<div class='inner'>
				<?php 
				$template = '<img src="./images/%s" alt="%s"  class="Logo"/>';
				foreach($main_logos as $main_logo){
					if($main_logo['url']) printf('<a class = "logo-image" href="%s">', $main_logo['url']);
					printf($template, $main_logo['src'], $main_logo['alt']);
					if($main_logo['url']) printf('</a>');
				} 
				if($is_old_ie){
					$template = '<a href="%s">%s</a>';
					$line_parts = array();
					foreach($install_links as $name => $url){
						$line_parts[] = sprintf($template, $url, $name);
					}
					echo "<p>",implode(" | ", $line_parts),"</p>";
				}else{
				?>
<nav class="navbar navbar-default">
<div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="<?=$_SERVER['REQUEST_URI'];?>">ATiM Portal / Portail ATiM</a>
    </div>
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
<?php
foreach($install_links as $name => $url){
?>
        <li class="dropdown">
          <a href="<?=$url;?>" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?=$name;?></span></a>
          <ul class="dropdown-menu">
            <li><a target="_blank" href="<?=$url;?>">Prod</a></li>
            <li role="separator" class="divider"></li>
            <li class = "atim-test"><a target="_blank" href="<?=$url."Test";?>">Test</a></li>
          </ul>
        </li>
<?php
}
?>
      </ul>
    </div>
  </div>
</nav>				
				<?php 
				}
				?>
				<div class="usefullLinks">
					<h2>Information and Links / Information et liens</h2>
					<ul>
					<?php 
					$template = '<li><a href="%s">%s</a></li>';
					foreach($usefull_links_and_data as $section_title => $url_and_data){
						if(is_array($url_and_data)){
							echo "<li><h3>",$section_title,"</h3><ul>";
							foreach($url_and_data as $sub_section_title => $inner_url_or_data){
								if(is_array($inner_url_or_data)) {
									echo "<li><h4>",$sub_section_title,"</h4><ul>";
									foreach($inner_url_or_data as $tile => $data) {
										printf("<li><b>%s: </b>%s</li>", $tile, $data);
									}
									echo "</ul></li>";

								} else {
									printf($template, $inner_url_or_data, $sub_section_title);
								}
							}
							echo "</ul></li>";
						}else{
							printf($template, $url_and_data, $section_title);
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
		<script src="script.js"></script>
	</body>
</html>
