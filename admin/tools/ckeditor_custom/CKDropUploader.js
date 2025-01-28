var CKDropUploader = {

    upload_url: null,
    base64_encode: false,
    instance_name: 'body',
    
    init: function(active, instance_name) {
		if (active) {
            if (instance_name) {
                CKDropUploader.instance_name = CKDropUploader.instance_name;
            }
            
            var root_selector = '#cke_' + CKDropUploader.instance_name;

			$(root_selector + ' iframe').contents().find('html').bind('dragenter', CKDropUploader.noopHandler);
	        $(root_selector + ' iframe').contents().find('html').bind('dragover', CKDropUploader.noopHandler);
	        $(root_selector + ' iframe').contents().find('html').bind('dragexit', CKDropUploader.noopHandler);
	        $(root_selector + ' iframe').contents().find('html').bind('drop', CKDropUploader.drop);
	        
		    $('#progressbar').progressbar();	
		} 
    },
    
    setUploadUrl: function(url) {
        CKDropUploader.upload_url = url;
    },
    
    drop: function(evt) {
        if (typeof FileReader == 'undefined') {
            return false;
        }
        
        e = evt.originalEvent;
        
        e.stopPropagation();
        e.preventDefault();
          
        if (!e.dataTransfer.files) {
            return;
        }
          
        var files = e.dataTransfer.files;
        var count = files.length;
        
        if (count == 0) {
            return false;
        }
            
        // get first file
        var file = files[0];
        var reader = new FileReader();
             
        reader.readAsDataURL(file);
        
        $('#progressbar').show();
        
		if (CKDropUploader.base64_encode) { // insert base64 encoded image instead direct upload
			reader.onload = CKDropUploader.handleReaderLoad;
        	reader.onprogress = CKDropUploader.handleReaderProgress;
			
		} else { // upload		
			CKDropUploader.send(file, reader);	
		}
    },
    
    send: function(file, reader) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', CKDropUploader.upload_url, true);

        if(typeof FormData == 'function') {
        	var fData = new FormData();
        	fData.append('upfile', file);
        	xhr.send(fData);
			
			xhr.onreadystatechange = function () {
			    if (xhr.readyState == 4) {
					if (xhr.responseText.indexOf('Image:') == 0) { // successfully uploaded
						var img_src = xhr.responseText.substr(6);
						CKDropUploader.addImage(img_src);
							
					} else { // got an error
						$('#progressbar').hide();
			        	alert(xhr.responseText);						
					}
			    }
			}
            
        } else if(xhr.sendAsBinary) {
        	
            var fReader = new FileReader();
            
        	fReader.addEventListener('load', function(){
        		var boundaryString = 'boundarystrg',
        			boundary = '--' + boundaryString,
        			requestbody = '';
        
        		requestbody += boundary + '\r\n'
        				+ 'Content-Disposition: form-data; name="upfile"; filename="' + file.name + '"' + '\r\n'
        				+ 'Content-Type: application/octet-stream' + '\r\n'
        				+ '\r\n'
        				+ fReader.result
        				+ '\r\n'
        				+ boundary;
        
        		xhr.setRequestHeader("Content-type", 'multipart/form-data; boundary="' + boundaryString + '"');
        		xhr.setRequestHeader("Connection", "close");
        		xhr.setRequestHeader("Content-length", requestbody.length);
        		xhr.sendAsBinary(requestbody);
        	}, false);
        	fReader.readAsBinaryString(file);
        }
    },
    
    noopHandler: function(evt) {
        evt.stopPropagation();
        evt.preventDefault(); 
    },
    
    handleReaderLoad: function(evt) {
		CKDropUploader.addImage(evt.target.result);
    },
    
    handleReaderProgress: function(evt) {
        if (evt.lengthComputable) {
            var loaded = (evt.loaded / evt.total);
            $('#progressbar').progressbar({value: loaded * 100});
        }
    },
	
	addImage: function(src) {
        var editor = CKEDITOR.instances[CKDropUploader.instance_name];
        var img = '<img src="' + src + '" />';
        
        var html_to_insert = '<span>' + img + '</span>';
        var element = CKEDITOR.dom.element.createFromHtml(html_to_insert);
        editor.insertElement(element);
		
        $('#progressbar').progressbar({value: 100});
        $('#progressbar').delay(1000).fadeOut('slow');
	}
}