CKEDITOR.editorConfig = function( config )
{
	
	// common 
    config.entities = false;
    config.entities_latin = false;
    config.entities_greek = false;
    config.disableNativeSpellChecker = false;
    config.versionCheck = false;
    
    config.browserContextMenuOnCtrl = true;
    config.fillEmptyBlocks = false;
    config.height = '200px';

	var AssetsPath = CKEDITOR.basePath.substr(0, CKEDITOR.basePath.length - 9);
    // config.templates_files = [ AssetsPath + 'ckeditor_custom/cktemplates.php' ];
    // config.stylesSet = 'kbpstyles:' + AssetsPath + 'ckeditor_custom/ckstyles.js';	
	config.contentsCss = [ CKEDITOR.basePath + '/contents.css', AssetsPath + '/ckeditor_custom/contents.css' ];
    
    // custom plugins
    var customPluginsPath = AssetsPath + 'ckeditor_custom/plugins/';
    CKEDITOR.plugins.addExternal('base64image', customPluginsPath + 'base64image/', 'plugin.js');
    CKEDITOR.plugins.addExternal('wordcount', customPluginsPath + 'wordcount/', 'plugin.js');
    CKEDITOR.plugins.addExternal('notification', customPluginsPath + 'notification/', 'plugin.js');
    
    config.extraPlugins = 'base64image,wordcount,notification';
    
    config.wordcount = {
        showParagraphs: false,
        showWordCount: false,
        showCharCount: true,
        countSpacesAsChars: true,
        countHTML: true,
        maxWordCount: -1,
    	maxCharCount: 65535
    };

	// custom 	
    config.enterMode = CKEDITOR.ENTER_BR;
    config.shiftEnterMode = CKEDITOR.ENTER_BR;	
	
	config.toolbar = 'Export';
	config.allowedContent = true;
	config.toolbar_Export =
	[
		{ name: 'document', items : [ 'Source','-','Preview','-','Maximize'] },
        // { name: 'editing', items : [ 'Undo','Redo' ] },
		{ name: 'basicstyles', items : [ 'Bold','Italic' ] },
		{ name: 'paragraph', items : [ 'NumberedList','BulletedList' ] },
        { name: 'justify', items : ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock' ] },
		{ name: 'links', items : [ 'Link','Unlink' ] },
		{ name: 'insert', items : [ 'base64image','HorizontalRule' ] },
		{ name: 'colors', items : [ 'TextColor','BGColor' ] },
		{ name: 'styles', items : [ 'Font','FontSize' ] },
		{ name: 'tools', items : [ 'About' ] },        
	];
	
};