var DialogBox = {
    ok_msg: 'Ok',
    cancel_msg: 'Cancel',
    frame: false,
    
    
    alert: function(message, show_native) {
        if(show_native) {
            DialogBox.native_alert.call(window, message);
            return;
        }
        
        if (DialogBox.isInsideFrame()) { // need to go higher
            window.top.DialogBox.alert(message);
            return;
        }
    
        DialogBox.createDialogBox();
    
        $('#dialog_box').ezmodal('show');
        
        message = message.replace(/(?:\r\n|\r|\n)/g, '<br />');
        $('#dialog_box div.ezmodal-content').html(message);
        
        $('#dialog_box button').unbind('click');
        $('#cancel_button').hide();
    },
    
    
    confirm: function(message, ok_callback, options) {
        if (DialogBox.isInsideFrame()) { // need to go higher
            window.top.DialogBox.frame = window;
            window.top.DialogBox.confirm(message, ok_callback, options);
            return false;
        }
        
        DialogBox.createDialogBox();
    
        $('#dialog_box').ezmodal('show');
        
        message = message.replace(/(?:\r\n|\r|\n)/g, '<br />');
        $('#dialog_box div.ezmodal-content').html(message);
        
        $('#dialog_box button').unbind('click');
        $('#dialog_box button').show();
        
        var context = (DialogBox.frame) ? DialogBox.frame: window;
        
        $('#ok_button').click(function () {
            if (typeof ok_callback === 'function') {
                ok_callback();
                
            } else if (ok_callback) {
                context.location.href = ok_callback;
                
            }
        });
        
        // options
        if (options && options['ok_title']) {
            $('#ok_button').html(options['ok_title']);
            
        } else {
            $('#ok_button').html(DialogBox.ok_msg);
        }
        
        
        if (options && options['cancel_callback']) {
            $('#cancel_button').click(function () {
                if (typeof options['cancel_callback'] === 'function') {
                    options['cancel_callback']();                
                }
            });
        }
        
        return false;
    },
    
    
    confirmForm: function(message, button_id, options) {
        var isFrame = DialogBox.isInsideFrame();

        return DialogBox.confirm(message, function(isFrame) {
            var context = (isFrame) ? window.parent.frames[0].document : document;

            $('#' + button_id, context).show();
            $('#' + button_id, context).attr('onclick', '');
            $('#' + button_id, context).click();
        }, options);
    },
    
    
    createDialogBox: function() {
        if (!$('#dialog_box').length) {
            var html = '<div id="dialog_box" class="ezmodal" ezmodal-escclose="true" ezmodal-closable="false">' +
                '<div class="ezmodal-container">' +
                '<div class="ezmodal-content"></div>' +
                '<div class="ezmodal-footer">' +
                '<button type="button" id="ok_button" class="button primary" style="min-width:80px;" data-dismiss="ezmodal">' + DialogBox.ok_msg + '</button>' +
                '<button type="button" id="cancel_button" class="button" style="min-width:80px;" data-dismiss="ezmodal">' + DialogBox.cancel_msg + '</button>' +       			
                '</div></div></div>';
                
            $(html).appendTo('body');
            $('#ok_button').focus();
            
            $('#dialog_box button').focus(function () {
                $('#dialog_box button').removeClass('active');
                $(this).addClass('active');
            });
        }
    },
    
    
    isInsideFrame: function() {
        if (window.frameElement) {
            return true;
            //return $(window.frameElement).hasClass('client_frame');
        }
    }
}

DialogBox.native_alert = window.alert;


window.alert = function(message, show_native) {
    return DialogBox.alert(message, show_native);
}


function confirm2(message, ok_callback, options) {
    return DialogBox.confirm(message, ok_callback, options);
}


function confirmForm(message, element, options) {
    return DialogBox.confirmForm(message, element, options);
}