<?php

namespace WPStaging\Framework\Newsfeed;

use WPStaging\Framework\Language\Language;

/**
 * Provides newsfeed data for display in the admin interface.
 *
 * Fetches JSON newsfeed data from remote server based on user's language preference
 * and provides it to the view for rendering.
 */
class NewsfeedProvider
{
    /** @var Language */
    private $language;

    /** @var bool */
    private $isDebug = false;

    /** @param Language $language */
    public function __construct(Language $language)
    {
        $this->language = $language;
    }

    /**
     * Get newsfeed data as an array for rendering
     *
     * @return array|null Newsfeed data or null if unavailable
     */
    public function getNewsfeedData()
    {
        $newsfeedUrls = $this->getNewsfeedUrls();
        $languageCode = $this->language->getLocaleLanguageCode();

        foreach ($newsfeedUrls as $newsfeedUrl) {
            if (!$newsfeedUrl['show']) {
                continue;
            }

            $url = $languageCode === 'de' ? $newsfeedUrl['de_url'] : $newsfeedUrl['en_url'];
            $requester = new NewsfeedRequester($newsfeedUrl['id'] . '_' . $languageCode, $url);
            $requester->setIsDebug($this->isDebug);

            $data = $requester->returnData();
            if ($data !== null) {
                return $data;
            }
        }

        return null;
    }

    /**
     * Set debug mode (bypasses cache and uses e2e-tests folder)
     *
     * @param bool $isDebug
     * @return void
     */
    public function setIsDebug(bool $isDebug)
    {
        $this->isDebug = $isDebug;
    }

    /**
     * Count the number of Pro-only features in the newsfeed data
     *
     * @param array $data
     * @return int
     */
    public function countProFeatures(array $data): int
    {
        $count = 0;

        foreach ($data['highlights'] ?? [] as $item) {
            if (!empty($item['pro_only'])) {
                $count++;
            }
        }

        foreach ($data['fixes'] ?? [] as $item) {
            if (!empty($item['pro_only'])) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get configured newsfeed URLs
     *
     * @return array
     */
    private function getNewsfeedUrls(): array
    {
        $testFolder = $this->isDebug ? '/e2e-tests/' : '/';

        return [
            [
                'id'     => 'newsfeed',
                'show'   => true,
                'de_url' => 'https://wp-staging.com/newsfeed' . $testFolder . 'newsfeed-de.json',
                'en_url' => 'https://wp-staging.com/newsfeed' . $testFolder . 'newsfeed-en.json',
            ],
        ];
    }
}
