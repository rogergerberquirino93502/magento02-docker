<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\PostJob;

use Firebear\ImportExport\Model\Migration\Config;
use Firebear\ImportExport\Model\Migration\DbConnection;
use Firebear\ImportExport\Model\Migration\PostJobInterface;
use Magento\Eav\Api\AttributeSetManagementInterface;
use Magento\Eav\Api\Data\AttributeSetInterfaceFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Api\AttributeSetRepositoryInterface;

/**
 * @package Firebear\ImportExport\Model\Migration\PostJob
 */
class SetDefaultAttributeSet implements PostJobInterface
{
    /**
     * @var DbConnection
     */
    protected $connector;

    /**
     * @var string
     */
    protected $destinationEntityTypeId;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var AttributeSetInterfaceFactory
     */
    private $attributeSetInterfaceFactory;

    /**
     * @var AttributeSetManagementInterface
     */
    private $attributeSetManagement;

    /**
     * @var EavSetup
     */
    private $eavSetup;

    /**
     * @var AttributeSetRepositoryInterface
     */
    private $attributeSetRepository;

    /**
     * @param DbConnection $connector
     * @param Config $config
     * @param AttributeSetInterfaceFactory $attributeSetInterfaceFactory
     * @param AttributeSetManagementInterface $attributeSetManagement
     * @param EavSetup $eavSetup
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param string $destinationEntityTypeId
     */
    public function __construct(
        DbConnection $connector,
        Config $config,
        AttributeSetInterfaceFactory $attributeSetInterfaceFactory,
        AttributeSetManagementInterface $attributeSetManagement,
        EavSetup $eavSetup,
        AttributeSetRepositoryInterface $attributeSetRepository,
        $destinationEntityTypeId
    ) {
        $this->connector = $connector;
        $this->config = $config;
        $this->attributeSetInterfaceFactory = $attributeSetInterfaceFactory;
        $this->attributeSetManagement = $attributeSetManagement;
        $this->eavSetup = $eavSetup;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->destinationEntityTypeId = $destinationEntityTypeId;
    }

    /**
     * @inheritdoc
     */
    public function job()
    {
        $destinationSelect = $this->connector->getDestinationChannel()
            ->select()
            ->from($this->config->getM2Prefix() . 'eav_attribute_set', ['attribute_set_id', 'attribute_set_name'])
            ->where('entity_type_id = ?', $this->destinationEntityTypeId)
            ->where('NOT (attribute_set_name = ?)', 'Default');

        $setIds = $this->connector->getDestinationChannel()->query($destinationSelect)->fetchAll();

        $defaultAttributeSet = $this->connector->getDestinationChannel()
            ->select()
            ->from($this->config->getM2Prefix() . 'eav_attribute_set', ['attribute_set_id'])
            ->where('entity_type_id = ?', $this->destinationEntityTypeId)
            ->where('attribute_set_name = ?', 'Default');
        $defaultAttributeSetId = $this->connector->getDestinationChannel()->query($defaultAttributeSet)->fetchAll();

        foreach ($setIds as $setField => $setId) {
            $attrSet = $this->attributeSetRepository->get($setId['attribute_set_id']);
            $this->attributeSetRepository->get($defaultAttributeSetId[0]['attribute_set_id']);
            $attrSet->initFromSkeleton($defaultAttributeSetId[0]['attribute_set_id']);
            $this->attributeSetRepository->save($attrSet);
        }
    }
}
