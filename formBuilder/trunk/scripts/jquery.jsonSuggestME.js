/**
 * Based on the original jsonSuggest, this is the Mich Edition
 * @date 2010-03-01
 */

var jsonSuggestObject;
var jsonSuggestE;
var jsonSuggestThis;
var jsonSuggestTimeoutID = 0;
(function($){
	$.fn.jsonSuggest = function(searchData, settings){
		var defaults = {minCharacters:1,maxResults:undefined,wildCard:"",caseSensitive:false,notCharacter:"!",maxHeight:350,highlightMatches:true,onSelect:undefined,ajaxResults:false};
		settings = $.extend(defaults,settings);
		return this.each(

			function(){
				function regexEscape(txt,omit){
					var specials=['/','.','*','+','?','|','(',')','[',']','{','}','\\'];
					if(omit){
						for(var i = 0; i < specials.length; i++){
							if(specials[i] === omit){
								specials.splice(i,1);
							}
						}
					}
					var escapePatt = new RegExp('(\\'+specials.join('|\\')+')','g');
					return txt.replace(escapePatt,'\\$1');
				}

				var obj = $(this),wildCardPatt = new RegExp(regexEscape(settings.wildCard||''),'g');
				var results = $('<div />');
				var currentSelection;
				var pageX;
				var pageY;
				$(obj).bind("keydown", function(e){ 
					if(e.keyCode == 13){ 
						$(currentSelection).trigger('click');
					}else if(e.keyCode == 9){
						$(".jsonSuggestResults").css("display", "none");
					}
				});

				function selectResultItem(item){
					obj.val(item.text);
					$(results).html('').hide();
					if(typeof settings.onSelect === 'function'){
						settings.onSelect(item);
					}
				}

				function setHoverClass(el){
					$('div.resultItem',results).removeClass('hover');
					$(el).addClass('hover');
					currentSelection=el;
				}

				function buildResults(resultObjects, sFilterTxt){
					if(resultObjects.length == 0){
						$(".jsonSuggestResults").css("display", "none");
					}else{
						sFilterTxt = "(" + sFilterTxt + ")";
						var bOddRow = true;
						var i;
						var iFound = 0;
						var filterPatt = settings.caseSensitive ? new RegExp(sFilterTxt,"g") : new RegExp(sFilterTxt,"ig");
						$(results).html('');
						
						for(i = 0; i < resultObjects.length; i += 1){
							var item = $('<div />');
							var textOrg = resultObjects[i].text;
							var text = textOrg;
							if(settings.highlightMatches === true){
								text = text.replace(filterPatt,"<strong>$1</strong>");
							}
							$(item).append('<p class="text">' + text + '<input type="hidden" value="' + textOrg + '"/></p>');
							if(typeof resultObjects[i].extra === 'string'){
								$(item).append('<p class="extra">' + resultObjects[i].extra + '</p>');
							}
							if(typeof resultObjects[i].image === 'string'){
								$(item).prepend('<img src="' + resultObjects[i].image + '" />').append('<br style="clear:both;" />');
							}
							$(item).addClass('resultItem').addClass((bOddRow) ? 'odd' : 'even').click(function(n){
								return function(){
									selectResultItem(resultObjects[n]);
								};
							}(i)).mouseover(function(el){
								return function(){
									setHoverClass(el);
								};}(item));
							$(results).append(item);
							bOddRow = !bOddRow;
							iFound +=1;
							if(typeof settings.maxResults === 'number' && iFound >= settings.maxResults){
								break;
							}
						}
						if($('div',results).length > 0 && !obj.attr("disabled")){
							currentSelection=undefined;
							$(results).show().css('height','auto');
	
							//div position
							if($(results).height()>settings.maxHeight){
								$(results).css({'overflow':'auto','height':settings.maxHeight+'px'});
							}
							
						}
					}
				}

				function runSuggest(e){
					this.previousValue = this.value;
					if(this.value.length < settings.minCharacters){
						$(results).html('').hide();
						return false;
					}
					var resultObjects = [];
					var sFilterTxt = (!settings.wildCard) ? regexEscape(this.value) : regexEscape(this.value,settings.wildCard).replace(wildCardPatt,'.*');
					var bMatch=true;
					var filterPatt;
					var i;
					if(settings.notCharacter && sFilterTxt.indexOf(settings.notCharacter) === 0){
						sFilterTxt = sFilterTxt.substr(settings.notCharacter.length,sFilterTxt.length);
						if(sFilterTxt.length > 0){
							bMatch = false;
						}
					}
					sFilterTxt = sFilterTxt || '.*';
					sFilterTxt = settings.wildCard ? '^' + sFilterTxt : sFilterTxt;
					filterPatt = settings.caseSensitive ? new RegExp(sFilterTxt) : new RegExp(sFilterTxt,"i");
					/*if(settings.ajaxResults === true){
						resultObjects = searchData(this.value, settings.wildCard, settings.caseSensitive, settings.notCharacter);
						if(typeof resultObjects === 'string'){
							resultObjects = JSON.parse(resultObjects);
						}
					}else{*/
						if(searchData){
							for(i = 0; i < searchData.length; i += 1){
								if(searchData[i] != undefined && filterPatt.test(searchData[i].text) === bMatch){
									resultObjects.push(searchData[i]);
								}
							}
						}
					//}
					buildResults(resultObjects,sFilterTxt);
				}

				function keyListenerTxt(e){
					switch(e.keyCode){
					//case 13:
					//	$(currentSelection).trigger('click');
					//	return false;
					case 40:
						if(typeof currentSelection === 'undefined'){
							currentSelection = $('div.resultItem:first', results).get(0);
						}else{
							currentSelection = $(currentSelection).next().get(0);
						}
						setHoverClass(currentSelection);
						if(currentSelection){
							$(results).scrollTop(currentSelection.offsetTop);
						}
						obj.val($(currentSelection).find("input").val());
						return false;
					case 38:
						if(typeof currentSelection === 'undefined'){
							currentSelection = $('div.resultItem:last', results).get(0);
						}else{
							currentSelection = $(currentSelection).prev().get(0);
						}
						setHoverClass(currentSelection);
						if(currentSelection){
							$(results).scrollTop(currentSelection.offsetTop);
						}
						obj.val($(currentSelection).find("input").val());
						return false;
					default:
						prepareToRun.apply(this, [e]);
						/*
						if(this.value.length >= settings.minCharacters){
							if(settings.ajaxResults){
								jsonSuggestE = e;
								jsonSuggestThis = this;
								$.ajax({
									type: settings.type,
									url: settings.url,
									data: settings.dataName + '=' + this.value.replace(/%/g, "%25").replace(/ /g, "%20"),
									dataType: 'json',
									async: true,
									success: function(msg){
										searchData = msg
										runSuggest.apply(jsonSuggestThis, [jsonSuggestE]);
									}
								});
							}else{
								runSuggest.apply(this, [e]);
							}
						}else{
							//TODO: delete copy of this
							$(results).html('').hide();
							return false;
						}*/
					}
				}
				
				function prepareToRun(e){
					if(this.value.length >= settings.minCharacters){
						if(settings.ajaxResults){
							jsonSuggestE = e;
							jsonSuggestThis = this;
							var value = this.value;
							if(settings.format){
								value = settings.format.apply(this, [this.value]);
							}
							$.ajax({
								type: settings.type,
								url: settings.url,
								data: settings.dataName + '=' + value.replace(/%/g, "%25").replace(/ /g, "%20"),
								dataType: 'json',
								async: true,
								success: function(msg){
									searchData = msg
									runSuggest.apply(jsonSuggestThis, [jsonSuggestE]);
								}
							});
						}else{
							runSuggest.apply(this, [e]);
						}
					}else{
						//TODO: delete copy of this
						$(results).html('').hide();
						return false;
					}
				}

				//original script code. Required a fix.
				//$(results).addClass('jsonSuggestResults').css({'top':(obj.position().top+obj.height()+500)+'px','left':obj.position().left+'500px','width':obj.width()+'px'}).hide();
				var width = (settings.width != undefined ? settings.width : obj.width);
				$(results).addClass('jsonSuggestResults').css({'position' : 'absolute', 'left' : obj.position().left + 'px', 'z-index' : 100, 'width' : width + 'px'}).hide();

				obj.after(results).keyup(keyListenerTxt).blur(
					function(e){
						var resPos = $(results).offset();
						resPos.bottom = resPos.top + $(results).height();
						resPos.right = resPos.left + 500 + $(results).width();
						if(pageY < resPos.top || pageY > resPos.bottom || pageX < resPos.left ||pageX > resPos.right){
							$(results).hide();
						}
					}
				).focus(
					function(e){
						$(results).css({'top':(obj.position().top+obj.height()+5)+'px','left':obj.position().left+'px'});
						if($('div',results).length > 0){
							if(this.previousValue == this.value){
								$(results).show();
							}else{
								runSuggest.apply(this, [e]);
							}
						}
					}
				).attr('autocomplete','off');$().mousemove(
					function(e){
						pageX=e.pageX;pageY=e.pageY;
				});

				if($.browser.opera){
					obj.keydown(function(e){
						if(e.keyCode===40){
							return keyListenerTxt(e);
						}
					});
				}
				settings.notCharacter = regexEscape(settings.notCharacter||'');
				if(!settings.ajaxResults){
					if(typeof searchData === 'function'){
						searchData=searchData();
					}
					if(typeof searchData === 'string'){
						searchData = JSON.parse(searchData);
					}
				}
			}
		);

};})(jQuery);




