<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\Field\Job;

use Firebear\ImportExport\Model\Migration\Config;
use Firebear\ImportExport\Model\Migration\DbConnection;
use Firebear\ImportExport\Model\Migration\Field\JobInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * @inheritdoc
 */
class MapAttributeId implements JobInterface
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
    protected $sourceEntityTypeId;

    /**
     * @var int
     */
    protected $destinationEntityTypeId;

    /**
     * @var array
     */
    protected $attributeCodeMapping = [];

    /**
     * @var array
     */
    protected $attributeIdMapping = [];

    /**
     * @param DbConnection $dbConnection
     * @param Config $config
     * @param int $sourceEntityTypeId
     * @param int $destinationEntityTypeId
     * @param array $attributeCodeMapping
     */
    public function __construct(
        DbConnection $dbConnection,
        Config $config,
        int $sourceEntityTypeId,
        int $destinationEntityTypeId,
        array $attributeCodeMapping
    ) {
        $this->dbConnection = $dbConnection;
        $this->config = $config;
        $this->sourceEntityTypeId = $sourceEntityTypeId;
        $this->destinationEntityTypeId = $destinationEntityTypeId;
        $this->attributeCodeMapping = $attributeCodeMapping;
    }

    /**
     * @return array
     */
    protected function fetchAttributeIdMapping()
    {
        $sourceSelect = $this->dbConnection->getSourceChannel()
            ->select()
            ->from($this->config->getM1Prefix() . 'eav_attribute', ['attribute_id', 'attribute_code'])
            ->where('entity_type_id = ?', $this->sourceEntityTypeId);

        $sourceAttributes = $this->dbConnection->getSourceChannel()->fetchPairs($sourceSelect);
        $sourceAttributes = array_flip($sourceAttributes);

        $destinationSelect = $this->dbConnection->getDestinationChannel()
            ->select()
            ->from($this->config->getM2Prefix() . 'eav_attribute', ['attribute_id', 'attribute_code'])
            ->where('entity_type_id = ?', $this->destinationEntityTypeId);

        $destinationAttributes = $this->dbConnection->getDestinationChannel()->fetchPairs($destinationSelect);
        $destinationAttributes = array_flip($destinationAttributes);

        $attributeIdMapping = [];

        foreach ($this->attributeCodeMapping as $sourceCode => $destinationCode) {
            if (isset($sourceAttributes[$sourceCode]) && isset($destinationAttributes[$destinationCode])) {
                $attributeIdMapping[$sourceAttributes[$sourceCode]] = $destinationAttributes[$destinationCode];
            }
        }

        return $attributeIdMapping;
    }

    /**
     * @inheritdoc
     */
    public function job(
        $sourceField,
        $sourceValue,
        $destinationFiled,
        $destinationValue,
        $sourceDataRow
    ) {
        if (empty($this->attributeIdMapping)) {
            $this->attributeIdMapping = $this->fetchAttributeIdMapping();
        }

        if (isset($this->attributeIdMapping[$sourceValue])) {
            return $this->attributeIdMapping[$sourceValue];
        }

        throw new LocalizedException(__("Source attribute id %1 not found in mapping.", $sourceValue));
    }
}
