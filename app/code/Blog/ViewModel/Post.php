<?php declare(strict_types=1);

namespace MaxiCompra\Blog\ViewModel;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use MaxiCompra\Blog\Api\Data\PostInterface;
use MaxiCompra\Blog\Api\PostRepositoryInterface;
use MaxiCompra\Blog\Model\ResourceModel\Post\Collection;

class Post implements ArgumentInterface
{
    public function __construct(
        private Collection $collection,
        private PostRepositoryInterface $postRepository,
        private RequestInterface $request
    )
    {}

    public function getList(): array
    {
        return $this->collection->getItems();
    }
    public function getCount(): int
    {
        return $this->collection->count();
    }
    public function getDetail(): PostInterface
    {
        $id = (int) $this->request->getParam('id');
        return $this->postRepository->getById($id);
    }
}