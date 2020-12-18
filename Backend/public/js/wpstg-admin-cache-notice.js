jQuery(document).ready(function ($) {
    jQuery(document).on('click', ".wpstg_hide_cache_notice", function (e) {
        e.preventDefault();
  
        jQuery.ajax({
            url: ajaxurl,
            type: "POST",
            data: { action: "wpstg_hide_cache_notice" },
            error: function(xhr, textStatus, errorThrown) {
                console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus);
                console.log(textStatus);

                alert(
                    "Unknown error. Please get in contact with us to solve it support@wp-staging.com"
                );
            },
            success: function(data) {
                jQuery(".wpstg-cache-notice").slideUp("fast");
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

    })
});