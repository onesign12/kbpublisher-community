CKEDITOR.plugins.add('kbp_inline_link', {
	icons: 'KBPInlineLink',
    lang: 'en',
    
	init: function(editor) {
        var _this = this;
        
        var assetsPath = CKEDITOR.basePath.substr(0, CKEDITOR.basePath.length - 15);
        
        // commands
        editor.inlineLinkFrameUrl = assetsPath + 'index.php?module=file&page=file_entry&field_name=r&field_id=r&popup=ckeditor_inline';
        editor.addCommand('openInlinePopup', new CKEDITOR.dialogCommand('KBPInlineLinkDialog'));
        CKEDITOR.dialog.add('KBPInlineLinkDialog', this.path + 'dialogs/kbp_inline_link.js');
        
        // buttons
        editor.ui.addButton('KBPInlineLink', {
            label: 'Inline Link',
            command: 'openInlinePopup',
            toolbar: 'kbp',
            icon: this.path + 'icons/KBPInlineLink.svg'
        });
    }
});