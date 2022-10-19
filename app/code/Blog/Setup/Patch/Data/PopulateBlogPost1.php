<?php declare(strict_types=1);

namespace MaxiCompra\Blog\Setup\Patch\Data;

use MaxiCompra\Blog\Api\PostRepositoryInterface;
use MaxiCompra\Blog\Model\PostFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchInterface;

class PopulateBlogPost1 implements DataPatchInterface{
        
        public function __construct(
            private ModuleDataSetupInterface $moduleDataSetup,
            private PostFactory $postFactory,
            private PostRepositoryInterface $postRepository)
        {}
        public static function getDependencies(): array
        {
            return [];
        }
        public function getAliases(): array
        {
            return [];
        }
        public function apply()
        {
            $this->moduleDataSetup->startSetup();
    
            $post = $this->postFactory->create();
            $post->setData([
                'title' => 'Today is Sunny',
                'content' => 'This is totally Sunny'  
            ]);
    
            $this->postRepository->save($post);
    
            $this->moduleDataSetup->endSetup();
        }
}