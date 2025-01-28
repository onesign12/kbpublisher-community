CKEDITOR.dialog.add('KBPTooltipDialog', function(editor) {
    return {
        title: editor.lang.kbp_tooltip.tooltipProperties,
        minWidth: 400,
        minHeight: 120,
        
        contents: [
            {
                id: 'kbp_tooltip',
                label: 'Tooltip',
                elements: [
                    {
                        type: 'textarea',
                        id: 'title',
                        label: editor.lang.kbp_tooltip.tooltipText,
                        
                        validate: CKEDITOR.dialog.validate.notEmpty(editor.lang.kbp_tooltip.noTextError),
                        
                        setup: function(element) {
                            this.setValue(element.getAttribute('title'));
                        },
                        
                        commit: function(element) {
                            element.setAttribute('title', this.getValue());
                        }
                    }
                ]
            }
        ],
        
        onShow: function() {
            var selection = editor.getSelection();
            
            var element = selection.getStartElement();
            if (element) {
                element = element.getAscendant('span', true);
            }
            
            if (!element || element.getName() != 'span') {
                element = editor.document.createElement('span');
                
                element.addClass('_tooltip_user');
                
                this.insertMode = true;
                
            } else {
                this.insertMode = false;
            }
            
            this.element = element;
            
            if (!this.insertMode) {
                this.setupContent(this.element);
            }
        },
        
        onOk: function() {
            var dialog = this;
            
            var tooltip = this.element;
            
            this.commitContent(tooltip);
            
            if (this.insertMode) {
                /*var selection = editor.document.getSelection();
                var ranges = selection.getRanges();
                var iterator = ranges.createIterator();
                var range = iterator.getNextRange();*/
                
                var selection = editor.getSelection()
                var range = selection.getRanges()[0];
                
                range.extractContents().appendTo(tooltip);
                range.insertNode(tooltip);
                
                editor.getSelection().getStartElement();
            }
        }
    };
});