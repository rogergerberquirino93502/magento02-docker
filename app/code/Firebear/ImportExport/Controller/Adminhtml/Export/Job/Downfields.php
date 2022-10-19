<?php

declare(strict_types=1);
/**
 * Downfields
 *
 * @copyright Copyright © 2020 Firebear Studio. All rights reserved.
 * @author    fbeardev@gmail.com
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

use Firebear\ImportExport\Controller\Adminhtml\Export\Context;
use Firebear\ImportExport\Controller\Adminhtml\Export\Job as JobController;
use Firebear\ImportExport\Ui\Component\Listing\Column\Entity\Options;
use Magento\Framework\App\HttpRequestInterface;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Framework\Controller\ResultInterface;

/**
 * Class Downfields
 * @package Firebear\ImportExport\Controller\Adminhtml\Export\Job
 */
class Downfields extends JobController
{
    /**
     * @var Options
     */
    private $exportEntityOptions;

    public function __construct(
        Context $context,
        Options $exportEntityOptions
    ) {
        parent::__construct($context);
        $this->exportEntityOptions = $exportEntityOptions;
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        /** @var ResultJson $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        $options[] = [
            'value' => '',
            'label' => __('-- Please Select --')
        ];
        /** @var HttpRequestInterface $request */
        $request = $this->getRequest();
        if ($request->isAjax() && $request->isPost()) {
            $entity = $this->getRequest()->getParam('entity');
            if ($entity) {
                $list = $this->exportEntityOptions->_loadEntityOptions($entity);
                $parseEntityOptions = $list[$entity] ?? [];
                $options = array_merge($options, $parseEntityOptions);
            }
        }

        return $resultJson->setData($options);
    }
}
