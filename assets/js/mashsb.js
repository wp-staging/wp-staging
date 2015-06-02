jQuery(document).ready( function($) {
    
    
    /*!------------------------------------------------------
 * jQuery nearest v1.0.3
 * http://github.com/jjenzz/jQuery.nearest
 * ------------------------------------------------------
 * Copyright (c) 2012 J. Smith (@jjenzz)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 */
(function($, d) {
  $.fn.nearest = function(selector) {
    var self, nearest, el, s, p,
        hasQsa = d.querySelectorAll;

    function update(el) {
      nearest = nearest ? nearest.add(el) : $(el);
    }

    this.each(function() {
      self = this;

      $.each(selector.split(','), function() {
        s = $.trim(this);

        if (!s.indexOf('#')) {
          // selector starts with an ID
          update((hasQsa ? d.querySelectorAll(s) : $(s)));
        } else {
          // is a class or tag selector
          // so need to traverse
          p = self.parentNode;
          while (p) {
            el = hasQsa ? p.querySelectorAll(s) : $(p).find(s);
            if (el.length) {
              update(el);
              break;
            }
            p = p.parentNode;
          }
        }
      });

    });

    return nearest || $();
  };
}(jQuery, document));
    
    
    
    /* Opens a new minus buttonwhen plus sign is clicked */
    /* Toogle function for more services */
    $( ".onoffswitch" ).click(function() {
        $('.onoffswitch').hide();
        $('.secondary-shares').show();
        $('.onoffswitch2').show();
        /*$( ".wpstg-buttons a" ).toggleClass( 'float-left');*/
    }); 
    $( ".onoffswitch2" ).click(function() {
        $('.onoffswitch').show();
        $('.secondary-shares').hide();         
    }); 

    /* Network sharer scripts */
    /* deactivate FB sharer when likeaftershare is enabled */
    if (typeof lashare_fb == "undefined" && typeof wpstg !== 'undefined') {
    $('.mashicon-facebook').click( function(mashfb) {
  
        winWidth = 520;
        winHeight = 550;
        var winTop = (screen.height / 2) - (winHeight / 2);
	var winLeft = (screen.width / 2) - (winWidth / 2);
        var url = $(this).attr('href');  
            //if (wpstg.singular === '1') {
                //window.open('http://www.facebook.com/sharer.php?s=100&u=' + wpstg.share_url + '&p[title]=' + wpstg.title + '&p[summary]=' + wpstg.desc + '&p[images][0]=' + wpstg.image, 'sharer', 'top=' + winTop + ',left=' + winLeft + ',toolbar=0,status=0,width=' + winWidth + ',height=' + winHeight);
            /*} else {
                console.log("not singular");
                window.open(url, 'sharer', 'top=' + winTop + ',left=' + winLeft + ',toolbar=0,status=0,width=' + winWidth + ',height=' + winHeight);
            }*/
        window.open(url, 'sharer', 'top=' + winTop + ',left=' + winLeft + ',toolbar=0,status=0,width=' + winWidth + ',height=' + winHeight);
        mashfb.preventDefault(mashfb);
        return false;
    });
    }
    if (typeof wpstg !== 'undefined') {
        shareurl = wpstg.share_url;
        if(typeof mashsu !== 'undefined'){
        mashsu.shorturl != 0 ? shareurl = mashsu.shorturl : shareurl = wpstg.share_url;
        }
    $('.mashicon-twitter').click( function(mashfw) {
        
        winWidth = 520;
        winHeight = 350;
        var winTop = (screen.height / 2) - (winHeight / 2);
	var winLeft = (screen.width / 2) - (winWidth / 2);
        var url = $(this).attr('href');
        
        // deprecated and removed because TW popup opens twice
       if (wpstg.twitter_popup === '1') {
            //console.log('wpstg.singular1: ' + wpstg.singular);
            //window.open('https://twitter.com/intent/tweet?text=' + wpstg.title + '&via=' + wpstg.hashtag + '&url=' + shareurl, 'sharer', 'top=' + winTop + ',left=' + winLeft + ',toolbar=0,status=0,width=' + winWidth + ',height=' + winHeight);
        /*}else{
            console.log('wpstg.singular2: ' + wpstg.singular);
            window.open(url, 'sharer', 'top=' + winTop + ',left=' + winLeft + ',toolbar=0,status=0,width=' + winWidth + ',height=' + winHeight);
        }*/
        window.open(url, 'sharer', 'top=' + winTop + ',left=' + winLeft + ',toolbar=0,status=0,width=' + winWidth + ',height=' + winHeight);
       } 
       mashfw.preventDefault(mashfw);
       return false;
        });
    }

    if (typeof wpstg !== 'undefined' && wpstg.subscribe === 'content'){
        /* Toogle container display:none */
        jQuery('.mashicon-subscribe').not('.trigger_active').nearest('.wpstg-toggle-container').hide();
        jQuery('.mashicon-subscribe').click( function() {
            var trig = jQuery(this);
            if ( trig.hasClass('trigger_active') ) {
                jQuery(trig).nearest('.wpstg-toggle-container').slideToggle('fast');
                trig.removeClass('trigger_active');
                //jQuery(".mashicon-subscribe").css({"padding-bottom":"10px"});
            } else {
                jQuery('.trigger_active').nearest('.wpstg-toggle-container').slideToggle('slow');
                jQuery('.trigger_active').removeClass('trigger_active');
                jQuery(trig).nearest('.wpstg-toggle-container').slideToggle('fast');
                trig.addClass('trigger_active');
                //jQuery(".mashicon-subscribe").css({"padding-bottom":"13px"});
            };
            return false;
        });
    } 
    
    if (typeof wpstg !== 'undefined' && wpstg.subscribe === 'link') {
        $('.mashicon-subscribe').click( function() {
        var href = wpstg.subscribe_url;
        $(this).attr("href", href);
        });
    };
   
 
/* Round the shares callback function
 * 
 * @param {type} value
 * @returns {String|@exp;value@call;toFixed}
 */    
function roundShares(value){
     if (typeof wpstg !== "undefined" && wpstg.round_shares == 1) {
             if (value > 1000000) {
                    //console.log("100000");
                    shares = Math.round((value / 1000000)*10)/10 + 'M';
                    return shares;
                    
                }
                if (value > 1000) {
                    //console.log("1000");
                    shares = Math.round((value / 1000)*10)/10 + 'k';
                    //wpstg.shares = wpstg.shares / 1000 + 'k';
                    //console.log("k: " + shares);
                    return shares;
                    
                 }  
     }
     /* zero decimals */
     return value.toFixed(0);
}

/* Count up script jquery-countTo
 * by mhuggins
 * 
 * Source: https://github.com/mhuggins/jquery-countTo
 */
(function ($) {
	$.fn.countTo = function (options) {
		options = options || {};

		return $(this).each(function () {
			// set options for current element
			var settings = $.extend({}, $.fn.countTo.defaults, {
				from:            $(this).data('from'),
				to:              $(this).data('to'),
				speed:           $(this).data('speed'),
				refreshInterval: $(this).data('refresh-interval'),
				decimals:        $(this).data('decimals')
			}, options);

			// how many times to update the value, and how much to increment the value on each update
			var loops = Math.ceil(settings.speed / settings.refreshInterval),
				increment = (settings.to - settings.from) / loops;

			// references & variables that will change with each update
			var self = this,
				$self = $(this),
				loopCount = 0,
				value = settings.from,
				data = $self.data('countTo') || {};

			$self.data('countTo', data);

			// if an existing interval can be found, clear it first
			if (data.interval) {
				clearInterval(data.interval);
			}
			data.interval = setInterval(updateTimer, settings.refreshInterval);

			// initialize the element with the starting value
			render(value);

			function updateTimer() {
				value += increment;
				loopCount++;

				render(value);

				if (typeof(settings.onUpdate) == 'function') {
					settings.onUpdate.call(self, value);
				}

				if (loopCount >= loops) {
					// remove the interval
					$self.removeData('countTo');
					clearInterval(data.interval);
					value = settings.to;

					if (typeof(settings.onComplete) == 'function') {
						settings.onComplete.call(self, value);
					}
				}
			}

			function render(value) {
				var formattedValue = settings.formatter.call(self, value, settings);
				$self.text(formattedValue);
			}
		});
	};

	$.fn.countTo.defaults = {
		from: 0,               // the number the element should start at
		to: 0,                 // the number the element should end at
		speed: 1000,           // how long it should take to count between the target numbers
		refreshInterval: 100,  // how often the element should be updated
		decimals: 0,           // the number of decimal places to show
		//formatter: formatter,  // handler for formatting the value before rendering
                formatter: roundShares,
		onUpdate: null,        // callback method for every time the element is updated
		onComplete: null       // callback method for when the element finishes updating
	};

	function formatter(value, settings) {
		return value.toFixed(settings.decimals);
	}
        

}(jQuery));

/* Start the counter
 * 
 */
if (typeof wpstg !== 'undefined' && wpstg.animate_shares == 1 && $('.wpstgcount').length) {
    $('.wpstgcount').countTo({from: 0, to: wpstg.shares, speed: 1000, refreshInterval: 100});
}


});

