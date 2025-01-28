$(document).ready(function() {
   
    // Fira Sans font
    $('body').addClass('font_not_loaded');
    var font = new FontFaceObserver('Fira Sans');
    font.load().then(function () {
        $('body').removeClass('font_not_loaded');
    });
    
    Foundation.addToJquery($);
    $(document).foundation();
    
     // fix to enable a decent tab navigation in dropdowns
    $('ul.dropdown-menu').each(function() {
        $(this).find('li > a').last().blur(function() {
            $(document).click();
        });
    });
    
    
    // icons in article float panel
    // $('.round_icon, .icon_title > span').mouseover(function(e) {
    $('.round_icon, .icon_title > span').on('mouseover', function(e) {
        if ($(e.target).hasClass('icon')) {
            el = $(e.target).parent().prev();
        } else {
            var el = $(e.target);
        }
        
        var color = el.attr('data-color');
        if (!color) {
            el.addClass("round_icon_hover");
        } else {
            el.css('background-color', color);
        }
    });
    
    // $('.round_icon, .icon_title > span').mouseout(function(e) {
    $('.round_icon, .icon_title > span').on('mouseout', function(e) {
        if ($(e.target).hasClass('icon')) {
            el = $(e.target).parent().prev();
        } else {
            var el = $(e.target);
        }
        
        $(el).css('background-color', '');
        $(el).removeClass("round_icon_hover");
    });
    
    
    // tooltipster
    $('._tooltip:not([title=""])').tooltipster({
        contentAsHTML: true,
        theme: ['tooltipster-kbp'],
        interactive: true,
        maxWidth: 300
    });
    
    $('._tooltip_left:not([title=""])').tooltipster({
        contentAsHTML: true,
        theme: ['tooltipster-kbp'],
        interactive: true,
        maxWidth: 300,
        side: 'left'
    });
    
    $('._tooltip_click').tooltipster({
        contentAsHTML: true,
        theme: ['tooltipster-kbp'],
        interactive: true,
        maxWidth: 300,
        trigger: 'click'
    });
    
    $('._tooltip_custom_glossary').tooltipster({
        contentAsHTML: true,
        interactive: true,
        trigger: 'custom',
        maxWidth: 300,
        functionReady: function(instance, helper) {
            $(helper.tooltip).on('mouseenter', function() {
                clearTimeout(tolerance);
            });
            
            $(helper.tooltip).on('mouseleave', function() {
                closeTooltip(_tooltip_el);
            });
        }
    });
    
    $('._tooltip_user').not('#body_raw ._tooltip_user').tooltipster({
        contentAsHTML: true,
        interactive: true,
        functionBefore: function(instance){
            var content = instance.content();
            content = content.replace(/(?:\r\n|\r|\n)/g, '<br />');
            instance.content(content);
        }
    });
    
	// reset text/hide loading, etc in safari 
    window.onpageshow = function(event) {
        if (event.persisted) {
			$('#loading_spinner').hide();
			
			if($('button[data-title]').length) {
                $('button[data-title]').each(function() {
                    $(this).text($(this).attr('data-title'));
                });
			}
        }
    };
    
    
    // hide empty page design blocks only if take the whole row
    $('div.small-12.medium-12.columns').each(function () {
        if ($(this).children('.hidenPadeDesignBlock').length == 1) {
            $(this).hide(); 
        }
    });


    // glossary and link behavior 
    $('a > span.glossaryItem').on("click", function (e) {
        "use strict"; //satisfy the code inspectors
        var link = $(this); //preselect the link
        if (link.hasClass('ghover')) {
            return true;
        } else {
            link.addClass("ghover").css('cursor', 'pointer');
            $('a > span.glossaryItem').not(this).removeClass("ghover");
            e.preventDefault();
            return false;
        }
    });
    
    
    setWidthCookie();
    // $(window).resize(setWidthCookie);
    $(window).on('resize', setWidthCookie);
});


function setWidthCookie() {
	createCookie('kb_window_width_', $(window).width());
}


function setFdownloadLinks(msg_open, msg_download, or_msg) {
    var fd = $('a[data-flink]'); 
    $(fd).tooltipster({
        content: '',
        interactive: true,
        theme: ['tooltipster-kbp_text'],
        contentCloning: true,
        arrow: false,
        side: 'right'
    });
    
    
    var msg = ($(fd).data('fparam')) ? msg_open : msg_download;
    var ftarget = ($(fd).data('fparam')) ? '_blank' : '_self';
    $.each(fd, function() {
         $(this).tooltipster('content', $('<span>'+or_msg+'</span> <a href="'+$(this).data('flink')+'" target="'+ftarget+'"> '+msg+'</a>'));
    });
}