<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\Adapter;

use Exception;
use Firebear\ImportExport\Model\Output\Xslt;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;
use XMLWriter;

/**
 * Xml Export Adapter
 */
class Xml extends AbstractAdapter
{
    /**
     * XML Writer
     *
     * @var XMLWriter
     */
    protected $writer;

    /**
     * Xslt Converter
     *
     * @var Xslt
     */
    protected $xslt;

    /**
     * Xsl Document
     *
     * @var string
     */
    protected $xsl;

    /**
     * Xml constructor.
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     * @param XMLWriter $writer
     * @param Xslt $xslt
     * @param null $destination
     * @param string $destinationDirectoryCode
     * @param array $data
     * @throws LocalizedException
     */
    public function __construct(
        Filesystem $filesystem,
        LoggerInterface $logger,
        XMLWriter $writer,
        Xslt $xslt,
        $destination = null,
        $destinationDirectoryCode = DirectoryList::VAR_DIR,
        array $data = []
    ) {
        $this->writer = $writer;
        $this->xslt = $xslt;

        if (!empty($data['xml_switch']) && isset($data['xslt'])) {
            $this->xsl = $data['xslt'];
        }
        parent::__construct($filesystem, $logger, $destination, $destinationDirectoryCode, $data);
    }

    /**
     * MIME-type for 'Content-Type' header.
     *
     * @return string
     */
    public function getContentType()
    {
        return 'text/xml';
    }

    /**
     * Get contents of export file
     *
     * @return string
     */
    public function getContents()
    {
        $this->writer->endDocument();
        $result = $this->writer->outputMemory();
        return $this->xsl
            ? $this->xslt->convert($result, $this->xsl)
            : $result;
    }

    /**
     * Return file extension for downloading.
     *
     * @return string
     */
    public function getFileExtension()
    {
        return 'xml';
    }

    /**
     * Write row data to source file.
     *
     * @param array $rowData
     * @return $this
     * @throws Exception
     */
    public function writeRow(array $rowData)
    {
        if (!empty($rowData)) {
            $this->writer->startElement('item');
            foreach ($rowData as $key => $value) {
                if (is_array($value)) {
                    $this->recursiveAdd($key, $value);
                } elseif (is_string($key)) {
                    $this->writer->writeElement($key, $value);
                }
            }
            $this->writer->endElement();
        }
        return $this;
    }

    /**
     * @param $key
     * @param array $data
     */
    protected function recursiveAdd($key, array $data)
    {
        if (!empty($data)) {
            if (!is_numeric($key)) {
                $this->writer->startElement($key);
            }
            foreach ($data as $ki => $values) {
                if (is_array($values)) {
                    $this->recursiveAdd($ki, $values);
                } else {
                    $this->writer->writeElement($ki, $values);
                }
            }
            if (!is_numeric($key)) {
                $this->writer->endElement();
            }
        }
    }

    /**
     * @return $this
     */
    protected function _init()
    {
        $this->writer->openURI('php://output');
        $this->writer->openMemory();
        $this->writer->startDocument("1.0", "UTF-8");
        $this->writer->setIndent(1);
        $this->writer->startElement("Items");

        return $this;
    }
}
