<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\FilterJobs;

use Firebear\ImportExport\Model\Migration\Config;
use Firebear\ImportExport\Model\Migration\DbConnection;

/**
 * @inheritdoc
 */
class AttributeId implements FilterJobsInterface
{
    /**
     * @var DbConnection
     */
    protected $dbConnection;

    /**
     * @var int
     */
    protected $sourceEntityTypeId;

    /**
     * @var array
     */
    protected $attributeCodes = [];

    /**
     * @var array
     */
    protected $attributeIds = [];

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param DbConnection $dbConnection
     * @param int $sourceEntityTypeId
     * @param array $attributeCodes
     * @param Config $config
     */
    public function __construct(
        DbConnection $dbConnection,
        Config $config,
        int $sourceEntityTypeId,
        array $attributeCodes
    ) {
        $this->dbConnection = $dbConnection;
        $this->config = $config;
        $this->sourceEntityTypeId = $sourceEntityTypeId;
        $this->attributeCodes = $attributeCodes;
        $this->attributeIds = $this->fetchAttributeIds();
    }

    /**
     * @return array
     */
    protected function fetchAttributeIds()
    {
        $select = $this->dbConnection->getSourceChannel()
            ->select()
            ->from($this->config->getM1Prefix() . 'eav_attribute', ['attribute_id'])
            ->where('entity_type_id = ?', $this->sourceEntityTypeId)
            ->where('attribute_code IN (?)', $this->attributeCodes);

        $attributeIds = $this->dbConnection->getSourceChannel()->fetchCol($select);

        return $attributeIds;
    }

    /**
     * @inheritdoc
     */
    public function apply($field, $select)
    {
        $select->where("{$field} IN (?)", $this->attributeIds);
    }
}
