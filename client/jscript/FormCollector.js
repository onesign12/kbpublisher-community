var FormCollector = {
    
    form_id: 'aContentForm',
    extra_field_callback: false,
    
    
    serializeForm: function(values) {
        // form
        $.each($('#' + FormCollector.form_id).serializeObject(), function(k, v) {
            values[k] = v;
        });
        
        // if($('#g-recaptcha-response').length) {
            // values['g-recaptcha-response'] = $('#g-recaptcha-response').serializeObject();
            // values['g-recaptcha-response'] = $('#g-recaptcha-response').serialize();
            // console.log($('#g-recaptcha-response').val());
        // }
        
        // recaptcha
        var grr = $.trim($("#g-recaptcha-response").val());
        if (grr) {
            values['g-recaptcha-response'] = grr;
        }
        
        
        // console.log('Fuck');
        console.log(values);
        // files
        values['_files'] = [];
        $('input[type=file]').each(function() {
            var name = $(this).attr('name');
            if (name) {
                var filename = $(this).val().split('\\').pop();
                if (filename) {
                    values['_files'][name] = {
                        name: filename,
                        size: $(this)[0].files[0].size
                    };
                }
            }
        });
    },
    
    
    parseField: function(field, values) {
        if (field.name.slice(-2) == '[]') { // array
            var name = field.name.slice(0, - 2);
            
            if (!values[name]) {
                values[name] = [];
            }
            
            values[name].push(field.value);
            
        } else {
            values[field.name] = field.value;
        }
    },
    

    collect: function(type) {
        var values = {};
        
        var types = {
            'emode': function(values) {
                values['custom'] = [];
                values['schedule_on'] = {};
                values['schedule'] = {1 : {'date': {}}, 2: {'date': {}}};
                
                $('#' + FormCollector.form_id).find('input[type="hidden"]').each(function() {
                    
                    if (this.name.indexOf('custom[') == 0) { // custom field
                        var is_array = this.name.slice(-2) == '[]';
                        var pattern = /\[(\d*)\]/i;
                        
                        var matches = this.name.match(pattern);
                        var custom_id = matches[1];
            
                        FormCollector.parseField({
                            name: (is_array) ? custom_id + '[]' : custom_id,
                            value: this.value
                        }, values['custom']);
                        
                    } else if (this.name.indexOf('schedule') == 0) {
                        console.log(this.name)
                        
                        var reg = /schedule\[(\d)\]\[(date|st|note)\]/;
                        var reg2 = /schedule_on\[(\d)\]/;
                        
                        var val = this.name.match(reg);
                        var val2 = this.name.match(reg2);
                        
                        if (val) {
                            values['schedule'][val[1]][val[2]] = this.value;
                                           
                        } else if (val2) {
                            if (this.value) {
                                values['schedule_on'][val2[1]] = 1;                    
                            }
                        }
                        
                    } else {
                        FormCollector.parseField(this, values);
                    }
                });
                
                if (window.oEditor) {
                    //values['body'] = oEditor.getData();
                }
            },
            
            'setting': function(values) {
                FormCollector.serializeForm(values);
            },
            
            'regular': function(values) {
                FormCollector.serializeForm(values);
                
        		// article
        		var select_fields = new Array('category', 'role_read', 'role_write', 'mustread_role');
        		var input_fields = new Array('related', 'related_ref', 'attachment');
        		
                // sort order
                values['sort_values'] = {};
                var order_reg = /sort_values\[(\d*)\]/; 
        
                $('select[name^=sort_values]').each(function() {
                    var val = this.name.match(order_reg);
                    values['sort_values'][val[1]] = this.value;
                });
                
        
                // special select, category, roles
                for (var i = 0;i < select_fields.length;i ++) {
                    var name = select_fields[i];
                    console.log(name);
                    
                    // $('select[name^=' + name + ']').each(function() { // not sure why this link with can't catch mustread[role]
                    $('select[id^=' + name + ']').each(function() { // not sure why this link with can't catch mustread[role]
                        if(name == 'mustread_role') {
                            values['mustread']['role'] = new Array();
                            $('#' + name).children('option').each(function() {
                                values['mustread']['role'].push(this.value);
                            });
                        } else {
                            values[name] = new Array();
                            $('#' + name).children('option').each(function() {
                                values[name].push(this.value);
                            });
                        }
                    });
                                    
                }
                
                // special input, related, attachments
                for (var i = 0;i < input_fields.length;i ++) {
                    var name = input_fields[i];
        
                    values[name] = new Array();
                    $('input[name^=' + name + ']').each(function() {        
                        if (this.name != name + '[]') {
                            return;
                        }
        
                        if (this.value) {
                            values[name].push(this.value);    
                        }
                    });
                }
        
                // schedule
                values['schedule_on'] = {};
                values['schedule'] = {1 : {'date': {}}, 2: {'date': {}}};
                
                var reg = /schedule\[(\d)\]\[(date|st|note)\]/;
                var reg2 = /schedule_on\[(\d)\]/;
                
                
                $('#' + FormCollector.form_id + ' :input[name^=schedule],select[name^=schedule]').each(function() {
                    var val = this.name.match(reg);
                    var val2 = this.name.match(reg2);
                    
                    if (val) {
                        values['schedule'][val[1]][val[2]] = this.value;
                                       
                    } else if (val2) {
                        if (this.checked) {
                            values['schedule_on'][val2[1]] = 1;                    
                        }    
                    }
                });
                
                
                // private
                values['private'] = [];
                $('input[name^=private]').each(function() {
                    if (this.checked) {
                        values['private'].push(this.value);    
                    }
                });
                
                // tags
                if (window.TagManager) {
                    values['tag'] = [];
                    for (var i = 0; i < TagManager.tags.length; i++) {
                        values['tag'].push(TagManager.tags[i][0]);
                    }    
                }
                
                if (window.oEditor) {
                    var ck_name = window.oEditor.name;
                    values[ck_name] = oEditor.getData();
                }
                
                // custom
                values['custom'] = FormCollector.getCustomFields();
            }
        }
        
        if (types[type]) {
            types[type](values);
            
        } else {
            types.regular(values);
        }
        
        if (FormCollector.extra_field_callback) {
            FormCollector.extra_field_callback(values);
        }
        
        console.log(values);
        
        return values;
    },
    
    
    getCustomFields: function(obj) {
		
        var custom = new Array();
		var obj2 = (obj) ? obj.$('*[name^=custom]') : $('*[name^=custom]');
		
        // $('*[name^=custom]').each(function() {
        obj2.each(function() {
            var pattern = /\[(\d*)\]/i;
            var matches = this.name.match(pattern);
            
            if (matches) {
                var custom_id = matches[1];

                if ($(this).is('input[type=radio]')) {
                    if ($(this).is(':checked')) {
                        custom[custom_id] = this.value;
                    }
                    
                } else if ($(this).is('select')) {
                    if ($(this).val()) {
                        custom[custom_id] = $(this).val();
                    }
                    
                } else if ($(this).is('input[type=checkbox]')) {
                    if (!(custom[custom_id] instanceof Array)) {
                        custom[custom_id] = new Array();
                    }                
                    if ($(this).is(':checked')) {
                        custom[custom_id].push(this.value);    
                    }
                    
                } else {
                    custom[custom_id] = $(this).val(); 
                }
            }
        });
        
		return custom;
	},
    
    
    setExtraFieldCallback: function(func) {
        FormCollector.extra_field_callback = func;
    }
}