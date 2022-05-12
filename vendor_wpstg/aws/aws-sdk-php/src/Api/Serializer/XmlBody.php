<?php

namespace WPStaging\Vendor\Aws\Api\Serializer;

use WPStaging\Vendor\Aws\Api\MapShape;
use WPStaging\Vendor\Aws\Api\Service;
use WPStaging\Vendor\Aws\Api\Shape;
use WPStaging\Vendor\Aws\Api\StructureShape;
use WPStaging\Vendor\Aws\Api\ListShape;
use WPStaging\Vendor\Aws\Api\TimestampShape;
use XMLWriter;
/**
 * @internal Formats the XML body of a REST-XML services.
 */
class XmlBody
{
    /** @var \Aws\Api\Service */
    private $api;
    /**
     * @param Service $api API being used to create the XML body.
     */
    public function __construct(\WPStaging\Vendor\Aws\Api\Service $api)
    {
        $this->api = $api;
    }
    /**
     * Builds the XML body based on an array of arguments.
     *
     * @param Shape $shape Operation being constructed
     * @param array $args  Associative array of arguments
     *
     * @return string
     */
    public function build(\WPStaging\Vendor\Aws\Api\Shape $shape, array $args)
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $this->format($shape, $shape['locationName'] ?: $shape['name'], $args, $xml);
        $xml->endDocument();
        return $xml->outputMemory();
    }
    private function startElement(\WPStaging\Vendor\Aws\Api\Shape $shape, $name, \XMLWriter $xml)
    {
        $xml->startElement($name);
        if ($ns = $shape['xmlNamespace']) {
            $xml->writeAttribute(isset($ns['prefix']) ? "xmlns:{$ns['prefix']}" : 'xmlns', $shape['xmlNamespace']['uri']);
        }
    }
    private function format(\WPStaging\Vendor\Aws\Api\Shape $shape, $name, $value, \XMLWriter $xml)
    {
        // Any method mentioned here has a custom serialization handler.
        static $methods = ['add_structure' => \true, 'add_list' => \true, 'add_blob' => \true, 'add_timestamp' => \true, 'add_boolean' => \true, 'add_map' => \true, 'add_string' => \true];
        $type = 'add_' . $shape['type'];
        if (isset($methods[$type])) {
            $this->{$type}($shape, $name, $value, $xml);
        } else {
            $this->defaultShape($shape, $name, $value, $xml);
        }
    }
    private function defaultShape(\WPStaging\Vendor\Aws\Api\Shape $shape, $name, $value, \XMLWriter $xml)
    {
        $this->startElement($shape, $name, $xml);
        $xml->text($value);
        $xml->endElement();
    }
    private function add_structure(\WPStaging\Vendor\Aws\Api\StructureShape $shape, $name, array $value, \XMLWriter $xml)
    {
        $this->startElement($shape, $name, $xml);
        foreach ($this->getStructureMembers($shape, $value) as $k => $definition) {
            $this->format($definition['member'], $definition['member']['locationName'] ?: $k, $definition['value'], $xml);
        }
        $xml->endElement();
    }
    private function getStructureMembers(\WPStaging\Vendor\Aws\Api\StructureShape $shape, array $value)
    {
        $members = [];
        foreach ($value as $k => $v) {
            if ($v !== null && $shape->hasMember($k)) {
                $definition = ['member' => $shape->getMember($k), 'value' => $v];
                if ($definition['member']['xmlAttribute']) {
                    // array_unshift_associative
                    $members = [$k => $definition] + $members;
                } else {
                    $members[$k] = $definition;
                }
            }
        }
        return $members;
    }
    private function add_list(\WPStaging\Vendor\Aws\Api\ListShape $shape, $name, array $value, \XMLWriter $xml)
    {
        $items = $shape->getMember();
        if ($shape['flattened']) {
            $elementName = $name;
        } else {
            $this->startElement($shape, $name, $xml);
            $elementName = $items['locationName'] ?: 'member';
        }
        foreach ($value as $v) {
            $this->format($items, $elementName, $v, $xml);
        }
        if (!$shape['flattened']) {
            $xml->endElement();
        }
    }
    private function add_map(\WPStaging\Vendor\Aws\Api\MapShape $shape, $name, array $value, \XMLWriter $xml)
    {
        $xmlEntry = $shape['flattened'] ? $shape['locationName'] : 'entry';
        $xmlKey = $shape->getKey()['locationName'] ?: 'key';
        $xmlValue = $shape->getValue()['locationName'] ?: 'value';
        $this->startElement($shape, $name, $xml);
        foreach ($value as $key => $v) {
            $this->startElement($shape, $xmlEntry, $xml);
            $this->format($shape->getKey(), $xmlKey, $key, $xml);
            $this->format($shape->getValue(), $xmlValue, $v, $xml);
            $xml->endElement();
        }
        $xml->endElement();
    }
    private function add_blob(\WPStaging\Vendor\Aws\Api\Shape $shape, $name, $value, \XMLWriter $xml)
    {
        $this->startElement($shape, $name, $xml);
        $xml->writeRaw(\base64_encode($value));
        $xml->endElement();
    }
    private function add_timestamp(\WPStaging\Vendor\Aws\Api\TimestampShape $shape, $name, $value, \XMLWriter $xml)
    {
        $this->startElement($shape, $name, $xml);
        $timestampFormat = !empty($shape['timestampFormat']) ? $shape['timestampFormat'] : 'iso8601';
        $xml->writeRaw(\WPStaging\Vendor\Aws\Api\TimestampShape::format($value, $timestampFormat));
        $xml->endElement();
    }
    private function add_boolean(\WPStaging\Vendor\Aws\Api\Shape $shape, $name, $value, \XMLWriter $xml)
    {
        $this->startElement($shape, $name, $xml);
        $xml->writeRaw($value ? 'true' : 'false');
        $xml->endElement();
    }
    private function add_string(\WPStaging\Vendor\Aws\Api\Shape $shape, $name, $value, \XMLWriter $xml)
    {
        if ($shape['xmlAttribute']) {
            $xml->writeAttribute($shape['locationName'] ?: $name, $value);
        } else {
            $this->defaultShape($shape, $name, $value, $xml);
        }
    }
}
