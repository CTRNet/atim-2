<?php
require_once("myFunctions.php");
?>
<html>
<head>
<script type="text/javascript" src="scripts/jquery-1.4.1.min.js"></script>
<script type="text/javascript" src="scripts/jquery.jsonSuggestME.js"></script>
<script type="text/javascript" src="scripts/jquery.color.js"></script>
<script type="text/javascript" src="scripts/jquery-ui-1.7.2.custom.min.js"></script>
<script type="text/javascript" src="scripts/jquery.tablesorter.min.js"></script>
<script type="text/javascript" src="scripts/script.js"></script>
<link rel="stylesheet" type="text/css" media="screen" href="css/style.css" />
<link rel="stylesheet" type="text/css" media="screen" href="css/jsonSuggestME.css" />
<link rel="stylesheet" type="text/css" media="screen" href="css/cupertino/jquery-ui-1.7.2.custom.css" />
<script>
</script>

</head>
<body>
	<textarea style="width: 100%; height: 20%; margin: 0px;"></textarea>
	<div class="ui-tabs ui-widget ui-widget-content ui-corner-all" style="width: 100%; position: relative; left: -3px; margin: 0px; padding: 0px;">
		Current database: 
		<?php 
		$query = "SHOW databases";
		$result = $mysqli->query($query) or die("show databases failed");
		?>
		<select id="dbSelect">
			<?php 
			while($row = $result->fetch_row()){
				if($row[0] != "information_schema" && $row[0] != "mysql"){
					$selected = ($row[0] == $db_schema ? ' selected="selected"' : "");
					echo("<option".$selected.">".$row[0]."</option>");
				}
			}
			?>
		</select>
		<a href='#' id='clearSql' class='ui-state-default ui-corner-all button_link '><span class='button_icon ui-icon ui-icon-circle-close'></span>Clear</a>
		<!-- 
		<a href='#' id='runSql' class='ui-state-default ui-corner-all button_link '><span class='button_icon ui-icon ui-icon-play'></span>Run sql</a>
		 -->
		<div id="warningBox" class="ui-widget" style="display: none;">
			<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0pt 0.7em;"> 
				<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.3em;"></span>
				<span id="warningMsg"></span></p>

			</div>
		</div>
		<br>
		<div id="errorBox" class="ui-widget" style="display: none;">
			<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;"> 
				<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span> 
				<span id="errMsg"></span></p>
			</div>
		</div>
	
	</div>
	<div id="databaseExplorer" style="width: 100%; margin-right: 10px; position: relative; left: -3px; margin: 0px; padding: 0px;">
		<ul>
			<li><a href="#tab1">Structures</a></li>
			<li><a href="#tab2">Fields</a></li>
			<li><a href="#tab4">Value domain</a></li>
			<li><a href="#tab5">Tables</a></li>
		</ul>
		<div id="tab1" style="white-space: nowrap;">
			<div>
				<input id="structure_search" type="search"/>[+]
				<br/>
				<?php 
				//structures
				$query = "SELECT id, alias FROM structures ORDER BY alias";
				$result = $mysqli->query($query) or die("STI");
				while($row = $result->fetch_assoc()){
					echo("<a href='#".$row['id']."' class='structLink'>".$row['alias']."</a> - <a href='#".$row['id']."' class='structLinkAdd'>[+]</a><br/>");
				}
				?>
			</div>
			<div id="structureResult">
			
			</div>
		</div>
		<div id="tab2">
			<div>
			<?php 
			//structures
			$query = "SELECT model FROM structure_fields GROUP BY model";
			$result = $mysqli->query($query) or die("STI");
			while($row = $result->fetch_row()){
				echo("<a href='#".$row[0]."' class='fieldLink'>".$row[0]."</a><br/>");
			}
			?>
			</div>
			<div id="fieldResult">
			
			</div>
		</div>
		<div id="tab4">
			<div>
			<input id="value_domains_search" type="search"/>[+]
			<br/>
			<?php 
			//structures
			$query = "SELECT domain_name FROM structure_value_domains ORDER BY domain_name";
			$result = $mysqli->query($query) or die("STI");
			while($row = $result->fetch_row()){
				echo("<a href='#".$row[0]."' class='vDomainLink'>".$row[0]."</a> - <a href='#".$row[0]."' class='vDomainLinkAdd'>[+]</a><br/>");
			}
			?>
			</div>
			<div id="valueDomainResult">
			
			</div>
		</div>
		<div id="tab5">
			<div>
			<?php 
			//structures
			$query = "SHOW tables";
			$result = $mysqli->query($query) or die("STI");
			while($row = $result->fetch_row()){
				if(strpos($row[0], "_revs") != strlen($row[0]) - 5){
					echo("<a href='#".$row[0]."' class='tableLink'>".$row[0]."</a> - <a href='#".$row[0]."' class='tableLinkAdd'>[+]</a><br/>");
				}
			}
			?>
			</div>
			<div id="tableResult">
			
			</div>
		</div>
	</div>
	<div id="queryBuilder" style="width: 100%; margin-right: 10px; position: relative; left: -3px; margin: 0px; padding: 0px;">
		<ul>
			<li><a href="#piton1">Structures</a></li>
			<li><a href="#piton2">Fields</a></li>
			<li><a href="#piton3">Sutrcture format</a></li>
			<li><a href="#piton4">Value domain</a></li>
			<li><a href="#piton5">Auto build</a></li>
		</ul>
		<div id="piton1" class="structuresDiv create">
			<h3>Create Structure</h3>
			<div style="overflow: auto">
			<table class="insert">
				<thead class="structures">
					<tr>
						<th class="autoincrement">old_id</th>
						<th class="notEmpty">alias</th>
						<th>language_title</th>
						<th>language_help</th>
						<th class="checkbox">flag_add_columns</th>
						<th class="checkbox">flag_edit_columns</th>
						<th class="checkbox">flag_search_columns</th>
						<th class="checkbox">flag_detail_columns</th>
					</tr>
				</thead>
				<tbody>
				</tbody>
				<tfoot>
				</tfoot>
			</table>
			</div>
		</div>
		<div id="piton2" class="structure_fieldsDiv create" style="border-style: solid; white-space: normal; overflow: scroll;">
			<h3>Create field</h3>
			<table class="insert">
				<thead class="structure_fields">
					<tr>
					</th>
						<th>public_identifier</th>
						<th class="autoincrement">old_id</th>
						<th class="notEmpty">plugin</th>
						<th class="notEmpty">model</th>
						<th>tablename</th>
						<th class="notEmpty">field</th>
						<th>language_label</th>
						<th>language_tag</th>
						<th class="notEmpty">type</th>
						<th>setting</th>
						<th>default</th>
						<th class="notEmpty">structure_value_domain</th>
						<th>language_help</th>
						<th>validation_control</th>
						<th>value_domain_control</th>
						<th>field_control</th>
					</tr>
				</thead>
				<tbody>
				</tbody>
				<tfoot>
				</tfoot>
			</table>
		</div>
		<div id="piton3" class="structure_formatsDiv create" style="border-style: solid; white-space: normal; overflow: scroll;">
			<h3>Create structure format</h3>
			<table class="insert">
				<thead class="structure_formats">
					<tr>
						<th class="readonly">old_id</th>
						<th class="readonly">structure_id</th>
						<th class="gen">structure_old_id</th>
						<th class="readonly">structure_field_id</th>
						<th class="gen autoincrement">structure_field_old_id</th>
						<th class="autoincrement">display_column</th>
						<th class="autoincrement">display_order</th>
						<th>language_heading</th>
						<th class="checkbox">flag_override_label</th>
						<th>language_label</th>
						<th class="checkbox">flag_override_tag</th>
						<th>language_tag</th>
						<th class="checkbox">flag_override_help</th>
						<th>language_help</th>
						<th class="checkbox">flag_override_type</th>
						<th>type</th>
						<th class="checkbox">flag_override_setting</th>
						<th>setting</th>
						<th class="checkbox">flag_override_default</th>
						<th>default</th>
						<th class="checkbox">flag_add</th>
						<th class="checkbox">flag_add_readonly</th>
						<th class="checkbox">flag_edit</th>
						<th class="checkbox">flag_edit_readonly</th>
						<th class="checkbox">flag_search</th>
						<th class="checkbox">flag_search_readonly</th>
						<th class="checkbox">flag_datagrid</th>
						<th class="checkbox">flag_datagrid_readonly</th>
						<th class="checkbox">flag_index</th>
						<th class="checkbox">flag_detail</th>
					</tr>
				</thead>
				<tbody>
				</tbody>
				<tfoot>
				</tfoot>
			</table>
		</div>
		<div id="piton4" class="structure_value_domainsDiv create" style="border-style: solid; white-space: normal; overflow: scroll;">
			<h3>Create structure value domains</h3>
			<table class="insert">
				<thead class="structure_value_domains">
					<tr>
						<th class="notEmpty">domain_name</th>
						<th>override</th>
						<th>category</th>
						<th class="notEmpty">source</th>
					</tr>
				</thead>
				<tbody>
				</tbody>
				<tfoot>
				</tfoot>
			</table>
			<h3>Create structure value domain permissible values</h3>
			<table class="insert">
				<thead class="custom struct_val_domain">
					<tr>
						<th class="notEmpty">domain_name</th>
						<th>value</th>
						<th>language_alias</th>
						<th>display_order</th>
						<th>flag_active</th>
					</tr>
				</thead>
				<tbody>
				</tbody>
				<tfoot>
				</tfoot>
			</table>
		</div>
		<div id="piton5" class="structure_value_domainsDiv create" style="border-style: solid; white-space: normal; overflow: scroll;">
			<table class="insert ui-widget ui-widget-content">
				<thead class="custom autoBuild1">
					<tr class='ui-widget-header'>
						<th>alias</th>
						<th>language_title</th>
					</tr>
				</thead>
				<tbody>
				</tbody>
				<tfoot></tfoot>
			</table>
			
			<table id="autoBuild2" class="insert ui-widget ui-widget-content">
				<thead class="custom autoBuild2">
					<tr class='ui-widget-header'>
						<th>plugin</th>
						<th class="notEmpty">model</th>
						<th class="clear">tablename</th>
						<th class="notEmpty clear">field</th>
						<th class="clear">language_label</th>
						<th class="clear">language_tag</th>
						<th class="notEmpty">type</th>
						<th>setting</th>
						<th>default</th>
						<th class="notEmpty">structure_value_domain</th>
						<th class="clear">language_help</th>
						<th>validation_control</th>
						<th>value_domain_controld</th>
						<th>field_control</th>
						<th class="notEmpty autoincrement">display_column</th>
						<th class="notEmpty autoincrement autoBuildIncrement">display_order</th>
						<th class="clear">language_heading</th>
						<th class="checkbox">flag_add</th>
						<th class="checkbox">flag_add_readonly</th>
						<th class="checkbox">flag_edit</th>
						<th class="checkbox">flag_edit_readonly</th>
						<th class="checkbox">flag_search</th>
						<th class="checkbox">flag_search_readonly</th>
						<th class="checkbox">flag_datagrid</th>
						<th class="checkbox">flag_datagrid_readonly</th>
						<th class="checkbox">flag_index</th>
						<th class="checkbox">flag_detail</th>
						<th class="readonly clear">sfi_id</th>
						<th class="readonly clear">sfo_id</th>
					</tr>
				</thead>
				<tbody>
				<!-- 
				<tr onclick="editLine(this);"><td>pluginne</td><td>32</td><td>ad_gel_matrices</td><td>cell_count</td><td>cell count</td><td></td><td>input</td><td></td><td></td><td>asd</td><td></td><td></td><td></td><td></td><td>0</td><td>0</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td>0</td><td>1</td></tr>
				<tr onclick="editLine(this);"><td>tre</td><td>234</td><td>ad_gel_matrices</td><td>cell_count</td><td>cell count</td><td></td><td>input</td><td></td><td></td><td>asd</td><td></td><td></td><td></td><td></td><td>0</td><td>0</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td>0</td><td>1</td></tr>
				<tr onclick="editLine(this);"><td>asd</td><td>001</td><td>ad_gel_matrices</td><td>cell_count</td><td>cell count</td><td></td><td>input</td><td></td><td></td><td>asd</td><td></td><td></td><td></td><td></td><td>0</td><td>0</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td>0</td><td>1</td></tr>
				 -->
				</tbody>
				<tfoot>
				</tfoot>
			</table>
			<a href="#" id="generateSQL" class="ui-state-default ui-corner-all button_link custom" name="custom autoBuild1"><span class="button_icon ui-icon ui-icon-play"></span><span>Generate SQL</span></a>
		</div>
	</div>
	
	<div id="confirmDialog">
		<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>
		WARNING: You currently have items in the auto build form. If you proceed, they will be removed.</p>
		<p>Do you wish to proceed?</p>	
	</div>
	<div id="noDataDialog">
		<p><span class="ui-icon ui-icon-info" style="float:left; margin:0 7px 20px 0;"></span>
		There is no data for the provided structure alias.</p>
	</div>
	<div id="saveDeleteDialog">
		<p><span class="ui-icon ui-icon-help" style="float:left; margin:0 7px 20px 0;"></span>
		There is already a line beign edited. What do you wish to do?</p>
	</div>
	<div id="duplicateFieldsDialog">
		<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>
		WARNING: The current structure has duplicate fields. The form builder does not support that.</p>
		<p id="duplicateFieldsMsg"></p>	
	</div>
</body>
</html>