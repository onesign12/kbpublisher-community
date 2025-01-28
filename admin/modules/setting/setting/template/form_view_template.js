<script src="../client/jscript/jquery/jquery_select.js"></script>
<script>
	
	var myOptions = {{myOptionsJson}};
			
	$(document).ready(function() {
		var format_value = document.getElementById('view_format').value;
		populateSelect(format_value);
        
        toggleSubscriptionTimePicker($('#subscribe_news_interval').val(), 'news');
        toggleSubscriptionTimePicker($('#subscribe_entry_interval').val(), 'entry');
        
        var hash = window.location.hash.substr(1);
        if (hash == 'anchor_page_to_load') {
            $('#template_page_to_load input[type="button"]').click();
        }
	});	
	
	function populateSelect(view_key) {
        
        var options_range = ['template', 'menu_type'];
        for (var i in options_range) {
            var vars = myOptions[options_range[i]][view_key];
            $('#view_' + options_range[i]).empty();
            
            if (vars.length == 0) {
                $('#view_' + options_range[i]).attr('disabled', true);
            } else {
                $('#view_' + options_range[i]).attr('disabled', false);
                
                for (var j = 0; j < vars.length; j ++) {
                    $('#view_' + options_range[i]).addOption(vars[j].val, vars[j].text, vars[j].s);
                }
            }
        }
        
        if (view_key == 'fixed') {
            $('#view_header, #container_width').prop('checked', true).prop('disabled', true);
            
        } else {
            $('#view_header, #container_width').prop('disabled', false);
        }
	}
    
    function toggleSubscriptionTimePicker(value, type) {
        
        const fields = ['_time', '_weekday', '_day'];
        const range_to_fields = {
            daily: ['_time'], 
            weekly: ['_time', '_weekday'],
            monthly: ['_time', '_day']
        }
        
        for (const element of fields) {
            $('#template_subscribe_' + type + element).find('select').prop('disabled', true);
            $('#template_subscribe_' + type + element).find('input').prop('disabled', true);
            $('#template_subscribe_' + type + element).addClass('auto_hidden');
        }
        
        for (const element of range_to_fields[value]) {
            $('#template_subscribe_' + type + element).find('select').prop('disabled', false);
            $('#template_subscribe_' + type + element).find('input').prop('disabled', false);
            $('#template_subscribe_' + type + element).removeClass('auto_hidden');
        }
    }
    
</script>
{field}