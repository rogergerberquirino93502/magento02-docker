<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use Firebear\ImportExport\Model\Lib\LibPoolInterface;

/**
 * Spout message observer
 */
class SpoutMessageObserver implements ObserverInterface
{
    /**
     * Message manager
     *
     * @var ManagerInterface
     */
    private $_messageManager;

    /**
     * Install lib pool
     *
     * @var LibPoolInterface
     */
    private $libPool;

    /**
     * Initialize observer
     *
     * @param ManagerInterface $messageManager
     * @param LibPoolInterface $libPool
     */
    public function __construct(
        ManagerInterface $messageManager,
        LibPoolInterface $libPool
    ) {
        $this->_messageManager = $messageManager;
        $this->libPool = $libPool;
    }

    /**
     * Add order condition to the SalesRule management
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        foreach ($this->libPool->get() as $lib) {
            if (!$lib->isInstalled()) {
                $this->_messageManager->addNoticeMessage($lib->getMessage());
            }
        }
    }
}
