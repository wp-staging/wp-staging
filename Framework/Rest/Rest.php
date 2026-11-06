<?php

namespace WPStaging\Framework\Rest;








class Rest
{
 
    const WPSTG_ROUTE_NAMESPACE_V1 = 'wpstg/v1';

 
    const REQUEST_TIMEOUT = 30;







    public function isRestUrl()
    {
        return function_exists('wpstgGetRequestRestRoute') && wpstgGetRequestRestRoute() !== '';
    }
}
