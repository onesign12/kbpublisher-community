// reapeat html	
function getMoreHtmlTrigger() {
	
	this.readroot = 'readroot';
	this.writeroot = 'writeroot';
	this.counter = 0;
	this.id_pref = 'more_html_';
	this.confirm_use = false; //alert to close window
	this.confirm_msg = '';
	
	
	this.get = function(el, name_value) {
        var id_value = el.name;
        
        var prefix = id_value.substring(0, id_value.lastIndexOf('_'));
        $('div[id^=' + prefix + '] input.minus_button:not(.step_in_use)').attr('disabled', false);
	    
		var new_id = this.id_pref + this.counter;
		var new_id2 = this.id_pref_populate + this.counter;
		
		var readBlock = this.getDivObject(this.readroot).cloneNode(true);console.log(this.id_pref, this.counter);
		readBlock.id = new_id;
		readBlock.style.display = 'block';
		
		// plus, minus
		var index = (document.all) ? 0 : 1;
		var contentDiv = readBlock.childNodes[index].childNodes; 
		
		for (var j=0;j<contentDiv.length;j++) {
			
			if(contentDiv[j].tagName == 'INPUT') {
				contentDiv[j].name = new_id; // name for +
			}
			
			if(contentDiv[j].tagName == 'HIDDEN') {
				contentDiv[j].name = new_id; // id for +
			}
		}
		
		// condition item
		var index = (document.all) ? 1 : 3;
		var contentDiv = readBlock.childNodes[index].childNodes;
        
        
        $(readBlock.childNodes[index]).find('select').attr('data-title', new_id2);
		$(readBlock.childNodes[index]).find('select').attr('name', this.condition_name+'['+this.counter+'][item]');
		
		
		// condition rule
		var index = (document.all) ? 2 : 5;
		//var contentDivObj = readBlock.childNodes[index];
		//var contentDiv = contentDivObj.childNodes;
		//contentDivObj.id = new_id2;
		//alert(contentDivObj);
        
        var name = this.condition_name + '[' + this.counter + '][rule][]';
        $(readBlock.childNodes[index]).find('select').attr('name', name);
        
		
		// insert after writeroot block
		/*var insertBlock = this.getDivObject(this.writeroot);
		insertBlock.parentNode.insertBefore(readBlock,insertBlock);*/
		
		// in place
		$(readBlock).insertAfter($(el).parent().parent());
		
        
        $('body').trigger('triggerRowAdded', {name: this.condition_name, id: new_id, id2: new_id2, counter: this.counter});
		
		if(this.confirm_use) {
			this.confirmAction();
		}
		
		this.counter++;
		return new_id;
	}
	
	
	this.confirmAction = function() {
		ret = confirm(this.confirm_msg);
		if(!ret) {
			window.close();
		}
	}
	
	
	this.remove = function (obj, confirm_msg) {
		var div = obj.parentNode.parentNode;	// get_more_html_1, ....
		this._remove(div, confirm_msg);
        
        var prefix = obj.name.substring(0, obj.name.lastIndexOf('_'));
        var selector = 'div[id^=' + prefix + ']';
        if ($(selector).length == 1) {
            $(selector).find('input.minus_button:not(.step_in_use)').attr('disabled', true);
        }
	}
	
	
	this._remove = function (obj, confirm_msg) {
		if(confirm_msg) {
			if(confirm(confirm_msg)) { 
				obj.parentNode.removeChild(obj);
                $('body').trigger('triggerRowRemoved', {id: obj.id});
			}
		} else {
			obj.parentNode.removeChild(obj);
            $('body').trigger('triggerRowRemoved', {id: obj.id});
		}
		
		//alert(obj.id);
	}	
	
	
	this.removeAll = function (div_class) {
		
		var block = this.getDivObject(this.writeroot).parentNode; //td
		var blocks = block.childNodes;
		var div_class = (div_class) ? div_class : 'popUpDiv';
		
		for (var j=0;j<blocks.length;j++) {
			alert();
			if(blocks[j].className == div_class) {
				this._remove(blocks[j]);
			}
		}
	}	
	
	
	this.getDivObject = function(div_obj) {
		if(typeof(div_obj) == 'object') {
			 return div_obj;
		} else {
			return document.getElementById(div_obj);
		}
	}
}


//s = new getMoreHtml();
//s.writeroot = document.getElementById('writeroot');