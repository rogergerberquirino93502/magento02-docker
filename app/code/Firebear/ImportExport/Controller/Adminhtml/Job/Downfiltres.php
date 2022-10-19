<?php
/**
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Context;
use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Firebear\ImportExport\Model\Export\Dependencies\Config as ExportConfig;
use Firebear\ImportExport\Model\Source\Factory as ModelFactory;

/**
 * Class Downfiltres
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Job
 */
class Downfiltres extends JobController
{
    /**
     * @var ExportConfig
     */
    private $config;

    /**
     * @var ModelFactory
     */
    private $createFactory;

    /**
     * Downfiltres constructor
     *
     * @param Context $context
     * @param ExportConfig $config
     * @param ModelFactory $createFactory
     */
    public function __construct(
        Context $context,
        ExportConfig $config,
        ModelFactory $createFactory
    ) {
        $this->config = $config;
        $this->createFactory = $createFactory;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if (!$this->getRequest()->isAjax()) {
            $this->_forward('noroute');
            return;
        }

        $data = [];
        $entity = $this->getRequest()->getParam('entity');
        $attribute = $this->getRequest()->getParam('attribute');

        $modelName = $this->config->getModel($entity);
        if ($modelName) {
            $model = $this->createFactory->create($modelName);
            $columns = $model->getFieldColumns();
            foreach ($columns[$entity] ?? [] as $column) {
                if ($attribute == $column['field']) {
                    $data = $column;
                    break;
                }
            }
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        return $resultJson->setData($data);
    }
}
