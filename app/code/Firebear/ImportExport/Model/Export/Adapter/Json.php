<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\Adapter;

use Bcn\Component\Json\Exception\WritingError;
use Bcn\Component\Json\Writer;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;

/**
 * Class Json
 * @package Firebear\ImportExport\Model\Export\Adapter
 */
class Json extends AbstractAdapter
{
    /**
     * @var Writer
     */
    private $writer;

    /**
     * @var string
     */
    private $file;

    /**
     * Json constructor.
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
        parent::__construct($filesystem, $logger, $destination, $destinationDirectoryCode, $data);
    }

    /**
     * @param array $rowData
     * @return $this|AbstractAdapter
     * @throws WritingError
     */
    public function writeRow(array $rowData)
    {
        $finalData = [];
        if (!empty($rowData)) {
            foreach ($rowData as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $finalData[$key][] = array_shift($v);
                    }
                } else {
                    $finalData[$key] = $value;
                }
            }
            $this->writer->write(null, $finalData);
        }
        return $this;
    }

    public function getContents()
    {
        $this->writer->leave();
        $this->writer->leave();
        return parent::getContents();
    }

    /**
     * MIME-type for 'Content-Type' header
     *
     * @return string
     */
    public function getContentType()
    {
        return 'application/json';
    }

    public function getFileExtension()
    {
        return 'json';
    }

    /**
     * @return $this|AbstractAdapter
     * @throws ValidatorException
     */
    protected function _init()
    {
        if (!class_exists(Writer::class)) {
            $this->getLogger()->error('Install package: composer require bcncommerce/json-stream');
            return $this;
        }
        $this->file = $this->_directoryHandle->getAbsolutePath(
            $this->_destination
        );
        $entityKey = $this->_data['entity'] ?? 'entity';
        $fileHandler = fopen($this->file, "w");
        $this->writer = new Writer($fileHandler);
        $this->writer->enter(Writer::TYPE_OBJECT);
        $this->writer->enter($entityKey, Writer::TYPE_ARRAY);
        return $this;
    }
}
