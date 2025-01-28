$('body').bind('kbpCategoryPopupOpened', {}, function(e, params) {
    $('#category_filter').focus();
});

$('body').bind('kbpCategoryPopupClosed', {}, function(e, params) {
    var parent_window = PopupManager.getParentWindow();
    var category_ids = [];
    for (var i in params.categories) {
        category_ids.push(params.categories[i]['value']);
    }
    
    if(parent_window.document.getElementById("subsc_frame")) { // from public in iframe
        parent_window.document.getElementById("subsc_frame").contentWindow.xajax_saveCategories(category_ids);
    } else {
        parent_window.xajax_saveCategories(category_ids);
    }
});