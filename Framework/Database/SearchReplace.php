<?php

namespace WPStaging\Framework\Database;

use WPStaging\Framework\Traits\ApplyFiltersTrait;
use WPStaging\Framework\Traits\DebugLogTrait;
use WPStaging\Framework\Traits\SerializeTrait;
use WPStaging\Framework\Traits\UrlTrait;





class SearchReplace
{
    use ApplyFiltersTrait;
    use DebugLogTrait;
    use SerializeTrait;
    use UrlTrait;









    const FILTER_REPLACE_EXTENDED_DATA = 'wpstg.database.searchreplace.replace_extended_data';

 
    const SINGLE_PASS_PATTERN_MAX_LENGTH = 8192;

 
    const MAX_SERIALIZED_NESTING = 8;

 
    const WALK_ALLOWANCE_SLACK = 8192;

 
    private $search;

 
    private $replace;

 
    private $exclude;

 
    private $caseSensitive;

 
    private $currentSearch;

 
    private $currentReplace;

 
    private $isWpBakeryActive;

 
    private $singlePass = false;

 
    private $singlePassActive = false;

 
    private $escapedReplace;

 
    private $replacementMap;

 
    private $replacementPattern;

    protected $smallerReplacement = PHP_INT_MAX;

 
    private $serializedNesting = 0;

 
    private $walkBudget = 0;

 
    private $replaceInputLength;

 
    private $walkRefusal;

    public function __construct(array $search = [], array $replace = [], $caseSensitive = true, array $exclude = [])
    {
        $this->search           = $search;
        $this->replace          = $replace;
        $this->caseSensitive    = $caseSensitive;
        $this->exclude          = $exclude;
        $this->isWpBakeryActive = false;
    }




    public function getSmallerSearchLength()
    {
        if ($this->smallerReplacement < PHP_INT_MAX) {
            return $this->smallerReplacement;
        }

        foreach ($this->search as $search) {
            if (strlen($search) < $this->smallerReplacement) {
                $this->smallerReplacement = strlen($search);
            }
        }

        return $this->smallerReplacement;
    }





    public function replace($data)
    {
        $inputLength = is_string($data) ? strlen($data) : null;
        $data        = $this->replaceWithEveryPair($data);
        $this->logWalkRefusal($inputLength);

        return $data;
    }






    protected function replaceWithEveryPair($data)
    {
        if (defined('DISABLE_WPSTG_SEARCH_REPLACE') && (bool)DISABLE_WPSTG_SEARCH_REPLACE) {
            return $data;
        }

        if (!$this->search || !$this->replace) {
            return $data;
        }

        $totalSearch  = count($this->search);
        $totalReplace = count($this->replace);
        if ($totalSearch !== $totalReplace) {
            throw new \RuntimeException(
                sprintf(
                    'Can not search and replace. There are %d items to search and %d items to replace',
                    $totalSearch,
                    $totalReplace
                )
            );
        }

        $this->replaceInputLength = is_string($data) ? strlen($data) : null;

        $this->singlePassActive = $this->canUseSinglePassReplacement();
        if ($this->singlePassActive) {
            $this->prepareSinglePassReplacement();
            return $this->walker($data);
        }

        $this->escapedReplace = $this->escapedReplace ?? $this->escapeBackreferences($this->replace);
        for ($i = 0; $i < $totalSearch; $i++) {
            $this->currentSearch  = (string)$this->search[$i];
            $this->currentReplace = $this->escapedReplace[$i];
            $data                 = $this->walker($data);
        }

        return $data;
    }








    public function replaceExtended($data)
    {
        if (defined('DISABLE_WPSTG_SEARCH_REPLACE') && (bool)DISABLE_WPSTG_SEARCH_REPLACE) {
            return $data;
        }

        $inputLength = is_string($data) ? strlen($data) : null;
        if ($this->isWpBakeryActive) {
            $data = preg_replace_callback('/\[vc_raw_html\](.+?)\[\/vc_raw_html\]/S', [$this, 'replaceWpBakeryValues'], $data);
        }

        $data = $this->replaceWithEveryPair($data);
        $this->logWalkRefusal($inputLength);

        if (!function_exists('has_filter') || has_filter(self::FILTER_REPLACE_EXTENDED_DATA) === false) {
            return $data;
        }

        return $this->applyFilters(self::FILTER_REPLACE_EXTENDED_DATA, $data, $this->search, $this->replace);
    }

    public function replaceWpBakeryValues($matched)
    {
        $data = $this->base64Decode($matched[1]);
        $data = $this->replaceWithEveryPair($data);
        return '[vc_raw_html]' . base64_encode($data) . '[/vc_raw_html]';
    }





    public function setSinglePass(bool $enabled)
    {
        $this->singlePass = $enabled;
        return $this;
    }

    public function hasSearch(): bool
    {
        return !empty($this->search);
    }

    public function setSearch(array $search)
    {
        $this->search = $search;
        $this->replacementMap = null;
        return $this;
    }

    public function setReplace(array $replace)
    {
        $this->replace = $replace;
        $this->replacementMap = null;
        $this->escapedReplace = null;
        return $this;
    }











    public function appendSearchReplacePair(string $search, string $replace)
    {
        $this->search[] = $search;
        $this->replacementMap = null;
        $this->replace[] = $replace;
        $this->escapedReplace = null;
        $this->smallerReplacement = PHP_INT_MAX;
        return $this;
    }

    public function setCaseSensitive($caseSensitive)
    {
        $this->caseSensitive = $caseSensitive;
        return $this;
    }

    public function setExclude(array $exclude)
    {
        $this->exclude = $exclude;
        return $this;
    }






    public function setWpBakeryActive($isActive = true)
    {
        $this->isWpBakeryActive = $isActive;
        return $this;
    }





    private function walker($data)
    {
        switch (gettype($data)) {
            case "string":
                return $this->replaceString($data);
            case "array":
                return $this->replaceArray($data);
            case "object":
                return $this->replaceObject($data);
        }

        return $data;
    }





    private function replaceString($data)
    {
        if (!$this->isSerialized($data)) {
            return $this->strReplace($data);
        }

        return $this->walkSerialized($data, function ($unserialized) {
            return $this->walker($unserialized);
        });
    }

 
    protected function walkSerialized(string $data, callable $walk): string
    {
        $isTopLevel = $this->serializedNesting === 0;
        if ($isTopLevel) {
            $this->walkBudget = $this->walkAllowanceFor(strlen($data));
        } elseif (!$this->reserveNestedDecode(strlen($data))) {
            return $data;
        }

        $rejected     = false;
        $unserialized = $this->safeMaybeUnserialize($data, [\stdClass::class], $rejected);

        if ($rejected || $unserialized === false) {
            return $data;
        }

        $this->serializedNesting++;
        try {
            $walked = serialize($walk($unserialized));
        } finally {
            $this->serializedNesting--;
        }

        $growth = max(0, strlen($walked) - strlen($data));
        if ($isTopLevel && $growth > $this->walkAllowanceFor(strlen($data))) {
            $this->walkRefusal = 'its replacements would grow it past the bytes allowed for it';
            return $data;
        }

        if ($isTopLevel) {
            return $walked;
        }

        $this->walkBudget -= $growth;
        return $walked;
    }

 
    private function reserveNestedDecode(int $length): bool
    {
        if ($this->serializedNesting >= self::MAX_SERIALIZED_NESTING) {
            $this->walkRefusal = sprintf('it holds serialized data nested more than %d levels deep', self::MAX_SERIALIZED_NESTING);
            return false;
        }

        if ($length > $this->walkBudget) {
            $this->walkRefusal = 'decoding its nested serialized data would pass the bytes allowed for it';
            return false;
        }

        $this->walkBudget -= $length;
        return true;
    }





    protected function logWalkRefusal($inputLength)
    {
        if ($this->walkRefusal === null) {
            return;
        }

        $this->debugLog(sprintf(
            'Search & replace skipped serialized data in a value%s, because %s.',
            $inputLength === null ? '' : ' of ' . $inputLength . ' bytes',
            $this->walkRefusal
        ), 'warning');
        $this->walkRefusal = null;
    }

 
    protected function adoptWalkRefusalOf(SearchReplace $searchReplace)
    {
        $this->walkRefusal          = $this->walkRefusal ?? $searchReplace->walkRefusal;
        $searchReplace->walkRefusal = null;
    }

 
    private function walkAllowanceFor(int $topLevelLength): int
    {
        return self::MAX_SERIALIZED_NESTING * ($this->replaceInputLength ?? $topLevelLength) + self::WALK_ALLOWANCE_SLACK;
    }

    private function replaceArray(array $data)
    {
        foreach ($data as $key => $value) {
            $data[$key] = $this->walker($value);
        }

        return $data;
    }

    private function replaceObject($data)
    {
 
 
 
        $props = get_object_vars($data);
        if (!empty($props['__PHP_Incomplete_Class_Name'])) {
            return $data;
        }

        foreach ($props as $key => $value) {
            if ($key === '' || (isset($key[0]) && ord($key[0]) === 0)) {
                continue;
            }

            $data->{$key} = $this->walker($value);
        }

        return $data;
    }

 
    private function replaceSinglePass(string $data): string
    {
        if ($this->replacementPattern === '') {
            return strtr($data, $this->replacementMap);
        }

        $result = preg_replace_callback($this->replacementPattern, function ($match) {
            return (string)$this->replacementMap[$match[0]];
        }, $data);

        if ($result === null) {
            throw new \RuntimeException('Could not search and replace database value. PCRE error: ' . preg_last_error());
        }

        return $result;
    }

 
    private function prepareSinglePassReplacement()
    {
        if ($this->replacementMap !== null) {
            return;
        }

        $replacementMap = array_combine($this->search, $this->replace);
        if (array_key_exists('', $replacementMap)) {
            throw new \InvalidArgumentException('Single-pass search strings must not be empty.');
        }

        uksort($replacementMap, function ($first, $second) {
            return strlen($second) <=> strlen($first);
        });

        $this->replacementMap = $replacementMap;
        $pattern = '#' . implode('|', array_map(function ($search) {
            return preg_quote((string)$search, '#');
        }, array_keys($this->replacementMap))) . '#';
        $this->replacementPattern = strlen($pattern) <= self::SINGLE_PASS_PATTERN_MAX_LENGTH ? $pattern : '';
    }

    private function strReplace($data = '')
    {
        if ($this->singlePassActive) {
            return $this->replaceSinglePass($data);
        }

        $regexExclude = '';
        foreach ($this->exclude as $excludeString) {
 
            $regexExclude .= $excludeString . '(*SKIP)(*FAIL)|';
        }

        $pattern = '#' . $regexExclude . preg_quote($this->currentSearch, '#') . '#';
        if (!$this->caseSensitive) {
            $pattern .= 'i';
        }

        return preg_replace($pattern, $this->currentReplace, $data);
    }





    private function escapeBackreferences(array $replacements): array
    {
        return str_replace(['\\', '$'], ['\\\\', '\\$'], $replacements);
    }

 
    private function canUseSinglePassReplacement(): bool
    {
        return $this->singlePass && $this->caseSensitive && !$this->exclude;
    }
}
