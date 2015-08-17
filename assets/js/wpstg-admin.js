/*
 * WP Staging
 * Main functions
 * 
 * @author René Hermenau
 * @author url https://wp-staging.com
 * @date 17.08.2015
 * 
 * @scince 0.9
 */

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
                

                
                // Show large files
                $('#wpstg-workflow').on('click', '#wpstg-show-large-files', function (e) {
                    e.preventDefault();
                //$('#wpstg-show-large-files').on('click', ) click(function(){
                    $('#wpstg-large-files').toggle();
                });
                        
            
                
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
