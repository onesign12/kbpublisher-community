$('body').bind('kbpCategoryPopupOpened', {}, function(e, params) {
    var parent_window = PopupManager.getParentWindow();
    var value = parent_window.$('#' + params.select_id).val();
    
    if (value) {
        var text = parent_window.$('#' + params.select_id + '_text').val();
        CategoryManager.addCategory(value, text, true);
    }
});


$('body').bind('kbpCategoryPopupClosed', {}, function(e, params) {
    if (params.categories.length) {
        var parent_window = PopupManager.getParentWindow();
        
        parent_window.$('#' + params.select_id).val(params.categories[0]['value']);
        parent_window.$('#' + params.select_id + '_text').val(params.categories[0]['text']);
    }
});