<?php declare (strict_types = 1);

namespace MaxiCompra\Blog\Controller\Post;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;

/**
 * Catalog detail page controller.
 */
class Detail implements HttpGetActionInterface
{
    public function __construct(
        private PageFactory $pageFactory,
        private EventManager $eventManager,
        private RequestInterface $request
    ) {}   
    public function execute(): Page
    {
        $this->eventManager->dispatch('maxicompra_blog_post_detail_view', 
        ['request' => $this->request]);
        return $this->pageFactory->create();
    }
}