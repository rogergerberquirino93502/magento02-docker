<?php
/**
 * AbstractAdapter
 *
 * @copyright Copyright © 2020 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\Adapter;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\Write;
use Magento\ImportExport\Model\Export\Adapter\AbstractAdapter as MagentoAbstractAdapter;
use OpenSpout\Common\Entity\Row;
use Psr\Log\LoggerInterface;

abstract class AbstractAdapter extends MagentoAbstractAdapter
{
    /**
     * Adapter Data
     *
     * @var []
     */
    protected $_data = [];
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * AbstractAdapter constructor.
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     * @param null $destination
     * @param string $destinationDirectoryCode
     * @param array $data
     * @throws LocalizedException
     */
    public function __construct(
        Filesystem $filesystem,
        LoggerInterface $logger,
        $destination = null,
        $destinationDirectoryCode = DirectoryList::VAR_DIR,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->_data = $data;

        parent::__construct($filesystem, $destination, $destinationDirectoryCode);
    }

    /**
     * Remove temp file after export
     */
    public function __destruct()
    {
        try {
            if ($this->_directoryHandle instanceof Write) {
                $this->_directoryHandle->delete($this->_destination);
            }
        } catch (FileSystemException $exception) {
            $this->logger->warning($exception->getMessage());
        } catch (ValidatorException $exception) {
            $this->logger->warning($exception->getMessage());
        }
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @throws LocalizedException
     */
    protected function getSpoutRow($rowData)
    {
        if (method_exists(Row::class, 'fromValues')) {
            $spoutRow = Row::fromValues($rowData);/** @phpstan-ignore-line */
        } elseif (class_exists(\OpenSpout\Writer\Common\Creator\WriterEntityFactory::class)) {
            $spoutRow = \OpenSpout\Writer\Common\Creator\WriterEntityFactory::createRowFromArray($rowData);
        } else {
            throw new LocalizedException(__("Unable to add a row to the stream."));
        }
        return $spoutRow;
    }
}
