<?php

namespace WPStaging\Framework\Newsfeed;

use WPStaging\Framework\Language\Language;







class NewsfeedProvider
{
 
    private $language;

 
    private $isDebug = false;

 
    public function __construct(Language $language)
    {
        $this->language = $language;
    }






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







    public function setIsDebug(bool $isDebug)
    {
        $this->isDebug = $isDebug;
    }







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
