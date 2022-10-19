<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Job\Strategy;

use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Model\Source\Type\SearchSourceTypeInterface;
use Firebear\ImportExport\Model\Job\Processor;

/**
 * @api
 */
class ScanDirStrategy implements StrategyInterface
{
    /**
     * @var ImportInterface|null
     */
    private $job;

    /**
     * @var int
     */
    private $key = 0;

    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var SearchSourceTypeInterface|null
     */
    private $source;

    /**
     * @var string[]
     */
    private $files;

    /**
     * @var mixed[]
     */
    private $data;

    /**
     * @var bool
     */
    private $lastResult = true;

    /**
     * Initialize
     *
     * @param Processor $processor
     */
    public function __construct(
        Processor $processor
    ) {
        $this->processor = $processor;
    }

    /**
     * Set job
     *
     * @param ImportInterface $job
     * @return $this
     */
    public function setJob(ImportInterface $job)
    {
        $this->job = $job;
        return $this;
    }

    /**
     * Checks if strategy is available
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isAvailable()
    {
        if ($this->job instanceof ImportInterface) {
            $this->data = $this->job->getSourceData();
            if (empty($this->data[SearchSourceTypeInterface::SCAN_DIR])) {
                return false;
            }

            $source = $this->createImportModel($this->data)->getSource();

            if ($source instanceof SearchSourceTypeInterface && $source->isSearchable()) {
                $this->source = $source;
                return true;
            }
        }
        return false;
    }

    /**
     * Return the current element
     *
     * @return ImportInterface
     */
    public function current()
    {
        return $this->job;
    }

    /**
     * Move forward to next element
     *
     * @return void
     */
    public function next()
    {
        $this->key++;
        $this->prepare();
    }

    /**
     * Return the key of the current element
     *
     * @return int
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * Checks if current position is valid
     *
     * @return bool
     */
    public function valid()
    {
        if ($this->isStopLoopOnFail() && !$this->lastResult) {
            $this->processor->addLogComment(
                __('Import all files from folder are stopped, because last file finished with error'),
                $this->processor->getOutput(),
                'error'
            );
            return false;
        }

        return $this->key < count($this->files);
    }

    /**
     * Rewind the \Iterator to the first element
     *
     * @return void
     */
    public function rewind()
    {
        $this->key = 0;
        $this->files = [];

        $filePath = $this->source->getImportFilePath() ?: $this->data['file_path'] ?? '';
        $fileType = $this->data['type_file'];

        if ($this->source->isExists($filePath) &&
            $this->source->isAllowablePath($filePath)
        ) {
            $this->processor->addLogComment(
                __('Scan Directory for files'),
                $this->processor->getOutput(),
                'info'
            );
            $files = $this->source->search("$filePath/*.$fileType");
            if ($files) {
                $this->files = $this->source->filterSearchedFiles($files);
                $this->prepare();
            } else {
                $this->processor->addLogComment(
                    __('Directory is Empty'),
                    $this->processor->getOutput(),
                    'error'
                );
            }
        }
    }

    /**
     * Prepare job
     *
     * @return void
     */
    private function prepare()
    {
        if (isset($this->files[$this->key()])) {
            $this->source = $this->createImportModel($this->data)->getSource();

            $file = $this->files[$this->key()];
            $file = $this->source->getFilePath($file);
            $this->processor->addLogComment(
                __('Import File %1', $file),
                $this->processor->getOutput(),
                'info'
            );
            $this->processor->setImportFile($file);
        }
    }

    /**
     * @param array $data
     * @return \Firebear\ImportExport\Model\Import
     */
    protected function createImportModel(array $data)
    {
        return $this->processor->getImportModel(true)->setData($data);
    }

    /**
     * @param bool $result
     */
    public function setLastResult(bool $result)
    {
        $this->lastResult = $result;
    }

    /**
     * @return bool
     */
    private function isStopLoopOnFail()
    {
        return (bool)($this->data['stop_loop_on_fail'] ?? false);
    }
}
