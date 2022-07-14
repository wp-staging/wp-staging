<?php

namespace WPStaging\Framework\Filesystem\Filters;

use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Utils\Strings;

class ExcludeFilter
{
    const NAME_BEGINS_WITH = 'name_begins_with';
    const NAME_ENDS_WITH = 'name_ends_with';
    const NAME_EXACT_MATCHES = 'name_exact_matches';
    const NAME_CONTAINS = 'name_contains';

    const SIZE_KB = 'kb';
    const SIZE_MB = 'mb';
    const SIZE_GB = 'gb';

    const SIZE_GREATER_THAN = 'size_greater_than';
    const SIZE_LESS_THAN = 'size_less_than';
    const SIZE_EQUAL_TO = 'size_equal_to';

    /**
     * @var Strings
     */
    private $strUtils;

    /**
     * @var TemplateEngine
     */
    private $templateEngine;

    public function __construct()
    {
        $this->strUtils = new Strings();
        $this->templateEngine = new TemplateEngine();
    }

    /**
     * Convert wpstg exclude rule to glob rule
     *
     * @param string $rule
     * @return string
     */
    public function mapExclude($rule)
    {
        if ($this->strUtils->startsWith($rule, 'ext:')) {
            return '/**/*.' . trim(substr($rule, 4));
        }

        $nameRule = $rule;
        if ($this->strUtils->startsWith($rule, 'file:')) {
            $nameRule = trim(substr($rule, 5));
        } elseif ($this->strUtils->startsWith($rule, 'dir:')) {
            $nameRule = trim(substr($rule, 4));
        }

        $globRule = $this->convertToNameGlob($nameRule);
        if ($this->strUtils->startsWith($rule, 'file:')) {
            // if rule has . that means it was provided with extension
            if (strpos($globRule, '.') !== false) {
                return $globRule;
            }

            return $globRule . '.*';
        }

        return $globRule . '/**';
    }

    /**
     * Return rendered exclude template as output for the wpstg exclude filter size rule
     *
     * @param string $rule
     *
     * @return string
     */
    public function renderSizeExclude($rule)
    {
        list($comparison, $size) = explode(' ', $rule);
        $bytes = (int)$size;
        return $this->templateEngine->render("Backend/views/templates/exclude-filters/file-size-exclude-filter.php", [
            "comparison" => trim($comparison),
            "bytes" => trim($bytes),
            "size" => trim($size)
        ]);
    }

    /**
     * Return rendered exclude template as output for the wpstg exclude filter glob rule
     *
     * @param string $rule
     *
     * @return string
     */
    public function renderGlobExclude($rule)
    {
        if ($this->strUtils->startsWith($rule, 'ext:')) {
            return $this->templateEngine->render("Backend/views/templates/exclude-filters/file-ext-exclude-filter.php", [
                "extension" => trim(substr($rule, 4)),
            ]);
        }

        if ($this->strUtils->startsWith($rule, 'file:')) {
            list($rule, $name) = explode(' ', trim(substr($rule, 5)));
            return $this->templateEngine->render("Backend/views/templates/exclude-filters/file-name-exclude-filter.php", [
                "rule" => trim($rule),
                "name" => trim($name),
            ]);
        }

        if ($this->strUtils->startsWith($rule, 'dir:')) {
            list($rule, $name) = explode(' ', trim(substr($rule, 4)));
            return $this->templateEngine->render("Backend/views/templates/exclude-filters/dir-name-exclude-filter.php", [
                "rule" => trim($rule),
                "name" => trim($name),
            ]);
        }

        return '';
    }

    /**
     * Convert wpstg name rule to glob rule
     *
     * @return string
     */
    private function convertToNameGlob($rule)
    {
        if ($this->strUtils->startsWith($rule, self::NAME_BEGINS_WITH)) {
            $len = strlen(self::NAME_BEGINS_WITH);
            return '/**/' . trim(substr($rule, $len)) . "*";
        }

        if ($this->strUtils->startsWith($rule, self::NAME_ENDS_WITH)) {
            $len = strlen(self::NAME_ENDS_WITH);
            return '/**/*' . trim(substr($rule, $len));
        }

        if ($this->strUtils->startsWith($rule, self::NAME_EXACT_MATCHES)) {
            $len = strlen(self::NAME_EXACT_MATCHES);
            return '/**/' . trim(substr($rule, $len));
        }

        $len = strlen(self::NAME_CONTAINS);
        return '/**/*' . trim(substr($rule, $len)) . "*";
    }
}
