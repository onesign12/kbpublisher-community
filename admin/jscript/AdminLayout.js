var AdminLayout = {
    min_width: 60,
    menu_tooltip: false,
    sidebar_cookie_name: 'kb_admin_sidebar_status_',
    sidebar2_cookie_name: 'kb_admin_sidebar2_status_',
    
    init: function() {
        AdminLayout.setHeight();
        $(window).resize(AdminLayout.setHeight);
        
        var sidebar_width = $('#sidebar').width();
        if (sidebar_width > (AdminLayout.min_width + 1)) {
            $('#sidebar').css('overflow', 'auto');
            $('#sidebar').css('overflow-x', 'hidden');

        } else {
            $('#sidebar').css('overflow', 'hidden');
        }
    },
    
    
    setHeight: function() {
        var height = $(window).height() - $('#header').outerHeight();
        $('#container, #sidebar, #content').height(height);
        $('#sidebar2').height(height);
        
        AdminLayout.setWidth();
        
        $('#content').show();
    },


    resizeRightBlock: function(event, ui) {
        if (event) {
            event.stopPropagation();
        }
        
        AdminLayout.setWidth();
    },
    
    
    setWidth: function() {
        var sidebar_width = $('#sidebar').outerWidth();
        var sidebar2_width = ($('#sidebar2:visible').length) ? $('#sidebar2:visible').outerWidth() : 0;
        var left_block_width = sidebar_width + sidebar2_width;
        // var right_block_width = window.outerWidth - left_block_width;
        var right_block_width = window.innerWidth - left_block_width;
        
        /*if ($('sidebar2_container').length) {
            width -= 17;
        }*/
        
        $('#content').css('width', right_block_width);
        $('#content').css('left', left_block_width);
        
        $('#sidebar2').css('left', sidebar_width);
        
        $('.bottom_button').css('left', left_block_width);
        
        if (sidebar_width < 140) {
            $('.menu_text').hide();
            AdminLayout.menu_tooltip.tooltipster('enable');    
            
        } else {
            $('.menu_text').show();
            AdminLayout.menu_tooltip.tooltipster('disable'); 
        }
    },
    
    
    toggleSidebar: function(sidebar_id, module_name) {
        if (!sidebar_id) {
            sidebar_id = 'sidebar';
        }
        
        var sidebar_status = 0;
        var sidebar_width = $('#' + sidebar_id).width();
        if (sidebar_width > (AdminLayout.min_width + 1)) {
            $('#' + sidebar_id).css('overflow', 'hidden'); // ie fix
            
            $('#' + sidebar_id).removeClass('shown').addClass('hidden');
            
        } else {
             // ie fixes
            $('#' + sidebar_id).css('overflow', 'auto');
            $('#' + sidebar_id).css('overflow-x', 'hidden');
            
            $('#' + sidebar_id).removeClass('hidden').addClass('shown');
            sidebar_status = 1;
        }
        
        cookie_name = AdminLayout[sidebar_id + '_cookie_name'];
        if (sidebar_id == 'sidebar2') {
            var sidebar2_statuses = getCookie(cookie_name);
            sidebar2_statuses = (sidebar2_statuses) ? $.parseJSON(sidebar2_statuses) : {};
            
            sidebar2_statuses[module_name] = sidebar_status;
            cookie_value = JSON.stringify(sidebar2_statuses);
            
        } else {
            cookie_value = sidebar_status;
        }
        
        createCookie(cookie_name, cookie_value, 365);
        
        AdminLayout.resizeRightBlock();
    },
    
    
    toggleSidebar2: function(module_name) {
        return AdminLayout.toggleSidebar('sidebar2', module_name);
    }
}