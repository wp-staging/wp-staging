<?php

namespace WPStaging\Framework\Interfaces;








interface ShutdownableInterface
{










    const SHUTDOWN_PRIORITY = -10000;






    public function onWpShutdown();
}
