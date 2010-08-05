function getJson(str){
	var startIndex = str.indexOf("{");
	if(startIndex > -1){
		return eval ('(' + str.substr(startIndex, str.lastIndexOf("}") - startIndex + 1) + ')');
	}
	return null;
}