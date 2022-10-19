<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Config;

use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Event
 *
 * @package Firebear\ImportExport\Model\Source\Config
 */
class Event implements ArrayInterface
{
    /**
     * Event list config path
     */
    const XML_EVENT = 'firebear_importexport/event/available';

    /**
     * Options as value-label pairs
     *
     * @var array
     */
    protected $_options;

    /**
     * Scope Config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Initialize source
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Convert config to array
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->_options === null) {
            $eventList = $this->_scopeConfig->getValue(
                self::XML_EVENT,
                ScopeInterface::SCOPE_STORES
            );
            $events = explode(',', $eventList);
            foreach ($events as $event) {
                $this->_options[] = ['value' => $event, 'label' => $event];
            }
        }
        return $this->_options;
    }
}
