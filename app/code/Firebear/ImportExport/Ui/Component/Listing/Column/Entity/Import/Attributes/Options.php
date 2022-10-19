<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Entity\Import\Attributes;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeHandler;
use Magento\Framework\Data\Collection\AbstractDb;
use Firebear\ImportExport\Model\Source\Import\Config;
use Magento\ImportExport\Model\Import\Entity\Factory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{

    const CATALOG_PRODUCT = 'catalog_product';

    const CATALOG_CATEGORY = 'catalog_category';

    const ADVANCED_PRICING = 'advanced_pricing';

    /**
     * @var array
     */
    protected $options;

    /**
     * @var ConfigurableAttributeHandler
     */
    protected $configurableAttributeHandler;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    protected $attributeCollection;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \Magento\ImportExport\Model\Import\Entity\Factory
     */
    protected $entityFactory;

    /**
     * @var \Firebear\ImportExport\Helper\Data
     */
    protected $helper;

    /**
     * Options constructor.
     *
     * @param ConfigurableAttributeHandler       $configurableAttributeHandler
     * @param Config                             $config
     * @param Factory                            $entityFactory
     * @param \Firebear\ImportExport\Helper\Data $helper
     */
    public function __construct(
        ConfigurableAttributeHandler $configurableAttributeHandler,
        Config $config,
        Factory $entityFactory,
        \Firebear\ImportExport\Helper\Data $helper
    ) {
        $this->configurableAttributeHandler = $configurableAttributeHandler;
        $this->config = $config;
        $this->entityFactory = $entityFactory;
        $this->helper = $helper;
    }

    /**
     * @param int $withoutGroup
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function toOptionArray($withoutGroup = 0)
    {

        $options = $this->getAttributeCatalog($withoutGroup);

        $this->options = $options;

        return $this->options;
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    public function getAttributeCollection()
    {

        $this->attributeCollection = $this->configurableAttributeHandler
            ->getApplicableAttributes();

        return $this->attributeCollection;
    }

    /**
     * @return array
     */
    protected function getAttributeCatalog($withoutGroup = 0)
    {
        $attributeCollection = $this->getAttributeCollection();
        $subOptions = [];
        /**
         * @var EavAttribute $attribute
        */
        foreach ($attributeCollection->getItems() as $attribute) {
            if ($this->configurableAttributeHandler->isAttributeApplicable($attribute)) {
                $label = (!$withoutGroup) ?
                    $attribute->getAttributeCode() . ' (' . $attribute->getFrontendLabel() . ')' :
                    $attribute->getAttributeCode();
                $subOptions[] =
                    [
                        'label' => $label,
                        'value' => $attribute->getAttributeCode()
                    ];
            }
        }

        return $subOptions;
    }
}
