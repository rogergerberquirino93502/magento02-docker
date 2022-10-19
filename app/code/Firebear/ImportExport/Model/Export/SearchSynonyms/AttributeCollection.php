<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\SearchSynonyms;

use Exception;
use Firebear\ImportExport\Helper\Data as FirebearHelper;
use Firebear\ImportExport\Model\Export\SearchSynonyms\AttributeSources\Scope;
use Firebear\ImportExport\Model\Export\SearchSynonyms\SynonymsInterface as Synonyms;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\AttributeFactory;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Search\Model\ResourceModel\SynonymGroup as SynonymResource;

/**
 * Class AttributeCollection
 *
 * @package Firebear\ImportExport\Model\Export\SearchSynonyms
 */
class AttributeCollection extends Collection
{
    /**
     * @var AttributeFactory
     */
    private $attributeFactory;

    /**
     * @var SynonymResource
     */
    private $synonymResource;

    /**
     * @var FirebearHelper
     */
    private $helper;

    /**
     * @var array
     */
    private $exportAttributes = [];

    /**
     * @var array
     */
    private $filterAttributes = [];

    /**
     * @var array
     */
    private $excludedFilterAttributes = [Synonyms::STORE_ID, Synonyms::WEBSITE_ID];

    /**
     * @var array
     */
    private $extensionAttributes = [
        'scope' => [
            'code' => Synonyms::WEBSITE_ID . ":" . Synonyms::STORE_ID,
            'type' => 'smallint',
            'input' => 'select',
            'model' => Scope::class,
        ],
    ];

    /**
     * AttributeCollection constructor.
     *
     * @param EntityFactory $entityFactory
     * @param AttributeFactory $attributeFactory
     * @param SynonymResource $synonymResource
     * @param FirebearHelper $helper
     * @throws Exception
     */
    public function __construct(
        EntityFactory $entityFactory,
        AttributeFactory $attributeFactory,
        SynonymResource $synonymResource,
        FirebearHelper $helper
    ) {
        $this->attributeFactory = $attributeFactory;
        $this->synonymResource = $synonymResource;
        $this->helper = $helper;
        parent::__construct($entityFactory);

        $tableFields = $this->getAllTableFields();
        foreach ($tableFields as $key => $field) {
            $attributeData = [
                AttributeInterface::ATTRIBUTE_ID => $key,
                AttributeInterface::ATTRIBUTE_CODE => $field['COLUMN_NAME'],
                AttributeInterface::FRONTEND_LABEL => ucwords(str_replace('_', ' ', $field['COLUMN_NAME'])),
                AttributeInterface::BACKEND_TYPE => $this->helper->convertTypesTables($field['DATA_TYPE']),
                AttributeInterface::FRONTEND_INPUT => $this->helper->convertTypesTables($field['DATA_TYPE']),
            ];

            $this->addItem(
                $this->attributeFactory->createAttribute(Attribute::class, $attributeData)
            );
        }

        foreach ($this->extensionAttributes as $key => $field) {
            if (!empty($key) && !empty($field['type'])) {
                $label = $field['label'] ?? ucwords(str_replace('_', ' ', $key));
                $input = $field['input'] ?? $this->helper->convertTypesTables($field['type']);
                $attributeData = [
                    AttributeInterface::ATTRIBUTE_ID   => $key,
                    AttributeInterface::ATTRIBUTE_CODE => $field['code'] ?? $key,
                    AttributeInterface::FRONTEND_LABEL => $label,
                    AttributeInterface::BACKEND_TYPE   => $input,
                    AttributeInterface::FRONTEND_INPUT => $input,
                    AttributeInterface::SOURCE_MODEL   => $field['model'] ?? null,
                ];

                $this->addItem(
                    $this->attributeFactory->createAttribute(Attribute::class, $attributeData)
                );
            }
        }
    }

    /**
     * Retrieve All Fields Source (the column descriptions for a table)
     *
     * @return array
     * @throws LocalizedException
     */
    private function getAllTableFields()
    {
        $connection = $this->synonymResource->getConnection();
        $mainTable = $this->synonymResource->getMainTable();
        $fields = $connection->describeTable($mainTable);

        return $fields;
    }

    /**
     * Retrieve entity Attributes for export
     *
     * @return array
     */
    public function getAttributesForExport()
    {
        if (empty($this->exportAttributes)) {
            /** @var Attribute $item */
            foreach ($this->getItems() as $item) {
                if (!in_array($item->getAttributeId(), array_keys($this->extensionAttributes))) {
                    array_push($this->exportAttributes, $item);
                }
            }
        }

        return $this->exportAttributes;
    }

    /**
     * Retrieve entity Attributes for filter
     *
     * @return array
     */
    public function getAttributesForFilter()
    {
        if (empty($this->filterAttributes)) {
            /** @var Attribute $item */
            foreach ($this->getItems() as $item) {
                if (!in_array($item->getAttributeCode(), $this->excludedFilterAttributes)) {
                    array_push($this->filterAttributes, $item);
                }
            }
        }

        return $this->filterAttributes;
    }
}
