var CategoryManager = {
    text_input_id: 'category_filter',
    block_id: 'category_list',
    
    displayed_results_limit: 20,
    load_results_limit: 20,
    width: 600,
    max_height: 200,
    creation_allowed: false,
    non_active_categories: [],
    limit: 0,
    insert_url: '',
    referer: '',
    status_icon: true,
    related_categories: false,
    msg: {},
    kb_url: '',
    
    start_results_limit: false,
    ajax_loaded: false,
    
    top_button_id: false,
    top_mode: true,
    
    term: '',
    
    init: function(categories) {
        CategoryManager.start_results_limit = CategoryManager.displayed_results_limit;
        
        var input = $('#' + CategoryManager.text_input_id);
        
        if (CategoryManager.top_button_id && CategoryManager.top_mode) { // top level only
            var _categories = [];
            
            for (var i in categories) {
                if (categories[i]['value'] == 0 || categories[i]['parent_id'] == 0) {
                    _categories.push(categories[i]);
                }
            }
            
        } else {
            var _categories = categories;
        }
        
        for (var i in _categories) {
            if (_categories[i]['disabled']) {
                CategoryManager.checkRelatedCategories(_categories[i]['value']);
            }
        }
        
        
        CategoryManager.width = input.width() + 5;
        
        input.autocomplete({
            minLength: 0,
            source: function(request, response) { // no scroll
                CategoryManager.term = request.term;
                
                var results = $.ui.autocomplete.filter(_categories, request.term);
                var data = results.slice(0, CategoryManager.displayed_results_limit);
                
                if (data.length < results.length) {
                    data.push({label: '...', value: 'more'});
                }
                response(data);
            },
            select: CategoryManager.select,
            focus: CategoryManager.copyNameToInput,
            change: CategoryManager.validate,
            open: CategoryManager.setFocus
        });
        
        input.focus(CategoryManager.showEntireList);
        input.keypress(CategoryManager.checkForCreation);
        
        input.on('autocompleteresponse', CategoryManager.showInfoItem);
        
        // overrides
        input.data('ui-autocomplete')._resizeMenu = CategoryManager.resizeMenu;
        input.data('ui-autocomplete')._renderItem = CategoryManager.renderItemWithHighlight;
    },
    
    
    select: function(e, ui) {
        e.preventDefault();
        
        if (ui.item.value == 'add') {
            if (CategoryManager.creation_allowed) {
                CategoryManager.openCategoryPopup();
            }
            
        } else if (ui.item.value != 'more' && !ui.item.disabled) {
            CategoryManager.addCategory(ui.item.value, ui.item.label);
        }
        
        $(this).autocomplete('close');
    },
    
    
    copyNameToInput: function(e, ui) {
        e.preventDefault();
        
        if (ui.item.value == 'more') {
            CategoryManager.displayed_results_limit += CategoryManager.load_results_limit;
            CategoryManager.ajax_loaded = true;
            $(this).autocomplete('search');
        }
        
        // populate input with choice 
        // var input_value = ui.item.label;
        // if ($.inArray(ui.item.value, ['more', 'add', 0]) != -1) {
        //     input_value = CategoryManager.term;
        // }
        //
        // $('#' + CategoryManager.text_input_id).val(input_value);
    },
    
    
    validate: function(e, ui) {
        if (!ui.item) {
            //$(this).val('');
            $(this).attr('title', '');
        }
    },
    
    
    setFocus: function(e, ui) {
        if (CategoryManager.ajax_loaded) {
            var menu = $('#' + CategoryManager.text_input_id).data('ui-autocomplete').menu;
            var items = $('li', menu.element);
            var item = items.eq(CategoryManager.displayed_results_limit - CategoryManager.load_results_limit);
            
            menu.focus(null, item);
            
            CategoryManager.ajax_loaded = false;
            
        } else {
            CategoryManager.displayed_results_limit = CategoryManager.start_results_limit;
        }
    },
    
    
    showEntireList: function() {
        $(this).autocomplete('search');
    },
    
    
    checkForCreation: function(e) {
    	if (e.which == '13') {
            e.preventDefault(); // prevent form submit
            
            if (CategoryManager.creation_allowed) {
                $('#' + CategoryManager.text_input_id).autocomplete('close');
                
                CategoryManager.openCategoryPopup();
            }
        }
    },
    
    
    showInfoItem: function(e, ui) {
        var autoFocus = false;
        if (ui.content.length > 0 && $('#' + CategoryManager.text_input_id).val().toUpperCase() == ui.content[0].label.toUpperCase()) {
            autoFocus = true;
        }
        
        $('#' + CategoryManager.text_input_id).autocomplete('option', 'autoFocus', autoFocus);
        
        if (ui.content.length == 0) {
            var label = (CategoryManager.creation_allowed) ? CategoryManager.msg.enter_category : CategoryManager.msg.no_matches;
            
        } else {
            if (!CategoryManager.creation_allowed) {
                return;
            }
            
            var label = (autoFocus) ? CategoryManager.msg.enter_category3 : CategoryManager.msg.enter_category2;
        }
        
        ui.content.push({label: label, value: 'add'});
    },
    
    
    resizeMenu: function () {
        var ul = this.menu.element;
        //ul.outerWidth(this.element.outerWidth()); // input width
        
        ul.css('max-height', CategoryManager.max_height);
        ul.css('overflow-y', 'auto');
        ul.css('overflow-x', 'hidden');
        
        ul.outerWidth(CategoryManager.width);
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
    },
    
    
    enableSorting: function() {
    	$('#' + CategoryManager.block_id).sortable({
            placeholder: 'view_placeholder'
        });
        
        $('#' + CategoryManager.block_id).disableSelection();
    },
    
    
    addCategory: function(value, text, auto_fired) {
        if ($('input[value="' + value + '"]').length) {
            return;
        }
        
        var status_icon_name = 'check-mark';
        var status_icon_class = 'published';
        var status_title = CategoryManager.msg.status_published;
        
        var text_block = '<span>' + text + '</span>';
        
        var is_non_active = $.inArray(parseInt(value), CategoryManager.non_active_categories);
        if (is_non_active != -1) {
            if (!auto_fired) {
                alert(CategoryManager.msg.non_active_category);
            }
            
            status_icon_name = 'square';
            status_icon_class = 'not_published';
            var status_title = CategoryManager.msg.status_not_published;
            
            text_block = '<span style="color: grey;">' + text + '</span>';
        }
        
        $('#category_row').show();
        
        var items_count = $('#' + CategoryManager.block_id).children().length;
        if (items_count > 0) {
            $('#also_listed_block').show();
        }
        
        // limit / all
        if (value == 0 || CategoryManager.category_limit) {
        	for (var i in categories) {
        	    if ($('#' + CategoryManager.block_id + ' input[value="' + categories[i]['value'] + '"]').length) {
        	        categories[i]['disabled'] = false;
        	    }
			}
			
			$('#' + CategoryManager.block_id).empty();
        }
        
        var status_icon = '';
        if (CategoryManager.status_icon && value) {
            status_icon = '<img src="../client/images/icons/' + status_icon_name + '.svg" title="' +
                status_title + '" class="category3_img ' + status_icon_class + '"/>';
        }
        
        var delete_icon = '<img src="' + CategoryManager.url + 'images/icons/x-mark.svg" width="8" height="8" title="' + CategoryManager.msg.delete
            + '" onclick="CategoryManager.deleteCategory(this);" class="category3_img" />';
        
        var html = '<li class="category3">'
            + text_block
            + delete_icon
            + status_icon
            + '<input type="hidden" name="sort_id[]" value="' + value + '" /></li>'; 
        $('#' + CategoryManager.block_id).append(html);
        
        // $('._tooltip').tooltipster({
        //     contentAsHTML: true,
        //     theme: 'tooltipster-kbp',
        //     interactive: true,
        // });
        
        if (value == 0) { // disabling all
        	for (var i in categories) {
		        categories[i]['disabled'] = true;
    		}
        	
        } else {
            if (window.xajax_getCategoryPrivateInfo && CategoryManager.status_icon) {
                xajax_getCategoryPrivateInfo(value, text);
            }
        }
        
        $('#submit_button').focus();
        
        $('#' + CategoryManager.text_input_id).val('');
        
        // disabling
        for (var i in categories) {
	        if (categories[i]['value'] == value) {
	            /*delete categories[i];
	            categories.splice(i, 1);*/
	           
	            categories[i]['disabled'] = true;
	        }
    	}
    	
    	CategoryManager.checkRelatedCategories(value);
    },
    
    
    checkRelatedCategories: function(value) {
        if (CategoryManager.related_categories) {
            var parents = CategoryManager.related_categories[value];
            
            for (i in parents) { // deleting and disabling
                var el = $('input[type="hidden"][value="' + parents[i] + '"]');
                if (el.length) {
                    el.parent().remove();
                }
                
                for (var j in categories) {
                    if (categories[j]['value'] == parents[i]) {                       
                        categories[j]['disabled'] = true;
                    }
                }
            }
        }
    },
    
    
    deleteCategory: function(el) {
        $(el).parent().fadeOut(500, function() {
        	var cat_id = $(this).find('input[name="sort_id[]"]').val();
        	
            $(this).remove();
            
            var items_count = $('#' + CategoryManager.block_id).children().length;
            if (items_count == 0) {
                $('#category_row').hide();
            }
            
            if (items_count == 1) {
                $('#also_listed_block').hide();
            }
            
            if (cat_id == 0) {
            	for (var i in categories) {
		        	categories[i]['disabled'] = false;
    			}
    			
            } else {
            	for (var i in categories) {
			        if (categories[i]['value'] == cat_id) {		           
			            categories[i]['disabled'] = false;
			        }
    			}
    			
    			
    			if (CategoryManager.related_categories) {
    			    var entry_to_delete_parents = CategoryManager.related_categories[cat_id];
                    var selected_entries_parents = [];
                    
                    $('#' + CategoryManager.block_id).find('input[type="hidden"]').each(function() {
                        var parents = CategoryManager.related_categories[this.value];
                        selected_entries_parents = selected_entries_parents.concat(parents);
                    });
                    
                    var entries_to_enable = [];
                    for (var i in entry_to_delete_parents) {
                        if ($.inArray(entry_to_delete_parents[i], selected_entries_parents) == -1) {
                            entries_to_enable.push(entry_to_delete_parents[i]);
                        }
                    }
                    
                    for (var i in entries_to_enable) {
                        if (categories[i]['value'] == entries_to_enable[i]) {                       
                            categories[i]['disabled'] = false;
                        }
                    }
                }
            }
        });
    },
    
    
    openCategoryPopup: function() {
        var referer = CategoryManager.referer;
        var extra_params = 'referer=' + referer + '&category_name=' + encodeURIComponent($('#' + CategoryManager.text_input_id).val());
        
        var url = CategoryManager.insert_url + '&' + extra_params;
        PopupManager.create(url, 'r', 'r');
    },
    
    
    toggleTopCategories: function() {
        CategoryManager.top_mode = !CategoryManager.top_mode;
        
        if (CategoryManager.top_mode) {
            $('#' + CategoryManager.top_button_id).removeClass('disabled');
            
        } else {
            $('#' + CategoryManager.top_button_id).addClass('disabled');
        }
        
        CategoryManager.init(categories);
        $('#' + CategoryManager.text_input_id).focus();
    }
}