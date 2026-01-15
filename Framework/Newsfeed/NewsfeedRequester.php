<?php

namespace WPStaging\Framework\Newsfeed;

use function WPStaging\functions\debug_log;

/**
 * Fetches newsfeed JSON data from remote endpoints and caches it in the database.
 *
 * The remote source URLs must provide valid JSON content with the newsfeed data structure.
 * Data is cached for 24 hours using WordPress transients.
 */
class NewsfeedRequester
{
    /**
     * The transient expiration time in seconds.
     * @var int
     */
    const TRANSIENT_EXPIRATION_TIME = 86400; // 24 hours

    /** @var string */
    private $url;

    /** @var string */
    private $transientIdentifier;

    /**
     * When debug mode is on, it will always fetch the data from the remote server.
     * @var bool
     */
    private $isDebug = false;

    /** @var NewsfeedValidator */
    private $validator;

    public function __construct(string $transientIdentifier = '', string $url = '')
    {
        if (empty($transientIdentifier)) {
            throw new \InvalidArgumentException('Transient identifier cannot be empty.');
        }

        if (empty($url)) {
            throw new \InvalidArgumentException('URL cannot be empty.');
        }

        $this->validator = new NewsfeedValidator();

        $this->url = $url;

        $this->transientIdentifier = 'wpstg_news_block_' . md5($transientIdentifier);
    }

    /**
     * Fetch and return newsfeed data as an array
     *
     * @return array|null Parsed JSON data or null on failure
     */
    public function returnData()
    {
        $cached = get_transient($this->transientIdentifier);

        if ($cached !== false && !$this->isDebug) {
            return $cached;
        }

        $data = $this->getRemoteData();
        if ($data === null || !$this->validator->validate($data)) {
            return null;
        }

        if (!$this->isDebug) {
            set_transient($this->transientIdentifier, $data, self::TRANSIENT_EXPIRATION_TIME);
        }

        return $data;
    }

    /**
     * @param bool $isDebug
     * @return void
     */
    public function setIsDebug(bool $isDebug)
    {
        $this->isDebug = $isDebug;
    }

    /**
     * Fetch and parse JSON from remote URL
     *
     * @return array|null Parsed JSON data or null on failure
     */
    private function getRemoteData()
    {
        if (!filter_var($this->url, FILTER_VALIDATE_URL)) {
            debug_log(sprintf('Invalid URL for retrieving the news from %s', $this->url));
            return null;
        }

        $response = wp_remote_get($this->url, ['timeout' => 5, 'sslverify' => true]);

        if (is_wp_error($response)) {
            debug_log(sprintf('Cannot retrieve news data from URL %s. Error: %s', $this->url, $response->get_error_message()));
            return null;
        }

        $statusCode = wp_remote_retrieve_response_code($response);

        if ($statusCode !== 200) {
            debug_log(sprintf('Cannot get remote news data from url %s Response: %s', $this->url, $statusCode));
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode(trim($body), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            debug_log(sprintf('Newsfeed JSON parse error from %s: %s', $this->url, json_last_error_msg()));
            return null;
        }

        return $data;
    }
}
