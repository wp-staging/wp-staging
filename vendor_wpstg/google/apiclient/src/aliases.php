<?php

namespace WPStaging\Vendor;

if (\class_exists('WPStaging\\Vendor\\Google_Client', \false)) {
    // Prevent error with preloading in PHP 7.4
    // @see https://github.com/googleapis/google-api-php-client/issues/1976
    return;
}
$classMap = ['WPStaging\\Vendor\\Google\\Client' => 'Google_Client', 'WPStaging\\Vendor\\Google\\Service' => 'Google_Service', 'WPStaging\\Vendor\\Google\\AccessToken\\Revoke' => 'Google_AccessToken_Revoke', 'WPStaging\\Vendor\\Google\\AccessToken\\Verify' => 'Google_AccessToken_Verify', 'WPStaging\\Vendor\\Google\\Model' => 'Google_Model', 'WPStaging\\Vendor\\Google\\Utils\\UriTemplate' => 'Google_Utils_UriTemplate', 'WPStaging\\Vendor\\Google\\AuthHandler\\Guzzle6AuthHandler' => 'Google_AuthHandler_Guzzle6AuthHandler', 'WPStaging\\Vendor\\Google\\AuthHandler\\Guzzle7AuthHandler' => 'Google_AuthHandler_Guzzle7AuthHandler', 'WPStaging\\Vendor\\Google\\AuthHandler\\Guzzle5AuthHandler' => 'Google_AuthHandler_Guzzle5AuthHandler', 'WPStaging\\Vendor\\Google\\AuthHandler\\AuthHandlerFactory' => 'Google_AuthHandler_AuthHandlerFactory', 'WPStaging\\Vendor\\Google\\Http\\Batch' => 'Google_Http_Batch', 'WPStaging\\Vendor\\Google\\Http\\MediaFileUpload' => 'Google_Http_MediaFileUpload', 'WPStaging\\Vendor\\Google\\Http\\REST' => 'Google_Http_REST', 'WPStaging\\Vendor\\Google\\Task\\Retryable' => 'Google_Task_Retryable', 'WPStaging\\Vendor\\Google\\Task\\Exception' => 'Google_Task_Exception', 'WPStaging\\Vendor\\Google\\Task\\Runner' => 'Google_Task_Runner', 'WPStaging\\Vendor\\Google\\Collection' => 'Google_Collection', 'WPStaging\\Vendor\\Google\\Service\\Exception' => 'Google_Service_Exception', 'WPStaging\\Vendor\\Google\\Service\\Resource' => 'Google_Service_Resource', 'WPStaging\\Vendor\\Google\\Exception' => 'Google_Exception'];
foreach ($classMap as $class => $alias) {
    \class_alias($class, $alias);
}
/**
 * This class needs to be defined explicitly as scripts must be recognized by
 * the autoloader.
 */
class Google_Task_Composer extends \WPStaging\Vendor\Google\Task\Composer
{
}
if (\false) {
    class Google_AccessToken_Revoke extends \WPStaging\Vendor\Google\AccessToken\Revoke
    {
    }
    class Google_AccessToken_Verify extends \WPStaging\Vendor\Google\AccessToken\Verify
    {
    }
    class Google_AuthHandler_AuthHandlerFactory extends \WPStaging\Vendor\Google\AuthHandler\AuthHandlerFactory
    {
    }
    class Google_AuthHandler_Guzzle5AuthHandler extends \WPStaging\Vendor\Google\AuthHandler\Guzzle5AuthHandler
    {
    }
    class Google_AuthHandler_Guzzle6AuthHandler extends \WPStaging\Vendor\Google\AuthHandler\Guzzle6AuthHandler
    {
    }
    class Google_AuthHandler_Guzzle7AuthHandler extends \WPStaging\Vendor\Google\AuthHandler\Guzzle7AuthHandler
    {
    }
    class Google_Client extends \WPStaging\Vendor\Google\Client
    {
    }
    class Google_Collection extends \WPStaging\Vendor\Google\Collection
    {
    }
    class Google_Exception extends \WPStaging\Vendor\Google\Exception
    {
    }
    class Google_Http_Batch extends \WPStaging\Vendor\Google\Http\Batch
    {
    }
    class Google_Http_MediaFileUpload extends \WPStaging\Vendor\Google\Http\MediaFileUpload
    {
    }
    class Google_Http_REST extends \WPStaging\Vendor\Google\Http\REST
    {
    }
    class Google_Model extends \WPStaging\Vendor\Google\Model
    {
    }
    class Google_Service extends \WPStaging\Vendor\Google\Service
    {
    }
    class Google_Service_Exception extends \WPStaging\Vendor\Google\Service\Exception
    {
    }
    class Google_Service_Resource extends \WPStaging\Vendor\Google\Service\Resource
    {
    }
    class Google_Task_Exception extends \WPStaging\Vendor\Google\Task\Exception
    {
    }
    interface Google_Task_Retryable extends \WPStaging\Vendor\Google\Task\Retryable
    {
    }
    class Google_Task_Runner extends \WPStaging\Vendor\Google\Task\Runner
    {
    }
    class Google_Utils_UriTemplate extends \WPStaging\Vendor\Google\Utils\UriTemplate
    {
    }
}
