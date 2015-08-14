/*jQuery(document).ready(function ($) {

// Start easytabs()
	if ( $( ".wpstg-tabs" ).length ) {
		$('#tab_container').easytabs({
			animate:true
		});
	}

	if ( $( ".mashtab" ).length ) {
		$('#mashtabcontainer').easytabs({
			animate:true
		});
	}
});*/

/*
 * jQuery hashchange event - v1.3 - 7/21/2010
 * http://benalman.com/projects/jquery-hashchange-plugin/
 * 
 * Copyright (c) 2010 "Cowboy" Ben Alman
 * Dual licensed under the MIT and GPL licenses.
 * http://benalman.com/about/license/
 */
//(function($,e,b){var c="hashchange",h=document,f,g=$.event.special,i=h.documentMode,d="on"+c in e&&(i===b||i>7);function a(j){j=j||location.href;return"#"+j.replace(/^[^#]*#?(.*)$/,"$1")}$.fn[c]=function(j){return j?this.bind(c,j):this.trigger(c)};$.fn[c].delay=50;g[c]=$.extend(g[c],{setup:function(){if(d){return false}$(f.start)},teardown:function(){if(d){return false}$(f.stop)}});f=(function(){var j={},p,m=a(),k=function(q){return q},l=k,o=k;j.start=function(){p||n()};j.stop=function(){p&&clearTimeout(p);p=b};function n(){var r=a(),q=o(m);if(r!==m){l(m=r,q);$(e).trigger(c)}else{if(q!==m){location.href=location.href.replace(/#.*/,"")+q}}p=setTimeout(n,$.fn[c].delay)}$.browser.msie&&!d&&(function(){var q,r;j.start=function(){if(!q){r=$.fn[c].src;r=r&&r+a();q=$('<iframe tabindex="-1" title="empty"/>').hide().one("load",function(){r||l(a());n()}).attr("src",r||"javascript:0").insertAfter("body")[0].contentWindow;h.onpropertychange=function(){try{if(event.propertyName==="title"){q.document.title=h.title}}catch(s){}}}};j.stop=k;o=function(){return a(q.location.href)};l=function(v,s){var u=q.document,t=$.fn[c].domain;if(v!==s){u.title=h.title;u.open();t&&u.write('<script>document.domain="'+t+'"<\/script>');u.close();q.location.hash=v}}})();return j})()})(jQuery,this);

/*
 * jQuery EasyTabs plugin 3.2.0
 *
 * Copyright (c) 2010-2011 Steve Schwartz (JangoSteve)
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 * Date: Thu May 09 17:30:00 2013 -0500
 */
//(function(a){a.easytabs=function(j,e){var f=this,q=a(j),i={animate:true,panelActiveClass:"active",tabActiveClass:"active",defaultTab:"li:first-child",animationSpeed:"fast",tabs:"> ul > li",updateHash:true,cycle:false,collapsible:false,collapsedClass:"collapsed",collapsedByDefault:true,uiTabs:false,transitionIn:"fadeIn",transitionOut:"fadeOut",transitionInEasing:"swing",transitionOutEasing:"swing",transitionCollapse:"slideUp",transitionUncollapse:"slideDown",transitionCollapseEasing:"swing",transitionUncollapseEasing:"swing",containerClass:"",tabsClass:"",tabClass:"",panelClass:"",cache:true,event:"click",panelContext:q},h,l,v,m,d,t={fast:200,normal:400,slow:600},r;f.init=function(){f.settings=r=a.extend({},i,e);r.bind_str=r.event+".easytabs";if(r.uiTabs){r.tabActiveClass="ui-tabs-selected";r.containerClass="ui-tabs ui-widget ui-widget-content ui-corner-all";r.tabsClass="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all";r.tabClass="ui-state-default ui-corner-top";r.panelClass="ui-tabs-panel ui-widget-content ui-corner-bottom"}if(r.collapsible&&e.defaultTab!==undefined&&e.collpasedByDefault===undefined){r.collapsedByDefault=false}if(typeof(r.animationSpeed)==="string"){r.animationSpeed=t[r.animationSpeed]}a("a.anchor").remove().prependTo("body");q.data("easytabs",{});f.setTransitions();f.getTabs();b();g();w();n();c();q.attr("data-easytabs",true)};f.setTransitions=function(){v=(r.animate)?{show:r.transitionIn,hide:r.transitionOut,speed:r.animationSpeed,collapse:r.transitionCollapse,uncollapse:r.transitionUncollapse,halfSpeed:r.animationSpeed/2}:{show:"show",hide:"hide",speed:0,collapse:"hide",uncollapse:"show",halfSpeed:0}};f.getTabs=function(){var x;f.tabs=q.find(r.tabs),f.panels=a(),f.tabs.each(function(){var A=a(this),z=A.children("a"),y=A.children("a").data("target");A.data("easytabs",{});if(y!==undefined&&y!==null){A.data("easytabs").ajax=z.attr("href")}else{y=z.attr("href")}y=y.match(/#([^\?]+)/)[1];x=r.panelContext.find("#"+y);if(x.length){x.data("easytabs",{position:x.css("position"),visibility:x.css("visibility")});x.not(r.panelActiveClass).hide();f.panels=f.panels.add(x);A.data("easytabs").panel=x}else{f.tabs=f.tabs.not(A);if("console" in window){console.warn("Warning: tab without matching panel for selector '#"+y+"' removed from set")}}})};f.selectTab=function(x,C){var y=window.location,B=y.hash.match(/^[^\?]*/)[0],z=x.parent().data("easytabs").panel,A=x.parent().data("easytabs").ajax;if(r.collapsible&&!d&&(x.hasClass(r.tabActiveClass)||x.hasClass(r.collapsedClass))){f.toggleTabCollapse(x,z,A,C)}else{if(!x.hasClass(r.tabActiveClass)||!z.hasClass(r.panelActiveClass)){o(x,z,A,C)}else{if(!r.cache){o(x,z,A,C)}}}};f.toggleTabCollapse=function(x,y,z,A){f.panels.stop(true,true);if(u(q,"easytabs:before",[x,y,r])){f.tabs.filter("."+r.tabActiveClass).removeClass(r.tabActiveClass).children().removeClass(r.tabActiveClass);if(x.hasClass(r.collapsedClass)){if(z&&(!r.cache||!x.parent().data("easytabs").cached)){q.trigger("easytabs:ajax:beforeSend",[x,y]);y.load(z,function(C,B,D){x.parent().data("easytabs").cached=true;q.trigger("easytabs:ajax:complete",[x,y,C,B,D])})}x.parent().removeClass(r.collapsedClass).addClass(r.tabActiveClass).children().removeClass(r.collapsedClass).addClass(r.tabActiveClass);y.addClass(r.panelActiveClass)[v.uncollapse](v.speed,r.transitionUncollapseEasing,function(){q.trigger("easytabs:midTransition",[x,y,r]);if(typeof A=="function"){A()}})}else{x.addClass(r.collapsedClass).parent().addClass(r.collapsedClass);y.removeClass(r.panelActiveClass)[v.collapse](v.speed,r.transitionCollapseEasing,function(){q.trigger("easytabs:midTransition",[x,y,r]);if(typeof A=="function"){A()}})}}};f.matchTab=function(x){return f.tabs.find("[href='"+x+"'],[data-target='"+x+"']").first()};f.matchInPanel=function(x){return(x&&f.validId(x)?f.panels.filter(":has("+x+")").first():[])};f.validId=function(x){return x.substr(1).match(/^[A-Za-z]+[A-Za-z0-9\-_:\.].$/)};f.selectTabFromHashChange=function(){var y=window.location.hash.match(/^[^\?]*/)[0],x=f.matchTab(y),z;if(r.updateHash){if(x.length){d=true;f.selectTab(x)}else{z=f.matchInPanel(y);if(z.length){y="#"+z.attr("id");x=f.matchTab(y);d=true;f.selectTab(x)}else{if(!h.hasClass(r.tabActiveClass)&&!r.cycle){if(y===""||f.matchTab(m).length||q.closest(y).length){d=true;f.selectTab(l)}}}}}};f.cycleTabs=function(x){if(r.cycle){x=x%f.tabs.length;$tab=a(f.tabs[x]).children("a").first();d=true;f.selectTab($tab,function(){setTimeout(function(){f.cycleTabs(x+1)},r.cycle)})}};f.publicMethods={select:function(x){var y;if((y=f.tabs.filter(x)).length===0){if((y=f.tabs.find("a[href='"+x+"']")).length===0){if((y=f.tabs.find("a"+x)).length===0){if((y=f.tabs.find("[data-target='"+x+"']")).length===0){if((y=f.tabs.find("a[href$='"+x+"']")).length===0){a.error("Tab '"+x+"' does not exist in tab set")}}}}}else{y=y.children("a").first()}f.selectTab(y)}};var u=function(A,x,z){var y=a.Event(x);A.trigger(y,z);return y.result!==false};var b=function(){q.addClass(r.containerClass);f.tabs.parent().addClass(r.tabsClass);f.tabs.addClass(r.tabClass);f.panels.addClass(r.panelClass)};var g=function(){var y=window.location.hash.match(/^[^\?]*/)[0],x=f.matchTab(y).parent(),z;if(x.length===1){h=x;r.cycle=false}else{z=f.matchInPanel(y);if(z.length){y="#"+z.attr("id");h=f.matchTab(y).parent()}else{h=f.tabs.parent().find(r.defaultTab);if(h.length===0){a.error("The specified default tab ('"+r.defaultTab+"') could not be found in the tab set ('"+r.tabs+"') out of "+f.tabs.length+" tabs.")}}}l=h.children("a").first();p(x)};var p=function(z){var y,x;if(r.collapsible&&z.length===0&&r.collapsedByDefault){h.addClass(r.collapsedClass).children().addClass(r.collapsedClass)}else{y=a(h.data("easytabs").panel);x=h.data("easytabs").ajax;if(x&&(!r.cache||!h.data("easytabs").cached)){q.trigger("easytabs:ajax:beforeSend",[l,y]);y.load(x,function(B,A,C){h.data("easytabs").cached=true;q.trigger("easytabs:ajax:complete",[l,y,B,A,C])})}h.data("easytabs").panel.show().addClass(r.panelActiveClass);h.addClass(r.tabActiveClass).children().addClass(r.tabActiveClass)}q.trigger("easytabs:initialised",[l,y])};var w=function(){f.tabs.children("a").bind(r.bind_str,function(x){r.cycle=false;d=false;f.selectTab(a(this));x.preventDefault?x.preventDefault():x.returnValue=false})};var o=function(z,D,E,H){f.panels.stop(true,true);if(u(q,"easytabs:before",[z,D,r])){var A=f.panels.filter(":visible"),y=D.parent(),F,x,C,G,B=window.location.hash.match(/^[^\?]*/)[0];if(r.animate){F=s(D);x=A.length?k(A):0;C=F-x}m=B;G=function(){q.trigger("easytabs:midTransition",[z,D,r]);if(r.animate&&r.transitionIn=="fadeIn"){if(C<0){y.animate({height:y.height()+C},v.halfSpeed).css({"min-height":""})}}if(r.updateHash&&!d){window.location.hash="#"+D.attr("id")}else{d=false}D[v.show](v.speed,r.transitionInEasing,function(){y.css({height:"","min-height":""});q.trigger("easytabs:after",[z,D,r]);if(typeof H=="function"){H()}})};if(E&&(!r.cache||!z.parent().data("easytabs").cached)){q.trigger("easytabs:ajax:beforeSend",[z,D]);D.load(E,function(J,I,K){z.parent().data("easytabs").cached=true;q.trigger("easytabs:ajax:complete",[z,D,J,I,K])})}if(r.animate&&r.transitionOut=="fadeOut"){if(C>0){y.animate({height:(y.height()+C)},v.halfSpeed)}else{y.css({"min-height":y.height()})}}f.tabs.filter("."+r.tabActiveClass).removeClass(r.tabActiveClass).children().removeClass(r.tabActiveClass);f.tabs.filter("."+r.collapsedClass).removeClass(r.collapsedClass).children().removeClass(r.collapsedClass);z.parent().addClass(r.tabActiveClass).children().addClass(r.tabActiveClass);f.panels.filter("."+r.panelActiveClass).removeClass(r.panelActiveClass);D.addClass(r.panelActiveClass);if(A.length){A[v.hide](v.speed,r.transitionOutEasing,G)}else{D[v.uncollapse](v.speed,r.transitionUncollapseEasing,G)}}};var s=function(z){if(z.data("easytabs")&&z.data("easytabs").lastHeight){return z.data("easytabs").lastHeight}var B=z.css("display"),y,x;try{y=a("<div></div>",{position:"absolute",visibility:"hidden",overflow:"hidden"})}catch(A){y=a("<div></div>",{visibility:"hidden",overflow:"hidden"})}x=z.wrap(y).css({position:"relative",visibility:"hidden",display:"block"}).outerHeight();z.unwrap();z.css({position:z.data("easytabs").position,visibility:z.data("easytabs").visibility,display:B});z.data("easytabs").lastHeight=x;return x};var k=function(y){var x=y.outerHeight();if(y.data("easytabs")){y.data("easytabs").lastHeight=x}else{y.data("easytabs",{lastHeight:x})}return x};var n=function(){if(typeof a(window).hashchange==="function"){a(window).hashchange(function(){f.selectTabFromHashChange()})}else{if(a.address&&typeof a.address.change==="function"){a.address.change(function(){f.selectTabFromHashChange()})}}};var c=function(){var x;if(r.cycle){x=f.tabs.index(h);setTimeout(function(){f.cycleTabs(x+1)},r.cycle)}};f.init()};a.fn.easytabs=function(c){var b=arguments;return this.each(function(){var e=a(this),d=e.data("easytabs");if(undefined===d){d=new a.easytabs(this,c);e.data("easytabs",d)}if(d.publicMethods[c]){return d.publicMethods[c](Array.prototype.slice.call(b,1))}})}})(jQuery);


// Cloning workflow
(function ($) {
	$(document).ready(function () {
        
        // Ajax loading spinner    
        var admin_url = ajaxurl.replace('/admin-ajax.php', '');
        var spinner_url = admin_url + '/images/spinner';
        if (2 < window.devicePixelRatio) {
            spinner_url += '-2x';
        }
        spinner_url += '.gif';
        var ajax_spinner = '<img src="' + spinner_url + '" alt="" class="ajax-spinner general-spinner" />';
        
        var doing_plugin_compatibility_ajax = false;
        
        // Basic functions
        
        /**
         * Check if object is JSON valid
         * 
         * @param {string} str
         * @returns {Boolean}
         */
        function wpstgIsJsonObj(obj) {
            if (typeof obj == 'object')
            {
                return true;
            }
        }
        
        
        /** 
         * Check if given value is an integer 
         * This also casts strings as potential integers as well
         * 
         * */
        function wpstgIsInt(value) {
            return !isNaN(value) &&
                    parseInt(Number(value)) == value &&
                    !isNaN(parseInt(value, 10));
        }
        
                /**
                 * Do some checks first for the clone name. 
                 * Check the max length of string and if clone name already exists
                 */
		$('#wpstg-workflow').on('keyup', '#wpstg-new-clone-id', function () {
			var data = {
				action: 'wpstg_check_clone',
				cloneID: this.value
			};
			$.post(ajaxurl, data, function (resp, status, xhr) {
                                if (resp.status !== "fail") {
					$('#wpstg-new-clone-id').removeClass('wpstg-error-input');
					$('#wpstg-start-cloning').removeAttr('disabled');
					$('#wpstg-clone-id-error').text(resp.message);
				} else {
					$('#wpstg-new-clone-id').addClass('wpstg-error-input');
					$('#wpstg-start-cloning').attr('disabled', 'disabled');
                                        $('#wpstg-clone-id-error').text(resp.message);
				}
			}).fail(function(xhr) { // Will be executed when $.post() fails
                            wpstg_show_error_die('Fatal Error: This should not happen but is often caused by other plugins. Enable first the option "Optimizer" in WP Staging->Settings and try again. If this does not help, enable <a href="https://codex.wordpress.org/Debugging_in_WordPress" target="_blank">wordpress debug mode</a> to find out which plugin is causing this:<br> ' + xhr.status + ' ' + xhr.statusText);
                            console.log(xhr.statusText);
                        });
		});

                /**
                 * Check cloning path
                 */
		$('#wpstg-workflow').on('keyup', '#wpstg-clone-path', function () {
			var path = this.value;
			if (path.length < 1)  {
				$('#wpstg-clone-path').removeClass('wpstg-error-input');
				$('#wpstg-start-cloning').removeAttr('disabled');
				$('#wpstg-path-error').text('');
				return true;
			}

			var data = {
				action: 'wpstg_check_path',
				path: path
			};

			$.post(ajaxurl, data, function (resp, status, xhr) {
				if (resp) {
					$('#wpstg-clone-path').removeClass('wpstg-error-input');
					$('#wpstg-start-cloning').removeAttr('disabled');
					$('#wpstg-path-error').text('');
				} else {
					$('#wpstg-clone-path').addClass('wpstg-error-input');
					$('#wpstg-start-cloning').attr('disabled', 'disabled');
					$('#wpstg-path-error').text('Folder does not exist or is not writable!');
				}
			}).fail(function(xhr) { // Will be executed when $.post() fails
                            wpstg_show_error_die('Fatal Error: This should not happen but is most often caused by other plugins. Try first the option "Optimizer" in WP Staging->Settings and try again. If this does not help, enable <a href="https://codex.wordpress.org/Debugging_in_WordPress" target="_blank">wordpress debug mode</a> to find out which plugin is causing this:<br> ' + xhr.status + ' ' + xhr.statusText);
                            console.log(xhr.statusText);
                        });
		});

                /**
                 * Edit profile
                 */
		$('#wpstg-workflow').on('click', '.wpstg-edit-clone', function (e) {
			e.preventDefault();

			var data = {
				action: 'wpstg_edit_profile',
				clone: $(this).data('clone'),
				nonce: wpstg.nonce
			};
			$('#wpstg-workflow').load(ajaxurl, data);
		});

                /**
                 * Save profile
                 */
		$('#wpstg-workflow').on('click', '#wpstg-save-profile', function (e) {
			e.preventDefault();

			var data = {
				action: 'wpstg_save_profile',
				cloneID: $(this).data('clone'),
				nonce: wpstg.nonce,
				dbCredentials: {
					dbname: $('#wpstgdb-name').val(),
					dbuser: $('#wpstgdb-user').val(),
					dbpassword: $('#wpstgdb-password').val(),
					dbhost: $('#wpstgdb-host').val()
				}
			};
			wpstg_additional_data(data, false);
			$.post(ajaxurl, data, function (resp, status, xhr) {
				location.reload();
			}).fail(function(xhr) { // Will be executed when $.post() fails
                            wpstg_show_error_die('Fatal Error: This should not happen but is most often caused by other plugins. Try first the option "Optimizer" in WP Staging->Settings and try again. If this does not help, enable <a href="https://codex.wordpress.org/Debugging_in_WordPress" target="_blank">wordpress debug mode</a> to find out which plugin is causing this:<br> ' + xhr.status + ' ' + xhr.statusText);
                            console.log(xhr.statusText);
                        });
		});

                /**
                 * Next step
                 */
		/*$('#wpstg-workflow').on('click', '.wpstg-next-step-link', function (e) {
			e.preventDefault();
			if ($(this).attr('disabled'))
				return false;

			$('#wpstg-workflow').addClass('loading');
			var data = {
				action: $(this).data('action'),
				nonce: wpstg.nonce
			};
			if (data.action == 'wpstg_cloning') {
				data.cloneID = $('#wpstg-new-clone-id').val() || new Date().getTime();
				wpstg_additional_data(data, false);
			}

			$('#wpstg-workflow').load(ajaxurl, data, function () {
				$('#wpstg-workflow').removeClass('loading');
				$('.wpstg-current-step').removeClass('wpstg-current-step')
					.next('li').addClass('wpstg-current-step');
				if (data.action == 'wpstg_cloning') {
					clone_db();
				 }
			});
		});*/
                $('#wpstg-workflow').on('click', '.wpstg-next-step-link', function (e) {
			e.preventDefault();
			if ($(this).attr('disabled'))
				return false;

			$('#wpstg-workflow').addClass('loading');
			var data = {
				action: $(this).data('action'),
				nonce: wpstg.nonce
			};
			if (data.action == 'wpstg_cloning') {
				data.cloneID = $('#wpstg-new-clone-id').val() || new Date().getTime();
				wpstg_additional_data(data, false);
			}

			$('#wpstg-workflow').load(ajaxurl, data, function (response, status, xhr) {
                                if ( status == 'error') { //Unknown error
                                        console.log(xhr.status + ' ' + xhr.statusText);
                                        wpstg_show_error_die('Fatal Error: This should not happen but is most often caused by other plugins. Try first the option "Optimizer" in WP Staging->Settings and try again. If this does not help, enable <a href="https://codex.wordpress.org/Debugging_in_WordPress" target="_blank">wordpress debug mode</a> to find out which plugin is causing this: ' + xhr.status + ' ' + xhr.statusText);
                                }
				$('#wpstg-workflow').removeClass('loading');
				$('.wpstg-current-step').removeClass('wpstg-current-step')
					.next('li').addClass('wpstg-current-step');
				if (data.action == 'wpstg_cloning') {
					clone_db();
				 }
			});
		});

                /**
                 * Previous step
                 */
		$('#wpstg-workflow').on('click', '.wpstg-prev-step-link', function (e) {
			e.preventDefault();
			$('#wpstg-workflow').addClass('loading');
			var data = {
				action: 'wpstg_overview',
				nonce: wpstg.nonce
			};
			$('#wpstg-workflow').load(ajaxurl, data, function (response, status, xhr) {
                                if ( status == 'error') { //Unknown error
                                        console.log(xhr.status + ' ' + xhr.statusText);
                                        wpstg_show_error_die('Fatal Error: This should not happen but is most often caused by other plugins. Try first the option "Optimizer" in WP Staging->Settings and try again. If this does not help, enable <a href="https://codex.wordpress.org/Debugging_in_WordPress" target="_blank">wordpress debug mode</a> to find out which plugin is causing this: ' + xhr.status + ' ' + xhr.statusText);
                                }
				$('#wpstg-workflow').removeClass('loading');
				$('.wpstg-current-step').removeClass('wpstg-current-step')
					.prev('li').addClass('wpstg-current-step');
			});
		});

		var cloneID;
		function wpstg_additional_data(data, isRemoving) {
			data.uncheckedTables = [];
			$('.wpstg-db-table input:not(:checked)').each(function () {
				data.uncheckedTables.push(this.name);
			});
			data.excludedFolders = [];
			$('.wpstg-dir input:not(:checked)').each(function () {
				if (! $(this).parent('.wpstg-dir').parents('.wpstg-dir').children('.wpstg-expand-dirs').hasClass('disabled'))
					data.excludedFolders.push(this.name);
			});

			cloneID = data.cloneID.toString();
		}

                /*
                 * Start scanning process
                 */
		$('#wpstg-workflow').on('click', '.wpstg-execute-clone', function (e) {
			e.preventDefault();
			$('#wpstg-workflow').addClass('loading');
			var data = {
				action: 'wpstg_scanning',
				clone: $(this).data('clone'),
				nonce: wpstg.nonce
			};

			$('#wpstg-workflow').load(ajaxurl, data, function (response, status, xhr) {
                                if ( status == 'error') { //Unknown error
                                        console.log(xhr.status + ' ' + xhr.statusText);
                                        wpstg_show_error_die('Fatal Error: This should not happen. Please try again! <br> If restarting does not work <a href="https://wordpress.org/support/plugin/wp-staging" target="blank">get in contact with us</a> ' + xhr.status + ' ' + xhr.statusText);
                                }
				$('#wpstg-workflow').removeClass('loading');
				$('.wpstg-current-step').removeClass('wpstg-current-step')
					.next('li').addClass('wpstg-current-step');
			});
		});

		$('#wpstg-workflow').on('click', '.wpstg-remove-clone', function (e) {
			e.preventDefault();
			$('.wpstg-clone').removeClass('active');
                        $( '#wpstg-existing-clones' ).append( ajax_spinner );
			var data = {
				action: 'wpstg_preremove',
				cloneID: $(this).data('clone')
			};
			$('#wpstg-removing-clone').load(ajaxurl, data, function (resp, status, xhr){
                            if ( status == 'error') { //Unknown error
                                        console.log(xhr.status + ' ' + xhr.statusText);
                                        wpstg_show_error_die('Fatal Error: This should not happen but is most often caused by other plugins. Try first the option "Optimizer" in WP Staging->Settings and try again. If this does not help, enable <a href="https://codex.wordpress.org/Debugging_in_WordPress" target="_blank">wordpress debug mode</a> to find out which plugin is causing this:<br> ' + xhr.status + ' ' + xhr.statusText);
                                }
                            if (status === 'success'){
                                $('#wpstg-existing-clones').children("img").remove();
                            }
                        });
		});

		$('#wpstg-workflow').on('click', '#wpstg-cancel-removing', function (e) {
			e.preventDefault();
			$('.wpstg-clone').removeClass('active');
			$('#wpstg-removing-clone').html('');
		});

		$('#wpstg-workflow').on('click', '#wpstg-remove-clone', function (e) {
			e.preventDefault();
			$('#wpstg-removing-clone').addClass('loading');
			var cloneID = $(this).data('clone');
			var data = {
				action: 'wpstg_remove_clone',
				cloneID: cloneID,
				nonce: wpstg.nonce
			};

			wpstg_additional_data(data, true);
                        console.log('test');
			$.post(ajaxurl, data, function (resp, status, xhr) {
                            console.log(xhr.status + ' ' + xhr.statusText);
                                if ( status == 'error') { //Unknown error
                                        console.log(xhr.status + ' ' + xhr.statusText);
                                        wpstg_show_error_die('Fatal Error: This should not happen but is most often caused by other plugins. Try first the option "Optimizer" in WP Staging->Settings and try again. If this does not help, enable <a href="https://codex.wordpress.org/Debugging_in_WordPress" target="_blank">wordpress debug mode</a> to find out which plugin is causing this:<br> ' + xhr.status + ' ' + xhr.statusText);
                                }
				if (resp == 0) {
					$('#wpstg-removing-clone').html('');
					$('.wpstg-clone#' + cloneID).remove();
					$('#wpstg-removing-clone').removeClass('loading');
					var remaining_clones = $('.wpstg-clone');
					if (remaining_clones.length < 1)
						$('#wpstg-existing-clones h3').text('');
				}
			}).fail(function(xhr) { // Will be executed when $.post() fails
                            wpstg_show_error_die('Fatal Error: This should not happen but is most often caused by other plugins. Try first the option "Optimizer" in WP Staging->Settings and try again. If this does not help, enable <a href="https://codex.wordpress.org/Debugging_in_WordPress" target="_blank">wordpress debug mode</a> to find out which plugin is causing this:<br> ' + xhr.status + ' ' + xhr.statusText);
                            console.log(xhr.statusText);
                        });
		});
                
                /**
                 * Show error message and die()
                 * Writes error message into log file
                 * 
                 * @param {string} $error notice
                 * @returns void
                 */
                function wpstg_show_error_die(error) {
                    $('#wpstg-try-again').css('display', 'inline-block');
                    $('#wpstg-cancel-cloning').text('Reset');
                    $('#wpstg-cloning-result').text('Fail');
                    $('#wpstg-error-wrapper').show();
                    $('#wpstg-error-details').show();
                    $('#wpstg-error-details').html(error);
                    $('#wpstg-loader').hide();
                    isFinished = true; // die cloning process
                    console.log(error);
                    var data = {
                        action: 'wpstg_error_processing',
                        wpstg_error_msg: error
                    };
                    $.post(ajaxurl, data);
                }


                /**
                 * Clone database
                 * 
                 * @return void
                 */
		var isCanceled = false;
		function clone_db() {
                    $('#wpstg-loader').show();
			var data = {
				action: 'wpstg_clone_db',
				nonce: wpstg.nonce    
			};
			$.post(ajaxurl, data, function (resp, status, xhr) {
				if (isCanceled) {
					cancelCloning();
					return false;
				}
                                if ( status == "error" ) { //Unknown error
                                        wpstg_show_error_die('Fatal Error: This should not happen. Please try again! <br> If restarting does not work <a href="https://wordpress.org/support/plugin/wp-staging" target="blank">get in contact with us</a> ' . xhr.statusText);
				} else if (!wpstgIsJsonObj(resp)) { //Unknown error
                                        wpstg_show_error_die('Fatal Error: This should not happen. Please try again! <br> If restarting does not work <a href="https://wordpress.org/support/plugin/wp-staging" target="blank">get in contact with us</a> ' . xhr.statusText);
				} else if (resp.percent < 0) { //Fail
					wpstg_show_error_die('Fatal Error: This should never happen. Please try again! <br>If restarting does not work <a href="https://wordpress.org/support/plugin/wp-staging" target="blank">get in contact with us</a> ' . xhr.statusText);
				} else if(resp.percent < 1) { //Continue cloning
					var complete = Math.floor(resp.percent * 100) + '%';
					$('#wpstg-db-progress').text(complete).css('width', complete);
                                        $('#wpstg-error-wrapper').show();
                                        if (resp.message !== '')
                                            $('#wpstg-log-details').append(resp.message);
                                        
                                        wpstg_logscroll_bottom();
					clone_db();
				} else { //Success cloning
					$('#wpstg-db-progress').text('').css('width', '100%');
                                        $('#wpstg-log-details').append(resp.message + '<br>');
                                        wpstg_logscroll_bottom();
					copy_files();
				}
			})
                        .fail(function(xhr) { // Will be executed when $.post() fails
                            wpstg_show_error_die(xhr.statusText);
                            console.log('Fatal Error: This should not happen but is most often caused by other plugins. Try first the option "Optimizer" in WP Staging->Settings and try again. If this does not help, enable <a href="https://codex.wordpress.org/Debugging_in_WordPress" target="_blank">wordpress debug mode</a> to find out which plugin is causing this:<br> ' + xhr.status + ' ' + xhr.statusText);
                        });                                                           
		}
                



		function copy_files() {
			var data = {
				action: 'wpstg_copy_files',
				nonce: wpstg.nonce
			};
			$.post(ajaxurl, data, function(resp, status, xhr) {
				if (isCanceled) {
					cancelCloning();
					return false;
				}

				/*if (resp == 'not writable') {
					$('#wpstg-try-again').css('display', 'inline-block');
					$('#wpstg-cancel-cloning').text('Reset');
					$('#wpstg-cloning-result').text('This folder does not exist or is not writable');
					$('#wpstg-loader').hide();
					isFinished = true;
					return;
				}*/

				if (!wpstgIsJsonObj(resp)) { //Unknown error
					wpstg_show_error_die('Fatal Error: This should not happen. Please try again! <br> If restarting does not work <a href="https://wordpress.org/support/plugin/wp-staging" target="blank">get in contact with us</a> ');
				} else if (resp.percent < 0) { //Fail
					wpstg_show_error_die('Fatal Error: This should never happen. Please try again! <br>If restarting does not work <a href="https://wordpress.org/support/plugin/wp-staging" target="blank">get in contact with us</a> '. xhr.statusText);
				} else if (resp.percent < 1) { //Continue copying
					var complete = Math.floor(resp.percent * 100) + '%';
					$('#wpstg-files-progress').text(complete).css('width', complete);
					$('#wpstg-loader').show();
                                        if (resp.message !== '')
                                            $('#wpstg-log-details').append(resp.message + '<br>');
                                        wpstg_logscroll_bottom();
					copy_files();
				} else { //Success copying
					$('#wpstg-files-progress').text('').css('width', '100%');
                                        wpstg_logscroll_bottom();
					replace_links();
				}
			}).fail(function(xhr) { // Will be executed when $.post() fails
                            wpstg_show_error_die('Fatal Error: This should not happen but is most often caused by other plugins. Try first the option "Optimizer" in WP Staging->Settings and try again. If this does not help, enable <a href="https://codex.wordpress.org/Debugging_in_WordPress" target="_blank">wordpress debug mode</a> to find out which plugin is causing this:<br> ' + xhr.status + ' ' + xhr.statusText);
                            console.log(xhr.statusText);
                        });
		}

		var isFinished = false;
		function replace_links() {
			var data = {
				action: 'wpstg_replace_links',
				nonce: wpstg.nonce
			};
			$.post(ajaxurl, data, function(resp, status, xhr) {
				if (isCanceled) {
					cancelCloning();
					return false;
				}
                                
                                if (!wpstgIsJsonObj(resp)) { //Unknown error
					wpstg_show_error_die('Fatal Error code: 1001. This should not happen. Please try again! <br> If restarting does not work <a href="https://wordpress.org/support/plugin/wp-staging" target="blank">get in contact with us</a> ');
				} else if (resp.percent < 0) { //Fail
					wpstg_show_error_die('Fatal Error code: 1002. This should never happen. Please try again! <br>If restarting does not work <a href="https://wordpress.org/support/plugin/wp-staging" target="blank">get in contact with us</a> ' . xhr.statusText);
				} else if (resp.percent < 1) { //Continue replacing string
                                        var complete = Math.floor(resp.percent * 100) + '%';
                                        $('#wpstg-links-progress').text('').css('width', complete);
                                        $('#wpstg-loader').show();
                                        if (resp.message !== '')
                                            $('#wpstg-log-details').append(resp.message + '<br>');
                                        wpstg_logscroll_bottom();
                                        replace_links();
                                } else { //Success
					$('#wpstg-links-progress').text('').css('width', '100%');
					$('#wpstg-loader').hide();
                                        wpstg_logscroll_bottom();
					cloneID = cloneID.replace(/[^A-Za-z0-9]/g, '');
					//var cloneURL = $('#wpstg-clone-url').attr('href') + '/' + cloneID + '/wp-login.php';
                                        var redirectURL = $('#wpstg-clone-url').attr('href') + '/' + cloneID + '/';
					setTimeout(function () {
						//$('#wpstg-cloning-result').text('Done');
                                                $('#wpstg-finished-result').show();
						//$('#wpstg-clone-url').text('Visit staging site <span style="font-size: 10px;">(login with your admin credentials)</span>' . cloneID).attr('href', cloneURL + '?redirect_to=' + encodeURIComponent( redirectURL ) );
                                                $('#wpstg-clone-url').text('Visit staging site <span style="font-size: 10px;">(login with your admin credentials)</span>' . cloneID).attr('href', redirectURL );
                                                $('#wpstg_staging_name').text(cloneID);
                                                $('#wpstg-cancel-cloning').hide();
                                                $('#wpstg-home-link').css('display', 'inline-block');
						isFinished = true;
					}, 1200);
                                        if (resp.message !== '')
                                            $('#wpstg-log-details').append(resp.message + '<br>');
                                        wpstg_logscroll_bottom();
				}

			}).fail(function(xhr) { // Will be executed when $.post() fails
                            wpstg_show_error_die('Fatal Error: This should not happen but is sometimes caused by other plugins. Try first the option "Optimizer" in WP Staging->Settings and try again. If this does not help, enable <a href="https://codex.wordpress.org/Debugging_in_WordPress" target="_blank">wordpress debug mode</a> to find out which plugin is causing this:<br> ' + xhr.status + ' ' + xhr.statusText);
                            console.log(xhr.statusText);
                        });
		};

                /**
                 * Show error log button
                 */
		$('#wpstg-workflow').on('click', '#wpstg-show-log-button', function (e) {
			e.preventDefault();
			$('#wpstg-log-details').toggle();
                        $('html, body').animate({
                            scrollTop: $("#wpstg-log-details").offset().top
                        }, 400);
		});
                
                /**
                 * Scroll the log window to the bottom
                 * 
                 * @return void
                 */
                function wpstg_logscroll_bottom(){
                         var $mydiv = $('#wpstg-log-details');
                        $mydiv.scrollTop($mydiv[0].scrollHeight);
                }

                /**
                 * Cancel Cloning process button
                 */
		$('#wpstg-workflow').on('click', '#wpstg-cancel-cloning', function (e) {
			e.preventDefault();
			if (! confirm('Are you sure?'))
				return false;
			$('#wpstg-try-again, #wpstg-home-link').hide();
			$(this).attr('disabled', 'disabled');
			isCanceled = true;
			$('#wpstg-cloning-result').text('Please wait...this can take up to a minute');
                        $('#wpstg-loader').hide();
                        $('#wpstg-show-log-button').hide();
                        $( this ).parent().append( ajax_spinner );
			if (isFinished)
				cancelCloning();
		});
                
                /**
                 * Remove Clone button
                 */
		$('#wpstg-workflow').on('click', '#wpstg-remove-cloning', function (e) {
			e.preventDefault();
			if (! confirm('Are you sure you want to remove the clone site ?'))
				return false;
			$('#wpstg-try-again, #wpstg-home-link').hide();
			$(this).attr('disabled', 'disabled');
                        $('#wpstg-clone-url').attr('disabled', 'disabled');
			isCanceled = true;
			$('#wpstg-cloning-result').text('Please wait...this can take up to a minute');
                        $('#wpstg-loader').hide();
                        $('#wpstg-success-notice').hide();
                        $('#wpstg-show-log-button').hide();
                        $('#wpstg-log-details').hide();
                        $( this ).parent().append( ajax_spinner );
			if (isFinished)
				cancelCloning();
		});

                /**
                 * Try again button
                 */
		$('#wpstg-workflow').on('click', '#wpstg-try-again', function (e) {
			e.preventDefault();
                        console.log('test');
			$('#wpstg-workflow').addClass('loading');
			var data = {
				action: 'wpstg_scanning',
				nonce: wpstg.nonce
			};
			$('#wpstg-workflow').load(ajaxurl, data, function (response, status, xhr) {
                                if ( status == 'error') { //Unknown error
                                        console.log(xhr.status + ' ' + xhr.statusText);
                                        wpstg_show_error_die('Fatal Error: This should not happen but is most often caused by other plugins. Try first the option "Optimizer" in WP Staging->Settings and try again. If this does not help, enable <a href="https://codex.wordpress.org/Debugging_in_WordPress" target="_blank">wordpress debug mode</a> to find out which plugin is causing this:<br> ' + xhr.status + ' ' + xhr.statusText);
                                }
				$('#wpstg-workflow').removeClass('loading');
				$('.wpstg-current-step').removeClass('wpstg-current-step')
					.prev('li').addClass('wpstg-current-step');
			});
		});

                /**
                 * Reset button
                 */
		$('#wpstg-workflow').on('click', '#wpstg-reset-clone', function (e) {
			e.preventDefault();
			$(this).attr('disabled', 'disabled')
				.next('.wpstg-next-step-link').hide();
			$('#wpstg-loader').show();
			cloneID = $(this).data('clone');
			cancelCloning();
		});

                /**
                 * Cancel Cloning process
                 * 
                 * @returns void
                 */
		function cancelCloning() {
			var data = {
				action: 'wpstg_cancel_cloning',
				nonce: wpstg.nonce,
				cloneID: cloneID
			};
			$.post(ajaxurl, data, function (resp, status, xhr) {
				if (resp == 0) {
					$('#wpstg-cloning-result').text('');
					$('#wpstg-cancel-cloning').text('Success').addClass('success').removeAttr('disabled');
					location.reload();
				}
			}).fail(function(xhr) { // Will be executed when $.post() fails
                            wpstg_show_error_die(xhr.statusText);
                            console.log('Fatal Error: This should not happen but is most often caused by other plugins. Try first the option "Optimizer" in WP Staging->Settings and try again. If this does not help, enable <a href="https://codex.wordpress.org/Debugging_in_WordPress" target="_blank">wordpress debug mode</a> to find out which plugin is causing this:<br> ' + xhr.status + ' ' + xhr.statusText);
                        });
		}

		$('#wpstg-workflow').on('click', '#wpstg-home-link', function (e) {
			e.preventDefault();
			location.reload();
		});

		/**
                 * Tabs
                 */
		$('#wpstg-workflow').on('click', '.wpstg-tab-header', function (e) {
			e.preventDefault();
			var section = $(this).data('id');
			$(this).toggleClass('expand');
			$(section).slideToggle();
			if ($(this).hasClass('expand'))
				$(this).find('.wpstg-tab-triangle').html('&#9660;');
			else
				$(this).find('.wpstg-tab-triangle').html('&#9658;');

		});

		/**
                 * Directory structure
                 */
		$('#wpstg-workflow').on('click', '.wpstg-expand-dirs', function (e) {
			e.preventDefault();
			if (! $(this).hasClass('disabled'))
				$(this).siblings('.wpstg-subdir').slideToggle();
		});
                

		$('#wpstg-workflow').on('change', '.wpstg-check-dir', function () {
			var dir = $(this).parent('.wpstg-dir');
			if (this.checked) {
				dir.parents('.wpstg-dir').children('.wpstg-check-dir').attr('checked', 'checked');
				dir.find('.wpstg-expand-dirs').removeClass('disabled');
				dir.find('.wpstg-subdir .wpstg-check-dir').attr('checked', 'checked');
			} else {
				dir.find('.wpstg-dir .wpstg-check-dir').removeAttr('checked');
				dir.find('.wpstg-expand-dirs, .wpstg-check-subdirs').addClass('disabled');
				dir.find('.wpstg-check-subdirs').data('action', 'check').text('check');
				dir.children('.wpstg-subdir').slideUp();
			}
		});
                
                /**
                 * install must-use plugin
                 */
                
                $( '#plugin-compatibility' ).change( function( e ) {
			var install = '1';
			if ( $( this ).is( ':checked' ) ) {
				var answer = confirm( wpstg.mu_plugin_confirmation );

				if ( !answer ) {
					$( this ).prop( 'checked', false );
					return;
				}
			} else {
				install = '0';
			}

			$( '.plugin-compatibility-wrap' ).toggle();

			$( this ).parent().append( ajax_spinner );
			$( '#plugin-compatibility' ).attr( 'disabled', 'disabled' );
			$( '.plugin-compatibility' ).addClass( 'disabled' );

			$.ajax( {
				url: ajaxurl,
				type: 'POST',
				dataType: 'text',
				cache: false,
				data: {
					action: 'wpstg_muplugin_install',
					install: install
				},
				error: function( jqXHR, textStatus, errorThrown ) {
					alert( "Error: " + wpstg.plugin_compatibility_settings_problem + '\r\n\r\n' + wpstg.status + ' ' + jqXHR.status + ' ' + jqXHR.statusText + '\r\n\r\n' + wpstg.response + '\r\n' + jqXHR.responseText );
					$( '.ajax-spinner' ).remove();
					$( '#plugin-compatibility' ).removeAttr( 'disabled' );
					$( '.plugin-compatibility' ).removeClass( 'disabled' );
				},
				success: function( data ) {
					if ( '' !== $.trim( data ) ) {
						alert( data );
					} else {
						$( '.plugin-compatibility' ).append( '<span class="ajax-success-msg">' + wpstg.saved + '</span>' );
						$( '.ajax-success-msg' ).fadeOut( 2000, function() {
							$( this ).remove();
						} );
					}
					$( '.ajax-spinner' ).remove();
					$( '#plugin-compatibility' ).removeAttr( 'disabled' );
					$( '.plugin-compatibility' ).removeClass( 'disabled' );
				}
			} );

		});

		if ( $( '#plugin-compatibility' ).is( ':checked' ) ) {
			$( '.plugin-compatibility-wrap' ).show();
		}
                $( '.plugin-compatibility-save' ).click( function() {
			if ( doing_plugin_compatibility_ajax ) {
				return;
			}
			$( this ).addClass( 'disabled' );
			var select_element = $( '#selected-plugins' );
			$( select_element ).attr( 'disabled', 'disabled' );
                        var query_limit = $( '#wpstg_settings\\[wpstg_query_limit\\]' );
                        var batch_size = $( '#wpstg_settings\\[wpstg_batch_size\\]' );
                        var disable_admin_login = $( '#wpstg_settings\\[disable_admin_login\\]' );
                        var uninstall_on_delete = $( '#wpstg_settings\\[uninstall_on_delete\\]' );
                        disable_admin_login = $( disable_admin_login ).prop('checked') ? '1' : '0';
                        uninstall_on_delete = $( uninstall_on_delete ).prop('checked') ? '1' : '0';

			doing_plugin_compatibility_ajax = true;
			$( this ).after( '<img src="' + spinner_url + '" alt="" class="plugin-compatibility-spinner general-spinner" />' );

			$.ajax( {
				url: ajaxurl,
				type: 'POST',
				dataType: 'text',
				cache: false,
				data: {
					action: 'wpstg_disable_plugins',
					blacklist_plugins: $( select_element ).val(),
                                        query_limit: $( query_limit ).val(),
                                        batch_size: $( batch_size ).val(),
                                        disable_admin_login: disable_admin_login,
                                        uninstall_on_delete: uninstall_on_delete,
                                        
				},
				error: function( jqXHR, textStatus, errorThrown ) {
					alert( wpstg.blacklist_problem + '\r\n\r\n' + wpstg.status + ' ' + jqXHR.status + ' ' + jqXHR.statusText + '\r\n\r\n' + wpstg.response + '\r\n' + jqXHR.responseText );
					$( select_element ).removeAttr( 'disabled' );
					$( '.plugin-compatibility-save' ).removeClass( 'disabled' );
					doing_plugin_compatibility_ajax = false;
					$( '.plugin-compatibility-spinner' ).remove();
					$( '.plugin-compatibility-success-msg' ).show().fadeOut( 2000 );
				},
				success: function( data ) {
					if ( '' !== $.trim( data ) ) {
						alert( data );
					}
					$( select_element ).removeAttr( 'disabled' );
					$( '.plugin-compatibility-save' ).removeClass( 'disabled' );
					doing_plugin_compatibility_ajax = false;
					$( '.plugin-compatibility-spinner' ).remove();
					$( '.plugin-compatibility-success-msg' ).show().fadeOut( 2000 );
				}
			} );
		} );
                
                // select all tables
		$( '.multiselect-select-all' ).click( function() {
			var multiselect = $( this ).parents( '.select-wrap' ).children( '.multiselect' );
			$( 'option', multiselect ).attr( 'selected', 1 );
			$( multiselect ).focus().trigger( 'change' );
		} );

		// deselect all tables
		$( '.multiselect-deselect-all' ).click( function() {
			var multiselect = $( this ).parents( '.select-wrap' ).children( '.multiselect' );
			$( 'option', multiselect ).removeAttr( 'selected' );
			$( multiselect ).focus().trigger( 'change' );
		} );

		// invert table selection
		$( '.multiselect-invert-selection' ).click( function() {
			var multiselect = $( this ).parents( '.select-wrap' ).children( '.multiselect' );
			$( 'option', multiselect ).each( function() {
				$( this ).attr( 'selected', !$( this ).attr( 'selected' ) );
			} );
			$( multiselect ).focus().trigger( 'change' );
		} );
                
	});
})(jQuery);

// Load twitter button async
window.twttr = (function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0],
    t = window.twttr || {};
  if (d.getElementById(id)) return t;
  js = d.createElement(s);
  js.id = id;
  js.src = "https://platform.twitter.com/widgets.js";
  fjs.parentNode.insertBefore(js, fjs);
 
  t._e = [];
  t.ready = function(f) {
    t._e.push(f);
  };
 
  return t;
}(document, "script", "twitter-wjs"));
