(function($) {  
	var self = null;

	$.fn.liveUpdate = function(list) {
		return this.each(function() {
			new $.liveUpdate(this, list);
		});
	};
	
	$.liveUpdate = function (e, list) {
		this.field = $(e);
		this.list  = $('#' + list);

		if (this.list.length > 0) {
			this.init();
		}
	};
	
	$.liveUpdate.prototype = {
		init: function() {
			var self = this;
			
            this.cache = [];
	        this.rows = [];
            this.setupCache();
            this.cache_length = this.cache.length;
            
			this.field.keyup(function() { self.filter(); });
			self.filter();
		},
        
        filter: function() {
            if ($.trim(this.field.val()) == '') {
                this.emptyQuery();            
                return;
            }

            this.displayResults(this.getScores(this.field.val().toLowerCase()));
        },
		
		getScores: function(term) {
			var scores = [];
			for (var i=0; i < this.cache_length; i++) {
				var score = this.cache[i].score(term);
				if (score > 0) { scores.push([score, i]); }
			}
			return scores.sort(function(a, b) { return b[0] - a[0]; });
		}
	}
})(jQuery);