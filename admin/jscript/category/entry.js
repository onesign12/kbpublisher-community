$('body').bind('kbpCategoryPopupOpened', {}, function(e, params) {
    $('body').bind('kbpCategoryAdded', {}, function(e, params) {
        categories.push({label: params.label, value: params.id});
    });
    
    var parent_window = PopupManager.getParentWindow();
    var focus = true;
    parent_window.$('#' + params.select_id).children('option').each(function() {
        CategoryManager.addCategory(this.value, this.text, true);
        focus = false;
    });
    
    if (focus) {
        $('#category_filter').focus();
    }
});


$('body').bind('kbpCategoryPopupClosed', {}, function(e, params) {
    var parent_window = PopupManager.getParentWindow();
    parent_window[params.handler_name].createSelectCategories(params.categories);
    parent_window[params.handler_name].callOnPopupCloseFunctions(params.categories);
    
    if (params.categories.length) {
        parent_window.$('body').trigger('kbpErrorResolved', [{field: 'category'}]);
    }
});