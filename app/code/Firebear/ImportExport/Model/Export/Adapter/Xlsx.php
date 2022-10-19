<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Export\Adapter;

use Magento\Framework\Exception\ValidatorException;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Writer\Common\Creator\WriterFactory;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use OpenSpout\Writer\WriterInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;
use OpenSpout\Common\Entity\Row;

/**
 * Xlsx Export Adapter
 */
class Xlsx extends AbstractAdapter
{
    /**
     * Spreadsheet Writer
     *
     * @var WriterInterface
     */
    private $writer;

    /**
     * Xlsx constructor
     *
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
        if (empty($data['export_source']['file_path']) && empty($data['export_source']['request_url'])) {
            throw new LocalizedException(__('Export File Path is Empty.'));
        }

        parent::__construct(
            $filesystem,
            $logger,
            $destination,
            $destinationDirectoryCode,
            $data
        );
    }

    /**
     * Write row data to source file
     *
     * @param array $rowData
     * @return AbstractAdapter
     * @throws IOException
     * @throws LocalizedException
     * @throws WriterNotOpenedException
     */
    public function writeRow(array $rowData)
    {
        if (null === $this->_headerCols) {
            $this->setHeaderCols(array_keys($rowData));
        }

        $this->addRow(
            array_merge(
                $this->_headerCols,
                array_intersect_key($rowData, $this->_headerCols)
            )
        );
        return $this;
    }

    /**
     * Prepare Row Data
     *
     * @param array $rowData
     * @return Row $rowData
     * @throws LocalizedException
     */
    private function prepareRow(array $rowData)
    {
        $rowData = array_map(function ($value) {
            return (string)$value;
        }, $rowData);

        return $this->getSpoutRow($rowData);
    }

    /**
     * Add Row Data
     *
     * @param array $rowData
     * @return void
     * @throws IOException
     * @throws WriterNotOpenedException
     * @throws LocalizedException
     */
    private function addRow(array $rowData)
    {
        $this->writer->addRow(
            $this->prepareRow($rowData)
        );
    }

    /**
     * Set column names
     *
     * @param array $headerColumns
     * @return AbstractAdapter
     * @throws IOException
     * @throws LocalizedException
     * @throws WriterNotOpenedException
     */
    public function setHeaderCols(array $headerColumns)
    {
        if (null !== $this->_headerCols) {
            throw new LocalizedException(__('The header column names are already set.'));
        }
        if ($headerColumns) {
            foreach ($headerColumns as $columnName) {
                $this->_headerCols[$columnName] = false;
            }
            $this->writer->addRow($this->getSpoutRow(array_keys($this->_headerCols)));
        }
        return $this;
    }

    /**
     * Get contents of export file
     *
     * @return string
     */
    public function getContents()
    {
        $this->writer->close();
        return parent::getContents();
    }

    /**
     * MIME-type for 'Content-Type' header
     *
     * @return string
     */
    public function getContentType()
    {
        return 'application/vnd.ms-excel';
    }

    /**
     * Return file extension for downloading
     *
     * @return string
     */
    public function getFileExtension()
    {
        return 'xlsx';
    }

    /**
     * Method called as last step of object instance creation
     *
     * @return AbstractAdapter
     * @throws ValidatorException
     * @throws IOException|UnsupportedTypeException
     */
    protected function _init()
    {
        if (empty($this->writer)) {
            $this->writer = WriterFactory::createFromFile('.' . $this->getFileExtension());
        }
        $file = $this->_directoryHandle->getAbsolutePath(
            $this->_destination
        );
        $this->writer->openToFile($file);
        return $this;
    }
}
