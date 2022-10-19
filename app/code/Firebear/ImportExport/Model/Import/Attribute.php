<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import;

use Exception;
use Firebear\ImportExport\Helper\Data;
use Firebear\ImportExport\Setup\EavSetup;
use Firebear\ImportExport\Traits\Import\Entity as ImportTrait;
use InvalidArgumentException;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute as Attr;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\AbstractEntity;
use Magento\ImportExport\Model\Import\AbstractSource;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\ImportFactory;
use Magento\Swatches\Helper\Media;
use Zend_Validate;

/**
 * Attribute Import
 */
class Attribute extends AbstractEntity implements ImportAdapterInterface
{
    use ImportTrait;

    /**
     * Entity Type Code
     */
    const ENTITY_TYPE_CODE = 'attribute';

    /**
     * Attribute Id column name
     */
    const COLUMN_ENTITY_ID = 'attribute_id';

    /**
     * Store Id column name
     */
    const COLUMN_STORE_ID = 'store_id';

    /**
     * Attribute code column name
     */
    const COLUMN_ATTRIBUTE_CODE = 'attribute_code';

    /**
     * Column product attribute set
     */
    const COLUMN_ATTRIBUTE_SET = 'attribute_set';

    /**
     * Column product attribute group
     */
    const COLUMN_ATTRIBUTE_GROUP = 'group:name';

    /**
     * Column product attribute group
     */
    const COLUMN_ATTRIBUTE_GROUP_SORT_ORDER = 'group:sort_order';

    /**
     * Column product attribute base option
     */
    const COLUMN_ATTRIBUTE_BASE_OPTION = 'option:base_value';

    /**
     * Column product attribute option
     */
    const COLUMN_ATTRIBUTE_OPTION = 'option:value';

    /**
     * Column product attribute option for swatches
     */
    const COLUMN_SWATCH_ATTRIBUTE_OPTION = 'option:swatch_value';

    /**
     * Column for swatches type
     */
    const COLUMN_SWATCH_ATTRIBUTE_TYPE = 'option:swatch_type';

    /**
     * Column product attribute delete option value
     */
    const COLUMN_ATTRIBUTE_DELETE_VALUES = 'option:delete_values';

    /**
     * Column product attribute delete option
     */
    const COLUMN_ATTRIBUTE_DELETE_OPTIONS = 'option:delete_options';

    /**
     * Column product attribute group
     */
    const COLUMN_ATTRIBUTE_OPTION_SORT_ORDER = 'option:sort_order';

    /**
     * Main Table Name
     *
     * @var string
     */
    protected $_mainTable = 'eav_attribute';

    /**
     * Attribute set
     *
     * @var \Magento\Eav\Model\Entity\Attribute\Set
     */
    protected $_set;

    /**
     * Attribute set list
     *
     * @var []
     */
    protected $_setList = [];

    /**
     * Default attribute set id
     *
     * @var integer
     */
    protected $_defaultSetId;

    /**
     * EAV config
     *
     * @var array
     */
    protected $_eavConfig;

    /**
     * EAV setup
     *
     * @var \Magento\Eav\Setup\EavSetup
     */
    protected $_eavSetup;

    /**
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * Catalog product entity typeId
     *
     * @var int
     */
    protected $_entityTypeId;

    /**
     * Permanent entity columns
     *
     * @var string[]
     */
    protected $_permanentAttributes = [
        self::COLUMN_ATTRIBUTE_CODE
    ];

    /**
     * Permanent entity columns
     *
     * @var string[]
     */
    protected $_storeDependentAttributes = [
        'option:value',
        'frontend_label'
    ];

    /**
     * Error Codes
     */
    const ERROR_DUPLICATE_ATTRIBUTE_CODE = 'duplicateAttributeCode';
    const ERROR_ATTRIBUTE_CODE_IS_EMPTY = 'attributeCodeIsEmpty';
    const ERROR_STORE_ID_IS_EMPTY = 'attributeStoreIdIsEmpty';
    const ERROR_ATTRIBUTE_SET_IS_EMPTY = 'attributeSetIsEmpty';

    /**
     * Validation Failure Message Template Definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_DUPLICATE_ATTRIBUTE_CODE => 'Attribute code is found more than once in the import file',
        self::ERROR_ATTRIBUTE_CODE_IS_EMPTY => 'Attribute code is empty',
        self::ERROR_STORE_ID_IS_EMPTY => 'Attribute store_id is empty',
        self::ERROR_ATTRIBUTE_SET_IS_EMPTY => 'Attribute set is empty',
    ];

    /**
     * Deleted attributes
     *
     * @var array
     */
    private $_alreadyDeleted = [];

    /**
     * Recorded attributes
     *
     * @var array
     */
    private $_alreadyRecorded = [];

    /**
     * @var \Magento\Catalog\Model\Product\Media\Config
     */
    protected $mediaConfig;

    /**
     * @var \Magento\CatalogImportExport\Model\Import\Uploader
     */
    protected $_fileUploader;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_mediaDirectory;

    /**
     * @var \Firebear\ImportExport\Model\Import\UploaderFactory
     */
    protected $_uploaderFactory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Swatches\Helper\Data
     */
    protected $swatchesHelperData;

    /**
     * @var \Magento\Swatches\Helper\Media
     */
    protected $swatchHelperMedia;

    /**
     * @var SetFactory
     */
    protected $attributeSetFactory;

    /**
     * Attribute constructor.
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param ImportFactory $importFactory
     * @param Set $set
     * @param EavConfig $eavConfig
     * @param EavSetup $eavSetup
     * @param Data $helper
     * @param Config $mediaConfig
     * @param Uploader $_fileUploader
     * @param Filesystem $filesystem
     * @param \Magento\Swatches\Helper\Data $swatchesHelperData
     * @param Media $swatchHelperMedia
     * @param UploaderFactory $_uploaderFactory
     * @param ConsoleOutput $output
     * @param ImportExportData $importExportData
     * @param JsonHelper $jsonHelper
     * @param Data $helper
     * @param SetFactory $attributeSetFactory
     * @param array $data
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        ImportFactory $importFactory,
        Set $set,
        EavConfig $eavConfig,
        EavSetup $eavSetup,
        Data $helper,
        Config $mediaConfig,
        Uploader $_fileUploader,
        Filesystem $filesystem,
        \Magento\Swatches\Helper\Data $swatchesHelperData,
        Media $swatchHelperMedia,
        UploaderFactory $_uploaderFactory,
        SetFactory $attributeSetFactory,
        array $data = []
    ) {
        $this->_logger = $context->getLogger();
        $this->output = $context->getOutput();
        $this->_resource = $context->getResource();
        $this->_importExportData = $context->getImportExportData();
        $this->_resourceHelper = $context->getResourceHelper();
        $this->jsonHelper = $context->getJsonHelper();
        $this->_set = $set;
        $this->_eavConfig = $eavConfig;
        $this->_eavSetup = $eavSetup;
        $this->_helper = $helper;
        $this->attributeSetFactory = $attributeSetFactory;

        $this->mediaConfig = $mediaConfig;
        $this->_fileUploader = $_fileUploader;
        $this->filesystem = $filesystem;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->_uploaderFactory = $_uploaderFactory;
        $this->swatchesHelperData = $swatchesHelperData;
        $this->swatchHelperMedia = $swatchHelperMedia;

        parent::__construct(
            $context->getStringUtils(),
            $scopeConfig,
            $importFactory,
            $context->getResourceHelper(),
            $context->getResource(),
            $context->getErrorAggregator(),
            $data
        );
        $this->initErrorTemplates();
        $this->initAttributeSet();
    }

    /**
     * Initialize Error Templates
     *
     * @return void
     */
    public function initErrorTemplates()
    {
        foreach ($this->_messageTemplates as $errorCode => $template) {
            $this->addMessageTemplate($errorCode, $template);
        }
    }

    /**
     * Source model setter
     *
     * @param AbstractSource $source
     * @return $this
     */
    public function setSource(AbstractSource $source)
    {
        $this->_source = $source;
        $this->_dataValidated = false;

        return $this;
    }

    /**
     * Inner source object getter
     *
     * @return AbstractSource
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getSource()
    {
        if (!$this->_source) {
            throw new LocalizedException(__('Please specify a source.'));
        }
        return $this->_source;
    }

    /**
     * Initialize Attribute Set
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initAttributeSet()
    {
        $collection = $this->_set->getCollection()
            ->setEntityTypeFilter(
                $this->_getEntityTypeId()
            );
        /* default set id */
        $this->_defaultSetId = $collection->getFirstItem()->getId();
        /* create set list */
        $this->_setList = [];
        foreach ($collection as $set) {
            $name = $set->getAttributeSetName();
            $this->_setList[$name] = $set->getId();
        }
    }

    /**
     * Get entity type id
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getEntityTypeId()
    {
        if (!$this->_entityTypeId) {
            $entityType = $this->_eavConfig->getEntityType('catalog_product');
            $this->_entityTypeId = $entityType->getId();
        }
        return $this->_entityTypeId;
    }

    /**
     * Retrieve Main Table Name
     *
     * @return string
     */
    public function getMainTable()
    {
        return $this->_resource->getTableName(
            $this->_mainTable
        );
    }

    /**
     * Retrieve additional attribute table name for specified entity type
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @codeCoverageIgnore
     */
    public function getAdditionalAttributeTable()
    {
        $entityType = $this->_eavConfig->getEntityType('catalog_product');
        return $this->_resource->getTableName(
            $entityType->getResource()->getAdditionalAttributeTable(
                $this->_getEntityTypeId()
            )
        );
    }

    /**
     * Retrieve All Fields Source
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAllFields()
    {
        $fields = array_keys($this->describeTable());
        $fields = array_merge(self::getAdditionalColumns(), $fields);
        return $fields;
    }

    /**
     * Retrieve the column descriptions for a table, include additional table
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function describeTable()
    {
        $fields = $this->_connection->describeTable($this->getMainTable());
        $fields+= $this->_connection->describeTable($this->getAdditionalAttributeTable());
        return $fields;
    }

    /**
     * Import Behavior Getter
     *
     * @param array $rowData
     * @return string
     */
    public function getBehavior(array $rowData = null)
    {
        if (!isset($this->_parameters['behavior']) ||
            $this->_parameters['behavior'] != Import::BEHAVIOR_APPEND &&
            $this->_parameters['behavior'] != Import::BEHAVIOR_REPLACE &&
            $this->_parameters['behavior'] != Import::BEHAVIOR_DELETE
        ) {
            return Import::getDefaultBehavior();
        }
        return $this->_parameters['behavior'];
    }

    /**
     * Import Data Rows
     *
     * @return boolean
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    protected function _importData()
    {
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            foreach ($bunch as $rowNumber => $rowData) {
                /* validate data */
                if (!$rowData || !$this->validateRow($rowData, $rowNumber)) {
                    continue;
                }

                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNumber);
                    continue;
                }
                /* behavior selector */
                switch ($this->getBehavior()) {
                    case Import::BEHAVIOR_DELETE:
                        $this->_deleteAttribute($rowData);
                        break;
                    case Import::BEHAVIOR_REPLACE:
                        $this->_saveAttribute(
                            $this->_prepareDataForReplace($rowData)
                        );
                        break;
                    case Import::getDefaultBehavior():
                    case Import::BEHAVIOR_APPEND:
                        $this->_saveAttribute(
                            $this->_prepareDataForUpdate($rowData)
                        );
                        break;
                }
            }
        }
        return true;
    }

    /**
     * Save Attribute
     *
     * @param array $rowData
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _saveAttribute(array $rowData)
    {
        $code = $rowData[self::COLUMN_ATTRIBUTE_CODE];
        unset($rowData[self::COLUMN_ATTRIBUTE_CODE]);

        if (!in_array($code, $this->_alreadyRecorded)) {
            $time = explode(' ', microtime());
            $startTime = $time[0] + $time[1];

            try {
                $this->_eavSetup->addAttribute($this->_getEntityTypeId(), $code, $rowData);
            } catch (\Exception $exception) {
                $this->addLogWriteln($exception->getMessage(), $this->getOutput(), 'error');
            }
            $time = explode(" ", microtime());
            $endTime = $time[0] + $time[1];
            $totalTime = $endTime - $startTime;
            $totalTime = round($totalTime, 5);
            $message = 'recorded attribute with code: %1 .... %2s';
            $this->addLogWriteln(__($message, $code, $totalTime), $this->output, 'info');

            if (!isset($rowData['option'])) {
                $this->_alreadyRecorded[] = $code;
            }
        }
    }

    /**
     * Delete Attribute
     *
     * @param array $rowData
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _deleteAttribute(array $rowData)
    {
        $code = $rowData[self::COLUMN_ATTRIBUTE_CODE];
        if (!in_array($code, $this->_alreadyDeleted)) {
            $time = explode(" ", microtime());
            $startTime = $time[0] + $time[1];

            $this->_eavSetup->removeAttribute($this->_getEntityTypeId(), $code);

            $time = explode(" ", microtime());
            $endTime = $time[0] + $time[1];
            $totalTime = $endTime - $startTime;
            $totalTime = round($totalTime, 5);
            $message = 'deleted attribute with code: %1 .... %2s';
            $this->addLogWriteln(__($message, $code, $totalTime), $this->output, 'info');

            $this->_alreadyDeleted[] = $code;
        }
    }

    /**
     * Validate Data Row
     *
     * @param array $rowData
     * @param int $rowNumber
     *
     * @return boolean
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function validateRow(array $rowData, $rowNumber)
    {
        if (isset($this->_validatedRows[$rowNumber])) {
            /* check that row is already validated */
            return !$this->getErrorAggregator()->isRowInvalid($rowNumber);
        }
        $this->_validatedRows[$rowNumber] = true;
        $this->_processedEntitiesCount++;
        /* behavior selector */
        switch ($this->getBehavior()) {
            case Import::BEHAVIOR_DELETE:
                $this->_validateRowForDelete($rowData, $rowNumber);
                break;
            case Import::BEHAVIOR_REPLACE:
                $this->_validateRowForReplace($rowData, $rowNumber);
                break;
            case Import::BEHAVIOR_APPEND:
                $this->_validateRowForUpdate($rowData, $rowNumber);
                break;
        }
        return !$this->getErrorAggregator()->isRowInvalid($rowNumber);
    }

    /**
     * Validate Row Data For Add/Update Behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    protected function _validateRowForUpdate(array $rowData, $rowNumber)
    {
        if (empty($rowData[self::COLUMN_ATTRIBUTE_CODE])) {
            $this->addRowError(self::ERROR_ATTRIBUTE_CODE_IS_EMPTY, $rowNumber);
        }

        $code = $rowData[self::COLUMN_ATTRIBUTE_CODE];
        $minLength = Attr::ATTRIBUTE_CODE_MIN_LENGTH;
        $maxLength = Attr::ATTRIBUTE_CODE_MAX_LENGTH;
        $isAllowedLength = Zend_Validate::is(
            trim($code),
            'StringLength',
            ['min' => $minLength, 'max' => $maxLength]
        );

        if (!preg_match("/^[a-zA-Z]+[a-zA-Z0-9_]+$/", $code)) {
            $errorMessage = __(
                'use (a-z or A-Z), (0-9), (_) in attribute_code field, and the first character should be a letter.'
            );
            $this->addRowError($errorMessage, $rowNumber);
        }

        if (!$isAllowedLength) {
            $errorMessage = __(
                'an attribute code must not be less than %1 and more than %2 characters.',
                $minLength,
                $maxLength
            );
            $this->addRowError($errorMessage, $rowNumber);
        }

        if (!isset($rowData[self::COLUMN_STORE_ID]) && !$this->isNeedStore($rowData)) {
            $this->addRowError(self::ERROR_STORE_ID_IS_EMPTY, $rowNumber);
        }

        $entityId = $this->_getExistEntityId($rowData);
        if (empty($rowData[self::COLUMN_ATTRIBUTE_SET]) && !$entityId) {
            $this->addRowError(self::ERROR_ATTRIBUTE_SET_IS_EMPTY, $rowNumber);
        }

        $fieldModel = [
            'backend_model',
            'source_model',
            'frontend_model',
            'attribute_model',
            'frontend_input_renderer'
        ];
        foreach ($fieldModel as $field) {
            if (!empty($rowData[$field]) && !class_exists($rowData[$field])) {
                $errorMessage = __(
                    '%1 model %2 not found. To import an attribute, you need to install the module %3.',
                    $field,
                    $rowData['frontend_input_renderer'],
                    $this->_getModuleName($rowData[$field])
                );
                $this->addRowError($errorMessage, $rowNumber);
            }
        }

        if (!empty($rowData['is_user_defined'])) {
            $info = $this->_eavSetup->getAttribute($this->_getEntityTypeId(), $code);
            $isSystem = isset($info['is_user_defined']) && $info['is_user_defined'] == '0';
            if ($isSystem) {
                $errorMessage = __(
                    'you can not change the is_user_defined field for the system attribute %1.',
                    $code
                );
                $this->addRowError($errorMessage, $rowNumber);
            }
        }

        if (is_numeric($rowData['attribute_code'])) {
            $errorMessage = __(
                '%1 attribute code is not correct. Please use only letters'.
                '(a-z or A-Z), numbers (0-9) or underscore (_) in this field,'.
                'and the first character should be a letter.',
                $rowData['attribute_code']
            );
            $this->addRowError($errorMessage, $rowNumber);
        }
    }

    /**
     * Retrieve module name
     *
     * @param string $model
     * @return string
     */
    protected function _getModuleName($model)
    {
        $module = [];
        $parts = explode('\\', $model);
        $count = 0;
        foreach ($parts as $part) {
            if ($part) {
                $module[] = $part;
                $count++;
            }
            if ($count > 1) {
                break;
            }
        }
        return implode('_', $module);
    }

    /**
     * Validate Row Data For Replace Behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    protected function _validateRowForReplace(array $rowData, $rowNumber)
    {
        $this->_validateRowForDelete($rowData, $rowNumber);
        if (!$this->getErrorAggregator()->isRowInvalid($rowNumber)) {
            $this->_validateRowForUpdate($rowData, $rowNumber);
        }
    }

    /**
     * Validate Row Data For Delete Behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _validateRowForDelete(array $rowData, $rowNumber)
    {
        if (empty($rowData[self::COLUMN_ATTRIBUTE_CODE])) {
            $this->addRowError(self::ERROR_ATTRIBUTE_CODE_IS_EMPTY, $rowNumber);
        }

        $code = $rowData[self::COLUMN_ATTRIBUTE_CODE];
        $behavior = ($this->getBehavior() == Import::BEHAVIOR_DELETE ||
            $this->getBehavior() == Import::BEHAVIOR_REPLACE);
        if (!in_array($code, $this->_alreadyDeleted) && $behavior) {
            $info = $this->_eavSetup->getAttribute($this->_getEntityTypeId(), $code);

            if (!$info) {
                $message = 'attribute with code %1 not found ... skipped ...';
                $this->addLogWriteln(__($message, $code), $this->output, 'info');
                $this->getErrorAggregator()->addRowToSkip($rowNumber);
                return;
            }

            if (!$info['is_user_defined'] || $code == 'cost') { // attribute cost is system
                $message = 'attribute %1 is a system attribute. You can not delete the system attribute.';
                $errorMessage = __($message, $code);
                $this->addRowError($errorMessage, $rowNumber);
            }

            if (empty($info['is_user_defined']) && $info['additional_data']) {
                $message = 'attribute %1 is used in swatch options. You can not delete this attribute.';
                $this->addLogWriteln(__($message . '... skipped ...', $code), $this->output, 'info');
                $errorMessage = __($message, $code);
                $this->addRowError($errorMessage, $rowNumber);
            }

            if ($this->_isUsedInConfigurable($rowData)) {
                $message = 'attribute %1 is used in configurable products. You can not delete this attribute.';
                $errorMessage = __($message, $code);
                $this->addRowError($errorMessage, $rowNumber);
            }
        }
    }

    /**
     * Is used in configurable
     *
     * @param array $rowData
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _isUsedInConfigurable($rowData)
    {
        $bind = ['attribute_id' => $this->_getExistEntityId($rowData)];
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $resource = $this->_resource;
        $table = $resource->getTableName('catalog_product_super_attribute');
        $productTable = $resource->getTableName('catalog_product_entity');

        $select->from(
            ['main_table' => $table],
            ['psa_count' => 'COUNT(product_super_attribute_id)']
        )->join(
            ['entity' => $productTable],
            'main_table.product_id = entity.entity_id'
        )->where('main_table.attribute_id = :attribute_id')
            ->group('main_table.attribute_id')
            ->limit(1);

        return (bool)$this->_connection->fetchOne($select, $bind);
    }

    /**
     * Is need store
     *
     * @param array $rowData
     * @return bool
     */
    public function isNeedStore($rowData)
    {
        foreach ($this->_storeDependentAttributes as $attribute) {
            if (isset($rowData[$attribute])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Is Empty Row
     *
     * @param array $rowData
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isEmptyRow($rowData)
    {
        if ($this->getBehavior() == Import::BEHAVIOR_DELETE) {
            return false;
        }
        /* check empty field */
        $empty = true;
        foreach ($this->getAllFields() as $field) {
            if (!empty($rowData[$field]) && $field != self::COLUMN_ENTITY_ID) {
                $empty = false;
                break;
            }
        }
        return $empty;
    }

    /**
     * Retrieve Entity Type Code
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return self::ENTITY_TYPE_CODE;
    }

    /**
     * Create new attribute set
     *
     * @param string $name
     *
     * @return integer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createNewSet($name)
    {
        /* @var $set \Magento\Eav\Model\Entity\Attribute\Set */
        $set = $this->attributeSetFactory->create();
        $set->unsetData('attribute_set_id');
        $time = explode(" ", microtime());
        $startTime = $time[0] + $time[1];

        $set->setAttributeSetName($name);
        $set->setEntityTypeId($this->_getEntityTypeId());
        $set->validate();
        $set->save();
        /* init from skeleton */
        $set->initFromSkeleton($this->_defaultSetId);
        $set->save();

        $time = explode(" ", microtime());
        $endTime = $time[0] + $time[1];
        $totalTime = $endTime - $startTime;
        $totalTime = round($totalTime, 5);
        $this->addLogWriteln(__('set with name: %1 .... %2s', $name, $totalTime), $this->output, 'info');

        /* update list */
        $this->initAttributeSet();
        return $set->getId();
    }

    /**
     * Retrieve Attribute id if entity is present in database
     *
     * @param array $rowData
     *
     * @return bool|int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getExistEntityId(array $rowData)
    {
        $bind = [
            ':attribute_code' => $rowData[self::COLUMN_ATTRIBUTE_CODE],
            ':entity_type_id' => $this->_getEntityTypeId()
        ];
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->getMainTable(), self::COLUMN_ENTITY_ID)
            ->where('attribute_code = :attribute_code')
            ->where('entity_type_id = :entity_type_id');

        return $this->_connection->fetchOne($select, $bind);
    }

    /**
     * Retrieve Attribute id if entity is present in database
     *
     * @param $attributeId
     * @param $storeId
     * @param $value
     * @return bool|int
     */
    protected function _getExistOptionId($attributeId, $storeId, $value)
    {
        $resource = $this->_resource;
        $optionTable = $resource->getTableName('eav_attribute_option');
        $valueTable = $resource->getTableName('eav_attribute_option_value');
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from(['o' => $optionTable], 'o.option_id')
            ->join(
                ['v' => $valueTable],
                'o.option_id = v.option_id',
                []
            )
            ->where('o.attribute_id = ?', $attributeId)
            ->where('v.store_id = ?', $storeId)
            ->where('v.value = ?', $value);

        return $this->_connection->fetchOne($select);
    }

    /**
     * Retrieve Attribute id if entity is present in database
     *
     * @param $attributeId
     * @param $storeId
     * @param $value
     * @return bool|int
     */
    protected function _getExistSwatchOptionId($attributeId, $storeId, $value)
    {
        $resource = $this->_resource;
        $optionTable = $resource->getTableName('eav_attribute_option');
        $valueTable = $resource->getTableName('eav_attribute_option_swatch');
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from(['o' => $optionTable], 'o.option_id')
            ->join(
                ['v' => $valueTable],
                'o.option_id = v.option_id',
                []
            )
            ->where('o.attribute_id = ?', $attributeId)
            ->where('v.store_id = ?', $storeId)
            ->where('v.value = ?', $value);

        return $this->_connection->fetchOne($select);
    }

    /**
     * Prepare Data For Update
     *
     * @param array $rowData
     *
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareDataForUpdate(array $rowData)
    {
        $entityId = $this->_getExistEntityId($rowData);
        $rowData[self::COLUMN_ENTITY_ID] = $entityId ?: null;
        if (isset($rowData['frontend_input']) && $rowData['frontend_input'] == 'multiselect') {
            $rowData['backend_model'] = \Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend::Class;
        }
        if (!empty($rowData[self::COLUMN_ATTRIBUTE_SET])) {
            $setName = trim($rowData[self::COLUMN_ATTRIBUTE_SET]);
            unset($rowData[self::COLUMN_ATTRIBUTE_SET]);
            $rowData['attribute_set_id'] = isset($this->_setList[$setName])
                ? $this->_setList[$setName]
                : $this->createNewSet($setName);
        } else {
            $rowData['attribute_set_id'] = null;
        }

        if (!empty($rowData[self::COLUMN_ATTRIBUTE_GROUP])) {
            $rowData['group'] = $rowData[self::COLUMN_ATTRIBUTE_GROUP];
            unset($rowData[self::COLUMN_ATTRIBUTE_GROUP]);
        }

        if (!empty($rowData[self::COLUMN_ATTRIBUTE_GROUP_SORT_ORDER])) {
            $rowData['sort_order'] = $rowData[self::COLUMN_ATTRIBUTE_GROUP_SORT_ORDER];
            unset($rowData[self::COLUMN_ATTRIBUTE_GROUP_SORT_ORDER]);
        }

        if (isset($rowData[self::COLUMN_ATTRIBUTE_OPTION])) {
            $optionId = 0;
            $storeId = 0;
            if (isset($rowData[self::COLUMN_STORE_ID])) {
                $storeId = $rowData[self::COLUMN_STORE_ID];
            }
            $value = trim($rowData[self::COLUMN_ATTRIBUTE_OPTION]);
            if ($entityId) {
                if (isset($rowData[self::COLUMN_ATTRIBUTE_BASE_OPTION]) && $storeId > 0) {
                    $baseValue = trim($rowData[self::COLUMN_ATTRIBUTE_BASE_OPTION]);
                    $optionId = $this->_getExistOptionId($entityId, 0, $baseValue);
                } else {
                    $optionId = $this->_getExistOptionId($entityId, $storeId, $value);
                }
            }

            $rowData['option'] = [];

            if ($optionId == 0) {
                $rowData['option']['value']['new'][$storeId] = $value;
            } else {
                $rowData['option']['value'][$optionId][$storeId] = $value;
            }

            unset($rowData[self::COLUMN_ATTRIBUTE_OPTION]);

            if (!empty($rowData[self::COLUMN_ATTRIBUTE_OPTION_SORT_ORDER])) {
                if ($optionId == 0) {
                    $rowData['option']['order']['new'] = $rowData[self::COLUMN_ATTRIBUTE_OPTION_SORT_ORDER];
                } else {
                    $rowData['option']['order'][$optionId] = $rowData[self::COLUMN_ATTRIBUTE_OPTION_SORT_ORDER];
                }
                unset($rowData[self::COLUMN_ATTRIBUTE_OPTION_SORT_ORDER]);
            }

            if (!empty($rowData[self::COLUMN_SWATCH_ATTRIBUTE_OPTION])) {
                if (!empty($rowData[self::COLUMN_SWATCH_ATTRIBUTE_TYPE])
                    && (int)$rowData[self::COLUMN_SWATCH_ATTRIBUTE_TYPE] === 2
                ) {
                    $valueSwatch = $this->uploadVisualSwatchImage(trim($rowData[self::COLUMN_SWATCH_ATTRIBUTE_OPTION]));
                } else {
                    $valueSwatch = trim($rowData[self::COLUMN_SWATCH_ATTRIBUTE_OPTION]);
                }
                if ($valueSwatch) {
                    $rowData['option']['swatch'] = $valueSwatch;
                    unset($rowData[self::COLUMN_SWATCH_ATTRIBUTE_OPTION]);
                }
            }
        }

        if (!empty($rowData[self::COLUMN_ATTRIBUTE_DELETE_VALUES])) {
            if ($this->getBehavior() == Import::BEHAVIOR_APPEND) {
                $valuesForDelete = array_map('trim', explode(',', $rowData[self::COLUMN_ATTRIBUTE_DELETE_VALUES]));
                $optionIdsForDelete = [];
                $valueIdsForDelete = [];

                foreach ($valuesForDelete as $valueForDelete) {
                    if ($entityId) {
                        $optionId = $this->_getExistOptionId($entityId, 0, $valueForDelete);
                        if (!empty($optionId)) {
                            $optionIdsForDelete[] = $optionId;
                        }
                        $valueId = $this->_getExistOptionValueId($entityId, $valueForDelete);
                        if (!empty($valueId)) {
                            $valueIdsForDelete[] = $valueId;
                        }
                    }
                }
                $rowData[self::COLUMN_ATTRIBUTE_DELETE_OPTIONS] = $optionIdsForDelete;
                $rowData[self::COLUMN_ATTRIBUTE_DELETE_VALUES] = $valueIdsForDelete;
            } else {
                $rowData[self::COLUMN_ATTRIBUTE_DELETE_OPTIONS] = [];
                $rowData[self::COLUMN_ATTRIBUTE_DELETE_VALUES] = [];
            }
        }

        if ($this->isNeedStore($rowData)) {
            foreach ($this->_storeDependentAttributes as $storeDependentAttribute) {
                if (isset($rowData[$storeDependentAttribute], $rowData[self::COLUMN_STORE_ID])
                    && $storeDependentAttribute === 'frontend_label'
                    && $rowData[self::COLUMN_STORE_ID] > 0
                ) {
                    $valueUpdate = [$rowData[self::COLUMN_STORE_ID] => $rowData[$storeDependentAttribute]];
                    $rowData['store_labels'] = $valueUpdate;
                    unset($rowData[$storeDependentAttribute]);
                }
            }
        }

        if (!isset($rowData['is_user_defined']) && !$entityId) {
            $rowData['is_user_defined'] = '1';
        }

        foreach ($rowData as $field => $value) {
            if ($value === '') {
                unset($rowData[$field]);
            }
        }
        return $rowData;
    }

    /**
     * Can be used to upload Image from platform interface
     *
     * @param string $swatchVisualFile
     *
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function uploadVisualSwatchImage(string $swatchVisualFile): string
    {
        return $this->uploadVisualSwatchFile($swatchVisualFile);
    }

    /**
     * @param string $swatchVisualFile
     *
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function uploadVisualSwatchFile(string $swatchVisualFile)
    {
        $config = $this->mediaConfig;
        $dirConfig = DirectoryList::getDefaultConfig();
        $mediaRelativePath = $dirConfig[DirectoryList::MEDIA][DirectoryList::PATH];
        $uploader = $this->_getUploader();
        if (preg_match('/\bhttps?:\/\//i', $swatchVisualFile, $matches)) {
            $tmpDir = $mediaRelativePath . DIRECTORY_SEPARATOR . $config->getBaseTmpMediaPath();
        } else {
            $tmpDir = $mediaRelativePath . DIRECTORY_SEPARATOR . $this->_mediaDirectory->getRelativePath('import');
        }
        if (!$uploader->setTmpDir($tmpDir)) {
            $uploader->createDirectory($tmpDir);
            $uploader->setTmpDir($this->_mediaDirectory->getAbsolutePath($tmpDir));
        }
        $newFile = '';
        try {
            $destDir = $uploader->getDestDir();
            $uploadDir = $mediaRelativePath . DIRECTORY_SEPARATOR . $config->getBaseTmpMediaPath();
            $uploadDir = $this->_mediaDirectory->getAbsolutePath($uploadDir);
            if (method_exists($uploader, 'isDirectoryWritable')
                && !$uploader->isDirectoryWritable($uploadDir)
            ) {
                $uploader->createDirectory($uploadDir);
            }
            if (!$uploader->setDestDir($uploadDir)) {
                $this->addRowError(
                    __('File directory \'%1\' is not writable.', $config->getBaseTmpMediaPath()),
                    null,
                    null,
                    null,
                    ProcessingError::ERROR_LEVEL_NOT_CRITICAL
                );
                throw new LocalizedException(
                    __('File directory \'%1\' is not writable.', $config->getBaseTmpMediaPath())
                );
            }
            $uploader->setFilesDispersion(true);
            $result = $uploader->move($swatchVisualFile);
            $newFile = $this->swatchHelperMedia->moveImageFromTmp($result['file']);
            $this->swatchHelperMedia->generateSwatchVariations($newFile);
            $uploader->setDestDir($destDir);
        } catch (Exception $e) {
            $this->addLogWriteln($e->getMessage(), $this->output, 'error');
        }

        return $newFile;
    }

    /**
     * @return \Firebear\ImportExport\Model\Import\Uploader
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function _getUploader()
    {
        $DS = DIRECTORY_SEPARATOR;
        if ($this->_fileUploader === null) {
            $this->_fileUploader = $this->_uploaderFactory->create();
            $this->_fileUploader->init();
            $dirConfig = DirectoryList::getDefaultConfig();
            $dirAddon = $dirConfig[DirectoryList::MEDIA][DirectoryList::PATH];
            $tmpPath = $dirAddon . $DS . $this->_mediaDirectory->getRelativePath('import');
            if (!$this->_fileUploader->setTmpDir($tmpPath)) {
                $this->addLogWriteln(
                    __('File directory \'%1\' is not readable.', $tmpPath),
                    $this->output,
                    'info'
                );
                $this->addRowError(
                    __('File directory \'%1\' is not readable.', $tmpPath),
                    null,
                    null,
                    null,
                    ProcessingError::ERROR_LEVEL_NOT_CRITICAL
                );
                throw new LocalizedException(
                    __('File directory \'%1\' is not readable.', $tmpPath)
                );
            }
            $destinationDir = "attribute/swatch";
            $destinationPath = $dirAddon . $DS . $this->_mediaDirectory->getRelativePath($destinationDir);

            $this->_mediaDirectory->create($destinationPath);
            if (!$this->_fileUploader->setDestDir($destinationPath)) {
                $this->addRowError(
                    __('File directory \'%1\' is not writable.', $destinationPath),
                    null,
                    null,
                    null,
                    ProcessingError::ERROR_LEVEL_NOT_CRITICAL
                );
                throw new LocalizedException(
                    __('File directory \'%1\' is not writable.', $destinationPath)
                );
            }
        }

        return $this->_fileUploader;
    }

    /**
     * Prepare Data For Replace
     *
     * @param array $rowData
     *
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareDataForReplace(array $rowData)
    {
        if (!empty($rowData['frontend_label'])) {
            $this->_deleteAttribute($rowData);
        }
        return $this->_prepareDataForUpdate($rowData);
    }

    /**
     * Prepare Entity Field Values
     *
     * @param array $entityRow
     * @param array $rowData
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareEntityRow(array $entityRow, array $rowData)
    {
        $keys = array_keys($entityRow);
        foreach ($this->getAllFields() as $field) {
            if (!in_array($field, $keys) && isset($rowData[$field])) {
                $entityRow[$field] = $rowData[$field];
            }
        }
        return $entityRow;
    }

    /**
     * Retrieve id for delete
     *
     * @param array $rowData
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getIdForDelete(array $rowData)
    {
        return $this->_getExistEntityId($rowData);
    }

    /**
     * Retrieve set id for attribute
     *
     * @param integer $attributeId
     * @return integer
     */
    protected function _getSetId($attributeId)
    {
        $select = $this->_connection->select();
        $select->from(
            ['e' => $this->_resource->getTableName('eav_entity_attribute')],
            ['e.attribute_set_id']
        )->where(
            'e.attribute_id = ?',
            $attributeId
        );
        return $this->_connection->fetchOne($select);
    }

    /**
     * Save Validated Bunches
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    protected function _saveValidatedBunches()
    {
        $source = $this->_getSource();
        $currentDataSize = 0;
        $bunchRows = [];
        $startNewBunch = false;
        $nextRowBackup = [];
        $maxDataSize = $this->_resourceHelper->getMaxDataSize();
        $bunchSize = $this->_importExportData->getBunchSize();

        $source->rewind();
        $this->_dataSourceModel->cleanBunches();
        $file = null;
        $jobId = null;
        if (isset($this->_parameters['file'])) {
            $file = $this->_parameters['file'];
        }
        if (isset($this->_parameters['job_id'])) {
            $jobId = $this->_parameters['job_id'];
        }

        while ($source->valid() || $bunchRows) {
            if ($startNewBunch || !$source->valid()) {
                $this->_dataSourceModel->saveBunches(
                    $this->getEntityTypeCode(),
                    $this->getBehavior(),
                    $jobId,
                    $file,
                    $bunchRows
                );
                $bunchRows = $nextRowBackup;
                $currentDataSize = strlen($this->phpSerialize($bunchRows));
                $startNewBunch = false;
                $nextRowBackup = [];
            }

            if ($source->valid()) {
                try {
                    $rowData = $source->current();
                } catch (InvalidArgumentException $e) {
                    $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                    $this->_processedRowsCount++;
                    $source->next();
                    continue;
                }
                $rowData = $this->customBunchesData($rowData);
                $this->_processedRowsCount++;
                if ($this->validateRow($rowData, $source->key())) {
                    $rowSize = strlen($this->jsonHelper->jsonEncode($rowData));

                    $isBunchSizeExceeded = $bunchSize > 0 && count($bunchRows) >= $bunchSize;

                    if ($currentDataSize + $rowSize >= $maxDataSize || $isBunchSizeExceeded) {
                        $startNewBunch = true;
                        $nextRowBackup = [$source->key() => $rowData];
                    } else {
                        $bunchRows[$source->key()] = $rowData;
                        $currentDataSize += $rowSize;
                    }
                }
                $source->next();
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public static function getAdditionalColumns()
    {
        $additionalColumns = [
            self::COLUMN_STORE_ID,
            self::COLUMN_ATTRIBUTE_SET,
            self::COLUMN_ATTRIBUTE_GROUP,
            self::COLUMN_ATTRIBUTE_GROUP_SORT_ORDER,
            self::COLUMN_ATTRIBUTE_BASE_OPTION,
            self::COLUMN_ATTRIBUTE_OPTION,
            self::COLUMN_ATTRIBUTE_OPTION_SORT_ORDER,
            self::COLUMN_SWATCH_ATTRIBUTE_TYPE,
            self::COLUMN_SWATCH_ATTRIBUTE_OPTION
        ];
        return $additionalColumns;
    }

    /**
     * Retrieve Option value id if it is present in database
     *
     * @param $attributeId
     * @param $value
     * @return mixed
     */
    protected function _getExistOptionValueId($attributeId, $value)
    {
        $resource = $this->_resource;
        $optionTable = $resource->getTableName('eav_attribute_option');
        $valueTable = $resource->getTableName('eav_attribute_option_value');
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from(['o' => $optionTable], 'v.value_id')
            ->join(
                ['v' => $valueTable],
                'o.option_id = v.option_id',
                []
            )
            ->where('o.attribute_id = ?', $attributeId)
            ->where('v.value = ?', $value);
        return $this->_connection->fetchOne($select);
    }
}
