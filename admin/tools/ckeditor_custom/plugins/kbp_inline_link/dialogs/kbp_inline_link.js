CKEDITOR.dialog.add('KBPInlineLinkDialog', function(editor) {
    return {
        title: editor.lang.kbp_inline_link.inlineLink,
        minWidth: 900,
        minHeight: 400,
        height: 400,
        buttons: [],
        
        contents: [
            {
                id: 'kbp_inline_link',
                label: '',
                elements: [
                    {
                        type: 'html',
                        html: '<iframe class="popup" style="height: 400px;" src="' + editor.inlineLinkFrameUrl + '"></iframe>'
                    }
                ]
            }
        ]
    };
});