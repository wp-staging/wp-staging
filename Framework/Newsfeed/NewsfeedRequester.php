<?php

namespace WPStaging\Framework\Newsfeed;

use WPStaging\Framework\Newsfeed\NewsfeedValidator;

use function WPStaging\functions\debug_log;

/**
 * This class is responsible for getting the newsfeed data from several remote endpoints and for caching it in the database.
 * The remote source URLs need to provide html content with structure below.
 *
 * Example:
 * <div class="wpstg-block--header">
 *     <strong class="wpstg-block--title">That#s a headline</strong>
 *     <span class="wpstg-block--date">September 27, 2024</span>
 * </div>
 * Regular text or html including links is allowed <a href="https://wp-staging.com" target="_blank">An example link</a>.
 * </br>
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
     * @return string
     */
    public function returnData(): string
    {
        $data = get_transient($this->transientIdentifier);

        if ($data !== false && !$this->isDebug) {
            return $data;
        }

        $data = $this->getRemoteData();
        if (empty($data) || !$this->validator->validate($data)) {
            return '';
        }

        if (!$this->isDebug) {
            set_transient($this->transientIdentifier, wp_kses_post($data), self::TRANSIENT_EXPIRATION_TIME);
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
     * @return string
     */
    private function getRemoteData(): string
    {
        if (!filter_var($this->url, FILTER_VALIDATE_URL)) {
            debug_log(sprintf('Invalid URL for retrieving the news from %s', $this->url));
            return '';
        }

        $response = wp_remote_get($this->url, ['timeout' => 5, 'sslverify' => true]);

        if (is_wp_error($response)) {
            debug_log(sprintf('Cannot retrieve news data from URL %s. Error: %s', $this->url, $response->get_error_message()));
            return '';
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code !== 200) {
            debug_log(sprintf('Can not get remote news data from url %s Response: %s', $this->url, $status_code));
            return '';
        }

        $content = wp_remote_retrieve_body($response);
        return trim($content);
    }
}
