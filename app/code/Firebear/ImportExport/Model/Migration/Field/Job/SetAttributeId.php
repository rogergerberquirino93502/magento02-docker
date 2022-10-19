<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\Field\Job;

use Firebear\ImportExport\Model\Migration\DbConnection;
use Firebear\ImportExport\Model\Migration\Config;

/**
 * @inheritdoc
 */
class SetAttributeId extends SetValue
{
    /**
     * @var DbConnection
     */
    protected $dbConnection;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var int
     */
    protected $entityTypeId;

    /**
     * @var string
     */
    protected $attributeCode;

    /**
     * @param DbConnection $dbConnection
     * @param Config $config
     * @param int $entityTypeId
     * @param string $attributeCode
     */
    public function __construct(
        DbConnection $dbConnection,
        Config $config,
        int $entityTypeId,
        string $attributeCode
    ) {
        $this->dbConnection = $dbConnection;
        $this->config = $config;
        $this->entityTypeId = $entityTypeId;
        $this->attributeCode = $attributeCode;
        $attributeId = $this->fetchAttributeId();

        parent::__construct($attributeId);
    }

    /**
     * @return string
     */
    protected function fetchAttributeId()
    {
        $select = $this->dbConnection->getDestinationChannel()
            ->select()
            ->from($this->config->getM2Prefix() . 'eav_attribute', ['attribute_id'])
            ->where('entity_type_id = ?', $this->entityTypeId)
            ->where('attribute_code = ?', $this->attributeCode);

        return $this->dbConnection->getDestinationChannel()->fetchOne($select);
    }
}
