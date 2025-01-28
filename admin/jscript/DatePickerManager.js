function DatePickerManager(id, options, year, month, day)  {
    
    this.hidden_field = $('#' + id);
    
    // creating a visible field
    this.field = $('<input type="text" />').attr('id', 'u' + id);
    this.field.insertBefore(this.hidden_field);
    
    if (options.class_name) {
        this.field.addClass(options.class_name);
    }
    
    var dp_options = {
        changeMonth: true,
        changeYear: true,
        dateFormat: options.format,
        firstDay: options.week_start,
        altField: '#' + id,
        altFormat: 'yymmdd',
        maxDate: new Date(options.current_date)
    };
    
    if (options.button) {
        dp_options.showOn = 'button';
    }
    
    if (options.type == 'week') {
        dp_options[options.mode + 'Field'] = '#u' + id;
        this.field.weekpicker(dp_options);
        
    } else {
        this.field.datepicker(dp_options);
    }
    
    
    this.setDate = function(year, month, day) {
        var date_obj = new Date(year, month, day);
        this.field.datepicker('setDate', date_obj);
    }
    
    
    this.setMinDate = function(date) {
        var min_date = new Date(date);
        this.field.datepicker('option', 'minDate', min_date);
    }
    
     
    this.bindTo = function(dp) {
        var _this = this;
        
        if (options.type == 'week') {
            
            this.field.weekpicker('option', 'onSelect', function() {
                var date_obj = _this.field.datepicker('getDate');
                dp.field.datepicker('option', 'minDate', date_obj);
                
                var date_obj_to = new Date(date_obj.getTime());
                date_obj_to.setDate(date_obj.getDate() + 6);
                
                if (dp.field.val() == '') {
                    dp.field.datepicker('setDate', date_obj_to);
                }
            });
            
        } else {
            this.field.change(function() {
                var date_obj = _this.field.datepicker('getDate');
                dp.field.datepicker('option', 'minDate', date_obj);
                
                if (dp.field.val() == '') {
                    dp.field.datepicker('setDate', date_obj);
                }
            });
        }
    }
    
    
    if (year) { // setting a date
        this.setDate(year, month, day);
    }
}