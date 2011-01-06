$(function(){
	$("li div").click(function(){
		if($(this).parent().attr("id") == "0"){
			alert("The root cannot be disabled");
		}else{
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
	});
});