var TableListHandler = {
    tableClass: 'listTable',
    titleClass: 'tdTitle',
    overClass: 'trOver',
    highlightClass: 'trHighlight',
    highlight2Class: 'trHighlight2',
    lighterClass: 'trLighter',
    darkerClass: 'trDarker',
    skipClass: 'action',
    trPrefix: 'row_',
    checkboxPrefix: 'ch_',
    actionsTriggerPrefix: 'trigger_actions',
    bulkFormId: 'bulk_form',
    bulkRowIdName: 'id',
    
    start_class: 'lighterClass',
    grouped: 1,
    
    
    init: function() {
           
        var tblList = $('table.' + TableListHandler.tableClass + ' > tbody');
        if(tblList.length == 0) {
            return;
        }
        
        // customization list menu
        if ($('#customize_columns').length) {
            var selector = '> tr.' + TableListHandler.titleClass;
            tblList.find(selector).bind('contextmenu', function(e) {
                TableListHandler.showCustomizationMenu(e);
            });
            
            // to not hide dropdown menu in safari on mouseup
            tblList.find(selector).bind('click', function(e) {
                e.stopPropagation();
            });
        }
        
        var selector = '> tr:not(.' + TableListHandler.titleClass + ')';
        
        var classname = TableListHandler.start_class;
        var counter = 0;
        
        tblList.find(selector).each(function(e) { // walking thru an array of table rows
        
            if (counter == TableListHandler.grouped) {
                counter = 0;
                classname = (classname == 'lighterClass') ? 'darkerClass' : 'lighterClass';
            }
            
            
            // click on a table row
            var selector = 'td:not(.' + TableListHandler.skipClass + ')';
            $(this).find(selector).click(function(e) {
            
                if (e.ctrlKey) { // macosx fix (ctrl + click)
                    e.stopPropagation();
                    return;
                }
                
                var toggle_checkbox = false;
                var forced_class = false;
                
                if (e.altKey) {
                    toggle_checkbox = true;
                    
                    var checkbox = $(this).parent().find('input[type=checkbox]');
                    forced_class = (checkbox.prop('checked')) ? false : TableListHandler.highlight2Class;
                        
                    e.stopPropagation();
                    
                } else {
                    var highlighted_by_checkbox = $(this).parent().hasClass(TableListHandler.highlight2Class);
                    if (highlighted_by_checkbox) {
                        return;
                    }
                }
                
                TableListHandler.select($(this).parent(), toggle_checkbox, forced_class);
            });
            
            /*$(this).find(selector).bind('contextmenu', function(e) {
                TableListHandler.showContextMenu(e, $(this).parent());
            });*/
            
            // click on a bulk checkbox
            selector = '> td > input[type=checkbox]';
            $(this).find(selector).click({cl: classname}, function(e, triggered_data) {
                var forced_class = ($(this).prop('checked')) ? TableListHandler.highlight2Class : '';
                
                if (triggered_data) {
                    forced_class = (triggered_data.checked) ? TableListHandler.highlight2Class : TableListHandler[e.data.cl];
                }
                
                TableListHandler.select($(this).parent().parent(), false, forced_class);
                
                // Uncheck Check All
                if($(this).prop('checked') == false){
                    $('input[name=id_check]').prop('checked', false);
                }
                
                e.stopPropagation();
            });
            
            $(this).find(selector).mouseover(function(e) {
                TableListHandler.highlight($(this).parent().parent(), TableListHandler.highlight2Class);
                e.stopPropagation();
            });
            
            $(this).find(selector).mouseout(function(e) {
                if($(this).prop('checked') == false) {
                    TableListHandler.highlight($(this).parent().parent(), TableListHandler.overClass, true);
                }
                e.stopPropagation();
            });
            
            // mouse out of a table row
            $(this).mouseout({cl: classname}, function(e) {
                TableListHandler.highlight($(this), TableListHandler[e.data.cl]);
            });
            
            
            counter ++;
        });
        
        // mouse over a table row
        selector = '> tr:not(.' + TableListHandler.titleClass + ')';
        tblList.find(selector).mouseover(function() {
            TableListHandler.highlight($(this), TableListHandler.overClass);
        });
        
        // click on a select all checkbox
        selector = 'input[name=id_check]';
        tblList.find(selector).click(function() {
            TableListHandler.toggleAll(this.checked);
        });
        
        //empty collumns if no data 
        var classes = ['tdPrivate', 'tdMustread', 'tdSchedule'];
        $.each(classes, function( index, value ) {
          // let result = true;
          selector = '#listTable td.' + value;
          
          // console.log(value + ' - ' + $(selector + ':not(:empty)').length);
          if(!$(selector + ':not(:empty)').length) {
            $(selector).remove()  
          }
          
          // $(selector).each(function() {
          //   console.log(selector);
          //   if (!$(this).is(':empty')) {
          //     result = false;
          //     return false;
          //   }
          // });
          // 
          // if(result) {
          //   $(selector).remove();
          // }
          
        });
    },
    
    
    getListTableIds: function(el) {
        var tblListTrId = el.attr('id');
        if (!tblListTrId) {
            return false;
        }
        var tblListId = tblListTrId.substr(TableListHandler.trPrefix.length);
        
        var tblListTrIds = [];
        tblListTrIds.push(TableListHandler.trPrefix + tblListId);
        
        if (TableListHandler.grouped > 1) {
            var prefix = tblListTrId.substring(0, tblListTrId.lastIndexOf('_'));
        
            $('tr[id^=' + prefix + ']').each(function() {
                tblListTrIds.push(this.id);
            });
            
            var tblListCbxId = TableListHandler.checkboxPrefix + tblListId.substring(0, tblListId.indexOf('_'));
            
        } else {
            var tblListCbxId = TableListHandler.checkboxPrefix + tblListId;
        }
        
        var tblListActionsId = TableListHandler.actionsTriggerPrefix + tblListId;
    
        return {tr: tblListTrIds, cbx: tblListCbxId, actions: tblListActionsId};
    },
    
    
    select: function(el, toggle_checkbox, forced_class) {
        
        var elements = [];

        if (el.attr('id') != undefined) { // tr has an id
            var tblListIds = TableListHandler.getListTableIds(el);
            
            if (toggle_checkbox) {
                var ch = $('#' + tblListIds.cbx);
                TableListHandler.toggleCheckbox(ch);
            }
            
            for (var i in tblListIds.tr) {
                elements.push($('#' + tblListIds.tr[i]));
            }
            
        } else {
            elements.push($(el));
        }
        
        var new_class;
        var current_class = $(el).attr('class');
        
        for (var i in elements) {
            
            if (forced_class) {
                new_class = forced_class;
                
            } else if(current_class == TableListHandler.overClass) {
                new_class = TableListHandler.highlightClass;
                    
            } else {
                new_class = TableListHandler.overClass;
            }
            
            elements[i].attr('class', new_class);
        }
        
        $('body').trigger('tableSelectionChanged');
    },
    
    
    toggleCheckbox: function(ch) {
        if(ch.length == 0) { // there is no checkbox
            return;
        }
        
        if(ch.attr('disabled')) { // this checkbox is disabled
            return;
        }
                
		if(ch.prop('checked') == true) {
            ch.prop('checked', false);
            
        } else {
            ch.prop('checked', true);
        }
    },
    
    
    highlight: function(el, cl, skip_check) {
        var elements = [];
        
        if (el.attr('id') != undefined) { // tr has an id
            var tblListIds = TableListHandler.getListTableIds(el);
            
            for (var i in tblListIds.tr) {
                elements.push($('#' + tblListIds.tr[i]));
            }
            
        } else {
            elements.push($(el));
        }
        
        if (!skip_check) {
            if ($(el).attr('class') == TableListHandler.highlightClass ||
                $(el).attr('class') == TableListHandler.highlight2Class) {
                return;
            }
        }
        
        for (var i in elements) {
            elements[i].attr('class', cl);
        }
    },
    
    
    toggleAll: function(checked) {
        $('#' + TableListHandler.bulkFormId + ' input[name="' + TableListHandler.bulkRowIdName + '[]"]').each(function() {
            
            if($(this).attr('disabled')) {
                return;
            }
            
            $(this).prop('checked', checked);
            $(this).triggerHandler('click', {
                checked: checked
            });
            
        });
        
        $('#' + TableListHandler.bulkFormId + ' input[name=id_check]').prop('checked', checked);
    },
    
    
    showContextMenu: function(e, el) {
        var tblListIds = TableListHandler.getListTableIds(el);
        if (!tblListIds) {
            return false;
        }
        
        var actions_id = tblListIds.actions;
        if ($('#' + actions_id).length == 0) {
            return;
        }
        
        e.preventDefault();
        e.stopPropagation();
        
        // unchecking
        TableListHandler.toggleAll(false);
        $('input[name=id_check]').prop('checked', false);
        TableListHandler.select(el, false);
        
        var mouseLeft = e.pageX - $('#sidebar').width();
        var mouseTop = e.pageY;
        
        if (TableListHandler.grouped > 1) {
            actions_id = actions_id.slice(0, -2);
        }
        $('#' + actions_id).jqDropdown('show');
                                
        var dropdown = $('.jq-dropdown:visible').eq(0);
        
        dropdown.css({
            left: mouseLeft - dropdown.width(),
            top: mouseTop
        });
    },
    
    
    showCustomizationMenu: function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var mouseLeft = e.pageX + $('#sidebar').width();
        var mouseTop = e.pageY - $('#header').height();
        
        $('#customize_columns').show();
        
        var dropdown = $('.jq-dropdown:visible').eq(0);
        
        dropdown.css({
            left: mouseLeft - dropdown.width(),
            top: mouseTop
        });
    }
}