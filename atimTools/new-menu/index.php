<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

        <title>Menus Configuration</title>

        <script src="js/jquery-1.12.4.js"></script>
        <script src="js/jquery-ui.js"></script>
        <!--<script src="js/jstree.min.js"></script>-->

        <script src="../common/js/common.js"></script>
        <script src="js/default.js"></script>
        
        <link rel="stylesheet" type="text/css" href="css/style.css"/>
        <!--<link rel="stylesheet" href="css/style.min.css"/>-->
        <link rel="stylesheet" href="css/jquery-ui.css">
        
        <script>
            $(function () {
                $("#dbSelect").change(function () {
                    document.location = "index.php?db=" + $(this).val();
                });
            });
        </script>

    </head>
    <body>
        <div id="top">
            Current database: 
            <?php
            require_once("../common/myFunctions.php");
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
        <div id = "menus">
            <div id = "left-menu">
                <h1>Left Menu</h1>
                <div class = "content">
                    <img src="images/wait.gif" alt="Please wait" style = "height: 100px; width: auto">
                    <script>
                        var ready = 0;
                        $.get('menu.php', {menu: 'left'}, function (data) {
                            $("#left-menu .content").hide();
                            $("#left-menu .content").html(data);
                            if (++ready===2){
                               initial();
                            }
                        });
                    </script>
                </div>
            </div>
            <div id = "right-menu">
                <h1>Right Menu</h1>
                <div class = "content">
                    <img src="images/wait.gif" alt="Please wait" style = "height: 100px; width: auto">
                    <script>
                        $.get('menu.php', {menu: 'right'}, function (data) {
                            $("#right-menu .content").hide();
                            $("#right-menu .content").html(data);
                            if (++ready===2){
                               initial();
                            }
                        });
                    </script>
                </div>
            </div>
        </div>
        <div>
            <button onclick="createQuery()">Create Query</button>
            <button id ="copy-queries" onclick="copyText()" disabled>Copy</button>
            <fieldset>
                <legend>Queries</legend>
                <textarea id="out"></textarea>
            </fieldset>
            <pre id="debug"></pre>
    </body>
</html>