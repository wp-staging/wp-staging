<?php

namespace WPStaging\Vendor;

if (\class_exists('WPStaging\\Vendor\\WPStaging_Vendor_Google_Client', \false)) {
    // Prevent error with preloading in PHP 7.4
    // @see https://github.com/googleapis/google-api-php-client/issues/1976
    return;
}
$classMap = ['WPStaging\\Vendor\\Google\\Client' => 'WPStaging_Vendor_Google_Client', 'WPStaging\\Vendor\\Google\\Service' => 'WPStaging_Vendor_Google_Service', 'WPStaging\\Vendor\\Google\\AccessToken\\Revoke' => 'WPStaging_Vendor_Google_AccessToken_Revoke', 'WPStaging\\Vendor\\Google\\AccessToken\\Verify' => 'WPStaging_Vendor_Google_AccessToken_Verify', 'WPStaging\\Vendor\\Google\\Model' => 'WPStaging_Vendor_Google_Model', 'WPStaging\\Vendor\\Google\\Utils\\UriTemplate' => 'WPStaging_Vendor_Google_Utils_UriTemplate', 'WPStaging\\Vendor\\Google\\AuthHandler\\Guzzle6AuthHandler' => 'WPStaging_Vendor_Google_AuthHandler_Guzzle6AuthHandler', 'WPStaging\\Vendor\\Google\\AuthHandler\\Guzzle7AuthHandler' => 'WPStaging_Vendor_Google_AuthHandler_Guzzle7AuthHandler', 'WPStaging\\Vendor\\Google\\AuthHandler\\Guzzle5AuthHandler' => 'WPStaging_Vendor_Google_AuthHandler_Guzzle5AuthHandler', 'WPStaging\\Vendor\\Google\\AuthHandler\\AuthHandlerFactory' => 'WPStaging_Vendor_Google_AuthHandler_AuthHandlerFactory', 'WPStaging\\Vendor\\Google\\Http\\Batch' => 'WPStaging_Vendor_Google_Http_Batch', 'WPStaging\\Vendor\\Google\\Http\\MediaFileUpload' => 'WPStaging_Vendor_Google_Http_MediaFileUpload', 'WPStaging\\Vendor\\Google\\Http\\REST' => 'WPStaging_Vendor_Google_Http_REST', 'WPStaging\\Vendor\\Google\\Task\\Retryable' => 'WPStaging_Vendor_Google_Task_Retryable', 'WPStaging\\Vendor\\Google\\Task\\Exception' => 'WPStaging_Vendor_Google_Task_Exception', 'WPStaging\\Vendor\\Google\\Task\\Runner' => 'WPStaging_Vendor_Google_Task_Runner', 'WPStaging\\Vendor\\Google\\Collection' => 'WPStaging_Vendor_Google_Collection', 'WPStaging\\Vendor\\Google\\Service\\Exception' => 'WPStaging_Vendor_Google_Service_Exception', 'WPStaging\\Vendor\\Google\\Service\\Resource' => 'WPStaging_Vendor_Google_Service_Resource', 'WPStaging\\Vendor\\Google\\Exception' => 'WPStaging_Vendor_Google_Exception'];
foreach ($classMap as $class => $alias) {
    \class_alias($class, $alias);
}
/**
 * This class needs to be defined explicitly as scripts must be recognized by
 * the autoloader.
 */
class WPStaging_Vendor_Google_Task_Composer extends \WPStaging\Vendor\Google\Task\Composer
{
}
if (\false) {
    class WPStaging_Vendor_Google_AccessToken_Revoke extends \WPStaging\Vendor\Google\AccessToken\Revoke
    {
    }
    class WPStaging_Vendor_Google_AccessToken_Verify extends \WPStaging\Vendor\Google\AccessToken\Verify
    {
    }
    class WPStaging_Vendor_Google_AuthHandler_AuthHandlerFactory extends \WPStaging\Vendor\Google\AuthHandler\AuthHandlerFactory
    {
    }
    class WPStaging_Vendor_Google_AuthHandler_Guzzle5AuthHandler extends \WPStaging\Vendor\Google\AuthHandler\Guzzle5AuthHandler
    {
    }
    class WPStaging_Vendor_Google_AuthHandler_Guzzle6AuthHandler extends \WPStaging\Vendor\Google\AuthHandler\Guzzle6AuthHandler
    {
    }
    class WPStaging_Vendor_Google_AuthHandler_Guzzle7AuthHandler extends \WPStaging\Vendor\Google\AuthHandler\Guzzle7AuthHandler
    {
    }
    class WPStaging_Vendor_Google_Client extends \WPStaging\Vendor\Google\Client
    {
    }
    class WPStaging_Vendor_Google_Collection extends \WPStaging\Vendor\Google\Collection
    {
    }
    class WPStaging_Vendor_Google_Exception extends \WPStaging\Vendor\Google\Exception
    {
    }
    class WPStaging_Vendor_Google_Http_Batch extends \WPStaging\Vendor\Google\Http\Batch
    {
    }
    class WPStaging_Vendor_Google_Http_MediaFileUpload extends \WPStaging\Vendor\Google\Http\MediaFileUpload
    {
    }
    class WPStaging_Vendor_Google_Http_REST extends \WPStaging\Vendor\Google\Http\REST
    {
    }
    class WPStaging_Vendor_Google_Model extends \WPStaging\Vendor\Google\Model
    {
    }
    class WPStaging_Vendor_Google_Service extends \WPStaging\Vendor\Google\Service
    {
    }
    class WPStaging_Vendor_Google_Service_Exception extends \WPStaging\Vendor\Google\Service\Exception
    {
    }
    class WPStaging_Vendor_Google_Service_Resource extends \WPStaging\Vendor\Google\Service\Resource
    {
    }
    class WPStaging_Vendor_Google_Task_Exception extends \WPStaging\Vendor\Google\Task\Exception
    {
    }
    interface WPStaging_Vendor_Google_Task_Retryable extends \WPStaging\Vendor\Google\Task\Retryable
    {
    }
    class WPStaging_Vendor_Google_Task_Runner extends \WPStaging\Vendor\Google\Task\Runner
    {
    }
    class WPStaging_Vendor_Google_Utils_UriTemplate extends \WPStaging\Vendor\Google\Utils\UriTemplate
    {
    }
}
