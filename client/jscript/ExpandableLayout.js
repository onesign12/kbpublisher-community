var ExpandableLayout = {
    min_width: 0,
    default_width: 200,
    cookie_name: 'kb_sidebar_width_',
    toggle: '#divider div',
    expanded_sidebar_width: null,
    resizable: true,
    
    
    init: function() {
        var sidebar_width = $('#sidebar').width();
        ExpandableLayout.expanded_sidebar_width = (sidebar_width > (ExpandableLayout.min_width + 1)) ? sidebar_width : ExpandableLayout.default_width;

        ExpandableLayout.setHeight();
        $(window).resize(ExpandableLayout.setHeight);
        
        
        var divider_width = $('#divider').width();
        //var left = (sidebar_width > divider_width) ? sidebar_width - divider_width : sidebar_width;
        var left = sidebar_width; 
        $('#divider').css('left', left);

        if (sidebar_width > (ExpandableLayout.min_width + 1)) {
            $('#sidebar').css('overflow', 'auto');
            $('#sidebar').css('overflow-x', 'hidden');
            $(ExpandableLayout.toggle).removeClass('sidebar_hidden').addClass('sidebar_shown');

        } else {
            $('#sidebar').css('overflow', 'hidden');
            $(ExpandableLayout.toggle).removeClass('sidebar_shown').addClass('sidebar_hidden');
        }
        
        if (ExpandableLayout.resizable) {
            $('#sidebar').resizable({
                minWidth: ExpandableLayout.min_width,
                maxWidth: 500,
                handles: 'e'
            }).bind('resize', ExpandableLayout.sidebarResized);
        }
    },
    
    
    setHeight: function() {
        var custom_header_height = ($('#custom_header').length) ? $('#custom_header').outerHeight() : 0;
        var custom_footer_height = ($('#custom_footer').length) ? $('#custom_footer').outerHeight() : 0;
        
        var areas_height = $('#header_div').outerHeight() + $('#footer').outerHeight() +
            custom_header_height + custom_footer_height + 1;
            
        var height = $(window).height() - areas_height;
        $('#container, #sidebar, #divider, #content').height(height);
        
        var left_block_width = ($('#sidebar').length) ? $('#sidebar').width() : 0;
        var width = $(window).width() - (left_block_width + 10);
        $('#content').css('width', width);
        
        var left_block_width = $('#sidebar').width();
        $('#content').css('left', left_block_width);
        $('#content').show();
    },


    resizeRightBlock: function(event, ui) {
        if (event) {
            event.stopPropagation();
        }
        
        var left_block_width = $('#sidebar').width();
        var right_block_width = $(window).width() - left_block_width;
        
        $('#content').css('left', left_block_width);
        $('#content').css('width', right_block_width);
        
        var divider_width = $('#divider').width();
        //var left = ($('#sidebar').width() > divider_width) ? $('#sidebar').width() - divider_width : $('#sidebar').width();
        var left = $('#sidebar').width();
        $('#divider').css('left', left);
        
        $('body').trigger('kbpMenuResized', [{width: left}]);
    },
    
    
    toggleSidebar: function() {
        var action = $('#sidebar_toggle').attr('title');
        var next_action = $('#sidebar_toggle').attr('data-title');
        
        $('#sidebar_toggle').attr('title', next_action);
        $('#sidebar_toggle').attr('data-title', action);
        
        var sidebar_width = $('#sidebar').width();
        if (sidebar_width > (ExpandableLayout.min_width + 1)) {
            ExpandableLayout.expanded_sidebar_width = $('#sidebar').width();
            
            $('#sidebar').css('overflow', 'hidden'); // ie fix
            $('#sidebar').width(ExpandableLayout.min_width + 1);
            
            $(ExpandableLayout.toggle).removeClass('sidebar_shown').addClass('sidebar_hidden');
            
        } else {
             // ie fixes
            $('#sidebar').css('overflow', 'auto');
            $('#sidebar').css('overflow-x', 'hidden');
            
            $('#sidebar').width(ExpandableLayout.expanded_sidebar_width);
            
            $(ExpandableLayout.toggle).removeClass('sidebar_hidden').addClass('sidebar_shown');
        }
        
        createCookie(ExpandableLayout.cookie_name, $('#sidebar').width(), ExpandableLayout.min_width);
        
        ExpandableLayout.resizeRightBlock();
    },
    
    
    sidebarResized: function() {
        ExpandableLayout.expanded_sidebar_width = $('#sidebar').width();
        createCookie(ExpandableLayout.cookie_name, $('#sidebar').width(), 0);    
        ExpandableLayout.resizeRightBlock();
        
        if (ExpandableLayout.expanded_sidebar_width > (ExpandableLayout.min_width + 1)) {
            $(ExpandableLayout.toggle).removeClass('sidebar_hidden').addClass('sidebar_shown');
            
        } else {
            $(ExpandableLayout.toggle).removeClass('sidebar_shown').addClass('sidebar_hidden');
            
            ExpandableLayout.expanded_sidebar_width = ExpandableLayout.default_width;
        }
    }
    
}