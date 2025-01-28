var CategoriesInputHandler = {
    text_input_id: 'filter',
    hidden_input_id: 'filterCategoryId',
    displayed_results_limit: 20,
    load_results_limit: 20,
    width: 600,
    maxHeight: 200,
    reset_option: true,
    
    start_results_limit: false,
    ajax_loaded: false,
    
    term: '',
    
    init: function(categories) {
        CategoriesInputHandler.start_results_limit = CategoriesInputHandler.displayed_results_limit;
        
        var input = $('#' + CategoriesInputHandler.text_input_id);
        
        input.autocomplete({
            minLength: 0,
            source: function(request, response) { // no scroll
                CategoriesInputHandler.term = request.term;
                
                var results = $.ui.autocomplete.filter(categories, request.term);
                var data = results.slice(0, CategoriesInputHandler.displayed_results_limit);
                
                var selected_category = $('#' + CategoriesInputHandler.hidden_input_id).val();
                if (selected_category != '0' && CategoriesInputHandler.reset_option) {
                    data.unshift({label: '__', value: 'reset'});
                }
                
                if (data.length < results.length) {
                    data.push({label: '...', value: 'more'});
                }
                response(data);
            },
            select: CategoriesInputHandler.select,
            focus: CategoriesInputHandler.copyNameToInput,
            change: CategoriesInputHandler.validate,
            open: CategoriesInputHandler.setFocus
        });
        
        input.click(CategoriesInputHandler.showEntireList);
        
        // overrides
        input.data('ui-autocomplete')._resizeMenu = CategoriesInputHandler.resizeMenu;
        input.data('ui-autocomplete')._renderItem = CategoriesInputHandler.renderItemWithHighlight;
        
        CategoriesInputHandler.input = input;
    },
    
    
    select: function(e, ui) {
        e.preventDefault();
        
        if (ui.item.value == 'reset') {
            $('#' + CategoriesInputHandler.text_input_id).val('');
            $('#' + CategoriesInputHandler.text_input_id).attr('title', '');
            $('#' + CategoriesInputHandler.hidden_input_id).val('').trigger('change');
            
            
            $('body').trigger('kbpCategoryReset', [{id: ui.item.value, text: ui.item.label}]);
            return;
        }
        
        if (ui.item.value != 'more') {
            $('body').trigger('kbpCategorySelected', [{id: ui.item.value, text: ui.item.label}]);
        }
    },
    
    
    copyNameToInput: function(e, ui) {
        e.preventDefault();
        
        var input_value = ui.item.label;
        if ($.inArray(ui.item.value, ['more', 'add', 0]) != -1) {
            input_value = CategoriesInputHandler.term;
            
        } else if (ui.item.value == 'reset') {
            input_value = '';
        }
        
        $('#' + CategoriesInputHandler.text_input_id).val(input_value);
        
        if (ui.item.value == 'more') {
            CategoriesInputHandler.displayed_results_limit += CategoriesInputHandler.load_results_limit;
            CategoriesInputHandler.ajax_loaded = true;
            $(this).autocomplete('search');
        }
        
        
    },
    
    
    validate: function(e, ui) {
        if (!ui.item) {
            $(this).val('');
            $(this).attr('title', '');
            $('#' + CategoriesInputHandler.hidden_input_id).val('').trigger('change');
        }
    },
    
    
    setFocus: function(e, ui) {
        if (CategoriesInputHandler.ajax_loaded) {
            var menu = $('#' + CategoriesInputHandler.text_input_id).data('ui-autocomplete').menu;
            var items = $('li', menu.element);
            var item = items.eq(CategoriesInputHandler.displayed_results_limit - CategoriesInputHandler.load_results_limit);
            
            menu.focus(null, item);
            
            CategoriesInputHandler.ajax_loaded = false;
            
        } else {
            CategoriesInputHandler.displayed_results_limit = CategoriesInputHandler.start_results_limit;
        }
    },
    
    
    showEntireList: function() {
        $(this).autocomplete('search');
    },
    
    
    resizeMenu: function () {
        var ul = this.menu.element;
        //ul.outerWidth(this.element.outerWidth()); // input width
        
        ul.css('max-height', CategoriesInputHandler.maxHeight);
        ul.css('overflow-y', 'auto');
        ul.css('overflow-x', 'hidden');
        
        ul.outerWidth(CategoriesInputHandler.width);
    },
    
    
    renderItemWithHighlight: function(ul, item) {
        if (item.value == 'more' || item.disabled) {
            var item_html = '<li><a style="color: #cccccc !important;">' + item.label + '</a></li>';
            return $(item_html).appendTo(ul);
            
        } else if (item.value == 'add') {
            var item_html = '<li><a style="color: grey !important;text-align: center;">' + item.label + '</a></li>';
            return $(item_html).appendTo(ul);
            
        } else {
            var pattern = '(' + $.ui.autocomplete.escapeRegex(this.term) + ')';
            var re = new RegExp(pattern, 'gi');
            
            var t = item.label.replace(re, '<span style="background: yellow;">$1</span>');
            return $('<li></li>').data('ui-autocomplete-item', item).append('<a>' + t + '</a>').appendTo(ul);
        }
    }
}