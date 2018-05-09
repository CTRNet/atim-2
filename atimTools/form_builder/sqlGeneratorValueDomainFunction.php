<?php

require_once("../common/myFunctions.php");
define("NL", "\n");

echo createValueDomainVariableQuery();

function createValueDomainVariableQuery() {

    $data = standardize($_POST);
    unset($_POST);

    $domainName = isset($data['domain_name']) ? $data['domain_name'] : "";
    $override = isset($data['override']) ? $data['override'] : "";
    $category = isset($data['category']) ? $data['category'] : "";
    $source = isset($data['source']) ? $data['source'] : "";

    if (empty($domainName)) {
        return "Should have domain_name value.";
    }

    $query = "SELECT * FROM structure_value_domains svd WHERE domain_name=\"$domainName\"";
    $db = getConnection();
    $stmt = $db->prepare($query) or die("Query failed at line " . __LINE__ . " " . $query . " " . $db->error);
    $stmt->execute();
    $res = $stmt->get_result();

    $row = $res->fetch_assoc();
    if (!$row) {
        return createNew($domainName, $override, $category, $source);
    } else {
        return edit($domainName, $override, $category, $source);
    }
}

function createNew($domainName, $override, $category, $source) {

    $return = "start transaction;\n\n";

    $return .= "INSERT INTO `structure_value_domains` (`domain_name`, `override`, `category`, `source`) VALUES ('$domainName', '$override', '$category', '$source');\n\n";

    $return .= "commit;\n";
    
    return $return;
}

function edit($domainName, $override, $category, $source) {
    $return = "";
    if (isChanged($domainName, $override, $category, $source)){
        $return = "start transaction;\n\n";

        $return .= "UPDATE `structure_value_domains` "
                . "SET override = '$override', category = '$category', source = '$source'"
                . "WHERE `domain_name`='$domainName';\n\n";

        $return .= "commit;\n";
    }
    
    return $return;
}

function isChanged($domainName, $override, $category, $source){
    $query = "SELECT * FROM structure_value_domains svd WHERE domain_name='$domainName' AND override = '$override' AND category = '$category' AND source = '$source'";
    $db = getConnection();
    $stmt = $db->prepare($query) or die("Query failed at line " . __LINE__ . " " . $query . " " . $db->error);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    return (empty($row));
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
