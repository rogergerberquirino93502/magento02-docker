<?php
declare(strict_types=1);
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Exception;
use Firebear\ImportExport\Controller\Adminhtml\Context;
use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Firebear\ImportExport\Model\Import\Platforms;
use Firebear\ImportExport\Model\Job\Processor;
use Firebear\ImportExport\Model\Source\Type\AbstractType;
use Firebear\ImportExport\Model\Source\Type\SourceTypeInterface;
use Firebear\ImportExport\Model\Source\Type\SearchSourceTypeInterface;
use Firebear\ImportExport\Ui\Component\Listing\Column\Entity\Import\Options;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Loadmap
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Job
 */
class Loadmap extends JobController
{
    /**
     * @var Processor
     */
    protected $processor;

    /**
     * @var Platforms
     */
    protected $platforms;

    /**
     * @var Options
     */
    protected $options;

    /**
     * @var SourceTypeInterface
     */
    private $importSource;

    /**
     * Loadmap constructor.
     *
     * @param Context $context
     * @param Platforms $platforms
     * @param Processor $processor
     * @param Options $options
     */
    public function __construct(
        Context $context,
        Platforms $platforms,
        Processor $processor,
        Options $options
    ) {
        parent::__construct($context);

        $this->platforms = $platforms;
        $this->processor = $processor;
        $this->options = $options;
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        $resultData = $messages = $result = [];
        if ($this->getRequest()->isAjax()) {
            [$importData, $sourceType, $maps] = $this->processRequestForm();
            try {
                if (isset($importData[SearchSourceTypeInterface::SCAN_DIR])
                    && $importData[SearchSourceTypeInterface::SCAN_DIR]
                ) {
                    $importSource = $this->getImportSource($importData);
                    $filePath = $importSource->getImportFilePath()
                        ?: $sourceData['file_path']
                        ?? '';
                    $fileType = $importData['type_file'];

                    if (!$importSource->isExists($filePath)) {
                        throw new LocalizedException(__('Path not exists'));
                    }
                    if (!$importSource->isAllowablePath($filePath)) {
                        throw new LocalizedException(__(
                            'Path not allowable. Only %1',
                            implode(', ', $importSource->allowedDirsForScan())
                        ));
                    }

                    $files = $importSource->search("$filePath/*.$fileType");
                    if (!empty($files)) {
                        $files = $this->getImportSource($importData)
                            ->filterSearchedFiles($files);

                        foreach ($files as $file) {
                            $importData[$sourceType . '_file_path'] = $file;
                            $importData['file_path'] = $file;
                            $result = $this->processor->getColumns(
                                $importData,
                                (bool)$this->getRequest()->getParam('_reset_source')
                            );
                            if (!$result) {
                                break;
                            }
                        }
                    }
                } else {
                    $result = $this->processor->getColumns(
                        $importData,
                        (bool)$this->getRequest()->getParam('_reset_source')
                    );
                }
                //load categories map
                foreach ($result as $key => $el) {
                    if (preg_match('/^(attribute\|).+/', $el ?? '')) {
                        unset($result[$key]);
                    }
                }

                if (is_array($result)) {
                    $messages = [];
                }
            } catch (Exception $e) {
                return $resultJson->setData(['error' => $e->getMessage()]);
            }
            /*render Import Attribute dropdown*/
            if (!is_array($result)) {
                return $resultJson->setData(['error' => $result]);
            }

            if (isset($importData['entity'])) {
                try {
                    $collect = $this->options->toOptionArray(1, $importData['entity']);
                    $options = $collect[$importData['entity']];
                    $resultData = [
                        'map' => $maps,
                        'columns' => $result,
                        'messages' => $messages,
                        'options' => $options
                    ];
                } catch (Exception $exception) {
                    return $resultJson->setData(['error' => $exception->getMessage()]);
                }
            }
        }
        return $resultJson->setData($resultData);
    }

    /**
     * @return array
     */
    protected function processRequestForm()
    {
        $type = $this->getRequest()->getParam('type');
        [$importData, $sourceType] = $this->processRequestImportData();
        $importData['platforms'] = $type;
        if (isset($importData['type_file'])) {
            $this->processor->setTypeSource($importData['type_file']);
        }
        $maps = [];
        if ($type) {
            $mapArr = $this->platforms->getAllData($type);
            if (!empty($mapArr)) {
                $maps = $mapArr;
            }
        }
        return [$importData, $sourceType, $maps];
    }

    /**
     * @param array $sourceData
     * @return AbstractType|SourceTypeInterface|SearchSourceTypeInterface
     * @throws LocalizedException
     */
    protected function getImportSource(array $sourceData)
    {
        if ($this->importSource === null) {
            $this->importSource = $this->processor->getImportModel()
                ->setData($sourceData)
                ->getSource();
        }
        return $this->importSource;
    }
}
