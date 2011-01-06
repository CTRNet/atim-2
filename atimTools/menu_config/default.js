$(function(){
	$("li div").click(update);
});

function update(){
	$(this).parent().toggleClass("disabled");
	var json = new Array();
	$("#0 li").each(function(){
		json.push('{ "id" : "' + $(this).attr("id") + '", "parent" : "' + $(this).parent().parent().attr("id") + '", "disabled" : "' + $(this).hasClass("disabled") + '" }');
	});
	json = '[' + json.join(', ') + ']';
	$.post("sqlGenerator.php", "json=" + json, function(data){
		$("#out").html(data);
	});
}