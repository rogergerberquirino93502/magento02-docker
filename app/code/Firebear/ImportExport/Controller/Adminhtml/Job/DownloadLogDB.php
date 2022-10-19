<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Context;
use Firebear\ImportExport\Controller\Adminhtml\Job as Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Exception\LocalizedException;
use Firebear\ImportExport\Model\Export\HistoryRepository as ExportRepository;
use Firebear\ImportExport\Model\Import\HistoryRepository as ImportRepository;

class DownloadLogDB extends Action
{
    /**
     * @var FileFactory
     */
    private $responseFileFactory;
    /**
     * @var ExportRepository
     */
    private $exportRepository;
    /**
     * @var ImportRepository
     */
    private $importRepository;

    /**
     * @param Context $context
     * @param FileFactory $responseFileFactory
     * @param ExportRepository $exportRepository
     * @param ImportRepository $importRepository
     */
    public function __construct(
        Context $context,
        FileFactory $responseFileFactory,
        ExportRepository $exportRepository,
        ImportRepository $importRepository
    ) {
        parent::__construct($context);
        $this->responseFileFactory = $responseFileFactory;
        $this->exportRepository = $exportRepository;
        $this->importRepository = $importRepository;
    }

    /**
     * @return ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        try {
            $historyId = (int)$this->getRequest()->getParam('history_id');
            $jobType = $this->getRequest()->getParam('job_type');
            $repository = ($jobType == 'export') ? $this->exportRepository : $this->importRepository;

            $history = $repository->getById($historyId);

            if (!$history->getDbLogStorage()) {
                throw new LocalizedException(__('The log is not stored in the database'));
            }

            $log = $history->getLogContent();

            return $this->responseFileFactory->create($history->getFile() . '.log', $log, DirectoryList::LOG);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            $this->_redirect($this->_redirect->getRefererUrl());
        }
    }
}
