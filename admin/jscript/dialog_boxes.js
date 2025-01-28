window.native_alert = window.alert;

window.alert = function(message, fallback) {
    if (fallback) {
        native_alert(message);
        return;
    }
    
    $(document.createElement('div'))
        .attr({title: 'Alert', 'class': 'alert'})
        .html(message)
        .dialog({
            buttons: {
                OK: function(){
                    $(this).dialog('close');
                }
            },
            close: function() {
                $(this).remove();
            },
            draggable: true,
            modal: true,
            resizable: false,
            width: 300
        });
};