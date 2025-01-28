function parse_xajax_getChildCategories() {
    $('#ct').bind('change', function(){
        var value = $(this).val();
        if (value == 'all') {
		    var select = document.getElementById('category_child');
		    select.options.length = 0;
            $('#category_filter_select').hide();
            return;            
        }
    
        xajax_getChildCategories(value);
    });
}


function ajaxPopulateCategoryChildSelect(values) {
    $('#category_filter_select').show();

    var select = document.getElementById('category_child');
    select.options.length = 0;
    for (i in values) {
        select.options[select.options.length] = new Option(values[i], i);
    }
}


function ToogleAdvancedSearch() {
	var d = (document.getElementById('advanced_search').style.display == 'none') ? 0 : 1;
	if(d) {
		$('#advanced_search').hide();
		$('#advanced_show_btn').val('+');
		xajax_parseAdvancedSearch(0);
		
	} else {
		$('#advanced_search').show();
		$('#advanced_show_btn').val('-');
		xajax_parseAdvancedSearch(1);
	}
}