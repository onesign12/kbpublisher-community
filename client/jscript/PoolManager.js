var PoolManager = {
    
    visible_display: 'inline',
    stick_msg: false,
    unstick_msg: false,
    
    
    add: function(id) {
        var pool = getCookie('kb_pool_');
        pool = (pool) ? $.parseJSON(pool) : [];
        
        if (pool.indexOf(id) == -1) {
            pool.push(id);
        }
        
        createCookie('kb_pool_', JSON.stringify(pool), 7);
        
        $('.pool_block').show();
        $('.pool_block a').show();
        
        var title = $('a.pool_block.login').tooltipster('content');
        title = title.replace(pool.length - 1, pool.length);
        $('.pool_block').attr('title', title);
        $('a.pool_block.login').tooltipster('content', title);
        
        $('.pool_num').attr('data-badge', pool.length)
        
        $('.pool_add_link').hide();
        $('.pool_delete_link').css('display', PoolManager.visible_display);
        
        // panel
        $('#stick_panel_item .hasBadgeIcon, #stick_panel_item2 .hasBadgeIcon').addClass('badgeIcon');
        $('#stick_panel_item').tooltipster('content', PoolManager.unstick_msg);
        $('#stick_panel_item2 div.icon_title').html(PoolManager.unstick_msg);
        $('#stick_panel_item2').attr('title', PoolManager.unstick_msg);
        
        $('#stick_panel_item a, #stick_panel_item2 a').attr('href', '#unpin');
        $('#stick_panel_item a, #stick_panel_item2 a').attr('onclick', 'PoolManager.remove('+id+');');
    },
    
    
    remove: function(id) {
        var pool = getCookie('kb_pool_');
        pool = (pool) ? $.parseJSON(pool) : [];
        
        var index = pool.indexOf(id);
        delete pool[index];
        pool.splice(index, 1);
        
        $('.pool_num').attr('data-badge', pool.length)
        
        if (pool.length == 0) {
            deleteCookie('kb_pool_', '/');
            $('.pool_block').hide();
            
        } else {
            createCookie('kb_pool_', JSON.stringify(pool), 7);
            $('.pool_block').show();
        }
        
        var title = $('a.pool_block.login').tooltipster('content');
        title = title.replace(pool.length + 1, pool.length);
        $('.pool_block').attr('title', title);
        $('a.pool_block.login').tooltipster('content', title);
        
        $('.pool_delete_link').hide();
        $('.pool_add_link').css('display', PoolManager.visible_display);
        
        // panel 
        $('#stick_panel_item .hasBadgeIcon, #stick_panel_item2 .hasBadgeIcon').removeClass('badgeIcon');
        $('#stick_panel_item').tooltipster('content', PoolManager.stick_msg);
        $('#stick_panel_item2 div.icon_title').html(PoolManager.stick_msg);
        $('#stick_panel_item2').attr('title', PoolManager.stick_msg);
        
        $('#stick_panel_item a, #stick_panel_item2 a').attr('href', '#pin');
        $('#stick_panel_item a, #stick_panel_item2 a').attr('onclick', 'PoolManager.add('+id+');');
    },
    
    
    replace: function(pool) {
        createCookie('kb_pool_', JSON.stringify(pool), 7);
    },
        
    
    empty: function() {
        deleteCookie('kb_pool_', '/');
    }
    
}