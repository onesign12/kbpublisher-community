/*
 * Easy Z modal
 * doc: http://markusslima.github.io/easy-z-modal/
 * github: https://github.com/markusslima/easy-z-modal
 *
 * Copyright (c) 2015 Markus Vinicius da Silva Lima
 * Version 0.1.3
 * Licensed under the MIT license.
 */
(function ($) {
    "use strict";

    $(window).on('keyup', function (event) {
        if (event.keyCode === 27) {
            $('.ezmodal').each(function () {
                if ($(this).ezmodal('isVisible')) {
                    if ($(this).data('ezmodal').options.escClose) {
                        $(this).ezmodal('hide');
                    }
                }
            });
        }
    });

    $(document).on('click', '.ezmodal', function () {
        if ($(this).data('ezmodal').options.closable) {
            $(this).ezmodal('hide');
        }
    });

    $(document).on('click', '.ezmodal .ezmodal-container', function (event) {
        event.stopPropagation();
    });

    $(document).on('click', '[data-dismiss="ezmodal"]', function () {
        $(this).parent().parent().parent().ezmodal('hide');
    });

    $(document).on('click', '[ezmodal-target]', function () {
        $($(this).attr('ezmodal-target')).ezmodal('show');
    });

    var EZmodal = function (element, options) {
            this.options = options;
            this.$element = $(element);
        },
        old;

    EZmodal.prototype = {
        show: function () {
            this.$element.show();
            this.options.onShow();
            $('body').css('overflow', 'hidden');
            if (this.$element.find('.ezmodal-container').find('input, textarea, select, button, a').length === 0) {
                this.$element.find('.ezmodal-footer').find('button, a').first().focus();
            } else {
                this.$element.find('.ezmodal-container').find('input, textarea, select, button, a').first().focus();
            }
        },
        
        hide: function () {
            this.$element.hide();
            this.options.onClose();
            $('body').css('overflow', 'inherit');
        },

        isVisible: function () {
            return this.$element.css('display') === 'block' ? true : false;
        },
        
        constructor: function () {
            var width = this.options.width,
                container = this.$element.find('.ezmodal-container'),
                footer = this.$element.find('.ezmodal-footer'),
                numElem = container.find('input, textarea, select, button, a').length;
                
            if (this.options.autoOpen) {
                this.show();
            }
            
            if (Number(this.options.width)) {
                container.css({
                    'width': width + 'px'
                });
            } else {
                switch (width) {
                case 'small':
                    container.css({'width': '40%'});
                    break;
                case 'medium':
                    container.css({'width': '75%'});
                    break;
                case 'full':
                    container.css({'width': '95%'});
                    break;
                }
            }

            // Control tab navigator
            container.find('input, textarea, select, button, a')
                .each(function (i) {
                    $(this).attr({'tabindex': i + 1});
                });

            footer.find('button, a')
                .each(function () {
                    numElem++;
                    $(this).attr({'tabindex': numElem});
                })
                .last()
                .blur(function () {
                    if (numElem === 0) {
                        this.$element.footer.find('button, a').first().focus();
                    } else {
                        container.find('input, textarea, select, button, a').first().focus();
                    }
                });
        }
    };

    old = $.fn.ezmodal;

    $.fn.ezmodal = function (option, value) {
        var get = '',
            element = this.each(function () {
                var $this = $(this),
                    data = $this.data('ezmodal'),
                    options = $.extend({}, $.fn.ezmodal.defaults, option, typeof option === 'object' && option);

                if (!data) {
                    $this.data('ezmodal', (data = new EZmodal(this, options)));
                    data.constructor();
                }

                if (typeof option === 'string') {
                    get = data[option](value);
                }
            });

        if (typeof get !== 'undefined') {
            return get;
        } else {
            return element;
        }
    };

    $.fn.ezmodal.defaults = {
        'width': 500,
        'closable': false, // March 16, 2021 eleontev: closable, escClose = false
        'escClose': false,
        'autoOpen': false,
        'onShow': function () {},
        'onClose': function () {}
    };

    $.fn.ezmodal.noConflict = function () {
        $.fn.ezmodal = old;
        return this;
    };
    
    $(function () {
        $('.ezmodal').each(function () {
            var $this = $(this),
                options = {
                    'width' : $this.attr('ezmodal-width'),
                    'escClose' : $this.attr('ezmodal-escclose') === 'false' ? false : true,
                    'closable' : $this.attr('ezmodal-closable') === 'false' ? false : true,
                    'autoOpen' : $this.attr('ezmodal-autoopen') === 'true' ? true : false
                };
            $this.ezmodal(options);
        });
    });
})(window.jQuery);