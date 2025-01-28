function showAction(action, actions) {
	HideDiv('bulk_update');
	ShowExtraDiv(action);

	for(var i=0; i<actions.length; i++) {
		var d = 'bulk_'+actions[i];
		
		if(action == actions[i]) {
			ShowDiv(d);
			ShowDiv('bulk_update');
		} else {
			HideDiv(d);
		}
	}	
}

function performAction(action, sure_common_msg, confirm_type) {
    if(!isAnyChecked('bulk_form', 'id[]')) { // 28-07-2022 eleontev
        return;
    }
    
    var ready = $('#bulk_action option[value="' + action + '"]').attr('data-ready');
    
    if (ready) {
        if (confirm_type == 'ezmodal') {
            confirm2(sure_common_msg);
            
        } else { 
            if (!$('#bulk_confirm').length) {
                var html = '<div id="bulk_confirm"><div style="margin-left: 20px; font-size: 1.2em;">' + sure_common_msg + '</div></div>';
                $('#bulk_block').append(html);
            }

            if (window.frameElement) {
                window.top.$('<div class="ui-widget-overlay ui-front" style="z-index: 999;"></div>').appendTo('body');
                window.top.$('.client_frame').addClass('modal_blocked');
            }

            $('#bulk_confirm').dialog({
                modal: true,
                width: 500,
                maxHeight: 500,
                title: $('#bulk_action option[value="' + action + '"]').text(),
                position: {
                    my: 'center top',
                    at: 'center top+15%',
                    of: window
                },
                dialogClass: 'bulk_dialog',
                autoResize: true,
                appendTo: '#bulk_form'
            });
            
            $('#bulk_update').appendTo('#bulk_confirm');
            $('#bulk_update').show();
            $('#bulk_update input[type="submit"]').attr('onclick', '');
            $('#bulk_update input[type="submit"]').focus();
        }
        
    } else {
        var extra_status = ShowExtraDiv(action);
        
        if (!extra_status) {
            $('#bulk_update').appendTo('#bulk_' + action);
            $('#bulk_update').show();
        
            $('#bulk_' + action).dialog({
                modal: true,
                width: 500,
                maxHeight: 500,
                title: $('#bulk_action option[value="' + action + '"]').text(),
                position: {
                    my: 'center top',
                    at: 'center top+15%',
                    of: window
                },
                dialogClass: 'bulk_dialog',
                autoResize: true,
                appendTo: '#bulk_form'
            });
        }
    }
}


function showActionCustom(field_id) {

	$('.bulk_custom_field').hide();
	$('.bulk_custom_append').hide();
	$('.bulk_custom_append_ch').prop('checked', false);
	// 	$('input[name=foo]').prop('checked', true);
	
	if (field_id == 'set') {
		$('.bulk_custom_field').show();

	} else {
		$('#bulk_custom_field_'+field_id).show();
		$('#bulk_custom_append_'+field_id).show();
	}
}

function ShowExtraDiv(action) {

}

function BulkOnSubmit() {
	
}

function bulkValidate(action) {
    return true;
}

function checkAll(action, form, name){
	//var ch = fr.getElementsByTagName('checkbox');
	var f = document.getElementById(form);
	for (i=0; i<f.length; i++) {
		if (f[i].name.indexOf(name) == 0) {
			if(f[i].disabled == true) {
				continue;
			}
			
			if(action) {
				f[i].checked = true;
			} else {
				f[i].checked = false;
			}
		}
	}	
}


function isAnyChecked(form, name) {
	var f = document.getElementById(form);
	for (i=0; i<f.length; i++) {
		if (f[i].name.indexOf(name) == 0) {
			if(f[i].checked == true) {
				return true;
			}
		}
	}
	
	return false;
}

function getCheckedValues(form, name) {
    var values = [];
    
    var selector = '#' + form + ' input[name^=' + name + ']:checked';
    $(selector).each(function() {
        values.push($(this).val());
    });
    
    return values;
}

function bulkSubmit(msg) {
    var action = document.getElementById('bulk_action').value;
    var error_msg = bulkValidate(action);
    
    if(error_msg !== true) {
        var error_block = $('<div />');
        error_block.addClass('boxMsgDiv error');
        error_block.html(error_msg);
        $('div.bulk_dialog div.error').remove();
        $('div.bulk_dialog div.ui-dialog-content').prepend(error_block);
        return false;
    }
    
    /*confirmForm(msg, 'bulk_submit');
    return false;*/
    
    return true;
}

function toggleBulkActionBlock(value, id) {
    if (value != 'remove') {
        $('#' + id).show();
    } else {
        $('#' + id).hide();
    }
}

function loadRoles(pin_refreshed_deleted_msg) {
    var values = getCheckedValues('bulk_form', 'id');
    if (values.length == 0) {
        alert(pin_refreshed_deleted_msg);
    } else {
        xajax_loadRoles(values);
    }
}

function HideShowCategory(action) {
    $('#bulk_update').appendTo('#bulk_category');
    $('#bulk_update').show();
    
    $('#bulk_category').dialog({
        modal: true,
        width: 500,
        maxHeight: 500,
        title: $('#bulk_action option[value="' + action + '"]').text(),
        position: {
            my: 'center top',
            at: 'center top+15%',
            of: window
        },
        dialogClass: 'bulk_dialog',
        autoResize: true,
        appendTo: '#bulk_form'
    });
    
    return true;
}


function validateTag(msg) {
    var ta = $("#tag_action").val();
    if(ta == 'set' || ta == 'add') {
        if (!tag_manager.records.length) {
            return msg;
        }
    }
    
    return true;
}


function validateAdminUser(msg) {
    var action = $('#admin_action').val();
    if(action == 'set') {
        if ($('#bulk_form input[name="value[admin_user][]"]').length < 2) {
            return msg;
        }
    }
    
    return true;
}


function toogleBulkActions(display) {    
    if (display) {
        $('#bulk_block').css('display', 'flex');
        $('li.bulk_actions').css('display', 'block');
    } else {
        $('#bulk_block').hide();
        $('li.bulk_actions').css('display', 'none');
    }
}