$('body').bind('kbpCategoryPopupOpened', {}, function(e, params) {
    var parent_window = PopupManager.getParentWindow();
    
    var focus = true;
    
    var parent_id = parent_window.$('#parent_id').val();
    
    if (parseInt(parent_id)) {
        for (var i in categories) {
            if (categories[i]['value'] == parent_id) {
                var text = categories[i]['label'];
                CategoryManager.addCategory(parent_id, text, true);
                focus = false;
            }
        }
    }
    
    if (focus) {
        $('#category_filter').focus();
    }
});


$('body').bind('kbpCategoryPopupClosed', {}, function(e, params) {
    var parent_window = PopupManager.getParentWindow();
    //var param = arguments[0];

    parent_window['selHandler'].createSelectCategories(params.categories);
    //parent_window[this.handler].callOnPopupCloseFunctions(popupCategories, param);
    
    parent_window.$('body').trigger('kbpSelectedEntryTransferredToParentWindow', [{handler: this, categories: params.categories}]);
});