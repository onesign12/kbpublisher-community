CKEDITOR.plugins.add('kbp_remote_image', {
    icons: 'KBPRemoteImage',
    lang: 'en',
    
    init: function(editor) {
        
        editor.addCommand('KBPRemoteImage', new CKEDITOR.dialogCommand('KBPRemoteImage'));
        
        editor.ui.addButton('KBPRemoteImage', {
            label: editor.lang.kbp_remote_image.insertRemoteImage,
            command: 'KBPRemoteImage',
            toolbar: 'kbp'
        });
        
        CKEDITOR.dialog.add('KBPRemoteImage', this.path + 'dialogs/kbp_remote_image.js');
        
        editor.on('doubleclick', function(evt) {
            var element = evt.data.element;
            
            if (!element.isReadOnly()) {
                var ascendant = element.getAscendant('img', true);
                if (ascendant) {
                    evt.data.dialog = 'KBPRemoteImage';
                }
            }
        }, null, null, 0);
    }
});

