<?php

namespace WPStaging\Staging\Service\Database;

use WPStaging\Framework\Database\SearchReplace;
use WPStaging\Framework\Traits\SerializeTrait;




class SubsiteUrlSearchReplace extends SearchReplace
{
    use SerializeTrait;

 
    private $searchReplace;

 
    private $protectPatterns = [];

 
    private $restoreReplacements = [];

 
    private $mappingAuthorities = [];

 
    private $authorityIds = [];

 
    private $candidatePatternTries = [];

 
    private $fallbackPatterns = [];

 
    private $isWpBakeryActive = false;

 
    private $baseSearch = [];

 
    private $baseReplace = [];









    public function __construct(
        SearchReplace $searchReplace,
        array $mappings,
        bool $includeBareEncoded = false,
        array $exclude = [],
        array $baseSearch = [],
        array $baseReplace = []
    ) {
        parent::__construct();

        $this->searchReplace = $searchReplace;
        $this->baseSearch    = $baseSearch;
        $this->baseReplace   = $baseReplace;
        $this->buildPatterns($mappings, $includeBareEncoded, $exclude);
    }





    public function replace($data)
    {
        if (defined('DISABLE_WPSTG_SEARCH_REPLACE') && (bool)DISABLE_WPSTG_SEARCH_REPLACE) {
            return $data;
        }

        $data = $this->transform($data, true);
        $data = $this->searchReplace->replace($data);

        return $this->transform($data, false);
    }





    public function replaceExtended($data)
    {
        if (defined('DISABLE_WPSTG_SEARCH_REPLACE') && (bool)DISABLE_WPSTG_SEARCH_REPLACE) {
            return $data;
        }

        $hasExtendedDataFilter = function_exists('has_filter')
            && has_filter(SearchReplace::FILTER_REPLACE_EXTENDED_DATA) !== false;

        $data = $this->transform($data, true);
        if ($hasExtendedDataFilter) {
            $data = $this->transformBase64JsonValues($data, true);
        }

        if ($this->isWpBakeryActive) {
            $result = preg_replace_callback(
                '/\[vc_raw_html\](.+?)\[\/vc_raw_html\]/S',
                [$this->searchReplace, 'replaceWpBakeryValues'],
                $data
            );

            if (is_string($result)) {
                $data = $result;
            }
        }

        $data = $this->searchReplace->replace($data);

        if ($hasExtendedDataFilter) {
            $data = $this->applyFilters(
                SearchReplace::FILTER_REPLACE_EXTENDED_DATA,
                $data,
                $this->baseSearch,
                $this->baseReplace
            );
        }

        $data = $this->transform($data, false);

        return $hasExtendedDataFilter ? $this->transformBase64JsonValues($data, false) : $data;
    }




    public function getSmallerSearchLength()
    {
        return $this->searchReplace->getSmallerSearchLength();
    }





    public function setWpBakeryActive($isActive = true)
    {
        $this->isWpBakeryActive = (bool)$isActive;
        $this->searchReplace->setWpBakeryActive($isActive);

        return $this;
    }







    private function buildPatterns(array $mappings, bool $includeBareEncoded, array $exclude)
    {
        $excludePattern = '';
        foreach ($exclude as $excludeString) {
            $excludePattern .= $excludeString . '(*SKIP)(*FAIL)|';
        }

        $mappingPairs        = $this->getMappingPairs($mappings);
        $claimedSources      = [];
        $protocolRelative    = [];
        $bareEncoded         = [];
        $representedSources  = [];
        foreach ($mappingPairs as $mappingPair) {
            $claimedSources[$this->getSourceKey($mappingPair['source'])] = true;
            $authority = $this->getAuthority($mappingPair['source']);
            if ($authority === '') {
                continue;
            }

            $authorityKey = strtolower($authority);
            if (!isset($this->authorityIds[$authorityKey])) {
                $this->authorityIds[$authorityKey] = count($this->authorityIds);
            }

            $marker = $this->createAuthorityMarker($this->authorityIds[$authorityKey]);
            $this->mappingAuthorities[$authorityKey] = $marker;
            $this->mappingAuthorities[strtolower(rawurlencode($authority))] = $marker;
        }

        $representations     = [];
        $representationOrder = 0;
        foreach ($mappingPairs as $mappingPair) {
            $authorityId = $this->getAuthorityId($mappingPair['source']);
            $mappingRepresentations = $this->getRepresentations(
                $mappingPair['source'],
                $mappingPair['target'],
                $includeBareEncoded,
                $claimedSources,
                $protocolRelative,
                $bareEncoded
            );

            foreach ($mappingRepresentations as $representation) {
                $representation['order'] = $representationOrder++;
                $representation['authorityId'] = $authorityId;
                $representations[]       = $representation;
            }
        }

        usort($representations, function (array $first, array $second): int {
            $lengthComparison = strlen($second['source']) <=> strlen($first['source']);
            if ($lengthComparison !== 0) {
                return $lengthComparison;
            }

            return $first['order'] <=> $second['order'];
        });

        $index = 0;
        foreach ($representations as $representation) {
            if (isset($representedSources[$representation['source']])) {
                continue;
            }

            $representedSources[$representation['source']] = true;

            $placeholder = $this->createPlaceholder($index++);
            $boundary    = '(?=$|%(?i:2f|3f|23)|[^A-Za-z0-9._~!$&\'()*+,;=:@%\-\x80-\xFF])';

            $patternIndex = count($this->protectPatterns);
            $this->protectPatterns[] = [
                'source'      => $representation['source'],
                'pattern'     => '#' . $excludePattern . $representation['leftBoundary'] . $this->quoteRepresentation($representation) . $boundary . '#',
                'replacement' => $placeholder,
            ];
            $this->restoreReplacements[$placeholder] = $representation['target'];

            if ($representation['authorityId'] === null) {
                $this->fallbackPatterns[] = $patternIndex;
            } else {
                $candidateSuffix = substr($representation['source'], $representation['prefixLength']);
                $candidateSuffix = strtr(strtolower($candidateSuffix), $this->mappingAuthorities);
                $this->indexCandidatePattern((int)$representation['authorityId'], $candidateSuffix, $patternIndex);
            }
        }
    }





    private function getMappingPairs(array $mappings): array
    {
        $pairs   = [];
        $targets = [];
        $order   = 0;
        foreach ($mappings as $mapping) {
            if (!is_array($mapping)) {
                continue;
            }

            $candidates = [
                [$mapping['sourceSiteUrl'] ?? '', $mapping['targetSiteUrl'] ?? ''],
                [$mapping['sourceHomeUrl'] ?? '', $mapping['targetHomeUrl'] ?? ''],
            ];

            foreach ($candidates as $candidate) {
                $source = rtrim((string)$candidate[0], '/');
                $target = rtrim((string)$candidate[1], '/');
                if ($source === '' || $target === '') {
                    continue;
                }

                $pairKey = $source . "\0" . $target;
                if (!isset($pairs[$pairKey])) {
                    $pairs[$pairKey] = [
                        'source' => $source,
                        'target' => $target,
                        'order'  => $order++,
                    ];
                }

                $targets[$target] = true;
            }
        }

        foreach (array_keys($targets) as $target) {
            $pairKey = $target . "\0" . $target;
            if (isset($pairs[$pairKey])) {
                continue;
            }

            $pairs[$pairKey] = [
                'source' => $target,
                'target' => $target,
                'order'  => $order++,
            ];
        }

        $pairs = array_values($pairs);
        usort($pairs, function (array $first, array $second): int {
            $lengthComparison = strlen($second['source']) <=> strlen($first['source']);
            if ($lengthComparison !== 0) {
                return $lengthComparison;
            }

            return $first['order'] <=> $second['order'];
        });

        return $pairs;
    }







    private function getRepresentations(
        string $source,
        string $target,
        bool $includeBareEncoded,
        array $claimedSources,
        array &$protocolRelative,
        array &$bareEncoded
    ): array {
        $sourceWithoutScheme = $this->stripScheme($source);
        $targetWithoutScheme = $this->stripScheme($target);
        $representations     = [];
        $sourceScheme        = strtolower((string)parse_url($source, PHP_URL_SCHEME));
        $sourceVariants      = [$sourceScheme . '://' . $sourceWithoutScheme];

        foreach (['http', 'https'] as $fallbackScheme) {
            $fallbackUrl = $fallbackScheme . '://' . $sourceWithoutScheme;
            if ($fallbackScheme === $sourceScheme || isset($claimedSources[$this->getSourceKey($fallbackUrl)])) {
                continue;
            }

            $sourceVariants[] = $fallbackUrl;
        }

        foreach ($sourceVariants as $sourceUrl) {
            $representations = array_merge($representations, $this->getFullUrlRepresentations($sourceUrl, $target));
        }

        $schemeLessKey = $this->getSchemeLessSourceKey($source);
        if (!isset($protocolRelative[$schemeLessKey])) {
            $protocolRelative[$schemeLessKey] = true;

            $sourceProtocolRelative = '//' . $sourceWithoutScheme;
            $targetProtocolRelative = '//' . $targetWithoutScheme;
            $sourcePrefix            = '//' . $this->getAuthority($source);
            $escapedSource           = str_replace('/', '\/', $sourceProtocolRelative);
            $escapedTarget           = str_replace('/', '\/', $targetProtocolRelative);

            $representations[] = [
                'source'       => $sourceProtocolRelative,
                'target'       => $targetProtocolRelative,
                'prefixLength' => strlen($sourcePrefix),
                'leftBoundary' => '',
            ];
            $representations[] = [
                'source'       => $escapedSource,
                'target'       => $escapedTarget,
                'prefixLength' => strlen(str_replace('/', '\/', $sourcePrefix)),
                'leftBoundary' => '',
            ];
            $representations[] = [
                'source'       => rawurlencode($sourceProtocolRelative),
                'target'       => rawurlencode($targetProtocolRelative),
                'prefixLength' => strlen(rawurlencode($sourcePrefix)),
                'leftBoundary' => '',
            ];
        }

        if ($includeBareEncoded && !isset($bareEncoded[$schemeLessKey])) {
            $bareEncoded[$schemeLessKey] = true;
            $sourceAuthority            = $this->getAuthority($source);
            $representations[] = [
                'source'       => str_replace('/', '%2F', $sourceWithoutScheme),
                'target'       => str_replace('/', '%2F', $targetWithoutScheme),
                'prefixLength' => strlen($sourceAuthority),
                'leftBoundary' => '(?:(?<![A-Za-z0-9._@\-\x80-\xFF])|(?<=%(?i:[01][0-9a-f]|20|2[1-9a-cf]|3[a-f]|5[b-e]|60|7[b-f])))',
            ];
        }

        return $representations;
    }




    private function getFullUrlRepresentations(string $source, string $target): array
    {
        $sourcePrefix  = strtolower((string)parse_url($source, PHP_URL_SCHEME)) . '://' . $this->getAuthority($source);
        $escapedSource = str_replace('/', '\/', $source);
        $escapedTarget = str_replace('/', '\/', $target);

        return [
            [
                'source'       => $source,
                'target'       => $target,
                'prefixLength' => strlen($sourcePrefix),
                'leftBoundary' => '',
            ],
            [
                'source'       => $escapedSource,
                'target'       => $escapedTarget,
                'prefixLength' => strlen(str_replace('/', '\/', $sourcePrefix)),
                'leftBoundary' => '',
            ],
            [
                'source'       => rawurlencode($source),
                'target'       => rawurlencode($target),
                'prefixLength' => strlen(rawurlencode($sourcePrefix)),
                'leftBoundary' => '',
            ],
        ];
    }

    private function getSourceKey(string $url): string
    {
        return strtolower((string)parse_url($url, PHP_URL_SCHEME)) . '://' . $this->getSchemeLessSourceKey($url);
    }

    private function getSchemeLessSourceKey(string $url): string
    {
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            return strtolower($this->stripScheme($url));
        }

        $key = strtolower((string)$parts['host']);
        if (isset($parts['port'])) {
            $key .= ':' . (int)$parts['port'];
        }

        return $key . (isset($parts['path']) ? (string)$parts['path'] : '');
    }

    private function getAuthority(string $url): string
    {
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            return '';
        }

        $authority = (string)$parts['host'];
        if (isset($parts['port'])) {
            $authority .= ':' . (int)$parts['port'];
        }

        return $authority;
    }




    private function quoteRepresentation(array $representation): string
    {
        $prefix = substr($representation['source'], 0, $representation['prefixLength']);
        $suffix = substr($representation['source'], $representation['prefixLength']);

        return '(?i:' . preg_quote($prefix, '#') . ')' . $this->quotePercentEncodedLiteral($suffix);
    }

    private function quotePercentEncodedLiteral(string $value): string
    {
        $pattern = '';
        $length  = strlen($value);
        for ($index = 0; $index < $length; $index++) {
            if ($value[$index] !== '%' || $index + 2 >= $length || !ctype_xdigit($value[$index + 1] . $value[$index + 2])) {
                $pattern .= preg_quote($value[$index], '#');
                continue;
            }

            $pattern .= '%' . $this->quoteHexDigit($value[$index + 1]) . $this->quoteHexDigit($value[$index + 2]);
            $index += 2;
        }

        return $pattern;
    }

    private function quoteHexDigit(string $digit): string
    {
        if (!ctype_alpha($digit)) {
            return $digit;
        }

        return '[' . strtolower($digit) . strtoupper($digit) . ']';
    }








    private function transformBase64JsonValues(string $data, bool $protect): string
    {
        $trimmed = ltrim($data);
        if ($trimmed === '' || ($trimmed[0] !== '{' && $trimmed[0] !== '[')) {
            return $data;
        }

        json_decode($data, true, 512, JSON_BIGINT_AS_STRING);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $data;
        }

        $result = '';
        $cursor = 0;
        $match  = [];
        while (preg_match('/"(?:\\\\.|[^"\\\\])*"/S', $data, $match, PREG_OFFSET_CAPTURE, $cursor)) {
            $offset = (int)$match[0][1];
            $token  = (string)$match[0][0];
            $result .= substr($data, $cursor, $offset - $cursor);
            $cursor  = $offset + strlen($token);

            if (preg_match('/\G\s*:/', $data, $unused, 0, $cursor)) {
                $result .= $token;
                continue;
            }

            $value = json_decode($token);
            if (!is_string($value) || $value === '') {
                $result .= $token;
                continue;
            }

            $plain = base64_decode($value, true);
            if ($plain === false) {
                $result .= $token;
                continue;
            }

            $transformed = $this->applyPatterns($plain, $protect);
            if ($transformed === $plain) {
                $result .= $token;
                continue;
            }

            $encoded = json_encode(base64_encode($transformed), JSON_UNESCAPED_SLASHES);

            $result .= is_string($encoded) ? $encoded : $token;
        }

        $result .= substr($data, $cursor);

        return $result;
    }

    private function stripScheme(string $url): string
    {
        return (string)preg_replace('#^https?://#i', '', rtrim($url, '/'));
    }

    private function createPlaceholder(int $index): string
    {
        try {
            $random = bin2hex(random_bytes(16));
        } catch (\Exception $e) {
            $random = sha1(uniqid('wpstg-subsite-', true));
        }

        return '__WPSTG_SUBSITE_URL_' . $index . '_' . $random . '__';
    }

    private function createAuthorityMarker(int $authorityId): string
    {
        return "\x1D" . $authorityId . "\x1E";
    }

    private function getAuthorityId(string $url)
    {
        $authority = $this->getAuthority($url);
        $authorityKey = strtolower($authority);
        if ($authority === '' || !isset($this->authorityIds[$authorityKey])) {
            return null;
        }

        return $this->authorityIds[$authorityKey];
    }






    private function indexCandidatePattern(int $authorityId, string $suffix, int $patternIndex)
    {
        if (!isset($this->candidatePatternTries[$authorityId])) {
            $this->candidatePatternTries[$authorityId] = ['patterns' => [], 'children' => []];
        }

        $node = &$this->candidatePatternTries[$authorityId];
        $length = strlen($suffix);
        for ($index = 0; $index < $length; $index++) {
            $character = $suffix[$index];
            if (!isset($node['children'][$character])) {
                $node['children'][$character] = ['patterns' => [], 'children' => []];
            }

            $node = &$node['children'][$character];
        }

        $node['patterns'][] = $patternIndex;
        unset($node);
    }






    private function getCandidatePatternIndexes(string $data): array
    {
        $selected = array_fill_keys($this->fallbackPatterns, true);
        if (empty($this->mappingAuthorities)) {
            return array_keys($selected);
        }

        $markedData = strtr(strtolower($data), $this->mappingAuthorities);
        $matches = [];
        if (!preg_match_all('/\x1D(\d+)\x1E/', $markedData, $matches, PREG_OFFSET_CAPTURE)) {
            return array_keys($selected);
        }

        foreach ($matches[0] as $matchIndex => $markerMatch) {
            $authorityId = (int)$matches[1][$matchIndex][0];
            $cursor = (int)$markerMatch[1] + strlen((string)$markerMatch[0]);
            if (!isset($this->candidatePatternTries[$authorityId])) {
                continue;
            }

            $node = $this->candidatePatternTries[$authorityId];
            foreach ($node['patterns'] as $patternIndex) {
                $selected[$patternIndex] = true;
            }

            $length = strlen($markedData);
            while ($cursor < $length && isset($node['children'][$markedData[$cursor]])) {
                $node = $node['children'][$markedData[$cursor++]];
                foreach ($node['patterns'] as $patternIndex) {
                    $selected[$patternIndex] = true;
                }
            }
        }

        $indexes = array_keys($selected);
        sort($indexes, SORT_NUMERIC);

        return $indexes;
    }






    private function transform($data, bool $protect)
    {
        switch (gettype($data)) {
            case 'string':
                return $this->transformString($data, $protect);
            case 'array':
                foreach ($data as $key => $value) {
                    $data[$key] = $this->transform($value, $protect);
                }

                return $data;
            case 'object':
                $props = get_object_vars($data);
                if (!empty($props['__PHP_Incomplete_Class_Name'])) {
                    return $data;
                }

                foreach ($props as $key => $value) {
                    if ($key === '' || (isset($key[0]) && ord($key[0]) === 0)) {
                        continue;
                    }

                    $data->{$key} = $this->transform($value, $protect);
                }

                return $data;
        }

        return $data;
    }






    private function transformString(string $data, bool $protect)
    {
        if (!$this->isSerialized($data)) {
            $data = $this->applyPatterns($data, $protect);

            return $this->isWpBakeryActive ? $this->transformWpBakeryValues($data, $protect) : $data;
        }

        $rejected     = false;
        $unserialized = $this->safeMaybeUnserialize($data, [\stdClass::class], $rejected);
        if ($rejected || $unserialized === false) {
            return $data;
        }

        return serialize($this->transform($unserialized, $protect));
    }






    private function transformWpBakeryValues(string $data, bool $protect): string
    {
        $result = preg_replace_callback('/\[vc_raw_html\](.+?)\[\/vc_raw_html\]/S', function (array $matches) use ($protect): string {
            $decoded = base64_decode($matches[1], true);
            if ($decoded === false) {
                return $matches[0];
            }

            return '[vc_raw_html]' . base64_encode($this->applyPatterns($decoded, $protect)) . '[/vc_raw_html]';
        }, $data);

        return is_string($result) ? $result : $data;
    }






    private function applyPatterns(string $data, bool $protect): string
    {
        if (!$protect) {
            if (strpos($data, '__WPSTG_SUBSITE_URL_') === false) {
                return $data;
            }

            $result = preg_replace_callback(
                '/__WPSTG_SUBSITE_URL_\d+_[a-f0-9]{32,40}__/',
                function (array $matches): string {
                    return $this->restoreReplacements[$matches[0]] ?? $matches[0];
                },
                $data
            );

            return is_string($result) ? $result : $data;
        }

        foreach ($this->getCandidatePatternIndexes($data) as $patternIndex) {
            $pattern = $this->protectPatterns[$patternIndex];
 
            if (stripos($data, $pattern['source']) === false) {
                continue;
            }

            $result = preg_replace_callback($pattern['pattern'], function () use ($pattern): string {
                return $pattern['replacement'];
            }, $data);

            if (is_string($result)) {
                $data = $result;
            }
        }

        return $data;
    }
}
