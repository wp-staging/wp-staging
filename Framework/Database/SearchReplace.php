<?php

namespace WPStaging\Framework\Database;

use RuntimeException;

class SearchReplace
{
    /** @var array */
    private $search;

    /** @var array */
    private $replace;

    /** @var array */
    private $exclude;

    /** @var bool */
    private $caseSensitive;

    /** @var string */
    private $currentSearch;

    /** @var string */
    private $currentReplace;

    public function __construct(array $search = [], array $replace = [], $caseSensitive = true, array $exclude = [])
    {
        $this->search = $search;
        $this->replace = $replace;
        $this->caseSensitive = $caseSensitive;
        $this->exclude = $exclude;
    }

    /**
     * @param array|object|string $data
     * @return array|object|string
     */
    public function replace($data)
    {
        if (!$this->search || !$this->replace) {
            return $data;
        }

        $totalSearch = count($this->search);
        $totalReplace = count($this->replace);
        if ($totalSearch !== $totalReplace) {
            throw new RuntimeException(
                sprintf(
                    'Can not search and replace. There are %d items to search and %d items to replace',
                    $totalSearch,
                    $totalReplace
                )
            );
        }

        for ($i = 0; $i < $totalSearch; $i++) {
            $this->currentSearch = (string)$this->search[$i];
            $this->currentReplace = (string)$this->replace[$i];
            $data = $this->walker($data);
        }

        return $data;
    }

    public function setSearch(array $search)
    {
        $this->search = $search;
        return $this;
    }

    public function setReplace(array $replace)
    {
        $this->replace = $replace;
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

    /**
     * @param string|array|object $data
     * @return string|array|object|bool|int|float|null
     */
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

    /**
     * @param string $data
     * @return string|array|object|bool|int|float|null
     */
    private function replaceString($data)
    {
        if (!is_serialized($data)) {
            return $this->strReplace($data);
        }

        // PDO instances can not be serialized or unserialized
        if (strpos($data, 'O:3:"PDO":0:') !== false) {
            return $data;
        }

        // DateTime object can not be unserialized.
        // Would throw PHP Fatal error:  Uncaught Error: Invalid serialization data for DateTime object in
        // Bug PHP https://bugs.php.net/bug.php?id=68889&thanks=6 and https://github.com/WP-Staging/wp-staging-pro/issues/74
        if (strpos($data, 'O:8:"DateTime":0:') !== false) {
            return $data;
        }

        // Some unserialized data cannot be re-serialized eg. SimpleXMLElements
        $unserialized = @unserialize($data);
        if ($unserialized !== false) {
            return serialize($this->walker($unserialized));
        }

        return $data;
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
        // This is not reliable as JsonSerializable and Serializable interfaces can record data into database
        // but get_object_vars won't be able to fetch them to replace
        // TODO use reflection to make sure even protected and private properties are searched and replaced
        $props = get_object_vars($data);
        if (!empty($props['__PHP_Incomplete_Class_Name'])) {
            return $data;
        }

        foreach ($props as $key => $value) {
            if ($key === '' || ord($key[0]) === 0) {
                continue;
            }
            $data->{$key} = $this->walker($value);
        }

        return $data;
    }

    private function strReplace($data)
    {
        // TODO: This filter doesn't really belong in a universally-used class, but usages of the class may need to be checked on whether they need to apply it
        // $excludes = apply_filters('wpstg_clone_searchreplace_excl', []);

        $regexExclude = '';
        foreach ($this->exclude as $excludeString) {
            //TODO: I changed (FAIL) to (*FAIL) because that's what tutorials say is the right syntax. This may need testing
            $regexExclude .= $excludeString . '(*SKIP)(*FAIL)|';
        }

        $pattern = '#' . $regexExclude . preg_quote($this->currentSearch, null) . '#';
        if (!$this->caseSensitive) {
            $pattern .= 'i';
        }

        return preg_replace($pattern, $this->currentReplace, $data);
    }
}
