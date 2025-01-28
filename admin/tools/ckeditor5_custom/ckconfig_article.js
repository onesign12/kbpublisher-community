const LICENSE_KEY = '';
// 
// if (!LICENSE_KEY) {
// 	alert(
// 		'CKEditor Commercial Features included in this demo require a license key.\n' +
// 		'Check the index.js file for more information.'
// 	);
// }

import {
    ClassicEditor,
    Essentials,
    Autoformat,
    BlockQuote,
    Indent, 
    IndentBlock,
    Bold,
    CloudServices,
    Code,
    CodeBlock,
    Heading,
    HorizontalLine,
    Image,
    ImageToolbar,
    ImageUpload,
    ImageCaption,
    ImageResize,
    ImageStyle,
    LinkImage,
    ImageInsertViaUrl,
    MediaEmbed,
    Base64UploadAdapter,
    Italic,
    Underline,
    Link,
    List,
    ListProperties,
    // Markdown,
    Mention,
    Paragraph,
    SourceEditing,
    SpecialCharacters,
    SpecialCharactersEssentials,
    Strikethrough,
    Table,
    TableToolbar,
    TextTransformation,
    TodoList,
    GeneralHtmlSupport,
    Style,
    Font,
    FindAndReplace,
    AccessibilityHelp,
    SelectAll,
    RemoveFormat,
    Subscript,
    Superscript,
    Alignment,
    PageBreak,
    // SimpleUploadAdapter,
} from 'ckeditor5';

// import {
    // SlashCommand
// } from 'ckeditor5-premium-features'

// import 'ckeditor5/ckeditor5.css';
// import 'ckeditor5-premium-features/ckeditor5-premium-features.css';

/**
 * Enrich the special characters plugin with emojis.
 */
// function SpecialCharactersEmoji(editor) {
//     if (!editor.plugins.get('SpecialCharacters')) {
//         return;
//     }
// 
//     // Make sure Emojis are last on the list.
//     this.afterInit = function () {
//         editor.plugins.get('SpecialCharacters').addItems('Emoji', EMOJIS_ARRAY);
//     };
// }

ClassicEditor.create(
    document.querySelector('#editor'),
    {
        plugins: [
            Autoformat,
            BlockQuote,
            Indent, 
            IndentBlock,
            Bold,
            CloudServices,
            Code,
            CodeBlock,
            Essentials,
            Heading,
            HorizontalLine,
            Image,
            ImageToolbar,
            ImageUpload,
            ImageCaption,
            ImageResize,
            ImageStyle,
            LinkImage,
            ImageInsertViaUrl,
            MediaEmbed,
            Base64UploadAdapter,
            Italic,
            Underline,
            Link,
            List,
            ListProperties,
            // Markdown,
            Mention,
            Paragraph,
            SourceEditing,
            SpecialCharacters,
            // SpecialCharactersEmoji,
            SpecialCharactersEssentials,
            Strikethrough,
            Table,
            TableToolbar,
            TextTransformation,
            TodoList,
            GeneralHtmlSupport,
            Style,
            Font,
            FindAndReplace,
            AccessibilityHelp,
            SelectAll,
            RemoveFormat,
            Subscript,
            Superscript,
            Alignment,
            PageBreak,
            // SimpleUploadAdapter,
            // ...(LICENSE_KEY ? [SlashCommand] : []),
        ],
        licenseKey: LICENSE_KEY,
        // language: 'en',
        toolbar: {
            items: [
                'undo',
                'redo',
                '|',
                'sourceEditing',
                '|',
                'bold',
                'italic',
                {
                    label: 'Fonts',
                    icon: 'text',
                    items: ['underline', 'strikethrough','|', 
                            'subscript', 'superscript', 'code', '|',
                            'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor' ]
                },
                'removeFormat',
                '|',
                'alignment',
                '|',
                'bulletedList',
                'numberedList',
                'todoList',
                '|',
                'link',
                // 'uploadImage',
                'insertImage',
                'mediaEmbed',
                'insertTable',
                'codeBlock',
                '|',
                {
                   // This dropdown has the icon disabled and a text label instead.
                   label: 'Other',
                   // icon: false,
                   items: [ 'blockQuote', '|', 'outdent', 'indent', '|','horizontalLine', 'specialCharacters', '|', 'pageBreak', 'selectAll', 'findAndReplace', 'accessibilityHelp', 
                    ]
                },
                '-', // break point
                'heading',
                '|',
                'style',
            ],
            shouldNotGroupWhenFull: true,
        },
        codeBlock: {
            languages: [
                { language: 'css', label: 'CSS' },
                { language: 'html', label: 'HTML' },
                { language: 'javascript', label: 'JavaScript' },
                { language: 'php', label: 'PHP' },
            ],
        },
        heading: {
            options: [
                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                {
                    model: 'heading1',
                    view: 'h1',
                    title: 'Heading 1',
                    class: 'ck-heading_heading1',
                },
                {
                    model: 'heading2',
                    view: 'h2',
                    title: 'Heading 2',
                    class: 'ck-heading_heading2',
                },
                {
                    model: 'heading3',
                    view: 'h3',
                    title: 'Heading 3',
                    class: 'lineTitle',
                },
                {
                    model: 'heading2Line',
                    view: {
                        name: 'h2',
                        classes: 'lineTitle'
                    },
                    title: 'Title H2 (Underline)',
                    class: 'ck-heading_heading2_lineTitle',
                    converterPriority: 'high'
                },
                {
                    model: 'heading3Line',
                    view: {
                        name: 'h3',
                        classes: 'lineTitle'
                    },
                    title: 'Title H3 (Underline)',
                    class: 'ck-heading_heading3_lineTitle',
                    converterPriority: 'high'
                },
                // {
                //     model: 'heading4',
                //     view: 'h4',
                //     title: 'Heading 4',
                //     class: 'ck-heading_heading4',
                // },
                // {
                //     model: 'heading5',
                //     view: 'h5',
                //     title: 'Heading 5',
                //     class: 'ck-heading_heading5',
                // },
                // {
                //     model: 'heading6',
                //     view: 'h6',
                //     title: 'Heading 6',
                //     class: 'ck-heading_heading6',
                // },
            ],
        },
        style: {
            definitions: [
                {
                    name: 'Block Yellow',
                    element: 'span',
                    classes: [ 'box', 'yellowBox' ]
                },
                {
                    name: 'Block Gray',
                    element: 'p',
                    classes: [ 'box', 'greyBox' ]
                },
                {
                    name: 'Block Dark',
                    element: 'p',
                    classes: [ 'box', 'darkBox' ]
                },
            ]
        },
        alignment: {
            options: [ 'left', 'right', 'center', 'justify' ]
        },
        image: {
            toolbar: [
                // 'imageTextAlternative',
                'resizeImage',
                '|',
                'imageStyle:inline', 'imageStyle:block', 'imageStyle:side',
			    '|',
			    'toggleImageCaption', 'imageTextAlternative',
                '|',
                'linkImage'
            ],
            insert: {
                type: 'auto'
            },
            resizeOptions: [
                {
                    name: 'resizeImage:original',
                    value: null,
                    icon: 'original'
                },
                {
                    name: 'resizeImage:custom',
                    value: 'custom',
                    icon: 'custom'
                },
                {
                    name: 'resizeImage:50',
                    value: '50',
                    icon: 'medium'
                },
                {
                    name: 'resizeImage:75',
                    value: '75',
                    icon: 'large'
                }
            ],
        },
        table: {
            contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'],
        },
        htmlSupport: {
            allow: [
                {
                    name: /.*/,
                    attributes: true,
                    classes: true,
                    styles: true
                }
            ]
        },
        list: {
            properties: {
                styles: true,
                startIndex: true,
                reversed: true
            }
        }
        
        //https://www.npmjs.com/package/ckeditor5-simple-upload
        // simpleUpload: {
        //     // The URL that the images are uploaded to.
        //     uploadUrl: 'http://example.com',
        // 
        //     // Enable the XMLHttpRequest.withCredentials property.
        //     withCredentials: true,
        // 
        //     // Headers sent along with the XMLHttpRequest to the upload server.
        //     headers: {
        //         'X-CSRF-TOKEN': 'CSRF-Token',
        //         Authorization: 'Bearer <JSON Web Token>'
        //     }
        // }
    }
)
.then((editor) => {
    window.oEditor = editor;
    // console.log(Array.from( editor.ui.componentFactory.names() ));
})
.catch((error) => {
    console.error(error.stack);
});