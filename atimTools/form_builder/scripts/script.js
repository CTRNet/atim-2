var structureTypes = '[{"text" : "autocomplete"},{"text" : "checkbox"},{"text" : "date"},{"text" : "datetime"},{"text" : "file"},{"text" : "hidden"},{"text" : "input"},{"text" : "input-readonly"},{"text" : "number"},{"text" : "password"},{"text" : "radio"},{"text" : "radiolist"},{"text" : "select"},{"text" : "textarea"},{"text" : "time"},{"text":"integer"},{"text":"integer_positive"},{"text":"float"},{"text":"float_positive"},{"text":"y_n_u"},{"text":"yes_no"}]';
var tmpLine = "";
var lineWithoutChanges = "";
var deleteLine = '<a href="#no" class="deleteLine">(x)</a>';

$(function () {


    $("#db_select_div_target").append($("#db_select_div"));

    $("#piton5").scroll(function () {
        $("td.scrollingButtons:last").css("padding-left", Math.max($(this).scrollLeft() - 110, 0));
        $("#autoBuild2 th:nth-child(4), #autoBuild2 td:nth-child(4)").css("left", $(this).scrollLeft());
    });
    $("#dbSelect").change(function () {
        document.location = "?db=" + $(this).children(":selected").html();
    });

    $("#runSql").click(function () {
        $.ajax({url: "sqlRunner.php", data: "sql=" + $("textarea").val(), type: "POST", success: function (data) {
                alert(data);
            }});
        return false;
    });

    $("#clearSql").click(function () {
        $("textarea").val("");
        return false;
    });

    //Second third of the screen function - displays a structure in the right pane
    $(".structLink").click(function () {
        var oldId = $(this).attr('href').substring(1);
        var val = 'json={"val" : "' + oldId + '", "type" : "structures"}';
        $.ajax({url: "loader.php", data: val, success: function (data) {
                $("#structureResult").html(data);
                setStructureGetField();
            }});
        return false;
    });

    //Second third of the screen function - displays a table in the right pane
    $(".tableLink").click(function () {
        var tableName = $(this).attr('href').substring(1);
        var val = 'json={"val" : "' + tableName + '", "type" : "tables"}';
        $.ajax({url: "loader.php", data: val, success: function (data) {
                $("#tableResult").html(data);
                setTableGetField();
            }});
        return false;
    });

    //Second third of the screen function - displays a model's fields in the right pane
    $(".fieldLink").click(function () {
        var modelName = $(this).attr('href').substring(1);
        var val = 'json={"val" : "' + modelName + '", "type" : "fields"}';
        $.ajax({url: "loader.php", data: val, success: function (data) {
                $("#fieldResult").html(data);
            }});
        return false;
    });

    //Second third of the screen function - displays a value domain in the right pane
    $(".vDomainLink").click(function () {
        var domainName = $(this).attr('href').substring(1);
        var val = 'json={"val" : "' + domainName + '", "type" : "value_domains"}';
        $.ajax({url: "loader.php", data: val, success: function (data) {
                $("#valueDomainResult").html(data);
            }});
        return false;
    });

    //Sets the selected value domain name in the structure value domain field of the structure building pane
    $(".vDomainLinkAdd").click(function () {
        var domainName = $(this).attr('href').substring(1);
        var val = 'json={"val" : "' + domainName + '", "type" : "value_domains"}';
        var query = "(SELECT id FROM structure_value_domains WHERE domain_name='" + domainName + "')";
        $("#structure_fields_structure_value_domain").val(query);
        $("#autoBuild2_structure_value_domain").val(domainName);
        $.ajax({url: "loader.php", data: val, success: function (data) {
                $("#valueDomainResult").html(data);
            }});
        return false;
    });

    //builds the input fields related to an "insert" table
    $(".insert").each(function () {
        var prefix = $(this).children("thead").attr("class");
        if (prefix.lastIndexOf(" ") > 0) {
            prefix = prefix.substr(prefix.lastIndexOf(" ") + 1);
        }
        $(this).addClass("ui-widget ui-widget-content");
        $(this).children("thead").children("tr").addClass("ui-widget-header");
        var result = "<tr>";
        $(this).children("thead").children("tr").children("th").each(function () {
            var css = "";
            var readonly = "";
            var autoincrement = "";
            if ($(this).hasClass("gen")) {
                css += " gen ";
            }
            if ($(this).hasClass("readonly")) {
                readonly = ' readonly="readonly" ';
                css += " readonly ";
            }
            if ($(this).hasClass("autoincrement")) {
                autoincrement = '<a href="javascript:void(0)" class="autoincrementButton">[+]</a>';
            }
            if ($(this).hasClass("notEmpty")) {
                css += " notEmpty ";
            }
            if ($(this).hasClass("clear")) {
                css += " clear ";
            }
            if ($(this).hasClass("autoBuildIncrement")) {
                css += " autoBuildIncrement ";
            }
            var inputTypeNValueNSize = $(this).hasClass("checkbox") ? 'type="checkbox" value="1" checked' : 'type="text" size="13"';
            result += '<td><input id="' + prefix + "_" + $(this).html() + '" ' + inputTypeNValueNSize + ' class="' + css + '" ' + readonly + '/>' + autoincrement + '</td>';
        });
        result += "</tr>";
        $(this).children("tfoot").html(result + '<tr><td colspan="30" class="scrollingButtons"><a href="javascript:void(0)" class="add ui-state-default ui-corner-all button_link ' + $(this).children("thead").attr("class") + '" name="' + $(this).children("thead").attr("class") + '"><span class="button_icon ui-icon ui-icon-plus"></span><span>Add</span></a></td></tr>');
    });

    //update structure value domain functions
    $("#piton4 table:first a").each(function () {
        $($(this).find("span")[0]).removeClass("ui-icon-plus").addClass("ui-icon-arrowthickstop-1-s");
        $($(this).find("span")[1]).html("Load value domain");
        $("#piton4 table:eq(1)").find("input:first, input:last").prop("type", "hidden");
        $($("#piton4 table:eq(1) tfoot tr:first input")[4]).prop("checked", true);
        $("#piton4 table:eq(1) tfoot tr:nth-child(2) td").append(' <a href="#no" id="addFromPopup" class="ui-state-default ui-corner-all button_link"><span class="button_icon ui-icon ui-icon-newwin"></span><span>Add from text area</span></a>');
        $("#addFromPopup").click(function () {
            $("#addFromTextAreaDialog").dialog('open').find("textarea").focus();
        });
        $(this).click(function () {
            //load value domain
            var command = {type: "value_domains", as: "json", val: $("#structure_value_domains_domain_name").val()};
            var $this=$(this);
            $this.siblings(".fix-variable").remove();
            $.post("loader.php", command, function (data) {
                data = $.parseJSON(data);
                $table = $(".select-value-domain1").eq(3);
                var obj = data.reduce(function(acc, cur, i) {
                  acc[cur.language_alias] = {fr: cur.fr, en: cur.en};
                  return acc;
                }, {});
                $table.data('translate', obj);
                
                if (data.length == 0) {
                    $("#noDataValueDomainDialog").dialog('open');
                } else {
                    $("#structure_value_domains_override").val(data[0].override);
                    $("#structure_value_domains_category").val(data[0].category);
                    $("#structure_value_domains_source").val(data[0].source);
                    var html = "";
                    var mTd = '<td class="clickable editable">';
                    for (var i = 0; i < data.length; ++i) {
                        var line = data[i];
                        line.flag_active = '<input type="checkbox" ' + (line.flag_active == 0 ? "" : 'checked="checked"') + '/>';
                        html += "<tr><td>" + deleteLine + "</td>" + mTd + line.value + "</td>" + mTd + line.language_alias + "</td>" + mTd + line.display_order + "</td><td>" + line.flag_active + "</td><td>" + line.structure_permissible_value_id + "</td></tr>";
                    }
                    $("#piton4 table:eq(1) tbody").html(html);
                    
                    if ($this.siblings(".fix-variable").length === 0) {
                        $this.after('<a href="javascript:void(0)" class="fix-variable ui-state-default ui-corner-all button_link structure_value_domains" name="fix-variable"><span>Fix --> Variable</span></a>');
                        $(".fix-variable").on("click", function () {
                            var domainValues=[];
                            domainName = $("#structure_value_domains_domain_name").val();
                            if (domainName == "") {
                                alert("Should not be empty");
                            } else {
                                domainValues['domainName'] = domainName;
                                domainValues['override'] = $("#structure_value_domains_override").val();
                                domainValues['category'] = $("#structure_value_domains_category").val();
                                $table = $(".select-value-domain1").eq(3);
                                domainValues['values'] = [];
                                
                                var $tr, tds, en, fr, languageAlias;
                                var i18n = $table.data('translate');
                                $table.find("tbody").find("tr").each(function () {
                                    $tr = $(this);
                                    tds = $tr.find("td");
                                    languageAlias = $(tds[2]).text();
                                    en = (typeof i18n[languageAlias]!=='undefined')?i18n[languageAlias]['en']:"";
                                    fr = (typeof i18n[languageAlias]!=='undefined')?i18n[languageAlias]['fr']:"";
                                    domainValues['values'].push({
                                        value: $(tds[1]).text(),
                                        languageAlias: $(tds[2]).text(),
                                        displayOrder: $(tds[3]).text(),
                                        flagActive: $(tds[4]).find("input[type='checkbox']").prop('checked'),
                                        structurePossibleValueId: $(tds[5]).text(),
                                        en: en, 
                                        fr: fr
                                    });
                                });
                                $$( "#select-value-domain" ).val(2).selectmenu("refresh").trigger("selectmenuchange");
                                fixToVariable(domainValues);
                            }
                        });
                    }
                }
            });
        });
        
        $("#piton4 table:eq(1) a.add").click(function () {
            var html = "<tr><td>" + deleteLine + "</td>";
            $("#piton4 table:eq(1) tfoot tr:first input").each(function () {
                if ($(this).attr("type") == "checkbox") {
                    html += '<td><input type="checkbox" ' + ($(this).prop("checked") ? 'checked="checked"' : "") + "/></td>";
                } else if ($(this).attr("type") != "hidden") {
                    html += '<td class="clickable editable">' + $(this).val() + "</td>";
                }
            });
            html += "<td></td></tr>";
            $("#piton4 table:eq(1) tbody").append(html);
            
            return false;
        });

        $("#clearAutoBuildTableValueDomain").click(function () {
            $("#piton4 table:eq(1) tbody").html("");
        });
    });

    $("#piton4 table:eq(2) a").each(function () {
        $($(this).find("span")[0]).removeClass("ui-icon-plus").addClass("ui-icon-arrowthickstop-1-s");
        $($(this).find("span")[1]).html("Load value domain");
        $("#piton4 table:eq(3)").find("input:first, input:last").prop("type", "hidden");
        $(this).click(function () {
            //load value domain
            var command = {type: "value_domains_variable", as: "json", val: $("#structure_value_domains_variable_domain_name").val()};
            $.post("loader.php", command, function (data) {
                data = $.parseJSON(data);
                if (data.length == 0) {
                    $("#noDataValueDomainDialog").dialog('open');
                } else {
                    $("#structure_value_domains_variable_name").val(data[0].name);
                    $("#structure_value_domains_variable_category").val(data[0].category);
                    $("#structure_value_domains_variable_override").val(data[0].override);
                    $("#structure_value_domains_variable_values_max_length").val(data[0].values_max_length);
                    $("#generateSQLValueDomain").attr("data-control_id", data[0].control_id);
                    var html = "";
                    var mTd = '<td class="clickable editable">';
                    for (var i = 0; i < data.length; ++i) {
                        var line = data[i];
                        line.use_as_input = '<input type="checkbox" ' + (line.use_as_input == 0 ? "" : 'checked="checked"') + '/>';
                        html += "<tr><td>" + deleteLine + "</td>" + mTd + line.value + "</td>" + mTd + line.en + "</td>" + mTd + line.fr + "</td>" + mTd + line.display_order + "</td><td>" + line.use_as_input + "</td><td>" + line.id + "</td></tr>";
                    }
                    $("#piton4 table:eq(3) tbody").html(html);
                }
            });
        });
        $("#piton4 table:eq(3) a.add").click(function () {
            var html = "<tr><td>" + deleteLine + "</td>";
            $("#piton4 table:eq(3) tfoot tr:first input").each(function () {
                if ($(this).attr("type") == "checkbox") {
                    html += '<td><input type="checkbox" ' + ($(this).prop("checked") ? 'checked="checked"' : "") + "/></td>";
                } else if ($(this).attr("type") != "hidden") {
                    html += '<td class="clickable editable">' + $(this).val() + "</td>";

                }
            });
            html += "<td></td></tr>";
            $("#piton4 table:eq(3) tbody").append(html);
            return false;
        });

        $("#clearAutoBuildTableValueDomain").click(function () {
            $("#piton4 table:eq(3) tbody").html("");
        });
    });

    $("#piton4 table:eq(4) a").each(function () {
        $($(this).find("span")[0]).removeClass("ui-icon-plus").addClass("ui-icon-arrowthickstop-1-s");
        $($(this).find("span")[1]).html("Load value domain");
        $(this).click(function () {
            //load value domain
            var command = {type: "value_domains_function", as: "json", val: $("#structure_value_domains_function_domain_name").val()};
            $.post("loader.php", command, function (data) {
                data = $.parseJSON(data);
                if (data.length == 0) {
                    $("#noDataValueDomainDialog").dialog('open');
                } else {
                    $("#structure_value_domains_function_override").val(data[0].override);
                    $("#structure_value_domains_function_category").val(data[0].category);
                    $("#structure_value_domains_function_source").val(data[0].source);
                    var source = data[0].source.replace(".", "::");
                    var plugin = source.split("::")[0];
                    var model = source.split("::")[1];
                    var func = source.split("::")[2];
                    $("#structure_value_domains_function_plugin").val(plugin);
                    $("#structure_value_domains_function_model").val(model);
                    $("#structure_value_domains_function_function").val(func);
                }
            });
        });
    });

    $("#generateSQLValueDomain").click(function () {
        selectedMenu = $("#select-value-domain").val();
        $("#resultZone").val("");
        if (selectedMenu == "1") {
            if ($("#structure_value_domains_domain_name").val().length == 0) {
                flashColor($("#structure_value_domains_domain_name"), "#f00");
            } else {
                var toSend = new Object();
                toSend.domain_name = $("#structure_value_domains_domain_name").val();
                toSend.override = $("#structure_value_domains_override").val();
                toSend.category = $("#structure_value_domains_category").val();
                toSend.source = "";
                toSend.rows = new Array();
                //as of jQuery 1.7.1, if the "tr" part is within the original call, some browsers return nothing
                $("#piton4 table:eq(1) tbody").find("tr").each(function () {
                    var tds = $(this).find("td");
                    var currentRow = new Object();
                    currentRow.value = $(tds[1]).html();
                    currentRow.language_alias = $(tds[2]).html();
                    currentRow.display_order = $(tds[3]).html();
                    currentRow.flag_active = $(tds[4]).find("input").prop("checked") ? 1 : 0;
                    currentRow.id = $(tds[5]).html();
                    toSend.rows.push(currentRow);
                });
                $.post("sqlGeneratorValueDomain.php", toSend, function (data) {
                    $("#resultZone").val($("#resultZone").val() + data + "\n");
                });
            }
        } else if (selectedMenu == "2") {
            if ($("#structure_value_domains_variable_domain_name").val().length == 0) {
                flashColor($("#structure_value_domains_variable_domain_name"), "#f00");
            }else if ($("#structure_value_domains_variable_name").val()==""){
                flashColor($("#structure_value_domains_variable_name"), "#f00");
            }else {
                var toSend = new Object();
                toSend.domain_name = $("#structure_value_domains_variable_domain_name").val();
                toSend.name = $("#structure_value_domains_variable_name").val();
                toSend.category = $("#structure_value_domains_variable_category").val();
                toSend.flag_active = $("#structure_value_domains_variable_flag_active").val();
                toSend.values_max_length = $("#structure_value_domains_variable_values_max_length").val();
                toSend.control_id = $("#generateSQLValueDomain").attr("data-control_id");
                toSend.rows = new Array();
                $("#piton4 table:eq(3) tbody").find("tr").each(function () {
                    var tds = $(this).find("td");
                    var currentRow = new Object();
                    currentRow.control_id = $("#generateSQLValueDomain").attr("data-control_id");
                    currentRow.value = $(tds[1]).html();
                    currentRow.en = $(tds[2]).html();
                    currentRow.fr = $(tds[3]).html();
                    currentRow.display_order = $(tds[4]).html();
                    currentRow.use_as_input = $(tds[5]).find("input").prop("checked") ? 1 : 0;
                    currentRow.id = $(tds[6]).html();
                    toSend.rows.push(currentRow);
                });
                $.post("sqlGeneratorValueDomainVariable.php", toSend, function (data) {
                    $("#resultZone").val($("#resultZone").val() + data + "\n");
                });

            }
        } else if (selectedMenu == "3") {
            if ($("#structure_value_domains_function_domain_name").val().length == 0) {
                flashColor($("#structure_value_domains_function_domain_name"), "#f00");
            } else {
                var toSend = new Object();
                toSend.domain_name = $("#structure_value_domains_function_domain_name").val();
                toSend.override = $("#structure_value_domains_function_override").val();
                toSend.category = $("#structure_value_domains_function_category").val();
                toSend.source = $("#structure_value_domains_function_plugin").val() + "." + $("#structure_value_domains_function_model").val() + "::" + $("#structure_value_domains_function_function").val();
                $.post("sqlGeneratorValueDomainFunction.php", toSend, function (data) {
                    $("#resultZone").val($("#resultZone").val() + data + "\n");
                });

            }
        }
        return false;
    });


    //Structure build - update display
    $(".add.autoBuild2 span").html("Add row");
    $(".add.autoBuild2").click(function () {
        addLine();
        return false;
    });
    $(".add.autoBuild2").parent().append(
            '&nbsp;<a href="javascript:void(0)" class="ui-state-default ui-corner-all button_link ignoreButton" style="display: none;"><span class="button_icon ui-icon ui-icon-arrowreturn-1-w"></span><span>Ignore changes</span></a>&nbsp;'
            + '<a href="javascript:void(0)" class="ui-state-default ui-corner-all button_link deleteButton" style="display: none;"><span class="button_icon ui-icon ui-icon-close"></span><span>Delete</span></a>'
            );

    $("a.autoBuild1").each(function () {
        $(this).children("span:nth-child(2)").html("Load structure");
        $(this).children("span:nth-child(1)").removeClass("ui-icon-plus");
        $(this).children("span:nth-child(1)").addClass("ui-icon-arrowthickstop-1-s");
        $(this).click(function () {
            if ($("#autoBuild1_alias").val().length == 0) {
                flashColor($("#autoBuild1_alias"), "#f00");
            } else {
                $("#confirmDialog").dialog('open');
            }
            return false;
        });
    });

    //Dialog when trying to load a structure
    $("#confirmDialog").dialog({
        resizable: false,
        height: 170,
        width: 400,
        modal: true,
        autoOpen: false,
        buttons: {
            'No': function () {
                $(this).dialog('close');
            },
            'Yes': function () {
                $(this).dialog('close');
                var val = 'json={"val" : "' + $("#autoBuild1_alias").val() + '", "type" : "autoBuildData"}';
                $.ajax({url: "autoBuildLoader.php", data: val, success: function (data) {
                        if (data.length > 0) {
                            if (data.indexOf("<tr>") > 10) {
                                $("#duplicateFieldsMsg").html(data.substr(0, data.indexOf("<tr>")));
                                $("#duplicateFieldsDialog").dialog('open');
                                data = data.substr(data.indexOf("<tr>"));
                            }
                            $("#autoBuild2").children("tbody").children("tr").remove();
                            $("#autoBuild2").children("tbody").append(data).find("td:nth-child(1)").prepend('<a href="#no" class="deleteLine">(x)</a> <a href="javascript:void(0)" class="copyLine">(+)</a> ');
                            $("#autoBuild2 tbody td:first-child").addClass("first_td");
                            $("#autoBuild2 tbody td:not(.first_td)").addClass("clickable").addClass("editable");
                            $("#autoBuild2").children("tbody").find("input[type=checkbox]").click(function (e) {
                                e.stopPropagation();
                            });
                            $("#autoBuild2").trigger('update');
                            calculateAutoBuild2LeftMargin();
                        } else {
                            $("#noDataDialog").dialog('open');
                        }
                    }});
            }
        }
    });

    //dialog when the load structure button call returns nothing
    $("#noDataDialog").dialog({
        resizable: false,
        height: 170,
        width: 400,
        modal: true,
        autoOpen: false,
        buttons: {
            'Close': function () {
                $(this).dialog('close');
            }
        }
    });

    //dialog when the load value domain button call returns nothing
    $("#noDataValueDomainDialog").dialog({
        resizable: false,
        height: 170,
        width: 400,
        modal: true,
        autoOpen: false,
        buttons: {
            'Close': function () {
                $(this).dialog('close');
            }
        }
    });

    //dialog when 2 identical fields (same structure_field_id) are part of the same structure
    $("#duplicateFieldsDialog").dialog({
        resizable: false,
        height: 170,
        width: 400,
        modal: true,
        autoOpen: false,
        buttons: {
            'Close': function () {
                $(this).dialog('close');
            }
        }
    });

    //dialog for structure value domain add from textarea
    $("#addFromTextAreaDialog").dialog({
        resizable: true,
        height: 359,
        width: 600,
        modal: true,
        autoOpen: false,
        buttons: {
            'Close': function () {
                $(this).dialog('close');
            }, 'Add': function () {
                var txt = $.trim($("#addFromTextAreaDialog textarea").val()).split("\n");
                if (txt.length > 0) {
                    var single = txt[0].indexOf("\t") == -1;
                    $("#struct_val_domain_flag_active").prop("checked", true);
                    if (single) {
                        for (var i = 0; i < txt.length; ++i) {
                            $("#struct_val_domain_value").val(txt[i]);
                            $("#struct_val_domain_language_alias").val(txt[i]);
                            $("#struct_val_domain_display_order").val(0);
                            $("#struct_val_domain_flag_active").prop("checked", true);
                            $("#piton4 table:nth-child(1) a.add").click();
                        }
                    } else {
                        for (var i = 0; i < txt.length; ++i) {
                            var part = txt[i].split("\t");
                            var err = "";
                            if (part.length == 3) {
                                $("#struct_val_domain_value").val(part[0]);
                                $("#struct_val_domain_language_alias").val(part[1]);
                                $("#struct_val_domain_display_order").val(part[2]);
                                $("#piton4 table:nth-child(4) a.add").click();
                            } else {
                                err += txt[i] + "\n";
                            }
                            if (err.length > 0) {
                                allert("The textarea contained some invalid rows\n\n" + err);
                            }
                        }
                    }
                }
                $(this).dialog('close');
            }
        }
    });

    //table sorter applied to auto build
    $("#autoBuild2").tablesorter();

    $(".ignoreButton").click(function () {
        ignoreChanges();
        return false;
    });

    $(".deleteButton").click(function () {
        deleteRow();
        return false;
    });

    $("#permissibleValuesCtrl").click(function () {
        var val = 'json={"val" : "", "type" : "structure_permissible_values"}';
        $.ajax({url: "loader.php", data: val, success: function (data) {
                $("#result").html(data);
            }});
        return false;
    });

    $(".add.structures").parent().parent().append("<td><a href='#' id='copy_structure' class='ui-state-default ui-corner-all button_link '><span class='button_icon ui-icon ui-icon-copy'></span>Copy structure with old_id</a><input id='structures_copy_old_id'/></td>");
    $("#copy_structure").click(function () {
        $(".add.structures").click();
        $("textarea").val($("textarea").val()
                + "INSERT INTO structure_formats (`old_id`, `structure_id`, `structure_old_id`, `structure_field_id`, `structure_field_old_id`, `display_column`, `display_order`, `language_heading`, `flag_override_label`, `language_label`, `flag_override_tag`, `language_tag`, `flag_override_help`, `language_help`, `flag_override_type`, `type`, `flag_override_setting`, `setting`, `flag_override_default`, `default`, `flag_add`, `flag_add_readonly`, `flag_edit`, `flag_edit_readonly`, `flag_search`, `flag_search_readonly`, `flag_datagrid`, `flag_datagrid_readonly`, `flag_index`, `flag_detail`) "
                + "(SELECT CONCAT('" + $("#structures_old_id").val() + "_', `structure_field_old_id`), (SELECT id FROM structures WHERE old_id='" + $("#structures_old_id").val() + "'), '" + $("#structures_old_id").val() + "', `structure_field_id`, `structure_field_old_id`, `display_column`, `display_order`, `language_heading`, `flag_override_label`, `language_label`, `flag_override_tag`, `language_tag`, `flag_override_help`, `language_help`, `flag_override_type`, `type`, `flag_override_setting`, `setting`, `flag_override_default`, `default`, `flag_add`, `flag_add_readonly`, `flag_edit`, `flag_edit_readonly`, `flag_search`, `flag_search_readonly`, `flag_datagrid`, `flag_datagrid_readonly`, `flag_index`, `flag_detail` FROM structure_formats WHERE structure_old_id='" + $("#structures_copy_old_id").val() + "');\n");
        return false;
    });
    $("input.gen").change(function () {
        generate();
    });

    $("#structure_search").keypress(function (event) {
        if (event.keyCode == 13) {
            var val = 'json={"val" : "' + $("#structure_search").val() + '", "type" : "structures"}';
            $.ajax({url: "loader.php", data: val, success: function (data) {
                    $("#result").html(data);
                    setStructureGetField();
                }});
        }
    });

    $("#value_domains_search").keypress(function (event) {
        if (event.keyCode == 13) {
            var val = 'json={"val" : "' + $("#value_domains_search").val() + '", "type" : "value_domains"}';
            $.ajax({url: "loader.php", data: val, success: function (data) {
                    $("#valueDomainResult").html(data);

                }});
        }
    });

    $(".autoincrementButton").click(function () {
        incrementField($(this).parent().children("input"));
        return false;
    });

    $('#structure_fields_model').jsonSuggest(function (text, wildCard, caseSensitive, notCharacter) {},
            {type: 'GET',
                url: 'suggest.php',
                dataName: "json",
                ajaxResults: true,
                minCharacters: 1,
                width: 240,
                format: function (inputTxt) {
                    return '{"val" : "' + inputTxt + '", "fetching" : "model", "plugin" : "' + $('#structure_fields_plugin').val() + '" }';
                }
            }
    );
    $('#structure_fields_plugin').jsonSuggest(function (text, wildCard, caseSensitive, notCharacter) {},
            {type: 'GET',
                url: 'suggest.php',
                dataName: "json",
                ajaxResults: true,
                minCharacters: 1,
                width: 240,
                format: function (inputTxt) {
                    return '{"val" : "' + inputTxt + '", "fetching" : "plugin" }';
                }
            }
    );

    $('#autoBuild2_language_label, #struct_val_domain_language_alias, #autoBuild2_language_tag, #autoBuild2_language_help, #autoBuild2_language_heading').jsonSuggest(function (text, wildCard, caseSensitive, notCharacter) {},
            {type: 'GET',
                url: 'suggest.php',
                dataName: "json",
                ajaxResults: true,
                minCharacters: 1,
                width: 500,
                format: function (inputTxt) {
                    return '{"val" : "' + inputTxt + '", "fetching" : "language" }';
                }
            }
    );

    $('#autoBuild2_model').jsonSuggest(function (text, wildCard, caseSensitive, notCharacter) {},
            {type: 'GET',
                url: 'suggest.php',
                dataName: "json",
                ajaxResults: true,
                minCharacters: 1,
                width: 240,
                format: function (inputTxt) {
                    return '{"val" : "' + inputTxt + '", "fetching" : "model", "plugin" : "' + $('#autoBuild2_plugin').val() + '" }';
                }
            }
    );
    $('#autoBuild2_plugin').jsonSuggest(function (text, wildCard, caseSensitive, notCharacter) {},
            {type: 'GET',
                url: 'suggest.php',
                dataName: "json",
                ajaxResults: true,
                minCharacters: 1,
                width: 240,
                format: function (inputTxt) {
                    return '{"val" : "' + inputTxt + '", "fetching" : "plugin" }';
                }
            }
    );

    $('#autoBuild2_structure_value_domain').jsonSuggest(function (text, wildCard, caseSensitive, notCharacter) {},
            {type: 'GET',
                url: 'suggest.php',
                dataName: "json",
                ajaxResults: true,
                minCharacters: 1,
                width: 240,
                format: function (inputTxt) {
                    return '{"val" : "' + inputTxt + '", "fetching" : "domain-value" }';
                }
            }
    );

    $('#autoBuild2_tablename').jsonSuggest(function (text, wildCard, caseSensitive, notCharacter) {},
            {type: 'GET',
                url: 'suggest.php',
                dataName: "json",
                ajaxResults: true,
                minCharacters: 1,
                width: 240,
                format: function (inputTxt) {
                    return '{"val" : "' + inputTxt + '", "fetching" : "tablenamelist" }';
                }
            }
    );

    $("#structure_fields_type").jsonSuggest(structureTypes);
    $("#autoBuild2_type").jsonSuggest(structureTypes);


    bottomMenu = ['yes', 'yes', 'no', 'no', 'yes'];
    $("#queryBuilder").tabs({
        selected: 1,
        select: function (event, ui) {
            if (((bottomMenu.length > ui.index) ? bottomMenu[ui.index] : 'no') == 'no') {
                $("#databaseExplorer").hide();
                $("#resultZone").hide();
            } else {
                $("#databaseExplorer").show();
                $("#resultZone").show();
            }
            if (ui.index == 4) {
                if ($$("#data-mart svg").length == 0) {
                    dataBrowser();
                }
            }
        }});
    $("#databaseExplorer").tabs();


    $('.ui-state-default').hover(function () {
        $(this).addClass('ui-state-hover');
    }, function () {
        $(this).removeClass('ui-state-hover');
    });

    $("#generateSQL").click(function () {
        var proceed = true;

        if ($("#autoBuild1_alias").val().length == 0) {
            flashColor($("#autoBuild1_alias"), '#f00');
            proceed = false;
        }
        if (proceed) {
            var global = '{ "alias" : "' + $("#autoBuild1_alias").val() + '", '
                    + ' "language_title" : "' + $("#autoBuild1_language_title").val() + '"}';
            var fields = '[ ';
            var headerArr = $("#autoBuild2").children("thead").children("tr").children("th");
            $("#autoBuild2").children("tbody").children("tr").each(function () {
                var fieldsArr = $(this).children("td");
                fields += '{ ';
                for (var i = 0; i < fieldsArr.length; ++i) {
                    var fieldVal = null;
                    if ($(headerArr[i]).hasClass("checkbox")) {
                        fieldVal = $(fieldsArr[i]).children("input").attr("checked") ? 1 : 0;
                    } else {
                        fieldVal = $(fieldsArr[i]).html();
                        if (i == 0) {
                            //remove delete
                            fieldVal = fieldVal.substr(41);
                        }
                    }
                    fields += ' "' + $(headerArr[i]).data("name") + '" : "' + fieldVal + '", ';
                }
                fields = fields.substr(0, fields.length - 2) + ' }, ';
            });
            fields = fields.substr(0, fields.length - 2) + ' ]';
            var val = 'json={"global" : ' + global + ', "fields" : ' + fields + ' }';
            val = val.replace(/%/g, "%25").replace(/&gt;/g, "%3E").replace(/&lt;/g, "%3C");
            $.ajax({url: "sqlGenerator.php", data: val, type: "POST", success: function (data) {
                    $("#resultZone").val($("#resultZone").val() + data + "\n");
                }});
        }
        return false;
    });

    $("th").each(function () {
        if ($(this).html().length > 0) {
            $(this).data("name", $(this).html()).html($(this).html().replace(/_/g, "<br/>"));
        }
    });

    $("#clearAutoBuildTable").click(function () {
        if (confirm("Are you sure you want to clear the table?")) {
            $("#autoBuild2 tbody").html("");
        }
    });

    calculateAutoBuild2LeftMargin();

    $$("#select-value-domain").on("selectmenuchange", function (event) {
        options = $$("#select-value-domain option");
        for (var i = 0; i < options.length; i++) {
            var val = options[i].value;

            if (val == event.target.value) {
                $(".select-value-domain" + val).show();
            } else {
                $(".select-value-domain" + val).hide();
            }
        }
    });
    
    
    $$("#select-value-domain").selectmenu({
        width: 200
    });

    $(document).delegate("#createAll", "click", function () {
        var toIgnore = ["id", "created", "created_by", "modified", "modified_by", "deleted"];
        $("#tableResult").find("tr").each(function () {
            if ($.inArray($(this).find("td:first").html(), toIgnore) == -1) {
                $(this).click();
                addLine();
            }
        });

    }).delegate(".clickable.editable", "click", function (event) {
        fieldToggle(this);
    }).delegate("input[type=checkbox]", "click", function (event) {
        event.stopPropagation();
    }).delegate(".deleteLine", "click", function (event) {
        $(this).parents("tr:first").remove();
        event.stopPropagation();
        return false;
    }).delegate(".copyLine", "click", function (event) {
        $this = $(this);
        var tds = $this.closest("tr").children("td");
        var $firstField = $("#autoBuild2_field");

        var i=1;
        for (i=1; i<tds.length-2; i++){
                $td = $(tds[i]);
                if ($td.children("input[type='checkbox']").length==0)
                {
                        $firstField.parent().siblings().eq(i-1).children("input").val ($td.text());
            }else{
                        checked = $td.children("input[type='checkbox']").is(":checked");
                        $firstField.parent().siblings().eq(i-1).children("input[type='checkbox']").prop('checked', checked);
            }
        }
        event.stopPropagation();
        return false;
    });

    $$('#structure_value_domains_override').closest("td").html(
            '<select name="override" id="structure_value_domains_override">      <option selected="selected"></option>      <option>open</option>      <option>extend</option>      <option>locked</option></select>'
            );

    $$('#structure_value_domains_function_override').closest("td").html(
            '<select name="override" id="structure_value_domains_function_override">      <option selected="selected"></option>      <option>open</option>      <option>extend</option>      <option>locked</option></select>'
            );


    autoCompelete();
});
var dmbc = [];
var dms = [];

function fixToVariable(domainValues){
    var tds = $("table.select-value-domain2:eq(0) td");
    $(tds[0]).find("input").val(domainValues['domainName']);
    $(tds[1]).find("input").val("");
    $(tds[2]).find("input").val(domainValues['category']);
    $(tds[3]).find("input").val("");
    $(tds[4]).find("input").prop("checked", true);

    var $newTr = $('<tr><td><a href="#no" class="deleteLine">(x)</a></td><td class="clickable editable"></td><td class="clickable editable"></td><td class="clickable editable"></td><td class="clickable editable"></td><td><input type="checkbox"></td><td></td></tr>');
    var $table = $("table.select-value-domain2:eq(1) tbody");
    var tds, $currentTr, value;
    $("table.select-value-domain2:eq(1) tbody tr").remove();

    for (var i=0; i<domainValues["values"].length; i++){
        $table.append($newTr.clone());
        $currentTr = $table.children("tr:last");
        value = domainValues["values"][i];
        tds = $currentTr.find("td");
        $(tds[1]).text(value['value']);
        $(tds[2]).text(value['en']);
        $(tds[3]).text(value['fr']);
        $(tds[4]).text(value['displayOrder']);
        $(tds[5]).find("input[type='checkbox']").prop("checked", value['flagActive']);
        $(tds[6]).text("");
    }
}

function dataBrowser() {
    idModel = [
        {id: 1, plugin: 'InventoryManagement', model: 'ViewAliquot', 'position':[2, 3]},
        {id: 2, plugin: 'InventoryManagement', model: 'ViewCollection', 'position':[2, 6]},
        {id: 3, plugin: 'StorageLayout', model: 'NonTmaBlockStorage', 'position':[2, 2]},
        {id: 4, plugin: 'ClinicalAnnotation', model: 'Participant', 'position':[2, 7]},
        {id: 5, plugin: 'InventoryManagement', model: 'ViewSample', 'position':[1, 5]},
        {id: 6, plugin: 'ClinicalAnnotation', model: 'MiscIdentifier', 'position':[0, 8]},
        {id: 7, plugin: 'InventoryManagement', model: 'ViewAliquotUse', 'position':[0, 2]},
        {id: 8, plugin: 'ClinicalAnnotation', model: 'ConsentMaster', 'position':[0, 7]},
        {id: 9, plugin: 'ClinicalAnnotation', model: 'DiagnosisMaster', 'position':[4, 7]},
        {id: 10, plugin: 'ClinicalAnnotation', model: 'TreatmentMaster', 'position':[4, 6]},
        {id: 11, plugin: 'ClinicalAnnotation', model: 'FamilyHistory', 'position':[2, 9]},
        {id: 12, plugin: 'ClinicalAnnotation', model: 'ParticipantMessage', 'position':[1, 9]},
        {id: 13, plugin: 'InventoryManagement', model: 'QualityCtrl', 'position':[4, 4]},
        {id: 14, plugin: 'ClinicalAnnotation', model: 'EventMaster', 'position':[4, 8]},
        {id: 15, plugin: 'InventoryManagement', model: 'SpecimenReviewMaster', 'position':[0, 5]},
        {id: 16, plugin: 'Order', model: 'OrderItem', 'position':[3, 2]},
        {id: 17, plugin: 'Order', model: 'Shipment', 'position':[4, 2]},
        {id: 18, plugin: 'ClinicalAnnotation', model: 'ParticipantContact', 'position':[4, 9]},
        {id: 19, plugin: 'ClinicalAnnotation', model: 'ReproductiveHistory', 'position':[3, 9]},
        {id: 20, plugin: 'ClinicalAnnotation', model: 'TreatmentExtendMaster', 'position':[4, 5]},
        {id: 21, plugin: 'InventoryManagement', model: 'AliquotReviewMaster', 'position':[0, 3]},
        {id: 22, plugin: 'Order', model: 'Order', 'position':[4, 1]},
        {id: 23, plugin: 'StorageLayout', model: 'TmaSlide', 'position':[1, 1]},
        {id: 24, plugin: 'StorageLayout', model: 'TmaBlock', 'position':[1, 2]},
        {id: 25, plugin: 'Study', model: 'StudySummary', 'position':{'exception': [[0, 1], [4, 0], [1, 4], [0, 6], [0, 9]]}},
        {id: 26, plugin: 'Order', model: 'OrderLine', 'position':[3, 1]},
        {id: 27, plugin: 'StorageLayout', model: 'TmaSlideUse', 'position':[1, 0]}
    ];

    $$.ajax({url: "idModel.php", type: "POST", success: function (data) {
            try {
                data = JSON.parse(data);
                a = idModel.map(function(item){return {plugin: item['plugin'], model: item['model']};});
                b = data['idModel'].map(function(item){return {plugin: item['plugin'], model: item['model']};});
                a.sort(function(x, y){
                    return x["plugin"].localeCompare(y["plugin"]);
                });
                b.sort(function(x, y){
                    return x["plugin"].localeCompare(y["plugin"]);
                });
                a = JSON.stringify(a);
                b = JSON.stringify(b);
                dms = data['idModel'];
                addPosition(dms);
                dmbc = data['dmbc'];
                if (a != b) {
                    alert("The datamart_structures table is not correspond to the default and so the result of data browsing is not guarantied.");
                }
                drawDataMartDiagram(dms, dmbc);
            } catch (err) {

            }
        }});
}

function addPosition(dms) {
    dms.forEach(function(dmsItem, i, dmsItems){
        var idModelItem = idModel.find(function(item){ return (item.plugin == dmsItem.plugin && item.model == dmsItem.model);});
        dmsItems[i]['position'] = idModelItem['position'];
        dmsItems[i]['id1'] = idModelItem['id'];
    });
}

function makeExceptions(dms) {
    exception = [];
    exception.push({'e1': [25, 27], 'e2': [0, 1]});
    exception.push({'e1': [25, 23], 'e2': [0, 1]});
    exception.push({'e1': [25, 7], 'e2': [0, 1]});
    exception.push({'e1': [25, 26], 'e2': [4, 0]});
    exception.push({'e1': [25, 22], 'e2': [4, 0]});
    exception.push({'e1': [25, 1], 'e2': [1, 4]});
    exception.push({'e1': [25, 8], 'e2': [0, 6]});
    exception.push({'e1': [25, 6], 'e2': [0, 9]});
    
    exceptions = [];

    exception.forEach(function(ex){
        x1 = dms.find(function(item){return ex.e1[0] == item.id1;});
        x2 = dms.find(function(item){return ex.e1[1] == item.id1;});
        exceptions[x1.id+'-'+x2.id ] = ex.e2;
    });

    return exceptions;
}

function drawDataMartDiagram(dms, dmbc) {
    var exceptions = makeExceptions(dms);
    var div = $$("#data-mart");
    div.html("");
    div.append("<table id='data-mart-table'>");
    div.append("<svg>");
    div.prepend('<a href="javascript:void(0)" id="refresh-data-mart" class="ui-state-default ui-corner-all button_link custom" name="custom autoBuild1"><span class="button_icon ui-icon ui-icon-refresh"></span><span>Refresh Datamart</span></a>');
    div.find("#refresh-data-mart").click(function(){
        div.html("Loading...");
        $$("#resultZone").text("");
        dataBrowser();
    });
    var table = $$(div.find("table"));
    var svg = $$(div.find("svg"));
    var i, j;
    for (i = 0; i < 5; i++) {
        table.append("<tr id = 'dm-row-" + i + "'></tr>");
        for (j = 0; j < 10; j++) {
            td = "<td id = 'dm-cell-" + i + "-" + j + "'></td>";
            table.find("tr").eq(i).append(td);
        }
    }
    svg.attr("id", "data-mart-svg");
    svg.css("top", "-502px");
    svg.attr("height", "502");
    svg.attr("width", "1042");

    generateQuery = '<a href="javascript:void(0)" id="generateSQLDataMart" class="ui-state-default ui-corner-all button_link custom" name="custom autoBuild1"><span class="button_icon ui-icon ui-icon-play"></span><span>Generate SQL</span></a>';
    div.append(generateQuery);

    for (i in dmbc) {
        var id1 = dmbc[i][0];
        var id2 = dmbc[i][1];
        var active = dmbc[i][2];
        if (typeof exceptions[id1 + "-" + id2] === 'undefined' && typeof exceptions[id2 + "-" + id1] === 'undefined') {
            dmsTemp1 = dms.find(function (item) {
                return item.id == id1;
            });
            dmsTemp2 = dms.find(function (item) {
                return item.id == id2;
            });
            x1 = dmsTemp1.position[0];
            y1 = dmsTemp1.position[1];
            x2 = dmsTemp2.position[0];
            y2 = dmsTemp2.position[1];
        } else if (typeof exceptions[id1 + "-" + id2] !== 'undefined') {
            x1 = exceptions[id1 + "-" + id2][0];
            y1 = exceptions[id1 + "-" + id2][1];
            dmsTemp2 = dms.find(function (item) {
                return item.id == id2
            });
            x2 = dmsTemp2.position[0];
            y2 = dmsTemp2.position[1];
        } else if (typeof exceptions[id2 + "-" + id1] !== 'undefined') {
            x1 = exceptions[id2 + "-" + id1][0];
            y1 = exceptions[id2 + "-" + id1][1];
            dmsTemp1 = dms.find(function (item) {
                return item.id == id1
            });
            x2 = dmsTemp1.position[0];
            y2 = dmsTemp1.position[1];
        }

        activeClass = (active) ? "data-mart-active" : "data-mart-passive";
        td1 = table.find("#dm-cell-" + x1 + "-" + y1);
        td2 = table.find("#dm-cell-" + x2 + "-" + y2);

        x1 = Math.floor(td1.position().left + 50);
        y1 = Math.floor(td1.position().top + 50);
        x2 = Math.floor(td2.position().left + 50);
        y2 = Math.floor(td2.position().top + 50);

        distance = 16;
        if (x1 < x2) {
            x1 += distance;
            x2 -= distance;
        } else if (x2 < x1) {
            x1 -= distance;
            x2 += distance;
        }

        if (y1 < y2) {
            y1 += distance;
            y2 -= distance;
        } else if (y2 < y1) {
            y1 -= distance;
            y2 += distance;
        }

        if (id1 != id2) {
            var line2 = $$(document.createElementNS('http://www.w3.org/2000/svg', 'line')).attr({id: 'line-' + i, x1: x1, y1: y1, x2: x2, y2: y2, class: activeClass, "data-from": id1, "data-to": id2, "data-active": active})
                    .css("stroke-width", "10").click(function () {
                createDataBartQuery(this);
            });

            svg.append(line2);
        } else {
            var circle2 = $$(document.createElementNS('http://www.w3.org/2000/svg', 'ellipse')).attr({
                class: activeClass, id: 'line-' + i, cx: x1, cy: y1 + 20, rx: 10, ry: 20, "data-from": id1, "data-to": id2, "data-active": active})
                    .css("stroke-width", "10").css("fill", "none").click(function () {
                createDataBartQuery(this);
            });
            svg.append(circle2);
        }
    }

    for (i in dms) {
        id = dms[i].id;
        name = dms[i].name;
        img = "<img src='img/datamart/" + id + ".png' alt = '" + name + "' title = '" + name + "'>";
        if (typeof dms[i].position[0] !== "undefined") {
            x = dms[i].position[0];
            y = dms[i].position[1];
            td = table.find("#dm-cell-" + x + "-" + y).prepend(img);
            y = Math.floor(td.position().top + Math.floor(td.css("height").replace("px", "")) / 2);
            x = Math.floor(td.position().left + Math.floor(td.css("width").replace("px", "")) / 2);
            var circle = $$(document.createElementNS('http://www.w3.org/2000/svg', 'circle')).attr({cx: x + 5, cy: y + 5, r: 10, class: "data-mart-node"})
                    .click(function () {
                        addEdge(this);
                    });

            svg.append(circle);
            var text = $$(document.createElementNS('http://www.w3.org/2000/svg', 'title'));
            svg.children(":last-child").append(text);
            svg.children(":last-child").children().text(name);
        } else {
            ex = dms[i].position.exception;
            for (j in ex) {
                x = ex[j][0];
                y = ex[j][1];
                td = table.find("#dm-cell-" + x + "-" + y).prepend(img);
                y = Math.floor(td.position().top + Math.floor(td.css("height").replace("px", "")) / 2);
                x = Math.floor(td.position().left + Math.floor(td.css("width").replace("px", "")) / 2);
                var circle = $$(document.createElementNS('http://www.w3.org/2000/svg', 'circle')).attr({cx: x + 5, cy: y + 5, r: 10, class: "data-mart-node"})
                        .click(function () {
                            addEdge(this);
                        });

                svg.append(circle);
                var text = $$(document.createElementNS('http://www.w3.org/2000/svg', 'title'));
                svg.children(":last-child").append(text);
                svg.children(":last-child").children().text(name);

            }
        }
    }


}

function addEdge(e) {
    $this = $$(e);
    if ($this.hasClass("data-mart-from")) {
        $this.removeClass("data-mart-from")
    } else {
        $from = $("#data-mart .data-mart-from");
        if (typeof $form == 'undefined') {
            $this.addClass("data-mart-from");
        }


    }
}

toEnable = [];
toDisable = [];
function createDataBartQuery(e) {
    $this = $$(e);
    $this.toggleClass("data-mart-active");
    active = $this.hasClass("data-mart-active");
    var id = $this.attr("id");
    var from = parseInt($this.attr("data-from"));
    var to = parseInt($this.attr("data-to"));
    var activeOld = parseInt($this.attr("data-active"));

    if (activeOld && active) {
        delete toDisable[id];
    } else if (activeOld && !active) {
        query = "UPDATE datamart_browsing_controls set flag_active_1_to_2 = 0, flag_active_2_to_1 = 0 WHERE (id1 = " + from + " AND id2 =" + to + ") OR (id1 = " + to + " AND id2 =" + from + ");\n";
        toDisable[id] = query;
    } else if (!activeOld && active) {
        query = "UPDATE datamart_browsing_controls set flag_active_1_to_2 = 1, flag_active_2_to_1 = 1 WHERE (id1 = " + from + " AND id2 =" + to + ") OR (id1 = " + to + " AND id2 =" + from + ");\n";
        toEnable[id] = query;
    } else if (!activeOld && !active) {
        delete toEnable[id];
    }
    var queries = "";
    for (i in toEnable) {
        queries += toEnable[i];
    }
    for (i in toDisable) {
        queries += toDisable[i];
    }
    if (queries.length != 0) {
        queries = "start transaction;\n" + queries + "commit;\n\n";
    }
    $$("#resultZone").val(queries);
}

function flashColor(item, color) {
    var timer = 80;
    $(item).animate({
        "background-color": color
    }, timer, function () {
        // Animation complete.
        $(item).animate({
            "background-color": "#fff"
        }, timer, function () {
            $(item).animate({
                "background-color": color
            }, timer, function () {
                $(item).animate({
                    "background-color": "#fff"
                }, timer)
            })
        })
    });
}

function toggleList(name) {
    $("#structuresLst").css("display", "none");
    $("#tablesLst").css("display", "none");
    $("#structure_fieldsLst").css("display", "none");
    $("#structure_value_domainsLst").css("display", "none");
    $("#" + name + "Lst").css("display", "inline-block");
}

function toggleCreate(name) {
    $(".create").css("display", "none");
    $("." + name + "Div").css("display", "block");
}

function genStructureFormatOldId() {
    $("#structure_formats_old_id").val($("#structure_formats_structure_old_id").val() + "_" + $("#structure_formats_structure_field_old_id").val());
}

function setStructureGetField() {
    $("#structureResult").children("table").children("tbody").children("tr").each(function () {
        $(this).click(function () {
            var oldId = $(this).find(".structure_field_old_id").html();
            $("#selectedField").html(oldId);
            $("#format_structure_field_old_id").val(oldId);
            genStructureFormatOldId();
            $("#format_structure_field_id").val("(SELECT id FROM structure_fields WHERE old_id = '" + oldId + "')");
        });
        $(this).addClass("clickable");
        return false;
    });
}

function setTableGetField() {
    $("#tableResult").children("table").children("tbody").children("tr").each(function () {
        $(this).click(function () {
            var field = $(this).find(".Field").html();
            $("#structure_fields_field").val(field);
            $("#structure_fields_language_label").val(field.replace(/_/g, " "));
            $("#autoBuild2_field").val(field);
            $("#autoBuild2_language_label").val(field.replace(/_/g, " "));
            $("#structure_fields_tablename").val($("#tablename").html());
            $("#autoBuild2_tablename").val($("#tablename").html());

            if ($("#table_autotype").attr("checked")) {
                var type = $(this).find(".Type").html();
                var outType = "";
                if (type.match(/^[a-z]*int\(\d+\)/)) {
                    //integer
                    outType = type.match(/^[a-z]*int\(\d+\) unsigned$/) ? "integer_positive" : "integer";
                } else if (type.match(/^(float|double|decimal)(\(\d+,?\d*\))?/)) {
                    //float
                    outType = type.match(/^(float|double|decimal)(\(\d+,?\d*\))? unsigned$/) ? "float_positive" : "float";
                } else if (type == "date") {
                    outType = "date";
                } else if (type == "datetime") {
                    outType = "datetime";
                } else {
                    outType = "input";
                }
                $("#structure_fields_type").val(outType);
                $("#autoBuild2_type").val(outType);
            }
            return false;
        });
        $(this).addClass("clickable");
    });
}

function generate() {
    genStructureFormatOldId();
    $("#structure_formats_structure_id").val("(SELECT id FROM structures WHERE old_id = '" + $("#structure_formats_structure_old_id").val() + "')");
    $("#structure_formats_structure_field_id").val("(SELECT id FROM structure_fields WHERE old_id = '" + $("#structure_formats_structure_field_old_id").val() + "')");
}

function isNumeric(form_value) {
    if (form_value.match(/^\d+$/) == null) {
        return false;
    } else {
        return true;
    }
}

function incrementField(field) {
    //var val = $(this).parent().children("input").val();
    var val = $(field).val();
    var prefix = "";

    if (isNumeric(val)) {
        $(field).val(parseInt(val, 10) + 1);
        flashColor($(field), "#0f0");
        $(field).trigger('change');
    } else if (val.lastIndexOf("-") > -1) {
        prefix = val.substr(0, val.lastIndexOf("-") + 1);
        val = val.substr(val.lastIndexOf("-") + 1);
        var valLength = val.length;
        val = (parseInt(val, 10) + 1) + "";
        while (val.length < valLength) {
            val = 0 + val;
        }
        $(field).val(prefix + val);
        flashColor($(field), "#0f0");
        $(field).trigger('change');
    } else {
        flashColor($(field), "#f00");
    }

}

function addLine() {
    var line = "<tr>";
    var valid = true;
    $(".add.autoBuild2").parent().parent().parent().parent().children("tfoot").children("tr:first").children("td").each(function () {
        var val = "";
        if ($(this).children("input").hasClass("notEmpty") && $(this).children("input").val().length == 0) {
            flashColor($(this).children("input"), "#f00");
            $(this).children("input").focus();
            valid = false;
        }
        if ($(this).children("input").attr("type") == "checkbox") {
            val = '<input type="checkbox"' + ($(this).children("input").attr("checked") ? ' checked="checked"' : "") + '/>';
        } else {
            val = $(this).children("input").val();
        }
        line += "<td class='clickable editable'>" + val + "</td>";
    });
    line += "</tr>";
    if (valid) {
        var table = $(".add.autoBuild2").closest("table");
        $(table).children("tbody").append(line);
        $(table).find("tbody tr:last td:first").removeClass("clickable").removeClass("editable").prepend('<a href="#no" class="deleteLine">(x)</a> ');
        $(table).find("tfoot tr:first td").each(function () {
            var field = $(this).children("input");
            if ($(field).hasClass("autoBuildIncrement")) {
                incrementField(field);
            }
            if ($(field).hasClass("clear")) {
                $(field).val("");
            }
        });
        toggleEditMode(false);
        $("#autoBuild2").trigger('update');
        calculateAutoBuild2LeftMargin();
    }
}

function deleteRow() {
    toggleEditMode(false);
    $("#autoBuild2_sfi_id").val("");
    $("#autoBuild2_sfo_id").val("");
    $("#autoBuild2_field").val("");
    calculateAutoBuild2LeftMargin();
}

function toggleEditMode(editMode) {
    if (editMode && $("a.autoBuild2").children("span:nth-child(1)").hasClass("ui-icon-plus")) {
        $("a.autoBuild2").children("span:nth-child(1)").removeClass("ui-icon-plus");
        $("a.autoBuild2").children("span:nth-child(1)").addClass("ui-icon-disk");
        $(".ignoreButton").show();
        $(".deleteButton").show();
        $("a.autoBuild2").children("span:nth-child(2)").html("Save");
    } else if (!editMode && $("a.autoBuild2").children("span:nth-child(1)").hasClass("ui-icon-disk")) {
        $("a.autoBuild2").children("span:nth-child(1)").removeClass("ui-icon-disk");
        $("a.autoBuild2").children("span:nth-child(1)").addClass("ui-icon-plus");
        $(".ignoreButton").hide();
        $(".deleteButton").hide();
        $("a.autoBuild2").children("span:nth-child(2)").html("Add");
    }
}

function calculateAutoBuild2LeftMargin() {
    var maxWidth = 0;
    $("#autoBuild2 thead th:nth-child(1), #autoBuild2 tbody td:nth-child(1), #autoBuild2 tfoot tr:nth-child(1) td:nth-child(1)").each(function () {
        maxWidth = Math.max($(this).width(), maxWidth);
    });

    $("#autoBuild2").css("margin-left", maxWidth + "px");
}

function fieldToggle(field) {
    if ($(field).is(".clickable.editable")) {
        if ($(field).find('input[type=checkbox]').length) {
            $(field).find('input[type=checkbox]').attr("checked", !$(field).find('input[type=checkbox]').attr("checked"));
        } else if ($(field).find('input[type=text]').length == 0) {
            var val = $(field).html();
            $(field).html('<input id="currentMod" type="text" value="" style="width: 100%;"/>');
            $(field).find("input").val(val).data("orgVal", val).select().focusout(function () {
                $(field).html($(this).val());
            }).keyup(function (event) {
                if (event.keyCode == 27) {
                    //esc
                    $(this).val($(this).data("orgVal")).focusout();
                }
            }).keydown(function (event) {
                if (event.keyCode == 9) {
                    //tab
                    //
                    var elem = event.shiftKey ? $(this).closest('td').prev() : $(this).closest('td').next();
                    $('#currentMod').focusout();
                    if ($(elem).find('input[type=checkbox]').length) {
                        $(elem).find('input[type=checkbox]').focus();
                    } else {
                        fieldToggle(elem);
                    }
                    return false;
                }
            });
        }
    }
}

function autoCompelete() {
    ids = [
        {id: "#structure_value_domains_domain_name", table: "structure_value_domains", field: "domain_name", order: "domain_name", where: "source is NULL || source = ''"},
        {id: "#structure_value_domains_variable_domain_name", table: "structure_value_domains", field: "domain_name", order: "domain_name", where: "source LIKE '%(%)%'"},
        {id: "#structure_value_domains_function_domain_name", table: "structure_value_domains", field: "domain_name", order: "domain_name", where: "(source IS NOT NULL AND source <> '') AND source NOT LIKE '%(%)%'"},
        {id: "#structure_value_domains_function_plugin", table: "acos", field: "alias", order: "alias", where: "parent_id='1'"},
        {id: "#autoBuild1_alias", table: "structures", field: "alias", order: "alias"}
    ];
    ids.forEach(function (item) {
        var val = item;
        $$.ajax({url: "autoCompelete.php", val: item, data: "data=" + JSON.stringify(val), type: "POST", success: function (data) {
                try {
                    data = JSON.parse(data);
                    $$(this.val.id).autocomplete({
                        autoFocus: true,
                        source: data,
                        select: function (event, ui) {
                            $(this).val(ui.item.value);
                            $($(this).closest("table").find("a")).trigger("click");
                        }
                    });
                } catch (err) {

                }
            }});

    });
}