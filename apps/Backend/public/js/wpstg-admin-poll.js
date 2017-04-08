jQuery(document).ready(function($){
    $(".wpstg_hide_poll").click(function(e) {
        e.preventDefault();

        WPStaging.ajax(
            {action: "wpstg_hide_poll"},
            function(response)
            {
                if (true === response)
                {
                    $(".wpstg_poll").slideUp("fast");
                    return true;
                }

                alert(
                    "Unexpected message received. This might mean the data was not saved " +
                    "and you might see this message again"
                );
            }
        );

        // var url = $(this).data("url");
        //
        // $.ajax({
        //     url     : url,
        //     type    : "POST",
        //     data    : {action: "wpstg_hide_poll"},
        //     dataType: "JSON",
        //     error       : function(xhr, textStatus, errorThrown) {
        //         console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus);
        //         console.log(textStatus);
        //     },
        //     success     : function(response) {
        //         if (true === response)
        //         {
        //             $(".wpstg_poll").slideUp("fast");
        //             return true;
        //         }
        //
        //         alert(
        //             "Unexpected message received. This might mean the data was not saved " +
        //             "and you might see this message again"
        //         );
        //     },
        //     statusCode  : {
        //         404: function() {
        //             alert("Something went wrong; can't find ajax request URL!");
        //         },
        //         500: function() {
        //             alert("Something went wrong; internal server error while processing the request!");
        //         }
        //     }
        // });
    })
});