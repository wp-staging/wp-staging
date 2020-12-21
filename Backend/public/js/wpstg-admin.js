"use strict";

/**
 * Show warning during cloning or push process when closing tab or browser, or changing page 
 * @param {beforeunload} event
 * @return {null}
 */
var wpstgWarnIfClose = function (event) {
    // Only some browsers shows the message below, most say something like "Changes you made may not be saved" (Chrome) or "You have unsaved changes. Exit?"
    event.returnValue = 'You MUST leave this window open while cloning/pushing. Please wait...';
    return null;
};

var WPStaging = (function ($) {
    var that = {
            isCancelled: false,
            isFinished: false,
            getLogs: false,
            time: 1,
            executionTime: false,
            progressBar: 0
        },
        cache = {elements: []},
        timeout, ajaxSpinner;

    /**
     * Get / Set Cache for Selector
     * @param {String} selector
     * @returns {*}
     */
    cache.get = function (selector) {
        // It is already cached!
        if ($.inArray(selector, cache.elements) !== -1) {
            return cache.elements[selector];
        }

        // Create cache and return
        cache.elements[selector] = jQuery(selector);

        return cache.elements[selector];
    };

    /**
     * Refreshes given cache
     * @param {String} selector
     */
    cache.refresh = function (selector) {
        selector.elements[selector] = jQuery(selector);
    };

    /**
     * Show and Log Error Message
     * @param {String} message
     */
    var showError = function (message) {
        cache.get("#wpstg-try-again").css("display", "inline-block");
        cache.get("#wpstg-cancel-cloning").text("Reset");
        cache.get("#wpstg-resume-cloning").show();
        cache.get("#wpstg-error-wrapper").show();
        cache.get("#wpstg-error-details").show().html(message);
        cache.get("#wpstg-removing-clone").removeClass("loading");
        cache.get(".wpstg-loader").hide();
        $('.wpstg--modal--process--generic-problem').show().html(message);
    };


    /**
     *
     * @param obj
     * @returns {boolean}
     */
    function isEmpty(obj) {
        for(var prop in obj) {
            if(obj.hasOwnProperty(prop))
                return false;
        }

        return true;
    }

    /**
     *
     * @param response the error object
     * @param prependMessage Overwrite default error message at beginning
     * @param appendMessage Overwrite default error message at end
     * @returns void
     */

    var showAjaxFatalError = function (response, prependMessage, appendMessage) {
        prependMessage = prependMessage ? prependMessage + '<br/><br/>' : 'Something went wrong! <br/><br/>';
        appendMessage = appendMessage ? appendMessage + '<br/><br/>' : '<br/><br/>Please try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.';

        if (response === false) {
            showError(prependMessage + ' Error: No response.' + appendMessage);
            window.removeEventListener('beforeunload', wpstgWarnIfClose);
            return;
        }

        if (typeof response.error !== 'undefined' && response.error) {
            console.error(response.message);
            showError(prependMessage + ' Error: ' + response.message + appendMessage);
            window.removeEventListener('beforeunload', wpstgWarnIfClose);
            return;
        }
    }

    /**
     *
     * @param response
     * @returns {{ok}|*}
     */
    var handleFetchErrors = function (response) {
        if (!response.ok) {
            showError('Error: ' + response.status + ' - ' + response.statusText + '. Please try again or contact support.');
        }
        return response;
    }

    /** Hide and reset previous thrown visible errors */
    var resetErrors = function () {
        cache.get("#wpstg-error-details").hide().html('');
    }

    var slugify = function (url) {
        return url.toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\s+/g, '-')
            .replace(/&/g, '-and-')
            .replace(/[^a-z0-9\-]/g, '')
            .replace(/-+/g, '-')
            .replace(/^-*/, '')
            .replace(/-*$/, '')
            ;
    };


    /**
     * Common Elements
     */
    var elements = function () {
        var $workFlow = cache.get("#wpstg-workflow"),
            isAllChecked = true,
            urlSpinner = ajaxurl.replace("/admin-ajax.php", '') + "/images/spinner",
            timer;

        if (2 < window.devicePixelRatio) {
            urlSpinner += "-2x";
        }

        urlSpinner += ".gif";

        ajaxSpinner = "<img src=''" + urlSpinner + "' alt='' class='ajax-spinner general-spinner' />";

        var getBaseValues = function () {
            var path = $('#wpstg-use-target-dir').data('base-path');
            var uri = $('#wpstg-use-target-hostname').data('base-uri');
            return {
                path
            };
        };

        $workFlow
            // Check / Un-check All Database Tables New
            .on("click", ".wpstg-button-unselect", function (e) {
                e.preventDefault();

                if (false === isAllChecked) {
                    console.log('true');
                    cache.get("#wpstg_select_tables_cloning .wpstg-db-table").prop("selected", "selected");
                    cache.get(".wpstg-button-unselect").text("Unselect All");
                    cache.get(".wpstg-db-table-checkboxes").prop("checked", true);
                    isAllChecked = true;
                } else {
                    console.log('false');
                    cache.get("#wpstg_select_tables_cloning .wpstg-db-table").prop("selected", false);
                    cache.get(".wpstg-button-unselect").text("Select All");
                    cache.get(".wpstg-db-table-checkboxes").prop("checked", false);
                    isAllChecked = false;
                }
            })

            /**
             * Select tables with certain tbl prefix | NEW
             * @param obj e
             * @returns {undefined}
             */
            .on("click", ".wpstg-button-select", function (e) {
                e.preventDefault();
                $("#wpstg_select_tables_cloning .wpstg-db-table").each(function () {
                    if (wpstg.isMultisite == 1) {
                        if ($(this).attr('name').match("^" + wpstg.tblprefix + "([^0-9])_*")) {
                            $(this).prop("selected", "selected");
                        } else {
                            $(this).prop("selected", false);
                        }
                    }

                    if (wpstg.isMultisite == 0) {
                        if ($(this).attr('name').match("^" + wpstg.tblprefix)) {
                            $(this).prop("selected", "selected");
                        } else {
                            $(this).prop("selected", false);
                        }
                    }
                })
            })
            // Expand Directories
            .on("click", ".wpstg-expand-dirs", function (e) {
                e.preventDefault();

                var $this = $(this);

                if (!$this.hasClass("disabled")) {
                    $this.siblings(".wpstg-subdir").slideToggle();
                }
            })
            // When a directory checkbox is Selected
            .on("change", "input.wpstg-check-dir", function () {
                var $directory = $(this).parent(".wpstg-dir");

                if (this.checked) {
                    $directory.parents(".wpstg-dir").children(".wpstg-check-dir").prop("checked", true);
                    $directory.find(".wpstg-expand-dirs").removeClass("disabled");
                    $directory.find(".wpstg-subdir .wpstg-check-dir").prop("checked", true);
                } else {
                    $directory.find(".wpstg-dir .wpstg-check-dir").prop("checked", false);
                    $directory.find(".wpstg-expand-dirs, .wpstg-check-subdirs").addClass("disabled");
                    $directory.find(".wpstg-check-subdirs").data("action", "check").text("check");
                }
            })
            // When a directory name is Selected
            .on("change", "href.wpstg-check-dir", function () {
                var $directory = $(this).parent(".wpstg-dir");

                if (this.checked) {
                    $directory.parents(".wpstg-dir").children(".wpstg-check-dir").prop("checked", true);
                    $directory.find(".wpstg-expand-dirs").removeClass("disabled");
                    $directory.find(".wpstg-subdir .wpstg-check-dir").prop("checked", true);
                } else {
                    $directory.find(".wpstg-dir .wpstg-check-dir").prop("checked", false);
                    $directory.find(".wpstg-expand-dirs, .wpstg-check-subdirs").addClass("disabled");
                    $directory.find(".wpstg-check-subdirs").data("action", "check").text("check");
                }
            })
            // Check the max length of the clone name and if the clone name already exists
            .on("keyup", "#wpstg-new-clone-id", function () {

                // Hide previous errors
                document.getElementById('wpstg-error-details').style.display = "none";

                // This request was already sent, clear it up!
                if ("number" === typeof (timer)) {
                    clearInterval(timer);
                }

                var cloneID = this.value;

                timer = setTimeout(
                    function () {
                        ajax(
                            {
                                action: "wpstg_check_clone",
                                accessToken: wpstg.accessToken,
                                nonce: wpstg.nonce,
                                cloneID: cloneID
                            },
                            function (response) {
                                if (response.status === "success") {
                                    cache.get("#wpstg-new-clone-id").removeClass("wpstg-error-input");
                                    cache.get("#wpstg-start-cloning").removeAttr("disabled");
                                    cache.get("#wpstg-clone-id-error").text('').hide();
                                } else {
                                    cache.get("#wpstg-new-clone-id").addClass("wpstg-error-input");
                                    cache.get("#wpstg-start-cloning").prop("disabled", true);
                                    cache.get("#wpstg-clone-id-error").text(response.message).show();
                                }
                            }
                        );
                    },
                    500
                );
            })
            // Restart cloning process
            .on("click", "#wpstg-start-cloning", function () {
                resetErrors();
                that.isCancelled = false;
                that.getLogs = false;
                that.progressBar = 0;
            })
            .on('input', '#wpstg-new-clone-id', function () {
                if ($('#wpstg-clone-directory').length < 1) {
                    return;
                }

                var slug = slugify(this.value);
                var $targetDir = $('#wpstg-use-target-dir');
                var $targetUri = $('#wpstg-use-target-hostname');
                var path = $targetDir.data('base-path');
                var uri = $targetUri.data('base-uri');

                if (path) {
                    path = path.replace(/\/+$/g, '') + '/' + slug + '/';
                }

                if (uri) {
                    uri = uri.replace(/\/+$/g, '') + '/' + slug;
                }


                $('.wpstg-use-target-dir--value').text(path);
                $('.wpstg-use-target-hostname--value').text(uri);

                $targetDir.attr('data-path', path);
                $targetUri.attr('data-uri', uri);
                $('#wpstg_clone_dir').attr('placeholder', path);
                $('#wpstg_clone_hostname').attr('placeholder', uri);
            })
            .on('input', '#wpstg_clone_hostname', function () {
                if ($(this).val() === "" || validateTargetHost()) {
                    $('#wpstg_clone_hostname_error').remove();
                    return;
                }
                if (!validateTargetHost() && !$('#wpstg_clone_hostname_error').length) {
                    $('#wpstg-clone-directory tr:last-of-type').after('<tr><td>&nbsp;</td><td><p id="wpstg_clone_hostname_error" style="color: red;">&nbsp;Invalid host name. Please provide it in a format like http://example.com</p></td></tr>');
                }
            })
        ;

        cloneActions();
    };

    /* @returns {boolean} */
    var validateTargetHost = function () {
        var the_domain = $('#wpstg_clone_hostname').val();

        if (the_domain === "") {
            return true;
        }

        var reg = /^http(s)?:\/\/.*$/;
        if (reg.test(the_domain) === false) {
            return false;
        }
        return true;
    };

    /**
     * Clone actions
     */
    var cloneActions = function () {
        var $workFlow = cache.get("#wpstg-workflow");

        $workFlow
            // Cancel cloning
            .on("click", "#wpstg-cancel-cloning", function () {
                if (!confirm("Are you sure you want to cancel cloning process?")) {
                    return false;
                }

                var $this = $(this);

                $("#wpstg-try-again, #wpstg-home-link").hide();
                $this.prop("disabled", true);

                that.isCancelled = true;
                that.progressBar = 0;

                $("#wpstg-processing-status").text("Please wait...this can take up a while.");
                $(".wpstg-loader, #wpstg-show-log-button").hide();

                $this.parent().append(ajaxSpinner);

                cancelCloning();
            })
            // Resume cloning
            .on("click", "#wpstg-resume-cloning", function () {
                resetErrors();
                var $this = $(this);

                $("#wpstg-try-again, #wpstg-home-link").hide();

                that.isCancelled = false;

                $("#wpstg-processing-status").text("Try to resume cloning process...");
                $("#wpstg-error-details").hide();
                $(".wpstg-loader").show();

                $this.parent().append(ajaxSpinner);

                that.startCloning();
            })
            // Cancel update cloning
            .on("click", "#wpstg-cancel-cloning-update", function () {
                resetErrors();

                var $this = $(this);

                $("#wpstg-try-again, #wpstg-home-link").hide();
                $this.prop("disabled", true);

                that.isCancelled = true;

                $("#wpstg-cloning-result").text("Please wait...this can take up a while.");
                $(".wpstg-loader, #wpstg-show-log-button").hide();

                $this.parent().append(ajaxSpinner);

                cancelCloningUpdate();
            })
            // Restart cloning
            .on("click", "#wpstg-restart-cloning", function () {
                resetErrors();

                var $this = $(this);

                $("#wpstg-try-again, #wpstg-home-link").hide();
                $this.prop("disabled", true);

                that.isCancelled = true;

                $("#wpstg-cloning-result").text("Please wait...this can take up a while.");
                $(".wpstg-loader, #wpstg-show-log-button").hide();

                $this.parent().append(ajaxSpinner);

                restart();
            })
            // Delete clone - confirmation
            .on("click", ".wpstg-remove-clone[data-clone]", function (e) {
                resetErrors();
                e.preventDefault();

                var $existingClones = cache.get("#wpstg-existing-clones");

                $workFlow.removeClass('active');

                cache.get(".wpstg-loader").show();

                ajax(
                    {
                        action: "wpstg_confirm_delete_clone",
                        accessToken: wpstg.accessToken,
                        nonce: wpstg.nonce,
                        clone: $(this).data("clone")
                    },
                    function (response) {
                        cache.get("#wpstg-removing-clone").html(response);

                        $existingClones.children("img").remove();

                        cache.get(".wpstg-loader").hide();

                        $('html, body').animate({
                            //This logic is meant to be a "scrollBottom"
                            scrollTop:  $("#wpstg-remove-clone").offset().top - $(window).height() +
                                $("#wpstg-remove-clone").height() + 50
                        }, 100);
                    },
                    "HTML"
                );
            })
            // Delete clone - confirmed
            .on("click", "#wpstg-remove-clone", function (e) {
                resetErrors();
                e.preventDefault();

                cache.get("#wpstg-removing-clone").addClass("loading");

                cache.get(".wpstg-loader").show();

                deleteClone($(this).data("clone"));
            })
            // Cancel deleting clone
            .on("click", "#wpstg-cancel-removing", function (e) {
                e.preventDefault();
                $(".wpstg-clone").removeClass("active");
                cache.get("#wpstg-removing-clone").html('');
            })
            // Update
            .on("click", ".wpstg-execute-clone", function (e) {
                e.preventDefault();

                var clone = $(this).data("clone");

                $workFlow.addClass("loading");

                ajax(
                    {
                        action: "wpstg_scanning",
                        clone: clone,
                        accessToken: wpstg.accessToken,
                        nonce: wpstg.nonce
                    },
                    function (response) {
                        if (response.length < 1) {
                            showError(
                                "Something went wrong! Error: No response.  Please try the <a href='https://wp-staging.com/docs/wp-staging-settings-for-small-servers/' target='_blank'>WP Staging Small Server Settings</a> or submit an error report and contact us."
                            );
                        }

                        $workFlow.removeClass("loading").html(response);
                        // register check disk space function for clone update process.
                        checkDiskSpace();
                        that.switchStep(2);
                    },
                    "HTML"
                );
            });
    };

    /**
     * Ajax Requests
     * @param Object data
     * @param Function callback
     * @param string dataType
     * @param bool showErrors
     * @param int tryCount
     * @param float incrementRatio
     */
    var ajax = function (data, callback, dataType, showErrors, tryCount, incrementRatio = null) {
        if ("undefined" === typeof (dataType)) {
            dataType = "json";
        }

        if (false !== showErrors) {
            showErrors = true;
        }

        tryCount = "undefined" === typeof (tryCount) ? 0 : tryCount;

        var retryLimit = 10;

        var retryTimeout = 10000 * tryCount;

        incrementRatio = parseInt(incrementRatio);
        if (!isNaN(incrementRatio)) {
            retryTimeout *= incrementRatio;
        }

        $.ajax({
            url: ajaxurl + '?action=wpstg_processing&_=' + (Date.now() / 1000),
            type: "POST",
            dataType: dataType,
            cache: false,
            data: data,
            error: function (xhr, textStatus, errorThrown) {
                console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus);

                //try again after 10 seconds
                tryCount++;
                if (tryCount <= retryLimit) {
                    setTimeout(function () {
                        ajax(data, callback, dataType, showErrors, tryCount, incrementRatio);
                        return;
                    }, retryTimeout);

                } else {
                    var errorCode = "undefined" === typeof (xhr.status) ? "Unknown" : xhr.status;
                    showError(
                        "Fatal Error:  " + errorCode + " Please try the <a href='https://wp-staging.com/docs/wp-staging-settings-for-small-servers/' target='_blank'>WP Staging Small Server Settings</a> or submit an error report and contact us."
                    );
                }


            },
            success: function (data) {
                if ("function" === typeof (callback)) {
                    callback(data);
                }
            },
            statusCode: {
                404: function () {
                    if (tryCount >= retryLimit) {
                        showError("Error 404 - Can't find ajax request URL! Please try the <a href='https://wp-staging.com/docs/wp-staging-settings-for-small-servers/' target='_blank'>WP Staging Small Server Settings</a> or submit an error report and contact us.");
                    }
                },
                500: function () {
                    if (tryCount >= retryLimit) {
                        showError("Fatal Error 500 - Internal server error while processing the request! Please try the <a href='https://wp-staging.com/docs/wp-staging-settings-for-small-servers/' target='_blank'>WP Staging Small Server Settings</a> or submit an error report and contact us.");
                    }
                },
                504: function () {
                    if (tryCount > retryLimit) {
                        showError("Error 504 - It looks like your server is rate limiting ajax requests. Please try to resume after a minute. If this still not works try the <a href='https://wp-staging.com/docs/wp-staging-settings-for-small-servers/' target='_blank'>WP Staging Small Server Settings</a> or submit an error report and contact us.\n\ ");
                    }

                },
                502: function () {
                    if (tryCount >= retryLimit) {
                        showError("Error 502 - It looks like your server is rate limiting ajax requests. Please try to resume after a minute. If this still not works try the <a href='https://wp-staging.com/docs/wp-staging-settings-for-small-servers/' target='_blank'>WP Staging Small Server Settings</a> or submit an error report and contact us.\n\ ");
                    }
                },
                503: function () {
                    if (tryCount >= retryLimit) {
                        showError("Error 503 - It looks like your server is rate limiting ajax requests. Please try to resume after a minute. If this still not works try the <a href='https://wp-staging.com/docs/wp-staging-settings-for-small-servers/' target='_blank'>WP Staging Small Server Settings</a> or submit an error report and contact us.\n\ ");
                    }
                },
                429: function () {
                    if (tryCount >= retryLimit) {
                        showError("Error 429 - It looks like your server is rate limiting ajax requests. Please try to resume after a minute. If this still not works try the <a href='https://wp-staging.com/docs/wp-staging-settings-for-small-servers/' target='_blank'>WP Staging Small Server Settings</a> or submit an error report and contact us.\n\ ");
                    }
                },
                403: function () {
                    if (tryCount >= retryLimit) {
                        showError("Refresh page or login again! The process should be finished successfully. \n\ ");
                    }
                }
            }
        });
    };

    /**
     * Next / Previous Step Clicks to Navigate Through Staging Job
     */
    var stepButtons = function () {

        var $workFlow = cache.get("#wpstg-workflow");

        $workFlow
            // Next Button
            .on("click", ".wpstg-next-step-link", function (e) {
                e.preventDefault();

                var $this = $(this);
                var isScan = false;

                if($('#wpstg_clone_hostname').length && !validateTargetHost()) {
                    $('#wpstg_clone_hostname').focus();
                    return false;
                }

                if ($this.data("action") === "wpstg_update") {
                    // Update Clone - confirmed
                    if (!confirm("STOP! This will overwrite your staging site with all selected data from the live site! This should be used only if you want to clone again your production site. Are you sure you want to do this? \n\nMake sure to exclude all tables and folders which you do not want to overwrite, first! \n\nDo not necessarily cancel the updating process! This can break your staging site. \n\n\Make sure you have a backop of your staging website before you proceed.")) {
                        return false;
                    }

                }

                // Button is disabled
                if ($this.attr("disabled")) {
                    return false;
                }

                if ($this.data("action") === "wpstg_cloning") {
                    // Verify External Database If Checked and Not Skipped
                    if($("#wpstg-ext-db").is(':checked')) {
                        verifyExternalDatabase($this, $workFlow);
                        return;
                    }

                }

                proceedCloning($this, $workFlow);
            })
            // Previous Button
            .on("click", ".wpstg-prev-step-link", function (e) {
                e.preventDefault();
                cache.get(".wpstg-loader").removeClass('wpstg-finished');
                cache.get(".wpstg-loader").hide();
                loadOverview();
            });
    };

    /**
     * Get Included (Checked) Database Tables
     * @returns {Array}
     */
    var getIncludedTables = function () {
        var includedTables = [];

        $("#wpstg_select_tables_cloning option:selected").each(function () {
            includedTables.push(this.value);
        });

        return includedTables;
    };

    /**
     * Get Excluded (Unchecked) Database Tables
     * Not used anymore!
     * @returns {Array}
     */
    var getExcludedTables = function () {
        var excludedTables = [];

        $(".wpstg-db-table input:not(:checked)").each(function () {
            excludedTables.push(this.name);
        });

        return excludedTables;
    };

    /**
     * Get Included Directories
     * @returns {Array}
     */
    var getIncludedDirectories = function () {
        var includedDirectories = [];

        $(".wpstg-dir input:checked.wpstg-root").each(function () {
            var $this = $(this);
            includedDirectories.push(encodeURIComponent($this.val()));
        });

        return includedDirectories;
    };

    /**
     * Get Excluded Directories
     * @returns {Array}
     */
    var getExcludedDirectories = function () {
        var excludedDirectories = [];

        $(".wpstg-dir input:not(:checked).wpstg-root").each(function () {
            var $this = $(this);
            excludedDirectories.push(encodeURIComponent($this.val()));
        });

        return excludedDirectories;
    };

    /**
     * Get included extra directories of the root level
     * All directories except wp-content, wp-admin, wp-includes
     * @returns {Array}
     */
    var getIncludedExtraDirectories = function () {
        // Add directories from the root level
        var extraDirectories = [];
        $(".wpstg-dir input:checked.wpstg-extra").each(function () {
            var $this = $(this);
            extraDirectories.push(encodeURIComponent($this.val()));
        });

        // Add any other custom selected extra directories
        if (!$("#wpstg_extraDirectories").val()) {
            return extraDirectories;
        }

        var extraCustomDirectories = encodeURIComponent($("#wpstg_extraDirectories").val().split(/\r?\n/));

        return extraDirectories.concat(extraCustomDirectories);
    };

    /**
     * Verify External Database for Cloning
     */
    var verifyExternalDatabase = function($this, workflow) {
        cache.get(".wpstg-loader").show();
        ajax(
            {
                action: "wpstg_database_verification",
                accessToken: wpstg.accessToken,
                nonce: wpstg.nonce,
                databaseUser: cache.get('#wpstg_db_username').val(),
                databasePassword: cache.get('#wpstg_db_password').val(),
                databaseServer: cache.get('#wpstg_db_server').val(),
                databaseDatabase: cache.get('#wpstg_db_database').val(),
            },
            function (response)
            {
                // Undefined Error
                if (false === response)
                {
                    showError(
                        "Something went wrong! Error: No response." +
                        "Please try again. If that does not help, " +
                        "<a href='https://wp-staging.com/support/' target='_blank'>open a support ticket</a> "
                    );
                    cache.get(".wpstg-loader").hide();
                    return;
                }

                // Throw Error
                if ("undefined" === typeof (response.success)) {
                    showError(
                        "Something went wrong! Error: Invalid response." +
                        "Please try again. If that does not help, " +
                        "<a href='https://wp-staging.com/support/' target='_blank'>open a support ticket</a> "
                    );
                    cache.get(".wpstg-loader").hide();
                    return;
                }

                if (response.success) {
                    cache.get(".wpstg-loader").hide();
                    proceedCloning($this, workflow);
                    return;
                }

                if (response.error_type === 'comparison') {
                    cache.get(".wpstg-loader").hide();
                    var render = '<table style="width: 100%;"><thead><tr><th>Property</th><th>Production DB</th><th>Staging DB</th><th>Status</th></tr></thead><tbody>';
                    response.checks.forEach(x=>{
                        var icon = '<i style="color: #00ff00">✔</i>';
                        if (x.production !== x.staging) {
                            icon = '<i style="color: #ff0000">❌</i>'
                        }
                        render += "<tr><td>"+x.name+"</td><td>"+x.production+"</td><td>"+x.staging+"</td><td>"+icon+"</td></tr>";
                    });
                    render += '</tbody></table><p>Note: Some mySQL properties do not match. You may proceed but the staging site may not work as expected.</p>';
                    Swal.fire({
                        title: 'Different Database Properties',
                        icon: 'warning',
                        html: render,
                        width: '650px',
                        focusConfirm: false,
                        confirmButtonText: 'Proceed Anyway',
                        showCancelButton: true,
                    }).then(function(result) {
                        if (result.value) {
                            proceedCloning($this, workflow);
                        }
                    });
                    return;
                }

                Swal.fire({
                    title: 'Different Database Properties',
                    icon: 'error',
                    html: response.message,
                    focusConfirm: true,
                    confirmButtonText: 'Ok',
                    showCancelButton: false,
                });
                cache.get(".wpstg-loader").hide();
            },
            "json",
            false
        );
    };

    /**
     * Get Cloning Step Data
     */
    var getCloningData = function () {
        if ("wpstg_cloning" !== that.data.action && "wpstg_update" !== that.data.action) {
            return;
        }

        that.data.cloneID = $("#wpstg-new-clone-id").val() || new Date().getTime().toString();
        // Remove this to keep &_POST[] small otherwise mod_security will throw error 404
        //that.data.excludedTables = getExcludedTables();
        that.data.includedTables = getIncludedTables();
        that.data.includedDirectories = getIncludedDirectories();
        that.data.excludedDirectories = getExcludedDirectories();
        that.data.extraDirectories = getIncludedExtraDirectories();
        that.data.databaseServer = $("#wpstg_db_server").val();
        that.data.databaseUser = $("#wpstg_db_username").val();
        that.data.databasePassword = $("#wpstg_db_password").val();
        that.data.databaseDatabase = $("#wpstg_db_database").val();
        that.data.databasePrefix = $("#wpstg_db_prefix").val();
        var cloneDir = $("#wpstg_clone_dir").val();
        that.data.cloneDir = encodeURIComponent($.trim(cloneDir));
        that.data.cloneHostname = $("#wpstg_clone_hostname").val();
        that.data.emailsDisabled = $("#wpstg_disable_emails").is(':checked');
        that.data.uploadsSymlinked = $("#wpstg_symlink_upload").is(':checked');

    };

    var proceedCloning = function($this, workflow) {
        // Add loading overlay
        workflow.addClass("loading");

        // Prepare data
        that.data = {
            action: $this.data("action"),
            accessToken: wpstg.accessToken,
            nonce: wpstg.nonce
        };

        // Cloning data
        getCloningData();

        console.log(that.data);

        // Send ajax request
        ajax(
            that.data,
            function (response) {

                // Undefined Error
                if (false === response) {
                    showError(
                        "Something went wrong!<br/><br/> Go to WP Staging > Settings and lower 'File Copy Limit' and 'DB Query Limit'. Also set 'CPU Load Priority to low '" +
                        "and try again. If that does not help, " +
                        "<a href='https://wp-staging.com/support/' target='_blank'>open a support ticket</a> "
                    );
                }


                if (response.length < 1) {
                    showError(
                        "Something went wrong! No response.  Go to WP Staging > Settings and lower 'File Copy Limit' and 'DB Query Limit'. Also set 'CPU Load Priority to low '" +
                        "and try again. If that does not help, " +
                        "<a href='https://wp-staging.com/support/' target='_blank'>open a support ticket</a> "
                    );
                }

                // Styling of elements
                workflow.removeClass("loading").html(response);

                if ($this.data("action") === "wpstg_scanning") {
                    that.switchStep(2);
                } else if ($this.data("action") === "wpstg_cloning" || $this.data("action") === "wpstg_update") {
                    that.switchStep(3);
                }

                // Start cloning
                that.startCloning();

            },
            "HTML"
        );
    };

    /**
     * Loads Overview (first step) of Staging Job
     */
    var loadOverview = function () {
        var $workFlow = cache.get("#wpstg-workflow");

        $workFlow.addClass("loading");

        ajax(
            {
                action: "wpstg_overview",
                accessToken: wpstg.accessToken,
                nonce: wpstg.nonce
            },
            function (response) {

                if (response.length < 1) {
                    showError(
                        "Something went wrong! No response. Please try the <a href='https://wp-staging.com/docs/wp-staging-settings-for-small-servers/' target='_blank'>WP Staging Small Server Settings</a> or submit an error report."
                    );
                }

                var $currentStep = cache.get(".wpstg-current-step");

                // Styling of elements
                $workFlow.removeClass("loading").html(response);

            },
            "HTML"
        );

        that.switchStep(1);
        cache.get(".wpstg-step3-cloning").show();
        cache.get(".wpstg-step3-pushing").hide();
    };

    /**
     * Load Tabs
     */
    var tabs = function () {

        cache.get("#wpstg-workflow").on("click", ".wpstg-tab-header", function (e) {
            e.preventDefault();

            var $this = $(this);
            var $section = cache.get($this.data("id"));

            $this.toggleClass("expand");

            $section.slideToggle();

            if ($this.hasClass("expand")) {
                $this.find(".wpstg-tab-triangle").html("&#9660;");
            } else {
                $this.find(".wpstg-tab-triangle").html("&#9658;");
            }


        });
    };

    /**
     * Delete Clone
     * @param {String} clone
     */
    var deleteClone = function (clone) {

        var deleteDir = $("#deleteDirectory:checked").data("deletepath");

        ajax(
            {
                action: "wpstg_delete_clone",
                clone: clone,
                accessToken: wpstg.accessToken,
                nonce: wpstg.nonce,
                excludedTables: getExcludedTables(),
                deleteDir: deleteDir
            },
            function (response) {
                if (response) {
                    showAjaxFatalError(response);

                    // Finished
                    if ("undefined" !== typeof response.delete && response.delete === 'finished') {

                        cache.get("#wpstg-removing-clone").removeClass("loading").html('');

                        $(".wpstg-clone#" + clone).remove();

                        if ($(".wpstg-clone").length < 1) {
                            cache.get("#wpstg-existing-clones").find("h3").text('');
                        }

                        cache.get(".wpstg-loader").hide();
                        return;
                    }
                }
                // continue
                if (true !== response) {
                    deleteClone(clone);
                    return;
                }

            }
        );
    };

    /**
     * Cancel Cloning Process
     */
    var cancelCloning = function () {

        that.timer('stop');


        if (true === that.isFinished) {
            return true;
        }

        ajax(
            {
                action: "wpstg_cancel_clone",
                clone: that.data.cloneID,
                accessToken: wpstg.accessToken,
                nonce: wpstg.nonce
            },
            function (response) {


                if (response && "undefined" !== typeof (response.delete) && response.delete === "finished") {
                    cache.get(".wpstg-loader").hide();
                    // Load overview
                    loadOverview();
                    return;
                }

                if (true !== response) {
                    // continue
                    cancelCloning();
                    return;
                }

                // Load overview
                loadOverview();
            }
        );
    };

    /**
     * Cancel Cloning Process
     */
    var cancelCloningUpdate = function () {
        if (true === that.isFinished) {
            return true;
        }

        ajax(
            {
                action: "wpstg_cancel_update",
                clone: that.data.cloneID,
                accessToken: wpstg.accessToken,
                nonce: wpstg.nonce
            },
            function (response) {


                if (response && "undefined" !== typeof (response.delete) && response.delete === "finished") {
                    // Load overview
                    loadOverview();
                    return;
                }

                if (true !== response) {
                    // continue
                    cancelCloningUpdate();
                    return;
                }

                // Load overview
                loadOverview();
            }
        );
    };

    /**
     * Cancel Cloning Process
     */
    var restart = function () {
        if (true === that.isFinished) {
            return true;
        }

        ajax(
            {
                action: "wpstg_restart",
                //clone: that.data.cloneID,
                accessToken: wpstg.accessToken,
                nonce: wpstg.nonce
            },
            function (response) {


                if (response && "undefined" !== typeof (response.delete) && response.delete === "finished") {
                    // Load overview
                    loadOverview();
                    return;
                }

                if (true !== response) {
                    // continue
                    cancelCloningUpdate();
                    return;
                }

                // Load overview
                loadOverview();
            }
        );
    };

    /**
     * Scroll the window log to bottom
     * @returns void
     */
    var logscroll = function () {
        var $div = cache.get(".wpstg-log-details");
        if ("undefined" !== typeof ($div[0])) {
            $div.scrollTop($div[0].scrollHeight);
        }
    }

    /**
     * Append the log to the logging window
     * @param string log
     * @returns void
     */
    var getLogs = function (log) {
        if (log != null && "undefined" !== typeof (log)) {
            if (log.constructor === Array) {
                $.each(log, function (index, value) {
                    if (value === null) {
                        return;
                    }
                    if (value.type === 'ERROR') {
                        cache.get(".wpstg-log-details").append('<span style="color:red;">[' + value.type + ']</span>-' + '[' + value.date + '] ' + value.message + '</br>');
                    } else {
                        cache.get(".wpstg-log-details").append('[' + value.type + ']-' + '[' + value.date + '] ' + value.message + '</br>');
                    }
                })
            } else {
                cache.get(".wpstg-log-details").append('[' + log.type + ']-' + '[' + log.date + '] ' + log.message + '</br>');
            }
        }
        logscroll();

    };

    /**
     * Check diskspace
     * @returns string json
     */
    var checkDiskSpace = function () {
        cache.get("#wpstg-check-space").on("click", function (e) {
            cache.get(".wpstg-loader").show();
            console.log("check disk space");
            ajax(
                {
                    action: "wpstg_check_disk_space",
                    accessToken: wpstg.accessToken,
                    nonce: wpstg.nonce
                },
                function (response) {
                    if (false === response) {
                        cache.get("#wpstg-clone-id-error").text('Can not detect required disk space').show();
                        cache.get(".wpstg-loader").hide();
                        return;
                    }

                    // Show required disk space
                    cache.get("#wpstg-clone-id-error").html('Estimated necessary disk space: ' + response.usedspace + '<br> <span style="color:#444;">Before you proceed ensure your account has enough free disk space to hold the entire instance of the production site. You can check the available space from your hosting account (cPanel or similar).</span>').show();
                    cache.get(".wpstg-loader").hide();
                },
                "json",
                false
            );
        });
    }

    var mainTabs = function () {
        $('.wpstg--tab--header a[data-target]').on('click', function () {
            var $this = $(this);
            var target = $this.attr('data-target');
            var $wrapper = $this.parents('.wpstg--tab--wrapper');
            var $menuItems = $wrapper.find('.wpstg--tab--header a[data-target]');
            var $contents = $wrapper.find('.wpstg--tab--contents > .wpstg--tab--content');

            $contents.filter('.wpstg--tab--active:not(.wpstg--tab--active' + target + ')').removeClass('wpstg--tab--active');
            $menuItems.not($this).removeClass('wpstg--tab--active');
            $this.addClass('wpstg--tab--active');
            $(target).addClass('wpstg--tab--active');

            if ('#wpstg--tab--snapshot' === target) {
                that.snapshots.init();
            }
        });
    };

    /**
     * Show or hide animated loading icon
     * @param isLoading bool
     */
    var isLoading = function (isLoading) {
        if (!isLoading || isLoading === false) {
            cache.get(".wpstg-loader").hide();
        } else {
            cache.get(".wpstg-loader").show();
        }
    };

    /**
     * Count up processing execution time
     * @param string status
     * @returns html
     */
    that.timer = function (status) {

        if (status === 'stop') {
            var time = that.time;
            that.time = 1;
            clearInterval(that.executionTime);
            return that.convertSeconds(time);
        }


        that.executionTime = setInterval(function () {
            if (null !== document.getElementById('wpstg-processing-timer')) {
                document.getElementById('wpstg-processing-timer').innerHTML = 'Elapsed Time: ' + that.convertSeconds(that.time);
            }
            that.time++;
            if (status === 'stop') {
                that.time = 1;
                clearInterval(that.executionTime);
            }
        }, 1000);
    };

    /**
     * Convert seconds to hourly format
     * @param int seconds
     * @returns string
     */
    that.convertSeconds = function (seconds) {
        var date = new Date(null);
        date.setSeconds(seconds); // specify value for SECONDS here
        return date.toISOString().substr(11, 8);
    };

    /**
     * Start Cloning Process
     * @type {Function}
     */
    that.startCloning = (function () {

        resetErrors();

        // Register function for checking disk space
        checkDiskSpace();

        if ("wpstg_cloning" !== that.data.action && "wpstg_update" !== that.data.action) {
            return;
        }

        that.isCancelled = false;

        // Start the process
        start();

        // Functions
        // Start
        function start() {

            console.log("Starting cloning process...");

            cache.get(".wpstg-loader").show();
            cache.get("#wpstg-cancel-cloning").text('Cancel');
            cache.get("#wpstg-resume-cloning").hide();
            cache.get("#wpstg-error-details").hide();


            // Clone Database
            setTimeout(function () {
                //cloneDatabase();
                window.addEventListener('beforeunload', wpstgWarnIfClose);
                processing();
            }, wpstg.delayReq);

            that.timer('start');

        }


        /**
         * Start ajax processing
         * @returns string
         */
        var processing = function () {

            if (true === that.isCancelled) {
                window.removeEventListener('beforeunload', wpstgWarnIfClose);
                return false;
            }

            isLoading(true);

            // Show logging window
            cache.get('.wpstg-log-details').show();

            WPStaging.ajax(
                {
                    action: "wpstg_processing",
                    accessToken: wpstg.accessToken,
                    nonce: wpstg.nonce,
                    excludedTables: getExcludedTables(),
                    includedDirectories: getIncludedDirectories(),
                    excludedDirectories: getExcludedDirectories(),
                    extraDirectories: getIncludedExtraDirectories()
                },
                function (response) {

                    showAjaxFatalError(response);

                    // Add Log messages
                    if ("undefined" !== typeof (response.last_msg) && response.last_msg) {
                        getLogs(response.last_msg);
                    }
                    // Continue processing
                    if (false === response.status) {
                        progressBar(response);

                        setTimeout(function () {
                            cache.get(".wpstg-loader").show();
                            processing();
                        }, wpstg.delayReq);

                    } else if (true === response.status && 'finished' !== response.status) {
                        cache.get("#wpstg-error-details").hide();
                        cache.get("#wpstg-error-wrapper").hide();
                        progressBar(response, true);
                        processing();
                    } else if ('finished' === response.status || ("undefined" !== typeof (response.job_done) && response.job_done)) {
                        window.removeEventListener('beforeunload', wpstgWarnIfClose);
                        finish(response);
                    }
                    ;
                },
                "json",
                false
            );
        };

        // Finish
        function finish(response) {

            if (true === that.getLogs) {
                getLogs();
            }

            progressBar(response);

            // Add Log
            if ("undefined" !== typeof (response.last_msg)) {
                getLogs(response.last_msg);
            }

            console.log("Cloning process finished");

            cache.get(".wpstg-loader").hide();
            cache.get("#wpstg-processing-header").html('Processing Complete');
            $("#wpstg-processing-status").text("Succesfully finished");

            cache.get("#wpstg_staging_name").html(that.data.cloneID);
            cache.get("#wpstg-finished-result").show();
            cache.get("#wpstg-cancel-cloning").hide();
            cache.get("#wpstg-resume-cloning").hide();
            cache.get("#wpstg-cancel-cloning-update").prop("disabled", true);

            var $link1 = cache.get("#wpstg-clone-url-1");
            var $link = cache.get("#wpstg-clone-url");
            $link1.attr("href", response.url);
            $link1.html(response.url);
            $link.attr("href", response.url);

            cache.get("#wpstg-remove-clone").data("clone", that.data.cloneID);

            // Finished
            that.isFinished = true;
            that.timer('stop');


            cache.get(".wpstg-loader").hide();
            cache.get("#wpstg-processing-header").html('Processing Complete');

            return false;

        }

        /**
         * Add percentage progress bar
         * @param object response
         * @returns {Boolean}
         */
        var progressBar = function (response, restart) {
            if ("undefined" === typeof (response.percentage))
                return false;

            if (response.job === 'database') {
                cache.get("#wpstg-progress-db").width(response.percentage * 0.2 + '%').html(response.percentage + '%');
                cache.get("#wpstg-processing-status").html(response.percentage.toFixed(0) + '%' + ' - Step 1 of 4 Cloning Database Tables...');
            }

            if (response.job === 'SearchReplace') {
                cache.get("#wpstg-progress-db").css('background-color', '#3bc36b');
                cache.get("#wpstg-progress-db").html('1. Database');
                //Assumption: All previous steps are done.
                //This avoids bugs where some steps are skipped and the progress bar is incomplete as a result
                cache.get("#wpstg-progress-db").width('20%');

                cache.get("#wpstg-progress-sr").width(response.percentage * 0.1 + '%').html(response.percentage + '%');
                cache.get("#wpstg-processing-status").html(response.percentage.toFixed(0) + '%' + ' - Step 2 of 4 Preparing Database Data...');
            }

            if (response.job === 'directories') {
                cache.get("#wpstg-progress-sr").css('background-color', '#3bc36b');
                cache.get("#wpstg-progress-sr").html('2. Data');
                cache.get("#wpstg-progress-sr").width('10%');

                cache.get("#wpstg-progress-dirs").width(response.percentage * 0.1 + '%').html(response.percentage + '%');
                cache.get("#wpstg-processing-status").html(response.percentage.toFixed(0) + '%' + ' - Step 3 of 4 Getting files...');
            }
            if (response.job === 'files') {
                cache.get("#wpstg-progress-dirs").css('background-color', '#3bc36b');
                cache.get("#wpstg-progress-dirs").html('3. Files');
                cache.get("#wpstg-progress-dirs").width('10%');

                cache.get("#wpstg-progress-files").width(response.percentage * 0.6 + '%').html(response.percentage + '%');
                cache.get("#wpstg-processing-status").html(response.percentage.toFixed(0) + '%' + ' - Step 4 of 4 Copy files...');
            }
            if (response.job === 'finish') {
                cache.get("#wpstg-progress-files").css('background-color', '#3bc36b');
                cache.get("#wpstg-progress-files").html('4. Copy Files');
                cache.get("#wpstg-progress-files").width('60%');

                cache.get("#wpstg-processing-status").html(response.percentage.toFixed(0) + '%' + ' - Cloning Process Finished');
            }
        }
    });

    that.switchStep = function (step) {
        cache.get(".wpstg-current-step")
            .removeClass("wpstg-current-step");
        cache.get(".wpstg-step" + step)
            .addClass("wpstg-current-step");
    }

    /**
     * Initiation
     * @type {Function}
     */
    that.init = (function () {
        loadOverview();
        elements();
        stepButtons();
        tabs();
        mainTabs();
    });

    /**
     * Ajax call
     * @type {ajax}
     */
    that.ajax = ajax;
    that.showError = showError;
    that.getLogs = getLogs;
    that.loadOverview = loadOverview;

    // TODO RPoC (too big, scattered and unorganized)
    that.snapshots = {
        type: null,
        isCancelled: false,
        processInfo: {
            title: null,
            interval: null,
        },
        modal: {
            create: {
                html: null,
                confirmBtnTxt: null,
            },
            process: {
                html: null,
                cancelBtnTxt: null,
                modal: null,
            },
            download: {
                html: null,
            },
            import: {
                html: null,
                btnTxtNext: null,
                btnTxtConfirm: null,
                btnTxtCancel: null,
                searchReplaceForm: null,
                file: null,
                containerUpload: null,
                containerFilesystem: null,
                setFile: (file, upload = true) => {
                    const toUnit = (bytes) => {
                        const i = Math.floor( Math.log(bytes) / Math.log(1024) );
                        return (bytes / Math.pow(1024, i)).toFixed(2) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i];
                    }

                    if (!file) {
                        return;
                    }

                    that.snapshots.modal.import.file = file;
                    that.snapshots.modal.import.data.file = file.name;
                    console.log(`File ${file.name}`);
                    $('.wpstg--snapshot--import--selected-file').html(`${file.name} <br /> (${toUnit(file.size)})`).show();
                    $('.wpstg--drag').hide();
                    $('.wpstg--drag-or-upload').show();

                    if (upload) {
                        $('.wpstg--modal--actions .swal2-confirm').prop('disabled', true);
                        that.snapshots.upload.start();
                    }
                },
                baseDirectory: null,
                data: {
                    file: null,
                    search: [],
                    replace: [],
                },
            },
        },
        messages: {
            WARNING: 'warning',
            ERROR: 'error',
            INFO: 'info',
            DEBUG: 'debug',
            CRITICAL: 'critical',
            data: {
                all: [], // TODO RPoC
                info: [],
                error: [],
                critical: [],
                warning: [],
                debug: [],
            },
            shouldWarn() {
              return that.snapshots.messages.data.error.length > 0
                || that.snapshots.messages.data.critical.length > 0
              ;
            },
            countByType(type = that.snapshots.messages.ERROR) {
                return that.snapshots.messages.data[type].length;
            },
            addMessage(message) {
                if (Array.isArray(message)) {
                    message.forEach(item => {
                        that.snapshots.messages.addMessage(item);
                    });
                    return;
                }
                const type = message.type.toLowerCase() || 'info';
                if (!that.snapshots.messages.data[type]) {
                    that.snapshots.messages.data[type] = [];
                }
                that.snapshots.messages.data.all.push(message); // TODO RPoC
                that.snapshots.messages.data[type].push(message);
            },
            reset() {
                that.snapshots.messages.data = {
                    all: [],
                    info: [],
                    error: [],
                    critical: [],
                    warning: [],
                    debug: [],
                };
            },
        },
        timer: {
            totalSeconds: 0,
            interval: null,
            start() {
                if (null !== that.snapshots.timer.interval) {
                    return;
                }

                const prettify = (seconds) => {
                    console.log(`Process running for ${seconds} seconds`);
                    // If potentially anything can exceed 24h execution time than that;
                    // const _seconds = parseInt(seconds, 10)
                    // const hours = Math.floor(_seconds / 3600)
                    // const minutes = Math.floor(_seconds / 60) % 60
                    // seconds = _seconds % 60
                    //
                    // return [hours, minutes, seconds]
                    //   .map(v => v < 10 ? '0' + v : v)
                    //   .filter((v,i) => v !== '00' || i > 0)
                    //   .join(':')
                    // ;
                    // Are we sure we won't create anything that exceeds 24h execution time? If not then this;
                    return `${(new Date(seconds * 1000)).toISOString().substr(11, 8)}`;
                };

                that.snapshots.timer.interval = setInterval(() => {
                    $('.wpstg--modal--process--elapsed-time').text(prettify(that.snapshots.timer.totalSeconds));
                    that.snapshots.timer.totalSeconds++;
                }, 1000);
            },
            stop() {
                that.snapshots.timer.totalSeconds = 0;
                if (that.snapshots.timer.interval) {
                    clearInterval(that.snapshots.timer.interval);
                    that.snapshots.timer.interval = null;
                }
            },
        },
        upload: {
            reader: null,
            file: null,
            iop: 1000 * 1024,
            uploadInfo(isShow) {
                const $containerUpload = $('.wpstg--modal--import--upload--process');
                const $containerUploader = $('.wpstg--uploader');
                if (isShow) {
                    $containerUpload.css('display', 'flex');
                    $containerUploader.hide();
                    return;
                }

                $containerUploader.css('display', 'flex');
                $containerUpload.hide();
            },
            start() {
                console.log(`file ${that.snapshots.modal.import.data.file}`);
                that.snapshots.upload.reader = new FileReader();
                that.snapshots.upload.file = that.snapshots.modal.import.file;
                that.snapshots.upload.uploadInfo(true);
                that.snapshots.upload.sendChunk();
            },
            sendChunk(startsAt = 0) {
                if (!that.snapshots.upload.file) {
                    return;
                }
                const isReset = startsAt < 1;
                const endsAt = startsAt + that.snapshots.upload.iop + 1;
                const blob = that.snapshots.upload.file.slice(startsAt, endsAt);
                that.snapshots.upload.reader.onloadend = function(event) {
                    if (event.target.readyState !== FileReader.DONE) {
                        return;
                    }

                    const body = new FormData();
                    body.append('accessToken', wpstg.accessToken);
                    body.append('nonce', wpstg.nonce);
                    body.append('data', event.target.result);
                    body.append('filename', that.snapshots.upload.file.name);
                    body.append('reset', isReset ? '1' : '0');


                    fetch(`${ajaxurl}?action=wpstg--snapshots--import--file-upload`, {
                        method: 'POST',
                        body,
                    }).then(handleFetchErrors)
                      .then(res => res.json())
                      .then(res => {
                          showAjaxFatalError(res, '', 'Submit an error report.');
                          const writtenBytes = startsAt + that.snapshots.upload.iop;
                          const percent = Math.floor((writtenBytes / that.snapshots.upload.file.size) * 100);
                          if (endsAt >= that.snapshots.upload.file.size) {
                              that.snapshots.upload.uploadInfo(false);
                              isLoading(false);
                              $('.wpstg--modal--actions .swal2-confirm').prop('disabled', false);
                              return;
                          }
                          $('.wpstg--modal--import--upload--progress--title > span').text(percent);
                          $('.wpstg--modal--import--upload--progress').css('width', `${percent}%`)
                          that.snapshots.upload.sendChunk(endsAt);
                      })
                      .catch(e => showAjaxFatalError(e, '', 'Submit an error report.'))
                    ;
                };
                that.snapshots.upload.reader.readAsDataURL(blob);
            },
        },
        status: {
            hasResponse: null,
            reTryAfter: 5000,
        },
        init() {
            this.create();
            this.delete();
            this.restore();
            this.edit();

            // noinspection JSIgnoredPromiseFromCall
            that.snapshots.fetchListing();

            $('body')
              .off('change', '#wpstg--snapshots--filter')
              .on('change', '#wpstg--snapshots--filter', function() {
                  const $records = $('#wpstg-existing-snapshots').find('> div[id][data-type].wpstg-snapshot');
                  if (this.value === '') {
                      $records.show();
                  } else if (this.value === 'database') {
                      $records.filter('[data-type="site"]').hide();
                      $records.filter('[data-type="database"]').show();
                  } else if (this.value === 'site') {
                      $records.filter('[data-type="database"]').hide();
                      $records.filter('[data-type="site"]').show();
                  }
              })
              .on('click', '.wpstg--snapshot--download', function() {
                const url = this.getAttribute('data-url');
                if (url.length > 0) {
                    window.location.href = url;
                    return;
                }
                that.snapshots.downloadModal({
                    titleExport: this.getAttribute('data-title-export'),
                    title: this.getAttribute('data-title'),
                    id: this.getAttribute('data-id'),
                    btnTxtCancel: this.getAttribute('data-btn-cancel-txt'),
                    btnTxtConfirm: this.getAttribute('data-btn-download-txt'),
                });
            })
              .off('click', '#wpstg-import-snapshot')
              .on('click', '#wpstg-import-snapshot', function() {
                  that.snapshots.importModal();
              })
              // Import
              .off('click', '.wpstg--snapshot--import--choose-option')
              .on('click', '.wpstg--snapshot--import--choose-option', function() {
                  const $this = $(this);
                  const $parent = $this.parent();
                  if (!$parent.hasClass('wpstg--show-options')) {
                      $parent.addClass('wpstg--show-options');
                      $this.text($this.attr('data-txtChoose'));
                  } else {
                      $parent.removeClass('wpstg--show-options');
                      $this.text($this.attr('data-txtOther'));
                  }
              })
              .off('click', '.wpstg--modal--snapshot--import--search-replace--new')
              .on('click', '.wpstg--modal--snapshot--import--search-replace--new', function(e) {
                  e.preventDefault();
                  const $container = $(Swal.getContainer()).find('.wpstg--modal--snapshot--import--search-replace--input--container');
                  const total = $container.find('.wpstg--modal--snapshot--import--search-replace--input-group').length;
                  $container.append(that.snapshots.modal.import.searchReplaceForm.replace(/{i}/g, total));
              })
              .off('input', '.wpstg--snapshot--import--search')
              .on('input', '.wpstg--snapshot--import--search', function() {
                  const index = parseInt(this.getAttribute('data-index'));
                  if (!isNaN(index)) {
                      that.snapshots.modal.import.data.search[index] = this.value;
                  }
              })
              .off('input', '.wpstg--snapshot--import--replace')
              .on('input', '.wpstg--snapshot--import--replace', function() {
                  const index = parseInt(this.getAttribute('data-index'));
                  if (!isNaN(index)) {
                      that.snapshots.modal.import.data.replace[index] = this.value;
                  }
              })
              // Other Options
              .off('click', '.wpstg--snapshot--import--option[data-option]')
              .on('click', '.wpstg--snapshot--import--option[data-option]', function() {
                  const option = this.getAttribute('data-option');

                  if (option === 'file') {
                      $('input[type="file"][name="wpstg--snapshot--import--upload--file"]').click();
                      return;
                  }

                  if (option === 'upload') {
                      that.snapshots.modal.import.containerFilesystem.hide();
                      that.snapshots.modal.import.containerUpload.show();
                      $('.wpstg--snapshot--import--choose-option').click();
                      $('.wpstg--modal--snapshot--import--search-replace--wrapper').show();

                  }

                  if (option !== 'filesystem') {
                      return;
                  }

                  that.snapshots.modal.import.containerUpload.hide();
                  const $containerFilesystem = that.snapshots.modal.import.containerFilesystem;
                  $containerFilesystem.show();

                  fetch(`${ajaxurl}?action=wpstg--snapshots--import--file-list&_=${Math.random()}&accessToken=${wpstg.accessToken}&nonce=${wpstg.nonce}`)
                      .then(handleFetchErrors)
                    .then(res => res.json())
                    .then(res => {
                        const $ul = $('.wpstg--modal--snapshot--import--filesystem ul');
                        $ul.empty();

                        if (!res || isEmpty(res)) {
                            $ul.append(`<span id="wpstg--snapshots--import--file-list-empty">No import file found! Upload an import file to the folder above.</span><br />`);
                            $('.wpstg--modal--snapshot--import--search-replace--wrapper').hide();
                            return;
                        }

                        $ul.append(`<span id="wpstg--snapshots--import--file-list">Select file to import:</span><br />`);
                        res.forEach(function(file, index){
                            //var checked = (index === 0) ? 'checked' : '';
                            $ul.append(`<li><label><input name="snapshot_import_file" type="radio" value="${file.fullPath}">${file.name} <br /> ${file.size}</label></li>`);
                        });
                        //$('.wpstg--modal--actions .swal2-confirm').prop('disabled', false);
                        return res;
                    })
                    .catch(e => showAjaxFatalError(e, '', 'Submit an error report.'))
                  ;
              })
              .off('change', 'input[type="file"][name="wpstg--snapshot--import--upload--file"]')
              .on('change', 'input[type="file"][name="wpstg--snapshot--import--upload--file"]', function() {
                  that.snapshots.modal.import.setFile(this.files[0] || null);
                  $('.wpstg--snapshot--import--choose-option').click();
              })
              .off('change', 'input[type="radio"][name="snapshot_import_file"]')
              .on('change', 'input[type="radio"][name="snapshot_import_file"]', function() {
                  $('.wpstg--modal--actions .swal2-confirm').prop('disabled', false);
                  that.snapshots.modal.import.data.file = this.value;
              })
              // Drag & Drop
              .on('drag dragstart dragend dragover dragenter dragleave drop', '.wpstg--modal--snapshot--import--upload--container', function(e) {
                  e.preventDefault();
                  e.stopPropagation();
              })
              .on('dragover dragenter', '.wpstg--modal--snapshot--import--upload--container', function() {
                  $(this).addClass('wpstg--has-dragover');
              })
              .on('dragleave dragend drop', '.wpstg--modal--snapshot--import--upload--container', function() {
                  $(this).removeClass('wpstg--has-dragover');
              })
              .on('drop', '.wpstg--modal--snapshot--import--upload--container', function(e) {
                  that.snapshots.modal.import.setFile(e.originalEvent.dataTransfer.files[0] || null);
              })
            ;
        },
        fetchListing(isResetErrors = true) {
            isLoading(true);

            if (isResetErrors) {
                resetErrors();
            }

            return fetch(`${ajaxurl}?action=wpstg--snapshots--listing&_=${Math.random()}&accessToken=${wpstg.accessToken}&nonce=${wpstg.nonce}`)
                .then(handleFetchErrors)
              .then(res => res.json())
              .then(res => {
                  showAjaxFatalError(res, '', 'Submit an error report.');
                  cache.get('#wpstg--tab--snapshot').html(res);
                  isLoading(false);
                  return res;
              })
              .catch(e => showAjaxFatalError(e, '', 'Submit an error report.'))
            ;
        },
        delete() {
            $('#wpstg--tab--snapshot')
                .off('click', '.wpstg-delete-snapshot[data-id]')
                .on('click', '.wpstg-delete-snapshot[data-id]', function (e) {
                    e.preventDefault();
                    resetErrors();
                    isLoading(true);
                    cache.get('#wpstg-existing-snapshots').hide();
                    var id = this.getAttribute('data-id');
                    that.ajax(
                        {
                            action: 'wpstg--snapshots--delete--confirm',
                            id: id,
                            accessToken: wpstg.accessToken,
                            nonce: wpstg.nonce
                        },
                        function (response) {
                            showAjaxFatalError(response, '', ' Please submit an error report by using the REPORT ISSUE button.');
                            isLoading(false);
                            cache.get('#wpstg-delete-confirmation').html(response);
                        },
                    );
                })
                // Delete final confirmation page
                .off('click', '#wpstg-delete-snapshot')
                .on('click', '#wpstg-delete-snapshot', function (e) {
                    e.preventDefault();
                    resetErrors();
                    isLoading(true);
                    var id = this.getAttribute('data-id');
                    that.ajax(
                        {
                            action: 'wpstg--snapshots--delete',
                            id: id,
                            accessToken: wpstg.accessToken,
                            nonce: wpstg.nonce
                        },
                        function (response) {
                            showAjaxFatalError(response, '', ' Please submit an error report by using the REPORT ISSUE button.');
                            // noinspection JSIgnoredPromiseFromCall
                            that.snapshots.fetchListing();
                            isLoading(false);
                        },
                    );
                })
                .off('click', '#wpstg-cancel-snapshot-delete')
                .on('click', '#wpstg-cancel-snapshot-delete', function (e) {
                    e.preventDefault();
                    isLoading(false);
                    // noinspection JSIgnoredPromiseFromCall
                    that.snapshots.fetchListing();
                })
            ;

            // Force delete if snapshot tables do not exist
            // TODO This is bloated, no need extra ID, use existing one?
            $('#wpstg-error-wrapper')
              .off('click', '#wpstg-snapshot-force-delete')
              .on('click', '#wpstg-snapshot-force-delete', function (e) {
                  e.preventDefault();
                  resetErrors();
                  isLoading(true);
                  var id = this.getAttribute('data-id');

                  if (!confirm("Do you want to delete this snapshot " + id + " from the listed snapshots?")) {
                      isLoading(false);
                      return false;
                  }

                  that.ajax(
                    {
                        action: 'wpstg--snapshots--delete',
                        id: id,
                        accessToken: wpstg.accessToken,
                        nonce: wpstg.nonce
                    },
                    function (response) {
                        showAjaxFatalError(response, '', ' Please submit an error report by using the REPORT ISSUE button.');
                        // noinspection JSIgnoredPromiseFromCall
                        that.snapshots.fetchListing();
                        isLoading(false);
                    },
                  );
              })
            ;
        },
        create() {
            var createSnapshot = function (data) {
                resetErrors();

                if (that.snapshots.isCancelled) {
                    // Swal.close();
                    return;
                }

                const reset = data['reset'];
                delete data['reset'];
                let requestData = Object.assign({}, data);
                let useResponseTitle = true;

                if (data.type === 'database') {
                    that.snapshots.type = data.type;
                    delete requestData['includedDirectories'];
                    delete requestData['wpContentDir'];
                    delete requestData['availableDirectories'];
                    delete requestData['wpStagingDir'];
                    requestData = that.snapshots.requestData(
                      'tasks.snapshot.database.create',
                      { ...requestData, type: 'manual' }
                    );
                } else if (data.type === 'site') {
                    that.snapshots.type = data.type;
                    requestData = that.snapshots.requestData(
                      'jobs.snapshot.site.create',
                      requestData
                    );
                    useResponseTitle = false;
                    requestData.jobs.snapshot.site.create.directories = [
                      data.wpContentDir,
                    ];
                    requestData.jobs.snapshot.site.create.excludedDirectories = data.availableDirectories
                      .split('|')
                      .filter(item => !data.includedDirectories.includes(item))
                      .map(item => `#${item}*#`)
                    ;

                    requestData.jobs.snapshot.site.create.excludedDirectories.push(`#${data.wpStagingDir}*#`);

                    // delete requestData.jobs.snapshot.site.create.includedDirectories;
                    delete requestData.jobs.snapshot.site.create.wpContentDir;
                    delete requestData.jobs.snapshot.site.create.wpStagingDir;
                    delete requestData.jobs.snapshot.site.create.availableDirectories;

                } else {
                    that.snapshots.type = null;
                    Swal.close();
                    showError('Invalid Snapshot Type');
                    return;
                }

                that.snapshots.timer.start();

                const statusStop = () => {
                    console.log('Status: Stop');
                    clearInterval(that.snapshots.processInfo.interval);
                    that.snapshots.processInfo.interval = null;
                };
                const status = () => {
                    if (that.snapshots.processInfo.interval !== null) {
                        return;
                    }
                    console.log('Status: Start');
                    that.snapshots.processInfo.interval = setInterval(() => {
                        if (true === that.snapshots.isCancelled) {
                            statusStop();
                            return;
                        }

                        if (that.snapshots.status.hasResponse === false) {
                            return;
                        }

                        that.snapshots.status.hasResponse = false;
                        fetch(`${ajaxurl}?action=wpstg--snapshots--status&accessToken=${wpstg.accessToken}&nonce=${wpstg.nonce}`)
                          .then(res => res.json())
                          .then(res => {
                              that.snapshots.status.hasResponse = true;
                              if (typeof res === 'undefined') {
                                  statusStop();
                              }

                              if (that.snapshots.processInfo.title === res.currentStatusTitle) {
                                  return;
                              }

                              that.snapshots.processInfo.title = res.currentStatusTitle;
                              const $container = $(Swal.getContainer());
                              $container.find('.wpstg--modal--process--title').text(res.currentStatusTitle);
                              $container.find('.wpstg--modal--process--percent').text('0');
                          })
                          .catch(e => {
                              that.snapshots.status.hasResponse = true;
                              showAjaxFatalError(e, '', 'Submit an error report.');
                          })
                        ;
                    }, 5000);
                };

                WPStaging.ajax(
                    {
                        action: 'wpstg--snapshots--create',
                        accessToken: wpstg.accessToken,
                        nonce: wpstg.nonce,
                        reset,
                        wpstg: requestData,
                    },
                    function (response) {
                        if (typeof response === 'undefined') {
                            setTimeout(function () {
                                createSnapshot(data);
                            }, wpstg.delayReq);
                            return;
                        }

                        that.snapshots.processResponse(response, useResponseTitle);
                        if (!useResponseTitle && !that.snapshots.processInfo.interval) {
                            status();
                        }

                        if (response.status === false){
                            createSnapshot(data);
                        } else if (response.status === true) {
                            $('#wpstg--progress--status').text('Snapshot successfully created!');
                            that.snapshots.type = null;
                            if (that.snapshots.messages.shouldWarn()) {
                                // noinspection JSIgnoredPromiseFromCall
                                that.snapshots.fetchListing();
                                that.snapshots.logsModal();
                                return;
                            }
                            statusStop();
                            Swal.close();
                            that.snapshots.fetchListing()
                              .then(() => {
                                  if (!response.snapshotId) {
                                      showError('Failed to get snapshot ID from response');
                                      return;
                                  }

                                  // TODO RPoC
                                  const $el = $(`.wpstg--snapshot--download[data-id="${response.snapshotId}"]`);
                                  that.snapshots.downloadModal({
                                      id: $el.data('id'),
                                      url: $el.data('url'),
                                      title: $el.data('title'),
                                      titleExport: $el.data('title-export'),
                                      btnTxtCancel: $el.data('btn-cancel-txt'),
                                      btnTxtConfirm: $el.data('btn-download-txt'),
                                  })
                                  $('.wpstg--modal--download--logs--wrapper').show();
                                  const $logsContainer = $('.wpstg--modal--process--logs');
                                  that.snapshots.messages.data.all.forEach(message => {
                                      const msgClass = `wpstg--modal--process--msg--${message.type.toLowerCase()}`;
                                      $logsContainer
                                        .append(`<p class="${msgClass}">[${message.type}] - [${message.date}] - ${message.message}</p>`)
                                      ;
                                  });
                              })
                            ;
                        } else {
                            setTimeout(function () {
                                createSnapshot(data);
                            }, wpstg.delayReq);
                        }
                    },
                    'json',
                    false,
                    0, // Don't retry upon failure
                    1.25
                );
            };

            const $body = $('body');

            $body
              .off('click', 'input[name="snapshot_type"]')
              .on('click', 'input[name="snapshot_type"]', function() {
                  const $advancedOptions = $('.wpstg-advanced-options');
                  if (this.value === 'database') {
                      $advancedOptions.hide();
                      return;
                  }
                  $advancedOptions.show();
              })
              .off('click', '.wpstg--tab--toggle')
              .on('click', '.wpstg--tab--toggle', function() {
                  const $this = $(this);
                  const $target = $($this.attr('data-target'));
                  $target.toggle();
                  if ($target.is(':visible')) {
                      $this.find('span').text('▼');
                  } else {
                      $this.find('span').text('►');
                  }
              })
              .off('change', '[name="includedDirectories\[\]"], [type="checkbox"][name="export_database"]')
              .on('change', '[type="checkbox"][name="includedDirectories\[\]"], [type="checkbox"][name="export_database"]', function() {
                  const totalDirs = $('[type="checkbox"][name="includedDirectories\[\]"]:checked').length;
                  const isExportDatabase = $('[type="checkbox"][name="export_database"]:checked').length === 1;
                  if (totalDirs < 1 && !isExportDatabase) {
                      $('.swal2-confirm').prop('disabled', true);
                  } else {
                      $('.swal2-confirm').prop('disabled', false);
                  }
              })
            ;

            // Add backup name and notes
            $('#wpstg--tab--snapshot')
                .off('click', '#wpstg-new-snapshot')
                .on('click', '#wpstg-new-snapshot', async function(e) {
                    resetErrors();
                    e.preventDefault();
                    that.snapshots.isCancelled = false;

                    if (!that.snapshots.modal.create.html || !that.snapshots.modal.create.confirmBtnTxt) {
                        const $newSnapshotModal = $('#wpstg--modal--snapshot--new');
                        const html = $newSnapshotModal.html();
                        const btnTxt = $newSnapshotModal.attr('data-confirmButtonText');
                        that.snapshots.modal.create.html = html || null;
                        that.snapshots.modal.create.confirmBtnTxt = btnTxt || null;
                        $newSnapshotModal.remove();
                    }

                    const { value: formValues } = await Swal.fire({
                        title: '',
                        html: that.snapshots.modal.create.html,
                        focusConfirm: false,
                        confirmButtonText: that.snapshots.modal.create.confirmBtnTxt,
                        showCancelButton: true,
                        preConfirm: () => {
                            const container = Swal.getContainer();

                            if(document.getElementById('snapshot_type_database').offsetParent == '') {
                                var snapshotType = 'database';
                            } else {
                                var snapshotType = container.querySelector('input[name="snapshot_type"]:checked').value;
                            }

                            return {
                                type: snapshotType || null,
                                name: container.querySelector('input[name="snapshot_name"]').value || null,
                                notes: container.querySelector('textarea[name="snapshot_note"]').value || null,
                                includedDirectories: Array.from((container.querySelectorAll('input[name="includedDirectories\\[\\]"]:checked') || [])).map(i => i.value),
                                wpContentDir: container.querySelector('input[name="wpContentDir"]').value || null,
                                availableDirectories: container.querySelector('input[name="availableDirectories"]').value || null,
                                wpStagingDir: container.querySelector('input[name="wpStagingDir"]').value || null,
                                exportDatabase: container.querySelector('input[name="export_database"]:checked') !== null,
                            };
                        },
                    });

                    if (!formValues) {
                      return;
                    }

                    formValues.reset = true;

                    that.snapshots.process({
                        execute: () => {
                            that.snapshots.messages.reset();
                            createSnapshot(formValues);
                        },
                    });
                })
            ;
        },
        restore() {
            var restoreSnapshot = function (prefix, reset) {
                isLoading(true);
                resetErrors();

                if (typeof reset === 'undefined') {
                    reset = false;
                }

                WPStaging.ajax(
                    {
                        action: 'wpstg--snapshots--restore',
                        accessToken: wpstg.accessToken,
                        nonce: wpstg.nonce,
                        wpstg: {
                            tasks: {
                                snapshot: {
                                    database: {
                                        create: {
                                            source: prefix,
                                            reset,
                                        },
                                    },
                                },
                            },
                        },
                    },
                    function (response) {
                        if (typeof response === 'undefined') {
                            setTimeout(function () {
                                restoreSnapshot(prefix);
                            }, wpstg.delayReq);
                            return;
                        }

                        that.snapshots.processResponse(response);

                        if (response.status === false || response.job_done === false) {
                            restoreSnapshot(prefix);
                        } else if (response.status === true && response.job_done === true) {
                            isLoading(false);
                            $('.wpstg--modal--process--title').text('Snapshot successfully restored');
                            setTimeout(() => {
                                Swal.close();
                                // noinspection JSIgnoredPromiseFromCall
                                that.snapshots.fetchListing();
                            }, 1000);
                        } else {
                            setTimeout(function () {
                                restoreSnapshot(prefix);
                            }, wpstg.delayReq);
                        }
                    },
                    'json',
                    false,
                    0,
                    1.25
                );
            };

            $('#wpstg--tab--snapshot')
                .off('click', '.wpstg--snapshot--restore[data-id]')
                .on('click', '.wpstg--snapshot--restore[data-id]', function (e) {
                    e.preventDefault();
                    resetErrors();
                    that.ajax(
                        {
                            action: 'wpstg--snapshots--restore--confirm',
                            accessToken: wpstg.accessToken,
                            nonce: wpstg.nonce,
                            id: $(this).data('id'),
                        },
                        function (data) {
                            cache.get('#wpstg--tab--snapshot').html(data);
                        },
                    );
                })
                .off('click', '#wpstg--snapshot--restore--cancel')
                .on('click', '#wpstg--snapshot--restore--cancel', function (e) {
                    resetErrors();
                    e.preventDefault();
                    // noinspection JSIgnoredPromiseFromCall
                    that.snapshots.fetchListing();
                })
                .off('click', '#wpstg--snapshot--restore[data-id]')
                .on('click', '#wpstg--snapshot--restore[data-id]', function (e) {
                    e.preventDefault();
                    resetErrors();
                    const id = this.getAttribute('data-id');
                    that.snapshots.process({
                        execute: () => {
                            that.snapshots.messages.reset();
                            restoreSnapshot(id, true);
                        },
                        isShowCancelButton: false,
                    });
                })
            ;
        },
        // Edit snapshots name and notes
        edit() {
          $('#wpstg--tab--snapshot')
            .off('click', '.wpstg--snapshot--edit[data-id]')
            .on('click', '.wpstg--snapshot--edit[data-id]', async function(e) {
              e.preventDefault();

              const $this = $(this);
              const name = $this.data('name');
              const notes = $this.data('notes');

              const { value: formValues } = await Swal.fire({
                title: '',
                html: `
                    <label id="wpstg-snapshot-edit-name">Backup Name</label>
                    <input id="wpstg-snapshot-edit-name-input" class="swal2-input" value="${name}">
                    <label>Additional Notes</label>
                    <textarea id="wpstg-snapshot-edit-notes-textarea" class="swal2-textarea">${notes}</textarea>
                  `,
                focusConfirm: false,
                confirmButtonText: 'Update Backup',
                showCancelButton: true,
                preConfirm: () => ({
                  name: document.getElementById('wpstg-snapshot-edit-name-input').value || null,
                  notes: document.getElementById('wpstg-snapshot-edit-notes-textarea').value || null,
                }),
              });

              if (!formValues) {
                return;
              }

              that.ajax(
                {
                  action: 'wpstg--snapshots--edit',
                  accessToken: wpstg.accessToken,
                  nonce: wpstg.nonce,
                  id: $this.data('id'),
                  name: formValues.name,
                  notes: formValues.notes,
                },
                function(response) {
                  showAjaxFatalError(response, '', 'Submit an error report.');
                    // noinspection JSIgnoredPromiseFromCall
                    that.snapshots.fetchListing();
                },
              );
            })
          ;
        },
        cancel() {
            that.snapshots.timer.stop();
            that.snapshots.isCancelled = true;
            Swal.close();
            setTimeout(() => that.ajax(
              {
                  action: 'wpstg--snapshots--cancel',
                  accessToken: wpstg.accessToken,
                  nonce: wpstg.nonce,
                  type: that.snapshots.type,
              },
              function(response) {
                  showAjaxFatalError(response, '', 'Submit an error report.');
              },
            ), 500);
        },
        /**
         * If process.execute exists, process.data and process.onResponse is not used
         * process = { data: {}, onResponse: (resp) => {}, onAfterClose: () => {}, execute: () => {}, isShowCancelButton: bool }
         * @param {object} process
         */
        process(process) {
            if (typeof process.execute !== 'function' && (!process.data || !process.onResponse)) {
                Swal.close();
                showError('process.data and / or process.onResponse is not set');
                return;
            }

            // TODO move to backend and get the contents as xhr response?
            if (!that.snapshots.modal.process.html || !that.snapshots.modal.process.cancelBtnTxt) {
                const $modal = $('#wpstg--modal--snapshot--process');
                const html = $modal.html();
                const btnTxt = $modal.attr('data-cancelButtonText');
                that.snapshots.modal.process.html = html || null;
                that.snapshots.modal.process.cancelBtnTxt = btnTxt || null;
                $modal.remove();
            }

            $('body')
              .off('click', '.wpstg--modal--process--logs--tail')
              .on('click', '.wpstg--modal--process--logs--tail', function(e) {
                  e.preventDefault();
                  const container = Swal.getContainer();
                  const $logs = $(container).find('.wpstg--modal--process--logs');
                  $logs.toggle();
                  if ($logs.is(':visible')) {
                      container.childNodes[0].style.width = '100%';
                      container.style['z-index'] = 9999;
                  } else {
                      container.childNodes[0].style.width = '600px';
                  }
              })
            ;

            process.isShowCancelButton = false !== process.isShowCancelButton;

            that.snapshots.modal.process.modal = Swal.mixin({
                customClass: {
                    cancelButton: 'wpstg--btn--cancel wpstg-blue-primary wpstg-link-btn',
                    content: 'wpstg--process--content',
                },
                buttonsStyling: false,
            }).fire({
                html: that.snapshots.modal.process.html,
                cancelButtonText: that.snapshots.modal.process.cancelBtnTxt,
                showCancelButton: process.isShowCancelButton,
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                width: 600,
                onRender: () => {
                    const _btnCancel = Swal.getContainer().getElementsByClassName('swal2-cancel wpstg--btn--cancel')[0];
                    const btnCancel = _btnCancel.cloneNode(true);
                    _btnCancel.parentNode.replaceChild(btnCancel, _btnCancel);

                    btnCancel.addEventListener('click', function(e) {
                        if (confirm('Are You Sure? This will cancel the process!')) {
                            Swal.close();
                        }
                    });

                    if (typeof process.execute === 'function') {
                        process.execute();
                        return;
                    }

                    if (!process.data || !process.onResponse) {
                        Swal.close();
                        showError('process.data and / or process.onResponse is not set');
                        return;
                    }

                    that.ajax(process.data, process.onResponse);
                },
                onAfterClose: () => typeof process.onAfterClose === 'function' && process.onAfterClose(),
                onClose: () => {
                    console.log('cancelled');
                    that.snapshots.cancel();
                }
            });
        },
        processResponse(response, useTitle) {
            if (response === null) {
                Swal.close();
                showError('Invalid Response; null');
                throw new Error(`Invalid Response; ${response}`);
            }

            const $container = $(Swal.getContainer());
            const title = () => {
                if ((response.title || response.statusTitle) && useTitle === true) {
                    $container.find('.wpstg--modal--process--title').text(response.title || response.statusTitle);
                }
            };
            const percentage = () => {
                if (response.percentage) {
                    $container.find('.wpstg--modal--process--percent').text(response.percentage);
                }
            };
            const logs = () => {
                if (!response.messages) {
                    return;
                }
                const $logsContainer = $container.find('.wpstg--modal--process--logs');
                const stoppingTypes = [
                  that.snapshots.messages.ERROR,
                  that.snapshots.messages.CRITICAL,
                ];
                const appendMessage = (message) => {
                    if (Array.isArray(message)) {
                        for (const item of message) {
                            appendMessage(item);
                        }
                        return;
                    }
                    const msgClass = `wpstg--modal--process--msg--${message.type.toLowerCase()}`;
                    $logsContainer.append(`<p class="${msgClass}">[${message.type}] - [${message.date}] - ${message.message}</p>`);

                    if (stoppingTypes.includes(message.type.toLowerCase())) {
                        that.snapshots.cancel();
                        setTimeout(that.snapshots.logsModal, 500);
                    }
                };
                for (const message of response.messages) {
                    if (!message) {
                        continue;
                    }
                    that.snapshots.messages.addMessage(message);
                    appendMessage(message);
                }

                if ($logsContainer.is(':visible')) {
                    $logsContainer.scrollTop($logsContainer[0].scrollHeight);
                }

                if (!that.snapshots.messages.shouldWarn()) {
                    return;
                }

                const $btnShowLogs = $container.find('.wpstg--modal--process--logs--tail');
                $btnShowLogs.html($btnShowLogs.attr('data-txt-bad'));

                $btnShowLogs
                  .find('.wpstg--modal--logs--critical-count')
                  .text(that.snapshots.messages.countByType(that.snapshots.messages.CRITICAL))
                ;

                $btnShowLogs
                  .find('.wpstg--modal--logs--error-count')
                  .text(that.snapshots.messages.countByType(that.snapshots.messages.ERROR))
                ;

                $btnShowLogs
                  .find('.wpstg--modal--logs--warning-count')
                  .text(that.snapshots.messages.countByType(that.snapshots.messages.WARNING))
                ;
            };

            title();
            percentage();
            logs();

            if (response.status === true && response.job_done === true) {
                that.snapshots.timer.stop();
                that.snapshots.isCancelled = true;
            }
        },
        requestData(notation, data) {
            const obj = {};
            const keys = notation.split('.');
            const lastIndex = keys.length - 1;
            keys.reduce((accumulated, current, index) => {
                return accumulated[current] = index >= lastIndex? data : {};
            }, obj);
            return obj;
        },
        logsModal() {
            Swal.fire({
                html: `<div class="wpstg--modal--error--logs" style="display:block"></div><div class="wpstg--modal--process--logs" style="display:block"></div>`,
                width: '95%',
                onRender: () => {
                    const $container = $(Swal.getContainer());
                    $container[0].style['z-index'] = 9999;

                    const $logsContainer = $container.find('.wpstg--modal--process--logs');
                    const $errorContainer = $container.find('.wpstg--modal--error--logs');
                    const $translations = $('#wpstg--js--translations');
                    const messages = that.snapshots.messages;
                    const title = $translations.attr('data-modal-logs-title')
                      .replace('{critical}', messages.countByType(messages.CRITICAL))
                      .replace('{errors}', messages.countByType(messages.ERROR))
                      .replace('{warnings}', messages.countByType(messages.WARNING))
                    ;

                    $errorContainer.before(`<h3>${title}</h3>`);
                    const warnings = [
                      that.snapshots.messages.CRITICAL,
                      that.snapshots.messages.ERROR,
                      that.snapshots.messages.WARNING,
                    ];

                    if (!that.snapshots.messages.shouldWarn()) {
                        $errorContainer.hide();
                    }

                    for (const message of messages.data.all) {
                        const msgClass = `wpstg--modal--process--msg--${message.type.toLowerCase()}`;
                        // TODO RPoC
                        if (warnings.includes(message.type)) {
                            $errorContainer.append(
                              `<p class="${msgClass}">[${message.type}] - [${message.date}] - ${message.message}</p>`
                            );
                        }
                        $logsContainer.append(
                          `<p class="${msgClass}">[${message.type}] - [${message.date}] - ${message.message}</p>`
                        );
                    }
                },
                onOpen: (container) => {
                    const $logsContainer = $(container).find('.wpstg--modal--process--logs');
                    $logsContainer.scrollTop($logsContainer[0].scrollHeight);
                },
            });
        },
        downloadModal({ title = null, titleExport = null,  id = null, url = null, btnTxtCancel = 'Cancel', btnTxtConfirm = 'Download' }) {

            if (null === that.snapshots.modal.download.html) {
                const $el = $('#wpstg--modal--snapshot--download');
                that.snapshots.modal.download.html = $el.html();
                $el.remove();
            }

            const exportModal = () => Swal.fire({
                html: `<h2>${titleExport}</h2><span class="wpstg-loader"></span>`,
                showCancelButton: false,
                showConfirmButton: false,
                onRender: () => {
                    that.ajax(
                      {
                          action: 'wpstg--snapshots--export',
                          accessToken: wpstg.accessToken,
                          nonce: wpstg.nonce,
                          id,
                      },
                      function (response) {
                          if (!response || !response.success || !response.data || response.data.length < 1) {
                              return;
                          }

                          const a = document.createElement('a');
                          a.style.display = 'none';
                          a.href = response.data;
                          document.body.appendChild(a);
                          a.click();
                          document.body.removeChild(a);

                          Swal.close();
                      },
                    );
                },
            });

            Swal.mixin({
                customClass: {
                    cancelButton: 'wpstg--btn--cancel wpstg-blue-primary wpstg-link-btn',
                    confirmButton: 'wpstg--btn--confirm wpstg-blue-primary wpstg-button wpstg-link-btn',
                    actions: 'wpstg--modal--actions',
                },
                buttonsStyling: false,
            })
              .fire({
                  icon: 'success',
                  html: that.snapshots.modal.download.html.replace('{title}', title).replace('{btnTxtLog}', 'Show Logs'),
                  cancelButtonText: btnTxtCancel,
                  confirmButtonText: btnTxtConfirm,
                  showCancelButton: true,
                  showConfirmButton: true,
              })
              .then(isConfirm => {
                  if (!isConfirm || !isConfirm.value) {
                      return;
                  }

                  if (url && url.length > 0) {
                      window.location.href = url;
                      return;
                  }

                  exportModal();
              })
            ;
        },
        importModal() {

            const restoreSiteSnapshot = (data) => {
                resetErrors();

                if (that.snapshots.isCancelled) {
                    console.log('cancelled');
                    // Swal.close();
                    return;
                }

                const reset = data['reset'];
                delete data['reset'];
                data['mergeMediaFiles'] = 1; // always merge for uploads / media
                let requestData = Object.assign({}, data);

                requestData = that.snapshots.requestData(
                  'jobs.snapshot.site.restore',
                  { ...that.snapshots.modal.import.data }
                );

                that.snapshots.timer.start();

                const statusStop = () => {
                    console.log('Status: Stop');
                    clearInterval(that.snapshots.processInfo.interval);
                    that.snapshots.processInfo.interval = null;
                };
                const status = () => {
                    if (that.snapshots.processInfo.interval !== null) {
                        return;
                    }
                    console.log('Status: Start');
                    that.snapshots.processInfo.interval = setInterval(() => {
                        if (true === that.snapshots.isCancelled) {
                            statusStop();
                            return;
                        }

                        if (that.snapshots.status.hasResponse === false) {
                            return;
                        }

                        that.snapshots.status.hasResponse = false;
                        fetch(`${ajaxurl}?action=wpstg--snapshots--status&process=restore&accessToken=${wpstg.accessToken}&nonce=${wpstg.nonce}`)
                          .then(res => res.json())
                          .then(res => {
                              that.snapshots.status.hasResponse = true;
                              if (typeof res === 'undefined') {
                                  statusStop();
                              }

                              if (that.snapshots.processInfo.title === res.currentStatusTitle) {
                                  return;
                              }

                              that.snapshots.processInfo.title = res.currentStatusTitle;
                              const $container = $(Swal.getContainer());
                              $container.find('.wpstg--modal--process--title').text(res.currentStatusTitle);
                              $container.find('.wpstg--modal--process--percent').text('0');
                          })
                          .catch(e => {
                              that.snapshots.status.hasResponse = true;
                              showAjaxFatalError(e, '', 'Submit an error report.');
                          })
                        ;
                    }, 5000);
                };

                WPStaging.ajax(
                  {
                      action: 'wpstg--snapshots--site--restore',
                      accessToken: wpstg.accessToken,
                      nonce: wpstg.nonce,
                      reset,
                      wpstg: requestData,
                  },
                  function (response) {
                      if (typeof response === 'undefined') {
                          setTimeout(function () {
                              restoreSiteSnapshot(data);
                          }, wpstg.delayReq);
                          return;
                      }

                      that.snapshots.processResponse(response, true);
                      if (!that.snapshots.processInfo.interval) {
                          status();
                      }

                      if (response.status === false){
                          restoreSiteSnapshot(data);
                      } else if (response.status === true) {
                          $('#wpstg--progress--status').text('Snapshot successfully restored!');
                          that.snapshots.type = null;
                          if (that.snapshots.messages.shouldWarn()) {
                              // noinspection JSIgnoredPromiseFromCall
                              that.snapshots.fetchListing();
                              that.snapshots.logsModal();
                              return;
                          }
                          statusStop();
                          var logEntries = $(".wpstg--modal--process--logs").get(1).innerHTML;
                          var html = '<div class="wpstg--modal--process--logs">' + logEntries + '</div>';
                          var issueFound = html.includes('wpstg--modal--process--msg--warning') || html.includes('wpstg--modal--process--msg--error') ? 'Issues(s) found! ' : '';
                          console.log('errors found: ' + issueFound);
                          //var errorMessage = html.includes('wpstg--modal--process--msg--error') ? 'Errors(s) found! ' : '';
                          //var Message = warningMessage + errorMessage;

                          //Swal.close();
                              Swal.fire({
                                  icon: 'success',
                                  title: 'Finished',
                                  html: 'System restored from snapshot. <br/><span class="wpstg--modal--process--msg-found">'+issueFound+'</span><button class="wpstg--modal--process--logs--tail" data-txt-bad="">Show Logs</button><br/>' + html,
                              }
                          );

                          // noinspection JSIgnoredPromiseFromCall
                          that.snapshots.fetchListing();
                      } else {
                          setTimeout(function () {
                              restoreSiteSnapshot(data);
                          }, wpstg.delayReq);
                      }
                  },
                  'json',
                  false,
                  0, // Don't retry upon failure
                  1.25
                );
            }

            if (!that.snapshots.modal.import.html) {
                const $modal = $('#wpstg--modal--snapshot--import');

                // Search & Replace Form
                const $form = $modal.find('.wpstg--modal--snapshot--import--search-replace--input--container');
                that.snapshots.modal.import.searchReplaceForm = $form.html();
                $form.find('.wpstg--modal--snapshot--import--search-replace--input-group').remove();
                $form.html(that.snapshots.modal.import.searchReplaceForm.replace(/{i}/g, 0));

                that.snapshots.modal.import.html = $modal.html();
                that.snapshots.modal.import.baseDirectory = $modal.attr('data-baseDirectory');
                that.snapshots.modal.import.btnTxtNext = $modal.attr('data-nextButtonText');
                that.snapshots.modal.import.btnTxtConfirm = $modal.attr('data-confirmButtonText');
                that.snapshots.modal.import.btnTxtCancel = $modal.attr('data-cancelButtonText');
                $modal.remove();
            }

            that.snapshots.modal.import.data.search = [];
            that.snapshots.modal.import.data.replace = [];

            let $btnConfirm = null;
            Swal
              .mixin({
                customClass: {
                    confirmButton: 'wpstg--btn--confirm wpstg-blue-primary wpstg-button wpstg-link-btn',
                    cancelButton: 'wpstg--btn--cancel wpstg-blue-primary wpstg-link-btn',
                    actions: 'wpstg--modal--actions',
                },
                buttonsStyling: false,
                //progressSteps: ['1', '2']
            })
              .queue([{
                  html: that.snapshots.modal.import.html,
                  confirmButtonText: that.snapshots.modal.import.btnTxtNext,
                  showCancelButton: false,
                  showConfirmButton: true,
                  showLoaderOnConfirm: true,
                  width: 650,
                  onRender() {
                      $btnConfirm = $('.wpstg--modal--actions .swal2-confirm');
                      $btnConfirm.prop('disabled', true);

                      that.snapshots.modal.import.containerUpload = $('.wpstg--modal--snapshot--import--upload');
                      that.snapshots.modal.import.containerFilesystem = $('.wpstg--modal--snapshot--import--filesystem');
                  },
                  preConfirm() {
                      const body = new FormData;
                      body.append('accessToken', wpstg.accessToken);
                      body.append('nonce', wpstg.nonce);
                      body.append('filePath', that.snapshots.modal.import.data.file);

                      that.snapshots.modal.import.data.search.forEach((item, index) => {
                          body.append(`search[${index}]`, item);
                      });
                      that.snapshots.modal.import.data.replace.forEach((item, index) => {
                          body.append(`replace[${index}]`, item);
                      });

                      return fetch(`${ajaxurl}?action=wpstg--snapshots--import--file-info`, {
                        method: 'POST',
                        body,
                      }).then(handleFetchErrors)
                        .then(res => res.json())
                        .then(html => {
                            return Swal.insertQueueStep({
                                html: html,
                                confirmButtonText: that.snapshots.modal.import.btnTxtConfirm,
                                cancelButtonText: that.snapshots.modal.import.btnTxtCancel,
                                showCancelButton: true,
                            });
                        })
                        .catch(e => showAjaxFatalError(e, '', 'Submit an error report.'))
                      ;
                  },
              }])
              .then(res => {
                  if (!res || !res.value || !res.value[1] || res.value[1] !== true) {
                      return;
                  }

                  that.snapshots.isCancelled = false;
                  const data = that.snapshots.modal.import.data;
                  data['file'] = that.snapshots.modal.import.baseDirectory + data['file'];
                  data['reset'] = true;

                  that.snapshots.process({
                      execute: () => {
                          that.snapshots.messages.reset();
                          restoreSiteSnapshot(data);
                      },
                  });
              })
            ;
        },
    };

    return that;
})(jQuery);

jQuery(document).ready(function () {
    WPStaging.init();
});

/**
 * Report Issue modal
 */
jQuery(document).ready(function ($) {

    $('#wpstg-report-issue-button').click(function (e) {
        $('.wpstg-report-issue-form').toggleClass('wpstg-report-show');
        e.preventDefault();
    });

    $('body').on('click', '#wpstg-snapshots-report-issue-button', function (e) {
        $('.wpstg-report-issue-form').toggleClass('wpstg-report-show');
        console.log('test');
        e.preventDefault();
    });

    $('#wpstg-report-cancel').click(function (e) {
        $('.wpstg-report-issue-form').removeClass('wpstg-report-show');
        e.preventDefault();
    });

    /*
     * Close Success Modal
     */

    $('body').on('click', '#wpstg-success-button', function (e) {
        e.preventDefault();
        $('.wpstg-report-issue-form').removeClass('wpstg-report-show');
    });

    function sendIssueReport(button, forceSend = 'false') {
        var spinner = button.next();
        var email = $('.wpstg-report-email').val();
        var hosting_provider = $('.wpstg-report-hosting-provider').val();
        var message = $('.wpstg-report-description').val();
        var syslog = $('.wpstg-report-syslog').is(':checked');
        var terms = $('.wpstg-report-terms').is(':checked');

        button.attr('disabled', true);
        spinner.css('visibility', 'visible');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            async: true,
            data: {
                'action': 'wpstg_send_report',
                'accessToken': wpstg.accessToken,
                'nonce': wpstg.nonce,
                'wpstg_email': email,
                'wpstg_provider': hosting_provider,
                'wpstg_message': message,
                'wpstg_syslog': +syslog,
                'wpstg_terms': +terms,
                'wpstg_force_send': forceSend
            },
        }).done(function (data) {
            button.attr('disabled', false);
            spinner.css('visibility', 'hidden');

            if (data.errors.length > 0) {
                $('.wpstg-report-issue-form .wpstg-message').remove();

                var errorMessage = $('<div />').addClass('wpstg-message wpstg-error-message');
                $.each(data.errors, function (key, value) {
                    if (value.status === 'already_submitted') {
                        errorMessage = '';
                        Swal.fire({
                            title: '',
                            customClass: {
                                container: 'wpstg-issue-resubmit-confirmation'
                            },
                            icon: 'warning',
                            html: value.message,
                            showCloseButton: true,
                            showCancelButton: true,
                            focusConfirm: false,
                            confirmButtonText: 'Yes',
                            cancelButtonText: 'No'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                sendIssueReport(button, 'true');
                            }
                        })
                    } else {
                        errorMessage.append('<p>' + value + '</p>');
                    }                    
                });

                $('.wpstg-report-issue-form').prepend(errorMessage);
            } else {
                var successMessage = $('<div />').addClass('wpstg-message wpstg-success-message');
                successMessage.append('<p>Thanks for submitting your request! You should receive an auto reply mail with your ticket ID immediately for confirmation!<br><br>If you do not get that mail please contact us directly at <strong>support@wp-staging.com</strong></p>');

                $('.wpstg-report-issue-form').html(successMessage);
                $('.wpstg-success-message').append('<div style="float:right;margin-top:10px;"><a id="wpstg-success-button" href="#">Close</a></div>');

                // Hide message
                setTimeout(function () {
                    $('.wpstg-report-issue-form').removeClass('wpstg-report-active');
                }, 2000);
            }
        });
    }

    $('#wpstg-report-submit').click(function (e) {
        var self = $(this);
        sendIssueReport(self, 'false');
        e.preventDefault();
    });

});