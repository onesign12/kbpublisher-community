var NotificationManager = {
    
    add: function() {
        var msg_num = $('#notification_block span').attr('data-badge');
        msg_num ++;
        
        NotificationManager.updateBadge(msg_num);
    },
    
    
    remove: function(id, msg_num) {
        msg_num = parseInt(msg_num);
        
        if ($('#notification_' + id).length) {
            $('#notification_' + id).fadeOut(700, function() {
                NotificationManager.updateBadge(msg_num);
            });
        } else {
            NotificationManager.updateBadge(msg_num);
        }
        
        // parent list open
        if($('#row_' + id).length) {
            $('#row_' + id + ' *').css("font-weight", "normal");
        }
        
    },
    
    
    updateBadge: function(msg_num) {
    	var block = $('#notification_block').add('#notification_block2');
        var title = document.title;
        title = title.replace(/\([0-9]+\) /g, '');
        
        if (msg_num) {
            block.find('span').show();
            block.find('span').attr('data-badge', msg_num);
            $('#notification_empty').hide();
            
            block.find('path').css('opacity', '1');
            document.title = '(' + msg_num + ') ' + title;
            
        } else {
            block.find('span').hide();
            $('#notification_empty').show();
            
            block.find('path').css('opacity', '0.5');
            document.title = title;
        }
    },
    
    
    updateList: function(html) {
    	var html = $('<div style="display: none;">' + html + '</div>');
        /*html.appendTo('#notifications div').slideToggle(600, function() { // a copy for the right panel
            var block = $('#notifications').html();
            $('#notifications2').html(block);
        });*/
       
        html.appendTo('#notifications div').show(0, function () {
            var block = $('#notifications').html();
            $('#notifications2').html(block);
        });
    },
    
    
    connect: function(url) {
        if ('WebSocket' in window) {
            var socket = new WebSocket('ws://' + url);
            
            socket.onmessage = function (event) {
                console.log('onmessage');
                
                var data = JSON.parse(event.data);
                console.log(data);
                
                if (data.type == 'error') {
                    $.growl.error({message: data.text, duration: 100000});
                    
                } else {
                    $.growl({title: data.title, message: data.text, duration: 100000});
                }
                
                NotificationManager.add();
            }
            
            socket.onopen = function () {
            }
            
            socket.onclose = function () {
                alert('onclose');
                //NotificationManager.connect();
            }
            
        } else {
            // do something if there is no websockets support
        }
    }
    
}