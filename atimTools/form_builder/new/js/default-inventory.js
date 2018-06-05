var samples = {};
$$(function () {
    function initial() {
        
        $$("#tools-inventory .display-inline-block").css("display", "inline-block").removeClass("display-inline-block");
        $$("#tools-inventory .display-block").css("display", "block").removeClass("display-block");
        $$("#tools-inventory .display-none").hide().removeClass("display-none");
        
        $$("#tools-inventory #aliquot-sample").html("Keep the mouse pointer on top of a <b>Sample</b> item.");
        $$("#tools-inventory #inventory-aliases").html("Keep the mouse pointer on top of a <b>Sample</b> item.");
        
        initialClickEventsSpans();

        $$("#tools-inventory-copy-queries").click(function () {
            $$("#tools-inventory-out").select();
            document.execCommand("Copy");
        });

        preview("#sample", "#preview-sample");

        $$("#tools-inventory").tooltip({show: {delay: 1000}});

        $$("div.aliquot-display-hover").hover(function () {
            var $this = $$(this);
            timeHover = setTimeout(function () {
                showRealiquoting($this);
                showAliases($this);
            }, 300);
        }, function () {
            clearTimeout(timeHover);
        });

        $$("#tools-inventory #content").before('<a href="#" id="load-inventory" class="ui-state-default ui-corner-all button_link custom" name="custom autoBuild1"><span class="button_icon ui-icon ui-icon-refresh"></span><span>Refresh</span></a>');
        
        $$("#load-inventory").click(function () {
            $$("#tools-inventory #content #sample").html("").text("Loading...");
            $$("#tools-inventory #content #preview-sample").html("");
            $$("#tools-inventory #content #aliquot-sample").html("Keep the mouse pointer on top of a <b>Sample</b> item.");
            $$("#tools-inventory #inventory-aliases").html("Keep the mouse pointer on top of a <b>Sample</b> item.");
            $$("#tools-inventory #tools-inventory-out").val("");
            $$(this).remove();
            loadInventory();
        });
    }

    function showAliases($this){
        var sample = samples[$this.text()];
        $("#inventory-aliases").html("<div><b>Sample: </b>"+$this.text()+"</div><br>");
        $("#inventory-aliases").append("<div><b>Detail Table Name: </b>"+sample["detailTablename"]+"</div><br>");
        var detailFormAlias;
        if (typeof sample["detailFormAliasTemp"]!=='undefined'){
            detailFormAlias= (sample["detailFormAliasTemp"]!="")?sample["detailFormAliasTemp"].split(","):[];
        }else{
            detailFormAlias= (sample["detailFormAlias"]!="")?sample["detailFormAlias"].split(","):[];
        }     
        $("#inventory-aliases").append("<div><b>Detail Form Alias: </b></div><ul></ul>");
        for (var i=0; i<detailFormAlias.length; i++){
            $("#inventory-aliases ul").append("<li>");
            $("#inventory-aliases ul li:last").append("<div class='div-alias-name'>"+detailFormAlias[i]+"</div>");
            $("#inventory-aliases ul li:last").append('<span class="remove-alias ui-icon ui-icon-minusthick" style="display: inline-block;"></span>');
        }
        $("#inventory-aliases ul").append('<li><span class="add-alias ui-icon ui-icon-plusthick" style="display: inline-block;"></span></li>');
        
        $("#inventory-aliases ul .add-alias").off("click").on("click", function(){
            $input = $$('<li><input id="input-alias-form-detail"><span class="save-alias ui-icon ui-icon-disk" style="display: inline-block;"></span></li>');
            var source = $$( "#autoBuild1_alias" ).autocomplete( "option", "source" );
            $(this).closest("li").before($input);
            $(this).hide();
            $$("#input-alias-form-detail").autocomplete({
                autoFocus: true,
                source : source
            });
            $$("#input-alias-form-detail").focus();
            $(".save-alias").click(function(){
                var detailFormAlias = [];
                $("#inventory-aliases div.div-alias-name").each(function(){detailFormAlias.push($(this).text());});
                if ($$("#input-alias-form-detail").val()!=""){
                    detailFormAlias.push($$("#input-alias-form-detail").val());
                    var li="<li><div class='div-alias-name'>"+$$("#input-alias-form-detail").val()+"</div><span class=\"remove-alias ui-icon ui-icon-minusthick\" style=\"display: inline-block;\"></span></li>";
                    $(this).closest("ul").find("li:last").before(li);
                    $(this).closest("ul").find(".remove-alias").on("click", function(){
                        $(this).closest("li").remove();
                        var detailFormAlias = [];
                        $("#inventory-aliases div.div-alias-name").each(function(){detailFormAlias.push($(this).text());});
                        samples[$this.text()]["detailFormAliasTemp"] = detailFormAlias.join(",");
                    });
                }
                samples[$this.text()]["detailFormAliasTemp"] = detailFormAlias.join(",");
                $(this).closest("ul").find(".add-alias").show();
                $(this).closest("li").remove();
            });
            
        });
        
        $("#inventory-aliases .remove-alias").on("click", function(){
            $(this).closest("li").remove();
            var detailFormAlias = [];
            $("#inventory-aliases div.div-alias-name").each(function(){detailFormAlias.push($(this).text());});
            samples[$this.text()]["detailFormAliasTemp"] = detailFormAlias.join(",");
        });
    }
    
    function showRealiquoting($this){
        id = $this.siblings("input.check-box").attr("data-id");
        aliquot = $$("#aliquot").find("li.sample[data-id ='" + id + "']").eq(0).clone();
        $$("#aliquot-sample").html("");
        $$("#aliquot-sample").append(aliquot);
        show("#aliquot-sample");
    }

    function initialClickEventsSpans(){
        $$('#tools-inventory').find("span.minus, span.plus, span.add, span.trash, span.delete, span.undo").off("click");

        $$('#tools-inventory span.minus').click(function () {
            $$(this).siblings("ul").children("li").hide(200);
            $$(this).css("display", "none");
            $$(this).siblings(".plus").css("display", "inline-block");
        });

        $$('#tools-inventory span.plus').click(function () {
            $$(this).siblings("ul").children("li").show(200);
            $$(this).css("display", "none");
            $$(this).siblings(".minus").css("display", "inline-block");
        });

        $$('#tools-inventory span.add').click(function () {
            var $this = $$(this);
            $input = $("<input>").attr("id", "autoCompelete-samples");
            $this.after($input);
            $(this).siblings($input).focus();
            $this.closest("li").find("#autoCompelete-samples").on("focusout", function(){
                $this.closest("li").find("#autoCompelete-samples").autocomplete( "destroy" );
                $this.closest("li").find("#autoCompelete-samples").remove();
                $this.show();
            });
//            var source = Object.keys(samples);
            var source = [];
            for (var i in samples){
                if (samples[i]["listed"]){
                    source.push(i);
                }
            }
            var dives = $this.closest("li").children("ul").children("li").children("div");
            var divstext = [];
            $this.closest("li").children("ul").children("li").children("div").each(function(){divstext.push($(this).text());});            
            source = source.filter(function(item){
                return divstext.indexOf(item) ===-1;
            });
            $this.closest("li").find("#autoCompelete-samples").autocomplete({
                source: source, 
                autoFocus: true,
                select: function (event, ui) {
                    var $li = $(this).closest("li");
                    $li.children("ul").append(createLi({'li': $li, 'val':ui.item.value}));
                    $(this).blur();
                    
                    preview("#tools-inventory #sample", "#tools-inventory #preview-sample");
                }
            });
            $this.css("display", "none");
        });

        $$('#tools-inventory span.trash').click(function () {
            var trashButton = $$(this);
            var text = trashButton.closest("li").children("div").eq(0).text();
            var parentText = trashButton.closest("ul").closest("li").children("div").text();
            if (trashButton.closest("li").attr("data-row-id")=="-1"){
                trashButton.closest("li").hide(500, function (){$(this).remove();});
            }else{
                var addButton = '<span class="add ui-icon ui-icon-plusthick" title="For adding the ralation between \''+text+'\' and the parent \''+parentText+'\'"></span>';
                var deleteDialogue = 
                        "<div id = 'popup-confirm-dialog'>" +
                            "<p>The relation between: <i>'"+text+"'</i> and the parent: <i>'"+parentText+"'</i> will be <i><b>deleted permanently</b></i>.</p>"+
                            "<p>For redoing this changing before run the query to database, should click on <i><b>Refresh</b></i> button</p>"+
                            "<p>For redoing this changing after run the query to database, should click on " + addButton + " button</p><br>"+
                            "<p>Are you sure to delete?</p>"+
                        "</div>";

                $$(deleteDialogue).dialog({
                    closeOnEscape: true, 
                    modal: true,
                    width: '550px', 
                    title: "Delete a sample relation permanently",
                    buttons: [
                        {
                            text: "No",
                            click: function () {
                                $$(this).dialog("close");
                            }
                        }, {
                            text: "Yes",
                            click: function () {
                                $$(this).dialog("close");
                                dataId = trashButton.siblings("input[type='checkbox']").attr('data-id');
//                              trashButton.closest("li").hide(500, function (){$(this).remove();});
                                trashButton.closest("li").hide(500);
                                trashButton.closest("li").addClass("delete-for-ever");

                                preview("#sample", "#preview-sample");
                            }
                        }
                    ]
                });
            }
            
            
            preview("#tools-inventory #sample", "#tools-inventory #preview-sample");
        });
        
        $$('#tools-inventory span.delete').click(function () {
            deleteButton = $$(this);
            text = deleteButton.closest("li").children("div").eq(0).text();
            undoButton = '<span class="undo ui-icon ui-icon-arrowreturnthick-1-w" title="For readding the \''+text+'\' in all the samples."></span>';
            deleteDialogue = 
                    "<div>" +
                        "All the " + text + " will be deleted, but you can undo this action by clicking on " + undoButton + " Button.<br><br>"+
                        "Are you sure to delete?"+
                    "</div>";

            $$(deleteDialogue).dialog({
                closeOnEscape: true, 
                modal: true,
                title: "Delete a sample",
                buttons: [
                    {
                        text: "No",
                        click: function () {
                            $$(this).dialog("close");
                        }
                    },{
                        text: "Yes",
                        click: function () {
                            $$(this).dialog("close");
                            dataId = deleteButton.siblings("input[type='checkbox']").attr('data-id');
                            $$("input[data-id=" + dataId + "]").each(function () {
                                $this = $$(this);
                                $this.siblings("div").css("text-decoration", "line-through");
                                $this.siblings(".undo").css("display", "inline-block");
                                $this.siblings(".delete").css("display", "none");
                                $this.siblings(".plus").css("display", "none");
                                $this.siblings(".trash").css("display", "none");
                                $this.siblings(".add").css("display", "none");
                                $this.siblings(".minus").css("display", "none");
                                $this.siblings(".empty").css("display", "inline-block");
                                $this.siblings("ul").children("li").css("display", "none");
                            });
                            preview("#sample", "#preview-sample");

                        }
                    }
                ]
            });
        });

        $$('#tools-inventory span.undo').click(function () {
            dataId = $$(this).siblings("input[type='checkbox']").attr('data-id');
            $$("input[data-id=" + dataId + "]").each(function () {
                $this = $$(this);
                $this.siblings("div").css("text-decoration", "none");
                $this.siblings(".delete").css("display", "inline-block");
                $this.siblings(".undo").css("display", "none");
                if ($this.siblings("ul").children("li").length !== 0) {
                    $this.siblings("span.minus").css("display", "none");
                    $this.siblings("span.trash").css("display", "inline-block");
                    $this.siblings("span.plus").css("display", "inline-block");
                    $this.siblings("span.add").css("display", "inline-block");
                    $this.siblings("span.empty").css("display", "none");
                }
            });
            preview("#tools-inventory #sample", "#tools-inventory #preview-sample");
        });

        $$("#tools-inventory input.check-box").change(function () {
            $this = $$(this);
            if (this.checked) {
                if ($this.siblings("ul").children("li").length !== 0) {
                    $this.siblings("span.empty").css("display", "none");
                    $this.siblings("span.plus").css("display", "inline-block");
                }
            } else {
                if ($this.siblings("ul").children("li").length !== 0) {
                    $this.siblings("ul").children("li").css("display", "none");
                    $this.siblings("span.minus").css("display", "none");
                    $this.siblings("span.plus").css("display", "none");
                    $this.siblings("span.empty").css("display", "inline-block");
                }
            }
            preview("#sample", "#preview-sample");
        });

        $$("#tools-inventory #tools-inventory-create-query").click(createQuery);

        $$("#tools-inventory span.delete").each(function () {
            $this = $$(this);
            text = $this.siblings("div").text();
            $this.attr("title", "For deleting the \"" + text + "\" in all the samples (Can be retrievable).");
        });

        $$("#tools-inventory span.undo").each(function () {
            $this = $$(this);
            text = $this.siblings("div").text();
            $this.attr("title", "For readding the \"" + text + "\" in all the samples.");
        });

        $$("#tools-inventory span.plus").each(function () {
            $this = $$(this);
            $this.attr("title", "See more sub samples.");
        });
        
    }

    function createLi(e){
        $li= e.li;
        parentId = $li.attr("data-id");
        var val = e.val;

        var display = "list-item";
        if ($li.children("span.plus").is(":visible")){
            display="none";
        }        
        
        html = '<li data-id="' + samples[val]['id'] + '" class="no-bull-li display-none sample-li" data-row-id="-1" data-parent-id="' + parentId + '" style="display: ' + display + ';">' + "\n"+
                '<span class="minus ui-icon ui-icon-caret-1-s display-none"></span>' + "\n"+
                '<span class="plus ui-icon ui-icon-caret-1-e display-none" title="See more sub samples."></span>' + "\n"+
                '<span class="empty ui-icon ui-icon-blank display-inline-block">1</span>' + "\n"+
                '<input class="check-box" type="checkbox" checked data-id="' + samples[val]['id'] + '">' + "\n"+
                '<div class="display-inline-block aliquot-display-hover new-sample">' + val + '</div>' + "\n"+
//                '<span class="delete ui-icon ui-icon-closethick display-inline-block" title="For deleting the &quot;peritoneal wash supernatant&quot; in all the samples (Can be retrievable)."></span>' + "\n"+
                '<span class="undo ui-icon ui-icon-arrowreturnthick-1-w display-none" title="For readding the &quot;peritoneal wash supernatant&quot; in all the samples."></span>' + "\n"+
                '<span class="trash ui-icon ui-icon-trash display-inline-block"></span>' + "\n"+
                '<span class="add ui-icon ui-icon-plusthick display-inline-block"></span>' + "\n"+
                '</li>';
        
        if ($li.children('ul').length===1){
            $li.children('ul').append(html);
        }else if ($li.children('ul').length===0){
            $li.append("<ul>"+html+"<ul>");
            $li.children('span.plus').show();
            $li.children('span.empty').hide();
        }

        if ($li.children("span.plus").is(":visible")){
            $li.children("span.plus").trigger("click");
        }        

        initialClickEventsSpans();

    }

    function show(scope) {
        $scope = $$(scope);
        $scope.find(".sample").find("li").prepend("<input type='checkbox' class = 'check-box' checked>");
        $scope.find('.disable').children('input.check-box').prop('checked', false);
        $scope.find('.disable').removeClass('disable');

        $scope.find("li.aliquot>input.check-box").each(function () {
            $this = $$(this);
            if ($this.is(":not(:checked)")) {
                $this.siblings('ul').find("input.check-box").each(function () {
                    $$(this).prop('checked', false);
                    $$(this).prop('disabled', true);
                });
                id = $this.closest("li").attr("data-id");
                $this.closest("ul").find(".re-aliquot[data-child-id='" + id + "'] input.check-box").each(function () {
                    $$(this).prop('checked', false);
                    $$(this).prop('disabled', true);
                });
            }
        });
        $scope.find(".sample").find("li input.check-box").change(function () {
            $this = $$(this);
            div = $this.siblings("div");
            if (this.checked) {
                $this.siblings('ul').find("input.check-box").each(function () {
                    id = $$(this).closest("li").attr("data-child-id");
                    if ($scope.find("li.aliquot[data-id='" + id + "']>input.check-box").is(":checked")) {
                        $$(this).prop('disabled', false);
                    }
                });
                id = $this.closest("li").attr("data-id");
                $this.closest("ul").find(".re-aliquot[data-child-id='" + id + "'] input.check-box").each(function () {
                    if ($$(this).closest("li.aliquot").children("input.check-box").is(":checked")) {
                        $$(this).prop('disabled', false);
                    }
                });
            } else {
                if ($this.closest("li").hasClass("aliquot")) {
                    $this.siblings('ul').find("input.check-box").each(function () {
                        $$(this).prop('checked', false);
                        $$(this).prop('disabled', true);
                    });
                    id = $this.closest("li").attr("data-id");
                    $this.closest("ul").find(".re-aliquot[data-child-id='" + id + "'] input.check-box").each(function () {
                        $$(this).prop('checked', false);
                        $$(this).prop('disabled', true);
                    });
                }
            }
            id = $scope.find("li.sample").attr('data-id');
            $aliquot = $$("#aliquot").find("li.sample[data-id ='" + id + "']").eq(0);
            $scope.find("li.sample input.check-box").each(function (index) {
                $this = $$(this);
                $that = $aliquot.find("li").eq(index);
                if ($this.is(":checked")) {
                    $that.removeClass("disable");
                } else {
                    $that.addClass("disable");
                }
            });
        });
    }

    function preview(scope, scopePreview) {
            scope = $$(scope);
            scopePreview = $$(scopePreview);
            scopePreview.html("<div></div>");
            scopePreview.children("div").eq(0).html(scope.children("ul").clone());
            scopePreview.find("input[type=checkbox]:not(:checked)").closest("li").remove();
            scopePreview.find("input[type=checkbox]:not(:checked)").closest("li").remove();
            scopePreview.find(".delete, .undo, input[type=checkbox], .plus, .minus, .empty, .add, .trash").remove();
            scopePreview.find("li.delete-for-ever").remove();
            scopePreview.find("input").remove();
//            scopePreview.find("div[style*='line-through']").closest("li").remove();
            scopePreview.find("li").css("display", "list-item");
            scopePreview.find("li").removeClass('display-none');
            scopePreview.children("div").eq(0).find("div").each(function () {
                $this = $$(this);
                $this.replaceWith($this.text());
            });
            scopePreview.children("div").eq(0).jstree();
        }

    function sqlHelper(scope) {
            $$("div#sql-helper").remove();
            scopePreview = $$("<div class = 'display-none' id='sql-helper'></div>");
            $$("body").append(scopePreview);

            scope = $$(scope);
            scopePreview.html(scope.children("ul").clone());
            scopePreview.find("input[type=checkbox]:not(:checked)").closest("li").remove()
            scopePreview.find(".delete, .undo, input[type=checkbox], .plus, .minus, .empty").remove();
            scopePreview.find("div[style*='line-through']").closest("li").remove();
            scopePreview.find("li").css("display", "list-item");
            scopePreview.find("li").removeClass('display-none');
            scopePreview.find("div").each(function () {
                $this = $$(this);
                $this.replaceWith($this.text());
            });
        }

    function createQuery() {
            var sample = new Array();
            var aliquot = new Array();
            var realiquot = new Array();
            var sampleControl = new Array();

            $$("#sample li").each(function () {
                $this = $$(this);

            if ($this.hasClass("delete-for-ever")){
                checked = 2;
            }else{
                checked = ($this.children("input[type='checkbox']").is(':checked') && $this.children("div[style*='line-through']").length === 0)?1:0;
            }

            id = $this.attr('data-row-id');
            parentId = $this.attr('data-parent-id');
            derivatedId = $this.attr('data-id');

            sample.push(
                    '{' +
                    '"id": ' + id +
                    ', "parent_id": ' + parentId +
                    ', "children_id": ' + derivatedId +
                    ', "flag_active": ' + checked +
                    '}'
                    );
            });

            $$("#aliquot li.aliquot").each(function () {
                $this = $$(this);
                checked = !$this.hasClass("disable");
                id = $this.attr("data-id");
                aliquot.push(
                        '{' +
                        '"id": ' + id +
                        ', "flag_active": ' + checked +
                        '}'
                        );
            });

            $$("#aliquot li.re-aliquot").each(function () {
                $this = $$(this);
                checked = !$this.hasClass("disable");
                id = $this.attr("data-id");
                realiquot.push(
                        '{' +
                        '"id": ' + id +
                        ', "flag_active": ' + checked +
                        '}'
                        );
            });
            
            for (var key in samples){
                var value = samples[key];
                if (typeof value["detailFormAliasTemp"] !=='undefined' && value["detailFormAliasTemp"]!=value["detailFormAlias"]){
                    sampleControl.push(                        
                        '{' +
                        '"id": ' + value["id"] +
                        ', "detail_form_alias": "' + value["detailFormAliasTemp"]+'"' +
                        '}'
                    );
                }
            }

            sample = sample.filter(function (item, pos) {
                return sample.indexOf(item) === pos;
            });
            json = '{' +
                    '"sample": [' + sample.join(', ') + '],' +
                    '"aliquot": [' + aliquot.join(', ') + '],' +
                    '"realiquot": [' + realiquot.join(', ') + '],' +
                    '"sampleControls": [' + sampleControl.join(', ') + ']' +
                    '}';

            $$.post("new/sqlGenerator-inventory.php", "json=" + json, function (data) {
                $$("#tools-inventory #tools-inventory-out").val(data);
                if (data !== "") {
                    $$("#tools-inventory #copy-queries").removeAttr("disabled");
                } else {
                    $$("#tools-inventory #copy-queries").attr("disabled", "disabled");
                }
            });


        }

    function initialAliquots(scope) {
        $scope = $$(scope);
        $scope.find('.no-display').remove();
    }

    function loadInventory() {
        $$.get('new/samples.php', {data: 'samplelist'}, function (data) {
            samples = $$.parseJSON(data);
            $$.get('new/samples.php', {data: 'sample'}, function (data) {
                $$("#sample").html(data);
                initial();
            });
        });
        $$.get('new/samples.php', {data: 'aliquot'}, function (data) {
            $$("#aliquot").html(data);
            initialAliquots("#aliquot");
        });
    }

    $$("document").ready(loadInventory);
});