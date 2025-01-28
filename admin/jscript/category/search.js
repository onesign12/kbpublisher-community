$('body').bind('kbpCategoryPopupOpened', {}, function(e, params) {
    var parent_window = PopupManager.getParentWindow();
    
    parent_window.$('div[id^="cat_selected_"]').each(function() {
        
        var cat_id = $(this).find('input').val();
        for (var i in categories) {
            if (categories[i]['value'] == cat_id) {
                CategoryManager.addCategory(cat_id, categories[i]['label'], true);
            }
        }
    });
    
    $('#category_filter').focus();
});


$('body').bind('kbpCategoryPopupClosed', {}, function(e, params) {
    
    var parent_window = PopupManager.getParentWindow();
    
    parent_window.$('div[id^="cat_selected_"]').remove();
    parent_window.$('#cat_cbx').hide();
    
    for (var i in params.categories) {
        var cat_id = params.categories[i]['value'];
        if (parent_window.$('#cat_selected_' + cat_id).length == 0) {
            
            var container = parent_window.$('<div class="search_item"></div>');
            container.attr('id', 'cat_selected_' + cat_id);
            
            var checkbox = parent_window.$('<input type="checkbox" name="c[]" checked />');
            checkbox.attr('id', cat_id + '_c');
            checkbox.val(cat_id);
            container.append(checkbox);
            
            var label = parent_window.$('<label>' + params.categories[i]['text'] + '</label>');
            label.attr('for',  cat_id + '_et');
            container.append(label);
            
            container.insertBefore('#cat_cbx');
            
            parent_window.$(container).find('input').iCheck({
                checkboxClass: 'icheckbox_square-blue',
            });
            
            parent_window.$('#cat_cbx').show();
        }
    }
    
});