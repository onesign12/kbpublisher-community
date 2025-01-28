function isAnySelected(oListbox) {
	for (var i=0; i < oListbox.options.length; i++) {
		if (oListbox.options[i].selected) {
			return true;
		}
	}
	
	return false;
}


// generate select for every category
function generateSortSelect() {
	
	//empty html
	var block = document.getElementById('sort_order_div');
	block.innerHTML = '<span id="writeroot_sort"></span>';	
	
	oListbox = document.getElementById('category');
	//if(oListbox.length) {
	//	return;
	//}
	
	for (var i=0; i < oListbox.options.length; i++) {
		xajax_populateSortSelect(oListbox.options[i].value, oListbox.options[i].text);
	}
}


var generateSortSelectHandlerTimerId = 0;
function generateSortSelectHandler() {

	if(generateSortSelectHandlerTimerId) {
		//alert(generateSortSelectHandlerTimerId);
		clearTimeout(generateSortSelectHandlerTimerId);
	}
	
	generateSortSelectHandlerTimerId = setTimeout(generateSortSelect, 1500);
}
