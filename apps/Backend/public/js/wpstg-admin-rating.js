jQuery(document).ready(function ($) {
    $(".wpstg_hide_rating").click(function (e) {
        e.preventDefault();

        WPStaging.ajax(
                {action: "wpstg_hide_rating"},
        function (response)
        {
            if (true === response)
            {
                $(".wpstg_fivestar").slideUp("fast");
                return true;
            } else {
                alert(
                        "Unexpected message received. This might mean the data was not saved " +
                        "and you might see this message again"
                        );
            }
        }
        );


    })
});