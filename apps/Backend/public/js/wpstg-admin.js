"use strict";

var WPStaging = (function($)
{
    var that        = {},
        $workFlow   = $("#wpstg-workflow");

    /**
     * Next Step Clicks to Navigate Through Staging Job
     */
    var nextStep = function()
    {
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
            var data = {
                action  : $this.data("action"),
                nonce   : wpstg.nonce
            };

            // Cloning data
            getCloningData(data);

            console.log(data);

            // $.ajax({
            //     url         : ajaxUrl,
            //     type        : "POST",
            //     dataType    : "HTML",
            //     cache       : false,
            //     data        : data,
            //     error       : function(jqXHR, textStatus, errorThrown) {
            //         console.log(xhr.status + ' ' + xhr.statusText);
            //
            //     },
            //     success     : function(data) {
            //
            //     },
            //     statusCode  : {
            //         404: function() {
            //
            //         },
            //         500: function() {
            //
            //         }
            //     }
            // });
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
        var getCloningData = function(data)
        {
            if ("wpstg_cloning" !== data.action)
            {
                return;
            }

            data.cloneID                = $("#wpstg-new-clone-id").val() || new Date().getTime().toString();
            data.excludedTables         = getExcludedTables();
            data.excludedDirectories    = getExcludedDirectories();
        };
    };

    that.init = (function() {
        nextStep();
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