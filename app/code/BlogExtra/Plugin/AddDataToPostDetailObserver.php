<?php declare(strict_types=1);

namespace MaxiCompra\BlogExtra\Plugin;

use Magento\Framework\Event\Observer;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use MaxiCompra\Blog\Observer\LogPostDetailView;

class addDataToPostDetailObserver
{
    public function __construct(
        private TimezoneInterface $timezone
    )
    {
        
    }
    public function beforeExecute(
        LogPostDetailView $subject, 
        Observer $observer)
    {
        $request = $observer->getData('request');
        $request->setParam('datetime', $this->timezone->date());

        return [$observer];
    }
}