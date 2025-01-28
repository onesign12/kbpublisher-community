// POP UP
function InsertFromPopUp(name, id, choosed_name, choosed_id) {
	
	var name = window.opener.document.getElementById(name);
	var id = window.opener.document.getElementById(id);
	
	//var msg = "Patient: "+patient_name+"\n";
	//msg += "Choose this patient and close the window?";
	
	//if(window.confirm(msg)) { 
		name.value = choosed_name;
		id.value = choosed_id;
		window.close(); 
	//}
}


function OpenPopup(page, field_name, field_id, popup_value, popup_name) {
	var pv = (popup_value) ? popup_value : 1;
	var pn = (popup_name) ? popup_name : 'popup';
	OpenWin(page+'&field_name='+field_name+'&field_id='+field_id+'&popup='+pv, pn, 900, 520, 'yes', false, false);		
}


function OpenPopupModal(page, field_name, field_id, popup_value, popup_name) {
	var pv = (popup_value) ? popup_value : 1;
    var pn = (popup_name) ? popup_name : 'popupModalDiv';
    
    if (window.top != window.self) { // we need a second popup
        pn = 'popupModalDiv2';
    }
    
    // create a popup div if it doesn't exist
    if (!window.top.$('#' + pn).length) {
        var div = '<div id="' + pn + '"></div>';
        $(window.top.document.body).append(div); 
    }
    
    var html = '<div id="loadingMessage" style="text-align: center;margin-top: 10px;"><img src="images/ajax/indicator.gif" align="absmiddle" alt="Loading" style="margin-right: 7px;"/>Loading</div>';
    window.top.$('#' + pn).html(html);
    
    var options = {
        modal: true,
        height: 520,
        width: 920,
        open: function() {
            $('.ui-dialog').css('box-shadow', '#555 2px 2px 2px');
        }
    };
    window.top.$('#' + pn).dialog(options);
    
    var url = page + '&field_name=' + field_name + '&field_id=' + field_id + '&popup=' + pv;
    var iframe = $('<iframe class="popup" name="' + pn + '" />').attr('src', url);
    window.top.$('#' + pn).append(iframe);
}


function ClosePopup() {
    window.close();
}


function ClosePopupModal() {
    if (window.top.$('#popupModalDiv2:visible').length) { // second level
        window.top.$('#popupModalDiv2').dialog('close');
    } else {
        window.top.$('#popupModalDiv').dialog('close');
    }
}

function disableLink(link) {    
    $(link).css('color', '#777777');
    $(link).css('text-decoration', 'none');
    $(link).css('cursor', 'text');
    $(link).attr('onClick', 'return false;');
}


function showAfterActionMessage(msg) {
    $('#afterActionMessage table table td').html(msg);
    $("#afterActionMessage").show().fadeOut(3000);
}


/*
// to create not to open twice
function OpenPopup(page, field_name, field_id, name) {
	var name = (name) ? name : 'popup';
	var url = page+'&field_name='+field_name+'&field_id='+field_id+'&popup=1';
	OpenWin(url, name, 750, 500, 'yes', false, false);
}

function popitup(url) {
	if (!newwindow.closed && newwindow.location) {
		newwindow.location.href = url;
	}
	else {
		newwindow=window.open(url,'name','height=200,width=150');
		if (!newwindow.opener) newwindow.opener = self;
	}
	if (window.focus) {newwindow.focus()}
	return false;
}
*/


// we use it when pop up, to empty field
function doEmpty() {
	var field;
	for(i=0;i<arguments.length;i++){
		field = document.getElementById(arguments[i]);
		field.value = '';
	}
}


// reapeat html	
function getMoreHtml() {
	
	this.readroot = 'readroot';
	this.writeroot = 'writeroot';
	this.counter = 0;
	this.id_pref = 'more_html_';
	this.confirm_use = false; //alert to close window
	this.confirm_msg = '';
    this.max_allowed = 0;
	
	
	this.get = function(id_value, name_value) {
	    
		this.counter ++;
		var new_id = this.id_pref + this.counter;
        
		var readBlock = $(this.readroot).clone();
        readBlock.attr('id', new_id);
        readBlock.css('display', 'block');
        
        if (this.max_allowed == 1) {
            var parent_window = PopupManager.getParentWindow();
            parent_window.$('div[id^=' + this.id_pref + ']').remove();
        }
        
        
        // left div with  values        
        var input = readBlock.find('div:first-child > input');
        
        if (input.attr('name') == 'related_title[]') {
            input.attr('name', 'related_title[' + id_value + ']');
            
        } else {
            input.val(id_value); // hidden value
        }
        
        
        var span = readBlock.find('div:first-child > span');
        span.html(name_value); // value
        
        
        // right div with action
        var link = readBlock.find('div:nth-child(2) > a');
        link.attr('id', id_value);
        
        var input = readBlock.find('div:nth-child(2) > input');
        input.val(id_value); // for some input
        
        
        // insert after writeroot block
		var insertBlock = $(this.writeroot);
        readBlock.insertBefore(insertBlock);
		
		if(this.confirm_use) {
			this.confirmAction();
		}
		
		return new_id;
	}	
    
    
    this.clone = function() {
	    
		this.counter ++;
		var new_id = this.id_pref + this.counter;
        
		var readBlock = $(this.readroot).clone();
        readBlock.attr('id', new_id);
        readBlock.css('display', 'block');
        readBlock.find('a.removeClone').attr("data-id", new_id);
        
        // $('#' + new_id +' .removeClone').data('data_id', new_id);
        // console.log(readBlock.find('a.removeClone'));
        // readBlock.find('a').data('data_id', new_id);
        
        
        // insert after writeroot block
		var insertBlock = $(this.writeroot);
        readBlock.insertBefore(insertBlock);
		
		return new_id;
	}
    
    
    this.removeClone = function (obj, confirm_msg) {
        var obj = $('#' + $(obj).data('id'));
        
        if(confirm_msg) {
            if(confirm(confirm_msg)) { 
                $(obj).remove(); 
            }
        } else {
            $(obj).remove();
        }
    }
	
	
	this.confirmAction = function() {
		ret = confirm(this.confirm_msg);
		if(!ret) {
			PopupManager.close();
		}
	}
	
	
	this.remove = function (obj, confirm_msg) {
		obj = obj.parentNode.parentNode;	// get_more_html_1, ....
		this._remove(obj, confirm_msg);
	}
	
	
	this._remove = function (obj, confirm_msg) {
		if(confirm_msg) {
			if(confirm(confirm_msg)) { 
				obj.parentNode.removeChild(obj); 
			}
		} else {
			obj.parentNode.removeChild(obj);
		}
		
		//alert(obj.id);
	}	
	
	
	this.removeAll = function (div_class) {
		
		var block = this.getDivObject(this.writeroot).parentNode; //td
		var blocks = block.childNodes;
		var div_class = (div_class) ? div_class : 'popUpDiv';
		
		for (var j=0;j<blocks.length;j++) {
			alert();
			if(blocks[j].className == div_class) {
				this._remove(blocks[j]);
			}
		}
	}	
	
	
	this.getDivObject = function(div_obj) {
		if(typeof(div_obj) == 'object') {
			 return div_obj;
		} else {
			return document.getElementById(div_obj);
		}
	}
}


function removeHtml(obj, confirm_msg) {
	//alert(obj.parentNode.parentNode.id); // get_more_html_1, ....
	obj = obj.parentNode.parentNode;
	if(confirm_msg) {
		confirm2(confirm_msg, function() {
		    obj.parentNode.removeChild(obj); 
		});
		
	} else {
		obj.parentNode.removeChild(obj);
	}
}


//s = new getMoreHtml();
//s.writeroot = document.getElementById('writeroot');