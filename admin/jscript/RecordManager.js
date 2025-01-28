var RecordManager = {
    records: [],
    assigned_block: false,
    input_field: false,
    suggest_block: false,
    suggest_link: false,
    press_enter_hint_msg1: false,
    press_enter_hint_msg2: false,
    
    
    init: function(options) {
        for (var i in options) {
            RecordManager[i] = options[i];
        }
        
        RecordManager.input_field.keypress(RecordManager.addTagOnEnter);
                
        RecordManager.input_field.autocomplete({
            source: RecordManager.suggest_link,
            select: RecordManager.handleSelectedMenuItem,
            response: RecordManager.prepareMenu,
            focus: RecordManager.blockUpdatingValue,
            search: RecordManager.showSpinner
         }).data('ui-autocomplete')._renderItem = RecordManager.renderItemWithHighlight;
    },
    
    
    addTagOnEnter: function(e) {
        if (e.which == '13') {
            e.preventDefault(); // prevent a form submission
            
            RecordManager.input_field.autocomplete('close');
            xajax_checkCcUser(RecordManager.input_field.val());
        }
    },
    
    
    // highlight matches or style the hint
    renderItemWithHighlight: function(ul, item) {
        if (item.id != 0) {
            /*if (this.term.length > 0) {
                var term = this.term.split(' ').join('|');
                var pattern = '(' + $.ui.autocomplete.escapeRegex(term) + ')';
                var re = new RegExp(pattern, 'gi');
                var t = item.label.replace(re, '<span style="background: yellow;">$1</span>');
            }*/
            
            var t = item.label;
                        
        } else {
            var t = '<div class="tag_enter_hint">' + item.label + '</div>';
        }
        
        return $('<li></li>').data('ui-autocomplete-item', item).append('<a>' + t + '</a>').appendTo(ul);
    },
    
    
    handleSelectedMenuItem: function(e, ui) {
        
        if (ui.item.id == 0) {
            xajax_checkCcUser(RecordManager.input_field.val());
            
        } else {
            RecordManager.create(ui.item.id, ui.item.name, ui.item.email);
            RecordManager.input_field.val('');
        }
        
        return false; 
    },
    
    
    prepareMenu: function(e, ui) {
        $('#cc_spinner').hide();
        
        // filter already added
        for (var i = 0; i < ui.content.length; i ++) {
            var id = ui.content[i].id;
            if ($('#tag_' + id).length) {
                ui.content.splice(i, 1);
                i --;
            }
        }
        
        // "press enter" hint
        var label = (ui.content.length) ? RecordManager.press_enter_hint_msg2 : RecordManager.press_enter_hint_msg1;
        ui.content.push({id: 0, label: label, value: RecordManager.input_field.val()});
    },
    
    
    blockUpdatingValue: function(e, ui) {
        if (ui.item.id == 0) {
            return false;
        }
    },
    
    
    create: function(id, name, email) {
        // already exists
        if ($.inArray(email, RecordManager.records) != -1) {
            return;
        }
        
        var index = RecordManager.records.length;
            
        var record = document.createElement('li');        
        record.id = 'cc_' + index;
        record.className = 'cc';
            
        var record_text = document.createElement('span');
        record_text.innerHTML = name + ' &lt;' + email + '&gt;';
        record.appendChild(record_text);
            
        var del = document.createElement('span');
        del.className = 'delete_cc';
        del.innerHTML = 'x';
        
        del.setAttribute('onclick', 'RecordManager.deleteRecord(' + index + ')');
        record.appendChild(del);
            
        $('#cc_none').hide();
        
        var hidden = document.createElement('input');
        hidden.setAttribute('type', 'hidden');
        hidden.setAttribute('name', 'cc[]');
        hidden.setAttribute('value', id);
        record.appendChild(hidden);
        
        $(record).insertBefore(RecordManager.input_field.parent());
        
        //RecordManager.assigned_block.append();
        RecordManager.records.push(email);
    },
    
    
    deleteRecord: function(index) {
        if(confirm(RecordManager.confirm_delete_msg)) {
            $('#cc_' + index).remove();
            
            if (RecordManager.assigned_block.children().length == 0) {
                $('#cc_none').show();
            }
            
            delete RecordManager.records[index];
        }
    },
    
    
    showSpinner: function() {
        $('#cc_spinner').show();
    }
}