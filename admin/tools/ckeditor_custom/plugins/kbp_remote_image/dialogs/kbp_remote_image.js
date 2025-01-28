CKEDITOR.dialog.add('KBPRemoteImage', function(editor) {
    return {
        title: editor.lang.kbp_remote_image.insertRemoteImage,
        minWidth: 400,
        minHeight: 80,
        
        contents: [
            {
                id: 'basic',
                elements: [
                    {
                        type: 'text',
                        id: 'url',
                        label: 'URL',
                        validate: CKEDITOR.dialog.validate.notEmpty(editor.lang.kbp_remote_image.noUrlError),
                        
                        setup: function(element) {
                            this.setValue(element.getAttribute('src'));
                        },
                        
                        commit: function(element) {
                            element.setAttribute('src', this.getValue());
                        }
                    },
                    {
                        type: 'text',
                        id: 'width',
                        label: editor.lang.kbp_remote_image.width,
                        
                        setup: function(element) {
                            this.setValue(element.getAttribute('width'));
                        },
                        
                        commit: function(element) {
                            element.setAttribute('width', this.getValue());
                        }
                    }
                ]
            }
        ],
        
        onShow: function() {
            var selection = editor.getSelection();
            
            var element = selection.getStartElement();console.log(element);
            if (element) {
                element = element.getAscendant('img', true);
            }
            
            if (!element || element.getName() != 'img') {
                element = editor.document.createElement('img');
                
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
            
            var image = this.element;
            this.commitContent(image);
            
            if (this.insertMode) {
                editor.insertElement(image);
            }
        }
    };
});