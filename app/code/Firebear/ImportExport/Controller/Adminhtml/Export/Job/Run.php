<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

use Firebear\ImportExport\Controller\Adminhtml\Export\Context;
use Firebear\ImportExport\Controller\Adminhtml\Export\Job as JobController;
use Magento\Framework\App\CacheInterface;

/**
 * Class Run
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Export\Job
 */
class Run extends JobController
{
    const CACHE_TAG = 'config_scopes';

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $cache;

    /**
     * Run constructor.
     *
     * @param Context $context
     */
    public function __construct(
        Context $context,
        CacheInterface $cache
    ) {
        parent::__construct($context);
        $this->cache = $cache;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        $result[0] = true;
        $exportFile = '';
        $lastEntityId = '';
        if ($this->getRequest()->isAjax()
            && $this->getRequest()->getParam('file')
            && $this->getRequest()->getParam('id')
        ) {
            try {
                session_write_close();
                ignore_user_abort(true);
                set_time_limit(0);
                ob_implicit_flush();
                $id = (int)$this->getRequest()->getParam('id');
                $file = $this->getRequest()->getParam('file');

                $page = $this->getRequest()->getParam('page');
                $this->cache->save($page, 'current_page', [self::CACHE_TAG]);
                $exportByPage = $this->cache->load('export_by_page' . $id);

                if (($page > 1) && ($exportByPage == 0)) {
                    return $resultJson->setData([
                        'export_by_page' . $id => false,
                        'result' => true,
                        'file' => $this->_backendUrl->getUrl(
                            'import/export_job/download',
                            ['id' => $id]
                        ),
                        'last_entity_id' => $lastEntityId,
                    ]);
                }

                $lastEntityId = $this->getRequest()->getParam('last_entity_value');

                if ($lastEntityId) {
                    $this->updateLastEntityId($id, $lastEntityId);
                }

                $historyId = $this->helper->runExport($id, $file);
                $result = $this->helper->getResultProcessor();

                if (isset($result[1])
                    && $result[1] > $lastEntityId
                ) {
                    $lastEntityId = $result[1];
                }

            } catch (\Exception $e) {
                $result[0] = false;
                $historyId = 0;
            }

            $exportByPage = $this->cache->load('export_by_page' . $id);
            return $resultJson->setData([
                'export_by_page' . $id => ($exportByPage == 1) ? true : false,
                'result' => $result[0],
                'file' => $this->_backendUrl->getUrl(
                    'import/export_job/download',
                    ['id' => $historyId]
                ),
                'last_entity_id' => $lastEntityId,
            ]);
        }
    }

    /**
     * @param $jobId
     * @param $lastEntityId
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function updateLastEntityId($jobId, $lastEntityId)
    {
        $exportJob = $this->repository->getById($jobId);
        $sourceData = $exportJob->getExportSource();
        $sourceData = array_merge(
            $sourceData,
            [
                'last_entity_id' => $lastEntityId,
            ]
        );
        $exportJob->setExportSource($sourceData);
        $this->repository->save($exportJob);
    }
}
