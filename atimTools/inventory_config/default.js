function getParentElement(currElement, parentName){
	do{
		currElement = $(currElement).parent();
		nodeName = currElement.length > 0 ? currElement[0].nodeName : "undefined";
	}while(nodeName != parentName && nodeName != "undefined");
	return currElement;
}

function requestSql(){
	var samplesIdArr = new Array();
	var aliquotsIdArr = new Array();
	var realiquotsIdArr = new Array();
	var samplesJsonArr = new Array();
	var aliquotsJsonArr = new Array();
	var realiquotsJsonArr = new Array();
	
	$(".sample").each(function(){
		var json = getJson($(this).attr("class"));
		if(samplesIdArr.indexOf(json.id) == -1){
			samplesJsonArr.push('{ "id" : ' + json.id + ', "disabled" : ' + $(this).hasClass("disabled") + '}');
			samplesIdArr.push(json.id);
		}
	});
	$(".aliquot").each(function(){
		var json = getJson($(this).attr("class"));
		if(aliquotsIdArr.indexOf(json.id) == -1){
			aliquotsJsonArr.push('{ "id" : ' + json.id + ', "disabled" : ' + $(this).hasClass("disabled") + '}');
			aliquotsIdArr.push(json.id);
		}
	});
	$(".realiquot").each(function(){
		var json = getJson($(this).attr("class"));
		if(realiquotsIdArr.indexOf(json.id) == -1){
			realiquotsJsonArr.push('{ "id" : ' + json.id + ', "disabled" : ' + $(this).hasClass("disabled") + '}');
			realiquotsIdArr.push(json.id);
		}
	});
	
	var data = 'json={ "samples" : [' + samplesJsonArr.join(",") + '], "aliquots" : [' + aliquotsJsonArr.join(",") + '], "realiquots" : [' + realiquotsJsonArr.join(",") + ']}';
	$.post("sqlGenerator.php", data, function(reply){
		$("#out").html(reply + "<br/>");
	});
}

$(function(){
	if($("input:radio[value=" + currentMode + "]").length == 1){
		$("input:radio[value=" + currentMode + "]").prop("checked", true);
	}
	
	$("#dbSelect, input:radio").change(function(){
		document.location = "index.php?db=" + $("#dbSelect").val() + "&display_mode=" + $("input:radio:checked").val(); 
	});
	
	
	var jg = new jsGraphics($("body")[0]);
	jg.setStroke(1);
	jg.setColor("black");
	var pOffset = null;
	var cOffset = null;
	var halfHeight = $(".sample_cell").outerHeight() / 2;
	var width = $(".sample_cell").outerWidth();
	var tmp = $(".sample_cell");
	var spacing = ($(tmp[1]).offset().left - $(tmp[0]).offset().left - width) / 2;
	var spacingFloor  = Math.floor(spacing);
	var spacingCeil = Math.ceil(spacing); 
	$(".sample_cell").each(function(){
		var parent = getParentElement($(this), "UL");
		if(parent != "undefined"){
			parent = getParentElement($(parent), "LI");
			parent = $(parent).find(".sample_cell:first");
			if(parent.length == 1){
				cOffset = $(this).offset();
				pOffset = $(parent).offset();
				//end
				jg.drawLine(cOffset.left - spacingFloor, cOffset.top + halfHeight, cOffset.left, cOffset.top + halfHeight);
				//arrow
				jg.fillPolygon(new Array(cOffset.left - 4, cOffset.left, cOffset.left - 4), new Array(cOffset.top + halfHeight - 4, cOffset.top + halfHeight, cOffset.top + halfHeight + 4));
				
				//middle
				jg.drawLine(pOffset.left + width, pOffset.top + halfHeight, pOffset.left + width + spacingCeil, pOffset.top + halfHeight);
				//start
				jg.drawLine(pOffset.left + width + spacingCeil, pOffset.top + halfHeight, cOffset.left - spacingFloor, cOffset.top + halfHeight); 
			}
		}
	});
	
	jg.paint();

	$(".sample_cell").click(function(){
		var parent = getParentElement($(this), "LI");
		var json = getJson($(parent).attr("class"));
		if($(parent).hasClass("disabled")){
			$(".sample_" + json.id).removeClass("disabled");
		}else{
			$(".sample_" + json.id).addClass("disabled");
		}
		requestSql();
	});
	$(".aliquot_cell").click(function(){
		var parent = getParentElement($(this), "LI");
		var json = getJson($(parent).attr("class"));
		if($(parent).hasClass("disabled")){
			$(".aliquot_" + json.id).removeClass("disabled");
		}else{
			$(".aliquot_" + json.id).addClass("disabled");
		}
		requestSql();
	});
	
	$(".realiquots li").click(function(){
		var json = getJson($(this).attr("class"));
		if($(this).hasClass("disabled")){
			$(".realiquot_" + json.id).removeClass("disabled");
		}else{
			$(".realiquot_" + json.id).addClass("disabled");
		}
		requestSql();
	});
	
	//derivative override
	var maxWidth = "0";
	$(".derivative").each(function(){
		maxWidth = Math.max(maxWidth, $(this).width());
	});
	$(".derivative").each(function(){
		$(this).width(maxWidth);
	});
	
	$(".derivative").click(function(){
		var json = getJson($(this).attr("class"));
		if($(this).hasClass("disabled")){
			$(this).removeClass("disabled");
			$(".derivative_" + json.id).each(function(){
				if($(this).hasClass("disabled")){
					$(this).find(".sample_cell:first").click();
				}
			});
		}else{
			$(this).addClass("disabled");
			$(".derivative_" + json.id).each(function(){
				if(!$(this).hasClass("disabled")){
					$(this).find(".sample_cell:first").click();
				}
			});
		}
	});
});