(function($) {  
	
	$.liveUpdate.prototype.emptyQuery = function() {
    	this.list.find('div[id^=template]').show();
    };
		
    $.liveUpdate.prototype.setupCache = function() {
        var self = this;
        
        this.list.find('div[id^=template]').each(function(){
            self.cache.push($(this).find('.searchable').text().toLowerCase());
            self.rows.push($(this));
        });
    };
		
        
	$.liveUpdate.prototype.displayResults = function(scores) {
	    var self = this;
	    this.list.find('div[id^=template]').hide();
            
	    $.each(scores, function(i, score) {
        	self.rows[score[1]].show();
        });
            
    };
	
})(jQuery);