<?php
require_once("myFunctions.php");
$json = json_decode(stripslashes($_GET['json'])) or die("decode failed");
if($json->type == 'autoBuildData'){
	$query = "SELECT sfi.plugin AS sfi_plugin, sfi.model AS sfi_model, sfi.tablename AS sfi_tablename, "
			."sfi.field AS sfi_field, sfi.language_label AS sfi_language_label, sfi.language_tag AS sfi_language_tag, "
			."sfi.type AS sfi_type, sfi.setting AS sfi_setting, sfi.default AS sfi_default, "
			."sfi.structure_value_domain AS sfi_structure_value_domain, sfi.language_help AS sfi_language_help, "
			."sfi.validation_control AS sfi_validation_control, sfi.value_domain_control AS sfi_value_domain_control, "
			."sfi.field_control AS sfi_field_control, sfo.display_column AS sfo_display_column, "
			."sfo.display_order AS sfo_display_order, sfo.language_heading AS sfo_language_heading, " 
			."sfo.flag_override_label AS sfo_flag_override_label, sfo.language_label AS sfo_language_label, "
			."sfo.flag_override_tag AS sfo_flag_override_tag, sfo.language_tag AS sfo_language_tag, "
			."sfo.flag_override_help AS sfo_override_help, sfo.language_help AS sfo_language_help, "
			."sfo.flag_override_type AS sfo_flag_override_type, sfo.type AS sfo_type, "
			."sfo.flag_override_setting AS sfo_flag_override_setting, sfo.setting AS sfo_setting, " 
			."sfo.flag_override_default AS sfo_flag_override_default, sfo.default AS sfo_default, "
			."sfo.flag_add AS sfo_flag_add, sfo.flag_add_readonly AS sfo_flag_add_readonly, "
			."sfo.flag_edit AS sfo_flag_edit, sfo.flag_edit_readonly AS sfo_flag_edit_readonly, "
			."sfo.flag_search AS sfo_flag_search, sfo.flag_search_readonly AS sfo_flag_search_readonly, "
			."sfo.flag_datagrid AS sfo_flag_datagrid, sfo.flag_datagrid_readonly AS sfo_flag_datagrid_readonly, "
			."sfo.flag_index AS sfo_flag_index, sfo.flag_detail AS sfo_flag_detail FROM structures AS s "
		."INNER JOIN structure_formats AS sfo ON s.id=sfo.structure_id "
		."INNER JOIN structure_fields AS sfi ON sfo.structure_field_id=sfi.id "
		."WHERE s.alias='".$json->val."'";//struct 208, 936-937
	$result = $mysqli->query($query) or die("<tr><td>Query failed ".$mysqli->error."</td></tr>");
	while($row = $result->fetch_assoc()){
		?>
		<tr>
			<td><?php echo($row['sfi_plugin']); ?></td>
			<td><?php echo($row['sfi_model']); ?></td>
			<td><?php echo($row['sfi_tablename']); ?></td>
			<td><?php echo($row['sfi_field']); ?></td>
			<td><?php echo($row['sfo_flag_override_label'] ? $row['sfo_language_label'] : $row['sfi_language_label']); ?></td>
			<td><?php echo($row['sfo_flag_override_tag'] ? $row['sfo_language_tag'] : $row['sfi_language_tag']); ?></td>
			<td><?php echo($row['sfo_flag_override_type'] ? $row['sfo_type'] : $row['sfi_type']); ?></td>
			<td><?php echo($row['sfo_flag_override_setting'] ? $row['sfo_setting'] : $row['sfi_setting']); ?></td>
			<td><?php echo($row['sfo_flag_override_default'] ? $row['sfo_default'] : $row['sfi_default']); ?></td>
			<td><?php echo($row['sfi_structure_value_domain'] == NULL ? "NULL" : $row['sfi_structure_value_domain'] ); ?></td>
			<td><?php echo($row['sfo_override_help'] ? $row['sfo_language_help'] : $row['sfi_language_help']); ?></td>
			<td><?php echo($row['sfi_validation_control']); ?></td>
			<td><?php echo($row['sfi_valude_domain_control']); ?></td>
			<td><?php echo($row['sfi_field_control']); ?></td>
			<td><?php echo($row['sfo_display_column']); ?></td>
			<td><?php echo($row['sfo_display_order']); ?></td>
			<td><?php echo($row['sfo_language_heading']); ?></td>
			<td><?php echo($row['sfo_flag_add']); ?></td>
			<td><?php echo($row['sfo_flag_add_readonly']); ?></td>
			<td><?php echo($row['sfo_flag_edit']); ?></td>
			<td><?php echo($row['sfo_flag_edit_readonly']); ?></td>
			<td><?php echo($row['sfo_flag_search']); ?></td>
			<td><?php echo($row['sfo_flag_search_readonly']); ?></td>
			<td><?php echo($row['sfo_flag_datagrid']); ?></td>
			<td><?php echo($row['sfo_flag_datagrid_readonly']); ?></td>
			<td><?php echo($row['sfo_flag_index']); ?></td>
			<td><?php echo($row['sfo_flag_detail']); ?></td>
		</tr>
		<?php 
	}
}
?>
