(function($) {  
	var self = null;
 	
	$.fn.liveUpdate = function(list, categories, counter) {	
		return this.each(function() {
			new $.liveUpdate(this, list, categories, counter);
		});
	};
	
    
	$.liveUpdate = function (e, list, categories, counter) {
		this.field = $(e);
		this.list  = $('#' + list);
		if (this.list.length > 0) {
			this.init(list, categories, counter);
		}
	};
	
    
	$.liveUpdate.prototype = {
        // Init-constructor
		init: function(list, categories, counter) {
			// Quit if this function has already been called
			if (arguments.callee.done) return;

			// Flag this function, don't do the same thing twice
			arguments.callee.done = true;
    
			var self = this;
            // Select Id to insert options
            this.insertId = list;
            // Counter Id
            this.counterId = counter;
            // Top filter word
            this.topfilter = 'top:';
            
            // Create options elements for each categories
            var objSelect =	document.getElementById(this.insertId);
            
            this.cnt = 0;
            for (var key in categories) {
                objSelect.options[objSelect.options.length] = new Option(categories[key], key);
                this.cnt++;
            }
            
            // Counter
            document.getElementById(this.counterId).innerHTML = this.cnt;
            
            // Make a cache lists
			this.setupCache(categories);
			this.field.parents('form').submit(function() { return false; });
			this.field.keyup(function(event) { if (event.keyCode != 37 && event.keyCode != 39) self.filter(categories); });
      self.filter(categories);
		},
		
        
		filter: function(categories) {
            var objSelect = document.getElementById(this.insertId);
               
            // Move scroll handler to the top
            objSelect.scrollTop = 0;
                
            // Create options elements for each categories
            if (($.trim(this.field.val()) == '') && (this.cache.length != 0)) {
                objSelect.options.length = 0;
                
                for (var key in categories) {
                    objSelect.options[objSelect.options.length] = new Option(categories[key], key);
                }
                document.getElementById(this.counterId).innerHTML = this.cache.length;                
                return;
            } else if($.trim(this.field.val()) == this.topfilter) {
                objSelect.options.length = 0;
                
                
                
                var tops = {};
                
                // Get top categories
                for (category in categories) {
                    if (!categories[category].match(/->/ig)) {
                        tops[category] = categories[category];
                    }
                }
                
                var count = 0;
                for (var key in tops) {
                    objSelect.options[objSelect.options.length] = new Option(tops[key], key);
                    count ++;
                }
                
                document.getElementById(this.counterId).innerHTML = count;
                  
            } else {
                this.displayResults(this.getScores(this.field.val().toLowerCase()), categories);
            }
		},
		
       
        setupCache: function(categories) {
            var self = this;
            // Cash of text nodes all options
            this.cache = [];
            // Cash of values all options
            this.cacheValue = [];
            // List of JQuery options objects
            this.rows = [];
            
            this.list.children('option').each(function() {
                // Create cash
                self.cache.push(this.innerHTML.toLowerCase());
                self.cacheValue.push(this.value);
                // Create list
                self.rows.push($(this));
            });

			this.cache_length = this.cache.length;
		},
		
        
		displayResults: function(scores, categories) {
			var self = this;
            
            // Clear all options
            document.getElementById(this.insertId).options.length = 0;
            			
			// Create list with indexes of valid id's
			var validList = [];	 
			for (i=0;i<scores.length;i++) {
				validList.push(scores[i][1]);
			}
            
            if (validList.length == 0) {
                document.getElementById(this.counterId).innerHTML = "0";
            }
            
            else {
                var objSelect = document.getElementById(this.insertId);
                
                // Create options elements for each categories
                for (i = 0; i < validList.length; i++) {
                    objSelect.options[objSelect.options.length] = new Option(categories[this.cacheValue[validList[i]]], this.cacheValue[validList[i]]);
                }
                
                // Counter
                document.getElementById(this.counterId).innerHTML = " " + (validList.length);
                
                // Move scroll handler to the top
                objSelect.scrollTop = 0;                
            }
            
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