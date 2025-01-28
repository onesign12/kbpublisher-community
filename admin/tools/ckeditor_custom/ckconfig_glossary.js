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
    config.height = '150px';
    config.allowedContent = true;

	var AssetsPath = CKEDITOR.basePath.substr(0, CKEDITOR.basePath.length - 9);
    // config.templates_files = [ AssetsPath + 'ckeditor_custom/cktemplates.php' ];
    // config.stylesSet = 'kbpstyles:' + AssetsPath + 'ckeditor_custom/ckstyles.js';	
	config.contentsCss = [ CKEDITOR.basePath + '/contents.css', AssetsPath + '/ckeditor_custom/contents.css' ];	


	// ckfinder
    config.filebrowserBrowseUrl = AssetsPath + 'ckfinder/ckfinder.html';
    config.filebrowserImageBrowseUrl = AssetsPath + 'ckfinder/ckfinder.html?type=Images';
    config.filebrowserFlashBrowseUrl = AssetsPath + 'ckfinder/ckfinder.html?type=Flash';
    config.filebrowserUploadUrl = AssetsPath + 'ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files';
    config.filebrowserImageUploadUrl = AssetsPath + 'ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images';
    config.filebrowserFlashUploadUrl = AssetsPath + 'ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash';


	// custom 	
    config.enterMode = CKEDITOR.ENTER_BR;
    config.shiftEnterMode = CKEDITOR.ENTER_BR;
	
	config.toolbar = 'Glossary';
	
	config.toolbar_Glossary =
	[
		{ name: 'document', items : [ 'Save','-','Source' ] },
		{ name: 'insert', items : [ 'Image' ] },
                '/',
		{ name: 'basicstyles', items : [ 'Bold','Italic' ] },
		{ name: 'paragraph', items : [ 'NumberedList','BulletedList' ] },
		{ name: 'links', items : [ 'Link','Unlink' ] },
		{ name: 'tools', items : [ 'About' ] }
	];
	
};