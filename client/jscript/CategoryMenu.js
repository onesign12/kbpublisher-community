function toggleCategory(id, base_href) {
    var category_padding = getPadding($('#menu_item_' + id));
    
    if ($('#menu_item_' + id).hasClass('category_loaded')) {
        
        var expand = $('#menu_item_' + id).hasClass('category_collapsed');
        var next_element = $('#menu_item_' + id).next();
        
        while (getPadding(next_element) > category_padding) {
            
            if (expand) {
                next_element.show(250);
                $('#menu_item_' + id).removeClass('category_collapsed');
                $('#menu_item_' + id).find('img').attr('src', base_href + '/client/images/icons/menu_category_expanded.svg');
                    
            } else {
                next_element.hide(250);
                $('#menu_item_' + id).addClass('category_collapsed');
                $('#menu_item_' + id).find('img').attr('src', base_href + '/client/images/icons/menu_category_collapsed.svg');
            }
            
            next_element = next_element.next();
        }
        
    } else {
        $('#menu_item_' + id).addClass('category_loaded');
        $('#menu_item_' + id).find('img').attr('src', base_href + '/client/images/icons/menu_category_expanded.svg');
        
        loading_img.css('paddingLeft', category_padding + 10);
        loading_img.insertAfter($('#menu_item_' + id));
        
        xajax_getCategoryChildren(id);
    }
}


function getPadding(el) {
    var padding_str = el.css('paddingLeft');
    if (!padding_str) {
        return 0;
    }
    
    var index = padding_str.indexOf('px');
    return parseInt(padding_str.substring(0, index)); 
}


function expandCategory(id, html) {
    loading_img.remove();
    $(html).insertAfter($('#menu_item_' + id));
}


function showAllEntries(id, html) {
    if ($('#menu_item_current_cat_end').length) {
        var prev_entries = $('#menu_item_current_cat_end').prevUntil('.category_loaded').addBack();
        prev_entries.remove();
    }
    
    if ($('#menu_item_current_cat_start').length) {
        var next_entries = $('#menu_item_current_cat_start').nextUntil('div:not([id^="menu_item_entry"])').addBack();
        next_entries.remove();
    }
    
    $(html).insertAfter('#menu_item_' + id);
}


function insertEntries(id, html) {
    id = '#menu_item__more_' + id;
    $(html).insertBefore(id);
    $(id).remove();
}