<?php

require_once("../../common/myFunctions.php");

class MenuConfiguration {

    static function getText($id, $lang = 'en') {
        $query = "SELECT * FROM i18n WHERE id=? LIMIT 1";
        $db = getConnection();
        if (!$stmt = $db->prepare($query)) {
            return $id;
        }
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        if (!$row) {
            return $id;
        }
        return $row[$lang];
    }

    static function getRightMenu($parent, $changable = true) {
        $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        $lang = 'en';
        $queryChildren = "
            SELECT * 
            FROM `menus`
            WHERE 
                parent_id = ? and
                is_root = 1 " .
                "ORDER BY display_order
        ";
        $db = getConnection();
        $stmt = $db->prepare($queryChildren) or die("Select a database;");
        $stmt->bind_param("s", $parent);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        if ($row) {
            echo "<ul class = 'draggable'>\n";
            while ($row){
                $changable = true;
                    $id = $row['id'];
                    $useLink = $row['use_link'];
                    $changable = (stripos($useLink, "datamart") == false) && (stripos($useLink, "administrate") == false) && $changable;
                    $canChange = ($changable) ? "changable" : "unchangable";
                    $title = self::getText($row['language_title'], $lang);
                    $description = self::getText($row['language_description'], $lang);
                    $active = ($row['flag_active']) ? "" : "disabled";
                    $dataRow = array(
                        "id" => $id,
                        "parent_id" => $parent,
                        "language_title" => $row['language_title'],
                        "language_description" => $row['language_description'],
                        "flag_active" => $row['flag_active'],
                        "display_order" => $row['display_order'],
                    );
                    $dataRow = json_encode($dataRow);
                    echo "<li id = '$id' class = '$active $canChange' data-row = '$dataRow'>";
                    echo "<div title = '$description'>$title</div>";
                    self::getRightMenu($id, $changable);
                    echo "</li>";
                $row = $res->fetch_assoc();
            }
        echo "</ul>";
        }
        $stmt->close();
    }

    static function getLeftMenu($parent) {
        $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        $lang = 'en';

        $query = "
            SELECT * 
            FROM menus m
            WHERE parent_id = ? and
            (is_root =0 || (SELECT id FROM menus WHERE parent_id = m.id LIMIT 1) IS NOT NULL)
            ORDER BY is_root, display_order
        ";
        $db = getConnection();
        $stmt = $db->prepare($query) or die("Select the data base.");
        $stmt->bind_param("s", $parent);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        if ($row){
            echo "<ul class = 'draggable'>\n";
            while ($row){
                    $id = $row['id'];
                    $title = self::getText($row['language_title'], $lang);
                    $description = self::getText($row['language_description'], $lang);
                    $active = ($row['flag_active']) ? "" : "disabled";
                    $dataRow = array(
                        "id" => $id,
                        "parent_id" => $parent,
                        "language_title" => $row['language_title'],
                        "language_description" => $row['language_description'],
                        "flag_active" => $row['flag_active'],
                        "display_order" => $row['display_order'],
                    );
                    $dataRow = json_encode($dataRow);
                    $canChange = ($row['is_root']==0)?'changable-parent':'unchangable-parent';
                    echo "<li id = '$id' class = '$active $canChange' data-row = '$dataRow'>";
                    echo "<div title = '$description'>$title</div>\n";
                    self::getLeftMenu($id);
                    echo "</li>\n";
                $row = $res->fetch_assoc();
            }
            echo "</ul>\n";
        }
    }

}

$data = $_GET['menu'];

if (strtolower($data) == 'left') {
    echo MenuConfiguration::getLeftMenu("Main_Menu_1");
} else if (strtolower($data) == 'right') {
    echo MenuConfiguration::getRightMenu("Main_Menu_1");
}


