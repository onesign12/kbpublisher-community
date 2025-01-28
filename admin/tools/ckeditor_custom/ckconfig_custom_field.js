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
    config.height = '100px';	
	
	
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
	
	config.toolbar = 'CustomField';
 	
	config.toolbar_CustomField =
	[
		{ name: 'document', items : [ 'Save','-','Source','-','Templates','-','Preview','-','Maximize' ] },
		{ name: 'clipboard', items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Print' ] },
		{ name: 'editing', items : [ 'Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat','Scayt' ] },
		{ name: 'tools', items : [ 'ShowBlocks','-','About' ] },
                '/',
        { name: 'basicstyles', items : [ 'Bold','Italic','Underline','Strike' ] },
        { name: 'paragraph', items : [ 'NumberedList','BulletedList','-','Outdent','Indent', '-', 'Blockquote' ] },
        { name: 'justify', items : ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock' ] },
        { name: 'links', items : [ 'Link','Unlink','Anchor' ] },
		{ name: 'insert', items : [ 'Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak' ] },
        { name: 'colors', items : [ 'TextColor','BGColor' ] },
                '/',        
		{ name: 'styles', items : [ 'Styles','Format','Font','FontSize' ] }
	];
	
	
};