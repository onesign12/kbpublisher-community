function destroy(parsed_body) {
    $('#history_comment').val('');
    $('#save_block').hide();
    $('#changes_hint').hide();
    
    initial_body = parsed_body;
    
    //CKEDITOR.instances['body'].destroy();
    //CKEDITOR.instances['body'].setData(body);
    
    CKEDITOR.instances['body'].focusManager.blur(true);
    $('#body').html(parsed_body);
    
    hljs.initHighlighting();
}

function cancel() {
    LeaveScreenMsg.fck_default = null;
    LeaveScreenMsg.changes = false;
    destroy(initial_body);
}

function checkProperty(property_name) {
    if ($('div:visible[id^=' + property_name + '_row_]').length == 0) {
        $('#empty_' + property_name + '_block').show();
    }
}

function insertCustomBlocks(blocks, append) {
    for (var i in blocks) {
        $('#custom_' + i).css('background-color', '#ffffcc');
        
        $('#custom_' + i).animate({
            backgroundColor: '#ffffff'
        }, 2500);
        
        if (parseInt(append)) {
           var parsed = $('<div/>').append(blocks[i]);
            
            parsed.find('*[id^=custom_block]').each(function() {
                if ($('#' + this.id).length == 0) {
                    $('#custom_' + i).append(blocks[i]);
                }
            })
            
        } else {
            $('#custom_' + i).html(blocks[i]);
        }
        
    }
}

function deleteEntryProperty(id, property_name, confirm_msg) {
    confirm2(confirm_msg, function() {
        
        $('#aContentForm input[name="' + property_name + '[]"][value="' + id + '"]').remove();
        $('#' + property_name + '_row_' + id).fadeOut(300, function() {checkProperty(property_name);});
        
        if (property_name == 'category') {
            var categories = []; 
            $('input[name="category[]"]').each(function() {
                categories.push(this.value);
            });
            
            xajax_getCustomToDelete(id, categories);
        }
        
        window.top.$('body').trigger('kbpEditModeEntryPropertyDeleted', [{id: id, name: property_name}]);
    });
}

function deleteCustom(ids) {
    for (var id in ids) {
        $('#custom_block_' + ids[id]).remove();
    }
}

function highlightErrors(fields) {
    for (var i in fields) {
        var field = fields[i];
        
        if (field == 'title') {
            $('h1.articleTitle').addClass('validationErrorEmode');
        }
        
        if (field == 'body') {
            $('#body div').addClass('validationErrorEmode');
        }
        
        if (field.lastIndexOf('custom[', 0) === 0) {
            $('#custom_field_bar').css('background', '#ffcccc');
            
            $(document.body).animate({
                'scrollTop': $('#custom_field_bar').offset().top
            }, 1000);
        }
    }
}