$(document).ready(function() {
    TableListHandler.init();
    
    LeaveScreenMsg.form_id = 'aContentForm';
    if(document.getElementById(LeaveScreenMsg.form_id)) {
        LeaveScreenMsg.setDoCheck(1);
        LeaveScreenMsg.check();
    }
    
    $('ul.dropdown-menu').each(function() { // fix to enable a decent tab navigation in dropdowns
        $(this).find('li > a').last().blur(function() {
            $(document).click();
        });
    });
    
    
    $('._tooltip:not([title=""])').tooltipster({
        contentAsHTML: true,
        theme: ['tooltipster-kbp'],
        interactive: true,
        maxWidth: 400,
        side: ['top', 'left']
    });
       
    $('._tooltip_right:not([title=""])').tooltipster({
        contentAsHTML: true,
        theme: ['tooltipster-kbp'],
        interactive: true,
        maxWidth: 800,
        side: ['right']
    });   
            
    $('._tooltip_click').tooltipster({
        contentAsHTML: true,
        theme: ['tooltipster-kbp'],
        interactive: true,
        maxWidth: 400,
        trigger: 'click',
        side: ['top', 'left']
    });
    
    $('#public_area_link').tooltipster({
        contentAsHTML: true,
        theme: ['tooltipster-kbp'],
        interactive: true,
        maxWidth: 400,
        side: ['bottom']
    });
    
    
    $('body').on('kbpCategorySelected', {}, function(e, params) {
        $('#' + CategoriesInputHandler.text_input_id).val(params.text);
        $('#' + CategoriesInputHandler.text_input_id).attr('title', params.text);
        $('#' + CategoriesInputHandler.hidden_input_id).val(params.id).trigger('change');
        
        submitFilterForm($('#filter_tbl'));
    });
    
    $('body').on('kbpCategoryReset', {}, function(e, params) {
        submitFilterForm($('#filter_tbl'));
    });

    $('body').bind('tableSelectionChanged', {}, function(e, params) {
        var bulk_ch = $('input[type="checkbox"][id^="' + TableListHandler.checkboxPrefix + '"]').length;
        if(bulk_ch) {
            var checked = $('input[type="checkbox"][id^="' + TableListHandler.checkboxPrefix + '"]:checked').length;
            toogleBulkActions(checked); // bulk.js
        }
    });
    
    $('#filter_tbl select, #filter_tbl input[type="checkbox"]').change(function() {
        submitFilterForm(this);
    });
    
    $('.fix_top').on('show', function(event, dropdownData) {
        dropdownData.jqDropdown.css('top', 'auto');
    });
    
    // submenu
    $('div.jq-dropdown > div[id^=actions_submenu]').each(function() {
        var submenu = this;
        $(this).prev().children('li').each(function() {
            if (!$(this).hasClass('jq-dropdown-divider') && !$(this).attr('onmouseover')) {
                $(this).mouseover(function() { //console.log('asdas');
                    $(submenu).hide();
                })
            }
        });
    });

    // collapsed msg boxes
    $('.boxMsgDiv.collapsed,.boxMsgDiv.expanded > .title').click(function() {
        $(this).closest('.boxMsgDiv').each(function() {
            if($(this).is(":visible")) {
                $(this).toggleClass('expanded collapsed');
            } else {
                $(this).toggleClass('collapsed expanded');
            }
        });
    });
    
	// to hide on cache loading msg
	window.onpageshow = function(event) {
	    if (event.persisted) {
	        $('#loadingMessagePage').hide();
	    }
	};

    // Clipboard
    var clipboard = new ClipboardJS('.clipboard');

    clipboard.on('success', function(e) {
        setClipboardTooltip(e.trigger, 'Copied!');
        hideClipboardTooltip(e.trigger);
        e.clearSelection();
    });

    clipboard.on('error', function(e) {
        setClipboardTooltip(e.trigger, 'Failed!');
        hideClipboardTooltip(e.trigger);
    });
    
    // clearable
    $('#filter_tbl [name="filter[q]"].colorInput').trigger("input");
});


// clearable
// $(document).on("input", ".clearable", function(){
$(document).on("input", '#filter_tbl [name="filter[q]"].colorInput', function(){
    // $(this)[tog(this.value)]("x");
    console.log(this.value);
    $(this)[tog(this.value)]("x clearable");
}).on("mousemove", ".x", function( e ){
    $(this)[tog(this.offsetWidth-18 < e.clientX-this.getBoundingClientRect().left)]("onX");
}).on("touchstart click", ".onX", function( ev ){
    ev.preventDefault();
    $(this).removeClass("x onX").val("").change();
    // $('[name="do_search"]').trigger('click');
});