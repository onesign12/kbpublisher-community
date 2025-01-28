var ModalManager = {
    
    show: function(id, position, close_btn) {
        if (!$('#popup2_overlay').length) {
            var overlay = $('<div id="popup2_overlay"></div>');
            overlay.appendTo(document.body);
        }
        
        $('#popup2_overlay').show();
        
        $('#popup2_overlay').click(function(e) {
            if (!$(e.target).is('#popup2_overlay')) {
                return;
            }
            ModalManager.hide(id);
        });

        var element = $('#' + id);
        
        element.addClass('popup2');
        element.addClass(position);
        
        element.show();
        element.appendTo('#popup2_overlay');
        
        if(close_btn != false) {
            if (!element.find('.close_button').length) {
                var close_button = '<button class="close_button" onclick="ModalManager.hide(\'' + id + '\');"><span>×</span></button>';
                element.prepend(close_button);
            }
        }
        
        $(document).bind('keyup.modal', {id: id}, ModalManager.hideByEscapeKey);
        
        var first_field = element.find('input[type=text],textarea,select').filter(':visible:first');
        first_field.focus();
    },
    
    
    hide: function(id) {
        var element = $('#' + id);
        element.hide();
        
        $('#popup2_overlay').hide();
        
        // December 22, 2020 eleontev, to hide error in comment form
        if ($('.tooltipster-kbp_error').length) {
            $('._error_tooltip').each(function(){
                $('body').trigger('kbpErrorResolved', [{field: this.name}]);
            });
        }
    },
    
    
    hideByEscapeKey: function(e) {
        var data = e.data;
        
        if (e.keyCode === $.ui.keyCode.ESCAPE) {
            ModalManager.hide(data.id);
        }
        
        e.stopPropagation();
    },  
    
}