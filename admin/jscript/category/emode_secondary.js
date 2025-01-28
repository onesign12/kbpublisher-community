$('body').bind('kbpCategoryPopupOpened', {}, function(e, params) {
    var parent_window = PopupManager.getParentWindow();
    parent_window.$('div:visible[id^=category_row_]').each(function() {
        var id = $(this).attr('id').substring(13);
        for (var i in categories) {
            if (categories[i]['value'] == id) {
                CategoryManager.addCategory(id, categories[i]['label'], true);
            }
        }
    });
    
    if (parent_window.main_category_id) {
        for (var i in categories) {
            if (categories[i]['value'] == parent_window.main_category_id) {
                categories[i]['disabled'] = true;
            }
        }
    }
    
    $('#category_filter').focus();
});


$('body').bind('kbpCategoryPopupClosed', {}, function(e, params) {
    var parent_window = PopupManager.getParentWindow();
    
    // firing changes
    parent_window.$('body').trigger('kbpEditModeCategoriesTransferred');
    
    parent_window.$('input[name="category[]"]').not(':first').remove();
    
    parent_window.$('div[id^="category_row_"]').remove();
    if ($('div[id^=category_row_]').length == 0) {
        parent_window.$('#empty_category_block').show();
    }
    
    // also listed
    var ids = [];
    var block_html = window.top.$('#readroot_category').html();
    for (var i in params.categories) {
        var block = $(block_html);
                
        block.attr('id', 'category_row_' + params.categories[i]['value']);
        block.find('a.articleLinkOther').html(params.categories[i]['text']);
        block.find('span.delete_tag').attr('onclick', 'deleteEntryProperty(' + params.categories[i]['value'] + ', \'category\', "' + params.sure_common_msg + '");');
                
        window.top.$(block).insertBefore('#writeroot_category');
        window.top.$('#empty_category_block').hide();
                
        var hidden = $('<input type="hidden" name="category[]" />').val(params.categories[i]['value']);
        parent_window.$('#aContentForm').append(hidden);
        
        ids.push(params.categories[i]['value']);
    }
    
    if (ids.length > 0) {
        parent_window.xajax_parseCategoryLinks(ids, 'spinner_category');
        
        if(typeof parent_window.xajax_getCustomByCategory === 'function') {
            parent_window.$('.custom_category').remove();
            parent_window.xajax_getCustomByCategory(ids, 'spinner_category');
        }
    }
    
});