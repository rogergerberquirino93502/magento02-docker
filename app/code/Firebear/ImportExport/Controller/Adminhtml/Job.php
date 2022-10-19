<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml;

use Firebear\ImportExport\Api\JobRepositoryInterface;
use Firebear\ImportExport\Helper\Data as Helper;
use Firebear\ImportExport\Model\JobFactory;
use Magento\Backend\App\Action;
use Magento\Framework\App\Request\Http;

/**
 * Class Job
 *
 * @package Firebear\ImportExport\Controller\Adminhtml
 */
abstract class Job extends Action
{
    const ADMIN_RESOURCE = 'Firebear_ImportExport::job';

    /**
     * @var JobFactory
     */
    protected $jobFactory;

    /**
     * @var JobRepositoryInterface
     */
    protected $repository;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var string[]
     */
    protected $sources = [
        'sftp',
        'url',
        'ftp',
        'dropbox',
        'google',
        'googledrive',
        'onedrive',
        'rest',
        'soap'
    ];

    /**
     * Job constructor.
     *
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct(
            $context->getContext()
        );

        $this->jobFactory = $context->getJobFactory();
        $this->repository = $context->getRepository();
        $this->helper = $context->getHelper();
    }

    /**
     * @param array $formData
     * @param Http $request
     * @param string $sourceType
     * @return array
     */
    protected function formatFormData(array $formData, Http $request, string $sourceType)
    {
        $importData = [];
        foreach ($formData as $data) {
            $index = strstr($data, '+', true);
            $index = str_replace($sourceType . '[', '', $index);
            $index = str_replace(']', '', $index);
            $importData[$index] = substr($data, strpos($data, '+') + 1);
        }
        if ($request->getParam('job_id')) {
            $importData['job_id'] = (int)$request->getParam('job_id');
        }

        if (!in_array($importData['import_source'], ['rest', 'soap']) && isset($importData['file_path'])) {
            $importData[$sourceType . '_file_path'] = $importData['file_path'];
        }

        return $importData;
    }

    /**
     * @return array
     */
    protected function processRequestImportData()
    {
        $locale = $this->getRequest()->getParam('language');
        $formData = $this->getRequest()->getParam('form_data');
        $sourceType = $this->getRequest()->getParam('source_type');
        $importData['locale'] = $locale;
        $importData['import_source'] = $importData['import_source'] ?? '';
        if (!empty($formData)) {
            foreach ($formData as $data) {
                $index = strstr($data, '+', true);
                $index = str_replace($sourceType . '[', '', $index);
                $index = str_replace(']', '', $index);
                $importData[$index] = substr($data, strpos($data, '+') + 1);
            }
            if ($this->getRequest()->getParam('job_id')) {
                $importData['job_id'] = (int)$this->getRequest()->getParam('job_id');
            }
            if (!in_array($importData['import_source'], ['rest', 'soap']) && isset($importData['file_path'])) {
                $importData[$sourceType . '_file_path'] = $importData['file_path'];
            }
        }
        return [$importData, $sourceType];
    }
}
