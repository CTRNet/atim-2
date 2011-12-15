var structureTypes = '[{"text" : "autocomplete"},{"text" : "checkbox"},{"text" : "date"},{"text" : "datetime"},{"text" : "file"},{"text" : "hidden"},{"text" : "input"},{"text" : "input-readonly"},{"text" : "number"},{"text" : "password"},{"text" : "radio"},{"text" : "radiolist"},{"text" : "select"},{"text" : "textarea"},{"text" : "time"},{"text":"integer"},{"text":"integer_positive"},{"text":"float"},{"text":"float_positive"},{"text":"yes_no"}]';
var tmpLine = "";
var lineWithoutChanges = "";


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
	
	$(".structLink").click(function(){
		var oldId = $(this).attr('href').substring(1);
		var val = 'json={"val" : "' + oldId + '", "type" : "structures"}';
		$.ajax({ url: "loader.php", data: val, success: function(data){
			$("#structureResult").html(data);
			setStructureGetField();
		}});
		return false;
	});

	$(".structLinkAdd").click(function(){
		var oldId = $(this).attr('href').substring(1);
		var val = 'json={"val" : "' + oldId + '", "type" : "structures"}';
		$("#selectedStruct").html(oldId);
		$("#structure_formats_structure_old_id").val(oldId);
		genStructureFormatOldId();
		$("#structure_formats_structure_id").val("(SELECT id FROM structures WHERE old_id = '" + oldId + "')");
		$.ajax({ url: "loader.php", data: val, success: function(data){
	        $("#structureResult").html(data);
	        setStructureGetField();
	    }});
		return false;
	});

	$(".tableLink").click(function(){
		var tableName = $(this).attr('href').substring(1);
		var val = 'json={"val" : "' + tableName + '", "type" : "tables"}';
		$.ajax({ url: "loader.php", data: val, success: function(data){
	        $("#tableResult").html(data);
	        setTableGetField();
	      }});
		return false;
	});

	$(".tableLinkAdd").click(function(){
		var tableName = $(this).attr('href').substring(1);
		$("#selectedTable").html(tableName);
		$("#structure_fields_tablename").val(tableName);
		return false;
	});

	$(".fieldLink").click(function(){
		var modelName = $(this).attr('href').substring(1);
		var val = 'json={"val" : "' + modelName + '", "type" : "fields"}';
		$.ajax({ url: "loader.php", data: val, success: function(data){
	        $("#fieldResult").html(data);
	        $("#fieldResult").children("table").children("tbody").children("tr").each(function(){
	    		$(this).click(function(){
		    		var oldId = $(this).find(".old_id").html();
	    			$("#selectedField").html(oldId);
	    			$("#structure_formats_structure_field_old_id").val(oldId);
	    			genStructureFormatOldId();
	    			$("#structure_formats_structure_field_id").val("(SELECT id FROM structure_fields WHERE old_id = '" + oldId + "')");
	    		});
	    		$(this).addClass("clickable");
	      	});
		}});
		return false;
	});

	$(".vDomainLink").click(function(){
		var domainName = $(this).attr('href').substring(1);
		var val = 'json={"val" : "' + domainName + '", "type" : "value_domains"}';
		$.ajax({ url: "loader.php", data: val, success: function(data){
	        $("#valueDomainResult").html(data);
		}});
		return false;
	});

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
	
	$("input[type=radio]").click(function(){
		toggleList($(this).val());
		toggleCreate($(this).val());
		return false;
	});

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
				var autoincrement = '<a href="#" class="autoincrementButton">[+]</a>';
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

	$(".add:not(.custom)").click(function(){
		var str = "INSERT INTO " + $(this).attr("name") + "(";
		$(this).parent().parent().parent().parent().children("thead").children("tr").children("th").each(function(){
			str += "`" + $(this).html().replace(/<br\>/g, "_") + "`, ";
		});
		str = str.substring(0, str.length - 2) + ") VALUES (";
		var valid = true;
		$(this).parent().parent().parent().parent().children("tfoot").children("tr:first").children("td").each(function(){
			if($(this).children("input").hasClass("notEmpty") && $(this).children("input").val().length == 0){
				flashColor($(this).children("input"), "#f00");
				$(this).children("input").focus();
				valid = false;
			}else if($(this).children("input").val().indexOf("SELECT") > -1 || $(this).children("input").val().toUpperCase() == "NULL"){
				str += "" + $(this).children("input").val() + ", ";
			}else if($(this).children("input").attr("type") == "checkbox"){
				if($(this).children("input").attr("checked")){
					str += "'" + $(this).children("input").val() + "', ";
				}else{
					str += "'0', ";
				}
			}else{
				str += "'" + $(this).children("input").val() + "', ";
			}
		});
		str = str.substring(0, str.length - 2) + ");";
		if(valid){
			$("textarea").val($("textarea").val() + str + "\n");
			$("textarea")[0].scrollTop = $("textarea")[0].scrollHeight;
		}
		return false;
	});

	$(".add.struct_val_domain").click(function(){
		$("textarea").val($("textarea").val() 
			+ 'INSERT IGNORE INTO structure_permissible_values (`value`, `language_alias`) VALUES("' + $("#struct_val_domain_value").val() + '", "' + $("#struct_val_domain_language_alias").val() + '");\n'
			+ 'INSERT INTO structure_value_domains_permissible_values (`structure_value_domain_id`, `structure_permissible_value_id`, `display_order`, `flag_active`) VALUES((SELECT id FROM structure_value_domains WHERE domain_name="' + $("#struct_val_domain_domain_name").val() + '"),  (SELECT id FROM structure_permissible_values WHERE value="' + $("#struct_val_domain_value").val() + '" AND language_alias="' + $("#struct_val_domain_language_alias").val() + '"), "' + $("#struct_val_domain_display_order").val() + '", "' + $("#struct_val_domain_flag_active").val() + '");\n'  
			+ "\n");
		return false;
	});
	
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
							$("#autoBuild2").children("tbody").append(data);
							$("#autoBuild2 tbody td:first-child").addClass("first_td");
							$("#autoBuild2 tbody td:not(.first_td)").addClass("clickable").addClass("editable");
							initDeleteLine("#autoBuild2 tbody tr");
							$("#autoBuild2 a.deleteLine").click(function(e){
								$(this).parent().parent().remove();
								e.stopPropagation();
							});
							$("#autoBuild2").children("tbody").find("input[type=checkbox]").click(function(e){e.stopPropagation()});
							$("#autoBuild2").trigger('update');
							calculateAutoBuild2LeftMargin();
						}else{
							$("#noDataDialog").dialog('open');
						}
					}});
			}
		}
	});
		
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
	
	$("#saveDeleteDialog").dialog({
		resizable: false,
		height: 170,
		width: 400,
		modal: true,
		autoOpen: false,
		buttons: {
			'Cancel': function(){
				$(this).dialog('close');
			},
			'Delete': function(){
				deleteRow();
				editLine(tmpLine);
				$(this).dialog('close');
			},
			'Ignore changes': function(){
				ignoreChanges();
				editLine(tmpLine);
				$(this).dialog('close');
			},
			'Save': function(){
				addLine();
				editLine(tmpLine);
				$(this).dialog('close');
			}
		}
	});
	
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
	////<a href="#a" class="ui-state-default ui-corner-all loginButton"><span class="button_icon ui-icon ui-icon-power"></span>Connecter</a>
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
	
	$("#queryBuilder").tabs({ selected : 4, disabled: [0, 1, 2] });
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
				for(i = 0; i < fieldsArr.length; ++ i){
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
				$("textarea").val($("textarea").val() + data + "\n");
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
		$("#tableResult").find("tr").each(function(){
			$(this).click();
			addLine();
		});

	}).delegate(".clickable.editable", "click", function(event){
		fieldToggle(this);
	}).delegate("input[type=checkbox]", "click", function(event){
		event.stopPropagation();
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
		initDeleteLine($(table).find("tbody tr:last"));
		$(table).find("tbody tr:last td:first").removeClass("clickable").removeClass("editable");
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

//function editLine(line){
//	if($("a.autoBuild2").children("span:nth-child(1)").hasClass("ui-icon-disk")){
//		tmpLine = line;
//		$("#saveDeleteDialog").dialog('open');
//	}else{
//		lineWithoutChanges = line;
//		var cells = $(line).children("td");
//		$(cells[0]).html($(cells[0]).html().substr(41));
//		var inputs = $(line).parent().parent().children("tfoot").children("tr:first").children("td");
//		for(var i = 0; i < cells.length; ++ i){
//			if($(inputs[i]).children("input").attr("type") == "checkbox"){
//				$(inputs[i]).children("input").attr("checked", $(cells[i]).find("input").attr("checked"));
//			}else{
//				$(inputs[i]).children("input").val($(cells[i]).html());
//			}
//		}
//		$(line).remove();
//		toggleEditMode(true);
//		$("#autoBuild2").trigger('update');
//		$("a.autoBuild2").children("span:nth-child(2)").html("Save");
//	}
//	calculateAutoBuild2LeftMargin();
//}

//function ignoreChanges(){
//	toggleEditMode(false);
//	$("#autoBuild2").children("tbody").append(lineWithoutChanges);
//	$(lineWithoutChanges).click(function(){
//		editLine(this);
//		calculateAutoBuild2LeftMargin();
//	});
//	initDeleteLine($("#autoBuild2 tbody tr").last());
//	$("#autoBuild2 tbody tr input[type=checkbox]").click(function(e){ e.stopPropagation();});
//	$("#autoBuild2").trigger('update');
//	calculateAutoBuild2LeftMargin();
//}

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

function initDeleteLine(scope){
	$(scope).each(function(){
		$(this).find("td").first().prepend('<a href="#no" class="deleteLine">(X)</a> ');
	});
	$(scope).find("a.deleteLine").click(function(e){
		$(this).parent().parent().remove();
		e.stopPropagation();
	});
}

function fieldToggle(field){
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