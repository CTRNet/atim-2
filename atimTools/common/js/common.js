function getJson(str){
	var startIndex = str.indexOf("{");
	if(startIndex > -1){
		return eval ('(' + str.substr(startIndex, str.lastIndexOf("}") - startIndex + 1) + ')');
	}
	return null;
}

function initDbSelector(selector){
    $.getJSON("../common/databaseList.php", function(data){
        $(data["options"]).each(function(i, v){
            var html = '<option value="' + v + '">' + v + '</option>';
            $(selector).append(html);
        });
        $(selector + " option[value=" + data["selected"] + "]")
            .attr("selected", true);
     });
    $(selector).change(function(){
        document.location = "?db=" + $(this).children(":selected").html();
    });
}