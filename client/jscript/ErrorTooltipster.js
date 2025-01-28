var ErrorTooltipster = {
    
    fields: [],
    selector: false,
    func: false,
    type: false,
    ck_names: [],
    current_val: false,
    current_class: false,
    
    
    highlight: function(fields) {
		ErrorTooltipster.fields = fields;
		
        console.log('Error fields', fields);
        
        var focused = $(':focus');
        var focused_id = focused.attr('id');
        
        $('body').bind('kbpErrorResolved', {}, function(e, params) {
            $('#' + params.field).add('*[name="' + params.field + '"]').removeClass('validationError');
            ErrorTooltipster.resolveError(params.field);
        });
        
        var instances = $.tooltipster.instances();
        $.each(instances, function(i, instance){
            var element = instance.elementOrigin();
            instance.close();
            
            if ($(element).hasClass('_error_tooltip')) {
                instance.destroy();
            }
        });
        
        var scrolled = false;
        var tooltip_shown = false;
        var tooltip_selector = false;
        
        for (var field in fields) {
			var error_msg = fields[field]['msg'];
            var error_key = fields[field]['key'];
			
            // growl for csrf
            $("div.growl").remove();
            // $("#errorBoxMessageDivCsrf").remove();
            $("#errorBoxMessageDiv").remove();
            if(error_key == 'csrf') {
                $.growl.error({title: '', message: error_msg, fixed: true});
                // var growl = $.growl.error({title: '', message: error_msg, fixed: true, size: 'large', location: 'tc'});
                // we may want to add csrf message as box above form 
                // var html = '<div id="errorBoxMessageDivCsrf" class="boxMsgDiv error">'+error_msg+'</div>';
                // $(html).insertBefore("div.niceForm");
                return;
            }
            
            
            field = field.replace(/[[]/g, '\\[');
            field = field.replace(/]/g, '\\]');
            
            ErrorTooltipster.selector = $('#' + field).
                                        add('#' + field + '_button').
                                        add('.' + field + '_error').
                                        add('*[name="' + field + '"]:not([type="radio"])').
                                        add('*[name="' + field + '[]"]:not([type="checkbox"])');
            
            console.log(field, $('#' + field).get(0));
            
            // focus
            ErrorTooltipster.selector.off('focus');
            
            var tag_name = ErrorTooltipster.selector.prop('tagName');
            
            if (tag_name == 'DIV') {
                ErrorTooltipster.selector.click(ErrorTooltipster.getResetErrorFunction2(field));
            
            } else if (tag_name == 'LABEL') { // eleontev 2018.10.09 
                ErrorTooltipster.selector.click(ErrorTooltipster.getResetErrorFunction2(field));
                
            } else {
                ErrorTooltipster.selector.focus(ErrorTooltipster.getResetErrorFunction(field, error_msg));
            }
            
            
            ErrorTooltipster.selector.tooltipster({
                contentAsHTML: true,
                content: error_msg,
                theme: ['tooltipster-kbp_error'],
                interactive: true,
                side: ['right', 'top'],
                maxWidth: 300,
                multiple: true,
                trigger: 'custom',
                animationDuration: [0, 0],
                zIndex: 1000 // December 22, 2020 eleontev
                // zIndex: 999
            });
            
            if (!tooltip_shown) {
                tooltip_selector = ErrorTooltipster.selector;
                tooltip_shown = true;
            }
            
            if (focused_id == field) {
                tooltip_selector = ErrorTooltipster.selector;
                tooltip_shown = true;
                
            } else {
                if (tag_name != 'LABEL') { // eleontev 2018.10.09 
                    ErrorTooltipster.selector.addClass('validationError');
                }
            }
            
            ErrorTooltipster.selector.addClass('_error_tooltip');
            
            // blur
            ErrorTooltipster.selector.off('blur');
            ErrorTooltipster.selector.blur(ErrorTooltipster.getBlurFunction(field, error_key));
            
            if (!scrolled) {
                if (!ErrorTooltipster.selector.isOnScreen()) {
                    $('html, body').animate({
                        scrollTop: ErrorTooltipster.selector.offset().top - 20
                    }, 500);
                }
                scrolled = true;
            }
        }
        
        tooltip_selector.tooltipster('show');
        ErrorTooltipster.selector.focus();
    },
    
    
    getResetErrorFunction: function(field, error_msg) {
        return function() {
            ErrorTooltipster.current_val = $(this).val();
            ErrorTooltipster.current_class = $(this).attr('class');
            
            // closing all tooltips
            var instances = $.tooltipster.instances();
            $.each(instances, function(i, instance){
                instance.close();
            });
            
            // new one
            /*$(this).tooltipster({
                contentAsHTML: true,
                content: error_msg,
                theme: ['tooltipster-kbp_error'],
                interactive: true,
                side: ['right'],
                maxWidth: 300,
                multiple: true,
                trigger: 'custom'
            });*/
            $(this).tooltipster('show');
            
            $(this).removeClass('validationError');
            $('.' + field + '_error').removeClass('validationError');
        };
    },
    
    
    getResetErrorFunction2: function(field) {
        return function() {
            // closing all tooltips
            var instances = $.tooltipster.instances();
            $.each(instances, function(i, instance){
                instance.close();
            });
            
            $('#' + field).add('.' + field + '_error').removeClass('validationError');
            $('#' + field).find('*').removeClass('validationError');
            ErrorTooltipster.resolveError(field);
        };
    },
	
	
	getBlurFunction: function(field, error_key) {
		return function() {
            var resolved = false;
            
            /*if (error_key == 'email') {
                var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
                resolved = regex.test($(this).val());
                
            } else {*/ // required
                resolved = ($(this).val());
            //}
            
            if (error_key != 'required') {
                
                //if ($.isEmptyObject(ErrorTooltipster.fields)) {
                    ErrorTooltipster.resendForm(this);
                //}
                
            } else {
                if (resolved) {
                    //ErrorTooltipster.resolveError(field);
                    // closing all tooltips
                    var instances = $.tooltipster.instances();
                    $.each(instances, function(i, instance){
                        instance.close();
                    });
                    
                    $(this).off('focus');
                    $(this).off('blur');
                    $(this).off('click');
                    
                } else {
                    $(this).addClass('validationError');
                }
            }
		};
	},
    
    
    resolveError: function(field) {
        
        var instances = $.tooltipster.instances();
        $.each(instances, function(i, instance){
            var element = instance.elementOrigin();
            instance.close();
            
            if ($(element).hasClass('_error_tooltip')) {
                instance.destroy();
            }
        });
    },
	
	
	resendForm: function(el) {
        
        if (!el) {
            el = this;
        }
        
        // closing all tooltips
        var instances = $.tooltipster.instances();
        $.each(instances, function(i, instance){
            instance.close();
        });
        
        if ($(el).val() == ErrorTooltipster.current_val) {
            $(el).attr('class', ErrorTooltipster.current_class);
            
        } else {
            ErrorTooltipster.selector.removeClass('validationError');
            
            ErrorTooltipster.current_val = false;
            ErrorTooltipster.current_class = false;
            
            var values = FormCollector.collect(ErrorTooltipster.type);
            var options = {
                callback: 'skip'
            };
            
            window['xajax_validate'](values, options);
            
            $(el).off('focus');
            $(el).off('blur');
        }
    }
	
}