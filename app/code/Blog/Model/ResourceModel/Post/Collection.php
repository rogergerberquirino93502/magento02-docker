<?php declare(strict_types=1);

namespace MaxiCompra\Blog\Model\ResourceModel\Post;


use MaxiCompra\Blog\Model\Post;
use MaxiCompra\Blog\Model\ResourceModel\Post as PostResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            Post::class,
            PostResourceModel::class);
    }
}