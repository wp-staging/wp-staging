"use strict";

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
    };

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
            return;
        }

        if (typeof response.error !== 'undefined' && response.error) {
            console.error(response.message);
            showError(prependMessage + ' Error: ' + response.message + appendMessage);
            return;
        }
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
        ;

        cloneActions();
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
                        nonce: wpstg.nonce,
                        clone: $(this).data("clone")
                    },
                    function (response) {
                        cache.get("#wpstg-removing-clone").html(response);

                        $existingClones.children("img").remove();

                        cache.get(".wpstg-loader").hide();
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
                        nonce: wpstg.nonce
                    },
                    function (response) {
                        if (response.length < 1) {
                            showError(
                                "Something went wrong! Error: No response.  Please try the <a href='https://wp-staging.com/docs/wp-staging-settings-for-small-servers/' target='_blank'>WP Staging Small Server Settings</a> or submit an error report and contact us."
                            );
                        }

                        $workFlow.removeClass("loading").html(response);

                        cache.get(".wpstg-current-step")
                            .removeClass("wpstg-current-step")
                            .next("li")
                            .addClass("wpstg-current-step");
                    },
                    "HTML"
                );
            });
    };

    /**
     * Ajax Requests
     * @param {Object} data
     * @param {Function} callback
     * @param {String} dataType
     * @param {Boolean} showErrors
     */
    var ajax = function (data, callback, dataType, showErrors, tryCount) {
        if ("undefined" === typeof (dataType)) {
            dataType = "json";
        }

        if (false !== showErrors) {
            showErrors = true;
        }

        var tryCount = "undefined" === typeof (tryCount) ? 0 : tryCount;

        var retryLimit = 10;

        var retryTimeout = 10000 * tryCount;

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
                        ajax(data, callback, dataType, showErrors, tryCount);
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
                404: function (data) {
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

                // Add loading overlay
                $workFlow.addClass("loading");

                // Prepare data
                that.data = {
                    action: $this.data("action"),
                    nonce: wpstg.nonce
                };

                // Cloning data
                getCloningData();

                console.log(that.data);

                isScan = ("wpstg_scanning" === that.action);

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
                        $workFlow.removeClass("loading").html(response);

                        cache.get(".wpstg-current-step")
                            .removeClass("wpstg-current-step")
                            .next("li")
                            .addClass("wpstg-current-step");

                        // Start cloning
                        that.startCloning();

                    },
                    "HTML"
                );
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
                return false;
            }

            isLoading(true);

            // Show logging window
            cache.get('.wpstg-log-details').show();

            WPStaging.ajax(
                {
                    action: "wpstg_processing",
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
                cache.get("#wpstg-progress-sr").width(response.percentage * 0.1 + '%').html(response.percentage + '%');
                cache.get("#wpstg-processing-status").html(response.percentage.toFixed(0) + '%' + ' - Step 2 of 4 Preparing Database Data...');
            }

            if (response.job === 'directories') {
                cache.get("#wpstg-progress-sr").css('background-color', '#3bc36b');
                cache.get("#wpstg-progress-sr").html('2. Data');
                cache.get("#wpstg-progress-dirs").width(response.percentage * 0.1 + '%').html(response.percentage + '%');
                cache.get("#wpstg-processing-status").html(response.percentage.toFixed(0) + '%' + ' - Step 3 of 4 Getting files...');
            }
            if (response.job === 'files') {
                cache.get("#wpstg-progress-dirs").css('background-color', '#3bc36b');
                cache.get("#wpstg-progress-dirs").html('3. Files');
                cache.get("#wpstg-progress-files").width(response.percentage * 0.6 + '%').html(response.percentage + '%');
                cache.get("#wpstg-processing-status").html(response.percentage.toFixed(0) + '%' + ' - Step 4 of 4 Copy files...');
            }
            if (response.job === 'finish') {
                cache.get("#wpstg-progress-files").css('background-color', '#3bc36b');
                cache.get("#wpstg-progress-files").html('4. Copy Files');
                cache.get("#wpstg-processing-status").html(response.percentage.toFixed(0) + '%' + ' - Cloning Process Finished');
            }
        }


    });

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

    that.snapshots = {
        init() {
            this.fetchListing();
            this.create();
            this.delete();
            this.restore();
            this.export();
            this.edit();
        },
        fetchListing() {
            isLoading(true);
            resetErrors();
            that.ajax(
                {
                    action: 'wpstg--snapshots--listing',
                    nonce: wpstg.nonce,
                },
                function (response) {
                    showAjaxFatalError(response, '', 'Submit an error report.');
                    cache.get('#wpstg--tab--snapshot').html(response);
                    isLoading(false);
                },
            );
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
                            nonce: wpstg.nonce,
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
                            nonce: wpstg.nonce,
                        },
                        function (response) {
                            showAjaxFatalError(response, '', ' Please submit an error report by using the REPORT ISSUE button.');
                            that.snapshots.fetchListing();
                            isLoading(false);
                        },
                    );
                })
                .off('click', '#wpstg-cancel-snapshot-delete')
                .on('click', '#wpstg-cancel-snapshot-delete', function (e) {
                    e.preventDefault();
                    isLoading(false);
                    var id = this.getAttribute('data-id');
                    that.snapshots.fetchListing();
                })
            ;
        },
        create() {
            var createSnapshot = function (name, notes) {
                isLoading(true);
                resetErrors();
                WPStaging.ajax(
                    {
                        action: 'wpstg--snapshots--create',
                        nonce: wpstg.nonce,
                        name,
                        notes,
                    },
                    function (response) {
                        if (typeof response === 'undefined') {
                            setTimeout(function () {
                                createSnapshot(name, notes);
                            }, wpstg.delayReq);
                            return;
                        }

                        showAjaxFatalError(response, '', 'Submit an error report and contact us.');

                        if (typeof response.last_msg !== 'undefined' && response.last_msg) {
                            getLogs(response.last_msg);
                        }

                        if (response.status === false) {
                            createSnapshot(name, notes);
                        } else if (response.status === true) {
                            isLoading(false);
                            $('#wpstg--progress--status').text('Snapshot successfully created!');
                            that.snapshots.fetchListing();
                        } else {
                            setTimeout(function () {
                                createSnapshot(name, notes);
                            }, wpstg.delayReq);
                        }
                    },
                    'json',
                    false
                );
            };
            // Add snapshot name and notes
            $('#wpstg--tab--snapshot')
                .off('click', '#wpstg-new-snapshot')
                .on('click', '#wpstg-new-snapshot', async function(e) {
                    resetErrors();
                    e.preventDefault();

                    const { value: formValues } = await Swal.fire({
                        title: '',
                        html: `
                          <label id="wpstg-snapshot-name">Snapshot Name</label>
                          <input id="wpstg-snapshot-name-input" class="swal2-input" placeholder="Name your snapshot for better distinction">
                          <label>Additional Notes</label>
                          <textarea id="wpstg-snapshot-notes-textarea" class="swal2-textarea" placeholder="Add an optional description e.g.: 'before push of staging site', 'before updating plugin XY'"></textarea>
                        `,
                        focusConfirm: false,
                        confirmButtonText: 'Take New Snapshot',
                        showCancelButton: true,
                        preConfirm: () => ({
                          name: document.getElementById('wpstg-snapshot-name-input').value || null,
                          notes: document.getElementById('wpstg-snapshot-notes-textarea').value || null,
                        }),
                    });

                    if (!formValues) {
                      return;
                    }

                    that.ajax(
                        {
                            action: 'wpstg--snapshots--create--progress',
                            nonce: wpstg.nonce,
                        },
                        function (response) {
                            showAjaxFatalError(response, '', 'Submit an error report and contact us.');
                            cache.get('#wpstg--tab--snapshot').html(response);
                          createSnapshot(formValues.name, formValues.notes);
                        },
                    );
                })
            ;
        },
        restore() {
            var restoreSnapshot = function (id, isReset) {
                isLoading(true);
                resetErrors();

                if (typeof isReset === 'undefined') {
                    isReset = false;
                }

                WPStaging.ajax(
                    {
                        action: 'wpstg--snapshots--restore',
                        nonce: wpstg.nonce,
                        id: id,
                        isReset: isReset,
                    },
                    function (response) {
                        if (typeof response === 'undefined') {
                            setTimeout(function () {
                                restoreSnapshot(id);
                            }, wpstg.delayReq);
                            return;
                        }

                        showAjaxFatalError(response, '', 'Submit an error report and contact us.');

                        if (typeof response.last_msg !== 'undefined' && response.last_msg) {
                            getLogs(response.last_msg);
                        }

                        if (response.status === false || response.job_done === false) {
                            restoreSnapshot(id);
                        } else if (response.status === true && response.job_done === true) {
                            isLoading(false);
                            $('#wpstg--progress--status').text('Snapshot successfully restored');
                        } else {
                            setTimeout(function () {
                                restoreSnapshot(id);
                            }, wpstg.delayReq);
                        }
                    },
                    'json',
                    false
                );
            };

            // Force delete if snapshot tables do not exist
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
                            force: 1,
                            nonce: wpstg.nonce,
                        },
                        function (response) {
                            showAjaxFatalError(response, '', ' Please submit an error report by using the REPORT ISSUE button.');
                            that.snapshots.fetchListing();
                            isLoading(false);
                        },
                    );
                })

            $('#wpstg--tab--snapshot')
                .off('click', '.wpstg--snapshot--restore[data-id]')
                .on('click', '.wpstg--snapshot--restore[data-id]', function (e) {
                    e.preventDefault();
                    resetErrors();
                    that.ajax(
                        {
                            action: 'wpstg--snapshots--restore--confirm',
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
                    that.snapshots.fetchListing();
                })
                .off('click', '#wpstg--snapshot--restore[data-id]')
                .on('click', '#wpstg--snapshot--restore[data-id]', function (e) {
                    e.preventDefault();
                    resetErrors();
                    var id = $(this).data('id');

                    that.ajax(
                        {
                            action: 'wpstg--snapshots--restore--progress',
                            nonce: wpstg.nonce,
                            id: id,
                        },
                        function (response) {
                            showAjaxFatalError(response, '', 'Submit an error report and contact us.');
                            cache.get('#wpstg--tab--snapshot').html(response);
                            restoreSnapshot(id, true);
                        },
                    );
                })
            ;
        },
        export() {
            function download(url) {
                var a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }

            $('#wpstg--tab--snapshot')
                .off('click', '.wpstg--snapshot--export')
                .on('click', '.wpstg--snapshot--export', function (e) {
                    e.preventDefault();
                    isLoading(true);
                    that.ajax(
                        {
                            action: 'wpstg--snapshots--export',
                            nonce: wpstg.nonce,
                            id: $(this).data('id'),
                        },
                        function (response) {
                            showAjaxFatalError(response, '', 'Submit an error report and contact us.');
                            isLoading(false);
                            if (response && response.success && response.data && response.data.length > 0) {
                                download(response.data);
                            }
                        },
                    );
                })
            ;
        },
        // Edit snapshots name and notes
        edit() {
          $('#wpstg--tab--snapshot')
            .off('click', '.wpstg--snapshot--edit[data-id]')
            .on('click', '.wpstg--snapshot--edit[data-id]', async function(e) {
              e.preventDefault();
              console.log('edit');

              const $this = $(this);
              const name = $this.data('name');
              const notes = $this.data('notes');

              const { value: formValues } = await Swal.fire({
                title: '',
                html: `
                    <label id="wpstg-snapshot-name">Snapshot Name</label>
                    <input id="wpstg-snapshot-name-input" class="swal2-input" value="${name}">
                    <label>Additional Notes</label>
                    <textarea id="wpstg-snapshot-notes-textarea" class="swal2-textarea">${notes}</textarea>
                  `,
                focusConfirm: false,
                confirmButtonText: 'Update Snapshot',
                showCancelButton: true,
                preConfirm: () => ({
                  name: document.getElementById('wpstg-snapshot-name-input').value || null,
                  notes: document.getElementById('wpstg-snapshot-notes-textarea').value || null,
                }),
              });

              if (!formValues) {
                return;
              }

              that.ajax(
                {
                  action: 'wpstg--snapshots--edit',
                  nonce: wpstg.nonce,
                  id: $this.data('id'),
                  name: formValues.name,
                  notes: formValues.notes,
                },
                function(response) {
                  showAjaxFatalError(response, '', 'Submit an error report.');
                  that.snapshots.fetchListing();
                },
              );
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

    $('#wpstg-report-submit').click(function (e) {
        var self = $(this);

        var spinner = self.next();
        var email = $('.wpstg-report-email').val();
        var message = $('.wpstg-report-description').val();
        var syslog = $('.wpstg-report-syslog').is(':checked');
        var terms = $('.wpstg-report-terms').is(':checked');

        self.attr('disabled', true);
        spinner.css('visibility', 'visible');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            async: true,
            data: {
                'action': 'wpstg_send_report',
                'nonce': wpstg.nonce,
                'wpstg_email': email,
                'wpstg_message': message,
                'wpstg_syslog': +syslog,
                'wpstg_terms': +terms
            },
        }).done(function (data) {
            self.attr('disabled', false);
            spinner.css('visibility', 'hidden');

            if (data.errors.length > 0) {
                $('.wpstg-report-issue-form .wpstg-message').remove();

                var errorMessage = $('<div />').addClass('wpstg-message wpstg-error-message');
                $.each(data.errors, function (key, value) {
                    errorMessage.append('<p>' + value + '</p>');
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

        e.preventDefault();
    });

});
