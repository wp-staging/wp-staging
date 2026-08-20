<?php

namespace WPStaging\Core\Utils;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Utils\Urls;

 
if (!defined("WPINC")) {
    die;
}

class Multisite
{



    private $url;

    public function __construct()
    {
        $urlsHelper = WPStaging::make(Urls::class);
        $this->url  = $urlsHelper->getHomeUrl();
    }





    public function getHomeDomain()
    {
        $result = parse_url($this->url);
        return $result['scheme'] . "://" . $result['host'];
    }






    public function getHomeDomainWithoutScheme()
    {
        return preg_replace('#^https?://#', '', rtrim($this->getHomeDomain(), '/'));
    }




    public function getHomeUrl()
    {
        return $this->url;
    }






    public function getHomeUrlWithoutScheme()
    {
        return preg_replace('#^https?://#', '', rtrim($this->url, '/'));
    }
}
