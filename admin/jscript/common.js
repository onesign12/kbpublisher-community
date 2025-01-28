function CheckFrames(page) {
	if (parent.frames.length == 0) {
		window.location.href = page;
	}
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


function RowHighlightByCheckBox(row_id, ch_id, normal_class, highlight_class, row) {
	
	var row = document.getElementById(row_id);
	var ch = document.getElementById(ch_id);
	
	if(row && ch) {
		if(ch.checked == true) { ch.checked = false; }
		else                   { ch.checked = true; }
	}

	if(row && ch) {
		if(ch.checked == true) { row.className = highlight_class; } 
		else                   { row.className = normal_class; }
	}
}


function RowHighlightOneOnly(id, normal_class, highlight_class) { 
	var rows = document.getElementsByTagName('tr');
	
	for(i=0;i<rows.length;i++){
		if(rows[i].id && rows[i].id == id) {
			rows[i].className = highlight_class;
		} else {
			if(rows[i].className != highlight_class) { continue; }
			rows[i].className = normal_class;
		}
	}
}

// redirect to path // using for tr and onDblClick
function RecordToDo(path) {
	document.location.href = path;
}

function OverColorSimple(obj, old_class, new_class) { 
	obj.className = new_class;
}

function Validate() {
	return true;
}

// centered popup
function OpenWin(URL, winName, width, height, scroll, menubar, status) {

	var winLeft = (screen.width - width) / 2;
	var winTop = (screen.height - height) / 5;
	
	winData =  'height='+height+',width='+width+',top='+winTop+',left='+winLeft+',';
	winData += 'scrollbars='+scroll+',menubar'+menubar+',status='+status+'';
	//winData += 'alwaysRaised=1';
	win = window.open(URL, winName, winData);
	
	//alert(screen.width);
	//alert(winData);
	
	if (parseInt(navigator.appVersion) >= 4) { win.window.focus(); }
}

// we use it when patient pop up, to empty field
function doEmpty() {
	var field;
	for(i=0;i<arguments.length;i++){
		field = document.getElementById(arguments[i]);
		field.value = '';
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

function ShowHideDiv(div, display, display_block) {
	if(display) { ShowDiv(div, display_block); } 
	else        { HideDiv(div); } 
}

function ShowHelp(div, title, desc, width, display_block, margin) {

	var width = (width) ? width : '';
	var div = document.getElementById(div);
	var html = '<div>';
	html += (title) ? '<b>' + title + '</b><br>' : '';
	html += desc;
	html += '</div>';
	
	div.style.display = (display_block) ? 'block' : 'inline';
	div.style.margin = (margin) ? margin : '5px 0px 0px 10px';
	//div.style.fontWeight = 'normal';
	
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
        
        console.log('Growl Options: ', gOptions);
        $.growl(gOptions);
    }
}


function emptyGrowls() {
    $('#growls-default').empty();
}


function setButtonsPopup() {
	$('#bottom_button').addClass('bottom_button');
	$('#bottom_button').addClass('bb_popup');
}

// in articles, news, if entry not published, no link to public area
function confirmNotPublishedEntry(msg, link) {
    confirm2(msg, function() {
        document.location.href = link;
    });
}

// set focus to obj, use true for second argument to set select to obj
function SetFocus(id) {
	var obj = document.getElementById(id);
	obj.focus();
	if(arguments[1]) { obj.select(); }
}

// show prompt to insert new value
function addOption(sel,oprompt) {
	
	var sel = document.getElementById(sel);	
	
	var other= sel.options[sel.selectedIndex];
	var newval= window.prompt(oprompt,'');
	if(!newval) { return; }
	sel.options[sel.options.length]= new Option(other.text,other.value,false,false);
	other.text = newval;
	other.value = 'new_'+newval;
}


function parseGetVars() {
    var getVars = location.search.substring(1).split("&");
    var returnVars = new Array();
    
    for(i=0; i < getVars.length; i++) {
        var newVar = getVars[i].split("=");
        returnVars[unescape(newVar[0])] = unescape(newVar[1]);
    }
    
    return returnVars;
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

function confirmFormActionSubmit(form_id, msg) {
    confirm2(msg, function() {
		$('#' + form_id).submit();
    });
}

function confirmFormActionLink(link, msg) {
    confirm2(msg, function() {
        location.href = link;
    });
}

// another cookie
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

// assig more than one handker on page
function addHandler(object, event, handler)
{
  if (typeof object.addEventListener != 'undefined')
    object.addEventListener(event, handler, false);
  else if (typeof object.attachEvent != 'undefined')
    object.attachEvent('on' + event, handler);
  else
    throw "Incompatible browser";
}

function removeHandler(object, event, handler)
{
  if (typeof object.removeEventListener != 'undefined')
    object.removeEventListener(event, handler, false);
  else if (typeof object.detachEvent != 'undefined')
    object.detachEvent('on' + event, handler);
  else
    throw "Incompatible browser";
}


//Univesal onload
function setGlobalOnLoad(f) {
   var root = window.addEventListener || window.attachEvent ? window : document.addEventListener ? document : null
   if (root){
      if(root.addEventListener) root.addEventListener("load", f, false)
      else if(root.attachEvent) root.attachEvent("onload", f)
   } else {
      if(typeof window.onload == 'function') {
         var existing = window.onload
         window.onload = function() {
            existing()
            f()
         }
      } else {
         window.onload = f
      }
   }
}


function checkboxToRadioBehavior(val, options) {
	var clicked = document.getElementById(val);
	if(clicked.checked == true) {
		for (var i=0;i<arguments.length;i++) {
			if(i == 0) { continue; }
			if(clicked.id == arguments[i]) { continue; }
			
			var ch = document.getElementById(arguments[i]);
			ch.checked = false;
		}		
	}
}


function ShowHideCustomDate(val) {
    if (val == 'custom_period' || val == 'custom_period2') {
        $('#' + val).show();
    } else {
        $('#' + val).hide();        
    }
}

function toggleBlock(key) {
    $('#' + key + '_toggle_block').slideToggle(400, function() {
        $('#' + key + '_toggle_title').removeClass('formToggleUnfolded');
        
        if ($(this).is(':visible')) {
            $('#' + key + '_toggle_title').addClass('formToggleUnfolded');    
        }
    });
}

function checkDates(msg) {
    var period = $('#period').val();
    
    var msg_needed = false;
    
    var year_from = $('#year_from select').val();
    var year_to = $('#year_to select').val();
    
    if (period == 'range_month') {
        var month_from = $('#month_from select').val();
        var month_to = $('#month_to select').val();
        
        if (year_from == year_to) {
            msg_needed = month_from > month_to;
            
        } else if (year_from > year_to)  {
            msg_needed = true;
        }
    }
    
    if (period == 'range_year') {
        if (year_from > year_to)  {
            msg_needed = true;
        }
    }
    
    if (msg_needed) {
        alert(msg);
    }
    
    return !msg_needed;
}


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
    
    // alert('button[name="'+button_name+'"]');
    // alert(document.getElementById('aContentForm').action);
    //input[type="submit"], button[type="submit"]
    // $('input[type="submit"]').value('fuck');
    
    // alert(button_name);
    // console.log(values);
    // console.log(options);
    
    window['xajax_' + func_name](values, options);
    return false;
}


function initUserTooltip() {
    $('._tooltip_user').tooltipster({
        contentAsHTML: true,
        interactive: true,
        functionBefore: function(instance){
            var content = instance.content();
            content = content.replace(/(?:\r\n|\r|\n)/g, '<br />');
            instance.content(content);
        }
    });
}


function setDebug(is_debug) {
    if (!is_debug) {
    	console.log('DEBUG is OFF');
        console.log = function() {};
	}
}


function showSpinner(button_name) {
    var msg = $('input[name=' + button_name + ']').attr('data-loading-msg');
    
    if (msg) {
        $('#loadingMessagePage span').text(msg);
    }
    
    $('#loadingMessagePage').show();
}


function addRecentSettingToPool(key, group_id) {
    var pool = getCookie('kb_recent_setting_');
    pool = (pool) ? $.parseJSON(pool) : [];
    
    key += '|' + group_id;
    
    var index = pool.indexOf(key);
    if (index != -1) { // already added
        pool.splice(index, 1);
    }
    
    pool.unshift(key);
        
    if (pool.length > 8) {
        pool.pop();
    }
    
    createCookie('kb_recent_setting_', JSON.stringify(pool), 7);
}

function submitFilterForm(el) {
    var submit_form = true;
    
    if ($(el).attr('name') == 'filter[ch]') { // children checkbox
        if ($('#filterCategoryId').val() == '0') {
            submit_form = false;
        }
    }
    
    if (submit_form) {
        if ($('#advanced_search input[name="submit"]').is(':visible')) {
            $('#advanced_search input[name="submit"]').click();
            
        } else {
            $(el).closest('form').submit();
        }
    }
}

function showExportPopup(title) {
    $('#popup_export').dialog({
        modal: true,
        width: 500,
        title: title,
        position: {
            my: 'center top',
            at: 'center top+20%',
            of: window
        },
        autoResize: true
    });
}

function showSubMenu(id, el) {
    $('#actions_submenu' + id).css('right', $('#actions' + id).width());
    $('#actions_submenu' + id).css('top', $(el).position().top - 2);
    $('#actions_submenu' + id).show();
}

function insertSpecialSearch(value) {
    $('[name="filter[q]"').val(value);
    $('[name="filter[q]"').focus();
    $('._tooltip_right').tooltipster('close');
}

function setClipboardTooltip(elem, message) {
    $(elem)
    .tooltipster({})
    .tooltipster('close')
    .tooltipster('content', message)
    .tooltipster('open');
}

function hideClipboardTooltip(elem) {
  setTimeout(function() {
    $(elem)
    .tooltipster('close')
    .tooltipster('destroy');
  }, 1000);
}

// used in clearable
function tog(v) {
	return v ? "addClass" : "removeClass";
}