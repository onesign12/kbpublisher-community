var LeaveScreenMsg = {
    
    changes: true,
    do_check: true,
    add_check: null,
    form_id: '',
	filter_id: null,
    msg: '',
    fck_default: '',
    editor: null,
    fields_to_check: [],
    force_alert: false,
    check_error_box: true,

    
    check: function() {
        window.onbeforeunload = LeaveScreenMsg.leave;
        return LeaveScreenMsgCheck();
    },
    

    initCheck: function(id) {
        LeaveScreenMsg.changes = false;
        LeaveScreenMsg.form_id = id;
        
        var id_str = '#' + id + ' ';
		var id_fil = (LeaveScreenMsg.filter_id) ? ':not(' + LeaveScreenMsg.filter_id + ')' : '';

        $(id_str + 'select' + id_fil).bind('change', function() {
            LeaveScreenMsg.changes = true;
        });
		
		$(id_str + 'textarea' + id_fil).bind('change', function() {
            LeaveScreenMsg.changes = true;
        });
		
		$(id_str + 'input' + id_fil).bind('change', function() {
            LeaveScreenMsg.changes = true;
        });
        
    },
    
    
    formCheck: function() {
        
        if (typeof LeaveScreenMsg.add_check == 'function') {
            LeaveScreenMsg.add_check();
        }
        
        if (LeaveScreenMsg.editor) {
            var fck_current = (window.is_empty) ? null : LeaveScreenMsg.getFckValue();
            if (LeaveScreenMsg.fck_default != fck_current) {console.log(LeaveScreenMsg.fck_default, fck_current);
                LeaveScreenMsg.changes = true;
            }
        }
        
		// form submission and error
		if(LeaveScreenMsg.changes == false && LeaveScreenMsg.check_error_box) {
			if(document.getElementById('errorBoxMessageDiv')) {
				LeaveScreenMsg.changes = true;
			}
		}
        
        // check fields
        for (var f in LeaveScreenMsg.fields_to_check) {

           $('#' + LeaveScreenMsg.form_id + ' input[name="' + f + '[]"]').each(function() {
               if (this.value) {
                   var in_arr = $.inArray(this.value, LeaveScreenMsg.fields_to_check[f]);
                   if (in_arr == -1) { // new added
                       LeaveScreenMsg.changes = true;
                       LeaveScreenMsg.fields_to_check[f].push(this.value);   
                   }
               }

           })
        }

        if (LeaveScreenMsg.do_check && LeaveScreenMsg.changes) {
            return true;
        } else {
            return false;
        }
    },
    
    
    leave: function() {
        var ch = LeaveScreenMsg.formCheck();
        if ((ch || LeaveScreenMsg.force_alert) && LeaveScreenMsg.do_check) {
            return LeaveScreenMsg.msg;
        }
    },
    
    
    setFckDefaultValue: function() {
        LeaveScreenMsg.fck_default = (window.is_empty) ? null : LeaveScreenMsg.getFckValue();
    },
    
    
    getFckValue: function(){
        return LeaveScreenMsg.editor.getData();       
    },
    
    
    setMsg: function(msg) {
        LeaveScreenMsg.msg = msg;        
    },    
    

    setEditor: function(editor) {
        LeaveScreenMsg.editor = editor;        
    },


    setDoCheck: function(do_check) {
        LeaveScreenMsg.do_check = do_check;      
    },


    skipCheck: function() {
        LeaveScreenMsg.do_check = false;      
    },
	
	
	setFilterFields: function(ids) {
		if (!(ids instanceof Array)) {
			return;
		}
        
		for(var i=0; i< ids.length; i++) {
			ids[i] = '#' + ids[i];
		}
		
        LeaveScreenMsg.filter_id = ids.join(', '); 
    },
    
    
    setFieldsToCheck: function(name) {
        for (var i = 0; i < name.length; i++) {
            LeaveScreenMsg.fields_to_check[name[i]] = [];
            
            var d = $('input[name="' + name[i] + '[]"]');
            
            d.each(function(index) {
                if (this.value) {
                    LeaveScreenMsg.fields_to_check[name[i]].push(this.value);    
                }
            })
            
        }                   
    }
    
};


function LeaveScreenMsgCheck() {
    LeaveScreenMsg.changes = false;
	if(document.getElementById(LeaveScreenMsg.form_id)) {
		LeaveScreenMsg.initCheck(LeaveScreenMsg.form_id);
	}
}