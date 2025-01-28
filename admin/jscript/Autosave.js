function Autosave(type) {
    this.id_key = '';
    
    this.save = function(type) {
        
        LeaveScreenMsg.check_error_box = false;
        var changes = LeaveScreenMsg.formCheck()
        
        if (!changes) {
           return;
        }
        
        var values = FormCollector.collect(type);
        xajax_autoSave(values, this.id_key);
    }

    
    this.setIdKey = function(id_key) {
        this.id_key = id_key;
    }
    

    this.getIdKey = function() {
        return this.id_key;
    }
	

	this.cancelHandler = function(do_delete) {
		if(do_delete && $('#id_key').val()) {
			xajax_deleteAutoSave(this.id_key); 
		}
	}	
	
}


function showAutosaveBlock(info_text, id_key, msg) {    
    $('#autosave_infoblock').show();
    $('#autosave_infoblock').css('visibility', 'visible');
    $('#autosave_infoblock').attr('title', info_text);
    
    $('#autosave_infoblock').tooltipster({
        contentAsHTML: true,
        theme: 'tooltipster-kbp',
        interactive: true,
    });
    
    $('#id_key').val(id_key);
    
    // revert changes to prevent new autosave
    LeaveScreenMsg.changes = false;
    LeaveScreenMsg.setFckDefaultValue();
    LeaveScreenMsg.force_alert = true;
    
    autosave_counter = 0;
    
    if (window.autosave_counter_interval) {
        clearInterval(autosave_counter_interval);
    }
    
    autosave_counter_interval = setInterval(
        function() {
            autosave_counter ++;
            
            if (autosave_counter < 60) {
                var text = autosave_counter + ' ' + msg.minute + ' ' + msg.ago;
                
            } else {
                var hours = Math.round(autosave_counter / 60);
                var text = hours + ' ' + msg.hour + ' ' + msg.ago;
            }
            
            $('#autosave_infoblock').tooltipster('content', msg.prefix + ': ' + text);
        }, 60000
    );
}