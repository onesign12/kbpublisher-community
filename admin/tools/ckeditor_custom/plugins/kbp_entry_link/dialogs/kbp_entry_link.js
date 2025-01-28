CKEDITOR.dialog.add('KBPArticleLinkDialog', function(editor) {
    return {
        title: editor.lang.kbp_entry_link.linkToArticle,
        minWidth: 900,
        minHeight: 400,
        height: 400,
        buttons: [],
        
        contents: [
            {
                id: 'kbp_article_link',
                label: '',
                elements: [
                    {
                        type: 'html',
                        html: '<iframe class="popup" style="height: 400px;" src="' + editor.articleFrameUrl + '"></iframe>'
                    }
                ]
            }
        ]
    };
});

CKEDITOR.dialog.add('KBPFileLinkDialog', function(editor) {
    return {
        title: editor.lang.kbp_entry_link.linkToFile,
        minWidth: 900,
        minHeight: 400,
        height: 400,
        buttons: [],
        
        contents: [
            {
                id: 'kbp_file_link',
                label: '',
                elements: [
                    {
                        type: 'html',
                        html: '<iframe class="popup" style="height: 400px;" src="' + editor.fileFrameUrl + '"></iframe>'
                    }
                ]
            }
        ]
    };
});

CKEDITOR.dialog.add('KBPFileEmbedDialog', function(editor) {
    return {
        title: editor.lang.kbp_entry_link.embedFile,
        minWidth: 900,
        minHeight: 400,
        height: 400,
        buttons: [],
        
        contents: [
            {
                id: 'kbp_file_embed',
                label: '',
                elements: [
                    {
                        type: 'html',
                        html: '<iframe class="popup" style="height: 400px;" src="' + editor.fileFrameUrl2 + '"></iframe>'
                    }
                ]
            }
        ]
    };
});