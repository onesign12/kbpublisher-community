function makeCommentsEditable(ok_msg, cancel_msg, empty_msg) {
    $('div.formatted_comment').editable(function(value, settings) {
        if (value == '') {
            alert(empty_msg);
            var raw_comment = $(this).next().html();
            return raw_comment;

        } else {
            var comment_id = $(this).parent().parent().attr('id').substr(8);
            xajax_updateComment(comment_id, value);
        }
        
        return value;
    }, {
        type: 'textarea',
        rows: 3,
        width: '100%',
        submit: ok_msg,
        cancel: cancel_msg,
        onblur: 'ignore',
        data: function(value, settings) {
            var raw_comment = $(this).next().html();
            return raw_comment;
        }
    });
}

function initBulkHandlers() {
    $('#commentsBlock input[type="checkbox"]').click(function() {
        $('body').trigger('tableSelectionChanged');
        
        if ($(this).prop('checked')) {
            $(this).parent().next().addClass('trHighlight2');
            
        } else {
            $(this).parent().next().removeClass('trHighlight2');
            $('input[name="id_check"]').prop('checked', false);
        }
    });
    
    $('input[name="id_check"]').click(function() {
        var checked = this.checked;
        $('#commentsBlock input[type="checkbox"]').prop('checked', checked);
        $('body').trigger('tableSelectionChanged');
        
        if (checked) {
            $('.commentBlock').addClass('trHighlight2');
            
        } else {
            $('.commentBlock').removeClass('trHighlight2');
        }
    });
}

function deleteAllComments(msg) {
    confirm2(msg, function() {
        xajax_deleteAllComments();
    });
}