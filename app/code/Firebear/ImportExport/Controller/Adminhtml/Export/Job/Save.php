<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

use Firebear\ImportExport\Controller\Adminhtml\Export\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validation\ValidationException;
use Psr\Log\LoggerInterface;

/**
 * Class Save
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Export\Job
 */
class Save extends \Firebear\ImportExport\Controller\Adminhtml\Export\Job
{

    const SOURCE_DATA = 'source_data';

    const SOURCE_FILTER = 'source_filter';

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $additionalFields = [
        'enable_last_entity_id',
        'last_entity_id',
        'language',
        'divided_additional',
        'use_api',
        'only_admin',
        'cron_groups',
        'email_type',
        'template',
        'receiver',
        'sender',
        'copy',
        'copy_method',
        'is_attach'
    ];

    /**
     * Save constructor.
     *
     * @param Context $context
     * @param DataPersistorInterface $dataPersistor
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor,
        LoggerInterface $logger
    ) {
        parent::__construct($context);

        $this->dataPersistor = $dataPersistor;
        $this->logger = $logger;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\Controller\Result\Json
     * @throws LocalizedException
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        $isAjax = (bool)$this->getRequest()->isAjax();
        $data = $this->getRequest()->getPostValue();
        $this->dataPersistor->set('firebear_export_job', $data);
        $data = $this->prepareData($data);

        if ($data) {
            $id = $this->getRequest()->getParam('entity_id');
            if (empty($data['entity_id'])) {
                $data['entity_id'] = null;
            }
            if (!$id) {
                $model = $this->jobFactory->create();
            } else {
                if (empty($data['entity_id'])) {
                    $data['entity_id'] = $id;
                }
                $model = $this->repository->getById($id);
                if (!$model->getId() && $id) {
                    if ($isAjax) {
                        return $resultJson->setData(false);
                    }
                    $this->messageManager->addErrorMessage(__('This export no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
            }
            $model->setData($data);
            try {
                $newModel = $this->repository->save($model);
                if (!$isAjax) {
                    $this->messageManager->addSuccessMessage(__('You saved the export.'));
                }

                if ($isAjax) {
                    return $resultJson->setData($newModel->getId());
                }

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['entity_id' => $model->getId()]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (ValidationException $e) {
                $messages = [];
                foreach ($e->getErrors() as $localizedError) {
                    if (!$isAjax) {
                        $this->messageManager->addErrorMessage($localizedError->getMessage());
                    }
                    $messages[] = $localizedError->getMessage();
                }
                if ($isAjax) {
                    return $resultJson->setData(['messages' => $messages]);
                }
            } catch (LocalizedException $e) {
                if ($isAjax) {
                    return $resultJson->setData(['messages' => [$e->getMessage()]]);
                }
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $message = __('Something went wrong while saving the export.');
                if ($isAjax) {
                    return $resultJson->setData(['messages' => [$message]]);
                }
                $this->messageManager->addExceptionMessage($e, $message);
            }

            if ($isAjax) {
                return $resultJson->setData(true);
            }

            return $resultRedirect->setPath(
                '*/*/edit',
                ['entity_id' => $this->getRequest()->getParam('entity_id')]
            );
        }

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Prepare data. Plugins can be attached to this function. Customize carefully
     *
     * @param array $data
     * @return array
     */
    public function prepareData($data)
    {
        $behavior = $this->searchFields($data, 'behavior_');
        $exportSource = $this->searchFields($data, 'export_source_');

        $sourceData = $this->searchFields($data, self::SOURCE_DATA . '_');
        if (isset($data['xml_switch'])) {
            $sourceData['xml_switch'] = $data['xml_switch'];
        }

        $sourceFilter = $this->searchFields($data, self::SOURCE_FILTER . '_');
        $data = $this->deleteFields($this->deleteFields($data, 'behavior_'), 'export_source_');
        $sourceData = $this->validSourceData($sourceData);
        $sourceFilter = $this->validSourceFilter($sourceFilter);

        foreach ($this->additionalFields as $adField) {
            if (isset($data[$adField])) {
                $exportSource[$adField] = $data[$adField];
                unset($data[$adField]);
            }
        }

        $data['source_data'] = $sourceData + $sourceFilter;
        $data['behavior_data'] = $behavior;
        $data['export_source'] = $exportSource;

        return $data;
    }

    /**
     * @param $data
     * @param $expr
     *
     * @return array
     */
    public function searchFields($data, $expr)
    {
        $array = [];
        foreach ($data as $key => $value) {
            if (strpos($key, $expr) !== false) {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    /**
     * @param $data
     * @param $expr
     *
     * @return mixed
     */
    protected function deleteFields($data, $expr)
    {
        foreach ($data as $key => $value) {
            if (strpos($key, $expr) !== false) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * @param $data
     * @return
     */
    public function validSourceData($data)
    {
        $del = 0;
        if (isset($data[self::SOURCE_DATA . '_export']['delete'])) {
            foreach ($data[self::SOURCE_DATA . '_export']['delete'] as $id => $value) {
                if ($value) {
                    $del = 1;
                    unset($data[self::SOURCE_DATA . '_system']['value'][$id]);
                    unset($data[self::SOURCE_DATA . '_system']['entity'][$id]);
                    unset($data[self::SOURCE_DATA . '_export']['value'][$id]);
                    unset($data[self::SOURCE_DATA . '_export']['order'][$id]);
                    unset($data[self::SOURCE_DATA . '_export']['delete'][$id]);
                    unset($data[self::SOURCE_DATA . '_replace']['value'][$id]);
                }
            }
        } else {
            if (isset($data[self::SOURCE_DATA . '_system'])) {
                unset($data[self::SOURCE_DATA . '_system']);
            }
            if (isset($data[self::SOURCE_DATA . '_export'])) {
                unset($data[self::SOURCE_DATA . '_export']);
            }
            if (isset($data[self::SOURCE_DATA . '_system'])) {
                unset($data[self::SOURCE_DATA . '_system']);
            }
        }
        if ($del) {
            if (isset($data[self::SOURCE_DATA . '_map'])) {
                $data[self::SOURCE_DATA . '_map'] = array_merge([], $data[self::SOURCE_DATA . '_map']);

                foreach ($data[self::SOURCE_DATA . '_map'] as $key => &$item) {
                    $item['record_id'] = $key;
                }
            }

            if (isset($data[self::SOURCE_DATA . '_system'])) {
                $data[self::SOURCE_DATA . '_system']['value'] = array_merge(
                    [],
                    $data[self::SOURCE_DATA . '_system']['value']
                );
                $data[self::SOURCE_DATA . '_system']['entity'] = array_merge(
                    [],
                    $data[self::SOURCE_DATA . '_system']['entity']
                );
            }

            if (isset($data[self::SOURCE_DATA . '_export'])) {
                $data[self::SOURCE_DATA . '_export']['value'] = array_merge(
                    [],
                    $data[self::SOURCE_DATA . '_export']['value']
                );
                $data[self::SOURCE_DATA . '_export']['order'] = array_merge(
                    [],
                    $data[self::SOURCE_DATA . '_export']['order']
                );
                $data[self::SOURCE_DATA . '_export']['delete'] = array_merge(
                    [],
                    $data[self::SOURCE_DATA . '_export']['delete']
                );
            }

            if (isset($data[self::SOURCE_DATA . '_replace'])) {
                $data[self::SOURCE_DATA . '_replace']['value'] = array_merge(
                    [],
                    $data[self::SOURCE_DATA . '_replace']['value']
                );
            }
        }

        return $data;
    }

    /**
     * @param $data
     * @return
     */
    public function validSourceFilter($data)
    {
        $del = 0;
        $this->logger->debug(json_encode($data));
        if (isset($data[self::SOURCE_FILTER . '_field']['delete'])) {
            foreach ($data[self::SOURCE_FILTER . '_field']['delete'] as $id => $item) {
                if (isset($data[self::SOURCE_FILTER . '_field']['delete'][$id])
                    && $data[self::SOURCE_FILTER . '_field']['delete'][$id] == 1) {
                    $del = 1;
                    unset($data[self::SOURCE_FILTER . '_map'][$id]);
                    unset($data[self::SOURCE_FILTER . '_field']['value'][$id]);
                    unset($data[self::SOURCE_FILTER . '_field']['entity'][$id]);
                    unset($data[self::SOURCE_FILTER . '_field']['order'][$id]);
                    unset($data[self::SOURCE_FILTER . '_filter']['value'][$id]);
                    unset($data[self::SOURCE_FILTER . '_field']['delete'][$id]);
                    $data[self::SOURCE_FILTER . '_field']['delete'][$id] = '';
                }
            }
        } else {
            if (isset($data[self::SOURCE_FILTER . '_field'])) {
                unset($data[self::SOURCE_FILTER . '_field']);
            }
            if (isset($data[self::SOURCE_FILTER . '_filter'])) {
                unset($data[self::SOURCE_FILTER . '_filter']);
            }
        }
        if ($del) {
            if (isset($data[self::SOURCE_FILTER . '_map'])) {
                $data[self::SOURCE_FILTER . '_map'] = array_merge([], $data[self::SOURCE_FILTER . '_map']);

                foreach ($data[self::SOURCE_FILTER . '_map'] as $key => &$item) {
                    $item['record_id'] = $key;
                }
            }
            $data[self::SOURCE_FILTER . '_field']['entity'] = array_merge(
                [],
                $data[self::SOURCE_FILTER . '_field']['entity']
            );
            $data[self::SOURCE_FILTER . '_field']['value'] = array_merge(
                [],
                $data[self::SOURCE_FILTER . '_field']['value']
            );
            $data[self::SOURCE_FILTER . '_field']['order'] = array_merge(
                [],
                $data[self::SOURCE_FILTER . '_field']['order']
            );
            if (isset($data[self::SOURCE_FILTER . '_filter']['value'])) {
                $data[self::SOURCE_FILTER . '_filter']['value'] = array_merge(
                    [],
                    $data[self::SOURCE_FILTER . '_filter']['value']
                );
            }
            $data[self::SOURCE_FILTER . '_field']['delete'] = array_merge(
                [],
                $data[self::SOURCE_FILTER . '_field']['delete']
            );
        }

        return $data;
    }
}
