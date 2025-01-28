CKFinder.addPlugin('kbp_safe_rename', function(api) {
        
    var options = {
        label: 'Safe Rename',
        command: 'SafeRename',
        group: 'file2'
    };
    
    var sendRequest = function(api, postData) {
        api.connector.sendCommandPost('SafeRename', null, postData, function(xml) {
            if (xml.checkError()) {
                return;
            }
            
            var renamed = xml.selectSingleNode('Connector/SafeRename/@renamed');
            
            if (renamed.value == '1') {
                api.refreshOpenedFolder();
                
            } else {
                var message = xml.selectSingleNode('Connector/SafeRename/@message');
                api.openMsgDialog('', message.value);
            }
        });
    };
        
    
	api.addFileContextMenuOption(options, function(api) {
        var file = api.getSelectedFile();
        var name = file.name;
        
        var extension = name.split('.').pop();
        
        api.openInputDialog(api.lang.Rename, api.lang.FileRename, name, function(value) {
            if (file == value || value.length == 0) {
                return;
            }
            
            var postData = [];
                
            var folder = file.folder;
            var path = folder.getPath();
            
            postData['file[name]'] = file.name;
            postData['file[type]'] = folder.type;
            postData['file[folder]'] = path;
            postData['file[newName]'] = value;
        
            var newExtension = value.split('.').pop();
            if (extension != newExtension) {
                
                if (value.split('.').length > 1) {
                    var message = 'Are you sure you want to change the extension from ".' + extension + '" to ".' + newExtension + '"?';    
                    
                } else {
                    var message = 'Are you sure you want to remove the extension ".' + extension + '"?';
                }
                
                api.openConfirmDialog('', message, function() {
                    sendRequest(api, postData);
                });
                
                return;
            }
            
            sendRequest(api, postData);
        });

	});
    
});