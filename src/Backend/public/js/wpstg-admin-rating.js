jQuery(document).ready(function ($) {

    $(".wpstg_hide_rating").click(function (e) {
        e.preventDefault();

        $.ajax({
            url: ajaxurl,
            type: "POST",
            data: {action: "wpstg_hide_rating"},
            error: function (xhr, textStatus, errorThrown) {
                console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus);
                console.log(textStatus);

                alert(
                        "Unknown error. Please get in contact with us to solve it support@wp-staging.com"
                        );
            },
            success: function (data) {
                $(".wpstg_fivestar").slideUp("fast");
                return true;
            },
            statusCode: {
                404: function () {
                    alert("Something went wrong; can't find ajax request URL! Please get in contact with us to solve it support@wp-staging.com");
                },
                500: function () {
                    alert("Something went wrong; internal server error while processing the request! Please get in contact with us to solve it support@wp-staging.com");
                }
            }
        });
    });

    $(".wpstg_rate_later").click(function (e) {
        e.preventDefault();

        $.ajax({
            url: ajaxurl,
            type: "POST",
            data: {action: "wpstg_hide_later"},
            error: function (xhr, textStatus, errorThrown) {
                console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus);
                console.log(textStatus);


                alert(
                        "Unknown error. Please get in contact with us to solve it support@wp-staging.com"
                        );
            },
            success: function (data) {
                $(".wpstg_fivestar").slideUp("fast");
                return true;
            },
            statusCode: {
                404: function () {
                    alert("Something went wrong; can't find ajax request URL! Please get in contact with us to solve it support@wp-staging.com");
                },
                500: function () {
                    alert("Something went wrong; internal server error while processing the request! Please get in contact with us to solve it support@wp-staging.com");
                }
            }
        });
    });
});