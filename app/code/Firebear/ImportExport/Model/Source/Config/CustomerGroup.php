<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Source\Config;

use Magento\Framework\Convert\DataObject;
use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Api\GroupRepositoryInterface;

/**
 * Customer group source
 */
class CustomerGroup implements ArrayInterface
{
    /**
     * @var GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var DataObject
     */
    protected $objectConverter;

    /**
     * Initialize source
     *
     * @param GroupRepositoryInterface $groupRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param DataObject $objectConverter
     */
    public function __construct(
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        DataObject $objectConverter
    ) {
        $this->groupRepository = $groupRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->objectConverter = $objectConverter;
    }

    /**
     * Retrieve options as array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $customerGroups = $this->groupRepository->getList(
            $this->searchCriteriaBuilder->create()
        )->getItems();

        return $this->objectConverter->toOptionArray($customerGroups, 'id', 'code');
    }
}
