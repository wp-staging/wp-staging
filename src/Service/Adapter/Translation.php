<?php

// TODO PHP7.x; declare(strict_types=1);

namespace WPStaging\Service\Adapter;

use Translations;
use NOOP_Translations;
use Translation_Entry;
use WPStaging\Service\Adapter\Dto\JedDto;

class Translation
{
    const DEFAULT_LANGUAGE = 'en';

    const DEFAULT_PLURAL_FORMS = 'nplurals=2; plural=n != 1;';

    /** @var string */
    private $domain;

    /**
     * @param string $domain
     */
    public function __construct($domain)
    {
        $this->domain = $domain;
    }

    /**
     * @return JedDto
     */
    public function toJed()
    {
        $translations = $this->provideTranslations();

        $jed = new JedDto;
        $jed->setDomain($this->domain);
        $jed->setLanguage($this->getLanguage($translations->headers));
        $jed->setPluralForms($this->getPluralForms($translations->headers));

        /**
         * @var string $key
         * @var Translation_Entry $value
         */
        foreach($translations->entries as $key => $value) {
            $jed->addTranslations($key, $value->translations);
        }

        return $jed;
    }

    /**
     * @return Translations|NOOP_Translations
     */
    private function provideTranslations()
    {
        return get_translations_for_domain($this->domain);
    }

    /**
     * @param array $headers
     * @return string
     */
    private function getLanguage(array $headers = [])
    {
        if (isset($headers['Language']) && $headers['Language']) {
            return strtolower($headers['Language']);
        }

        return self::DEFAULT_LANGUAGE;
    }

    /**
     * @param array $headers
     * @return string
     */
    private function getPluralForms(array $headers = [])
    {
        if (isset($headers['Plural-Forms']) && $headers['Plural-Forms']) {
            return (string) $headers['Plural-Forms'];
        }

        return self::DEFAULT_PLURAL_FORMS;
    }
}
