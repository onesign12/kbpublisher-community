function getMoreHtmlFiles() {
    
    this.readroot = 'readroot_file';
    this.writeroot = 'writeroot_file';
    this.counter = 1; // one input file is always embedded in the page
    this.id_pref = 'more_html_file_';
    this.name_pref = 'file_';
    this.confirm_remove = false;
    this.confirm_remove_msg = 'Sure!';
    this.denied_extension_msg = 'Denied file extension!';
    this.file_exists_handler = false;
    this.file_exists_msg = 'File with the same name exists!';
    this.max_allowed = 3;
    this.allowed_extension = [];
    this.denied_extension = [];


    this.get = function() {
        
        this.showAddLink(0);
        if(this.getInputsNum() > this.max_allowed) {
            return;
        }
    
        this.counter++;
        var new_id = this.id_pref + this.counter;
	
        var readBlock = this.getDivObject(this.readroot).cloneNode(true);
        readBlock.id = new_id;
        readBlock.style.display = 'block';

        // var index = (document.all) ? 0 : 1;
        // var contentDiv = readBlock.childNodes[index].childNodes;         
        var contentDiv = readBlock.childNodes;

        for (var j=0;j<contentDiv.length;j++) {
		
            if(contentDiv[j].tagName == 'INPUT') {
                contentDiv[j].name = this.name_pref + this.counter;
            }
		
            if(contentDiv[j].tagName == 'SPAN') {
                contentDiv[j].style.display = 'none';
            }
        }
	
        // insert after writeroot block
        var insertBlock = this.getDivObject(this.writeroot);
        insertBlock.parentNode.insertBefore(readBlock,insertBlock);
	
        return new_id;
    }


    this.remove = function (obj) {
        obj = obj.parentNode;
        this._remove(obj);
    }


    this._remove = function(obj){
        if (this.confirm_remove && confirm(this.confirm_remove_msg)) {
            obj.parentNode.removeChild(obj);
            this.showAddLink(1);
        } else {
            obj.parentNode.removeChild(obj);
            this.showAddLink(1);
        }
    }


    this.getDivObject = function(div_obj) {
        if(typeof(div_obj) == 'object') {
            return div_obj;
        } else {
            return document.getElementById(div_obj);
        }
    }


    this.getInputsNum = function(val) {    
        var inputs = document.getElementsByTagName('input');
        var count = -1; //-1  because one in readroot

        for (var j=0;j<inputs.length;j++) {
            if(inputs[j].className == 'file_input') {
            // if(inputs[j].name.indexOf(this.id_pref)) {
                count ++;
            }
        }
        
        return count;
    }


    this.showAddLink = function(val) {
        var display = (val) ? 'inline' : 'none';
        document.getElementById('file_add').style.display = display;
    }
    

    this.onFileSelected = function(obj) {
        
        var error_msg = false;        
        var filename = obj.value;
        
        if (filename == '') { // cancelled
            $(obj).next().hide();
            return;
        }
        
        var extension = filename.substr(filename.lastIndexOf('.') + 1);
    
        if (this.allowed_extension) {
            if ($.inArray(extension.toLowerCase(), this.allowed_extension) == -1) {
                error_msg = true;   
            }
        
        } else if(this.denied_extension) {
            if ($.inArray(extension.toLowerCase(), this.denied_extension) != -1) {
                error_msg = true;
            }            
        }
        
        if(!error_msg && this.getInputsNum() < this.max_allowed) {
            this.showAddLink(1);
            
        } else {
            this.showAddLink(0);
        }
    

        var parent = obj.parentNode;
        var elements = parent.childNodes;
	
        for (var j=0;j<elements.length;j++) {
	
            if(elements[j].className == 'file_delete') {
                elements[j].style.display = 'inline';
            }
            
            if(elements[j].className == 'file_error_msg') {
                if (error_msg) {
                    //elements[j].style.display = 'inline';
                    //elements[j].innerHTML = this.denied_extension_msg;
                    alert(this.denied_extension_msg);
                    
                    elements[j].id = 'file_error_msg_' + obj.id;
                    
                    $('#file_error_msg_' + obj.id).html(this.denied_extension_msg);
                    
                } else {
                    elements[j].style.display = 'none';
					
	                if(this.file_exists_handler) {
	                    elements[j].id = 'file_error_msg_' + obj.id;
			            xajax_isFileExists(filename, elements[j].id);
			        }
                }
            }
            			
        }

    }
    
        
    this.onFileExists = function(id, msg){
        document.getElementById(id).style.display = 'inline';
        document.getElementById(id).innerHTML = msg;
    }

}