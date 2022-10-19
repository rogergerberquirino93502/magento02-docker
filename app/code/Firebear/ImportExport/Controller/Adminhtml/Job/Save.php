<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Context;
use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Api\Data\JobReplacingInterface;
use Firebear\ImportExport\Model\Job\ReplacingFactory;
use Firebear\ImportExport\Api\Data\DataSourceReplacingInterface as Replacing;
use Firebear\ImportExport\Model\Job;
use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Firebear\ImportExport\Model\Job\MappingFactory;
use Firebear\ImportExport\Model\Data\ProcessorInterface;
use Magento\Framework\Serialize\Serializer\Serialize as PhpSerializer;

/**
 * Class Save
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Job
 */
class Save extends JobController
{
    /**
     * @var PhpSerializer
     */
    protected $phpSerializer;

    /**
     * @var MappingFactory
     */
    protected $mappingFactory;

    /**
     * @var ReplacingFactory
     */
    protected $replacingFactory;

    /**
     * @var ProcessorInterface
     */
    protected $dataProcessor;

    /**
     * @var array
     */
    protected $additionalFields = [
        'associate_child_review_to_configurable_parent_product',
        'associate_child_review_to_bundle_parent_product',
        'platforms',
        'clear_attribute_value',
        'remove_product_association',
        'remove_product_website',
        'remove_all_customer_address',
        'remove_product_categories',
        'type_file',
        'configurable_switch',
        'configurable_create',
        'configurable_type',
        'configurable_field',
        'configurable_part',
        'configurable_symbols',
        'round_up_prices',
        'round_up_special_price',
        'copy_simple_value',
        'language',
        'reindex',
        'indexers',
        'generate_url',
        'enable_product_url_pattern',
        'product_url_pattern',
        'xml_switch',
        'root_category_id',
        'replace_default_value',
        'remove_current_mappings',
        'remove_images',
        'remove_images_dir',
        'remove_related_product',
        'remove_crosssell_product',
        'remove_upsell_product',
        'use_only_fields_from_mapping',
        'disable_products',
        'product_supplier',
        'send_email',
        'generate_shipment_by_track',
        'generate_invoice_by_track',
        'translate_attributes',
        'translate_store_ids',
        'translate_version',
        'translate_key',
        'translate_referer',
        'xlsx_sheet',
        'cron_groups',
        'email_type',
        'template',
        'receiver',
        'sender',
        'copy',
        'copy_method',
        'is_attach',
        'image_resize',
        'delete_file_after_import',
        'deferred_images',
        'cache_products',
        'increase_product_stock_by_qty',
        'archive_file_after_import',
        'include_option_id',
        'scan_directory',
        'stop_loop_on_fail',
        'copy_base_image'
    ];

    /**
     * Save constructor.
     *
     * @param Context $context
     * @param MappingFactory $mappingFactory
     * @param ReplacingFactory $replacingFactory
     * @param ProcessorInterface $dataProcessor
     * @param PhpSerializer $serializer
     */
    public function __construct(
        Context $context,
        MappingFactory $mappingFactory,
        ReplacingFactory $replacingFactory,
        ProcessorInterface $dataProcessor,
        PhpSerializer $serializer
    ) {
        parent::__construct($context);

        $this->mappingFactory = $mappingFactory;
        $this->replacingFactory = $replacingFactory;
        $this->dataProcessor = $dataProcessor;
        $this->phpSerializer = $serializer;
    }

    /**
     * @return \Firebear\ImportExport\Controller\Adminhtml\Job\Save|\Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $data = $this->dataProcessor->process($data);
            $jobId = $this->getRequest()->getParam('entity_id');
            if (isset($data['is_active']) && $data['is_active'] === 'true') {
                $data['is_active'] = ImportInterface::STATUS_ENABLED;
            }
            if (!$jobId) {
                $model = $this->jobFactory->create();
                $data['entity_id'] = null;
            } else {
                if (empty($data['entity_id'])) {
                    $data['entity_id'] = $jobId;
                }
                $model = $this->repository->getById($jobId);
                if (!$model->getId() && $jobId) {
                    $this->messageManager->addErrorMessage(__('This job no longer exists.'));

                    return $resultRedirect->setPath('*/*/');
                }
            }
            $data['import_source'] = $data['import_source'] ?? '';
            // Prepare Behavior Data
            $this->spliteBeahivorData($data);
            // Prepare Source Data
            $this->spliteSourceData($data);
            $model->addData($data);
            $dataMapWithDeleteData = [];
            if (isset($data['source_data_attribute_values_map'])) {
                $dataMapWithDeleteData = $data['source_data_attribute_values_map'];
            }
            if (isset($data['source_data_categories_map'])) {
                foreach ($data['source_data_categories_map'] as $categoryMapItems) {
                    if (!isset($categoryMapItems['delete'])) {
                        $dataMapWithDeleteData[] = $categoryMapItems;
                    }
                }
            }
            $model->setMapping($this->phpSerializer->serialize($dataMapWithDeleteData));

            $priceRules = [];
            if (isset($data['price_rules_rows'])) {
                foreach ($data['price_rules_rows'] as $priceRule) {
                    if (isset($priceRule['delete'])) {
                        continue;
                    }

                    if (isset($priceRule['price_rules_conditions_hidden'])) {
                        parse_str(
                            $priceRule['price_rules_conditions_hidden'],
                            $priceRule['price_rules_conditions_hidden']
                        );
                    }

                    $priceRules[] = $priceRule;
                }
            }
            $model->setPriceRules($this->phpSerializer->serialize($priceRules));

            // init model and set data
            $this->spliteMapsData($data, $model);

            $data = $this->spliteReplacingsData($data, $model);

            // try to save it
            try {
                // save the data
                $newModel = $this->repository->save($model);
                // display success message
                if (!$this->getRequest()->isAjax()) {
                    $this->messageManager->addSuccessMessage(__('Job was saved successfully.'));
                }
                // clear previously saved data from session
                $this->_session->setFormData(false);
                // check if 'Save and Continue'
                if ($this->getRequest()->isAjax()) {
                    return $resultJson->setData($newModel->getId());
                }
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['entity_id' => $model->getId()]);
                }

                // go to grid
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->_session->setFormData($data);
                if ($this->getRequest()->isAjax()) {
                    return $resultJson->setData(false);
                }
                // redirect to edit form
                return $resultRedirect->setPath(
                    '*/*/edit',
                    ['entity_id' => $this->getRequest()->getParam('entity_id')]
                );
            }
        }
        if ($this->getRequest()->isAjax()) {
            return $resultJson->setData(true);
        }

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @param $data
     */
    protected function spliteBeahivorData(&$data)
    {
        $behavior = $data['behavior'];
        $jobModel = $this->jobFactory->create();
        $behaviorFields = $jobModel->getBehaviorFormFields();
        $data['behavior_data']['behavior'] = $behavior;
        foreach ($behaviorFields as $field) {
            if (!isset($data[$field])) {
                continue;
            }
            $data['behavior_data'][$field] = $data[$field];
            unset($data[$field]);
        }
        unset($data['behavior']);
    }

    /**
     * @param $data
     */
    protected function spliteSourceData(&$data)
    {
        $fields = $this->helper->getConfigFields();
        $data['source_data']['import_source'] = $data['import_source'];
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data['source_data'][$field] = $data[$field];
                unset($data[$field]);
            }
        }

        foreach ($this->additionalFields as $adField) {
            if (isset($data[$adField])) {
                $data['source_data'][$adField] = $data[$adField];
                unset($data[$adField]);
            }
        }
        if (isset($data['configurable_variations'])) {
            $confData = [];
            foreach ($data['configurable_variations'] as $row) {
                if (!(isset($row['delete']) && $row['delete'] == true)) {
                    $confData[] = $row['configurable_variations_attributes'];
                }
            }
            $data['source_data']['configurable_variations'] = $confData;
        }

        if (isset($data['copy_simple_value'])) {
            $confData = [];
            foreach ($data['copy_simple_value'] as $row) {
                if (!(isset($row['delete']) && $row['delete'] == true)) {
                    $confData[] = $row['copy_simple_value_attributes'];
                }
            }
            $data['source_data']['copy_simple_value'] = $confData;
        }
    }

    /**
     * @param $data
     * @param $model
     */
    protected function spliteMapsData(&$data, $model)
    {
        $model->deleteMap();
        if (isset($data['source_data_map'])) {
            foreach ($data['source_data_map'] as $row) {
                if (!(isset($row['delete']) && $row['delete'] == true)) {
                    $newMap = $this->mappingFactory->create();
                    if (isset($row['source_data_import']) && !empty($row['source_data_import'])) {
                        $newMap->setImportCode($row['source_data_import']);
                    }
                    if (is_numeric($row['source_data_system'])) {
                        $newMap->setAttributeId((int)$row['source_data_system']);
                    } else {
                        $newMap->setSpecialAttribute($row['source_data_system']);
                    }
                    if (isset($row['source_data_replace']) && $row['source_data_replace'] != '') {
                        $newMap->setDefaultValue($row['source_data_replace']);
                    }
                    if (isset($row['position']) && $row['position'] != '') {
                        $newMap->setPosition($row['position']);
                    }
                    $newMap->setCustom($row['custom']);
                    $model->addMap($newMap);
                }
            }
            unset($data['source_data_map']);
        }
    }

    /**
     * @param array $data
     * @param ImportInterface|Job $model
     * @return array
     */
    protected function spliteReplacingsData($data, $model)
    {
        $model->deleteReplacing();
        if (isset($data[Replacing::SOURCE_DATA_REPLACING])) {
            foreach ($data[Replacing::SOURCE_DATA_REPLACING] as $row) {
                $row[Replacing::DATA_SOURCE_REPLACING_ENTITY_TYPE] = $data['entity'];
                $model = $this->addReplacingsToModel($model, $row);
            }
            unset($data[Replacing::SOURCE_DATA_REPLACING]);
        }
        return $data;
    }

    /**
     * @param ImportInterface|Job $model
     * @param array $row
     * @return ImportInterface|Job
     */
    private function addReplacingsToModel(ImportInterface $model, array $row)
    {
        if (isset($row['delete']) && $row['delete'] === true) {
            return $model;
        }
        /** @var JobReplacingInterface $replacing */
        $replacing = $this->replacingFactory->create();
        $replacing
            ->setAttributeCode((string) $row[Replacing::DATA_SOURCE_REPLACING_ATTRIBUTE])
            ->setTarget((int) $row[Replacing::DATA_SOURCE_REPLACING_TARGET])
            ->setEntityType((string) $row[Replacing::DATA_SOURCE_REPLACING_ENTITY_TYPE])
            ->setIsCaseSensitive((int) $row[Replacing::DATA_SOURCE_REPLACING_IS_CASE_SENSITIVE])
            ->setFind((string) $row[Replacing::DATA_SOURCE_REPLACING_FIND])
            ->setReplace((string) $row[Replacing::DATA_SOURCE_REPLACING_REPLACE])
        ;
        $model->addReplacing($replacing);
        return $model;
    }
}
