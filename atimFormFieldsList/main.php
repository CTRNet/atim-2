<?php

// === DATABASE =============================================================================================================================
$db_ip = "localhost";
$db_port = "";
$db_user = "root";
$db_pwd = "";
$db_schema = "";
$db_charset = "utf8";
$db_name = "procurecentral";

global $db_connection;
$db_connection = @mysqli_connect($db_ip . (! empty($db_port) ? ":" . $db_port : ''), $db_user, $db_pwd) or die("ERR_DATABASE_CONNECTION: Could not connect to MySQL");
if (! mysqli_set_charset($db_connection, $db_charset)) {
    die("ERR_DATABASE_CONNECTION: Invalid charset");
}

if (! @mysqli_select_db($db_connection, $db_name))
    die("ERR_DATABASE_CONNECTION: Could not use $db_name");
    
    // === DIRECTORY & structures list ===========================================================================================================

$path_dir = "C:\_NicolasLuc\Server\www\_atim_tools\atimFormFieldsList/";
$file_name = "ATiM_fields.csv";

$structure_properties = array(
    array('participants','',false),
    array('miscidentifiers','',false),
    array('consent_controls','',true),
    array('event_controls','', true),
    array('treatment_controls','',true),
    array('collections','',false),
    array('sample_controls','',true),
    array('aliquot_controls','',true),
    array('aliquotinternaluses','',false),
    
    array('qualityctrls','',false),
    array('specimen_review_controls','',true),
    array('aliquot_review_controls','',true),
    array('storage_controls','',true),
    array('tma_slides','',false),
    array('drugs','',false),
    array('studysummaries','',false),
    array('studyfundings','',false),
    array('studyinvestigators','',false),
    array('orders','',false),
    array('orderlines','',false),
    array('shipments','',false)
);

// === VARIABLES ================================================================================================================================

global $values_of_lists;
$values_of_lists = array();

$csv_separator = ';';

// === PROCESS ================================================================================================================================

echo "Launch Process<br>";
echo "<br>-------------------------------------------------------------------------------------------<br><br>";

if (! is_dir($path_dir))
    die("ERR_DIRECTORY: Could not open $path_dir");
$file_output = fopen($path_dir . $file_name, "w+t");

// display info
echo 'Form / Header' . $csv_separator . 'Field Label' . $csv_separator . 'Field Type' . $csv_separator . 'Options (Field Select Only)' . $csv_separator . 'Help' . $csv_separator . 'System data' . $csv_separator . '<br>';
fwrite($file_output, "\"Form / Header\"$csv_separator\"Field Label\"$csv_separator\"Field Type\"$csv_separator\"Options (Field Select Only)\"$csv_separator\"Help\"$csv_separator\"System data\"$csv_separator\n");
echo '<br>';
fwrite($file_output, "\n");

$query = "SELECT form_view.*, 
		IFNULL(i18n_language_heading.en, form_view.language_heading) AS language_heading, 
		IFNULL(i18n_language_label.en, form_view.language_label) AS language_label,
		IFNULL(i18n_language_tag.en, form_view.language_tag) AS language_tag, 
		IFNULL(i18n_language_help.en, form_view.language_help) AS language_help
		FROM (
			select `str`.`alias` AS `structure_alias`,
			`sfo`.`id` AS `structure_format_id`,
			`sfi`.`id` AS `structure_field_id`,
			`sfo`.`structure_id` AS `structure_id`,
			`sfi`.`plugin` AS `plugin`,
			`sfi`.`model` AS `model`,
			`sfi`.`tablename` AS `tablename`,
			`sfi`.`field` AS `field`,
			`sfi`.`structure_value_domain` AS `structure_value_domain`,
			`svd`.`domain_name` AS `structure_value_domain_name`,
			`sfi`.`flag_confidential` AS `flag_confidential`,
			if((`sfo`.`flag_override_label` = '1'),	`sfo`.`language_label`,	`sfi`.`language_label`) AS `language_label`,
			if((`sfo`.`flag_override_tag` = '1'),`sfo`.`language_tag`,`sfi`.`language_tag`) AS `language_tag`,
			if((`sfo`.`flag_override_help` = '1'),`sfo`.`language_help`,`sfi`.`language_help`) AS `language_help`,
			if((`sfo`.`flag_override_type` = '1'),`sfo`.`type`,`sfi`.`type`) AS `type`,
			if((`sfo`.`flag_override_setting` = '1'),`sfo`.`setting`,`sfi`.`setting`) AS `setting`,
			if((`sfo`.`flag_override_default` = '1'),`sfo`.`default`,`sfi`.`default`) AS `default`,
			`sfo`.`flag_add` AS `flag_add`,
			`sfo`.`flag_add_readonly` AS `flag_add_readonly`,
			`sfo`.`flag_edit` AS `flag_edit`,
			`sfo`.`flag_edit_readonly` AS `flag_edit_readonly`,
			`sfo`.`flag_search` AS `flag_search`,
			`sfo`.`flag_search_readonly` AS `flag_search_readonly`,
			`sfo`.`flag_addgrid` AS `flag_addgrid`,
			`sfo`.`flag_addgrid_readonly` AS `flag_addgrid_readonly`,
			`sfo`.`flag_editgrid` AS `flag_editgrid`,
			`sfo`.`flag_editgrid_readonly` AS `flag_editgrid_readonly`,
			`sfo`.`flag_batchedit` AS `flag_batchedit`,
			`sfo`.`flag_batchedit_readonly` AS `flag_batchedit_readonly`,
			`sfo`.`flag_index` AS `flag_index`,
			`sfo`.`flag_detail` AS `flag_detail`,
			`sfo`.`flag_summary` AS `flag_summary`,
			`sfo`.`flag_float` AS `flag_float`,
			`sfo`.`display_column` AS `display_column`,
			`sfo`.`display_order` AS `display_order`,
			`sfo`.`language_heading` AS `language_heading`,
			`sfo`.`margin` AS `margin` 
			from `structure_formats` `sfo` 
			join `structure_fields` `sfi` on `sfo`.`structure_field_id` = `sfi`.`id`
			join `structures` `str` on `str`.`id` = `sfo`.`structure_id` 
			left join `structure_value_domains` `svd` on `svd`.`id` = `sfi`.`structure_value_domain`
			where `str`.`alias` IN (%%alias%%)
			AND (`sfo`.`flag_add` = 1 OR `sfo`.`flag_edit` = 1 OR `sfo`.`flag_addgrid` = 1 OR `sfo`.`flag_detail` = 1 OR `sfo`.`flag_editgrid` = 1 OR `sfo`.`flag_index` = 1)
			ORDER BY flag_float DESC, display_column asc, display_order asc
		) as form_view
		LEFT JOIN i18n AS i18n_language_label ON i18n_language_label.id = form_view.language_label
		LEFT JOIN i18n AS i18n_language_tag ON i18n_language_tag.id = form_view.language_tag
		LEFT JOIN i18n AS i18n_language_help ON i18n_language_help.id = form_view.language_help
		LEFT JOIN i18n AS i18n_language_heading ON i18n_language_heading.id = form_view.language_heading;";

foreach ($structure_properties as $new_structure_property) {
    list ($structure_property_1, $structure_property_2, $is_control) = $new_structure_property;
    $all_structure_alias = array();
    if (! $is_control) {
        $all_structure_alias[] = array(
            $structure_property_1,
            "'$structure_property_1'"
        );
    } else {
        $control_data_query = "SELECT * FROM $structure_property_1 WHERE flag_active = 1";
        if($structure_property_1 == 'sample_controls') {
            $control_data_query = "SELECT $structure_property_1.* 
                FROM $structure_property_1 INNER JOIN parent_to_derivative_sample_controls ON $structure_property_1.id = parent_to_derivative_sample_controls.derivative_sample_control_id
                WHERE parent_to_derivative_sample_controls.flag_active = 1";
        }
        foreach (getSelectQueryResult($control_data_query) as $new_control_data) {
            $form_alias = str_replace('_controls', 'masters', $structure_property_1) . strlen($new_control_data['detail_form_alias']) ? ',' . $new_control_data['detail_form_alias'] : '';
            $form_alias = str_replace(',', "','", $form_alias);
            $form_title = '?';
            if(!isset($new_control_data['databrowser_label'])) {
                if(isset($new_control_data['sop_group']) && isset($new_control_data['type'])) {
                    $new_control_data['databrowser_label'] = 'sop|'.$new_control_data['sop_group'].'|'.$new_control_data['type'];
                }                    
            }
            if(!isset($new_control_data['databrowser_label'])) { 
                echo "<pre>";
                print_r($new_control_data);
                echo "</pre>";
            }
            if (strlen($new_control_data['databrowser_label'])) {
                $form_title = array();
                foreach (explode('|', $new_control_data['databrowser_label']) as $new_i18n_value) {
                    $new_i18n_value = str_replace("'", "''", $new_i18n_value);
                    $i18n_res = getSelectQueryResult("SELECT id,en FROM i18n WHERE id = '$new_i18n_value'");
                    $form_title[] = ($i18n_res && strlen($i18n_res[0]['en'])) ? $i18n_res[0]['en'] : $new_i18n_value;
                }
                $form_title = implode(' - ', $form_title);
            }
            $all_structure_alias[] = array(
                $form_title,
                "'$form_alias'"
            );
            if ($structure_property_2) {
                $control_2_fk = str_replace('_controls', '_control_id', $structure_property_2);
                if (isset($new_control_data[$control_2_fk])) {
                    $scd_control_data = getSelectQueryResult("SELECT * FROM $structure_property_2 WHERE flag_active = 1 AND id = " . $new_control_data[$control_2_fk]);
                    if ($scd_control_data) {
                        $scd_control_data = $scd_control_data[0];
                        $form_alias = str_replace('_controls', 'masters', $structure_property_2) . strlen($scd_control_data['detail_form_alias']) ? ',' . $scd_control_data['detail_form_alias'] : '';
                        $form_alias = str_replace(',', "','", $form_alias);
                        $form_title = '?';
                        if (strlen($scd_control_data['databrowser_label'])) {
                            $form_title = array();
                            foreach (explode('|', $scd_control_data['databrowser_label']) as $new_i18n_value) {
                                $new_i18n_value = str_replace("'", "''", $new_i18n_value);
                                $i18n_res = getSelectQueryResult("SELECT id,en FROM i18n WHERE id = '$new_i18n_value'");
                                $form_title[] = ($i18n_res && strlen($i18n_res[0]['en'])) ? $i18n_res[0]['en'] : $new_i18n_value;
                            }
                            $form_title = implode(' - ', $form_title);
                        }
                        $all_structure_alias[] = array(
                            $form_title,
                            "'$form_alias'"
                        );
                    }
                }
            }
        }
    }
    
    foreach ($all_structure_alias as $data_title => $structure_alias_definition) {
        list ($structure_alias_title, $structure_alias) = $structure_alias_definition;
        // display info
        echo "<br>Form : $structure_alias_title$csv_separator$csv_separator$csv_separator$csv_separator$csv_separator$csv_separator<br>";
        fwrite($file_output, "\n\"Form : $structure_alias_title\"$csv_separator$csv_separator$csv_separator$csv_separator$csv_separator$csv_separator\n");
        if ($structure_alias) {
            $fields = getSelectQueryResult(str_replace('%%alias%%', $structure_alias, $query));
            $previous_language_label = '';
            foreach ($fields as $new_field) {
                if (strlen($new_field['language_heading']))
                    echo $new_field['language_heading'] . $csv_separator . '<br>';
                    // field label
                $field_title = '';
                if (strlen($new_field['language_label'])) {
                    $field_title = $new_field['language_label'] . (strlen($new_field['language_tag']) ? ' ' . $new_field['language_tag'] : '');
                } else 
                    if (strlen($new_field['language_tag'])) {
                        $field_title = (strlen($previous_language_label) ? $previous_language_label . ' ' : '') . $new_field['language_tag'];
                    }
                $previous_language_label = $new_field['language_label'];
                if ($new_field['flag_confidential'])
                    $field_title .= " (CONFIDENTIAL)";
                    // field type
                $field_type = $new_field['type'];
                // field values
                $field_options = getValues($new_field['structure_value_domain']);
                // help
                $help = $new_field['language_help'];
                // system data
                $system_data = $new_field['model'] . '.' . $new_field['field'] . ' (' . $new_field['plugin'] . ' - ' . $new_field['tablename'] . ')';
                // display info
                echo $csv_separator . $field_title . $csv_separator . $field_type . $csv_separator . $field_options . $csv_separator . $help . $csv_separator . $system_data . $csv_separator . '<br>';
                fwrite($file_output, "$csv_separator\"$field_title\"$csv_separator\"$field_type\"$csv_separator\"$field_options\"$csv_separator\"$help\"$csv_separator\"$system_data\"$csv_separator\n");
            }
        }
    }
}

fclose($file_output);

echo "<br>-------------------------------------------------------------------------------------------<br><br>";
echo "Process Done : created $file_output in $path_dir<br>";

// === FUNCTIONS ================================================================================================================================
function getValues($structure_value_domain_id)
{
    global $values_of_lists;
    
    if ($structure_value_domain_id) {
        if (! isset($values_of_lists[$structure_value_domain_id])) {
            $values_of_lists[$structure_value_domain_id] = '**Empty**';
            if ($structure_value_domain_id) {
                $domain_data_results = getSelectQueryResult("SELECT id, source FROM structure_value_domains WHERE id = $structure_value_domain_id");
                if (! empty($domain_data_results)) {
                    $domain_data_results = $domain_data_results[0];
                    if ($domain_data_results['source']) {
                        if (preg_match('/getCustomDropdown\(\'(.*)\'\)/', $domain_data_results['source'], $matches)) {
                            $query = "SELECT val.value, val.en
								FROM structure_permissible_values_custom_controls AS ct
								INNER JOIN structure_permissible_values_customs val ON val.control_id = ct.id
								WHERE ct.name = '" . $matches[1] . "';";
                            $domains_values = array();
                            foreach (getSelectQueryResult($query) as $domain_value) {
                                $tmp_val = strlen($domain_value['en']) ? $domain_value['en'] : $domain_value['value'];
                                $domains_values[$tmp_val] = $tmp_val;
                            }
                            if ($domains_values)
                                $values_of_lists[$structure_value_domain_id] = implode(' // ', $domains_values);
                        } else {
                            $values_of_lists[$structure_value_domain_id] = '**Generated by function**';
                        }
                    } else {
                        $query = "SELECT val.value, i18n.en
							FROM structure_permissible_values val
							INNER JOIN structure_value_domains_permissible_values link ON link.structure_permissible_value_id = val.id
							LEFT JOIN i18n ON i18n.id = val.language_alias
							WHERE link.structure_value_domain_id = " . $domain_data_results['id'] . " AND link.flag_active = '1';";
                        $domains_values = array();
                        foreach (getSelectQueryResult($query) as $domain_value) {
                            $tmp_val = strlen($domain_value['en']) ? $domain_value['en'] : $domain_value['value'];
                            $domains_values[$tmp_val] = $tmp_val;
                        }
                        if ($domains_values)
                            $values_of_lists[$structure_value_domain_id] = implode(' // ', $domains_values);
                    }
                }
            }
        }
        return $values_of_lists[$structure_value_domain_id];
    }
    
    return '';
}

function getSelectQueryResult($query)
{
    if (! preg_match('/^[\ ]*SELECT/i', $query)) {
        echo "ERR_QUERY : 'SELECT' query expected [" . $query . ']';
        return false;
    }
    $select_result = array();
    $query_result = customQuery($query);
    if ($query_result) {
        while ($row = $query_result->fetch_assoc()) {
            $select_result[] = $row;
        }
    }
    return $select_result;
}

function customQuery($query, $insert = false)
{
    global $db_connection;
    $error = false;
    $query_res = mysqli_query($db_connection, $query) or $error = true;
    if ($error) {
        echo "ERR_QUERY : " . mysqli_error($db_connection) . ' - ' . $query;
        return false;
    }
    return ($insert) ? mysqli_insert_id($db_connection) : $query_res;
}

?>