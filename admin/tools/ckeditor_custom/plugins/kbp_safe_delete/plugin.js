CKFinder.addPlugin('kbp_safe_delete', function(api) {
        
    var options = {
        label: 'Safe Delete',
        command: 'SafeDelete',
        group: 'file3'
    };
    
	api.addFileContextMenuOption(options, function(api) {
        var file = api.getSelectedFile();
        
        api.openConfirmDialog('', 'Are you sure?', function() {
            var postData = [];
            
            var folder = file.folder;
            var path = folder.getPath();
            
            postData['file[name]'] = file.name;
            postData['file[type]'] = folder.type;
            postData['file[folder]'] = path;
            
            api.connector.sendCommandPost('SafeDelete', null, postData, function(xml) {
                if (xml.checkError()) {
                    return;
                }
                
                var deleted = xml.selectSingleNode('Connector/SafeDelete/@deleted');
                
                if (deleted.value == '1') {
                    api.refreshOpenedFolder();
                    
                } else {
                    var message = xml.selectSingleNode('Connector/SafeDelete/@message');
                    api.openMsgDialog('', message.value);    
                }
    		});
        });
	});
    
});