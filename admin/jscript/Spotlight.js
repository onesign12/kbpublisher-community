var Spotlight = {
    currentEntryType: '',
    url: '',
    section_title_msg : '',

    
    init: function() {
        $('#search_field').autocomplete({
            source: Spotlight.url,
            appendTo: '#search_block',
            position: {
                my: 'left top',
                at: 'left bottom',
                of: '#search_block'
            },
            open: function(event, ui) {
                Spotlight.currentEntryType = '';
            },
            select: function(event, ui) {
                event.preventDefault();
                window.location.href = ui.item.value;
            },
            focus: function(event, ui) {
                return false;  
            },
            search: function(event, ui) {
                $('#search_spinner').show();
            },
            response: function(event, ui) {
                $('#search_spinner').hide();
            }
        });
                    
        $('#search_field').data('ui-autocomplete')._renderItem = function(ul, item) {
            if (item.entry_type != Spotlight.currentEntryType) { // new section
                var li = $('<li class="ui-autocomplete-category"></li>');
                var html = '<a href="#" title="' + Spotlight.section_title_msg + '">' + item.entry_type + '</a>';
                       
                var _item = {value: item.section_link};
                li.data('ui-autocomplete-item', _item).append(html).appendTo(ul);
                    
                Spotlight.currentEntryType = item.entry_type;
            }
            
            var li = $('<li style="background: #fafafa;"></li>');
            var html = '<a href="#" style="padding: 3px .4em 3px 13px;"><div style="text-overflow: ellipsis;overflow: hidden;white-space: nowrap;width: 290px;"><img src="' +
                   item.icon + '" style="height: 12px;margin-left: 7px;margin-right: 10px;vertical-align: middle;text-overflow: ellipsis;" />' + item.label + '</div></a>';
            
            return li.data('ui-autocomplete-item', item).append(html).appendTo(ul);
        }
                    
        $('#search_field').data('ui-autocomplete')._resizeMenu = function() {
             this.menu.element.outerWidth(310);
        }
        
        $(document).mouseup(function(e) {
            var search_block = $('#search_block');
            var toggle = $('#search_toggle');
        
            if (!search_block.is(e.target) && search_block.has(e.target).length === 0 && !toggle.is(e.target)) {
                search_block.slideUp();
            }
        });
    }
}