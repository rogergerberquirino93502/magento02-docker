<?php

/**
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
declare(strict_types=1);

namespace Firebear\ImportExport\Model\Import\Product\Type;

use Firebear\ImportExport\Traits\Import\Product\Type;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Downloadable
 */
class Simple extends \Magento\CatalogImportExport\Model\Import\Product\Type\Simple
{
    use Type;

    /**
     * @var Config
     */
    protected $eavConfig;

    /**
     * Simple constructor.
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFac
     * @param CollectionFactory $prodAttrColFac
     * @param ResourceConnection $resource
     * @param array $params
     * @param MetadataPool|null $metadataPool
     * @param Config $eavConfig
     * @throws LocalizedException
     */
    public function __construct(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFac,
        CollectionFactory $prodAttrColFac,
        ResourceConnection $resource,
        array $params,
        Config $eavConfig,
        MetadataPool $metadataPool = null
    ) {
        $this->eavConfig = $eavConfig;
        parent::__construct($attrSetColFac, $prodAttrColFac, $resource, $params, $metadataPool);
    }

    /**
     * @param array $rowData
     * @return array
     */
    protected function addAdditionalAttributes(array $rowData)
    {
        return [];
    }
}
