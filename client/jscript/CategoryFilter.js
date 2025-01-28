// default all options
var all;
// top categories
var top_cats = [];
// child options
var opts = [];
// top categories ids
var ids = [];
// top categories names
var names = [];
// top categories selection
var selections = [];

$(document).ready(function() {
    cacheSelect();
});

function cacheSelect() {
    // first select element selection
    var selFirst = document.getElementById('cf');
	
	// filter is not loaded
	if (!selFirst) {
		return;
	}
	
    var index = (selFirst.selectedIndex == -1) ? 0 : selFirst.selectedIndex;

    var r_value = selFirst.options[index].value;
        
    // second select element selection
    var selSec = document.getElementById('c');
    var nums = selSec.options.length;
    var selection = [];

    for(i = 0;i < nums;i ++) {
        if (selSec.options[i].selected == true) {
			selection.push(selSec.options[i].value);
		}
    }

    all = $('#c').html();
    top_cats = [];
    
    $('#c [id^=top]').each(function() {
        var outer = '<option id="' + this.id + '" value="' + this.value + '">' + this.innerHTML + '</option>';
        top_cats.push(outer);
    });
        
    
    $('#c [id^=top]').each(function(i){
        var id = $(this).attr('value');
      
        child = '';
        $('#c [id^=_' + id + ']').each(function() {
            var outer = '<option id="' + this.id + '" value="' + this.value + '">' + this.innerHTML + '</option>';
            child += outer;
        });

        opts[id] = child;
        
        // get top categories id
		ids[id] = $(this).attr('id');
        // get top categories names
        names[id] = this.innerHTML;
        // get top categories status
        selections[id] = this.selected;
    });
    
     
    rebuild(r_value);
        
    // set selection
    selSec = document.getElementById('c');
    
    for (i = 0; i < selSec.options.length; i++) {
        if ($.inArray(selSec.options[i].value, selection) !== -1) {
            selSec.options[i].selected = true;
        }
    }   

    $('#cf').bind('change', function(){
        var value = $(this).val();
        rebuild(value);
        
        var index = document.getElementById('c').selectedIndex;
        
        if (index == -1 && value != 'top' && value != 'all') {
            document.getElementById('c').selectedIndex = 0;
        }
    });
}


function rebuild(value) {
    if (value == 'all') {
        var out_html = all;
	} else if (value == 'top') {
        var out_html = top_cats.join('');       
    } else {
        var status = (selections[value]) ? 'selected' : '';
        var out_html = '<option id="'+ ids[value] + 
                       '" value="' + value +
                       '"' + status + '>'+ names[value] + '</option>' + 
                       opts[value];
    }
            
    $('#c').html(out_html).focus();
}


function setDefault() {
    $('#c').html(all);
    document.getElementById('c').selectedIndex = -1;
    document.getElementById('cf').selectedIndex = 0;
}