<?php
require_once("../common/myFunctions.php");

class InvConf {

    static $queryAliquot = "SELECT * FROM aliquot_controls 
		WHERE sample_control_id=? ORDER BY aliquot_type";
    static $querySample = "SELECT assoc.parent_sample_control_id AS parent_sample_control_id,
		assoc.derivative_sample_control_id AS derivative_sample_control_id,
		assoc.id AS control_id,
		assoc.flag_active AS flag_active,
		s.sample_type AS sample_type,
		s.detail_form_alias AS detail_form_alias
 		FROM parent_to_derivative_sample_controls AS assoc  
		INNER JOIN sample_controls AS s ON assoc.derivative_sample_control_id=s.id 
		WHERE assoc.parent_sample_control_id=?
		ORDER BY s.sample_type ";
    static $queryRealiquoting = "SELECT *, rc.id AS mid, rc.flag_active AS flag_active FROM realiquoting_controls AS rc 
		INNER JOIN aliquot_controls AS a ON rc.child_aliquot_control_id=a.id
		WHERE rc.parent_aliquot_control_id=?";
    static $printedSamples = array();

    static function printInner($id, $depth, $display_disabled_options) {
        $db = getConnection();
        $db2 = getConnection();
        //aliquot
        $stmt = $db->prepare(InvConf::$queryAliquot) or die("printInner qry 2 failed");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = bindRow($stmt);
        $stmt2 = $db2->prepare(InvConf::$queryRealiquoting) or die("printInner qry3 faield");
        $row2 = bindRow($stmt2);
        if ($stmt->fetch()) {
            echo("<ul class='aliquots'>\n");
            do {
                $disabled = $row['flag_active'] ? "" : " disabled ";
                $json = '{ "id" : "' . $row['id'] . '" }';
                if (empty($disabled) || $display_disabled_options) {
                    echo("<li class='aliquot aliquot_" . $row['id'] . " " . $disabled . " " . $json . "'><div class='aliquot_cell'>" . $row['aliquot_type'] . ($display_disabled_options ? "<br/><span class='small'>" . $row['detail_form_alias'] . "</span>" : "") . "</div>");
                }
                $stmt2->bind_param("i", $row['id']);
                $stmt2->execute();
                if ($stmt2->fetch()) {
                    echo("<ul class='realiquots'>\n");
                    do {
                        $disabled = $row2['flag_active'] ? "" : " disabled ";
                        $json = '{ "id" : "' . $row2['mid'] . '" }';
                        if (empty($disabled) || $display_disabled_options) {
                            echo("<li class='realiquot realiquot_" . $row2['mid'] . " " . $disabled . " " . $json . "'>" . $row2['aliquot_type'] . "</li>\n");
                        }
                    } while ($stmt2->fetch());
                    echo("</ul>\n");
                }
                echo("</li>\n");
            } while ($stmt->fetch());
            echo("</ul>\n");
        }
        $stmt->close();
        echo("</div>");

        //child	
        $stmt = $db->prepare(InvConf::$querySample) or die("printInner qry 1 failed ");
        $stmt->bind_param("i", $id);
        $row = bindRow($stmt);
        $stmt->execute();
        if ($stmt->fetch()) {
            echo("<ul class='samples' style='vertical-align: middle;'>\n");
            do {
                //block direct redundancy and limit depth in case of error
                //curernt max depth is 6 so 8 should suffice as a precaution
                if (!in_array($row['parent_sample_control_id'], InvConf::$printedSamples) && $depth < 8) {
                    $disabled = $row['flag_active'] ? "" : " disabled";
                    $json = '{ "id" : "' . $row['control_id'] . '"}';
                    if (empty($disabled) || $display_disabled_options) {
                        echo("<li class='sample sample_" . $row['control_id'] . " derivative_" . $row['derivative_sample_control_id'] . $disabled . " " . $json . "'><div class='sample_node'><div class='sample_cell'>" . $row['sample_type'] . ($display_disabled_options ? "<br/><span class='small'>" . $row['detail_form_alias'] . "</span>" : "") . "</div>");
                        if ($row['derivative_sample_control_id'] == $row['parent_sample_control_id']) {
                            array_push(InvConf::$printedSamples, $row['derivative_sample_control_id']);
                        }
                        InvConf::printInner($row['derivative_sample_control_id'], $depth + 1, $display_disabled_options);
                        if ($row['derivative_sample_control_id'] == $row['parent_sample_control_id']) {
                            array_pop(InvConf::$printedSamples);
                        }
                        echo("</li>\n");
                    }
                }
            } while ($stmt->fetch());
            echo("</ul>\n");
        }
        $stmt->close();
    }

}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <!--<link rel="stylesheet" type="text/css" href="style.css"/>-->
        <link rel="stylesheet" type="text/css" href="css/style.css"/>
        <link rel="stylesheet" type="text/css" href="css/style.min.css"/>
        <link rel="stylesheet" type="text/css" href="css/jquery-ui.css"/>
        <!--<link rel="stylesheet" type="text/css" href="print.css" media="print"/>-->
        <title>Inventory Configuration</title>
        <!--<script type="text/javascript" src="../common/js/jquery-1.6.min.js"></script>-->
        <!--<script type="text/javascript" src="../common/js/wz_jsgraphics.js"></script>-->
        <script type="text/javascript" src="../common/js/common.js"></script>
        <!--<script type="text/javascript" src="default.js"></script>-->
        <script type="text/javascript" src="js/default.js"></script>
        <script type="text/javascript" src="js/jquery-1.12.4.js"></script>
        <script type="text/javascript" src="js/jquery-ui.js"></script>
        <script type="text/javascript" src="js/jstree.min.js"></script>

    </head>
    <body id="tools-inventory">
        <script>
            $(function () {
                $("#dbSelect").change(function () {
                    document.location = "index.php?db=" + $(this).val();
                });
            });
        </script>

        <div id="top">
            Current database: 
            <?php
            $db2 = getConnection();
            $query = "SHOW databases";
            $result = $db->query($query) or die("show databases failed");
            ?>
            <select id="dbSelect">
                <option></option>
                <?php
                while ($row = $result->fetch_row()) {
                    if ($row[0] != "information_schema" && $row[0] != "mysql") {
                        $selected = ($row[0] == $db_schema ? ' selected="selected"' : "");
                        echo("<option" . $selected . ">" . $row[0] . "</option>");
                    }
                }
                ?>
            </select>
        </div>

        <div id='content'>
            <div id="sample-div">
                <fieldset>
                    <legend>Samples</legend>
                    <div id="sample">Loading...</div>
                </fieldset>
            </div>

            <div>
                <fieldset>
                    <legend>Aliquots + Realiquoting</legend>
                    <div id="aliquot-sample"></div>
                </fieldset>
            </div>

            <div id="preview">
                <fieldset>
                    <legend>Samples Preview Tree</legend>
                    <div id="preview-sample"></div>
                </fieldset>
            </div>
        </div>

        <script>
            $.get('samples.php', {data: 'sample'}, function (data) {
                $("#sample").html(data);
                initial();
            });
            $.get('samples.php', {data: 'aliquot'}, function (data) {
                $("#aliquot").html(data);
                initialAliquots("#aliquot");
            });
        </script>

        <button id="create-query">Query</button>
        <button id ="copy-queries" disabled>Copy</button>

        <fieldset>
            <legend>Queries</legend>
            <textarea id="out"></textarea>
        </fieldset>
        <div style="display: none" id="dialog">
            The description's text for delete.
        </div>
        <div id="aliquot" style="display: none">
        </div>
        
    </body>
</html>