"use strict";

var WPStaging = (function($)
{
    var that        = {},
        cache       = {elements : []};

    /**
     * Get / Set Cache for Selector
     * @param {String} selector
     * @returns {*}
     */
    cache.get   = function(selector)
    {
        // It is already cached!
        if ($.inArray(selector, cache.elements) !== -1)
        {
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
    cache.refresh = function(selector)
    {
        selector.elements[selector] = jQuery(selector);
    };

    /**
     * Show and Log Error Message
     * @param {String} message
     */
    var showError = function(message)
    {
        cache.get("#wpstg-try-again").css("display", "inline-block");
        cache.get("#wpstg-cancel-cloning").text("Reset");
        cache.get("#wpstg-cloning-result").text("Fail");
        cache.get("#wpstg-error-wrapper").show();
        cache.get("#wpstg-error-details")
            .show()
            .html(message);

        cache.get("#wpstg-loader").hide();

        // Log error message on
        if (false === wpstg.settings.isDebugMode)
        {
            return;
        }

        $.post(
            ajaxurl,
            {
                action  : "wpstg_error_processing",
                message : message
            }
        );
    };

    /**
     * Common Elements
     */
    var elements = function()
    {
        var $workFlow       = cache.get("#wpstg-workflow"),
            isAllChecked    = true,
            urlSpinner      = ajaxurl.replace("/admin-ajax.php", '') + "/images/spinner",
            timer, ajaxSpinner;

        if (2 < window.devicePixelRatio)
        {
            urlSpinner += "-2x";
        }

        urlSpinner += ".gif";

        ajaxSpinner = "<img src=''" + urlSpinner + "' alt='' class='ajax-spinner general-spinner' />";

        $workFlow
            // Check / Un-check Database Tables
            .on("click", ".wpstg-button-unselect", function (e) {
                e.preventDefault();

                if (false === isAllChecked)
                {
                    cache.get(".wpstg-db-table-checkboxes").prop("checked", true);
                    cache.get(".wpstg-button-unselect").text("Un-check All");
                    isAllChecked = true;
                }
                else
                {
                    cache.get(".wpstg-db-table-checkboxes").prop("checked", false);
                    cache.get(".wpstg-button-unselect").text("Check All");
                    isAllChecked = false;
                }
            })
            // Expand Directories
            .on("click", ".wpstg-expand-dirs", function (e) {
                e.preventDefault();

                var $this = $(this);

                if (!$this.hasClass("disabled"))
                {
                    $this.siblings(".wpstg-subdir").slideToggle();
                }
            })
            // When a Directory is Selected
            .on("change", ".wpstg-check-dir", function () {
                var $directory = $(this).parent(".wpstg-dir");

                if (this.checked)
                {
                    $directory.parents(".wpstg-dir").children(".wpstg-check-dir").prop("checked", true);
                    $directory.find(".wpstg-expand-dirs").removeClass("disabled");
                    $directory.find(".wpstg-subdir .wpstg-check-dir").prop("checked", true);
                }
                else
                {
                    $directory.find(".wpstg-dir .wpstg-check-dir").prop("checked", false);
                    $directory.find(".wpstg-expand-dirs, .wpstg-check-subdirs").addClass("disabled");
                    $directory.find(".wpstg-check-subdirs").data("action", "check").text("check");
                    $directory.children(".wpstg-subdir").slideUp();
                }
            })
            // Check the max length of the clone name and if the clone name already exists
            .on("keyup", "#wpstg-new-clone-id", function () {

                // This request was already sent, clear it up!
                if ("number" === typeof(timer))
                {
                    clearInterval(timer);
                }

                var cloneID = this.value;

                timer = setTimeout(
                    function() {
                        ajax(
                            {
                                action  : "wpstg_check_clone",
                                cloneID : cloneID
                            },
                            function(response)
                            {
                                if (response.status === "success")
                                {
                                    cache.get("#wpstg-new-clone-id").removeClass("wpstg-error-input");
                                    cache.get("#wpstg-start-cloning").removeAttr("disabled");
                                    cache.get("#wpstg-clone-id-error").text('');
                                }
                                else
                                {
                                    cache.get("#wpstg-new-clone-id").addClass("wpstg-error-input");
                                    cache.get("#wpstg-start-cloning").prop("disabled", true);
                                    cache.get("#wpstg-clone-id-error").text(response.message);
                                }
                            },
                            "json"
                        );
                    },
                    500
                );
            });
    };

    var ajax    = function(data, callback, dataType)
    {
        if ("undefined" === typeof(dataType))
        {
            dataType = "HTML";
        }

        $.ajax({
            url         : ajaxurl,
            type        : "POST",
            dataType    : dataType,
            cache       : false,
            data        : data,
            error       : function(xhr, textStatus, errorThrown) {
                console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus);
                console.log(textStatus);

                showError(
                    "Fatal Error: This should not happen but is most often caused by other plugins. " +
                    "Try first the option 'Optimizer' in WP Staging->Settings and try again. " +
                    "If this does not help, enable " +
                    "<a href='https://codex.wordpress.org/Debugging_in_WordPress' target='_blank'>wordpress debug mode</a> " +
                    "to find out which plugin is causing this."
                );
            },
            success     : function(data) {
                if ("function" === typeof(callback))
                {
                    callback(data);
                }
            },
            statusCode  : {
                404: function() {
                    showError("Something went wrong; can't find ajax request URL!");
                },
                500: function() {
                    showError("Something went wrong; internal server error while processing the request!");
                }
            }
        });
    };

    /**
     * Next Step Clicks to Navigate Through Staging Job
     */
    var nextStep = function()
    {
        var $workFlow = cache.get("#wpstg-workflow");

        $workFlow.on("click", ".wpstg-next-step-link", function(e) {
            e.preventDefault();

            var $this = $(this);

            // Button is disabled
            if ($this.attr("disabled"))
            {
                return false;
            }

            // Add loading overlay
            $workFlow.addClass("loading");

            // Prepare data
            that.data = {
                action  : $this.data("action"),
                nonce   : wpstg.nonce
            };

            // Cloning data
            getCloningData();

            console.log(that.data);

            // Send ajax request
            ajax(
                that.data,
                function(response) {
                    var $currentStep = cache.get(".wpstg-current-step");

                    // Styling of elements
                    $workFlow.removeClass("loading").html(response);

                    $currentStep
                        .removeClass("wpstg-current-step")
                        .next("li")
                        .addClass("wpstg-current-step");

                    // Start cloning
                    that.startCloning();
                }
            );
        });

        /**
         * Get Excluded (Unchecked) Database Tables
         * @returns {Array}
         */
        var getExcludedTables = function()
        {
            var excludedTables = [];

            $(".wpstg-db-table input:not(:checked)").each(function () {
                excludedTables.push(this.name);
            });

            return excludedTables;
        };

        /**
         * Get Excluded Directories
         * @returns {Array}
         */
        var getExcludedDirectories = function()
        {
            var excludedDirectories = [];

            $('.wpstg-dir input:not(:checked)').each(function () {
                if (!$(this).parent('.wpstg-dir').parents('.wpstg-dir').children('.wpstg-expand-dirs').hasClass('disabled'))
                    excludedDirectories.push(this.name);
            });

            return excludedDirectories;
        };

        /**
         * Get Cloning Step Data
         * @param {Object} data
         */
        var getCloningData = function()
        {
            if ("wpstg_cloning" !== that.data.action)
            {
                return;
            }

            that.data.cloneID               = $("#wpstg-new-clone-id").val() || new Date().getTime().toString();
            that.data.excludedTables        = getExcludedTables();
            that.data.excludedDirectories   = getExcludedDirectories();
            that.data.extraDirectories      = $("#wpstg_extraDirectories").val() || null;
        };
    };

    /**
     * Tabs
     */
    var tabs = function()
    {
        cache.get("#wpstg-workflow").on("click", ".wpstg-tab-header", function(e) {
            e.preventDefault();

            var $this       = $(this),
                $section    = cache.get($this.data("id"));

            $this.toggleClass("expand");

            $section.slideToggle();

            if ($this.hasClass("expand"))
            {
                $this.find(".wpstg-tab-triangle").html("&#9660;");
            }
            else
            {
                $this.find(".wpstg-tab-triangle").html("&#9658;");
            }
        });
    };

    /**
     * Start Cloning Process
     * @type {Function}
     */
    that.startCloning = (function() {
        console.log("Staring cloning process...");
    });

    /**
     * Initiation
     * @type {Function}
     */
    that.init = (function() {
        console.log("Initiating WPStaging...");
        elements();
        nextStep();
        tabs();
    });

    return that;
})(jQuery);

jQuery(document).ready(function() {
    WPStaging.init();
});

// Load twitter button async
window.twttr = (function (d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0],
        t = window.twttr || {};
    if (d.getElementById(id))
        return t;
    js = d.createElement(s);
    js.id = id;
    js.src = "https://platform.twitter.com/widgets.js";
    fjs.parentNode.insertBefore(js, fjs);

    t._e = [];
    t.ready = function (f) {
        t._e.push(f);
    };

    return t;
}(document, "script", "twitter-wjs"));