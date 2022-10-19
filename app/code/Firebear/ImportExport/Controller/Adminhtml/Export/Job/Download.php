<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

use Firebear\ImportExport\Api\Export\History\GetByIdInterface;
use Firebear\ImportExport\Controller\Adminhtml\Export\Context;
use Firebear\ImportExport\Controller\Adminhtml\Export\Job as Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;

/**
 * Download controller
 */
class Download extends Action
{
    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var ReadInterface
     */
    private $directory;

    /**
     * @var GetByIdInterface
     */
    private $getByIdCommand;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string[]
     */
    private $extensionList = [
        'csv',
        'xml',
        'ods',
        'xlsx',
        'json',
        'zip'
    ];

    /**
     * @var string[]
     */
    private $blackList = [
        DirectoryList::APP,
        DirectoryList::GENERATED,
        DirectoryList::SETUP,
        'lib',
        'bin',
        'dev',
        'vendor',
        'phpserver',
        '.github'
    ];

    /**
     * Download constructor.
     *
     * @param Context $context
     * @param Filesystem $filesystem
     * @param FileFactory $fileFactory
     * @param DirectoryList $directoryList
     * @param GetByIdInterface $getByIdCommand
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Filesystem $filesystem,
        FileFactory $fileFactory,
        DirectoryList $directoryList,
        GetByIdInterface $getByIdCommand,
        LoggerInterface $logger
    ) {
        $this->fileFactory = $fileFactory;
        $this->filesystem = $filesystem;
        $this->directoryList = $directoryList;
        $this->directory = $this->filesystem->getDirectoryRead(DirectoryList::ROOT);
        $this->getByIdCommand = $getByIdCommand;
        $this->logger = $logger;

        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        try {
            $historyId = (int)$this->getRequest()->getParam('id');
            $history = $this->getByIdCommand->execute($historyId);
            $path = $history->getTempFile();
            if (!empty($path)) {
                $path = $this->directory->getAbsolutePath($path);
                if ($this->isValid($path)) {
                    return $this->fileFactory->create(
                        basename($path),
                        ['type' => 'filename', 'value' => $path],
                        DirectoryList::ROOT
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
        throw new NotFoundException(
            __('The url is incorrect.')
        );
    }

    /**
     * @inheritDoc
     */
    private function isValid($path)
    {
        if (preg_match('/\.\.(\\\|\/)/', $path) !== 0) {
            return false;
        }

        $extension = mb_strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (empty($extension) || !in_array($extension, $this->extensionList)) {
            return false;
        }

        $dir = dirname($path);
        if ($dir == $this->directoryList->getRoot()) {
            return false;
        }

        foreach ($this->blackList as $forbiddenPath) {
            $forbiddenPath = $this->directory->getAbsolutePath($forbiddenPath);
            if (mb_strpos($dir, $forbiddenPath) !== false) {
                return false;
            }
        }

        return $this->directory->isFile($path);
    }
}
