<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Context;
use Firebear\ImportExport\Controller\Adminhtml\Job as Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem;

/**
 * Download log controller
 */
class DownloadLog extends Action
{
    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var ReadInterface
     */
    private $directory;

    /**
     * Download constructor.
     *
     * @param Context $context
     * @param Filesystem $filesystem
     * @param FileFactory $fileFactory
     */
    public function __construct(
        Context $context,
        Filesystem $filesystem,
        FileFactory $fileFactory
    ) {
        $this->fileFactory = $fileFactory;
        $this->directory = $filesystem->getDirectoryRead(DirectoryList::LOG);

        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $file = $this->getRequest()->getParam('file');
        $file = preg_replace('#[^0-9\-]#', '', $file);
        return $this->downloadFile($file);
    }

    /**
     * @param $file
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    protected function downloadFile($file)
    {
        $file = $this->directory->getAbsolutePath(). 'firebear/' . $file .".log" ;
        return $this->fileFactory->create(basename($file), file_get_contents($file), DirectoryList::LOG);
    }
}
