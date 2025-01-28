/* title in place */
function updateTitleInPlace(el) {
    window.updateTitleTimer = setTimeout(function() {
        $('#title_text').css('visibility', 'hidden');
        
        $('#title_input').show();
        $('#title_input').focus();
        
        // cursor at the end
        var data = $('#title_input').val();
        $('#title_input').focus().val('').val(data);
        
        $('body').click(clickHandler);
    }, 1000);
}

function denyTitleUpdate() {
    if (window.updateTitleTimer) {
        clearTimeout(window.updateTitleTimer);
    }
}

function checkForSpecialKey(e, el) {
    var deny = false;
    
    if (e.ctrlKey && e.keyCode == 13) { // ctrl + enter
        var height = $('#title_input').outerHeight() + 20;
        $('#title_input').parent().css('height', height + 'px');
        
        el.value = el.value.substring(0, el.selectionStart) + "\n" + el.value.substring(el.selectionStart);
        
    } else if (e.keyCode == 13) { // enter
        deny = true;
        
        var title = $('#title_input').val();
        title = title.replace(/(\r\n\t|\n|\r\t)/gm,"");
        
        $('#title_text').html(title);
        $('#title_input').val(title);
        
        $('#title_input').parent().css('height', 'auto');
        
        xajax_saveTitle(title);
        
        e.stopPropagation();
        e.preventDefault();
    }
    
    if (e.keyCode === $.ui.keyCode.ESCAPE) {
        deny = true;
        
        var title = $('#title_text').html();
        $('#title_input').val(title);
        
        e.stopPropagation();
        e.preventDefault();
    }
    
    if (deny) {
        $('#title_input').hide();
        $('#title_text').css('visibility', 'visible');
        
        $('body').unbind('click', clickHandler);
    }
}

function clickHandler(e) {
    if(e.target.id == 'title_input') {
        return;
    }
    
    var title = $('#title_input').val();
    title = title.replace(/(\r\n\t|\n|\r\t)/gm,"");
    
    $('#title_input').hide();
    $('#title_text').css('visibility', 'visible');
    
    $('#title_text').html(title);
    $('#title_input').val(title);
    
    $('#title_input').parent().css('height', 'auto');
    
    xajax_saveTitle(title);
    
    $('body').unbind('click', clickHandler);
}