<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Source\Config;

use Magento\Framework\Option\ArrayInterface;
use Magento\User\Model\ResourceModel\User\CollectionFactory;

/**
 * Receiver source
 */
class Receiver implements ArrayInterface
{
    /**
     * Options array
     *
     * @var array
     */
    protected $options;

    /**
     * User collection factory
     *
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Initialize source
     *
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Retrieve options as array
     *
     * @return array
     */
    public function getOptions()
    {
        if (null === $this->options) {
            $this->options = [];
            $collection = $this->collectionFactory->create();
            foreach ($collection as $user) {
                $this->options[$user->getEmail()] = $user->getEmail();
            }
        }
        return $this->options;
    }

    /**
     * Retrieve options as array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->getOptions() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }
        return $options;
    }

    /**
     * Retrieve options in key-value format
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getOptions();
    }
}
