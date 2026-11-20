<?php

namespace WPStaging\Framework\Security;









class Capabilities
{



    const WPSTG_VISITOR_ROLE = 'wpstg_visitor';




    public function manageWPSTG()
    {
        return 'manage_options';
    }







    public function isSingleSiteOrCurrentUserSuperAdmin(): bool
    {
        return !is_multisite() || is_super_admin();
    }
}
