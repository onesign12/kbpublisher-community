var DynamicEntriesScrollLoader = {

    limit: false,
    offset: false,
    element_selector: 'div.articleListBottomLine',
    insert_selector: 'div.articleList',
    loading_icon_selector: '#spinner',
    context: window,
    load_button: '#load_button',
    
    
    init: function(limit, offset, context) {
        DynamicEntriesScrollLoader.limit = limit;
        DynamicEntriesScrollLoader.offset = offset;
        
        if (context != 'false') {
            DynamicEntriesScrollLoader.context = context;
        }
    },
    
    setLoader: function() {
        $(DynamicEntriesScrollLoader.element_selector).waypoint(
            DynamicEntriesScrollLoader.load,
            {offset: function() {
                return $(window).height();
            },
            context: DynamicEntriesScrollLoader.context
        });
    },
    
    resetLoader: function() {
        $(DynamicEntriesScrollLoader.element_selector).waypoint('destroy');
        DynamicEntriesScrollLoader.setLoader();
    },
    
    load: function(direction) {
        if (direction == 'down') {
            if (DynamicEntriesScrollLoader.offset) {
                $(DynamicEntriesScrollLoader.loading_icon_selector).show();
                xajax_loadNextEntries(DynamicEntriesScrollLoader.offset);
            }
        }
    },
    
    insert: function(data, end_reached) {
        $(DynamicEntriesScrollLoader.insert_selector).append(data);
        
        if (end_reached == 1) {
            DynamicEntriesScrollLoader.offset = false;
            $(DynamicEntriesScrollLoader.load_button).hide();
            
        } else {
            DynamicEntriesScrollLoader.offset += DynamicEntriesScrollLoader.limit;
        }
        
        $(DynamicEntriesScrollLoader.loading_icon_selector).hide();
    },
}