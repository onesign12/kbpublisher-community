function init() {
	UpdateSort.init(document.forms['category']);
}


function subForm() {
    LeaveScreenMsg.skipCheck();
	optionalHandlersSelectAll();
}


function parentCategoryHandler2(category_id) {

    // sometimes user can try to move category to not allowed level...
    if(!isInteger(category_id)) {
        return false;
    }

    UpdateSort.populate();
    categoryAjaxHandler(category_id);
    getCategoryAdmin(category_id);
}


function getCategoryAdmin(category_id) {
    $('div.popUpDiv:not(#readroot)').remove();
    xajax_getCategoryAdmin(category_id);
}


function ajaxEmptyAdminUser() {
	var block = document.getElementById('tdAdmin');
	var blocks = block.childNodes;
	
	for (var j=0; j<blocks.length; j++) {
		//alert(blocks[j].id);
		if(blocks[j].className == 'popUpDiv') {
			block.removeChild(blocks[j]);
		}
	}
}


function ajaxFillAdminUser(id, name, counter) {
	var sa = new getMoreHtml();
	sa.readroot = document.getElementById('readroot');
	sa.writeroot = document.getElementById('writeroot');
	sa.confirm_use = false;
	sa.confirm_msg = '';
	sa.counter = counter;
	sa.class_name = 'popUpDiv';
	
	if(id != 'empty') {
		sa.get(id, name);
	}
}


// toogle role divs and private checkbox
function _ShowHideRolesXajax(val, private_id, div) {
	
	var ch = document.getElementById(private_id);
	
	// read
	if(private_id == 'private') {
	    if(val == 1 || val == 3) {
	        ch.checked = true;
	        ShowHideDiv(div, true, true);
	    } else {
	        ch.checked = false;
	        ShowHideDiv(div, false, true);
	    }
	    
    // write 
	} else {
	    if(val == 1 || val == 2) {
	        ch.checked = true;
	        ShowHideDiv(div, true, true);
	    } else {
            ch.checked = false;
	        ShowHideDiv(div, false, true);   
	    }
	}
}


function AssignPrivateReadFromParent(is_private, roles_ids) {
	_ShowHideRolesXajax(is_private, "private", "roles_div");
    selRoleHandler.createSelectCategories(roles_ids);    
}


function AssignPrivateWriteFromParent(is_private, roles_ids) {
	_ShowHideRolesXajax(is_private, "private_write", "roles_write_div");
	selRoleWriteHandler.createSelectCategories(roles_ids);
}	


function categoryAjaxHandler(category_id) {
	if(typeof xajax_getCategoryRoles === 'function') {
		xajax_getCategoryRoles(category_id); // set message for read and write
		xajax_addParentRoles(category_id);   // assign from parent
	}
	xajax_setCategoryValues(category_id);  // set checkboxex, type, etc from parent
}