<?php

require_once("../../common/myFunctions.php");
$data = $_GET['data'];
if (strtolower($data) == 'sample') {
    echo InventoryConfiguration::sampleControl();
} elseif (strtolower($data) == 'aliquot') {
    echo InventoryConfiguration::aliquotControl();
} elseif (strtolower($data) == 'samplelist') {
    echo InventoryConfiguration::sampleListControl();
} elseif (strtolower($data) == 'valueunit') {
    echo InventoryConfiguration::valueUnit();
}

class InventoryConfiguration {

    static function valueUnit(){
        $return = [];
        $query = "
            SELECT * FROM structure_value_domains AS svd
            INNER JOIN structure_value_domains_permissible_values AS svdpv ON svd.id=svdpv.structure_value_domain_id
            INNER JOIN structure_permissible_values AS spv ON svdpv.structure_permissible_value_id=spv.id				
            INNER JOIN i18n ON i18n.id=spv.language_alias				
            WHERE (domain_name = 'aliquot_volume_unit' OR svd.id='aliquot_volume_unit')
        ";

        $db = getConnection();
        $stmt = $db->prepare($query) or die("Error in file:". __FILE__.", line:".__LINE__);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        while ($row) {
            $value = $row['value'];
            $en = $row['en'];
            $return[]=array(
                "val" => $value,
                "text" => $en
            );
        $row = $res->fetch_assoc();
        }

        return json_encode($return);
    }
    
    static function sampleControl($parent = null, $parentsId = array()) {
        $return = "";
        $parentsId[] = $parent;
        if (!$parent) {
            $s = "p.parent_sample_control_id is NULL";
        } else {
            $s = "p.parent_sample_control_id = ?";
        }

        $query = "
            SELECT p.id row_id, s.id id, s.sample_type, s.detail_form_alias, s.detail_tablename, s.databrowser_label, p.flag_active
            FROM parent_to_derivative_sample_controls p
            JOIN sample_controls s ON p.derivative_sample_control_id = s.id
            WHERE 
                " . $s . "
                ORDER BY display_order
        ";
        $db = getConnection();
        $stmt = $db->prepare($query) or die("Select a database;");

        if ($parent) {
            $stmt->bind_param("s", $parent);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        if ($row) {
            $return .= "<ul class = 'draggable'>\n";
            while ($row) {
                $id = $row['id'];
                $rowId = $row['row_id'];
                $title = $row['sample_type'];
                $flagActive = $row['flag_active'];

                $display = (!$parent) ? "display-block" : "display-none";
                $checked = ($flagActive) ? "checked" : "";
                $parentId = ($parent) ? $parent : -1;
                $return .= "<li data-id = '$id' class = 'no-bull-li $display sample-li' data-row-id = '$rowId' data-parent-id = '$parentId'>";
                if (in_array($id, $parentsId) === false) {
                    $tempRespons = self::sampleControl($id, $parentsId);
                }

                $plusDisplay = (!empty($tempRespons) && $flagActive && in_array($id, $parentsId) === false) ? "display-inline-block" : "display-none";
                $minusDisplay = "display-none";
                $undoDisplay = "display-none";
                $deleteDisplay = "display-inline-block";
                $addDisplay = "display-inline-block";
//                $trashDisplay = "display-inline-block";
                $emptyDisplay = ($plusDisplay == "display-none") ? "display-inline-block" : "display-none";

                $return .= "
                        <span class = 'minus ui-icon ui-icon-caret-1-s $minusDisplay'></span>
                        <span class = 'plus ui-icon ui-icon-caret-1-e $plusDisplay'></span>
                        <span class = 'empty ui-icon ui-icon-blank $emptyDisplay'></span>
                    ";

                $return .= "<input class = 'check-box' type = 'checkbox' " . $checked . " data-id = '$id'>";
                $return .= "<div class = 'display-inline-block aliquot-display-hover' >$title</div>";
                $return .= "<span class = 'delete ui-icon ui-icon-closethick $deleteDisplay'></span>
                            <span class = 'undo ui-icon ui-icon-arrowreturnthick-1-w $undoDisplay'></span>";
//                $return .= ($parentId==-1)?"":"<span class = 'trash ui-icon ui-icon-trash $trashDisplay'></span>";
                $return .= "<span class = 'add ui-icon ui-icon-plusthick $addDisplay' title='Add sub sample'></span>";
                if (in_array($id, $parentsId) === false) {
                    $return .= $tempRespons;
                }

                $return .= "</li>";
                $row = $res->fetch_assoc();
            }
            $return .= "</ul>";
        }
        $stmt->close();
        return $return;
    }

    static function sampleListControl() {
        $return = array();
        $query = "
                SELECT 
                    id,
                    sample_type, 
                    sample_category,
                    detail_form_alias,
                    detail_tablename,
                    databrowser_label
                FROM 
                    sample_controls
                ORDER BY sample_type ASC        
            ";
        $db = getConnection();
        $stmt = $db->prepare($query) or die("Select a database;");

        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        while ($row) {
            $id = $row['id'];
            $title = $row['sample_type'];
            $sampleCategory = $row['sample_category'];
            $detailFormAlias = $row['detail_form_alias'];
            $detailTablename= $row['detail_tablename'];
            $databrowserLabel = $row['databrowser_label'];
            $return[$title] = array(
                'id' => $id, 
                'title' => $title,
                'sampleCategory' => $sampleCategory,
                'detailFormAlias' => $detailFormAlias,
                'detailTablename' => $detailTablename,
                'databrowserLabel' => $databrowserLabel,
                'listed' => ($sampleCategory == 'specimen') ? false : true
            );
            $row = $res->fetch_assoc();
        }
        $stmt->close();
        return json_encode($return);
    }

    static function aliquotControl() {
        $return = "";
        $query = "
            SELECT 
                re.id, 
                sc.id sample_id,
                sc.sample_type sample_type,
                ac1.flag_active ac_flag_active_p,
                ac2.flag_active ac_flag_active_c,
                ac1.aliquot_type parent_aliquot,
                ac2.aliquot_type child_aliquot,
                re.child_aliquot_control_id child_id, 
                ac1.id parent_id, 
                re.flag_active re_flag_active,
                ac1.volume_unit vu
            FROM  realiquoting_controls re
            INNER JOIN aliquot_controls ac2 on ac2.id=re.child_aliquot_control_id
            RIGHT JOIN aliquot_controls ac1 on ac1.id=re.parent_aliquot_control_id
            INNER JOIN sample_controls sc on sc.id=ac1.sample_control_id
            ORDER BY sample_id, parent_id DESC, child_id DESC";

        $db = getConnection();
        $stmt = $db->prepare($query) or die("Problem in Aliquot Control Query.");

        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        if ($row) {
            $sampleId = -1;
            $return .= "<ul id = 'aliquot-list'>";
            while ($row) {
                $id = $row['id'];
                $sampleType = $row['sample_type'];
                $acFlagActiveP = $row['ac_flag_active_p'];
                $acFlagActiveC = $row['ac_flag_active_c'];
                $parentAliquot = $row['parent_aliquot'];
                $childAliquot = $row['child_aliquot'];
                $childId = $row['child_id'];
                $reFlagActive = $row['re_flag_active'];
                $volumeUnit = $row['vu'];
                if ($sampleId != $row['sample_id']) {
                    $parentId = $row['parent_id'];

                    if ($sampleId != -1) {
                        $return .= "</ul>";
                        $return .= "</li>";
                        $return .= "</ul>";
                        $return .= "</li>";
                    }
                    $sampleId = $row['sample_id'];
                    $return .= "<li class='sample' data-id='$sampleId'>\n";
                    $return .= "<div>$sampleType</div>";
                    $return .= "<ul>";
                    $disable = ($acFlagActiveP == 0) ? "disable" : "";
                    $return .= "<li class = 'aliquot $disable' data-id='$parentId' data-volume-unit='$volumeUnit'>";
                    $return .= "<div>$parentAliquot</div>";
                    $reAliquotExists = ($id) ? '' : 'no-display';
                    $return .= "<ul class = '$reAliquotExists'>";
                    $disable = ($reFlagActive == 0) ? "disable" : "";
                    $return .= "<li class='re-aliquot $disable' data-child-id = '$childId' data-id='$id'>\n";
                    $return .= "<div>$childAliquot</div>";
                    $return .= "</li>";

                } else {
                    if ($parentId != $row['parent_id'] || !$parentId) {
                        $parentId = $row['parent_id'];
                        $return .= "</ul>";
                        $return .= "</li>";
                        $disable = ($acFlagActiveP == 0) ? "disable" : "";
                        $return .= "<li class = 'aliquot $disable' data-id='$parentId' data-volume-unit='$volumeUnit'>";
                        $return .= "<div>$parentAliquot</div>";
                        $reAliquotExists = ($id) ? '' : 'no-display';
                        $return .= "<ul class = '$reAliquotExists'>";
                    }
                    $disable = ($reFlagActive == 0) ? "disable" : "";
                    $return .= "<li class='re-aliquot $disable' data-child-id = '$childId' data-id='$id'>\n";
                    $return .= "<div>$childAliquot</div>";
                    $return .= "</li>";

                }

                $row = $res->fetch_assoc();
            }
            $return .= "</ul>";
            $return .= "</li>";
            $return .= "</ul>";
            $return .= "</ul>";
        }

        return $return;
    }

}
