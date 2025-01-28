function subForm() {
    LeaveScreenMsg.skipCheck();	
    selHandler.selectAll();
    optionalHandlersSelectAll();
}


function categoryAjaxHandler() {
	
	$('#category_private_div').html('<span id="writeroot_private"></span>');
    $('#category_private_content, #category_private_content2').hide();
	
	var writeroot = document.getElementById('writeroot_sort');
	if(writeroot) {
		$('#writeroot_sort').html('<span id="writeroot_sort"></span>');
	}
    
    $('#category').children('option').each(function() {
        
        if (typeof xajax_getCategoryPrivateInfo === 'function') {
            xajax_getCategoryPrivateInfo(this.value, this.text);
        }
		
		// no need to sort in add local files form and file rules 
		// and SortShowMore required ???
		if(SortShowMore && writeroot) {
			xajax_populateSortSelect(this.value, this.text);
		}
    });
    
    if (window.button_text_enabled) {
        updateButtonText();
    }
}


// custom fields, with categories
function customFieldCategoryHandler(msg) {
    
    selHandler.addOnPopupCloseFunction('getCustomByCategory');
    
    var onclick = $('#delete_category_button').attr('onclick');
    
    $('#delete_category_button').attr('onclick', '');
    $('#category').attr('ondblclick', '');
    
    $('#delete_category_button').click(onclick, _customFieldCategoryHandler);        
    $('#category').dblclick(onclick, _customFieldCategoryHandler);
}

function _customFieldCategoryHandler(e) {
    eval(e.data);
}

function getCustomByCategory(data) {
    if(typeof xajax_getCustomByCategory !== 'function') {
        return;
    }
    
    var ids = [];
    var categories = data[0];
    for (var i in categories) {
        ids.push(categories[i]['value']);
    }
    
    xajax_getCustomByCategory(ids);
}

function deleteCustom(ids) {
    for (var id in ids) {
        $('#tr_custom_' + ids[id]).remove();
    }
}

function deleteCustomAll() {
    $('.custom_category').remove();
}

function insertCustom(html) {
    var ids = [];
    $(html).find('tr').each(function() {
        if (this.id) {
            if (!$('#' + this.id).length) {
                $(this).insertAfter('#custom_field_bottom_border');
            }
            ids.push(this.id);
        }
    });
    
    deleteCustomByCategory(ids);
}

function deleteCustomByCategory(ids) {
    $('.custom_category').each(function() {
        if (jQuery.inArray(this.id, ids) == -1) {
            $('#' + this.id).remove();
        }
    });
}

function call_SetEntryTemplate(new_content, action, msg) {
    editor = CKEDITOR.instances.body;

    if(action == 'replace') {
        var fck_content = editor.getData();
        if(fck_content == '') {
            editor.setData(new_content);

        } else {
            confirm2(msg, function() {
                editor.setData(new_content);
            });
        }
    } else {
        // var html_to_insert = '<div>' + new_content + '</div>';
        // var element = CKEDITOR.dom.element.createFromHtml(html_to_insert);
        // editor.insertElement(element);

        new_content += "\n";
        editor.insertHtml(new_content);
    }
}


function updateButtonText() {

    if (button_text_enabled) {

        var status = parseInt($('select[name="active"]').val());
        // var published_statuses = [{published_statuses}];
        // var non_active_categories = [{non_active_categories}];

        var published = true;
        $('#category option').each(function() {
            // at least one cat not active set published = false not correct 
            // should be at least on one published published = true
            if ($.inArray(parseInt($(this).val()), uBtnOptions.non_active_categories) != -1) {
                published = false;
                return false;
            }
        });

        if (published && $.inArray(status, uBtnOptions.published_statuses) == -1) {
            published = false;
        }
        
        var save_text = (uBtnOptions.draft_module) ? uBtnOptions.save_draft_msg : uBtnOptions.save_msg;
        var button_text = (published) ? uBtnOptions.publish_msg : save_text;
    
        if ($('#submit_button').attr('data-key') == 'save') {
        	$('#submit_button').val(button_text);
        }
    
        $('.split_button li[data-key="save"]').html(button_text);
    }
}