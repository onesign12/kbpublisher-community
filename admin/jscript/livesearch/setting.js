(function($) {
	
	$.liveUpdate.prototype.emptyQuery = function() {
    	this.list.find('div[id^=template]:not(.auto_hidden)').show();
    	this.list.find('div[id^=group]').show();
    	this.list.find('div[id^=delim]').show();
    	this.list.find('div[id^=submit]').show();

    	$('#buttons').show();
    };
		
    $.liveUpdate.prototype.setupCache = function(){
        var self = this;
        
        this.list.find('div[class=main]').find('div[id^=template]').each(function(){
            self.cache.push($(this).find('.searchable').text().toLowerCase());
            self.rows.push($(this));
        });
        
        this.list.find('div[class=main]').find('b.searchable').each(function(){
            self.cache.push($(this).text().toLowerCase());
            self.rows.push($(this));
        });
    };
		
        
	$.liveUpdate.prototype.displayResults = function(scores) {
	    var self = this;
	    this.list.find('div[class=main]').children('div').hide();
            
	    this.list.find('div[id^=delim]').hide();
	    this.list.find('div[id^=submit]').hide();

	    if (scores.length == 0) {
        	$('#buttons').hide();
	    } else {
	        $('#buttons').show();
	    }
            
	    $.each(scores, function(i, score) {
            // group title matches, show all
            if (!self.rows[score[1]].attr('id')) {
                self.rows[score[1]].parent().parent().parent().parent().children('div:not(.auto_hidden)').show();
                
            } else {
                if (!self.rows[score[1]].hasClass('auto_hidden')) {
                    self.rows[score[1]].show();
                }
            
        	    gr_id = self.rows[score[1]].parent().children('div[id^=gr]').attr('id');
        	    $('#' + gr_id).show();
        	    $('#delim_' + gr_id).show();
        	    $('#submit_' + gr_id).show(); 
            }
        });
            
    };
	
})(jQuery);