function RecordManager(options)  {
    this.name = 'tag';
    this.records = [];
    this.creation_allowed = true;
    this.creation_validator = false;
    this.limit = false;
    this.assigned_block = false;
    this.input_field = false;
    this.container = false;
    this.suggest_block = false;
    this.suggest_link = false;
    this.width = 500;
    this.maxHeight = 200;
    this.on_enter_handler = 'xajax_addTag';
    
    // messages
    this.invalid_value_hint_msg1 = false;
    this.invalid_value_hint_msg2 = false;
    this.press_enter_hint_msg1 = false;
    this.press_enter_hint_msg2 = false;
    
    
    for (var i in options) {
        this[i] = options[i];
    }
    
    var _this = this;
    
    
    // highlight matches or style the hint
    this.renderItemWithHighlight = function(ul, item) {
        if (item.id != 0) {
            /*if (this.term.length > 0) {
                var term = this.term.split(' ').join('|');
                var pattern = '(' + $.ui.autocomplete.escapeRegex(term) + ')';
                var re = new RegExp(pattern, 'gi');
                var t = item.label.replace(re, '<span style="background: yellow;">$1</span>');
            }*/
            
            var t = item.label;
                        
        } else {
            var t = '<div class="badge_enter_hint">' + item.label + '</div>';
        }
        
        return $('<li></li>').data('ui-autocomplete-item', item).append('<a>' + t + '</a>').appendTo(ul);
    }
    
    this.resizeMenu = function () {
        var ul = this.menu.element;
        //ul.outerWidth(this.element.outerWidth()); // input width
        
        ul.css('max-height', _this.maxHeight);
        ul.css('overflow-y', 'auto');
        ul.css('overflow-x', 'hidden');
        
        ul.outerWidth(_this.width);
    }
    
    
    this.showHint = function(e) {
        $('#tag_hint').css('visibility', 'visible');
    }
    
    
    this.hideHint = function(e) {
        $('#tag_hint').css('visibility', 'hidden');
        
        if (_this.container.hasClass('focused')) {
            _this.container.removeClass('focused');
            _this.input_field.hide();
            
            _this.container.find('li.focused').removeClass('focused');
        }
        
        _this.assigned_block.show();
    }
    
    
    this.handlePressedKey = function(e) {
        if (e.which == '13') { // enter button
            if ($(_this.input_field.autocomplete('widget')).find('a.ui-state-focus').length) { // pressed on a list item, skip
                return;
            }
            
            e.preventDefault(); // prevent a form submission
            
            if (_this.creation_allowed) {
                _this.input_field.autocomplete('close');
                window[_this.on_enter_handler](_this.input_field.val(), 'spinner_tag');
            }
            
        } else if (e.which == '8') { // backspace button
            if (_this.input_field.val() == '' && _this.records.length) {
                
                if (_this.container.find('li.focused').length) { // there is a selected item
                    var del_id = _this.container.find('li.focused .delete_badge_item').attr('id');
                    _this.deleteById(del_id);
                }
                
                if (_this.records.length) {
                    var last_item = _this.records[_this.records.length - 1];
                    $('#' + _this.name + '_' + last_item[0]).addClass('focused');
                }
            }
            
        } else if (e.which == '46') { // delete button
            if (_this.container.find('li.focused').length) { // there is a selected item
                var del_id = _this.container.find('li.focused .delete_badge_item').attr('id');
                _this.deleteById(del_id);
            }
            
        } else {
            _this.container.find('li.focused').removeClass('focused');
        }
    }
    
    
    this.handleSelectedMenuItem = function(e, ui) {
        if (ui.item.id == 0) {
            if (_this.creation_allowed) {
                window[_this.on_enter_handler](_this.input_field.val(), 'spinner_tag');
            }
            
        } else {
            _this.create(ui.item.id, ui.item.value, ui.item.tooltip);
            _this.input_field.val('');
        }
        
        return false; 
    }
    
    
    this.prepareMenu = function(e, ui) {
        //$('#spinner').hide();
        _this.container.removeClass('search');
        
        // filter already added
        for (var i = 0; i < ui.content.length; i ++) {
            var id = ui.content[i].id;
            if ($('#' + _this.name + '_' + id).length) {
                ui.content.splice(i, 1);
                i --;
            }
        }
        
        var value = _this.input_field.val();
        
        if (_this.creation_allowed) { // "press enter" hint
        
            if (_this.creation_validator) { // validation first
                var status = _this.creation_validator(value);
                if (status) { // all good
                    var label = (ui.content.length) ? _this.press_enter_hint_msg2 : _this.press_enter_hint_msg1;
                    
                } else { // failed
                    var label = (ui.content.length) ? _this.invalid_value_hint_msg2 : _this.invalid_value_hint_msg1;
                }
                
            } else {
                var label = (ui.content.length) ? _this.press_enter_hint_msg2 : _this.press_enter_hint_msg1;
            }
            
            ui.content.push({id: 0, label: label, value: value});
            
        } else if (ui.content.length == 0) {
            var label = _this.no_matches_msg;
            ui.content.push({id: 0, label: label, value: value});
        }
    }
    
    
    this.blockUpdatingValue = function(e, ui) {
        return false;
        
        // if (ui.item.id == 0) {
            // return false;
        // }
    }
    
    
    this.create = function(id, name, tooltip) {
        console.log(id, name);
        
        // already exists
        var badge_id = _this.name + '_' + id;
        if ($('li[id="' + badge_id + '"]').length) {
            return;
        }
        
        var index = _this.records.length;
        if (_this.limit !== false && (index == _this.limit)) { // reached the limit
            _this.deleteAll();
        }
            
        var record = document.createElement('li');        
        record.id = _this.name + '_' + id;
        record.className = 'badge_item';
            
        var record_text = document.createElement('span');
        record_text.innerHTML = name;
        record.appendChild(record_text);
        
        if (tooltip) {
            record_text.setAttribute('title', tooltip);
            
            $(record_text).tooltipster({
                contentAsHTML: true,
                theme: ['tooltipster-kbp_menu'],
                interactive: true
            });
        }
            
        var del = document.createElement('span');
        del.className = 'delete_badge_item';
        del.id = _this.name + '_delete_' + id;
        del.innerHTML = '×';
        
        del.setAttribute('onclick', _this.name + '_manager.deleteById("' + del.id + '", true)');
        //del.setAttribute('onclick', '_this.deleteRecord(' + index + ')');
        record.appendChild(del);
        
        var hidden = document.createElement('input');
        hidden.setAttribute('type', 'hidden');
        hidden.setAttribute('name', _this.name + '[]');
        hidden.setAttribute('value', id);
        record.appendChild(hidden);
        
        //$(record).insertBefore(_this.input_field.parent());
        _this.assigned_block.append(record);
        
        //_this.assigned_block.append();
        _this.records.push([id, name]);
        
        _this.container.removeClass('empty');
        _this.assigned_block.show();
        
        if (_this.limit == _this.records.length) { // finished
            _this.input_field.blur();
        }
        
        $('body').trigger('kbpRecordAdded', [{name: this.name, id: id, title: name}]);
    }
    
    
    this.createList = function(records) {
        for (var i = 0; i < records.length; i ++) {
            _this.create(records[i].id, records[i].title);    
        }
    }
    
    
    this.deleteById = function(id, user_triggered) {
        
        id = id.substr(_this.name.length + 8);
        // id = id.substr(11);  // eleontev wanted to change above line October 17, 2019
        
        if (user_triggered) {
            confirm2(_this.confirm_delete_msg, function() {
                if (_this.suggest_list_opened) {
                    if ($('#suggest_tag_' + id).length == 1) { // enable suggest tag if exists
                        $('#suggest_tag_' + id).removeClass('suggest_tag_disabled').addClass('suggest_tag_active');
                        
                    } else { // or load the new tags list if the removed tag is new
                        _this.getAllTags();  
                    }    
                }
                
                _this._deleteById(id);
            });
            
        } else {
            _this._deleteById(id);
        }
    }
    
    
    this._deleteById = function(id) {
        $('li[id="' + _this.name + '_' + id + '"]').remove();
        // $('li[id="tag_' + id + '"]').remove();   // eleontev wanted to change above line October 17, 2019
        
        for (var i = 0; i < _this.records.length; i ++) {
            if (_this.records[i][0] == id) {
                _this.records.splice(i, 1);
            }
        }
        
        $('body').trigger('kbpRecordDeleted', [{name: this.name, id: id}]);
        
        if (_this.records.length == 0) {
            $('body').trigger('kbpRecordsDeleted', [{name: this.name}]);
            _this.container.addClass('empty');
        }
    }
    
    
    this.deleteAll = function() {
        for (var i = 0; i < _this.records.length; i ++) {
            _this.deleteById(_this.name + '_delete_' + _this.records[i][0], false);
        }
        
        _this.records = [];
    }
    
    
    this.deleteRecord = function(index) {
        if(confirm(_this.confirm_delete_msg)) {
            $('#cc_' + index).remove();
            
            delete _this.records[index];
        }
    }
    
    
    this.showSpinner = function() {
        //$('#spinner').show();
        _this.container.addClass('search');
    }
    
    this.container.click(function(e) {
        if ($(e.target).hasClass('delete_badge_item')) {
            return;
        }
        
        if (_this.limit == 1 && _this.records.length == 1) {
            _this.assigned_block.hide();
        }
        
        if (!_this.container.hasClass('focused')) {
            _this.container.addClass('focused');
            _this.input_field.show();
            _this.input_field.focus();
        }
    });
    
    //this.input_field.focus(this.showHint);
    this.input_field.blur(this.hideHint);
    this.input_field.keydown(this.handlePressedKey);
    //this.input_field.keypress(this.handlePressedKey);
    
    this.input_field.autocomplete({
        position: {
            my: 'left top',
            at: 'left bottom',
            of: _this.container
        },
        source: this.suggest_link,
        select: this.handleSelectedMenuItem,
        response: this.prepareMenu,
        focus: this.blockUpdatingValue,
        search: this.showSpinner
    });
    
    
    this.input_field.data('ui-autocomplete')._renderItem = this.renderItemWithHighlight; 
    this.input_field.data('ui-autocomplete')._resizeMenu = this.resizeMenu;
}