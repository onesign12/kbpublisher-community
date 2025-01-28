/*
Copyright (c) 2003-2011, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.stylesSet.add( 'kbpstyles',
[
   
	/* KBPublisher styles */ 
	
    { name : 'Block Yellow'     , element : 'div', attributes : { 'class' : 'box yellowBox' } },
    { name : 'Block Gray'       , element : 'div', attributes : { 'class' : 'box greyBox' } },
    { name : 'Block Dark'       , element : 'div', attributes : { 'class' : 'box darkBox' } },
    
	{ name : 'Title H2 (underline)'	, element : 'h2', attributes : { 'class' : 'lineTitle' } },
    // { name : 'Title H2'    , element : 'h3', attributes : { 'class' : 'colorTitle' } }, // added in 7.0 eleontev 18 Mar 2019
    { name : 'Title H3 (underline)'	, element : 'h3', attributes : { 'class' : 'lineTitle' } },
    { name : 'Title H3'	, element : 'h3', attributes : { 'class' : 'colorTitle' } }, // changed in 7.0 eleontev 18 Mar 2019
    
	    

	/* from previous versions and new ones */

    // comented in version 7.0 eleontev 28 Apr 2018
    // { name : 'Title'             , element : 'h3', attributes : { 'class' : 'FCKTitle' } },
    // { name : 'Blue Title'        , element : 'h3', styles : { 'color' : 'Blue' } },
    // { name : 'Red Title'        , element : 'h3', styles : { 'color' : 'Red' } },

    { name : 'Code'	, element : 'span', attributes : { 'class' : 'FCKCode' } },
	{ name : 'Marker: Yellow'	, element : 'span', styles : { 'background-color' : 'Yellow' } },
	{ name : 'Marker: Green'	, element : 'span', styles : { 'background-color' : 'Lime' } },
	
	{ name : 'Big'				, element : 'big' },
	{ name : 'Small'			, element : 'small' },
	{ name : 'Typewriter'		, element : 'tt' },
	
	{ name : 'Computer Code'	, element : 'code' },
	{ name : 'Keyboard Phrase'	, element : 'kbd' },
	{ name : 'Sample Text'		, element : 'samp' },
	{ name : 'Variable'			, element : 'var' },
	
	{ name : 'Deleted Text'		, element : 'del' },
	{ name : 'Inserted Text'	, element : 'ins' },
	
	{ name : 'Cited Work'		, element : 'cite' },
	{ name : 'Inline Quotation'	, element : 'q' },
	
	{ name : 'Language: RTL'	, element : 'span', attributes : { 'dir' : 'rtl' } },
	{ name : 'Language: LTR'	, element : 'span', attributes : { 'dir' : 'ltr' } },

    
	/* Object Styles */
	
	{
		name : 'Image on Left',
		element : 'img',
		attributes :
		{
			'style' : 'padding: 5px; margin-right: 5px',
			'border' : '2',
			'align' : 'left'
		}
	},
	
	{
		name : 'Image on Right',
		element : 'img',
		attributes :
		{
			'style' : 'padding: 5px; margin-left: 5px',
			'border' : '2',
			'align' : 'right'
		}
	},
	
	{ name : 'Borderless Table', element : 'table', styles: { 'border-style': 'hidden', 'background-color' : '#E6E6FA' } },
	{ name : 'Square Bulleted List', element : 'ul', styles : { 'list-style-type' : 'square' } },
    
    
    /* from recent version 4.8.0 */
    
	{
		name: 'Compact Table',
		element: 'table',
		attributes: {
			cellpadding: '5',
			cellspacing: '0',
			border: '1',
			bordercolor: '#ccc'
		},
		styles: {
			'border-collapse': 'collapse'
		}
	}

]);
