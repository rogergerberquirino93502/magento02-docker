<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\Adapter;

use Firebear\ImportExport\Api\Data\SeparatorFormatterInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Filesystem;
use Magento\ImportExport\Model\Export\Adapter\Csv as AbstractAdapter;
use Magento\Framework\App\CacheInterface;
use Psr\Log\LoggerInterface;

/**
 * Csv Export Adapter
 */
class Csv extends AbstractAdapter
{
    /**
     * Adapter Data
     *
     * @var []
     */
    protected $_data;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $_cache;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Initialize Adapter
     *
     * @param Filesystem $filesystem
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     * @param SeparatorFormatterInterface $separatorFormatter
     * @param null $destination
     * @param array $data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Filesystem $filesystem,
        CacheInterface $cache,
        LoggerInterface $logger,
        SeparatorFormatterInterface $separatorFormatter,
        $destination = null,
        array $data = []
    ) {
        $this->_data = $data;
        $this->_cache = $cache;
        if (isset($data['behavior_data'])) {
            $data = $data['behavior_data'];
            $this->_enclosure = $data['enclosure'] ?? $this->_enclosure;
            $this->_delimiter = $data['separator'] ?? $this->_delimiter;
            $this->_delimiter = $separatorFormatter->format($this->_delimiter);
        }

        parent::__construct(
            $filesystem,
            $destination
        );
        $this->logger = $logger;
    }

    /**
     * Write row data to source file.
     *
     * @param array $rowData
     * @throws \Exception
     * @return $this
     */
    public function writeRow(array $rowData)
    {
        $jobId = $this->_data['job_id'] ?? '';
        $exportByPage = $this->_cache->load('export_by_page' . $jobId);

        if ((null === $this->_headerCols) && ($exportByPage == 0)) {
            $this->setHeaderCols(array_keys($rowData));
        }
        if (null === $this->_headerCols) {
            $headerColumns = array_keys($rowData);
            foreach ($headerColumns as $columnName) {
                $this->_headerCols[$columnName] = false;
            }
        }

        if (isset($rowData['behavior_data'])) {
            $enclosure = '\"';
            $rowData['behavior_data'] = str_replace($enclosure, "", $rowData['behavior_data']);
        }

        $this->_fileHandler->writeCsv(
            array_merge($this->_headerCols, array_intersect_key($rowData, $this->_headerCols)),
            $this->_delimiter,
            $this->_enclosure
        );
        return $this;
    }

    /**
     * Remove temp file after export
     */
    public function __destruct()
    {
        try {
            $this->_directoryHandle->delete($this->_destination);
        } catch (FileSystemException $exception) {
            $this->logger->warning($exception->getMessage());
        } catch (ValidatorException $exception) {
            $this->logger->warning($exception->getMessage());
        }
    }
}
