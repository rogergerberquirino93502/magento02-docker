<?php
/**
 * Fetchsheet
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    fbeardev@gmail.com
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Exception;
use Firebear\ImportExport\Controller\Adminhtml\Context;
use Firebear\ImportExport\Helper\XlsxHelper;
use Firebear\ImportExport\Model\Import\Platforms;
use Firebear\ImportExport\Model\Job\Processor;
use Firebear\ImportExport\Ui\Component\Listing\Column\Entity\Import\Options;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\Json;

/**
 * Class Fetchsheet
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Job
 */
class Fetchsheet extends Loadmap
{
    /**
     * @var XlsxHelper
     */
    protected $xlsxHelper;

    /**
     * Fetchsheet constructor.
     * @param Context $context
     * @param Platforms $platforms
     * @param Processor $processor
     * @param Options $options
     * @param XlsxHelper $xlsxHelper
     */
    public function __construct(
        Context $context,
        Platforms $platforms,
        Processor $processor,
        Options $options,
        XlsxHelper $xlsxHelper
    ) {
        parent::__construct($context, $platforms, $processor, $options);
        $this->xlsxHelper = $xlsxHelper;
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        $sheetsName = [];
        if ($this->getRequest()->isAjax()) {
            try {
                [$importData, $sourceType] = $this->processRequestImportData();
                if ($sourceType === 'file' && isset($importData['file_path'])) {
                    $file = $importData['file_path'];
                } else {
                    $file = $this->getImportSource($importData)->uploadSource();
                }
                if ($file) {
                    $sheetsName = $this->xlsxHelper->getSheetsOptions($file);
                }
            } catch (Exception $exception) {
                $this->messageManager->addExceptionMessage($exception);
            }
        }
        return $resultJson->setData($sheetsName);
    }
}
