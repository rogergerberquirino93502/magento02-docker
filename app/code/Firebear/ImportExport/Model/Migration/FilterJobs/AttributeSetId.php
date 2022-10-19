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
class AttributeSetId implements FilterJobsInterface
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
    protected $attributeSetIds = [];

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param DbConnection $dbConnection
     * @param int $sourceEntityTypeId
     * @param Config $config
     */
    public function __construct(
        DbConnection $dbConnection,
        Config $config,
        int $sourceEntityTypeId
    ) {
        $this->dbConnection = $dbConnection;
        $this->config = $config;
        $this->sourceEntityTypeId = $sourceEntityTypeId;
        $this->attributeSetIds = $this->fetchAttributeSetIds();
    }

    /**
     * @return array
     */
    protected function fetchAttributeSetIds()
    {
        $select = $this->dbConnection->getSourceChannel()
            ->select()
            ->from($this->config->getM1Prefix() . 'eav_attribute_set', ['attribute_set_id'])
            ->where('entity_type_id = ?', $this->sourceEntityTypeId)
            ->where('NOT attribute_set_name = ?', 'Default');

        $attributeSetIds = $this->dbConnection->getSourceChannel()->fetchCol($select);

        return $attributeSetIds;
    }

    /**
     * @inheritdoc
     */
    public function apply($field, $select)
    {
        $select->where("{$field} IN (?)", $this->attributeSetIds);
    }
}
