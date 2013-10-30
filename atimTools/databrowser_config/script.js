//requires raphael-min.js, dracula_graffle.js, dracula_graph.js,
//jquery-1.7.1.min.js, common.js"

var redraw, g, renderer, browsingData, toDisable, toEnable;
var toDisable = Array();
var toEnable = Array();
var disabledColor = "#FF6666";
var enabledColor = "#000000";

window.onload = function(){
    initDbSelector("#dbSelect");
    $.getJSON("datamartInfo.php", function(data){
        browsingData = data;
        drawGraph(data);

        //paint disabled options in red
        $(data["links"]).each(function(i, v){
            if(v[2] == 0){
                $("#" + v[0] + "_" + v[1]).attr("stroke", disabledColor);
            }
        });
    });
};

function removeFromArray(item, arr){
    var index;
    if((index = $.inArray(item, arr)) > -1){
       return arr.splice(index, 1);
    }
}

function genQueries(){
    var parts = [];
    var buildWhere = function(ids){
        $(ids).each(function(i, v){
            v = v.split("_");
            parts.push("id1=" + v[0] + " and id2=" + v[1]);
        });
        return parts;
    };
    
    var queries = "";
    if(toDisable.length){
        queries += "UPDATE datamart_browsing_controls "
            + "SET flag_active_1_to_2=0, flag_active_2_to_1=0 "
            + "WHERE (" + buildWhere(toDisable).join(") or (") + ");<br/>";
    }
    if(toEnable.length){
        queries += "UPDATE datamart_browsing_controls "
            + "SET flag_active_1_to_2=1, flag_active_2_to_1=1 "
            + "WHERE (" + buildWhere(toEnable).join(") or (") + ");<br/>";
    }
    $("fieldset div").html(queries);
}

var render = function(r, n) {
    var wrapWidth = 15;
    var set = r.set().push(
        /* custom objects go here */
        r.circle(n.point[0], n.point[1], 10)
            .attr({"fill": "#fff", "stroke-width": 1, r : "9px"}))
            
    var pushToLine = function(part, line){
        set.push(r.text(n.point[0], n.point[1] + 6 + line * 12, part)
                .attr({"font-size":"12px"}));
    }
        var line = 1;
        var currentLine = ""
        $(n.label.split(" ")).each(function(i, v){
            if((currentLine + " " + v).length > wrapWidth){
                pushToLine(currentLine, line);
                ++ line;
                currentLine = "";
            }else{
                currentLine += " "
            }
            currentLine += v;
        });
        pushToLine(currentLine, line);
        //.push(r.text(n.point[0], n.point[1] + 20, n.label)
        //      .attr({"font-size":"20px"}));
    /* custom tooltip attached to the set */
    return set;
};


function drawGraph(data){
    var width = 800;
    var height = 600;
    g = new Graph();
    $.each(data["names"], function(key, value){
        g.addNode(key, {label: value, render: render});
    });
    $.each(data["links"], function(index, value){
        var id = value[0] + "_" + value[1];
        g.addEdge(value[0], value[1], {"id" : id});
        $(document).delegate("#" + id, "click", function(){
            var color;
            var index;
            var id = $(this)[0].id;
            if($(this).attr("stroke") == disabledColor){
                color = enabledColor;
                if(removeFromArray(id, toDisable) == undefined){
                    toEnable.push(id);
                }
            }else{
                color = disabledColor;
                if(removeFromArray(id, toEnable) == undefined){
                    toDisable.push(id);
                }
            }
            $(this).attr("stroke", color);
            genQueries();
        });
    });

    var layouter = new Graph.Layout.Spring(g);
    
    /* draw the graph using the RaphaelJS draw implementation */
    renderer = new Graph.Renderer.Raphael('canvas', g, width, height);
    $("#canvas path").attr("stroke-width", 3).css("cursor", "pointer");

    redraw = function() {
        layouter.layout();
        renderer.draw();
        
    };
    hide = function(id) {
        g.nodes[id].hide();
    };
    show = function(id) {
        g.nodes[id].show();
    };
}