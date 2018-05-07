<?php

require_once("../common/myFunctions.php");
define("NL", "\n");

echo createValueDomainVariableQuery();

function createValueDomainVariableQuery() {

    $data = standardize($_POST);
//print_r($data); 
    unset($_POST);
    $values = isset($data['rows']) ? $data['rows'] : array();

    $domainName = isset($data['domain_name']) ? $data['domain_name'] : "";
    $name = isset($data['name']) ? $data['name'] : "";
    $category = isset($data['category']) ? $data['category'] : "";
    $source = sprintf("StructurePermissibleValuesCustom::getCustomDropdown(\'%s\')", $name);
    $valuesMaxLength = isset($data['values_max_length']) ? $data['values_max_length'] : "";
    $flag_active = isset($data['flag_active']) ? $data['flag_active'] : 0;
    $control_id = isset($data['control_id']) ? $data['control_id'] : 0;


    if (empty($domainName)) {
        return "Should have domain_name value.";
    }

    $query = "SELECT * FROM structure_value_domains svd WHERE domain_name=\"$domainName\"";
    $db = getConnection();
    $stmt = $db->prepare($query) or die("Query failed at line " . __LINE__ . " " . $query . " " . $db->error);
    $stmt->execute();
    $res = $stmt->get_result();

    $row = $res->fetch_assoc();
    $nameTemp = substr($row['source'], strpos($row['source'], "'") + 1, -2);
    if (!$row || $nameTemp != $name) {
        return createNew($values, $domainName, $name, $category, $source, $valuesMaxLength, $flag_active, !$row);
    } else {
        return edit($values, $name, $category, $valuesMaxLength, $flag_active, $control_id);
    }
}

function createNew($values, $domainName, $name, $category, $source, $valuesMaxLength, $flag_active, $svdNew) {

    $return = "start transaction;\n";

    $return .= "INSERT INTO structure_permissible_values_custom_controls \n(name, flag_active, values_max_length, category) VALUES\n";
    $return .= "('$name', $flag_active, $valuesMaxLength, '$category');" . NL . NL;

    $return .= "SET @control_id = (SELECT id FROM structure_permissible_values_custom_controls WHERE name = '$name');" . NL . NL;

    $return .= "SET @user_id = 2;" . NL . NL;

    $return .= "INSERT INTO structure_permissible_values_customs \n(`value`, `en`, `fr`, `display_order`, `use_as_input`, `control_id`, `modified`, `created`, `created_by`, `modified_by`) VALUES\n";

    $maxLen = 0;
    $valueAsIputCounter = 0;
    $query = array();
    foreach ($values as $value) {
        if ($value['use_as_input']) {
            $valueAsIputCounter++;
        }
        $query[] = '("' . $value['value'] . '", "' . $value['en'] . '", "' . $value['fr'] . '", "' . $value['display_order'] . '", "' . $value['use_as_input'] . '", @control_id, NOW(), NOW(), @user_id, @user_id)';
        if ($maxLen < max(strlen($value['value']), strlen($value['en']), strlen($value['fr']))) {
            $maxLen = max(strlen($value['value']), strlen($value['en']), strlen($value['fr']));
        }
    }

    $return .= implode(", \n", $query) . ";" . NL . NL;

    $return .= "UPDATE structure_permissible_values_custom_controls \n"
            . "SET values_max_length = IF($valuesMaxLength<$maxLen, $maxLen, $valuesMaxLength),"
            . " values_used_as_input_counter = $valueAsIputCounter, values_counter = " . count($values)
            . " WHERE name = '$name';" . NL . NL;
    if ($svdNew) {
        $return .= "INSERT INTO structure_value_domains (domain_name, override, category, source) values\n"
                . "('$domainName', '', '', '$source');" . NL . NL;
    } elseif ($svdNew == false) {
        $return .= "UPDATE structure_value_domains SET source = '$source' WHERE domain_name = '$domainName';" . NL . NL;
    }


    $return .= "commit;\n";
    return $return;
}

function edit($values, $name, $category, $valuesMaxLength, $flag_active, $control_id) {
    $return = "";
    $changing = array();
    $isCustumChanged = isChangingInStructure_permissible_values_customs($values, $control_id, $changing);
    $isControlChanged = isStructure_permissible_values_custom_controlsChanged($flag_active, $valuesMaxLength, $category, $name);
    if ($isCustumChanged || $isControlChanged) {

        if ($isControlChanged) {
            $return .= "UPDATE structure_permissible_values_custom_controls \n"
                    . "SET flag_active = $flag_active, values_max_length = '$valuesMaxLength', category = '$category'\n"
                    . "WHERE name = '$name';" . NL . NL;
        }
        if ($isCustumChanged) {
            $return .= "SET @user_id = 2;" . NL . NL;

            $insert = "INSERT INTO structure_permissible_values_customs \n(`value`, `en`, `fr`, `display_order`, `use_as_input`, `control_id`, `modified`, `created`, `created_by`, `modified_by`) VALUES\n";

            $maxLen = 0;
            $valueAsIputCounter = 0;
            $insertQuery = array();
            $updateQuery = array();

            foreach ($values as $value) {
                if ($value['use_as_input']) {
                    $valueAsIputCounter++;
                }
                if (empty($value['id'])) {
                    $insertQuery[] = "('" . $value["value"] . "', '" . $value["en"] . "', '" . $value["fr"] . "', '" . $value["display_order"] . "', '" . $value["use_as_input"] . "', $control_id, NOW(), NOW(), @user_id, @user_id)";
                } elseif (isStructure_permissible_values_customsChanged($value)) {
                    $updateQuery [] = "UPDATE structure_permissible_values_customs \nSET "
                            . "`value` = '" . $value['value'] . "', `en` = '" . $value['en'] . "', `fr` = '" . $value['fr'] . "', `use_as_input` = '" . $value['use_as_input'] . "', `control_id` = $control_id, `modified` = NOW() \n"
                            . "WHERE id = " . $value['id'] . ";";
                }
                if ($maxLen < max(strlen($value['value']), strlen($value['en']), strlen($value['fr']))) {
                    $maxLen = max(strlen($value['value']), strlen($value['en']), strlen($value['fr']));
                }
            }

            if (!empty($insertQuery)) {
                $return .= $insert . implode(", \n", $insertQuery) . ";" . NL . NL;
            }
            if (!empty($updateQuery)) {
                $return .= implode(", \n", $updateQuery) . NL . NL;
            }

            $return .= "UPDATE structure_permissible_values_custom_controls \n"
                    . "SET values_max_length = IF($valuesMaxLength<$maxLen, $maxLen, $valuesMaxLength),"
                    . " values_used_as_input_counter = $valueAsIputCounter, values_counter = " . count($values)
                    . " WHERE id = '$control_id';" . NL . NL;

            if ($changing['delete']) {
                $deletes = implode(", ", $changing['delete']);
                $return .= "DELETE FROM structure_permissible_values_customs WHERE id IN($deletes);\n\n";
            }
        }
    }
    if (!empty($return)) {
        $return = "start transaction;\n" . $return . "commit;\n";
    }
    return $return;
}

function isStructure_permissible_values_custom_controlsChanged($flag_active, $valuesMaxLength, $category, $name) {
    $query = "SELECT * FROM structure_permissible_values_custom_controls\n"
            . "WHERE name = '$name' AND flag_active = '$flag_active' AND values_max_length = '$valuesMaxLength' AND category = '$category'";
    $db = getConnection();
    $stmt = $db->prepare($query) or die("Query failed at line " . __LINE__ . " " . $query . " " . $db->error);
    $stmt->execute();
    $res = $stmt->get_result();

    $row = $res->fetch_assoc();

    return (empty($row));
}

function isStructure_permissible_values_customsChanged($v) {
    $value = $v['value'];
    $en = $v['en'];
    $fr = $v['fr'];
    $display_order = $v['display_order'];
    $use_as_input = $v['use_as_input'];
    $query = "SELECT * FROM structure_permissible_values_customs\n"
            . "WHERE value = '$value' AND en = '$en' AND fr = '$fr' AND display_order = '$display_order' AND use_as_input = '$use_as_input'";
    $db = getConnection();
    $stmt = $db->prepare($query) or die("Query failed at line " . __LINE__ . " " . $query . " " . $db->error);
    $stmt->execute();
    $res = $stmt->get_result();

    $row = $res->fetch_assoc();
    return (!$row);
}

function isChangingInStructure_permissible_values_customs($values, $control_id, &$return) {
    $return = array('update' => array(), 'noChange' => array(), 'delete' => array(), 'add' => array());
    $changed = false;
    $idsNew = array();
    foreach ($values as $k => $value) {
        if (empty($value['id'])) {
            $return['add'][] = $k;
            $changed = true;
        } elseif (isStructure_permissible_values_customsChanged($value)) {
            $return['update'][] = $value['id'];
            $changed = true;
            $idsNew[] = $value['id'];
        } else {
            $return['noChange'][] = $value['id'];
            $idsNew[] = $value['id'];
        }
    }

    $query = "SELECT id FROM structure_permissible_values_customs\n"
            . "WHERE control_id = '$control_id'";
    $db = getConnection();
    $stmt = $db->prepare($query) or die("Query failed at line " . __LINE__ . " " . $query . " " . $db->error);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $ids = array();
    while ($row) {
        $ids[] = $row['id'];
        $row = $res->fetch_assoc();
    }

    $return['delete'] = array_diff($ids, $idsNew);
    return (!empty($return['delete']) || !empty($return['update']) || !empty($return['add']));
}

function standardize($values) {
    if (gettype($values) == 'string') {
        return addslashes($values);
    } else if (gettype($values) == 'array') {
        foreach ($values as &$value) {
            $value = standardize($value);
        }
    } else {
        return $values;
    }
    return $values;
}
