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
		init: function(list, categories, counter) {
			// quit if this function has already been called
			if (arguments.callee.done) return;

			// flag this function, don't do the same thing twice
			arguments.callee.done = true;
    
			var self = this;
            
            // the select id to insert options
            this.insertId = list;
            
            // counter id
            this.counterId = counter;
            
            // top filter phrase
            this.topfilter = 'top:';
            
            // create an option element for each category
            var objSelect =	document.getElementById(this.insertId);
            
            this.cnt = 0;
            
            for (var i = 0; i < categories.length; i ++) {
                var id = categories[i]['id'];
                var title = categories[i]['title'];
                                
                objSelect.options[objSelect.options.length] = new Option(title, id);
                
                this.cnt ++;
            }
            
            // counter
            $('#' + this.counterId).html(this.cnt);
            
            // make cache lists
			this.setupCache(categories);
            
			this.field.parents('form').submit(function() {
                return false;
            });
                
			this.field.keyup(function(event) {
                if (event.keyCode != 37 && event.keyCode != 39) {
                    self.filter(categories);
                }
            });
                
            self.filter(categories); // applying a filter (if exists)
		},
		
        
		filter: function(categories) {
            var objSelect = document.getElementById(this.insertId);
               
            // move scroll handler to the top
            objSelect.scrollTop = 0;
                
            // create an option element for each category
            if (($.trim(this.field.val()) == '') && (this.cache.length != 0)) {
                objSelect.options.length = 0;
                
                for (var i = 0; i < categories.length; i ++) {
                    var id = categories[i]['id'];
                    var title = categories[i]['title'];
                
                    objSelect.options[objSelect.options.length] = new Option(title, id);
                }
                
                // counter
                $('#' + this.counterId).html(this.cache.length);
                
            } else if($.trim(this.field.val()) == this.topfilter) { // top filter applied
                objSelect.options.length = 0;
                
                var tops = {};
                
                // get top categories
                for (var i = 0; i < categories.length; i ++) {
                    var id = categories[i]['id'];
                    var title = categories[i]['title'];
                    
                    if (!title.match(/->/ig)) {
                        tops[id] = title;
                    }
                }
                
                var count = 0;
                for (var key in tops) {
                    objSelect.options[objSelect.options.length] = new Option(tops[key], key);
                    count ++;
                }
                
                // counter
                $('#' + this.counterId).html(count);
                  
            } else {
                var scores = this.getScores(this.field.val().toLowerCase());
                this.displayResults(scores, categories);
            }
		},
		
       
        setupCache: function(categories) {
            var self = this;
            
            // cache of text nodes all options (for quicksilver)
            this.cache = [];
            
            // cache of values all options
            this.cacheValue = [];
            
            // list of jQuery options objects
            this.rows = [];
            
            this.list.children('option').each(function() {
                // create a cache
                self.cache.push($(this).text().toLowerCase());
                self.cacheValue.push(this.value);
                
                // create a list
                self.rows.push($(this));
            });

			this.cache_length = this.cache.length;
		},
		
        
		displayResults: function(scores, categories) {
            // clear all
            document.getElementById(this.insertId).options.length = 0;
            			
			// create a list with ids of matched categories
			var matchedIds = [];	 
			for (var i = 0; i < scores.length; i ++) {
                var category_id = parseInt(this.cacheValue[scores[i][1]]);
                matchedIds.push(category_id);
			}
                        
            if (matchedIds.length > 0) { // we got some matches
                var data = [];
                for (var j = 0; j < categories.length; j ++) {
                    
                    if ($.inArray(categories[j]['id'], matchedIds) != -1) { // matches
                        data.push(categories[j]);
                    }
                }
            
                var objSelect = document.getElementById(this.insertId);
                
                for (var i = 0; i < data.length; i++) {
                    var id = data[i]['id'];
                    var title = data[i]['title'];
                    
                    objSelect.options[objSelect.options.length] = new Option(title, id);
                }
            }
            
            // counter
            $('#' + this.counterId).html(matchedIds.length);
		},
                
        
		getScores: function(term) {
			var scores = [];
            
			for (var i = 0; i < this.cache_length; i ++) {
				var score = this.cache[i].score(term);
				if (score > 0) {
                    scores.push([score, i]);
                }
			}
			
            return scores.sort(function(a, b) {
                return b[0] - a[0];
            });
		}
	}
})(jQuery);