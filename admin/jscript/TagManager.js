var TagManager = {
    tags: [],
    creation_allowed: 1,
    suggest_list_limit: 5,
    suggest_list_offset: 0,
    assigned_block: false,
    tag_input: false,
    suggest_block: false,
    suggest_link: false,
    press_enter_hint_msg1: false,
    press_enter_hint_msg2: false,
    no_tags_msg: false,
    no_matches_msg: false,
    suggest_list_opened: false,
    load_tags_link_behaviour: 2, // 1 = inline, 2 = popup
    suggest_list_opened: false,
    
    init: function(options) {
        for (var i in options) {
            TagManager[i] = options[i];
        }
        
        TagManager.tag_input.keypress(TagManager.addTagOnEnter);
        
        TagManager.tag_input.autocomplete({
            source: TagManager.suggest_link,
            select: TagManager.handleSelectedMenuItem,
            response: TagManager.prepareMenu,
            focus: TagManager.blockUpdatingValue
         }).data('ui-autocomplete')._renderItem = TagManager.renderItemWithHighlight;
    },
    
    
    addTagOnEnter: function(e) {
        if (e.which == '13') {
            e.preventDefault(); // prevent form submit
            
            if (TagManager.creation_allowed) {
                TagManager.tag_input.autocomplete('close');
                xajax_addTag(TagManager.tag_input.val(), 'spinner_tag');
            }
        }
    },
    
    
    // highlight matches or style the hint
    renderItemWithHighlight: function(ul, item) {
        if (item.id != 0) {
            if (this.term.length > 0) {
                var term = this.term.split(' ').join('|');
                var pattern = '(' + $.ui.autocomplete.escapeRegex(term) + ')';
                var re = new RegExp(pattern, 'gi');
                var t = item.label.replace(re, '<span style="background: yellow;">$1</span>');
            }
                        
        } else {
            var t = '<div class="tag_enter_hint">' + item.label + '</div>';
        }
        
        return $('<li></li>').data('ui-autocomplete-item', item).append('<a>' + t + '</a>').appendTo(ul);
    },
    
    
    handleSelectedMenuItem: function(e, ui) {
        if (ui.item.id == 0) {
            if (TagManager.creation_allowed) {
                xajax_addTag(TagManager.tag_input.val(), 'spinner_tag');
            }
            
        } else {
            TagManager.create(ui.item.id, ui.item.value);
            TagManager.tag_input.val('');
        }
        
        return false; 
    },
    
    
    prepareMenu: function(e, ui) {
        // filter already added
        for (var i = 0; i < ui.content.length; i ++) {
            var id = ui.content[i].id;
            if ($('#tag_' + id).length) {
                ui.content.splice(i, 1);
                i --;
            }
        }
        
        if (TagManager.creation_allowed) { // "press enter" hint
            var label = (ui.content.length) ? TagManager.press_enter_hint_msg2 : TagManager.press_enter_hint_msg1;
            ui.content.push({id: 0, label: label, value: TagManager.tag_input.val()});
            
        } else if (ui.content.length == 0) {
            var label = TagManager.no_matches_msg;
            ui.content.push({id: 0, label: label, value: TagManager.tag_input.val()});
        }
    },
    
    
    blockUpdatingValue: function(e, ui) {
        if (ui.item.id == 0) {
            return false;
        }
    },
    
    
    create: function(id, title) {
        // already exists
        if ($('#tag_' + id).length) {
            return;
        }
        
        // disable the suggest tag
        if ($('#suggest_tag_' + id).length) {
            $('#suggest_tag_' + id).removeClass('suggest_tag_active').addClass('suggest_tag_disabled');
        }
        
        if ($('span.suggest_tag:visible').length == 0) { // there are no available tags
            //TagManager.suggest_block.html(TagManager.no_tags_msg);    
        }
            
        var tag = document.createElement('span');
        tag.id = 'tag_' + id;
        tag.className = 'tag_container';
            
        var tag_text = document.createElement('span');
        tag_text.className = 'tag';
        tag_text.innerHTML = title;
        tag.appendChild(tag_text);
            
        var del = document.createElement('span');
        del.className = 'delete_tag';
        del.id = 'tag_delete_' + id;
        del.innerHTML = '×';
        del.setAttribute('onclick', 'TagManager.deleteById("' + del.id + '", true)');
        tag.appendChild(del);
            
        $('#delete_tag_button').show();
            
        var hidden = document.createElement('input');
        hidden.setAttribute('type', 'hidden');
        hidden.setAttribute('name', 'tag[]');
        hidden.setAttribute('value', id);
        tag.appendChild(hidden);
            
        TagManager.assigned_block.append($(tag));
        TagManager.tags.push([id, title]);
        
        $('body').trigger('kbpTagAdded', [{id: id}]);
    },
    
    
    createList: function(tags) {
        for (var i = 0; i < tags.length; i ++) {
            TagManager.create(tags[i].id, tags[i].title);    
        }
    },
    
    
    loadSuggestList: function(limit, offset) {
        TagManager.suggest_list_opened = true;
        xajax_getTags(limit, offset);
    },
    
    
    updateSuggestList: function(tags) {
        var available = false;
        
        //TagManager.suggest_block.empty();
        TagManager.suggest_block.show();
        
        for (var i = 0; i < tags.length; i ++) {
            var id = tags[i][0];
            var title = tags[i][1];
            
            var tag = document.createElement('span');
            tag.innerHTML = title;
            tag.id = 'suggest_tag_' + id;
            
            title = title.replace(/'|\\'/g, "\\'");
            tag.onclick = new Function('TagManager.create(' + id + ", '" + title + "')");
            
            if ($('#tag_' + id).length) { // already added
                tag.className = 'suggest_tag_disabled';
                
            } else { // or not
                tag.style.display = 'inline';
                tag.className = 'suggest_tag_active';
                available = true;
            }
            
            TagManager.suggest_block.append($(tag));    
        }
        
        // there are no available tags
        if (!available) {
            TagManager.suggest_block.html(TagManager.no_tags_msg);
        }
    },
    
    
    deleteById: function(id, user_triggered) {
        
        id = id.substr(11);
        
        if (user_triggered) {
            confirm2(TagManager.confirm_delete_msg, function() {
                if (TagManager.suggest_list_opened) {
                    if ($('#suggest_tag_' + id).length == 1) { // enable suggest tag if exists
                        $('#suggest_tag_' + id).removeClass('suggest_tag_disabled').addClass('suggest_tag_active');
                        
                    } else { // or load the new tags list if the removed tag is new
                        TagManager.getAllTags();  
                    }    
                }
                
                TagManager._deleteById(id);
            });
            
        } else {
            TagManager._deleteById(id);
        }
    },
    
    
    _deleteById: function(id) {
        $('#tag_' + id).remove();
        
        // hide the delete all button
        if (TagManager.assigned_block.children().length == 0) {
            $('#delete_tag_button').hide();
        }
        
        for (var i = 0; i < TagManager.tags.length; i ++) {
            if (TagManager.tags[i][0] == id) {
                TagManager.tags.splice(i, 1);
            }
        }
        
        $('body').trigger('kbpTagDeleted', [{id: id}]);
        
        if (TagManager.tags.length == 0) {
            $('body').trigger('kbpTagsDeleted');
        }
    },
    
    
    deleteAll: function() {
        
        if(!confirm(TagManager.confirm_delete_msg)) {
            return;
        }
        
        for (var i = 0; i < TagManager.tags.length; i ++) {
            TagManager.deleteById('tag_delete_' + TagManager.tags[i][0], false);
        }
        
        TagManager.assigned_block.empty();
        $('#delete_tag_button').hide();
        TagManager.tags = [];
        
        if (TagManager.suggest_list_opened) {
            TagManager.suggest_block.find('span.suggest_tag_active,span.suggest_tag_disabled').remove();
            TagManager.loadSuggestList(TagManager.suggest_list_offset, 0);
        }
    },
    
    
    getPopularTags: function(link) {
        
        //$('#tag_get_popular_button').hide();
        
        if (TagManager.load_tags_link_behaviour == 2) {
            PopupManager.create(link, 'r', 'r');
                
        } else {
            TagManager.getMoreTags();
        }    
    },
    
    
    getMoreTags: function() {
        TagManager.loadSuggestList(TagManager.suggest_list_limit, TagManager.suggest_list_offset);
        TagManager.suggest_list_offset += TagManager.suggest_list_limit;
    },
    
    
    getAllTags: function() {
        TagManager.suggest_block.find('span.suggest_tag_active,span.suggest_tag_disabled').remove();
        TagManager.loadSuggestList(0, 0);
        TagManager.suggest_list_offset = 0;
    },
    
    
    showAllButtons: function() {
        $('#tag_get_buttons').show();    
    },
    
    hideAllButtons: function() {
        $('#tag_get_buttons').hide();
    }
}