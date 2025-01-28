var ErrorHighlighter = {
    
    fields: [],
    selector: false,
    func: false,
    type: false,
    ck_names: [],
    current_val: false,
    current_class: false,
    context: 'html, body',
    
    
    highlight: function(fields) {
		ErrorHighlighter.fields = fields;
		
        console.log('Error fields', fields);
        
        ErrorHighlighter.detectCkEditorInstances();
        
        $('body').bind('kbpErrorResolved', {}, function(e, params) {
            $('#' + params.field).add('*[name="' + params.field + '"]').removeClass('validationError');
            ErrorHighlighter.resolveError(params.field);
        });
        
        
        var scrolled = false;
        for (var field in fields) { //console.log('Field', field);
			var group = fields[field];
			
			escaped_field = field.replace(/[[]/g, '\\\\[');
           	escaped_field = escaped_field.replace(/]/g, '\\\\]');
			
            field = field.replace(/[[]/g, '\\[');
            field = field.replace(/]/g, '\\]');
            
            if (ErrorHighlighter.isCkEditor(field)) { // ckeditor
                ErrorHighlighter.selector = $('#cke_' + field); 
                
                var editor = CKEDITOR.instances[field];
                editor.editable().addClass('validationError');
                
                // focus
                editor.on('focus', ErrorHighlighter.getResetCkErrorFunction(field));
                
                // blur
                if (group == 'required') {
                    editor.on('blur', ErrorHighlighter.getRequiredCkBlurFunction(field));
                    
                } else {
                    // we don't have such situation
                }
                
            } else {
                ErrorHighlighter.selector = $('#' + field).
                                            add('#' + field + '_button').
                                            add('.' + field + '_error').
                                            add('*[name="' + field + '"]').
                                            add('*[name="' + escaped_field + '"]').
                                            add('*[name="' + field + '[]"]');
                                            
                ErrorHighlighter.selector.addClass('validationError');
                
                // focus
                ErrorHighlighter.selector.off('focus');
                
                if (ErrorHighlighter.selector.hasClass('_tooltip_password')) {
                    ErrorHighlighter.selector.on('focus', function() {
                        $(this).tooltipster('show');
                    });
                }
                    
                ErrorHighlighter.selector.focus(ErrorHighlighter.getResetErrorFunction(field));
                ErrorHighlighter.selector.click(ErrorHighlighter.getResetErrorFunction2(field)); // onfocus not working for divs
                
                // blur
                var filter_selector = 'input[type="text"], input[type="password"], textarea, select';
                if (group == 'required') {
                    ErrorHighlighter.selector.filter(filter_selector).blur(ErrorHighlighter.getRequiredBlurFunction(field));
                    
                } else {
                    ErrorHighlighter.selector.filter(filter_selector).off('blur');
                    
                    if (ErrorHighlighter.selector.hasClass('_tooltip_password')) {
                        ErrorHighlighter.selector.filter(filter_selector).on('blur', function() {
                            $(this).tooltipster('hide');
                        });
                    }
                    
                    ErrorHighlighter.selector.filter(filter_selector).blur(ErrorHighlighter.resendForm);
                }
            }
            
            if (!scrolled) {
                if (!ErrorHighlighter.selector.isOnScreen()) {
                    $(ErrorHighlighter.context).animate({
                        scrollTop: ErrorHighlighter.selector.offset().top - 60
                    }, 500);
                }
                scrolled = true;
            }
        }
    },
    
    
    
    // determining the names of all ckeditor instances
    detectCkEditorInstances: function() {
        if (window.CKEDITOR) {
            for (var i in CKEDITOR.instances) {
                ErrorHighlighter.ck_names.push(i);
            }
        }
    },
    
    
    isCkEditor: function(field) {
        return ($.inArray(field, ErrorHighlighter.ck_names) !== -1);
    },
    
    
    getResetErrorFunction: function(field) {
        return function() {
            ErrorHighlighter.current_val = $(this).val();
            ErrorHighlighter.current_class = $(this).attr('class');
            
            $(this).removeClass('validationError');
            $('.' + field + '_error').removeClass('validationError');
        };
    },
    
    
    getResetErrorFunction2: function(field) {
        return function() {
            $('#' + field).add('.' + field + '_error').removeClass('validationError');
            $('#' + field).find('*').removeClass('validationError');
            ErrorHighlighter.resolveError(field);
        };
    },
    
    
    getResetCkErrorFunction: function(field) {
        return function() {
            if (this.editable) {
                this.editable().removeClass('validationError');
            }
        };
    },
	
	
	getRequiredBlurFunction: function(field) {
		return function() {
            var is_empty = !$(this).val();
            
            if (field == 'category') {
                is_empty = !($('#category option').length);
            }
            
			if (is_empty) {
	            $(this).addClass('validationError');
                
                if ($.isEmptyObject(ErrorHighlighter.fields)) {
                    ErrorHighlighter.resendForm(this);
                }
	            
	        } else { // in group
				ErrorHighlighter.resolveError(field);
	        }
		};
	},
    
    
    getRequiredCkBlurFunction: function(field) {
        return function() {
            if (!this.getData()) {
                this.editable().addClass('validationError');
                
            } else {
                ErrorHighlighter.resolveError(field);
            }
        };
    },
    
    
    resolveError: function(field) {
        var group = ErrorHighlighter.fields[field];
        delete ErrorHighlighter.fields[field];
                    
        var delete_growl = true;
        for (var i in ErrorHighlighter.fields) {
            // if (ErrorHighlighter.fields[i] == group) {
                delete_growl = false;
            // }
        }
        // console.log('resolveError');
        // console.log(delete_growl);
        // console.log(group);
        // console.log(field);
        // console.log(ErrorHighlighter.fields);
        
        if (delete_growl) {
            // $('#growl_' + group).remove();
            // $('#growl_error').remove();
            emptyGrowls();
        }
    },
	
	
	resendForm: function(el) {
        
        if (!el || el.hasOwnProperty('originalEvent')) {
            el = this;
        }
        
        if ($(el).val() == ErrorHighlighter.current_val) {
            $(el).attr('class', ErrorHighlighter.current_class);
            
        } else {
            ErrorHighlighter.selector.removeClass('validationError');
            
            ErrorHighlighter.current_val = false;
            ErrorHighlighter.current_class = false;
            
            var values = FormCollector.collect(ErrorHighlighter.type);
            var options = {
                callback: 'skip'
            };
            
            window['xajax_' + ErrorHighlighter.func](values, options);
        }
    }
	
}