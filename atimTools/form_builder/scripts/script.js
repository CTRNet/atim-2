var structureTypes = '[{"text" : "autocomplete"},{"text" : "checkbox"},{"text" : "date"},{"text" : "datetime"},{"text" : "file"},{"text" : "hidden"},{"text" : "input"},{"text" : "input-readonly"},{"text" : "number"},{"text" : "password"},{"text" : "radio"},{"text" : "radiolist"},{"text" : "select"},{"text" : "textarea"},{"text" : "time"},{"text":"integer"},{"text":"integer_positive"},{"text":"float"},{"text":"float_positive"},{"text":"yes_no"}]';
var tmpLine = "";
var lineWithoutChanges = "";
var deleteLine = '<a href="#no" class="deleteLine">(x)</a>';

$(function(){
	$("#db_select_div_target").append($("#db_select_div"));
	
	$("#piton5").scroll(function() { 
		$("td.scrollingButtons:last").css("padding-left", Math.max($(this).scrollLeft() - 110, 0));
		$("#autoBuild2 th:nth-child(4), #autoBuild2 td:nth-child(4)").css("left", $(this).scrollLeft());
	});
	$("#dbSelect").change(function(){
		document.location = "?db=" + $(this).children(":selected").html();
	});
	
	$("#runSql").click(function(){
		$.ajax({ url: "sqlRunner.php", data: "sql=" + $("textarea").val(), type: "POST", success: function(data){
	        alert(data);
	    }});
		return false;
	});
	
	$("#clearSql").click(function(){
		$("textarea").val("");
		return false;
	});
	
	//Second third of the screen function - displays a structure in the right pane
	$(".structLink").click(function(){
		var oldId = $(this).attr('href').substring(1);
		var val = 'json={"val" : "' + oldId + '", "type" : "structures"}';
		$.ajax({ url: "loader.php", data: val, success: function(data){
			$("#structureResult").html(data);
			setStructureGetField();
		}});
		return false;
	});

	//Second third of the screen function - displays a table in the right pane
	$(".tableLink").click(function(){
		var tableName = $(this).attr('href').substring(1);
		var val = 'json={"val" : "' + tableName + '", "type" : "tables"}';
		$.ajax({ url: "loader.php", data: val, success: function(data){
	        $("#tableResult").html(data);
	        setTableGetField();
	      }});
		return false;
	});

	//Second third of the screen function - displays a model's fields in the right pane
	$(".fieldLink").click(function(){
		var modelName = $(this).attr('href').substring(1);
		var val = 'json={"val" : "' + modelName + '", "type" : "fields"}';
		$.ajax({ url: "loader.php", data: val, success: function(data){
	        $("#fieldResult").html(data);
		}});
		return false;
	});

	//Second third of the screen function - displays a value domain in the right pane
	$(".vDomainLink").click(function(){
		var domainName = $(this).attr('href').substring(1);
		var val = 'json={"val" : "' + domainName + '", "type" : "value_domains"}';
		$.ajax({ url: "loader.php", data: val, success: function(data){
	        $("#valueDomainResult").html(data);
		}});
		return false;
	});

	//Sets the selected value domain name in the structure value domain field of the structure building pane
	$(".vDomainLinkAdd").click(function(){
		var domainName = $(this).attr('href').substring(1);
		var val = 'json={"val" : "' + domainName + '", "type" : "value_domains"}';
		var query = "(SELECT id FROM structure_value_domains WHERE domain_name='" + domainName + "')";
		$("#structure_fields_structure_value_domain").val(query);
		$("#autoBuild2_structure_value_domain").val(domainName);
		$.ajax({ url: "loader.php", data: val, success: function(data){
	        $("#valueDomainResult").html(data);
		}});
		return false;
	});
	
	//builds the input fields related to an "insert" table
	$(".insert").each(function(){
		var prefix = $(this).children("thead").attr("class");
		if(prefix.lastIndexOf(" ") > 0){
			prefix = prefix.substr(prefix.lastIndexOf(" ") + 1);
		}
		$(this).addClass("ui-widget ui-widget-content");
		$(this).children("thead").children("tr").addClass("ui-widget-header");
		var result = "<tr>";
		$(this).children("thead").children("tr").children("th").each(function(){
			var css = "";
			var readonly = "";
			var autoincrement = "";
			if($(this).hasClass("gen")){
				css += " gen ";
			}
			if($(this).hasClass("readonly")){
				readonly = ' readonly="readonly" ';
				css += " readonly ";
			}
			if($(this).hasClass("autoincrement")){
				autoincrement = '<a href="#" class="autoincrementButton">[+]</a>';
			}
			if($(this).hasClass("notEmpty")){
				css += " notEmpty ";
			}
			if($(this).hasClass("clear")){
				css += " clear ";
			}
			if($(this).hasClass("autoBuildIncrement")){
				css += " autoBuildIncrement ";
			}
			var inputTypeNValueNSize = $(this).hasClass("checkbox") ? 'type="checkbox" value="1"' : 'type="text" size="13"';
			result += '<td><input id="' + prefix + "_" + $(this).html() + '" ' + inputTypeNValueNSize + ' class="' + css + '" ' + readonly + '/>' + autoincrement + '</td>';
		});
		result += "</tr>";
		$(this).children("tfoot").html(result + '<tr><td colspan="30" class="scrollingButtons"><a href="#" class="add ui-state-default ui-corner-all button_link ' + $(this).children("thead").attr("class") + '" name="' + $(this).children("thead").attr("class") + '"><span class="button_icon ui-icon ui-icon-plus"></span><span>Add</span></a></td></tr>');
	});
	
	//update structure value domain functions
	$("#piton4 table:first a").each(function(){
		$($(this).find("span")[0]).removeClass("ui-icon-plus").addClass("ui-icon-arrowthickstop-1-s");
		$($(this).find("span")[1]).html("Load value domain");
		$("#piton4 table:nth-child(4)").find("input:first, input:last").prop("type", "hidden");
		$($("#piton4 table:nth-child(4) tfoot tr:first input")[4]).prop("checked", true);
		$("#piton4 table:nth-child(4) tfoot tr:nth-child(2) td").append(' <a href="#no" id="addFromPopup" class="ui-state-default ui-corner-all button_link"><span class="button_icon ui-icon ui-icon-newwin"></span><span>Add from text area</span></a>');
		$("#addFromPopup").click(function(){ $("#addFromTextAreaDialog").dialog('open').find("textarea").focus(); });
		$(this).click(function(){
			//load value domain
			var command = { type : "value_domains", as : "json", val : $("#structure_value_domains_domain_name").val() };
			$.post("loader.php", command, function(data){
				data = $.parseJSON(data);
				if(data.length == 0){
					$("#noDataValueDomainDialog").dialog('open');
				}else{
					$("#structure_value_domains_override").val(data[0].override);
					$("#structure_value_domains_category").val(data[0].category);
					$("#structure_value_domains_source").val(data[0].source);
					var html = "";
					var mTd = '<td class="clickable editable">';
					for(var i = 0; i < data.length; ++ i){
						var line = data[i];
						line.flag_active = '<input type="checkbox" ' + (line.flag_active == 0 ? "" : 'checked="checked"') + '/>'; 
						html += "<tr><td>" + deleteLine + "</td>" + mTd + line.value + "</td>" + mTd + line.language_alias + "</td>" + mTd + line.display_order + "</td><td>" + line.flag_active + "</td><td>" + line.structure_permissible_value_id + "</td></tr>";
					}
					$("#piton4 table:nth-child(4) tbody").html(html);
				}
			});
		});
		$("#piton4 table:nth-child(4) a.add").click(function(){
			var html = "<tr><td>" + deleteLine + "</td>";
			$("#piton4 table:nth-child(4) tfoot tr:first input").each(function(){
				if($(this).attr("type") == "checkbox"){
					html += '<td><input type="checkbox" ' + ($(this).prop("checked") ? 'checked="checked"' : "") + "/></td>";
				}else if($(this).attr("type") != "hidden"){
					html += '<td class="clickable editable">' + $(this).val() + "</td>";
					
				}
			});
			html += "<td></td></tr>";
			$("#piton4 table:nth-child(4) tbody").append(html);
			return false;
		});
		
		$("#clearAutoBuildTableValueDomain").click(function(){
			$("#piton4 table:nth-child(4) tbody").html("");
		});
		$("#generateSQLValueDomain").click(function(){
			if($("#structure_value_domains_domain_name").val().length == 0){
				flashColor($("#structure_value_domains_domain_name"), "#f00");
			}else{
				var toSend = new Object();
				toSend.domain_name = $("#structure_value_domains_domain_name").val(); 
				toSend.override = $("#structure_value_domains_override").val();
				toSend.category = $("#structure_value_domains_category").val();
				toSend.source = $("#structure_value_domains_source").val();
				toSend.rows = new Array();
				//as of jQuery 1.7.1, if the "tr" part is within the original call, some browsers return nothing
				$("#piton4 table:nth-child(4) tbody").find("tr").each(function(){
					var tds = $(this).find("td");
					var currentRow = new Object();
					currentRow.value = $(tds[1]).html();
					currentRow.language_alias = $(tds[2]).html();
					currentRow.display_order = $(tds[3]).html();
					currentRow.flag_active = $(tds[4]).find("input").prop("checked") ? 1 : 0;
					currentRow.id = $(tds[5]).html();
					toSend.rows.push(currentRow);
				});
				$.post("sqlGeneratorValueDomain.php", toSend, function(data){
					console.log(data);
					$("#resultZone").val($("#resultZone").val() + data + "\n");
				});
			}
			return false;
		});
	});
			

	//Structure build - update display
	$(".add.autoBuild2 span").html("Add row");
	$(".add.autoBuild2").click(function(){
		addLine();
		return false;
	});
	$(".add.autoBuild2").parent().append(
			'&nbsp;<a href="#" class="ui-state-default ui-corner-all button_link ignoreButton" style="display: none;"><span class="button_icon ui-icon ui-icon-arrowreturn-1-w"></span><span>Ignore changes</span></a>&nbsp;'
			+ '<a href="#" class="ui-state-default ui-corner-all button_link deleteButton" style="display: none;"><span class="button_icon ui-icon ui-icon-close"></span><span>Delete</span></a>'
	);
	
	$("a.autoBuild1").each(function(){
		$(this).children("span:nth-child(2)").html("Load structure");
		$(this).children("span:nth-child(1)").removeClass("ui-icon-plus");
		$(this).children("span:nth-child(1)").addClass("ui-icon-arrowthickstop-1-s");
		$(this).click(function(){
			if($("#autoBuild1_alias").val().length == 0){
				flashColor($("#autoBuild1_alias"), "#f00");
			}else{
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
			'No': function(){
				$(this).dialog('close');
			},
			'Yes': function(){
					$(this).dialog('close');
					var val = 'json={"val" : "' + $("#autoBuild1_alias").val() + '", "type" : "autoBuildData"}';
					$.ajax({ url: "autoBuildLoader.php", data: val, success: function(data){
						if(data.length > 0){
							if(data.indexOf("<tr>") > 10){
								$("#duplicateFieldsMsg").html(data.substr(0, data.indexOf("<tr>")));
								$("#duplicateFieldsDialog").dialog('open');
								data = data.substr(data.indexOf("<tr>"));
							}
							$("#autoBuild2").children("tbody").children("tr").remove();
							$("#autoBuild2").children("tbody").append(data).find("td:nth-child(1)").prepend('<a href="#no" class="deleteLine">(x)</a> ');
							$("#autoBuild2 tbody td:first-child").addClass("first_td");
							$("#autoBuild2 tbody td:not(.first_td)").addClass("clickable").addClass("editable");
							$("#autoBuild2").children("tbody").find("input[type=checkbox]").click(function(e){e.stopPropagation();});
							$("#autoBuild2").trigger('update');
							calculateAutoBuild2LeftMargin();
						}else{
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
			'Close': function(){
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
			'Close': function(){
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
			'Close': function(){
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
			'Close': function(){
				$(this).dialog('close');
			}, 'Add': function(){
				var txt = $.trim($("#addFromTextAreaDialog textarea").val()).split("\n");
				if(txt.length > 0){
					var single = txt[0].indexOf("\t") == -1;
					$("#struct_val_domain_flag_active").prop("checked", true);
					if(single){
						for(var i = 0; i < txt.length; ++ i){
							$("#struct_val_domain_value").val(txt[i]);
							$("#struct_val_domain_language_alias").val(txt[i]);
							$("#struct_val_domain_display_order").val(0);
							$("#struct_val_domain_flag_active").prop("checked", true);
							$("#piton4 table:nth-child(4) a.add").click();
						}
					}else{
						for(var i = 0; i < txt.length; ++ i){
							var part = txt[i].split("\t");
							var err = "";
							if(part.length == 3){
								$("#struct_val_domain_value").val(part[0]);
								$("#struct_val_domain_language_alias").val(part[1]);
								$("#struct_val_domain_display_order").val(part[2]);
								$("#piton4 table:nth-child(4) a.add").click();
							}else{
								err += txt[i] + "\n";
							}
							if(err.length > 0){
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
	
	$(".ignoreButton").click(function(){
		ignoreChanges();
		return false;
	});
	
	$(".deleteButton").click(function(){
		deleteRow();
		return false;
	});

	$("#permissibleValuesCtrl").click(function(){
		var val = 'json={"val" : "", "type" : "structure_permissible_values"}';
		$.ajax({ url: "loader.php", data: val, success: function(data){
	        $("#result").html(data);
		}});
		return false;
	});

	$(".add.structures").parent().parent().append("<td><a href='#' id='copy_structure' class='ui-state-default ui-corner-all button_link '><span class='button_icon ui-icon ui-icon-copy'></span>Copy structure with old_id</a><input id='structures_copy_old_id'/></td>");
	$("#copy_structure").click(function(){
		$(".add.structures").click();
		$("textarea").val($("textarea").val()
			+ "INSERT INTO structure_formats (`old_id`, `structure_id`, `structure_old_id`, `structure_field_id`, `structure_field_old_id`, `display_column`, `display_order`, `language_heading`, `flag_override_label`, `language_label`, `flag_override_tag`, `language_tag`, `flag_override_help`, `language_help`, `flag_override_type`, `type`, `flag_override_setting`, `setting`, `flag_override_default`, `default`, `flag_add`, `flag_add_readonly`, `flag_edit`, `flag_edit_readonly`, `flag_search`, `flag_search_readonly`, `flag_datagrid`, `flag_datagrid_readonly`, `flag_index`, `flag_detail`) "
			+ "(SELECT CONCAT('" + $("#structures_old_id").val() + "_', `structure_field_old_id`), (SELECT id FROM structures WHERE old_id='" + $("#structures_old_id").val() + "'), '" + $("#structures_old_id").val() + "', `structure_field_id`, `structure_field_old_id`, `display_column`, `display_order`, `language_heading`, `flag_override_label`, `language_label`, `flag_override_tag`, `language_tag`, `flag_override_help`, `language_help`, `flag_override_type`, `type`, `flag_override_setting`, `setting`, `flag_override_default`, `default`, `flag_add`, `flag_add_readonly`, `flag_edit`, `flag_edit_readonly`, `flag_search`, `flag_search_readonly`, `flag_datagrid`, `flag_datagrid_readonly`, `flag_index`, `flag_detail` FROM structure_formats WHERE structure_old_id='" + $("#structures_copy_old_id").val() + "');\n");
		return false;
	});
	$("input.gen").change(function(){
		generate();
	});

	$("#structure_search").keypress(function(event){
		if(event.keyCode == 13){
			var val = 'json={"val" : "' + $("#structure_search").val() + '", "type" : "structures"}';
			$.ajax({ url: "loader.php", data: val, success: function(data){
				$("#result").html(data);
				setStructureGetField();
			}});
		}
	});
	
	$("#value_domains_search").keypress(function(event){
		if(event.keyCode == 13){
			var val = 'json={"val" : "' + $("#value_domains_search").val() + '", "type" : "value_domains"}';
			$.ajax({ url: "loader.php", data: val, success: function(data){
				$("#valueDomainResult").html(data);
				
			}});
		}
	});

	$(".autoincrementButton").click(function(){
		incrementField($(this).parent().children("input"));
		return false;
	});

	$('#structure_fields_model').jsonSuggest(function(text, wildCard, caseSensitive, notCharacter){}, 
			{ type: 'GET',
			url: 'suggest.php',
			dataName: "json",
			ajaxResults: true,
			minCharacters : 1,
			width: 240,
			format: function(inputTxt){
					return '{"val" : "' + inputTxt + '", "fetching" : "model", "plugin" : "' + $('#structure_fields_plugin').val() + '" }';
				}
			}
		);
	$('#structure_fields_plugin').jsonSuggest(function(text, wildCard, caseSensitive, notCharacter){}, 
			{ type: 'GET',
			url: 'suggest.php',
			dataName: "json",
			ajaxResults: true,
			minCharacters : 1,
			width: 240,
			format: function(inputTxt){
					return '{"val" : "' + inputTxt + '", "fetching" : "plugin" }';
				}
			}
		);
	
	$('#autoBuild2_model').jsonSuggest(function(text, wildCard, caseSensitive, notCharacter){}, 
			{ type: 'GET',
			url: 'suggest.php',
			dataName: "json",
			ajaxResults: true,
			minCharacters : 1,
			width: 240,
			format: function(inputTxt){
					return '{"val" : "' + inputTxt + '", "fetching" : "model", "plugin" : "' + $('#autoBuild2_plugin').val() + '" }';
				}
			}
		);
	$('#autoBuild2_plugin').jsonSuggest(function(text, wildCard, caseSensitive, notCharacter){}, 
			{ type: 'GET',
			url: 'suggest.php',
			dataName: "json",
			ajaxResults: true,
			minCharacters : 1,
			width: 240,
			format: function(inputTxt){
					return '{"val" : "' + inputTxt + '", "fetching" : "plugin" }';
				}
			}
		);	

	$("#structure_fields_type").jsonSuggest(structureTypes);
	$("#autoBuild2_type").jsonSuggest(structureTypes);
	
	$("#queryBuilder").tabs({selected: 1});
	$("#databaseExplorer").tabs();
	
	$('.ui-state-default').hover(function(){
		$(this).addClass('ui-state-hover');
	}, function(){
		$(this).removeClass('ui-state-hover');
	});
	
	$("#generateSQL").click(function(){
		var proceed = true;

		if($("#autoBuild1_alias").val().length == 0){
			flashColor($("#autoBuild1_alias"), '#f00');
			proceed = false;
		}
		if(proceed){
			var global = '{ "alias" : "' + $("#autoBuild1_alias").val() + '", '
						+ ' "language_title" : "' + $("#autoBuild1_language_title").val() + '"}';
			var fields = '[ ';
			var headerArr = $("#autoBuild2").children("thead").children("tr").children("th");
			$("#autoBuild2").children("tbody").children("tr").each(function(){
				var fieldsArr = $(this).children("td");
				fields += '{ ';
				for(var i = 0; i < fieldsArr.length; ++ i){
					var fieldVal = null;
					if($(headerArr[i]).hasClass("checkbox")){
						fieldVal = $(fieldsArr[i]).children("input").attr("checked") ? 1 : 0;
					}else{
						fieldVal = $(fieldsArr[i]).html(); 
						if(i == 0){
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
			$.ajax({ url: "sqlGenerator.php", data: val, type: "POST", success: function(data){
				$("#resultZone").val($("#resultZone").val() + data + "\n");
			}});
		}
		return false;
	});
	
	$("th").each(function(){
		if($(this).html().length > 0){
			$(this).data("name", $(this).html()).html($(this).html().replace(/_/g, "<br/>"));
		}
	});
	
	$("#clearAutoBuildTable").click(function(){
		if(confirm("Are you sure you want to clear the table?")){
			$("#autoBuild2 tbody").html("");
		}
	});
	
	calculateAutoBuild2LeftMargin();
	
	$(document).delegate("#createAll", "click", function(){
		var toIgnore = [ "id", "created", "created_by", "modified", "modified_by", "deleted" ];
		$("#tableResult").find("tr").each(function(){
			if($.inArray($(this).find("td:first").html(), toIgnore) == -1){
				$(this).click();
				addLine();
			}
		});

	}).delegate(".clickable.editable", "click", function(event){
		fieldToggle(this);
	}).delegate("input[type=checkbox]", "click", function(event){
		event.stopPropagation();
	}).delegate(".deleteLine", "click", function(event){
		$(this).parents("tr:first").remove();
		event.stopPropagation();
		return false;
	});
});

function flashColor(item, color){
	var timer = 80;
	$(item).animate({
		"background-color": color
	  }, timer, function() {
	    // Animation complete.
		    $(item).animate({
				"background-color": "#fff"
			  }, timer, function() {
				  $(item).animate({
						"background-color": color
					  }, timer, function() {
						  $(item).animate({
								"background-color": "#fff"
							  }, timer)
					  })
			  })
	  });	
}

function toggleList(name){
	$("#structuresLst").css("display", "none");
	$("#tablesLst").css("display", "none");
	$("#structure_fieldsLst").css("display", "none");
	$("#structure_value_domainsLst").css("display", "none");
	$("#" + name + "Lst").css("display", "inline-block");
}

function toggleCreate(name){
	$(".create").css("display", "none");
	$("." + name + "Div").css("display", "block");
}

function genStructureFormatOldId(){
	$("#structure_formats_old_id").val($("#structure_formats_structure_old_id").val() + "_" + $("#structure_formats_structure_field_old_id").val()); 
}

function setStructureGetField(){
	$("#structureResult").children("table").children("tbody").children("tr").each(function(){
		$(this).click(function(){
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

function setTableGetField(){
	$("#tableResult").children("table").children("tbody").children("tr").each(function(){
		$(this).click(function(){
    		var field = $(this).find(".Field").html();
			$("#structure_fields_field").val(field);
			$("#structure_fields_language_label").val(field.replace(/_/g, " "));
			$("#autoBuild2_field").val(field);
			$("#autoBuild2_language_label").val(field.replace(/_/g, " "));
			$("#structure_fields_tablename").val($("#tablename").html());
			$("#autoBuild2_tablename").val($("#tablename").html());
			
			if($("#table_autotype").attr("checked")){
				var type = $(this).find(".Type").html();
				var outType = "";
				if(type.match(/^[a-z]*int\(\d+\)/)){
					//integer
					outType = type.match(/^[a-z]*int\(\d+\) unsigned$/) ? "integer_positive" : "integer";
				}else if(type.match(/^(float|double|decimal)(\(\d+,?\d*\))?/)){
					//float
					outType = type.match(/^(float|double|decimal)(\(\d+,?\d*\))? unsigned$/) ? "float_positive" : "float";
				}else if(type == "date"){
					outType = "date";
				}else if(type == "datetime"){
					outType = "datetime";
				}else{
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

function generate(){
	genStructureFormatOldId();
	$("#structure_formats_structure_id").val("(SELECT id FROM structures WHERE old_id = '" + $("#structure_formats_structure_old_id").val() + "')");
	$("#structure_formats_structure_field_id").val("(SELECT id FROM structure_fields WHERE old_id = '" + $("#structure_formats_structure_field_old_id").val() + "')");
}

function isNumeric(form_value){ 
    if (form_value.match(/^\d+$/) == null){ 
        return false; 
    }else{ 
        return true;
    }
} 

function incrementField(field){
	//var val = $(this).parent().children("input").val();
	var val = $(field).val();
	var prefix = "";
	
	if(isNumeric(val)){
		$(field).val(parseInt(val, 10) + 1);
		flashColor($(field), "#0f0");
		$(field).trigger('change');
	}else if(val.lastIndexOf("-") > -1){
		prefix = val.substr(0, val.lastIndexOf("-") + 1);
		val = val.substr(val.lastIndexOf("-") + 1);
		var valLength = val.length;
		val = (parseInt(val, 10) + 1) + "";
		while(val.length < valLength){
			val = 0 + val;
		}
		$(field).val(prefix + val);
		flashColor($(field), "#0f0");
		$(field).trigger('change');
	}else{
		flashColor($(field), "#f00");
	}

}

function addLine(){
	var line = "<tr>";
	var valid = true;
	$(".add.autoBuild2").parent().parent().parent().parent().children("tfoot").children("tr:first").children("td").each(function(){
		var val = "";
		if($(this).children("input").hasClass("notEmpty") && $(this).children("input").val().length == 0){
			flashColor($(this).children("input"), "#f00");
			$(this).children("input").focus();
			valid = false;
		}
		if($(this).children("input").attr("type") == "checkbox"){
			val = '<input type="checkbox"' + ($(this).children("input").attr("checked") ? ' checked="checked"' : "") + '/>';
		}else{
				val = $(this).children("input").val();
		}
		line += "<td class='clickable editable'>" + val + "</td>";
	});
	line += "</tr>";
	if(valid){
		var table = $(".add.autoBuild2").closest("table"); 
		$(table).children("tbody").append(line);
		$(table).find("tbody tr:last td:first").removeClass("clickable").removeClass("editable").prepend('<a href="#no" class="deleteLine">(x)</a> ');
		$(table).find("tfoot tr:first td").each(function(){
			var field = $(this).children("input"); 
			if($(field).hasClass("autoBuildIncrement")){
				incrementField(field);
			}
			if($(field).hasClass("clear")){
				$(field).val("");
			}
		});
		toggleEditMode(false);
		$("#autoBuild2").trigger('update');
		calculateAutoBuild2LeftMargin();
	}
}

function deleteRow(){
	toggleEditMode(false);
	$("#autoBuild2_sfi_id").val("");
	$("#autoBuild2_sfo_id").val("");
	$("#autoBuild2_field").val("");
	calculateAutoBuild2LeftMargin();
}

function toggleEditMode(editMode){
	if(editMode && $("a.autoBuild2").children("span:nth-child(1)").hasClass("ui-icon-plus")){
		$("a.autoBuild2").children("span:nth-child(1)").removeClass("ui-icon-plus");
		$("a.autoBuild2").children("span:nth-child(1)").addClass("ui-icon-disk");
		$(".ignoreButton").show();
		$(".deleteButton").show();
		$("a.autoBuild2").children("span:nth-child(2)").html("Save");
	}else if(!editMode && $("a.autoBuild2").children("span:nth-child(1)").hasClass("ui-icon-disk")){
		$("a.autoBuild2").children("span:nth-child(1)").removeClass("ui-icon-disk");
		$("a.autoBuild2").children("span:nth-child(1)").addClass("ui-icon-plus");
		$(".ignoreButton").hide();
		$(".deleteButton").hide();
		$("a.autoBuild2").children("span:nth-child(2)").html("Add");
	}		
}

function calculateAutoBuild2LeftMargin(){
	var maxWidth = 0;
	$("#autoBuild2 thead th:nth-child(1), #autoBuild2 tbody td:nth-child(1), #autoBuild2 tfoot tr:nth-child(1) td:nth-child(1)").each(function(){
		maxWidth = Math.max($(this).width(), maxWidth);
	});
	
	$("#autoBuild2").css("margin-left", maxWidth + "px");
}

function fieldToggle(field){
	if($(field).is(".clickable.editable")){
		if($(field).find('input[type=checkbox]').length){
			$(field).find('input[type=checkbox]').attr("checked", !$(field).find('input[type=checkbox]').attr("checked"));
		}else if($(field).find('input[type=text]').length == 0){
			var val = $(field).html(); 
			$(field).html('<input id="currentMod" type="text" value="" style="width: 100%;"/>');
			$(field).find("input").val(val).data("orgVal", val).select().focusout(function(){
				$(field).html($(this).val());
			}).keyup(function(event){
				if(event.keyCode == 27){
					//esc
					$(this).val($(this).data("orgVal")).focusout();
				}
			}).keydown(function(event){
				if(event.keyCode == 9){
					//tab
					//
					var elem = event.shiftKey ? $(this).closest('td').prev() : $(this).closest('td').next();
					$('#currentMod').focusout();
					if($(elem).find('input[type=checkbox]').length){
						$(elem).find('input[type=checkbox]').focus();
					}else{
						fieldToggle(elem);
					}
					return false;
				}			
			});
		}
	}
}