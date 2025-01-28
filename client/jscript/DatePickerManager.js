function DatePickerManager(id, options, year, month, day)  {
    
    this.hidden_field = $('#' + id);
    
    // creating a visible field
    this.field = $('<input type="text" size="14" />').attr('id', 'u' + id);
    this.field.insertBefore(this.hidden_field);
    
    this.max_date = new Date(options.current_date);
    if(options.max_date) {
        this.max_date = (options.max_date == 'skip') ? null : new Date(options.max_date);
    }
    
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
        maxDate: this.max_date,
        onSelect: function() {
            $(this).change();
            $('body').trigger('kbpErrorResolved', [{field: 'u' + id}]);
        }
    };
    
    if (options.button) {
        dp_options.showOn = 'button';
    }
    
    this.field.datepicker(dp_options);
    
    
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
        this.field.change(function() {
            var date_obj = _this.field.datepicker('getDate');
            dp.field.datepicker('option', 'minDate', date_obj);
            
            if (dp.field.val() == '') {
                dp.field.datepicker('setDate', date_obj);
            }
        });
    }
    
    
    if (year) { // setting a date
        this.setDate(year, month, day);
    }
}