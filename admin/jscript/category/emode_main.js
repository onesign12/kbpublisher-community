$('body').bind('kbpCategoryPopupOpened', {}, function(e, params) {
    var parent_window = PopupManager.getParentWindow();
    if (parent_window.main_category_id) {
        for (var i in categories) {
            if (categories[i]['value'] == parent_window.main_category_id) {
                CategoryManager.addCategory(parent_window.main_category_id, categories[i]['label'], true);
            }
        }
        
        var _this = this;
        parent_window.$('div:visible[id^=category_row_]').each(function() {
            var id = $(this).attr('id').substring(13);
            
            for (var i in categories) {
                if (categories[i]['value'] == id) {
                    CategoryManager.addCategory(id, categories[i]['label'], true);
                }
            }
        });
    }
    
    $('#category_filter').focus();
});


$('body').bind('kbpCategoryPopupClosed', {}, function(e, params) {
    
    var parent_window = PopupManager.getParentWindow();
    
    // firing changes
    parent_window.$('body').trigger('kbpEditModeCategoriesTransferred');
    
    // clearing
    parent_window.$('input[name="category[]"]').remove();
    parent_window.$('#main_category').empty();
    
    parent_window.$('div[id^="category_row_"]').remove();
    if (parent_window.$('div[id^=category_row_]').length == 0) {
        parent_window.$('#empty_category_block').show();
    }
    
    
    if (params.categories.length == 0) { // no one categories selected
        parent_window.main_category_id = false;
        return;
    }
    
    var category_ids = [];
    for (var i in params.categories) {
        category_ids[i] = params.categories[i]['value'];
    }
    
    // main
    var main_category = params.categories[0];
    parent_window.$('#main_category').html(main_category.text);
        
    var old_id = parent_window.main_category_id;
    parent_window.main_category_id = main_category.value;
    
    var hidden = parent_window.$('<input type="hidden" name="category[]" />').val(main_category['value']);
    parent_window.$('#aContentForm').append(hidden);
    
    delete params.categories[0];
    
    
    // also listed
    var secondary_ids = [];
    var block_html = window.top.$('#readroot_category').html();
    for (var i in params.categories) {
        var block = $(block_html);
                
        block.attr('id', 'category_row_' + params.categories[i]['value']);
        block.find('a.articleLinkOther').html(params.categories[i]['text']);
        block.find('span.delete_tag').attr('onclick', 'deleteEntryProperty(' + params.categories[i]['value'] + ', \'category\', "' + params.sure_common_msg + '");');
                
        window.top.$(block).insertBefore('#writeroot_category');
        window.top.$('#empty_category_block').hide();
                
        var hidden = parent_window.$('<input type="hidden" name="category[]" />').val(params.categories[i]['value']);
        parent_window.$('#aContentForm').append(hidden);
        
        secondary_ids.push(params.categories[i]['value']);
    }
    
    if (secondary_ids.length > 0) {
        parent_window.xajax_parseCategoryLinks(secondary_ids); // 'spinner_category'
        parent_window.$('.custom_category').remove();
    }
    
    if(typeof parent_window.xajax_getCustomByCategory === 'function') {
        if (old_id) {
            parent_window.xajax_getCustomToDelete(old_id, category_ids);
        }
        parent_window.xajax_getCustomByCategory(category_ids); // 'spinner_main_category'
    }
});