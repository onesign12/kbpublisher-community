(function($) {
	
	$.liveUpdate.prototype.emptyQuery = function() {
        this.list.find('a').removeAttr('style');
        this.list.find('img').removeAttr('style');
    };
    	
    $.liveUpdate.prototype.setupCache = function(){
        var self = this;
        
        this.list.find('a').each(function(){
            self.cache.push($(this).text().toLowerCase());
            self.rows.push($(this));
        });
    };	
        
    $.liveUpdate.prototype.displayResults = function(scores) {
        var self = this;
        
        this.list.find('span.map_show_all').hide();
        this.list.find('div.children div').show();
        
        this.list.find('a').css('color', '#dddddd');
        this.list.find('img').css('opacity', '0.4').css('filter', 'alpha(opacity=40)');
        
        $.each(scores, function(i, score) {
            self.rows[score[1]].css('color', 'black');
            self.rows[score[1]].parent().parent().prev().find('img').removeAttr('style');
        });
    };
	
})(jQuery);