// Action Handler for one/two select element manipulation

function CategoriesSelectHandler()  {
    if (arguments.length == 0) return null; 
   
    // Cache list
    var cache = {};
   
    // Id of first Select element
    this.idFirst = arguments[0];
    
    // Id of second Select element
    this.idSecond = (arguments[1]) ? arguments[1] : this.idFirst;
    
    // Id of parent window Select element
    if (arguments[2]) this.idParent = arguments[2];
    
    // Handler variable name on parent page
    this.handler = null;
    
    // Max size of select element
    this.maxSize = 7;
 
    // Functions name to call after the close of popup
    this.onPopupCloseFunctions = new Array;
    
    // Max selected
    this.maxSelected = null;
    
    // Selected
    this.curSelected = null;
    
    this.nonActiveCategoryMsg = '';
    
    this.deleteNonActive = false;
    
    // Default categories list
    var cats = {};
    
    
    this.init = function() {
         $('#' + this.idFirst).children('option').each(function() {
              cats[this.value] = this.text;
         });
    }
    
    
    // Add existing options from parent window 
    this.getCategories = function() {
        if (window.top && this.idParent) {
            var objFirst = document.getElementById(this.idFirst);
            var objSecond = document.getElementById(this.idSecond);
            
            var parentCategories = [];
            
            var parent_window = PopupManager.getParentWindow();
            
            parent_window.$('#' + this.idParent).children('option').each(function(){
                parentCategories.push({
                    value: this.value,
                    text: this.text
                });
                
                cache[this.value] = this.text;
            });
            
            // Add categories from parent window
            for (var key in parentCategories) {
                var text = parentCategories[key]['text'];
                var value = parentCategories[key]['value'];
                
                objSecond.options[objSecond.options.length] = new Option(text, value);
                objFirst.options[this.indexByValue(value, this.idFirst)].disabled = true;
                
                $('body').trigger('kbpSelectedEntryTransferredToPopup', [{handler: this, id: value}]);
            }
        }   
    }

    
    // Insert option
    this.insertOption = function(id, index, categories) {
        
        if (index == -1) { // no selection
            return;
        }
        
        $('body').trigger('kbpEntrySelected', [{handler: this, id: id}]);
        
        var objSecond =	document.getElementById(this.idSecond);
        
        // for popup mode
        if (window.top != window.self) {
            var parent_window = PopupManager.getParentWindow();
            var parentHandler = parent_window[this.handler];
            
            // check if max
            if (parentHandler && parentHandler.maxSelected && (objSecond.options.length >= parentHandler.maxSelected)) {
                this.deleteOption(objSecond.options[objSecond.options.length - 1].value);
            }            
        }
        
        // Already added
        if (this.keyExists(id, cache)) {
            return;
        }
         
        var title = id;
        if (categories) {
            for (var i = 0; i < categories.length; i ++) {
                if (categories[i]['id'] == id) {
                    var title = categories[i]['title'];
                    cache[id] = categories[i]['title'];
                    break;
                }
            }
            
        } else {
            cache[id] = id;
        }
        
        objSecond.options[objSecond.options.length] = new Option(title, id);
        
        
        // Make added option disabled
        if (typeof(index) !== 'undefined') {
            var objFirst = document.getElementById(this.idFirst);
            objFirst.options[index].disabled = true;            
        }
        
        $('body').trigger('kbpEntryCreated', [{handler: this, id: id}]);
    }
    
    
    // Insert option by button
    this.insertOptionByButton = function(cats) {
        var objFirst =	document.getElementById(this.idFirst);
        if (objFirst.value){
            this.insertOption(objFirst.value, objFirst.selectedIndex, cats);
        }    
    }
    
    
    // Insert option by shift + enter
    this.insertOptionByHotkey = function() {
        var objFirst = document.getElementById(this.idFirst);
            if (objFirst.value){
                this.insertOption(objFirst.value, objFirst.selectedIndex, categories);
            }    
    }
    
    
    // Insert all options
    this.insertAll = function() {
        var objSecond =	document.getElementById(this.idSecond);
        objSecond.options.length = 0;
        for (var id in cats) {
            objSecond.options[objSecond.options.length] = new Option(cats[id], id);
            cache[id] = cats[id];
        }
        
        $('#' + this.idFirst).children('option').attr("disabled","disabled"); 
        this.resizeSelect();
    }
    

    this.deleteOption = function() {
        var objFirst = document.getElementById(this.idFirst);
        var objSecond =	document.getElementById(this.idSecond);
        
        if (arguments[0]) { // by passed value
            var sIndex = this.indexByValue(arguments[0], this.idSecond);
            objSecond.options[sIndex] = null;
            
            var fIndex = this.indexByValue(arguments[0], this.idFirst);
            objFirst.options[fIndex].disabled = false;
            
        } else { // getting selected options
            var items_to_delete = [];
            $('#' + this.idSecond + ' option:selected').each(function() {
                items_to_delete.push($(this).val());
            });
            
            if (items_to_delete.length) {                
                var last_deleted_index = false;
                for (var i in items_to_delete) {
                    last_deleted_index = $('#' + this.idSecond + ' option[value="' + items_to_delete[i] + '"]').index();
                    $('#' + this.idSecond + ' option[value="' + items_to_delete[i] + '"]').remove();
                }
                
                // highlighting the next option
                if (objSecond.options[last_deleted_index]) {
                    objSecond.options[last_deleted_index].selected = true;
                    
                } else { // or the 1st
                    if (objSecond.options[0]) {
                        objSecond.options[0].selected = true;
                    }
                }
            
                if (this.idFirst != this.idSecond) {
                    for (var i in items_to_delete) {  // enable it
                        $('#' + this.idFirst + ' option[value="' + items_to_delete[i] + '"]').attr('disabled', false);
                        
                        $('body').trigger('kbpEntryDeleted', [{
                            handler: this,
                            id: items_to_delete[i]
                        }]);
                    }
                }
            }
        
        }
        
        for (var key in cache) {
            delete cache[key];
        }

        $('#' + this.idSecond).children('option').each(function() {
            cache[this.value] = this.text;
        });
    }
    
    
    this.deleteOptionConfirm = function(alert_msg) {
        if(document.getElementById(this.idFirst).selectedIndex != -1) {
            var _this = this;
            confirm2(alert_msg, function() {
                _this.deleteOption();
            });
        }
    }
    
    
    // Clear all options
    this.clear = function() {
        var objSecond =	document.getElementById(this.idSecond);
        objSecond.options.length = 0;

        for (var key in cache) {
            delete cache[key];
        }
        
        $('#' + this.idFirst).children('option').removeAttr("disabled");
        this.resizeSelect();
    }
    
    
    // Move selected option to the top of list
    this.onTop = function() {
        var objSecond =	document.getElementById(this.idSecond);
        if (objSecond.selectedIndex != -1) {
            var curOption = objSecond.options[objSecond.selectedIndex];
            var firstOption = objSecond.options[0];
            objSecond.insertBefore(curOption, firstOption);
        }
    }
    
    
    // Move selected option to the one position above
    this.moveUp = function() {
        var objSecond =	document.getElementById(this.idSecond);
        // Check for option isn't on top
        if(objSecond.selectedIndex != 0) {
            var curOption = objSecond.options[objSecond.selectedIndex];
            var prevOption = objSecond.options[objSecond.selectedIndex - 1];
            objSecond.insertBefore(curOption, prevOption);   
        }
    }
    
    
    // Move selected option to the one position below
    this.moveDown = function() {
        var objSecond =	document.getElementById(this.idSecond);
        var curOption = objSecond.options[objSecond.selectedIndex];
        var nextOption = objSecond.options[objSecond.selectedIndex + 1];
        if (nextOption) objSecond.insertBefore(nextOption, curOption);
    }
    
    
    // Resize size of select element
    this.resizeSelect = function() {
        var objSecond =	document.getElementById(this.idSecond);
        if (objSecond.options.length <= this.maxSize) objSecond.size = objSecond.options.length;
        else objSecond.size = this.maxSize;
    }
    
    
    // Call PopUp window
    this.callPopUp = function(module, page, action, no_button, all_option, extra_params) {
        var action = (action) ? action : 'category';
        
        if (module.indexOf('http') == 0) {
            var url = module;
            
        } else {
            var url = 'index.php?module=' + module + '&page=' + page + '&action=' + action;
        }
        
        if (no_button) {
             url += '&no_button=1';
        }
        
        if (all_option) {
             url += '&all_option=1';
        }
        
        if (extra_params) {
             url += '&' + extra_params;
        }
        // console.log(url);
        PopupManager.create(url, 'r', this.getObjectName());
    }
    
    
    // Get object variable name
    this.getObjectName = function() {
        for (var k in window) {
            if (window[k] == this) {
                return k.toString();
            }
        }
    }
    
    
    // Set disabled options in Select
    this.setDisabled = function(ids) {
        
        $('#' + this.idFirst).children('option').each(function() {
            cache[this.value] = this.text;  
        });
        
        for (value in cache) {
            if (this.elExists(value, ids)) {
                var index = this.indexByValue(value, this.idFirst);
                document.getElementById(this.idFirst).options[index].disabled = true;
            }
            delete cache[value]; 
        }
        
    }
    
    
    this.setEnabled = function(ids) {
        
        $('#' + this.idFirst).children('option').each(function() {
            cache[this.value] = this.text;  
        });
        
        for (value in cache) {
            if (this.elExists(value, ids)) {
                var index = this.indexByValue(value, this.idFirst);
                document.getElementById(this.idFirst).options[index].disabled = false;
            }
        }
        
    }
    
    
    // Transfer selected categories to the parent window on submit form
    this.addParentSelect = function() {
        if (window.top != window.self) {
            var popupCategories = [];
            
            $('#' + this.idSecond).children('option').each(function() {
                popupCategories.push({
                    value: this.value,
                    text: this.text
                });
            });
            
            var param = arguments[0];

            var parent_window = PopupManager.getParentWindow();

            parent_window[this.handler].createSelectCategories(popupCategories);
            parent_window[this.handler].callOnPopupCloseFunctions(popupCategories, param);
            
            parent_window.$('body').trigger('kbpSelectedEntryTransferredToParentWindow', [{handler: this, items: popupCategories}]);
            
            PopupManager.close();
        }
    }
    
    
    // Create categories in parent window
    this.createSelectCategories = function(categories) {
        // Clear cache
        for (var key in cache) {
            delete cache[key];
        }
                
        var objSelect = document.getElementById(this.idFirst);
        objSelect.options.length = 0;
        
        for (var key in categories) {
            var text = categories[key]['text'];
            var value = categories[key]['value'];
            
            if (!(value in cache)) {
                cache[value] = text;
                objSelect.options[objSelect.options.length] = new Option(text, value);    
            }
        }   
    }
    
    
    // Search key in object
    this.keyExists =  function(key, array) {
        if(!array || (array.constructor !== Array && array.constructor !== Object) ){
            return false;  
        }
        return key in array;  
    }  
    
    
    // Search element in array
    this.elExists = function(el, arr) {
        for(var i = 0, l = arr.length; i < l; i++) {
            if(arr[i] == el) {
                return true;
		    }
	    }
	    return false;
    }
    
    
    // Return index of Option element in Select element by value
    this.indexByValue = function(value, id) {
        var index = null;
        $('#' + id).children('option').each(function() {
            if (this.value == value) index = this.index;
        });
        return index;
    }
     
    
	this.selectAll = function() {
        $('#' + this.idSecond).children('option').each(function() {
            this.selected = true;
        });
	}
       
    
    this.rebuildDisabled = function(predefined_ids) {
        var ids = (predefined_ids) ? predefined_ids.slice(0) : [];
        
        $('#' + this.idSecond).children('option').each(function() {
            ids.push(this.value);
        });
        
        for (var i in ids) {
            var id = ids[i];
            $('#' + this.idFirst).find('option[value="' + id + '"]').attr('disabled', true);
            
            $('body').trigger('kbpEntriesFiltered', [{handler: this, id: id}]);
        }
    }
    
    
    this.setSelectWidth = function(id) {
        /*if ($.browser.msie) {
            document.getElementById(id).style.width = '100%';
        } else {*/
            document.getElementById(id).style.minWidth = '100%';
        //}
    }
    
        
    this.setParentHandler = function(name) {
		this.handler = name;
    }
    
    
    this.callOnPopupCloseFunctions = function() {
        var params = arguments;
        for (var func in this.onPopupCloseFunctions) {
            // function exists
            if (typeof window[this.onPopupCloseFunctions[func]] == 'function') {
                window[this.onPopupCloseFunctions[func]](params);                
            }
        }     
    }
    
    
    this.addOnPopupCloseFunction = function(name) {
        this.onPopupCloseFunctions.push(name);   
    }
    
    
    this.setMaxAllowSelected = function(val) {
        this.maxSelected = val;
    }

}


function optionalHandlersSelectAll() {
    if (typeof selRoleHandler !== 'undefined') {   
        selRoleHandler.selectAll();
    }
    if (typeof selRoleWriteHandler !== 'undefined') {   
        selRoleWriteHandler.selectAll();
    }
    if (typeof selMustreadRoleHandler !== 'undefined') {   
        selMustreadRoleHandler.selectAll();
    }
}