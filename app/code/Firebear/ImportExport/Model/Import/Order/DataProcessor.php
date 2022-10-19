<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\Order;

use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;

/**
 * Order Data Processor
 */
class DataProcessor
{
    /**
     * Open File Resource
     *
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_varDirectory;

    /**
     * File Name
     *
     * @var string
     */
    protected $_fileName;

    /**
     * Initialize Processor
     *
     * @param Filesystem $filesystem
     */
    public function __construct(
        Filesystem $filesystem
    ) {
        $this->_varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
    }

    /**
     * Retrieve File Name
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->_fileName;
    }

    /**
     * Set File Name
     *
     * @return $this
     */
    public function setFileName($fileName)
    {
        $this->_fileName = $fileName . '.json';

        return $this;
    }

    /**
     * Load data from File
     *
     * @param string $identifier
     * @return $this
     */
    public function load($identifier = null)
    {
        return [];
    }

    /**
     * Save data to File
     *
     * @param array $ids
     * @param string $identifier
     * @return boolean
     */
    public function merge(array $ids, $identifier)
    {
        return $ids;
    }
}
