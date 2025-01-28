var SearchMenu = {

    form_element: false,
    skip_show: false,
    enter_flag: false,
    
    init: function () {    
        SearchMenu.form_element = $('#ui_element');
        
		SearchMenu.form_element.find('.sb_down').bind('click', SearchMenu.showFilterOptions);
        $(document).mouseup(SearchMenu.hideFilterOptions);
		SearchMenu.form_element.find('.sb_dropdown input[type="checkbox"]').bind('click', SearchMenu.selectOption);
        
        $('.sb_up, .sb_down').keyup(SearchMenu.hideFilterOptionsOnEnter);
    },
    
    
    showFilterOptions: function(e) {
        if (!SearchMenu.skip_show) {
            
            if ($(e.target).attr('class') == 'sb_up') {
                SearchMenu.enter_flag = true;
            }
            
            SearchMenu.form_element.find('.sb_down')
                .addClass('sb_up')
                .removeClass('sb_down')
                .addBack()
                .find('.sb_dropdown')
                .show();
                
        } else {
            SearchMenu.skip_show = false;
        }
    },
    
	
    hideFilterOptions: function(e) {
        var search_block = $('ul.sb_dropdown');
        
        if (!search_block.is(e.target) && search_block.has(e.target).length === 0) {
            if ($('a.sb_up').is(e.target)) {
                SearchMenu.skip_show = true;
            }
            
            SearchMenu.form_element.find('.sb_up')
    		   .addClass('sb_down')
    		   .removeClass('sb_up')
    		   .addBack()
    		   .find('.sb_dropdown')
    		   .hide();
        } 
    },
    
    
    hideFilterOptionsOnEnter: function(e) {
        if (e.keyCode == 13 && SearchMenu.enter_flag) {
            SearchMenu.form_element.find('.sb_up')
    		   .addClass('sb_down')
    		   .removeClass('sb_up')
    		   .addBack()
    		   .find('.sb_dropdown')
    		   .hide();
               
            SearchMenu.enter_flag = false;
        }
    },  
    
	
    selectOption: function() {
        SearchMenu.form_element.find('.sb_dropdown input[type="checkbox"]').not(this).prop('checked', false);
        var category_id = $(this).attr('data-category');
        
        if (category_id) {
            SearchMenu.form_element.append('<input type="hidden" id="filter_category" name="c[]" />');
            $('#filter_category').val(category_id);
            
        } else {
            $('#filter_category').remove();
        }
    },
    
    
    highlightOption: function(el) {
        $('ul.sb_dropdown li').removeClass('selected');
        $(el).addClass('selected');
    }
    
}