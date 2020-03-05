<?php

// TODO PHP7.x; declare(strict_types=1);

namespace WPStaging\Service\Adapter\Dto;

use JsonSerializable;

class JedDto implements JsonSerializable
{
    /** @var string */
    private $domain;

    /** @var string */
    private $language;

    /** @var string */
    private $pluralForms;

    /** @var array */
    private $translations = [];

    /**
     * @return array
     */
    public function toArray()
    {
        $settings = [
            '' => [
                'domain' => $this->domain,
                'lang' => $this->language,
                'plural_forms' => $this->pluralForms,
            ],
        ];

        /** @noinspection AdditionOperationOnArraysInspection */
        return $settings + $this->translations;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return string
     */
    public function getPluralForms()
    {
        return $this->pluralForms;
    }

    /**
     * @param string $pluralForms
     */
    public function setPluralForms($pluralForms)
    {
        $this->pluralForms = $pluralForms;
    }

    /**
     * @return array
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param array $translations
     */
    public function setTranslations(array $translations = [])
    {
        $this->translations = $translations;
    }

    /**
     * @param string $key
     * @param array $translations
     */
    public function addTranslations($key, array $translations)
    {
        $this->translations[$key] = $translations;
    }
}
