function initial() {
    
    l = $('#left-menu, #right-menu');
    l.find('ul').css("list-style-type", "none");
    l.find('ul li:has(ul)').prepend("<span class = 'minus'>-</span><span class = 'plus' style = 'display: none'>+</span>");
    l.find('ul li:not(:has(ul))').prepend("<span class = 'empty'></span>");
    $('span.minus').click(function(){
            li = $(this).closest("li");
            div = li.children('div').eq(0);
            $(this).closest("li").children().toggle();
            div.css("display", "inline-block");
    });
    $('span.plus').click(function(){
            li = $(this).closest("li");
            div = li.children('div').eq(0);
            $(this).closest("li").children().toggle();
            div.css("display", "inline-block");
    });    
    
    
    $(".draggable").sortable();
    $(".draggable").sortable({cancel: ".unchangable"});
    $(".draggable").disableSelection();

    $("li.changable div, li.changable-parent div").click(function () {
        if ($(this).parent(".unchangable").length === 0) {
            $(this).parent().toggleClass("disabled");
        }
    });

    $(".unchangable div").unbind("click");
    
    $("#right-menu .content").toggle();
    $("#left-menu .content").toggle();
}

function createQuery() {
    var json = new Array();
    $("#menus li").each(function () {
        var displayOrder = $(this).closest("ul").children('li').index(this);
        json.push(
                '{' +
                '"id" : "' + $(this).attr("id") +
                '", "flag_active" : "' + $(this).hasClass("disabled") +
                '", "display_order" : "' + displayOrder +
                '" }'
                );
    });
    json = '[' + json.join(', ') + ']';
    $.post("sqlGenerator.php", "json=" + json, function (data) {
        $("#out").val(data);
        if (data !== "") {
            $("#copy-queries").removeAttr("disabled");
        } else {
            $("#copy-queries").attr("disabled", "disabled");
        }
    });

}

function copyText() {
    $("#out").select();
    document.execCommand("Copy");
}