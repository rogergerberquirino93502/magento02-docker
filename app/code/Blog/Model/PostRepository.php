<?php declare(strict_types=1);

namespace MaxiCompra\Blog\Model;


use MaxiCompra\Blog\Api\Data\PostInterface;
use MaxiCompra\Blog\Model\ResourceModel\Post as PostResourceModel;
use MaxiCompra\Blog\Api\PostRepositoryInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class PostRepository implements PostRepositoryInterface{

    public function __construct(
        private PostFactory $postFactory,
        private PostResourceModel $postResourceModel
    )
    {}   
    public function getById(int $id): PostInterface{

        $post = $this->postFactory->create();
        $this->postResourceModel->load($post, $id);
        if(!$post->getId()){
            throw new NoSuchEntityException(__('Post with id "%1" does not exist.', $id));
        }

        return $post;
    }

    public function save(PostInterface $post): PostInterface{
        try{
            $this->postResourceModel->save($post);
        }catch(\Exception $e){
            throw new CouldNotSaveException(__($e->getMessage()));
        }
        return $post;
    }

    public function deleteById(int $id): bool{
        $post = $this->getById($id);
        try{
        $this->postResourceModel->delete($post);
    }catch(\Exception $e){
        throw new CouldNotDeleteException(__($e->getMessage()));
    }
        return true;
    }
}