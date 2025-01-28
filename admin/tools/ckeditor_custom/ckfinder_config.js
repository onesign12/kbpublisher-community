CKFinder.customConfig = function( config )
{
    CKFinder.addExternalPlugin('kbp_safe_delete', '../ckeditor_custom/plugins/kbp_safe_delete/');
    CKFinder.addExternalPlugin('kbp_safe_rename', '../ckeditor_custom/plugins/kbp_safe_rename/');
    config.extraPlugins = 'kbp_safe_delete,kbp_safe_rename';
};
