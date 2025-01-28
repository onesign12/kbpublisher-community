$(document).ready(function(){
    $('#submit_button').button();
    
    $('#submit_button').click(function() {
        $('#aContentForm').submit();
    });
    
    $('#select_button').button({
        text: false,
        icons: {
            primary: 'ui-icon-triangle-1-s'
        }
    });
    
    $('#select_button').click(function() {
        var menu = $(this).parent().next().show().position({
            my: 'right top',
            at: 'right bottom',
            of: this
        });
        
        $(document).one('click', function() {
            menu.hide();
        });
        
        return false;
    });
        
    $('#submit_buttonset').buttonset();
    $('#select_button').parent().next().hide();
        
    $('#select_button').parent().next().menu({
        select: function(event, ui) {
            /*$('#submit_button').button({
                label: ui.item.text()
            });*/
            $('#active').val(ui.item.val());
            $('#aContentForm').submit();
        }
    });
});