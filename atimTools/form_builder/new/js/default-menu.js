function initial() {


    l = $$('#tools-menu #left-menu, #right-menu');
    l.find('ul').css("list-style-type", "none");
//    l.find('ul li:has(ul)').prepend("<span class = 'minus'>-</span><span class = 'plus' style = 'display: none'>+</span>");
    l.find('ul li:has(ul)').prepend("<span class = 'minus ui-icon ui-icon-minusthick'></span><span class = 'plus ui-icon ui-icon-plusthick' style = 'display: none'></span>");
    l.find('ul li:not(:has(ul))').prepend("<span class = 'empty'></span>");
    $$('#tools-menu span.minus').click(function () {
        $$(this).closest("li").children('ul').toggle(200);
        $$(this).hide();
        $$(this).siblings(".plus").show();
    });
    $$('#tools-menu span.plus').click(function () {
        $$(this).closest("li").children('ul').toggle(200);
        $$(this).hide();
        $$(this).siblings(".minus").show();
    });


    $$("#tools-menu .draggable").sortable();
    $$("#tools-menu .draggable").sortable({cancel: ".unchangable"});
    $$("#tools-menu .draggable").disableSelection();

    $$("#tools-menu li.changable div, li.changable-parent div").click(function () {
        if ($$(this).parent(".unchangable").length === 0) {
            $$(this).parent().toggleClass("disabled");
        }
    });

    $$("#tools-menu .unchangable div").unbind("click");

    $$("#tools-menu #right-menu .content").toggle();
    $$("#tools-menu #left-menu .content").toggle();
    
    $$("#tools-menu .minus").each(function(){
            $currentMinus = $(this);
            if ($currentMinus.css("display")!="none"){
                    $currentMinus.trigger("click");
        }
    });    
}

function createQuery() {
    var json = new Array();
    $$("#tools-menu #menus li").each(function () {
        var displayOrder = $$(this).closest("ul").children('li').index(this);
        json.push(
                '{' +
                '"id" : "' + $$(this).attr("id") +
                '", "flag_active" : "' + !$$(this).hasClass("disabled") +
                '", "display_order" : "' + displayOrder +
                '" }'
                );
    });
    json = '[' + json.join(', ') + ']';
    $$.post("new/sqlGenerator-menu.php", "json=" + json, function (data) {
        $$("#tools-menu #out").val(data);
        if (data !== "") {
            $$("#copy-queries").removeAttr("disabled");
        } else {
            $$("#copy-queries").attr("disabled", "disabled");
        }
    });

}

function copyText() {
    $$("#tools-menu #out").select();
    document.execCommand("Copy");
}

$$(document).ready(function () {
    getMenu();
});

function getMenu() {
    var ready = 0;
    $$.get('new/menu.php', {menu: 'left'}, function (data) {
        $$("#tools-menu #left-menu .content").hide();
        $$("#tools-menu #left-menu .content").html(data);
        if (++ready === 2) {
            initial();
        }
    });

    $$.get('new/menu.php', {menu: 'right'}, function (data) {
        $$("#tools-menu #right-menu .content").hide();
        $$("#tools-menu #right-menu .content").html(data);
        if (++ready === 2) {
            initial();
        }
    });

}