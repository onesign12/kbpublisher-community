var SearchBar = {
    
    suggestions_msg: '',
    
    init: function (url, field_id, attach_pos, attach_el) {
        
        $('#' + field_id).autocomplete({
            source: url,
            search: function (event, ui) {
                //$('#search_menu').hide();
            },
            open: function (event, ui) {
                $(this).autocomplete('widget').attr('id', 'search_suggestions');
                $(this).autocomplete("widget").appendTo("#results").css("position", "static");
            },
            focus: function(event, ui) {
                console.log(ui);
            },
            select: function(event, ui) {
                $('#' + field_id).val(ui.item.value);
                
                var form = $('#' + field_id).closest('form');
                form.submit();
            },
            position: {
                my: 'left top',
                at: attach_pos,
                of: '#' + attach_el
            }
        });
        
        $('#' + field_id).data('ui-autocomplete')._renderItem = SearchBar.renderItemWithHighlight;
        $('#' + field_id).data('ui-autocomplete')._renderMenu = SearchBar.renderMenu;
        
        // $( window ).resize(function() {
        $( window ).on('resize', function() {
            $('#' + field_id).autocomplete('close');
            $('#' + field_id).autocomplete('search');
        });
    },
    
    
    show: function() {
        ModalManager.show('search_bar2', 'top');
    },
    
    
    hide: function() {
        ModalManager.hide('search_bar2');
    },
    
	
    hideBarByClick: function(e) {
        var search_overlay = $('#search_overlay');
        if (search_overlay.is(e.target)) {
            SearchBar.hide();
        }
    },
    
    
    renderItemWithHighlight: function(ul, item) {
    	var pattern = '(' + $.ui.autocomplete.escapeRegex(this.term) + ')';
        var re = new RegExp(pattern, 'gi');
        
        var t = item.label.replace(re, '<span style="background: yellow;">$1</span>');
        return $('<li></li>').data('ui-autocomplete-item', item).append('<a>' + t + '</a>').appendTo(ul);
    },
    
    
    renderMenu: function (ul, items) {
        var self = this;
        $.each(items, function (index, item) {
            self._renderItem(ul, item);
            if (index == 0) {
                var caption = '<h3 class="ui-state-disabled">'
                    + SearchBar.suggestions_msg + ':</h3>';
                ul.prepend(caption);
            }
        });
    }
}


// for advanced search form 
var SearchBar2 = {
    
    
    init: function (url, field_id, attach_pos, attach_el) {
        
        $('#' + field_id).autocomplete({
            source: url,
            search: function (event, ui) {
                //$('#search_menu').hide();
            },
            open: function (event, ui) {
                $(this).autocomplete('widget').attr('id', 'search_suggestions2');
                $(this).autocomplete("widget").appendTo("#results").css("position", "static");
            },
            focus: function(event, ui) {
                // return false;
                console.log(ui);
            },
            select: function(event, ui) {
                $('#' + field_id).val(ui.item.value);
                
                var form = $('#' + field_id).closest('form');
                form.submit();
            },
            position: {
                my: 'left top',
                at: attach_pos,
                of: '#' + attach_el
            }
        });
        
        $('#' + field_id).data('ui-autocomplete')._renderItem = SearchBar.renderItemWithHighlight;
        $('#' + field_id).data('ui-autocomplete')._renderMenu = SearchBar2.renderMenu;
        
        var width = $(window).width(), height = $(window).height();
        // $( window ).resize(function() {
        $( window ).on('resize', function() {
            if($(window).width() != width || $(window).height() != height ) { // to fix resize on mobile on scroll
                $('#' + field_id).autocomplete('close');
                $('#' + field_id).autocomplete('search');
            }
        });
    },
    
    
    renderMenu: function (ul, items) {
        var self = this;
        $.each(items, function (index, item) {
            self._renderItem(ul, item);
        });
    }
}
