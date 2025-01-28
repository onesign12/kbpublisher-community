$('body').bind('kbpSelectedEntryTransferredToPopup', {}, function(e, params) {
    if (params.id == 0) {
        $('#' + handler.idFirst + ' option[value!="0"]').attr('disabled', true);
    }
});


$('body').bind('kbpEntrySelected', {}, function(e, params) {
    if (params.id == 0) {
        $('#' + params.handler.idSecond).find('option').remove();
        $('#' + params.handler.idFirst + ' option').attr('disabled', true);
    }  
});


$('body').bind('kbpEntryDeleted', {}, function(e, params) {
    if (params.id == 0) {
        $('#' + params.handler.idFirst + ' option').attr('disabled', false);
    }
});


$('body').bind('kbpEntriesFiltered', {}, function(e, params) {
    if (params.id == 0) {
        $('#' + handler.idFirst + ' option[value!="0"]').attr('disabled', true);
    }
});