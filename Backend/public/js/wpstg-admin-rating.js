var wpstgTimesWaited = 0;

/*
Let's wait for jQuery to be available to show the rating.
We need it to dispatch AJAX requests.
 */
var wpstgWaitForJQuery = setInterval(function () {
    if (wpstgTimesWaited > 100) {
        // Give up waiting.
        clearInterval(wpstgWaitForJQuery);
    }
    if (typeof jQuery != 'undefined') {

        wpstgRegisterRatingEvents();

        clearInterval(wpstgWaitForJQuery);
    }
    wpstgTimesWaited = wpstgTimesWaited + 1;
}, 100);

function wpstgRegisterRatingEvents() {

    // Show the rating once jQuery is loaded.
    jQuery('.wpstg_fivestar').show();

    /**
     * Dispatch the request to hide the video after user clicks to rate the plugin.
     */
    jQuery(document).on('click', '#wpstg_clicked_deserved_it', function(e) {
        jQuery.ajax({
            url: ajaxurl,
            type: "POST",
            data: { action: "wpstg_hide_rating" },
            error: function(xhr, textStatus, errorThrown) {
                console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus);
                console.log(textStatus);

                alert(
                    "Unknown error. Please get in contact with us to solve it support@wp-staging.com"
                );
            },
            success: function(data) {
                jQuery(".wpstg_fivestar").slideUp("fast");
                return true;
            },
            statusCode: {
                404: function() {
                    alert("Something went wrong; can't find ajax request URL! Please get in contact with us to solve it support@wp-staging.com");
                },
                500: function() {
                    alert("Something went wrong; internal server error while processing the request! Please get in contact with us to solve it support@wp-staging.com");
                }
            }
        });
    });

    jQuery(".wpstg_hide_rating").click(function(e) {
        e.preventDefault();

        jQuery.ajax({
            url: ajaxurl,
            type: "POST",
            data: { action: "wpstg_hide_rating" },
            error: function(xhr, textStatus, errorThrown) {
                console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus);
                console.log(textStatus);

                alert(
                    "Unknown error. Please get in contact with us to solve it support@wp-staging.com"
                );
            },
            success: function(data) {
                jQuery(".wpstg_fivestar").slideUp("fast");
                return true;
            },
            statusCode: {
                404: function() {
                    alert("Something went wrong; can't find ajax request URL! Please get in contact with us to solve it support@wp-staging.com");
                },
                500: function() {
                    alert("Something went wrong; internal server error while processing the request! Please get in contact with us to solve it support@wp-staging.com");
                }
            }
        });
    });

    jQuery(".wpstg_rate_later").click(function(e) {
        e.preventDefault();

        jQuery.ajax({
            url: ajaxurl,
            type: "POST",
            data: { action: "wpstg_hide_later" },
            error: function(xhr, textStatus, errorThrown) {
                console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus);
                console.log(textStatus);

                alert(
                    "Unknown error. Please get in contact with us to solve it support@wp-staging.com"
                );
            },
            success: function(data) {
                jQuery(".wpstg_fivestar").slideUp("fast");
                return true;
            },
            statusCode: {
                404: function() {
                    alert("Something went wrong; can't find ajax request URL! Please get in contact with us to solve it support@wp-staging.com");
                },
                500: function() {
                    alert("Something went wrong; internal server error while processing the request! Please get in contact with us to solve it support@wp-staging.com");
                }
            }
        });
    });
}

document.styleSheets[0].insertRule("@media only screen and (max-width:600px){.wpstg-welcome-box{display:block !important}.wpstg-welcome-video-container{width:100% !important;height:auto !important}.wpstg-welcome-text{padding-left:8px !important}}", "");

document.addEventListener("DOMContentLoaded", function() {
    var player;
    var accepted = wpstgYouTubeConfig.accepted;
    var playerPlaceholder = document.getElementById("welcomeNoticeFree");
    playerPlaceholder.addEventListener("click", function() {
        if (!accepted) {
            var message = wpstgYouTubeConfig.message + "\n \n" + wpstgYouTubeConfig.regards;
            var conf = confirm(message);
            if (conf) {
                accepted = true;
                wpstgFetchVideo();
            }
        }
    });
});

function wpstgFetchVideo() {
    var tag = document.createElement('script');

    tag.src = "https://www.youtube.com/iframe_api";
    var firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

}

function onYouTubeIframeAPIReady() {
    player = new YT.Player('welcomeNoticeFree', {
        height: '225',
        width: '400',
        videoId: 'fsC9ZvbRQ5Y',
        playerVars: {
            rel: 0,
            showinfo: 0,
            ecver: 2
        },
        events: {
            'onReady': onPlayerReady
        }
    });
}

function onPlayerReady(event) {
    event.target.playVideo();
}
