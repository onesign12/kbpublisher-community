function ShowHelp(div, title, desc, width) {

	var width = (width) ? width : '';
	var div = document.getElementById(div);
	var html = '<div>';
	html += (title) ? '<b>' + title + '</b><br>' : '';
	html += desc;
	html += '</div>';
	
	div.style.margin = '0px 0px 0px 5px';
	//div.style.whiteSpace = 'nowrap';
	
	div.style.display = 'inline';
	div.style.position = 'absolute';
	div.style.width = width;
	div.style.backgroundColor = 'lightyellow';
	div.style.border = 'solid 1px black';
	div.style.padding = '3px';
	div.style.color = '#000000';
	div.innerHTML = html;
}

function HideHelp(div) {
	document.getElementById(div).style.display = 'none';
}

// function showGrowl(show, title, message) {
//     if(show) {
//         $.growl({title: title, message: message, duration: 4800});
//     }
// }

function showGrowl(show, title, message, style, fixed) {
    if(show) {
        var gStyle = (style) ? style : "default";
        var gOptions = {
            title: title,
            message: message,
            style: gStyle
        };
        
        if(fixed) {
            gOptions.fixed = 'true';
        } else {
            // gOptions.duration = 4800;
        }
        
        console.log(gOptions);
        $.growl(gOptions);
    }
}


function selectAll(action, id, values) {
	
	var obj = document.getElementById(id);
	
	if(values != null) {
		var values = values.split(',');
		var apos_action = (action) ? false : true;
		
		selectAll(apos_action, id);
		
		for (i=0; i<values.length; i++) {
			for (j=0; j<obj.options.length; j++) {
				if(values[i] == obj.options[j].value) {
					obj.options[j].selected = action;
					break;
				}
			}
		}
		
	} else {
		for (i=0; i<obj.options.length; i++) {
			obj.options[i].selected = action;
		}	
	}
}

function ShowDiv(div, display_block) {
	var div = document.getElementById(div);
	div.style.display = (display_block) ? 'block' : 'inline';
}

function HideDiv(div) {
	var div = document.getElementById(div);
	div.style.display = 'none';
}

function createCookie(name,value,days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	// document.cookie = name+"="+value+expires+"; path=/";
	document.cookie = name+"="+value+expires+"; path=/; SameSite=Lax";
}

function deleteCookie( name, path, domain ) {
	if ( getCookie( name ) ) document.cookie = name + "=" +
			( ( path ) ? ";path=" + path : "") +
			( ( domain ) ? ";domain=" + domain : "" ) +
			";expires=Thu, 01-Jan-1970 00:00:01 GMT";
}

function getCookie( name ) {
	var start = document.cookie.indexOf( name + "=" );
	var len = start + name.length + 1;
	if ( ( !start ) && ( name != document.cookie.substring( 0, name.length ) ) ) {
		return null;
	}
	if ( start == -1 ) return null;
	var end = document.cookie.indexOf( ";", len );
	if ( end == -1 ) end = document.cookie.length;
	return unescape( document.cookie.substring( len, end ) );
}

function goPage(page) {
	document.location.href = page;
	return false;
}

function browseSubmit(page, form_id, rewrite) {
	if(rewrite != 0 || !rewrite) {
		goPage(page);
	} else {
		document.getElementById(form_id).submit(true);
	}
}

function doBottomRate(val) {
	var f = document.getElementById('form_vote2');
	var f2 = document.getElementById('rate2');
	f2.value = val;
	f.submit(true);
}

function validateQuickSearch(message, fid) {
	var fid = (!fid) ? 'searchq' : fid;
	
    // if ($('#' + fid).is(':visible')) {
	    var f = document.getElementById(fid);
        if(isBlank(f.value)) {
            // alert(fid);
            // $.growl.error({title: "", message: message});
            f.focus();
            return false;
        }
    // }

	return true;	
}

//-------------------------------------------------------------------
// isBlank(value)
//   Returns true if value only contains spaces
//-------------------------------------------------------------------
function isBlank(val){
	if(val == null){ return true; }
	for(var i=0;i<val.length;i++) {
		if ((val.charAt(i)!=' ')&&(val.charAt(i)!="\t")&&(val.charAt(i)!="\n")&&(val.charAt(i)!="\r")){return false;}
	}
	return true;
}

function isCkEditorDataBlank(data) {
    data = data.replace(/&nbsp;/gi, '');
    data = data.replace(/<\/?[^>]+>/gi, '');
    data = data.trim();
    return !data.length;
}

function shareArticle(url) {    
    window.open(url, '' , 'status = 0, width = 650, height = 360, personalbar = 0, toolbar = 0, scrollbars = 1, resizable = 1');
    
    ModalManager.hide('panel_more');
}

function OverColor(obj, new_class, highlight_class, more_id) { 
	if(obj.className != highlight_class) {
		obj.className = new_class;
		if(more_id) {
			document.getElementById(more_id).className = new_class;
		}
	}
}

function Highlight(obj, def_class, new_class, more_id) { 
	if(obj.className == def_class) { obj.className=new_class; }
	else                           { obj.className=def_class; }
	if(more_id) {
		document.getElementById(more_id).className = obj.className;
	}
}

function RecordToDo() {
	return false;
}

/* forum */
// addInputFile.count = 1;
// function addInputFile() {
//
//     if (addInputFile.count > 10) {
//         return alert('Maximum limit');
//     }
//
//     var empty_input = $("#attachContainer input[value='']").length;
//     if (empty_input > 0) {
//         return;
//     }
//
//     addInputFile.count ++;
//
//     var container = document.getElementById('attachContainer');
//
//     var input = document.createElement('input');
//     var br = document.createElement('br');
//
//     input.type = 'file';
//     input.name = 'attach_' + addInputFile.count;
//     input.id = 'attach_' + addInputFile.count;
//     input.setAttribute('size', '74');
//     input.className = 'longText';
//     input.setAttribute('onChange', 'addInputFile()');
//
//     container.appendChild(br);
//     container.appendChild(input);
// }


function validateForm(button_name, func_name, callback, type) {
    if (!type) {
        type = false;
    }
    
    var values = FormCollector.collect(type);
    var options = {};
    
    if (button_name) {
        options['button_name'] = button_name;
    }
    
    if (callback) {
        options['callback'] = callback;
    }
    
    if (!func_name) {
        func_name = 'validate';
    }
    
    ErrorHighlighter.func = func_name;
    ErrorHighlighter.type = type;
    
    window['xajax_' + func_name](values, options);
    return false;
}


function toggleLeftMenu() {
    var action = $('#left_menu_toggle').attr('title');
    var next_action = $('#left_menu_toggle').attr('data-title');
    
    $('#left_menu_toggle').attr('title', next_action);
    $('#left_menu_toggle').attr('data-title', action);
    
    if ($('#menu_content').is(':visible')) {
        $('#menu_content').hide();
        createCookie('kb_sidebar_width_', 0, 0);
        
    } else {
        $('#menu_content').show();
        deleteCookie('kb_sidebar_width_', '/');
    }
}

function setDebug(is_debug) {
    if (!is_debug) {
        console.log('DEBUG is OFF');
        console.log = function() {};
    }
}

function copyUrl() {
    var temp = $('<input>');
    $('body').append(temp);
    temp.val($('.share_url').val()).select();
    document.execCommand('copy');
    temp.remove();
    
    $('.copy_block').hide();
    $('.copied_block').show();
    $('.share_url').select();
    
    setTimeout(function() {
        $('.copied_block').hide();
        $('.copy_block').show();
    }, 1000);
}

function toggleToc(div_id, cookie_name) {
    var v = ($('#'+div_id+':visible').length);
    if (v) {
        $('#'+div_id).hide();
        createCookie(cookie_name, 1, 30);
    } else {
        $('#'+div_id).show();
        deleteCookie(cookie_name, '/');
    }
}

function closeTooltip(el) {
    _tooltip_el = el;
    tolerance = setTimeout(function() {
		$(el).tooltipster('hide');
	}, 500);
}

function showLoading() {
    $('#loading_spinner').show();
    // $('#loading_spinner').css('display', 'block');
    // console.log('loading_spinner');
}