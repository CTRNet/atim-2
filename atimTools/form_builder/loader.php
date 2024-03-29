<?php

require_once("../common/myFunctions.php");
$json = new \stdClass();
if ($_POST) {
    foreach ($_POST as $key => $val) {
        $json->$key = $val;
    }
} else {
    $json = json_decode(stripslashes($_GET['json'])) or die("decode failed");
}
$query = "";
if ($json->type == 'structures') {
    if (is_numeric($json->val)) {
        $query = "SELECT alias FROM structures WHERE id='" . $json->val . "'";
        $result = $db->query($query) or die("Query failed " . $db->error);
        if ($row = $result->fetch_assoc()) {
            echo("<h3>Structure " . $row['alias'] . "</h3>\n");
        }
        $query = "SELECT * "
                . "FROM `structure_formats` AS sfo "
                . "INNER JOIN structure_fields AS sfi ON sfi.id = sfo.structure_field_id "
                . "WHERE sfo.structure_id = '" . $json->val . "'";
    } else {
        $query = "SELECT alias FROM structures WHERE alias='" . $json->val . "'";
        $result = $db->query($query) or die("Query failed " . $db->error);
        if ($row = $result->fetch_assoc()) {
            echo("<h3>Structure " . $row['alias'] . "</h3>\n");
        }
        $query = "SELECT * "
                . "FROM `structure_formats` AS sfo "
                . "INNER JOIN structure_fields AS sfi ON sfi.id = sfo.structure_field_id "
                . "WHERE sfo.id = '" . $json->val . "'";
    }
} else if ($json->type == 'tables') {
    echo "<h3>Table: <span id='tablename'>" . $json->val . "</span></h3>";
    echo "Autotype: <input id='table_autotype' type='checkbox' checked='checked'/>";
    $query = "DESC " . $json->val;
    echo '<button id="createAll">Create All</button>';
} else if ($json->type == 'fields') {
    $query = "SELECT * FROM structure_fields WHERE model = '" . $json->val . "'";
} else if ($json->type == 'structure_permissible_values') {
    $query = "SELECT * FROM structure_permissible_values ORDER BY language_alias";
} else if ($json->type == 'value_domains') {
    $query = "SELECT * FROM structure_value_domains AS svd
				INNER JOIN structure_value_domains_permissible_values AS svdpv ON svd.id=svdpv.structure_value_domain_id
				INNER JOIN structure_permissible_values AS spv ON svdpv.structure_permissible_value_id=spv.id				
				LEFT JOIN i18n ON i18n.id=spv.language_alias				
				WHERE (domain_name = '" . $json->val . "' OR svd.id='" . $json->val . "') ORDER BY display_order";
    } else if ($json->type == 'value_domains_variable') {
    $query = "SELECT 
                    value,
                    sc.id control_id, 
                    name,
                    flag_active,
                    category,
                    en,
                    fr,
                    display_order,
                    use_as_input,
                    values_max_length,
                    (SELECT source
                            FROM structure_value_domains svd
                            WHERE domain_name=\"" . $json->val . "\") source,
                    s.id id
                FROM 
                    structure_permissible_values_custom_controls sc
                JOIN structure_permissible_values_customs s ON s.control_id=sc.id
                where 
                    sc.name = (
                        SELECT 
                            SUBSTR(source, LOCATE(\"('\", source)+2, LOCATE(\"')\", source) - LOCATE(\"('\", source)-2) name
                        FROM
                            (SELECT source
                            FROM structure_value_domains svd
                            WHERE domain_name=\"" . $json->val . "\") temp
                        )";
} else if ($json->type == 'value_domains_function') {
    $query = "SELECT * FROM `structure_value_domains` WHERE (`source` IS NOT NULL && `source` <> '') AND `source` NOT LIKE '%(%)%' AND domain_name ='".$json->val."';";
} else {
    $query = "SELECT 'error'";
}
$result = $db->query($query) or die("Query failed " . $db->error);
if (isset($json->as) && $json->as == 'json') {
    $json_result = array();
    while ($row = $result->fetch_assoc()) {
        $json_result[] = $row;
    }
    echo json_encode($json_result);
} else {
    echo('<table class="ui-widget ui-widget-content">');
    if ($row = $result->fetch_assoc()) {
        echo("<thead><tr class='ui-widget-header'>");
        foreach ($row as $k => $v) {
            echo("<th>" . $k . "</td>");
        }
        echo("</tr></thead>");
        echo("<tbody>");
        foreach ($row as $k => $v) {
            echo("<td class='" . $k . "'>" . $v . "</td>");
        }
        echo("</tr>");
    }
    while ($row = $result->fetch_assoc()) {
        echo("<tr>");
        foreach ($row as $k => $v) {
            echo("<td class='" . $k . "'>" . $v . "</td>");
        }
        echo("</tr>");
    }
    echo("<tdoby></table>");
}
?>