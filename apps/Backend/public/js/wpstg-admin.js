"use strict";

var WPStaging = (function ($)
{
    var that = {
        isCancelled: false,
        isFinished: false,
        getLogs: false
    },
    cache = {elements: []},
    timeout, ajaxSpinner;

    /**
     * Get / Set Cache for Selector
     * @param {String} selector
     * @returns {*}
     */
    cache.get = function (selector)
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
    cache.refresh = function (selector)
    {
        selector.elements[selector] = jQuery(selector);
    };

    /**
     * Show and Log Error Message
     * @param {String} message
     */
    var showError = function (message)
    {
        cache.get("#wpstg-try-again").css("display", "inline-block");
        cache.get("#wpstg-cancel-cloning").text("Reset");
        cache.get("#wpstg-cloning-result").text("Fail");
        cache.get("#wpstg-error-wrapper").show();
        cache.get("#wpstg-error-details")
                .show()
                .html(message);
        cache.get("#wpstg-removing-clone").removeClass("loading");
        cache.get("#wpstg-loader").hide();
    };

    /**
     * Common Elements
     */
    var elements = function ()
    {
        var $workFlow = cache.get("#wpstg-workflow"),
                isAllChecked = true,
                urlSpinner = ajaxurl.replace("/admin-ajax.php", '') + "/images/spinner",
                timer;

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
                .on("click", ".wpstg-button-select", function (e) {
                    console.log('test1');

                    e.preventDefault();

                    $(".wpstg-db-table input").each(function () {
                        if ($(this).attr('name').match("^" + wpstg.tblprefix)) {
                            $(this).prop("checked", true);
                        } else {
                            $(this).prop("checked", false);

                        }
                    });
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
                    if ("number" === typeof (timer))
                    {
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
                                function (response)
                                {
                                    if (response.status === "success")
                                    {
                                        cache.get("#wpstg-new-clone-id").removeClass("wpstg-error-input");
                                        cache.get("#wpstg-start-cloning").removeAttr("disabled");
                                        cache.get("#wpstg-clone-id-error").text('').hide();
                                    }
                                    else
                                    {
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
                    that.isCancelled = false;
                    that.getLogs = false;
                })
        // Display logs
//                .on("click", "#wpstg-show-log-button", function (e) {
//                    e.preventDefault();
//                    var $logDetails = cache.get("#wpstg-log-details");
//
//                    $logDetails.toggle();
//
//                    logscroll();
//
//                    that.getLogs = (false === that.getLogs);
//                });

        cloneActions();
    };


    /**
     * Clone actions
     */
    var cloneActions = function ()
    {
        var $workFlow = cache.get("#wpstg-workflow");

        $workFlow
                // Cancel cloning
                .on("click", "#wpstg-cancel-cloning", function () {
                    if (!confirm("Are you sure you want to cancel cloning process?"))
                    {
                        return false;
                    }

                    var $this = $(this);

                    $("#wpstg-try-again, #wpstg-home-link").hide();
                    $this.prop("disabled", true);

                    that.isCancelled = true;

                    $("#wpstg-cloning-result").text("Please wait...this can take up a while.");
                    $("#wpstg-loader, #wpstg-show-log-button").hide();

                    $this.parent().append(ajaxSpinner);

                    cancelCloning();
                })
                // Cancel update cloning
                .on("click", "#wpstg-cancel-cloning-update", function () {
                    if (!confirm("Are you sure you want to cancel clone updating process?"))
                    {
                        return false;
                    }

                    var $this = $(this);

                    $("#wpstg-try-again, #wpstg-home-link").hide();
                    $this.prop("disabled", true);

                    that.isCancelled = true;

                    $("#wpstg-cloning-result").text("Please wait...this can take up a while.");
                    $("#wpstg-loader, #wpstg-show-log-button").hide();

                    $this.parent().append(ajaxSpinner);

                    cancelCloningUpdate();
                })
                // Delete clone - confirmation
                .on("click", ".wpstg-remove-clone[data-clone]", function (e) {
                    e.preventDefault();

                    var $existingClones = cache.get("#wpstg-existing-clones");

                    $workFlow.removeClass('active');

                    cache.get("#wpstg-loader").show();

                    ajax(
                            {
                                action: "wpstg_confirm_delete_clone",
                                nonce: wpstg.nonce,
                                clone: $(this).data("clone")
                            },
                    function (response)
                    {
                        cache.get("#wpstg-removing-clone").html(response);

                        $existingClones.children("img").remove();

                        cache.get("#wpstg-loader").hide();
                    },
                            "HTML"
                            );
                })
                // Delete clone - confirmed
                .on("click", "#wpstg-remove-clone", function (e) {
                    e.preventDefault();

                    cache.get("#wpstg-removing-clone").addClass("loading");

                    cache.get("#wpstg-loader").show();

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

                    if (!confirm("Are you sure you want to update the staging site? All your staging site modifications will be overwritten with the data from the live site. So make sure that your live site is up to date."))
                    {
                        return false;
                    }

                    var clone = $(this).data("clone");

                    $workFlow.addClass("loading");

                    ajax(
                            {
                                action: "wpstg_scanning",
                                clone: clone,
                                nonce: wpstg.nonce
                            },
                    function (response)
                    {
                        if (response.length < 1)
                        {
                            showError("Something went wrong, please try again");
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
    var ajax = function (data, callback, dataType, showErrors)
    {
        if ("undefined" === typeof (dataType))
        {
            dataType = "json";
        }

        if (false !== showErrors)
        {
            showErrors = true;
        }

        $.ajax({
            url: ajaxurl,
            type: "POST",
            dataType: dataType,
            cache: false,
            data: data,
            error: function (xhr, textStatus, errorThrown) {
                console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus);
                console.log(textStatus);

                if (false === showErrors)
                {
                    return false;
                }

                showError(
                        "Fatal Unknown Error. Go to WP Staging > Settings and lower 'File Copy Limit'" +
                        "Than try again. If this does not help, " +
                        "<a href='https://wpquads.com/support/' target='_blank'>open a support ticket</a> "
                        );
            },
            success: function (data) {
                if ("function" === typeof (callback))
                {
                    callback(data);
                }
            },
            statusCode: {
                404: function () {
                    showError("Something went wrong; can't find ajax request URL!");
                },
                500: function () {
                    showError("Something went wrong; internal server error while processing the request!");
                }
            }
        });
    };

    /**
     * Next / Previous Step Clicks to Navigate Through Staging Job
     */
    var stepButtons = function ()
    {
        var $workFlow = cache.get("#wpstg-workflow");

        $workFlow
                // Next Button
                .on("click", ".wpstg-next-step-link", function (e) {
                    e.preventDefault();

                    var $this = $(this),
                            isScan = false;

                    // Button is disabled
                    if ($this.attr("disabled"))
                    {
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

                                if (response.length < 1)
                                {
                                    showError("Something went wrong, please try again");
                                }

                                // Styling of elements
                                $workFlow.removeClass("loading").html(response);

                                cache.get(".wpstg-current-step")
                                        .removeClass("wpstg-current-step")
                                        .next("li")
                                        .addClass("wpstg-current-step");

                                // Start cloning
                                that.startCloning();
                                //processing();
                            },
                            "HTML"
                            );
                })
                // Previous Button
                .on("click", ".wpstg-prev-step-link", function (e) {
                    e.preventDefault();
                    cache.get("#wpstg-loader").removeClass('wpstg-finished');
                    cache.get("#wpstg-loader").hide();
                    loadOverview();
                });
    };

    /**
     * Get Excluded (Unchecked) Database Tables
     * @returns {Array}
     */
    var getExcludedTables = function ()
    {
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
    var getIncludedDirectories = function ()
    {
        var includedDirectories = [];

        $(".wpstg-dir input:checked").each(function () {
            var $this = $(this);
            if (!$this.parent(".wpstg-dir").parents(".wpstg-dir").children(".wpstg-expand-dirs").hasClass("disabled"))
            {
                includedDirectories.push($this.val());
            }
        });

        return includedDirectories;
    };

    /**
     * Get Excluded Directories
     * @returns {Array}
     */
    var getExcludedDirectories = function ()
    {
        var excludedDirectories = [];

        $(".wpstg-dir input:not(:checked)").each(function () {
            var $this = $(this);
            if (!$this.parent(".wpstg-dir").parents(".wpstg-dir").children(".wpstg-expand-dirs").hasClass("disabled"))
            {
                excludedDirectories.push($this.val());
            }
        });

        return excludedDirectories;
    };

    /**
     * Get Included Extra Directories
     * @returns {Array}
     */
    var getIncludedExtraDirectories = function ()
    {
        var extraDirectories = [];

        if (!$("#wpstg_extraDirectories").val()) {
            return extraDirectories;
        }

        var extraDirectories = $("#wpstg_extraDirectories").val().split(/\r?\n/);
        console.log(extraDirectories);

        //excludedDirectories.push($this.val());

        return extraDirectories;
    };



    /**
     * Get Cloning Step Data
     */
    var getCloningData = function ()
    {
        if ("wpstg_cloning" !== that.data.action && "wpstg_update" !== that.data.action)
        {
            return;
        }

        that.data.cloneID = $("#wpstg-new-clone-id").val() || new Date().getTime().toString();
        that.data.excludedTables = getExcludedTables();
        that.data.includedDirectories = getIncludedDirectories();
        that.data.excludedDirectories = getExcludedDirectories();
        that.data.extraDirectories = getIncludedExtraDirectories();
        console.log(that.data);

    };

    /**
     * Loads Overview (first step) of Staging Job
     */
    var loadOverview = function ()
    {
        var $workFlow = cache.get("#wpstg-workflow");

        $workFlow.addClass("loading");

        ajax(
                {
                    action: "wpstg_overview",
                    nonce: wpstg.nonce
                },
        function (response) {

            if (response.length < 1)
            {
                showError("Something went wrong, please try again");
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
    var tabs = function ()
    {
//        var $loaded = false;
//
//        if ($loaded === false) {
//            console.log('select default tables');
//
//            $(".wpstg-db-table input").each(function () {
//
//                $loaded = true;
//
//                if ($(this).attr('name').match("^" + wpstg.tblprefix)) {
//                    $(this).prop("checked", true);
//                } else {
//                    $(this).prop("checked", false);
//                }
//            });
//        }

        cache.get("#wpstg-workflow").on("click", ".wpstg-tab-header", function (e) {
            e.preventDefault();

            var $this = $(this);
            var $section = cache.get($this.data("id"));

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
     * Delete Clone
     * @param {String} clone
     */
    var deleteClone = function (clone)
    {

        ajax(
                {
                    action: "wpstg_delete_clone",
                    clone: clone,
                    nonce: wpstg.nonce,
                    excludedTables: getExcludedTables(),
                    deleteDir: $("#deleteDirectory:checked").val()
                },
        function (response)
        {
            if (response) {
                // Error
                if ("undefined" !== typeof response.error && "undefined" !== typeof response.message) {
                    showError(response.message);
                    console.log(response.message);
                }

                // Finished
                if ("undefined" !== typeof response.delete && response.delete === 'finished') {

                    cache.get("#wpstg-removing-clone").removeClass("loading").html('');
                    $(".wpstg-clone#" + clone).remove();

                    if ($(".wpstg-clone").length < 1)
                    {
                        cache.get("#wpstg-existing-clones").find("h3").text('');
                    }

                    cache.get("#wpstg-loader").hide();
                    return;
                }
            }
            // continue
            if (true !== response)
            {
                deleteClone(clone);
                return;
            }

        }
        );
    };

    /**
     * Cancel Cloning Process
     */
    var cancelCloning = function ()
    {
        if (true === that.isFinished)
        {
            return true;
        }

        ajax(
                {
                    action: "wpstg_cancel_clone",
                    clone: that.data.cloneID,
                    nonce: wpstg.nonce
                },
        function (response)
        {


            if (response && "undefined" !== typeof (response.delete) && response.delete === "finished") {
                // Load overview
                loadOverview();
                return;
            }

            if (true !== response)
            {
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
    var cancelCloningUpdate = function ()
    {
        if (true === that.isFinished)
        {
            return true;
        }

//alert(that.data.cloneID);
        ajax(
                {
                    action: "wpstg_cancel_update",
                    clone: that.data.cloneID,
                    nonce: wpstg.nonce
                },
        function (response)
        {


            if (response && "undefined" !== typeof (response.delete) && response.delete === "finished") {
                // Load overview
                loadOverview();
                return;
            }

            if (true !== response)
            {
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
        var $div = cache.get("#wpstg-log-details");
        if ("undefined" !== typeof ($div[0])) {
            $div.scrollTop($div[0].scrollHeight);
        }
    }

    /**
     * Append the log to the logging window
     * @param string log
     * @returns void
     */
    var getLogs = function (log)
    {
        if (log != null && "undefined" !== typeof (log)) {
            if (log.constructor === Array) {
                $.each(log, function (index, value) {
                    if (value === null) {
                        return;
                    }
                    if (value.type === 'ERROR') {
                        cache.get("#wpstg-log-details").append('<span style="color:red;">[' + value.type + ']</span>-' + '[' + value.date + '] ' + value.message + '</br>');
                    } else {
                        cache.get("#wpstg-log-details").append('[' + value.type + ']-' + '[' + value.date + '] ' + value.message + '</br>');
                    }
                })
            } else {
                cache.get("#wpstg-log-details").append('[' + log.type + ']-' + '[' + log.date + '] ' + log.message + '</br>');
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
            cache.get("#wpstg-loader").show();
            console.log("check disk space");
            ajax(
                    {
                        action: "wpstg_check_disk_space",
                        nonce: wpstg.nonce
                    },
            function (response)
            {
                if (false === response)
                {
                    cache.get("#wpstg-clone-id-error").text('Can not detect disk space').show();
                    cache.get("#wpstg-loader").hide();
                    return;
                }

                // Not enough disk space
                cache.get("#wpstg-clone-id-error").text('Available free disk space ' + response.freespace + ' | Estimated necessary disk space: ' + response.usedspace).show();
                cache.get("#wpstg-loader").hide();
            },
                    "json",
                    false
                    );
        });

    }

    /**
     * Fire the update cloning process
     * @returns {undefined}
     */
//    var startUpdate = function (){
//        var $workFlow = cache.get("#wpstg-workflow");
//        $workFlow.on("click", "#wpstg-start-updating", function (e) {
//            e.preventDefault();
//                    
//            var cloneID = $("#wpstg-new-clone-id").val();
//            updateStruc(cloneID);
//        });
//    };
//    
//    var updateStruc = function (cloneID){
//                    ajax(
//                    {
//                        action: "wpstg_update_struc",
//                        nonce: wpstg.nonce,
//                        cloneID: cloneID,
//                        excludedTables: getExcludedTables(),
//                        includedDirectories: getIncludedDirectories(),
//                        excludedDirectories: getExcludedDirectories(),
//                        extraDirectories: getIncludedExtraDirectories()
//                    },
//                        function (response) {
//                            //console.log(response);
//                            updating(cloneID);
//                        },
//            "HTML",
//            false
//        );
//    }

    /**
     * Start ajax updating process
     * @returns string
     */
//    var updating = function (cloneID) {
//        
//            console.log("Start updating");
//            
//            // Show loader gif
//            cache.get("#wpstg-loader").show();
//            cache.get(".wpstg-loader").show();
//
//            ajax(
//                    {
//                        action: "wpstg_update",
//                        nonce: wpstg.nonce,
//                        cloneID: cloneID,
//                        excludedTables: getExcludedTables(),
//                        includedDirectories: getIncludedDirectories(),
//                        excludedDirectories: getExcludedDirectories(),
//                        extraDirectories: getIncludedExtraDirectories()
//                    },
//                        function (response) {
//                           
//                            // Throw Error
//                            if ("undefined" !== typeof(response.error) && response.error){
//                                console.log(response.message);
//                                showError(response.message);
//                                return;
//                            }
//                
//                            // Add percentage
//                            if ("undefined" !== typeof (response.percentage))
//                            {
//                                cache.get("#wpstg-db-progress").width(response.percentage + '%');
//                            }
//                            // Add Log
//                            if ("undefined" !== typeof (response.last_msg))
//                            {
//                                getLogs(response.last_msg);
//                            }
//
//                            // Continue clone DB
//                            if (false === response.status)
//                            {
//                                setTimeout(function () {
//                                    updating(cloneID);
//                                }, wpstg.cpuLoad);
//                            }
//                            // Next Step
//                            else if (true === response.status)
//                            {
//                                console.log('startCloning ' + response.status);
//                                setTimeout(function () {
//                                    // Prepare data
//                                    that.data = {
//                                        action: 'wpstg_cloning',
//                                        nonce: wpstg.nonce
//                                    };
//                                    that.startCloning();
//                                }, wpstg.cpuLoad);
//                            }
//                        },
//            "json",
//            false
//        );
//    };

    /**
     * Start Cloning Process
     * @type {Function}
     */
    that.startCloning = (function () {

        // Register function for checking disk space
        checkDiskSpace();

        if ("wpstg_cloning" !== that.data.action && "wpstg_update" !== that.data.action)
        {
            return;
        }

        // Start the process
        start();

        // Functions
        // Start
        function start()
        {
            console.log("Starting cloning process...");

            cache.get("#wpstg-loader").show();

            // Clone Database
            setTimeout(function () {
                cloneDatabase();
            }, wpstg.cpuLoad);
        }

        // Step 1: Clone Database
        function cloneDatabase()
        {
            if (true === that.isCancelled)
            {
                return false;
            }

            if (true === that.getLogs)
            {
                getLogs();
            }

            setTimeout(
                    function () {
                        ajax(
                                {
                                    action: "wpstg_clone_database",
                                    nonce: wpstg.nonce
                                },
                        function (response) {
                            // Add percentage
                            if ("undefined" !== typeof (response.percentage))
                            {
                                cache.get("#wpstg-db-progress").width(response.percentage + '%');
                            }
                            // Add Log
                            if ("undefined" !== typeof (response.last_msg))
                            {
                                getLogs(response.last_msg);
                            }

                            // Continue clone DB
                            if (false === response.status)
                            {
                                setTimeout(function () {
                                    cloneDatabase();
                                }, wpstg.cpuLoad);
                            }
                            // Next Step
                            else if (true === response.status)
                            {
                                //console.log('prepareDirectories ' + response.status);
                                setTimeout(function () {
                                    prepareDirectories();
                                }, wpstg.cpuLoad);
                            }
                        }
                        );
                    },
                    500
                    );
        }

        // Step 2: Prepare Directories
        function prepareDirectories()
        {
            if (true === that.isCancelled)
            {
                return false;
            }

            if (true === that.getLogs)
            {
                getLogs();
            }

            setTimeout(
                    function () {
                        ajax(
                                {
                                    action: "wpstg_clone_prepare_directories",
                                    nonce: wpstg.nonce
                                },
                        function (response) {
                            // Add percentage
                            if ("undefined" !== typeof (response.percentage))
                            {
                                cache.get("#wpstg-directories-progress").width(response.percentage + '%');
                            }

                            // Add Log
                            if ("undefined" !== typeof (response.last_msg))
                            {
                                getLogs(response.last_msg);
                            }

                            if (false === response.status)
                            {
                                setTimeout(function () {
                                    prepareDirectories();
                                }, wpstg.cpuLoad);
                            }
                            else if (true === response.status)
                            {
                                console.log('prepareDirectories' + response.status);
                                cloneFiles();
                            }
                        }
                        );
                    },
                    500
                    );
        }

        // Step 3: Clone Files
        function cloneFiles()
        {
            if (true === that.isCancelled)
            {
                return false;
            }

            if (true === that.getLogs)
            {
                getLogs();
            }

            ajax(
                    {
                        action: "wpstg_clone_files",
                        nonce: wpstg.nonce
                    },
            function (response) {
                // Add percentage
                if ("undefined" !== typeof (response.percentage))
                {
                    cache.get("#wpstg-files-progress").width(response.percentage + '%');
                }

                // Add Log
                if ("undefined" !== typeof (response.last_msg))
                {
                    getLogs(response.last_msg);
                }

                if (false === response.status)
                {
                    setTimeout(function () {
                        cloneFiles();
                    }, wpstg.cpuLoad);
                }
                else if (true === response.status)
                {
                    setTimeout(function () {
                        replaceData();
                    }, wpstg.cpuLoad);
                }
            }
            );
        }

        // Step 4: Replace Data
        function replaceData()
        {
            if (true === that.isCancelled)
            {
                return false;
            }

            if (true === that.getLogs)
            {
                console.log('getLogs1')
                getLogs();
            }

            ajax(
                    {
                        action: "wpstg_clone_replace_data",
                        nonce: wpstg.nonce
                    },
            function (response) {
                // Add percentage
                if ("undefined" !== typeof (response.percentage))
                {
                    cache.get("#wpstg-links-progress").width(response.percentage + '%');
                }

                // Add Log
                if ("undefined" !== typeof (response.last_msg))
                {
                    console.log('get Logs');
                    getLogs(response.last_msg);
                }

                if (false === response.status)
                {
                    setTimeout(function () {
                        console.log('replace data');
                        replaceData();
                    }, wpstg.cpuLoad);
                }
                else if (true === response.status)
                {
                    console.log('finish');
                    finish();
                }
            }
            );
        }

        // Finish
        function finish()
        {
            if (true === that.getLogs)
            {
                getLogs();
            }

            if (true === that.isCancelled || true === that.isFinished)
            {
                cache.get("#wpstg-loader").hide();
                return false;
            }

            ajax(
                    {
                        action: "wpstg_clone_finish",
                        nonce: wpstg.nonce
                    },
            function (response)
            {
                // Invalid response
                if ("object" !== typeof (response))
                {
                    showError(
                            "Couldn't finish the cloning process properly. " +
                            "Your clone has been copied but failed to do clean up and " +
                            "saving its records to the database." +
                            "Please contact support and provide your logs."
                            );

                    return;
                }

                // Add Log
                if ("undefined" !== typeof (response.last_msg))
                {
                    getLogs(response.last_msg);
                }

                console.log("Cloning process finished");

                var $link1 = cache.get("#wpstg-clone-url-1");
                var $link = cache.get("#wpstg-clone-url");

                cache.get("#wpstg_staging_name").html(that.data.cloneID);
                cache.get("#wpstg-finished-result").show();
                cache.get("#wpstg-cancel-cloning").prop("disabled", true);
                cache.get("#wpstg-cancel-cloning-update").prop("disabled", true);
                $link1.attr("href", $link1.attr("href") + '/' + response.directoryName);
                $link1.append('/' + response.directoryName);
                $link.attr("href", $link.attr("href") + '/' + response.directoryName);
                cache.get("#wpstg-remove-clone").data("clone", that.data.cloneID);

                // Finished
                that.isFinished = true;

                finish();
            }
            );
        }
    });


    /**
     * Initiation
     * @type {Function}
     */
    that.init = (function () {
        loadOverview();
        elements();
        //startUpdate();
        stepButtons();
        tabs();
        //optimizer();
    });

    /**
     * Ajax call
     * @type {ajax}
     */
    that.ajax = ajax;
    that.showError = showError;
    that.getLogs = getLogs;
    that.loadOverview = loadOverview;

    return that;
})(jQuery);

jQuery(document).ready(function () {
    WPStaging.init();
});