<?php
require_once("../common/myFunctions.php");
$json = json_decode(stripslashes($_GET['json'])) or die("decode failed");
if($json->type == 'autoBuildData'){
	$query = "SELECT count(*) AS c, sfi.field AS field FROM structure_formats AS sfo "
		."INNER JOIN structures AS s ON s.id=sfo.structure_id "
		."INNER JOIN structure_fields AS sfi ON sfo.structure_field_id=sfi.id "
		."WHERE s.alias='".$json->val."' GROUP BY sfo.structure_field_id HAVING c > 1";
	$result = $db->query($query) or die("<tr><td>Query failed ".$db->error."</td></tr>");
	if($result->num_rows > 0){
		echo ("Duplicate fields detected on: ");
		$fields = "";
		while($row = $result->fetch_assoc()){
			$fields .= $row['field'].", ";
		}
		echo(substr($fields, 0, strlen($fields) - 2)."\n");
	}
	$query = "SELECT sfi.id AS sfi_id, sfi.plugin AS sfi_plugin, sfi.model AS sfi_model, sfi.tablename AS sfi_tablename, "
			."sfi.field AS sfi_field, sfi.language_label AS sfi_language_label, sfi.language_tag AS sfi_language_tag, "
			."sfi.type AS sfi_type, sfi.setting AS sfi_setting, sfi.default AS sfi_default, "
			."sfi.structure_value_domain AS sfi_structure_value_domain, sfi.language_help AS sfi_language_help, "
			."sfo.display_column AS sfo_display_column, "
			."sfo.id AS sfo_id, sfo.display_order AS sfo_display_order, sfo.language_heading AS sfo_language_heading, " 
			."sfo.flag_override_label AS sfo_flag_override_label, sfo.language_label AS sfo_language_label, "
			."sfo.flag_override_tag AS sfo_flag_override_tag, sfo.language_tag AS sfo_language_tag, "
			."sfo.flag_override_help AS sfo_override_help, sfo.language_help AS sfo_language_help, "
			."sfo.flag_override_type AS sfo_flag_override_type, sfo.type AS sfo_type, "
			."sfo.flag_override_setting AS sfo_flag_override_setting, sfo.setting AS sfo_setting, " 
			."sfo.flag_override_default AS sfo_flag_override_default, sfo.default AS sfo_default, "
			."sfo.flag_add AS sfo_flag_add, sfo.flag_add_readonly AS sfo_flag_add_readonly, "
			."sfo.flag_edit AS sfo_flag_edit, sfo.flag_edit_readonly AS sfo_flag_edit_readonly, "
			."sfo.flag_search AS sfo_flag_search, sfo.flag_search_readonly AS sfo_flag_search_readonly, "
			."sfo.flag_addgrid AS sfo_flag_addgrid, sfo.flag_addgrid_readonly AS sfo_flag_addgrid_readonly, "
			."sfo.flag_editgrid AS sfo_flag_editgrid, sfo.flag_editgrid_readonly AS sfo_flag_editgrid_readonly, "
			."sfo.flag_batchedit AS sfo_flag_batchedit, sfo.flag_batchedit_readonly AS sfo_flag_batchedit_readonly, "
			."sfo.flag_index AS sfo_flag_index, sfo.flag_detail AS sfo_flag_detail, sfo.flag_summary AS sfo_flag_summary, "
			."sfo.flag_float AS sfo_flag_float, "
			."svd.domain_name AS svd_domain_name, sfi.flag_confidential AS sfi_flag_confidential , sfo.margin AS margin "
		."FROM structures AS s "
		."INNER JOIN structure_formats AS sfo ON s.id=sfo.structure_id "
		."INNER JOIN structure_fields AS sfi ON sfo.structure_field_id=sfi.id "
		."LEFT JOIN structure_value_domains AS svd ON sfi.structure_value_domain=svd.id "
		."WHERE s.alias='".$json->val."'";
	$result = $db->query($query) or die("<tr><td>Query failed ".$db->error."</td></tr>");
	$checbox = '<input type="checkbox"%s/>';
	while($row = $result->fetch_assoc()){
		?>
		<tr>
			<td><?php echo($row['sfi_field']); ?></td>
			<td><?php echo($row['sfi_plugin']); ?></td>
			<td><?php echo($row['sfi_model']); ?></td>
			<td><?php echo($row['sfi_tablename']); ?></td>
			<td><?php echo($row['sfo_flag_override_label'] ? $row['sfo_language_label'] : $row['sfi_language_label']); ?></td>
			<td><?php echo($row['sfo_flag_override_tag'] ? $row['sfo_language_tag'] : $row['sfi_language_tag']); ?></td>
			<td><?php echo($row['sfo_flag_override_type'] ? $row['sfo_type'] : $row['sfi_type']); ?></td>
			<td><?php echo($row['sfo_flag_override_setting'] ? $row['sfo_setting'] : $row['sfi_setting']); ?></td>
			<td><?php echo($row['sfo_flag_override_default'] ? $row['sfo_default'] : $row['sfi_default']); ?></td>
			<td><?php echo($row['sfi_structure_value_domain'] == NULL ? "NULL" : $row['svd_domain_name'] ); ?></td>
			<td><?php echo($row['sfo_override_help'] ? $row['sfo_language_help'] : $row['sfi_language_help']); ?></td>
			<td><?php echo($row['sfo_display_column']); ?></td>
			<td><?php echo($row['sfo_display_order']); ?></td>
			<td><?php echo($row['sfo_language_heading']); ?></td>
			<td><?php printf($checbox, $row['sfi_flag_confidential'] ? ' checked="checked"' : ""); ?></td>
			<td><?php printf($checbox, $row['sfo_flag_add'] ? ' checked="checked"' : ""); ?></td>
			<td><?php printf($checbox, $row['sfo_flag_add_readonly'] ? ' checked="checked"' : ""); ?></td>
			<td><?php printf($checbox, $row['sfo_flag_edit'] ? ' checked="checked"' : ""); ?></td>
			<td><?php printf($checbox, $row['sfo_flag_edit_readonly'] ? ' checked="checked"' : ""); ?></td>
			<td><?php printf($checbox, $row['sfo_flag_search'] ? ' checked="checked"' : ""); ?></td>
			<td><?php printf($checbox, $row['sfo_flag_search_readonly'] ? ' checked="checked"' : ""); ?></td>
			<td><?php printf($checbox, $row['sfo_flag_addgrid'] ? ' checked="checked"' : ""); ?></td>
			<td><?php printf($checbox, $row['sfo_flag_addgrid_readonly'] ? ' checked="checked"' : ""); ?></td>
			<td><?php printf($checbox, $row['sfo_flag_editgrid'] ? ' checked="checked"' : ""); ?></td>
			<td><?php printf($checbox, $row['sfo_flag_editgrid_readonly'] ? ' checked="checked"' : ""); ?></td>
			<td><?php printf($checbox, $row['sfo_flag_batchedit'] ? ' checked="checked"' : ""); ?></td>
			<td><?php printf($checbox, $row['sfo_flag_batchedit_readonly'] ? ' checked="checked"' : ""); ?></td>
			<td><?php printf($checbox, $row['sfo_flag_index'] ? ' checked="checked"' : ""); ?></td>
			<td><?php printf($checbox, $row['sfo_flag_detail'] ? ' checked="checked"' : ""); ?></td>
			<td><?php printf($checbox, $row['sfo_flag_summary'] ? ' checked="checked"' : ""); ?></td>
			<td><?php printf($checbox, $row['sfo_flag_float'] ? ' checked="checked"' : ""); ?></td>
			<td><?php echo($row['margin']); ?></td>
			<td><?php echo($row['sfi_id']); ?></td>
			<td><?php echo($row['sfo_id']); ?></td>
		</tr>
		<?php 
	}
}
?>
