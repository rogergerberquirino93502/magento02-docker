<?php

namespace Sirv\Magento2\Controller\Adminhtml\Ajax;

/**
 * Synchronize ajax controller
 *
 * @author    Sirv Limited <support@sirv.com>
 * @copyright Copyright (c) 2018-2022 Sirv Limited <support@sirv.com>. All rights reserved
 * @license   https://sirv.com/
 * @link      https://sirv.com/integration/magento/
 */
class Synchronize extends \Sirv\Magento2\Controller\Adminhtml\Settings
{
    /**
     * Sync helper
     *
     * @var \Sirv\Magento2\Helper\Sync\Backend
     */
    protected $syncHelper = null;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Sirv\Magento2\Helper\Data\BackendFactory $dataHelperFactory
     * @param \Sirv\Magento2\Helper\Sync\Backend $syncHelper
     * @return void
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Sirv\Magento2\Helper\Data\BackendFactory $dataHelperFactory,
        \Sirv\Magento2\Helper\Sync\Backend $syncHelper
    ) {
        parent::__construct($context, $resultPageFactory, $dataHelperFactory);
        $this->syncHelper = $syncHelper;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $postData = $this->getRequest()->getPostValue();
        $action = isset($postData['dataAction']) ? $postData['dataAction'] : '';

        $result = [
            'success' => false,
            'data' => []
        ];
        $data = [];

        /** @var \Sirv\Magento2\Helper\Data\Backend $dataHelper */
        /* $dataHelper = $this->getDataHelper(); */

        switch ($action) {
            case 'synchronize':
                $stage = isset($postData['syncStage']) ? (int)$postData['syncStage'] : 0;
                if ($stage) {
                    $doClean = isset($postData['doClean']) ? $postData['doClean'] == 'true' : false;
                    $httpAuthUser = isset($postData['httpAuthUser']) ? $postData['httpAuthUser'] : '';
                    $httpAuthPass = isset($postData['httpAuthPass']) ? $postData['httpAuthPass'] : '';
                    $data = $this->syncHelper->syncMediaGallery(
                        $stage,
                        [
                            'doClean' => $doClean,
                            'httpAuthUser' => $httpAuthUser,
                            'httpAuthPass' => $httpAuthPass
                        ]
                    );
                } else {
                    $data = $this->syncHelper->getSyncData(true);
                }
                $result['success'] = true;
                break;
            case 'flush':
                $flushMethod = isset($postData['flushMethod']) ? $postData['flushMethod'] : false;
                if ($flushMethod) {
                    $result['success'] = $this->syncHelper->flushCache($flushMethod);
                    $data = [
                        'method' => $flushMethod
                    ];
                }
                break;
            case 'get_failed':
                $productMediaRelPath = $this->syncHelper->getProductMediaRelPath();
                $mediaDirAbsPath = $this->syncHelper->getMediaDirAbsPath();
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $storeManager = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
                $mediaBaseUrl = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                $mediaBaseUrl = rtrim($mediaBaseUrl, '\\/');

                $failedPathes = $this->syncHelper->getCachedPathes(\Sirv\Magento2\Helper\Sync::IS_FAILED);
                $failedCount = count($failedPathes);
                foreach ($failedPathes as $i => $path) {
                    $failedPathes[$i] = $productMediaRelPath . '/' . ltrim($path, '\\/');
                }
                $failedPathes = array_flip($failedPathes);

                $messageModel = $this->syncHelper->getMessageModel();
                $failedData = [];
                foreach ($messageModel->getCollection() as $modelItem) {
                    $relPath = $modelItem->getPath();

                    if (isset($failedPathes[$relPath])) {
                        unset($failedPathes[$relPath]);

                        $absPath = $mediaDirAbsPath . $relPath;
                        $isFile = is_file($absPath);
                        $fileSize = $isFile ? filesize($absPath) : 0;

                        $message = $modelItem->getMessage();
                        if (!isset($failedData[$message])) {
                            $failedData[$message] = [];
                        }

                        $failedData[$message][] = [
                            'path' => $absPath,
                            'url' => $mediaBaseUrl . $relPath,
                            'isFile' => $isFile,
                            'fileSize' => $fileSize
                        ];
                    }
                }

                if (!empty($failedPathes)) {
                    $message = 'Unknown error.';
                    if (!isset($failedData[$message])) {
                        $failedData[$message] = [];
                    }
                    foreach (array_flip($failedPathes) as $relPath) {
                        $absPath = $mediaDirAbsPath . $relPath;
                        $isFile = is_file($absPath);
                        $fileSize = $isFile ? filesize($absPath) : 0;
                        $failedData[$message][] = [
                            'path' => $absPath,
                            'url' => $mediaBaseUrl . $relPath,
                            'isFile' => $isFile,
                            'fileSize' => $fileSize
                        ];
                    }
                    $messageEx = $message .  ' See <a href="https://my.sirv.com/#/events/" target="_blank" class="sirv-open-in-new-window">Sirv notification section</a> for more information.';
                    $failedData[$messageEx] = $failedData[$message];
                    unset($failedData[$message]);
                }

                $failedData = [
                    'count' => $failedCount,
                    'groups' => $failedData,
                ];

                $data = ['failed' => $failedData];
                $result['success'] = true;
                break;
            case 'get_storage_size':
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                /** @var \Magento\Framework\Shell $shell */
                $shell = $objectManager->get(\Magento\Framework\Shell::class);
                /** @var \Magento\Framework\Filesystem $filesystem */
                $filesystem = $objectManager->get(\Magento\Framework\Filesystem::class);
                /** @var \Magento\Framework\Filesystem\Directory\ReadInterface $mediaDirectory */
                $mediaDirectory = $filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
                $mediaDirAbsPath = rtrim($mediaDirectory->getAbsolutePath(), '\\/') . '/';
                $flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS;
                $pathes = $mediaDirectory->read('catalog/product');
                $pathes[] = 'catalog/category';
                $pathes[] = 'wysiwyg';
                $size = 0;
                foreach ($pathes as $path) {
                    if (('catalog/product/cache' != $path) && $mediaDirectory->isDirectory($path)) {
                        $absPath = $mediaDirAbsPath . $path;
                        try {
                            $command = 'du --bytes --summarize ' . $absPath;
                            $output = $shell->execute($command);
                            if (preg_match('#^(\d++)\s#', $output, $match)) {
                                $size += (int)$match[1];
                            } else {
                                throw new \Exception('Unexpected result when executing command: ' . $command);
                            }
                        } catch (\Exception $e) {
                            try {
                                $iterator = new \RecursiveIteratorIterator(
                                    new \RecursiveDirectoryIterator($absPath, $flags),
                                    \RecursiveIteratorIterator::CHILD_FIRST
                                );
                                $_size = 0;
                                foreach ($iterator as $item) {
                                    if ($item->isFile()) {
                                        $_size += $item->getSize();
                                    }
                                }
                                $size += $_size;
                            } catch (\Exception $e) {
                                throw new \Magento\Framework\Exception\FileSystemException(
                                    new \Magento\Framework\Phrase($e->getMessage()),
                                    $e
                                );
                            }
                        }
                    }
                }
                $data = ['size' => $size];
                $result['success'] = true;
                break;
            default:
                $data['error'] = __('Unknown action: "%1"', $action);
                break;
        }

        $result['data'] = $data;

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
        $resultJson->setData($result);

        return $resultJson;
    }
}
