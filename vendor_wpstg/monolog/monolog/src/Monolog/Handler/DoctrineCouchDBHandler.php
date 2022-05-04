<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPStaging\Vendor\Monolog\Handler;

use WPStaging\Vendor\Monolog\Logger;
use WPStaging\Vendor\Monolog\Formatter\NormalizerFormatter;
use WPStaging\Vendor\Doctrine\CouchDB\CouchDBClient;
/**
 * CouchDB handler for Doctrine CouchDB ODM
 *
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class DoctrineCouchDBHandler extends \WPStaging\Vendor\Monolog\Handler\AbstractProcessingHandler
{
    private $client;
    public function __construct(\WPStaging\Vendor\Doctrine\CouchDB\CouchDBClient $client, $level = \WPStaging\Vendor\Monolog\Logger::DEBUG, $bubble = \true)
    {
        $this->client = $client;
        parent::__construct($level, $bubble);
    }
    /**
     * {@inheritDoc}
     */
    protected function write(array $record)
    {
        $this->client->postDocument($record['formatted']);
    }
    protected function getDefaultFormatter()
    {
        return new \WPStaging\Vendor\Monolog\Formatter\NormalizerFormatter();
    }
}
