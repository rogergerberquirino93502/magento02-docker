<?php

namespace Firebear\ImportExport\Test\Integration;

use Firebear\ImportExport\Api\JobRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Exception;

class AbstractImport extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var JobRepositoryInterface
     */
    protected $jobRepository;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $driverFile;

    /**
     * @var \Firebear\ImportExport\Test\Integration\Helpers\JobFixturesManager
     */
    protected $jobFixtureManager;

    protected function setUp(): void
    {
        /** @phpstan-ignore-next-line */
        $this->objectManager = Bootstrap::getObjectManager();
        $this->jobRepository = $this->objectManager->get(JobRepositoryInterface::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->driverFile = $this->objectManager->get(\Magento\Framework\Filesystem\Driver\File::class);
        $this->jobFixtureManager = $this->objectManager->get(
            \Firebear\ImportExport\Test\Integration\Helpers\JobFixturesManager::class
        );
    }

    /**
     * @param string $sourcePath
     * @param string $destPath
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function copyFile(string $sourcePath, string $destPath)
    {
        if (!$this->driverFile->isExists($sourcePath)) {
            throw new Exception('File not found. Path: ' . $sourcePath);
        }

        if (!$this->driverFile->isWritable(dirname($destPath))) {
            $this->driverFile->createDirectory(dirname($destPath));
        }

        if (!$this->driverFile->copy($sourcePath, $destPath)) {
            throw new Exception('Failed to copy file');
        }
    }

    /**
     * @return \Magento\Catalog\Api\Data\ProductSearchResultsInterface
     */
    protected function getAllProducts()
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        return $this->productRepository->getList($searchCriteriaBuilder->create());
    }
}
