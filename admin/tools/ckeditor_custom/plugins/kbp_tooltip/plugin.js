CKEDITOR.plugins.add('kbp_tooltip', {
	icons: 'KBPTooltip,KBPRemoveTooltip',
    lang: 'en', 
    
	init: function(editor) {
        
        // commands
        editor.addCommand('kbp_tooltip', new CKEDITOR.dialogCommand('KBPTooltipDialog', {
	        startDisabled: true,
            canUndo: true
        }));
        
        editor.addCommand('kbp_remove_tooltip', {
            exec: function(editor) {
                var style = new CKEDITOR.style({
                    element: 'span',
                    attributes: {'class': '_tooltip_user'},
                    type: CKEDITOR.STYLE_INLINE,
                    alwaysRemoveElement: 1
                });
			    editor.removeStyle(style);
            },
            refresh: function( editor, path ) {
    			var element = path.lastElement && path.lastElement.getAscendant('span', true);
    
    			if (element && element.getName() == 'span' && element.hasClass('_tooltip_user')) {
                    this.setState(CKEDITOR.TRISTATE_OFF);
                    
                } else {
                    this.setState(CKEDITOR.TRISTATE_DISABLED);
                }
		    },
            contextSensitive: 1,
            startDisabled: true
        });
        
        // buttons
        editor.ui.addButton('KBPTooltip', {
            label: editor.lang.kbp_tooltip.insertTooltip,
            command: 'kbp_tooltip',
            toolbar: 'kbp'
        });
        
        editor.ui.addButton('KBPRemoveTooltip', {
            label: editor.lang.kbp_tooltip.removeTooltip,
            command: 'kbp_remove_tooltip',
            toolbar: 'kbp'
        });
        
        // states
        var _this = this;
        
        editor.on('contentDom', function(evt) {
            var editable = editor.editable();
            
            editable.attachListener(CKEDITOR.env.ie ? editable : editor.document.getDocumentElement(), 'mouseup', function() {
				mouseupTimeout = setTimeout(function() {
					_this.setToolbarStates(editor);
				}, 0);
			});
            
            editable.on('keyup', function(evt) {
                _this.setToolbarStates(editor);
            });
        });
        
        editor.on('selectionChange', function(evt) {
            _this.setToolbarStates(editor);
        });
        
        editor.on('afterCommandExec', function(evt) {
            if (evt.data.name == 'enter') {
                var el = evt.editor.getSelection().getStartElement();
                if (el.getAttribute('class') == '_tooltip_user') {
                    el.setAttribute('class', '');
                }
            }
        });
        
        // dialogs
        CKEDITOR.dialog.add('KBPTooltipDialog', this.path + 'dialogs/kbp_tooltip.js');
        editor.on('doubleclick', function(evt) {
            var element = evt.data.element;
            
            if (!element.isReadOnly()) {
                var ascendant = element.getAscendant('span', true);
                if (ascendant && ascendant.hasClass('_tooltip_user')) {
                    evt.data.dialog = 'KBPTooltipDialog';
                }
            }
        }, null, null, 0);
        
        
        if (editor.contextMenu) {
            editor.addMenuGroup('KBPTooltipGroup');
            
            editor.addMenuItem('KBPEditTooltip', {
                label: editor.lang.kbp_tooltip.editTooltip,
                icon: this.path + 'icons/KBPTooltip.png',
                command: 'kbp_tooltip',
                group: 'KBPTooltipGroup'
            });
            
            editor.addMenuItem('KBPRemoveTooltip', {
                label: editor.lang.kbp_tooltip.removeTooltip,
                icon: this.path + 'icons/KBPRemoveTooltip.png',
                command: 'kbp_remove_tooltip',
                group: 'KBPTooltipGroup'
            });
            
            editor.addMenuItem('KBPAddTooltip', {
                label: editor.lang.kbp_tooltip.insertTooltip,
                icon: this.path + 'icons/KBPTooltip.png',
                command: 'kbp_tooltip',
                group: 'KBPTooltipGroup'
            });
            
            editor.contextMenu.addListener(function(element) {
                var items = {};
                
                if (editor.contextMenu.items.length == 3) { // selection
                    for (var i in editor.contextMenu.items) {
                        if (editor.contextMenu.items[i]['state'] == CKEDITOR.TRISTATE_DISABLED) {
                            return false;
                        }
                    }
                    
                    items['KBPAddTooltip'] = CKEDITOR.TRISTATE_OFF;
                }
                
                var ascendant = element.getAscendant('span', true);
                if (ascendant && ascendant.hasClass('_tooltip_user')) {
                    items = {
                        KBPEditTooltip: CKEDITOR.TRISTATE_OFF,
                        KBPRemoveTooltip: CKEDITOR.TRISTATE_OFF
                    }
                }
                
                return items;
            });
        }
    },
    
    
    setToolbarStates: function(editor) {
        var sel = editor.getSelection();
        var ranges = sel.getRanges();
        var selectionIsEmpty = sel.getType() == CKEDITOR.SELECTION_NONE || (ranges.length == 1 && ranges[0].collapsed);
        var state = (selectionIsEmpty) ? CKEDITOR.TRISTATE_DISABLED : CKEDITOR.TRISTATE_OFF;
        editor.getCommand('kbp_tooltip').setState(state);
    }
});