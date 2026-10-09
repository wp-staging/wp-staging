<?php

namespace WPStaging\Framework\ThirdParty;

use WPStaging\Framework\Adapter\WpAdapter;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Network\HttpBasicAuth;
use WPStaging\Framework\Utils\ServerVars;

use function WPStaging\functions\debug_log;





class LiteSpeedCache
{
    use HttpBasicAuth;




    const TRANSIENT_PURGE_LITESPEED_CACHE = "wpstg_purge_litespeed_cache";

 
    const TRANSIENT_SERVER_PURGE_TOKEN = 'wpstg_litespeed_server_purge';

 
    const AJAX_ACTION_SERVER_PURGE = 'wpstg_litespeed_purge';

 
    const QUERY_SERVER_PURGE = 'token';

 
    const HEADER_PURGE_ALL = 'X-LiteSpeed-Purge: *';

 
    const SERVER_PURGE_TOKEN_LIFETIME = 300;




    protected $wpAdapter;

 
    private $serverVars;





    public function __construct(WpAdapter $wpAdapter, ServerVars $serverVars)
    {
        $this->wpAdapter  = $wpAdapter;
        $this->serverVars = $serverVars;
    }








    public function purgeAfterRestore()
    {
        set_transient(self::TRANSIENT_PURGE_LITESPEED_CACHE, "true");
        if (!$this->serverVars->isLitespeed()) {
            return null;
        }

        $token = bin2hex(random_bytes(16));
        set_transient(self::TRANSIENT_SERVER_PURGE_TOKEN, hash('sha256', $token), self::SERVER_PURGE_TOKEN_LIFETIME);
        return $this->requestServerPurge(add_query_arg([
            'action'                 => self::AJAX_ACTION_SERVER_PURGE,
            self::QUERY_SERVER_PURGE => $token,
        ], admin_url('admin-ajax.php')));
    }







    public function answerServerPurgeRequest()
    {
        if (!isset($_GET[self::QUERY_SERVER_PURGE])) {
            return;
        }

        $expectedHash = get_transient(self::TRANSIENT_SERVER_PURGE_TOKEN);
        $token        = Sanitize::sanitizeString($_GET[self::QUERY_SERVER_PURGE]);
        if (!is_string($expectedHash) || !hash_equals($expectedHash, hash('sha256', $token))) {
            return;
        }

        delete_transient(self::TRANSIENT_SERVER_PURGE_TOKEN);
        if ($this->responseHeadersAreSent()) {
            return;
        }

        $this->sendHeader(self::HEADER_PURGE_ALL);
        $this->sendHeader('Cache-Control: no-store');
        debug_log('LiteSpeed server cache purged.');
        $this->endResponse();
    }




    public function maybePurgeLiteSpeedCache()
    {
        if (!$this->isLiteSpeedCacheActive()) {
            delete_transient(self::TRANSIENT_PURGE_LITESPEED_CACHE);
            return;
        }

        if (!class_exists('\LiteSpeed\Purge', false) || !method_exists('\LiteSpeed\Purge', 'purge_all')) {
            return;
        }

        \LiteSpeed\Purge::purge_all('wp-staging');
        debug_log('LiteSpeed Cache cache cleared.');
        delete_transient(self::TRANSIENT_PURGE_LITESPEED_CACHE);
    }





    protected function requestServerPurge(string $url): int
    {
        $response = wp_remote_get($url, [
            'timeout'     => 15,
            'redirection' => 0,
            'sslverify'   => apply_filters('https_local_ssl_verify', false),
            'headers'     => $this->getHttpAuthHeaders(),
        ]);

        if (is_wp_error($response)) {
            debug_log('LiteSpeed server purge request failed: ' . $response->get_error_message());
            return 0;
        }

        return (int)wp_remote_retrieve_response_code($response);
    }




    protected function responseHeadersAreSent(): bool
    {
        return headers_sent();
    }





    protected function sendHeader(string $header)
    {
        header($header);
    }




    protected function endResponse()
    {
        status_header(204);
        exit;
    }






    private function isLiteSpeedCacheActive(): bool
    {
        return $this->wpAdapter->isPluginActive('litespeed-cache/litespeed-cache.php');
    }
}
