CKEDITOR.plugins.add('kbp_entry_link', {
	icons: 'KBPArticleLink,KBPFileLink,KBPFileEmbed',
    lang: 'en',
    
	init: function(editor) {
        var _this = this;
        
        var assetsPath = CKEDITOR.basePath.substr(0, CKEDITOR.basePath.length - 15);
        
        // commands
        editor.articleFrameUrl = assetsPath + 'index.php?module=knowledgebase&page=kb_entry&no_attach=1&field_name=r&field_id=r&popup=ckeditor';
        editor.addCommand('openArticlePopup', new CKEDITOR.dialogCommand('KBPArticleLinkDialog', {
	        startDisabled: true,
            canUndo: true
        }));
        CKEDITOR.dialog.add('KBPArticleLinkDialog', this.path + 'dialogs/kbp_entry_link.js');
        
        
        editor.fileFrameUrl = assetsPath + 'index.php?module=file&page=file_entry&field_name=r&field_id=r&popup=ckeditor';
        editor.addCommand('openFilePopup', new CKEDITOR.dialogCommand('KBPFileLinkDialog', {
	        startDisabled: true,
            canUndo: true
        }));
        CKEDITOR.dialog.add('KBPFileLinkDialog', this.path + 'dialogs/kbp_entry_link.js');

        
        editor.fileFrameUrl2 = assetsPath + 'index.php?module=file&page=file_entry&field_name=r&field_id=r&popup=embed';
        editor.addCommand('openFilePopup2', new CKEDITOR.dialogCommand('KBPFileEmbedDialog', {
	        startDisabled: false,
            canUndo: true
        }));
        CKEDITOR.dialog.add('KBPFileEmbedDialog', this.path + 'dialogs/kbp_entry_link.js');
        
        
        editor.addCommand('insertLink', {
            exec: function(editor, data) {
                if (CKEDITOR.env.ie) {
                    editor.focus();
                }
                
                if (!_this.selection) {
                    if (editor.getSelection().getType() != CKEDITOR.SELECTION_TEXT) {
                        alert(editor.lang.kbp_entry_link.insertNoSelected);
                        return;
                    }
            
                    var selection = editor.getSelection().getNative();
            
                    var text = (selection.createRange) ? selection.createRange().text : selection;
                    var text = String(text);
                }
                
        
                //if (text.length > 0) {
               
                    var ids = data.value_id.toString().split(',');
                    if(ids[1]) {
                        var l = "[link:" + data.field + "|" + ids[0] + "|" + ids[1] + "]";
                        
                    } else {
                        var l = "[link:" + data.field + "|" + ids[0] + "]";
                    }
        
                    var styleNode = new CKEDITOR.dom.element('a', editor.document);
                    styleNode.setAttribute('href', l);
        
                    var selection = editor.document.getSelection();
                    //var selection = _this.selection;
                    var ranges = selection.getRanges();
                    var iterator = ranges.createIterator();
                    styleRange = iterator.getNextRange();
        
                    styleRange.extractContents().appendTo(styleNode);
                    styleRange.insertNode(styleNode);
                    
                    if (data.do_confirm) {
                        if (confirm(editor.lang.kbp_entry_link.insertLinkAdded)) {
                            PopupManager.close();
                            CKEDITOR.dialog.getCurrent().hide();
                        }
                    } else {
                        if (CKEDITOR.dialog.getCurrent()) {
                            CKEDITOR.dialog.getCurrent().hide();
                        }
                    }
        
                /*} else {
                   alert(editor.lang.kbp_entry_link.insertNoSelected);
                }*/
            }
        });
        
        editor.addCommand('embedPdf', {
            exec: function(editor, data) {
                
                if (CKEDITOR.env.ie) {
                    editor.focus();
                }            
        
                var ids = data.value_id.toString().split(',');
                if(ids[1]) {
                    var l = "[embed:" + data.field + "|" + ids[0] + "|" + ids[1] + "]";
                    
                } else {
                    var l = "[embed:" + data.field + "|" + ids[0] + "]";
                }
    
                editor.insertHtml('<iframe class="embedPdf" width="100%" height="100" src="'+ l +'"></iframe>');
                // CKEDITOR('<p>This is a new paragraph.</p>');
    
    
                // var styleNode = new CKEDITOR.dom.element('frame', editor.document);
                // styleNode.setAttribute('src', l);
                // styleNode.setAttribute('class', 'embedPdf');
    
                // var selection = editor.document.getSelection();
                
                // alert( selection.getType() );
                
                // var selection = _this.selection;
                // var ranges = selection.getRanges();
                // var iterator = ranges.createIterator();
                // styleRange = iterator.getNextRange();
    
                // styleRange.extractContents().appendTo(styleNode);
                // styleRange.insertNode(styleNode);
                
                if (data.do_confirm) {
                    if (confirm(editor.lang.kbp_entry_link.insertFileEmbed)) {
                        PopupManager.close();
                        CKEDITOR.dialog.getCurrent().hide();
                    }
                } else {
                    if (CKEDITOR.dialog.getCurrent()) {
                        CKEDITOR.dialog.getCurrent().hide();
                    }
                }
            }
        });
        
        // buttons
        editor.ui.addButton('KBPArticleLink', {
            label: editor.lang.kbp_entry_link.linkToArticle,
            command: 'openArticlePopup',
            toolbar: 'kbp'
        });
        
        editor.ui.addButton('KBPFileLink', {
            label: editor.lang.kbp_entry_link.linkToFile,
            command: 'openFilePopup',
            toolbar: 'kbp'
        });
                
        editor.ui.addButton('KBPFileEmbed', {
            label: editor.lang.kbp_entry_link.embedFile,
            command: 'openFilePopup2',
            toolbar: 'kbp'
        });
        
        // states        
        editor.on('contentDom', function(evt) {
            var editable = editor.editable();
            
            editable.attachListener(CKEDITOR.env.ie ? editable : editor.document.getDocumentElement(), 'mouseup', function() {
				mouseupTimeout = setTimeout( function() {
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
        
        
        if (editor.contextMenu) {
            editor.addMenuGroup('KBPEntryLinkGroup');
            
            editor.addMenuItems({
                insertArticleLink : {
                    label : editor.lang.kbp_entry_link.linkToArticle,
                    command : 'openArticlePopup',
                    group : 'KBPEntryLinkGroup',
                    order : 1,
                    icon: this.path + 'icons/KBPArticleLink.png'
                },
                insertFileLink : {
                    label : editor.lang.kbp_entry_link.linkToFile,
                    command : 'openFilePopup',
                    group : 'KBPEntryLinkGroup',
                    order : 1,
                    icon: this.path + 'icons/KBPFileLink.png'
                }
            });
            
            editor.contextMenu.addListener(function(element) {
                var count = editor.contextMenu.items.length;
                
                if (count == 4 || count == 5) { // selection
                    for (var i in editor.contextMenu.items) {
                        if (editor.contextMenu.items[i]['state'] == CKEDITOR.TRISTATE_DISABLED) {
                            return false;
                        }
                    }
                    
                    return {
                        insertArticleLink: CKEDITOR.TRISTATE_OFF,
                        insertFileLink: CKEDITOR.TRISTATE_OFF
                    };
                }
            });
        }
    },
    
    
    setToolbarStates: function(editor) {
        var sel = editor.getSelection();
        var ranges = sel.getRanges();
        var selectionIsEmpty = sel.getType() == CKEDITOR.SELECTION_NONE || (ranges.length == 1 && ranges[0].collapsed);
        var state = (selectionIsEmpty) ? CKEDITOR.TRISTATE_DISABLED : CKEDITOR.TRISTATE_OFF;
        editor.getCommand('openArticlePopup').setState(state);
        editor.getCommand('openFilePopup').setState(state);
    }
});