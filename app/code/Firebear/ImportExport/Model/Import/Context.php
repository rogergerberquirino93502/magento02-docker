<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import;

use Firebear\ImportExport\Model\ResourceModel\Import\Data as DataSourceModel;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\ImportExport\Helper\Data as ImportExportData;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ResourceModel\Helper as ResourceHelper;
use Symfony\Component\Console\Output\ConsoleOutput;
use Psr\Log\LoggerInterface;

/**
 * Import Adapter Context
 */
class Context
{
    /**
     * Json Serializer
     *
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * Json Helper
     *
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * Import Export Data
     *
     * @var \Magento\ImportExport\Helper\Data
     */
    protected $importExportData;

    /**
     * DB Data Source Model
     *
     * @var \Firebear\ImportExport\Model\ResourceModel\Import\Data
     */
    protected $dataSourceModel;

    /**
     * Eav Model Config
     *
     * @var \Magento\Eav\Model\Config
     */
    protected $config;

    /**
     * Resource Connection
     *
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * Resource Helper
     *
     * @var \Magento\ImportExport\Model\ResourceModel\Helper
     */
    protected $resourceHelper;

    /**
     * String Lib
     *
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $string;

    /**
     * Processing Error Aggregator
     *
     * @var \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface
     */
    protected $errorAggregator;

    /**
     * Console output
     *
     * @var \Symfony\Component\Console\Output\ConsoleOutput
     */
    protected $output;

    /**
     * Logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Context constructor.
     *
     * @param SerializerInterface $serializer
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\ImportExport\Helper\Data $importExportData
     * @param \Firebear\ImportExport\Model\ResourceModel\Import\Data $dataSourceModel
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface $errorAggregator
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Symfony\Component\Console\Output\ConsoleOutput $output
     */
    public function __construct(
        SerializerInterface $serializer,
        JsonHelper $jsonHelper,
        ImportExportData $importExportData,
        DataSourceModel $dataSourceModel,
        Config $config,
        ResourceConnection $resource,
        ResourceHelper $resourceHelper,
        StringUtils $string,
        ProcessingErrorAggregatorInterface $errorAggregator,
        LoggerInterface $logger,
        ConsoleOutput $output
    ) {
        $this->serializer = $serializer;
        $this->jsonHelper = $jsonHelper;
        $this->importExportData = $importExportData;
        $this->dataSourceModel = $dataSourceModel;
        $this->config = $config;
        $this->resource = $resource;
        $this->resourceHelper = $resourceHelper;
        $this->string = $string;
        $this->errorAggregator = $errorAggregator;
        $this->logger = $logger;
        $this->output = $output;
    }

    /**
     * Retrieve Json Serializer
     *
     * @return SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * Retrieve Json Helper
     *
     * @return \Magento\Framework\Json\Helper\Data
     */
    public function getJsonHelper()
    {
        return $this->jsonHelper;
    }

    /**
     * Retrieve Import Export Data
     *
     * @return \Magento\ImportExport\Helper\Data
     */
    public function getImportExportData()
    {
        return $this->importExportData;
    }

    /**
     * Retrieve Data Source Model
     *
     * @return \Firebear\ImportExport\Model\ResourceModel\Import\Data
     */
    public function getDataSourceModel()
    {
        return $this->dataSourceModel;
    }

    /**
     * Retrieve Eav Model Config
     *
     * @return \Magento\Eav\Model\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Retrieve Resource Connection
     *
     * @return \Magento\Framework\App\ResourceConnection
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Retrieve Resource Helper
     *
     * @return \Magento\ImportExport\Model\ResourceModel\Helper
     */
    public function getResourceHelper()
    {
        return $this->resourceHelper;
    }

    /**
     * Retrieve String Lib
     *
     * @return \Magento\Framework\Stdlib\StringUtils
     */
    public function getStringUtils()
    {
        return $this->string;
    }

    /**
     * Retrieve Processing Error Aggregator
     *
     * @return \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface
     */
    public function getErrorAggregator()
    {
        return $this->errorAggregator;
    }

    /**
     * Retrieve logger
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Retrieve output
     *
     * @return \Symfony\Component\Console\Output\ConsoleOutput
     */
    public function getOutput()
    {
        return $this->output;
    }
}
