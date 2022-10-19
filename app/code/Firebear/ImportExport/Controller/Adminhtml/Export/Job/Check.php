<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

use Firebear\ImportExport\Controller\Adminhtml\Export\Context;
use Firebear\ImportExport\Controller\Adminhtml\Export\Job as JobController;
use Firebear\ImportExport\Helper\Additional;

/**
 * Class Check
 * @package Firebear\ImportExport\Controller\Adminhtml\Export\Job
 */
class Check extends JobController
{
    const SOURCE = 'export_source';

    /**
     * @var Additional
     */
    protected $helper;

    /**
     * Check constructor.
     *
     * @param Context $context
     * @param Additional $helper
     */
    public function __construct(
        Context $context,
        Additional $helper
    ) {
        parent::__construct($context);
        $this->helper = $helper;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        $result = false;
        if ($this->getRequest()->isAjax()) {
            $formData = $this->getRequest()->getParam('form_data');
            $exportData = [];
            foreach ($formData as $data) {
                $exData = strstr($data, '+', true);
                $exportData[$exData] = substr($data, strpos($data, '+') + 1);
            }
            $entity = $exportData['export_source_entity'];
            $source = $this->helper->getSourceModelByType($exportData['export_source_entity']);
            unset($exportData['export_source_entity']);
            $exportData = $this->getSourceData($exportData, $entity);

            $source->setData($exportData);

            $result = $source->check();

            return $resultJson->setData($result);
        }
    }

    /**
     * @param $sourceData
     * @param $entity
     * @return array
     */
    public function getSourceData($sourceData, $entity)
    {
        $source = [];
        $sourceKey = self::SOURCE . "_" . $entity . "_";

        foreach ($sourceData as $key => $param) {
            if (strpos($key, $sourceKey) !== false) {
                $source[substr($key, strlen($sourceKey))] = $param;
            }
        }

        return $source;
    }
}
