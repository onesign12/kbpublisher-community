(function($) {  
	
	$.liveUpdate.prototype.emptyQuery = function() {
    	this.list.find('div[id^=file]').show();  
    	$('#file_caption').show();
    };
		
    $.liveUpdate.prototype.setupCache = function(){
        var self = this;

        this.list.find('div[class=main]').find('div[id^=file]').each(function(){
            self.cache.push($(this).find('.searchable').text().toLowerCase());
            self.rows.push($(this));
        });
    };
		
        
    $.liveUpdate.prototype.displayResults = function(scores) {
        var self = this;
        this.list.find('div[class=main]').children('div').hide();
        
        $('#files_all').prop('checked', false);
        
        this.list.find('input.filesch').each(function() {
            if (this.checked) {
                $(this).parent().parent().parent().show();
                $('#file_caption').show();     
            }
        });   
        
        $.each(scores, function(i, score) {
            self.rows[score[1]].show();
            $('#file_caption').show();
        });        
    };
	
})(jQuery);