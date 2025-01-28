$('body').bind('kbpCategoryPopupClosed', {}, function(e, params) {
    var parent_window = PopupManager.getParentWindow();
     
    var main_category_id = params.categories[0]['value'];
    delete params.categories[0];
    
    var cat_ids = {CategoryIDs: []};
    for (var i in params.categories) {
        cat_ids['CategoryIDs'].push(params.categories[i]['value']);
    }
    
    var url = params.category_link.replace('0', main_category_id + '&' + $.param(cat_ids));
    parent_window.location.href = url;
});