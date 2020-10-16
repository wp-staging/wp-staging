jQuery(document).ready(function ($) {
    $(".wpstg_hide_poll").click(function (e) {
        e.preventDefault();

        WPStaging.ajax(
                {action: "wpstg_hide_poll"},
        function (response)
        {
            if (true === response)
            {
                $(".wpstg_poll").slideUp("fast");
                return true;
            } else {

                alert(
                        "Unexpected message received. This might mean the data was not saved " +
                        "and you might see this message again."
                        );
            }
        }
        );
    })
});