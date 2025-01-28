var SortShowMore = {
    
    up_msg: '',
    down_msg: '',
    loading_msg: '',
    
    
    init: function() {

        $('.sort_values').bind('change', function(){
            var val = this.options[this.selectedIndex].value;
            
            // bottom   
            if (val == 'show_bottom') {

                var category_id = SortShowMore.getCategory(this.name);

                // last element
				var index = this.options.length - 2;
				var index_val = this.options[index].value;
                
                $('#sort_values_' + category_id + ' option[value=show_bottom]').text(SortShowMore.loading_msg);
	
				xajax_getNextCategories('bottom', index_val, category_id);
                
            } else if (val == 'show_top') { // top
            
                var category_id = SortShowMore.getCategory(this.name);
                
                // first element
				var index_val = this.options[3].value;
                
                $('#sort_values_' + category_id + ' option[value=show_top]').text(SortShowMore.loading_msg);
                
                xajax_getNextCategories('top', index_val, category_id);
            }
            
        });
    },
    
    
    addNextBottomArticle: function(category_id, title, value, is_selected) {
        
        $('#sort_values_' + category_id + ' option[value=show_bottom]').text(SortShowMore.down_msg);
        
        var select = document.getElementById('sort_values_' + category_id);        
        var sel = new Option(title, value);
		
        select.insertBefore(sel, select.options[select.options.length - 1]);
        
        // ie fix
        select.options[select.options.length - 2].text = title;
        
        if (is_selected) select.selectedIndex = select.options.length - 2; 

    },
    
    
    addNextTopArticle: function(category_id, title, value, is_selected) {
        
        $('#sort_values_' + category_id + ' option[value=show_top]').text(SortShowMore.up_msg);
        
        var select = document.getElementById('sort_values_' + category_id);        
        var sel = new Option(title, value);

        select.insertBefore(sel, select.options[3]);
        
        // ie fix
        select.options[3].text = title;
        
        if (is_selected) select.selectedIndex = 3;

    },
    
    
    getCategory: function(name) {
        
        var pattern = /\[(\d*)\]/;
        var category = name.match(pattern);
        
		return category[1];
    },
    
    
    deleteShow: function(category_id, show_id) {        
        $('#sort_values_' + category_id + ' option[value=' + show_id + ']').remove();
    }
	
}