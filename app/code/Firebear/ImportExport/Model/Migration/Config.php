<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Config
 * @package Firebear\ImportExport\Model\Migration
 */
class Config
{
    const XML_PATH_HOST = 'firebear_importexport/source_database/host';
    const XML_PATH_USERNAME = 'firebear_importexport/source_database/username';
    const XML_PATH_PASSWORD = 'firebear_importexport/source_database/password';
    const XML_PATH_DATABASE_NAME = 'firebear_importexport/source_database/dbname';
    const XML_PATH_M1_PREFIX = 'firebear_importexport/source_database/m1_prefix';
    const XML_PATH_M2_PREFIX = 'firebear_importexport/source_database/m2_prefix';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_HOST);
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_USERNAME);
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_PASSWORD);
    }

    /**
     * @return string
     */
    public function getDatabase()
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_DATABASE_NAME);
    }

    /**
     * @return string
     */
    public function getM1Prefix()
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_M1_PREFIX);
    }

    /**
     * @return string
     */
    public function getM2Prefix()
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_M2_PREFIX);
    }
}
