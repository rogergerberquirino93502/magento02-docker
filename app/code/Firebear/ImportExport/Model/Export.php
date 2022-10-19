<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model;

use Exception;
use Firebear\ImportExport\Api\ExportJobRepositoryInterface;
use Firebear\ImportExport\Model\Export\Adapter\Factory as FireExportAdapterFactory;
use Firebear\ImportExport\Model\Export\Dependencies\Config as FireExportDiConfig;
use Firebear\ImportExport\Model\Export\EntityInterface;
use Firebear\ImportExport\Model\Source\Type\File\Config as FireExportConfig;
use Firebear\ImportExport\Traits\General as GeneralTrait;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Phrase;
use Magento\ImportExport\Model\Export as MagentoModelExport;
use Magento\ImportExport\Model\Export\Adapter\AbstractAdapter;
use Magento\ImportExport\Model\Export\AbstractEntity as AbstractEntity;
use Magento\ImportExport\Model\Export\Entity\AbstractEntity as EntityAbstractEntity;
use Magento\ImportExport\Model\Export\Entity\Factory as EntityFactory;
use Magento\ImportExport\Model\Export\ConfigInterface as ExportConfigInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class Export
 *
 * @package Firebear\ImportExport\Model
 */
class Export extends MagentoModelExport
{
    use GeneralTrait;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var FireExportDiConfig
     */
    protected $fireExportDiConfig;

    /**
     * @var FireExportConfig
     */
    protected $fireExportConfig;

    /**
     * @var ExportJobRepositoryInterface
     */
    protected $exportJobRepository;

    /**
     * @var FireExportAdapterFactory
     */
    protected $_exportAdapterFac;

    /**
     * Export constructor.
     *
     * @param Filesystem $filesystem
     * @param ExportConfigInterface $exportConfig
     * @param EntityFactory $entityFactory
     * @param FireExportAdapterFactory $exportAdapterFac
     * @param ScopeConfigInterface $scopeConfig
     * @param FireExportDiConfig $fireExportDiConfig
     * @param FireExportConfig $fireExportConfig
     * @param ExportJobRepositoryInterface $exportJobRepository
     * @param LoggerInterface $logger
     * @param ConsoleOutput $output
     * @param array $data
     */
    public function __construct(
        Filesystem $filesystem,
        ExportConfigInterface $exportConfig,
        EntityFactory $entityFactory,
        FireExportAdapterFactory $exportAdapterFac,
        ScopeConfigInterface $scopeConfig,
        FireExportDiConfig $fireExportDiConfig,
        FireExportConfig $fireExportConfig,
        ExportJobRepositoryInterface $exportJobRepository,
        LoggerInterface $logger,
        ConsoleOutput $output,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->fireExportDiConfig = $fireExportDiConfig;
        $this->fireExportConfig = $fireExportConfig;
        $this->exportJobRepository = $exportJobRepository;
        $this->output = $output;

        parent::__construct(
            $logger,
            $filesystem,
            $exportConfig,
            $entityFactory,
            $exportAdapterFac,
            $data
        );
    }

    /**
     * Add log comment
     *
     * @param mixed $debugData
     * @return $this
     */
    public function addLogComment($debugData)
    {
        if (is_array($debugData)) {
            $this->_logTrace = array_merge($this->_logTrace, $debugData);
        } else {
            $this->_logTrace[] = $debugData;
        }

        if (is_scalar($debugData)) {
            $this->addLogWriteln($debugData, $this->output, 'debug');
        } else {
            foreach ($debugData as $message) {
                if ($message instanceof Phrase) {
                    $this->addLogWriteln($message->__toString(), $this->output, 'debug');
                } else {
                    $this->addLogWriteln($message, $this->output, 'debug');
                }
            }
        }

        return $this;
    }

    /**
     * Retrieve Entity Model
     *
     * @return EntityInterface
     * @throws LocalizedException
     */
    protected function _getEntityAdapter()
    {
        if (!$this->_entityAdapter) {
            $entities = $this->fireExportDiConfig->get();
            if (isset($entities[$this->getEntity()])) {
                $entity = $entities[$this->getEntity()];
                try {
                    $this->_entityAdapter = $this->_entityFactory->create($entity['model']);
                } catch (Exception $e) {
                    $this->_logger->critical($e);
                    $this->addLogWriteln($e->getMessage(), $this->output, 'error');
                    throw new LocalizedException(__('Please enter a correct entity model.'));
                }
                if (!$this->_entityAdapter instanceof EntityInterface) {
                    throw new LocalizedException(
                        __('The entity adapter object must be an instance of %1.', EntityInterface::class)
                    );
                }

                if (!$this->_entityAdapter instanceof EntityAbstractEntity
                    && !$this->_entityAdapter instanceof AbstractEntity
                ) {
                    throw new LocalizedException(
                        __(
                            'The entity adapter object must be an instance of %1 or %2.',
                            EntityAbstractEntity::class,
                            AbstractEntity::class
                        )
                    );
                }

                if ($this->getEntity() != $this->_entityAdapter->getEntityTypeCode()) {
                    throw new LocalizedException(__('The input entity code is not equal to entity adapter code.'));
                }

                $data = $this->getData();
                if (empty($data['behavior_data']['deps']) && isset($entity['fields'])) {
                    $data['behavior_data']['deps'] = array_keys($entity['fields']);
                }
                $this->_entityAdapter->setParameters($data);
            } else {
                throw new LocalizedException(__('Please enter a correct entity.'));
            }
        }

        return $this->_entityAdapter;
    }

    /**
     * Export data.
     *
     * @return string
     * @throws LocalizedException
     */
    public function export()
    {
        if (isset($this->_data[self::FILTER_ELEMENT_GROUP])) {
            $this->addLogComment(__('Begin export of %1', $this->getEntity()));

            $countRows = 0;
            $lastEntityId = 0;
            $exportData = $this->_getEntityAdapter()
                ->setLogger($this->_logger)
                ->setWriter($this->_getWriter())
                ->export();
            $result = $exportData[0];
            if (isset($exportData[1])) {
                $countRows = (int)$exportData[1];
            }
            if (isset($exportData[2])) {
                $lastEntityId = (int)$exportData[2];
            }
            $exportJob = $this->exportJobRepository->getById($this->getData('job_id'));
            $sourceData = $exportJob->getExportSource();
            if ($lastEntityId > 0) {
                $sourceData = array_merge($sourceData, ['last_entity_id' => $lastEntityId]);
                $exportJob->setExportSource($sourceData);
                $this->exportJobRepository->save($exportJob);
            }
            if (!$countRows) {
                $this->addLogComment([__('There is no data for the export.')]);

                return false;
            }
            if ($result) {
                $this->addLogComment([__('Exported %1 items.', $countRows), __('The export is finished.')]);
            }
            return $result;
        } else {
            throw new LocalizedException(__('Please provide filter data.'));
        }
    }

    /**
     * Retrieve Writer
     *
     * @return mixed
     * @throws LocalizedException
     */
    protected function _getWriter()
    {
        if (!$this->_writer) {
            $data = $this->fireExportConfig->get();
            $fileFormats = $data['export'];
            if (isset($fileFormats[$this->getFileFormat()])) {
                try {
                    $this->_writer = $this->_exportAdapterFac->create(
                        $fileFormats[$this->getFileFormat()]['model'],
                        ['data' => $this->_data]
                    );
                } catch (Exception $e) {
                    $this->_logger->critical($e);
                    throw new LocalizedException(__('Please enter a correct entity model.'));
                }
                if (!$this->_writer instanceof AbstractAdapter) {
                    throw new LocalizedException(
                        __('The adapter object must be an instance of %1.', AbstractAdapter::class)
                    );
                }
            } else {
                throw new LocalizedException(__('Please correct the file format.'));
            }
        }
        return $this->_writer;
    }

    /**
     * Retrieve entity field for export
     *
     * @return array
     * @throws LocalizedException
     */
    public function getFields()
    {
        return $this->_getEntityAdapter()->getFieldsForExport();
    }
}
