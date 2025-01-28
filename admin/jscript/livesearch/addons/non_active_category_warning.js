$('body').bind('kbpEntryCreated', {}, function(e, params) {
    
    if (window.top.$('#published_only:not(:checked)').length) {
        return;
    }
    
    if (window.non_active_categories) {
        id = parseInt(params.id);
        var is_non_active = $.inArray(id, window.non_active_categories);
        if (is_non_active != -1) {
            alert(params.handler.nonActiveCategoryMsg);
            
            if (params.handler.deleteNonActive) {
                params.handler.deleteOption(id);
            }
        }
    }
});