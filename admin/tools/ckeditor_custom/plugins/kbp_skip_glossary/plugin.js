( function() {
	CKEDITOR.plugins.add( 'kbp_skip_glossary', {
		lang: 'en',
		icons: 'icon',
		init: function( editor ) {
			var style = new CKEDITOR.style({ element: 'skip_glоssary' });
			var styleCommand = new CKEDITOR.styleCommand( style );
			var command = editor.addCommand( 'KBPScipGlossary', styleCommand );
            
            // variant 1
            // editor.attachStyleStateChange(style, state => {if(!editor.readOnly) command.setState(state);});

            // variant 2
            editor.attachStyleStateChange(style, function f (state) {if(!editor.readOnly){ command.setState(state);}});
			
            editor.ui.addButton( 'KBPScipGlossary',{
				label: editor.lang.kbp_skip_glossary.bth,
                // icon: this.path + 'icons/icon',
				icon: 'icon',
				command: 'KBPScipGlossary',
                toolbar: 'kbp'
			})
		}
	});
} )();