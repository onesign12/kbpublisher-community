CKEDITOR.plugins.add('kbp_draggable_template', {
    requires: 'widget',
    
    init: function(editor) {
		editor.widgets.add('kbp_draggable_template', {

			editables: {
				content: {
					selector: 'div.box',
					allowedContent: null
				}
			},
            
			upcast: function(element) {
				return element.name == 'div' && element.hasClass('box');
			}
		});
	}
} );
