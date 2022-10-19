<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Platform;

use Magento\Catalog\Model\Product\Visibility;
use Firebear\ImportExport\Model\Import\Product;
use Magento\Backend\Model\Session;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory;
use Magento\Eav\Model\Entity\Context;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\ClassModelFactory;

/**
 * Class Bol
 *
 * @package Firebear\ImportExport\Model\Source\Platform
 */
class Bol extends AbstractPlatform
{
    /**
     * Prepare Rows
     *
     * @param $rowData
     *
     * @return mixed
     */
    public function prepareRow($rowData)
    {
        $rowData['product_type'] = 'simple';
        $rowData['_attribute_set'] = $this->getAttributeSetName();
        return $rowData;
    }

    /**
     * @param $rowData
     * @return mixed
     */
    public function prepareColumns($rowData)
    {
        $rowData[] = 'product_type';
        $rowData[] = '_attribute_set';

        return $rowData;
    }

    public function afterColumns($rowData, $maps)
    {
        return $rowData;
    }

    public function getAttributeSetName()
    {
        $attributeSetCollection = $this->attributeSetCollectionFactory->create();
        $attributeSetName = $attributeSetCollection->getFirstItem();

        return $this->attributeSetCollectionFactory->create()->getFirstItem()->getAttributeSetName();
    }
}
