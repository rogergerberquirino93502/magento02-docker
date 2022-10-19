<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\ResourceModel\Import\CustomerComposite;

use Firebear\ImportExport\Model\Import\CustomerComposite;

/**
 * Class Data
 *
 * @package Firebear\ImportExport\Model\ResourceModel\Import\CustomerComposite
 */
class Data extends \Firebear\ImportExport\Model\ResourceModel\Import\Data
{
    /**
     * Entity type
     *
     * @var string
     */
    protected $entityType = CustomerComposite::COMPONENT_ENTITY_CUSTOMER;

    /**
     * Customer attributes
     *
     * @var array
     */
    protected $customerAttributes = [];

    /**
     * Class constructor
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Json\Helper\Data $coreHelper
     * @param string $connectionName
     * @param array $arguments
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        $connection = null,
        array $arguments = []
    ) {
        parent::__construct($context, $jsonHelper, $connection);

        if (isset($arguments['entity_type'])) {
            $this->entityType = $arguments['entity_type'];
        }
        if (isset($arguments['customer_attributes'])) {
            $this->customerAttributes = $arguments['customer_attributes'];
        }
    }

    /**
     * Get next bunch of validated rows.
     *
     * @return array|null
     */
    public function getNextBunch()
    {
        $rows = parent::getNextBunch();
        if ($rows != null) {
            $result = [];
            foreach ($rows as $rowNumber => $rowData) {
                $rowData = $this->prepareRow($rowData);
                if ($rowData !== null) {
                    unset($rowData['_scope']);
                    $result[$rowNumber] = $rowData;
                }
            }
            return $result;
        } else {
            return $rows;
        }
    }

    /**
     * Prepare row
     *
     * @param array $rowData
     * @return array|null
     */
    protected function prepareRow(array $row)
    {
        $entityCustomer = CustomerComposite::COMPONENT_ENTITY_CUSTOMER;
        if ($this->entityType == $entityCustomer) {
            if ($row['_scope'] == CustomerComposite::SCOPE_DEFAULT) {
                return $row;
            } else {
                return null;
            }
        } else {
            return $this->prepareAddressRowData($row);
        }
    }

    /**
     * Prepare data row for address entity validation or import
     *
     * @param array $rowData
     * @return array
     */
    protected function prepareAddressRowData(array $row)
    {
        $prefix = CustomerComposite::COLUMN_ADDRESS_PREFIX;
        $excludedAttributes = [
            CustomerComposite::COLUMN_DEFAULT_BILLING,
            CustomerComposite::COLUMN_DEFAULT_SHIPPING,
        ];

        $result = [];
        foreach ($row as $key => $value) {
            if (!in_array($key, $this->customerAttributes)) {
                if (!in_array($key, $excludedAttributes)) {
                    $key = str_replace($prefix, '', $key);
                }
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
