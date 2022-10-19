<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

declare(strict_types=1);

namespace Firebear\ImportExport\Model\Export\SearchTerms;

use Exception;
use Firebear\ImportExport\Helper\Data as FirebearHelper;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\AttributeFactory;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute\Source;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Search\Model\ResourceModel\Query as SearchQueryModel;

/**
 * Class AttributeCollection
 *
 * @package Firebear\ImportExport\Model\Export\SearchTerms
 */
class AttributeCollection extends Collection
{
    /**
     * Search Query fields
     */
    const IS_ACTIVE         = 'is_active';
    const IS_PROCESSED      = 'is_processed';
    const DISPLAY_IN_TERMS  = 'display_in_terms';
    const STORE_ID          = 'store_id';
    const UPDATED_AT        = 'updated_at';
    /** @ */

    /**
     * @var AttributeFactory
     */
    private $attributeFactory;

    /**
     * @var FirebearHelper
     */
    protected $helper;

    /**
     * @var SearchQueryModel
     */
    protected $searchQuery;

    /**
     * AttributeCollection constructor.
     *
     * @param EntityFactory    $entityFactory
     * @param AttributeFactory $attributeFactory
     * @param SearchQueryModel $searchQuery
     * @param FirebearHelper   $helper
     * @throws LocalizedException
     * @throws Exception
     */
    public function __construct(
        EntityFactory $entityFactory,
        AttributeFactory $attributeFactory,
        SearchQueryModel $searchQuery,
        FirebearHelper $helper
    ) {
        $this->attributeFactory = $attributeFactory;
        $this->searchQuery = $searchQuery;
        $this->helper = $helper;
        parent::__construct($entityFactory);

        $tableFields = $this->getAllTableFields();
        foreach ($tableFields as $key => $field) {
            $attributeData = [
                AttributeInterface::ATTRIBUTE_ID => $key,
                AttributeInterface::ATTRIBUTE_CODE => $field['COLUMN_NAME'],
                AttributeInterface::FRONTEND_LABEL => ucwords(str_replace('_', ' ', $field['COLUMN_NAME'])),
                AttributeInterface::BACKEND_TYPE => $field['DATA_TYPE'],
                AttributeInterface::FRONTEND_INPUT => $this->helper->convertTypesTables($field['DATA_TYPE']),
            ];

            switch ($key) {
                case self::UPDATED_AT:
                    $attributeData[AttributeInterface::BACKEND_TYPE] = 'datetime';
                    break;
                case self::STORE_ID:
                    $attributeData[AttributeInterface::FRONTEND_INPUT] = 'select';
                    $attributeData[AttributeInterface::SOURCE_MODEL] = Source\Store::class;
                    break;
                case self::IS_ACTIVE:
                case self::IS_PROCESSED:
                case self::DISPLAY_IN_TERMS:
                    $attributeData[AttributeInterface::FRONTEND_INPUT] = 'select';
                    $attributeData[AttributeInterface::SOURCE_MODEL] = Source\Boolean::class;
                    break;
            }

            $this->addItem(
                $this->attributeFactory->createAttribute(Attribute::class, $attributeData)
            );
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
        $connection = $this->searchQuery->getConnection();
        $tableName = $this->searchQuery->getMainTable();
        $fields = $connection->describeTable($tableName);

        return $fields;
    }
}
