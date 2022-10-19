<?php declare (strict_types = 1);

namespace MaxiCompra\Blog\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;

class LogPostDetailView implements ObserverInterface
{
    public function __construct(
        private LoggerInterface $logger
    )
    {
        
    }
    public function execute(Observer $observer)
    {
        $request = $observer->getData('request');
        $this->logger->info('Post detail view', [
            'params' => $request->getParams(),
        ]);
    }
}
