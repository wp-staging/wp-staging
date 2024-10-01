<?php

namespace WPStaging\Framework\Newsfeed;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Language\Language;

class NewsfeedProvider
{
    /** @var Language */
    private $language;

    private $isDebug = false;

    public function __construct(Language $language)
    {
        $this->language = $language;
    }

    public function getNewsfeed(): string
    {
        $newsfeeds = [];

        $newsfeedUrls = $this->getNewsfeedUrls();
        $languageCode = $this->language->getLocaleLanguageCode();

        foreach ($newsfeedUrls as $newsfeedUrl) {
            if (!$newsfeedUrl['show']) {
                continue;
            }

            $newsfeed = new NewsfeedRequester($newsfeedUrl['id'], $languageCode === 'de' ? $newsfeedUrl['de_url'] : $newsfeedUrl['en_url']);
            $newsfeed->setIsDebug($this->isDebug);
            $newsfeeds[] = $newsfeed->returnData();
        }

        return implode('<hr>', $newsfeeds);
    }

    /**
     * @return void
     */
    public function printNewsfeed()
    {
        echo wp_kses_post($this->getNewsfeed());
    }

    private function getNewsfeedUrls(): array
    {
        return [
            [
                'id' => 'partner_newsfeed',
                'show' => !WPStaging::isPro() && (defined('BORLABS_COOKIE_VERSION') && version_compare(BORLABS_COOKIE_VERSION, '3.2', '<')),
                'de_url' => 'https://wp-staging.com/newsfeed/partner-newsfeed-de.txt',
                'en_url' => 'https://wp-staging.com/newsfeed/partner-newsfeed-en.txt',
            ],
            [
                'id' => 'newsfeed',
                'show' => true,
                'de_url' => 'https://wp-staging.com/newsfeed/newsfeed-de.txt',
                'en_url' => 'https://wp-staging.com/newsfeed/newsfeed-en.txt',
            ]
        ];
    }
}
