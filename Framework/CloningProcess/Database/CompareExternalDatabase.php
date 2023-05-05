<?php

namespace WPStaging\Framework\CloningProcess\Database;

use WPStaging\Framework\Database\DbInfo;
use WPStaging\Framework\Database\WpDbInfo;
use WPStaging\Core\WPStaging;

class CompareExternalDatabase
{
    /*
     * @var \WPStaging\Framework\Database\DbInfo
     */
    protected $stagingDbInfo;

    /*
     * @var \WPStaging\Framework\Database\DbInfo
     */
    protected $productionDbInfo;

    /*
     * @var bool
     */
    protected $isProductionDbConnected;

    /*
    * @param string $hostServer
    * @param string $user
    * @param string $password
    * @param string $database
    * @param string $useSsl
    */
    public function __construct($hostServer, $user, $password, $database, $useSsl = false)
    {
        $this->stagingDbInfo    = new DbInfo($hostServer, $user, $password, $database, $useSsl);
        $this->productionDbInfo = new WpDbInfo(WPStaging::getInstance()->get("wpdb"));
    }

    /*
     * @return array
     */
    public function maybeGetComparison()
    {
        $stagingDbError = $this->stagingDbInfo->getError();
        if ($stagingDbError !== null) {
            return [
                "success"    => false,
                'error_type' => 'connection',
                "message"    => $stagingDbError
            ];
        }

        // DB properties are equal. Do nothing
        if ($this->productionDbInfo->toArray() === $this->stagingDbInfo->toArray()) {
            return [
                "success" => true
            ];
        }

        // DB Properties are different. Get comparison table
        return [
            "success"    => false,
            'error_type' => 'comparison',
            "checks"     => [
                [
                    "name"       => __('DB Collation'),
                    "production" => $this->productionDbInfo->getDbCollation(),
                    "staging"    => $this->stagingDbInfo->getDbCollation(),
                ],
                [
                    "name"       => __('DB Storage Engine'),
                    "production" => $this->productionDbInfo->getDbEngine(),
                    "staging"    => $this->stagingDbInfo->getDbEngine(),
                ],
                [
                    "name"       => __('MySQL Server Version'),
                    "production" => $this->productionDbInfo->getMySqlServerVersion(),
                    "staging"    => $this->stagingDbInfo->getMySqlServerVersion(),
                ]
            ]
        ];
    }
}
