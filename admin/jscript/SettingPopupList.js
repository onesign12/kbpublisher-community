
var SettingPopupList = {
    
    required_msg: '',
    sure_common_msg: '',
    setting_name: '',
    

    showAddedRule: function (html, line_num) {
        $('#rules_list tbody').append(html);
        $('input[type="text"]').val('');
    
        this.makeEditable('#rule_' + line_num);
        this.hideAddBlock();
    },

    hideDeletedRule: function (line) {
        $('#rule_' + line).remove();
    
        var i = 0;
        $('#rules_list tr:not(.not_sortable)').each(function() {
            $(this).attr('id', 'rule_' + i);
            i ++;
        })
    },

    // addRule: function () {
    //     var data = {};
    //     data['title'] = $('#title').val();
    //     data['link'] = $('#link').val();
    // 
    //     $("#growls").empty();
    // 
    //     if (data['title'] && data['link']) {
    //         xajax_addRule(data);
    //         this.hideAddBlock();
    // 
    //     } else {
    //         $.growl.error({title: "", message: SettingPopupList.required_msg, fixed: true});
    //     }
    // },

    addRule: function (arr) {
        var fields = (arr) ? arr : ['title', 'link']; 
        var data = {};
        for (var i = 0; i < fields.length; i++) {
            data[fields[i]] = $('#' + fields[i]).val();
        }
        
        var res = Object.values(data).every(function(e) {
            return e != '';
        });
        
        if (res) {
            xajax_addRule(data);
            this.hideAddBlock();
        
        } else {
            emptyGrowls();
            $.growl.error({title: "", message: SettingPopupList.required_msg, fixed: true});
        }
    },

    populateRule: function (title, link) {
        $('#title').val(title);
        $('#link').val(link);
    },

    hideAddBlock: function () {
        $('#new_rule').hide();
        emptyGrowls();
    },

    deleteRule: function (el) {
        var line = $(el).parent().parent().attr('id').substring(5);
        confirm2(SettingPopupList.sure_common_msg, function() {
            xajax_deleteRule(line);
        });
    },

    makeEditable: function (selector) {
        $(selector + ' td:not(.not_editable)').editable(function(value, settings) {
            var line_num = $(this).parent().attr('id').substr(5);
            var field = $(this).attr('class').substr(9);
        
            if (value || field == 2) {
                ajax_value = value;
                if (value == '' && field == 2) {
                    ajax_value = 0;
                }
            
                xajax_updateItem(line_num, field, ajax_value);
                return value;
            
            } else {
                emptyGrowls();
                $.growl.error({title: "", message: SettingPopupList.required_msg, fixed: true});
                return false;
            }
        
        }, {
            onblur : 'submit',
            placeholder: '',
            width: '95%',
            height: 16
        });
    },

    updateCounter: function () {
        window.top.$('#aContentForm').attr('target', '_self');
        window.top.$('#aContentForm input[name=popup]').remove();
        window.top.$('#aContentForm').attr('action', window.top.$('#aContentForm').attr('action').replace('&popup=SettingPopupList.setting_name', ''));
    
        // update the counter
        if ($('#rules_list').length) {
            var rules_num = $('#rules_list tr').length - 1;
            window.top.$(SettingPopupList.setting_name + '_count').text(rules_num);
        }
    }
    
}


function initSort() {
    $('#sortable_views').sortable({
        placeholder: 'view_placeholder',
        items: 'li:not(.not_sortable)',
        stop: function(event, ui) {   
            LeaveScreenMsg.changes = true;
        }
    });
    $('#sortable_views').disableSelection();

    LeaveScreenMsg.setDoCheck(1);
    LeaveScreenMsg.check();
}
