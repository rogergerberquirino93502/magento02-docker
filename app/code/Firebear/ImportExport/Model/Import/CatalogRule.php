<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import;

use Firebear\ImportExport\Traits\Import\Entity as ImportTrait;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\CatalogRule\Model\Rule\Action\SimpleActionOptionsProvider;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\AbstractEntity;
use Magento\ImportExport\Model\ImportFactory;
use Magento\Rule\Model\ConditionFactory;

/**
 * CatalogRule import
 */
class CatalogRule extends AbstractEntity implements ImportAdapterInterface
{
    use ImportTrait;

    /**
     * Rule id column name
     */
    const COLUMN_RULE_ID = 'rule_id';

    /**
     * Rule factory
     *
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * Condition factory
     *
     * @var ConditionFactory
     */
    protected $conditionFactory;

    /**
     * Json serializer
     *
     * @var Serializer
     */
    protected $serializer;

    /**
     * Simple Action Options Provider
     *
     * @var SimpleActionOptionsProvider
     */
    protected $simpleActionProvider;

    /**
     * Simple Action Options
     *
     * @var string[]
     */
    protected $simpleAction = [];

    /**
     * Source model
     *
     * @var \Magento\ImportExport\Model\ResourceModel\Helper
     */
    protected $resourceHelper;

    /**
     * Import export data
     *
     * @var \Magento\ImportExport\Helper\Data
     */
    protected $importExportData;

    protected $_permanentAttributes = [
        'name',
        'website_ids',
        'customer_group_ids',
        'discount_amount'
    ];

    /**
     * Field list
     *
     * @var array
     */
    protected $fields = [
        self::COLUMN_RULE_ID,
        'name',
        'description',
        'website_ids',
        'customer_group_ids',
        'from_date',
        'to_date',
        'stop_rules_processing',
        'simple_action',
        'discount_amount',
        'sort_order',
        'is_active'
    ];

    /**
     * Error codes
     */
    const ERROR_RULE_ID_IS_EMPTY = 'ruleIdIsEmpty';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_RULE_ID_IS_EMPTY => 'Rule id is empty',
    ];

    /**
     * Initialize import
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param ImportFactory $importFactory
     * @param RuleFactory $ruleFactory
     * @param ConditionFactory $conditionFactory
     * @param Serializer $serializer
     * @param SimpleActionOptionsProvider $simpleActionProvider
     * @param array $data
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        ImportFactory $importFactory,
        RuleFactory $ruleFactory,
        ConditionFactory $conditionFactory,
        Serializer $serializer,
        SimpleActionOptionsProvider $simpleActionProvider,
        array $data = []
    ) {
        $this->_logger = $context->getLogger();
        $this->output = $context->getOutput();
        $this->importExportData = $context->getImportExportData();
        $this->resourceHelper = $context->getResourceHelper();
        $this->jsonHelper = $context->getJsonHelper();
        $this->ruleFactory = $ruleFactory;
        $this->conditionFactory = $conditionFactory;
        $this->serializer = $serializer;
        $this->simpleActionProvider = $simpleActionProvider;

        parent::__construct(
            $context->getStringUtils(),
            $scopeConfig,
            $importFactory,
            $context->getResourceHelper(),
            $context->getResource(),
            $context->getErrorAggregator(),
            $data
        );

        $this->prepareSimpleAction();
    }

    /**
     * Import data rows
     *
     * @return boolean
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
                        $this->delete($rowData);
                        break;
                    case Import::BEHAVIOR_REPLACE:
                        $this->delete($rowData);
                        $this->save($rowData);
                        break;
                    case Import::BEHAVIOR_ADD_UPDATE:
                        $this->save($rowData);
                        break;
                }
            }
        }
        return true;
    }

    /**
     * Imported entity type code getter
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'catalog_rule';
    }

    /**
     * Retrieve All Fields Source
     *
     * @return array
     */
    public function getAllFields()
    {
        return $this->fields;
    }

    /**
     * Validate data row
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return bool
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
                $this->validateRowForDelete($rowData, $rowNumber);
                break;
            case Import::BEHAVIOR_REPLACE:
                $this->validateRowForReplace($rowData, $rowNumber);
                break;
            case Import::BEHAVIOR_ADD_UPDATE:
                $this->validateRowForUpdate($rowData, $rowNumber);
                break;
        }
        return !$this->getErrorAggregator()->isRowInvalid($rowNumber);
    }

    /**
     * Validate row data for replace behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    public function validateRowForReplace(array $rowData, $rowNumber)
    {
        $this->validateRowForDelete($rowData, $rowNumber);
        $this->validateRowForUpdate($rowData, $rowNumber);
    }

    /**
     * Validate row data for delete behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    public function validateRowForDelete(array $rowData, $rowNumber)
    {
        if (empty($rowData[self::COLUMN_RULE_ID])) {
            $this->addRowError(self::ERROR_RULE_ID_IS_EMPTY, $rowNumber);
        }
    }

    /**
     * Validate row data for update behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    public function validateRowForUpdate(array $rowData, $rowNumber)
    {
        if (!empty($rowData['conditions_serialized'])) {
            $conditions = $this->serializer->unserialize($rowData['conditions_serialized']);
            if (is_array($conditions)) {
                $this->validateConditions($conditions, $rowNumber);
            } else {
                $errorMessage = __('invalid conditions serialized.');
                $this->addRowError($errorMessage, $rowNumber);
            }
        }

        if (!empty($rowData['simple_action'])) {
            if (!in_array($rowData['simple_action'], $this->simpleAction)) {
                $errorMessage = __(
                    'unsupported simple action: %1.',
                    $rowData['simple_action']
                );
                $this->addRowError($errorMessage, $rowNumber);
            }
        }
    }

    /**
     * Prepare Simple Action Options
     *
     * @return void
     */
    protected function prepareSimpleAction()
    {
        $options = $this->simpleActionProvider->toOptionArray();
        foreach ($options as $option) {
            $this->simpleAction[] = $option['value'];
        }
    }

    /**
     * Validate row conditions
     *
     * @param array $conditions
     * @param int $rowNumber
     * @return void
     */
    protected function validateConditions(array $conditions, $rowNumber)
    {
        if (!empty($conditions['type'])) {
            if ($this->validateModel($conditions['type'], $rowNumber)) {
                if (!empty($conditions['attribute'])) {
                    $this->validateAttribute($conditions, $rowNumber);
                }
            }
        } else {
            $errorMessage = __('conditions type is empty.');
            $this->addRowError($errorMessage, $rowNumber);
        }

        if (!empty($conditions['conditions']) && is_array($conditions['conditions'])) {
            foreach ($conditions['conditions'] as $condition) {
                $this->validateConditions($condition, $rowNumber);
            }
        }
    }

    /**
     * Validate conditions attribute
     *
     * @param array $conditions
     * @param int $rowNumber
     * @return void
     */
    protected function validateAttribute($conditions, $rowNumber)
    {
        try {
            $condition = $this->conditionFactory->create($conditions['type']);
            $attributes = $condition->loadAttributeOptions()->getAttributeOption();

            if (!isset($attributes[$conditions['attribute']])) {
                $errorMessage = __('the attribute %1 is not exist.', $conditions['attribute']);
                $this->addRowError($errorMessage, $rowNumber);
            } else {
                $condition->setAttribute($conditions['attribute']);
                if (in_array($condition->getInputType(), ['select', 'multiselect'])) {
                    $condition->unsetData('value_select_options');
                    $condition->unsetData('value_option');

                    $errorOptions = [];
                    $options = $condition->getValueOption();
                    $values = is_array($conditions['value'])
                        ? $conditions['value']
                        : [$conditions['value']];

                    foreach ($values as $value) {
                        if (!in_array($value, $options)) {
                            $errorOptions[] = $value;
                        }
                    }

                    if (0 < count($errorOptions)) {
                        $errorMessage = __(
                            'the option(s) %1 of attribute %2 is not exist.',
                            implode(',', $errorOptions),
                            $conditions['attribute']
                        );
                        $this->addRowError($errorMessage, $rowNumber);
                    }
                }
            }
        } catch (\Exception $e) {
            $errorMessage = __('invalid model %1.', $conditions['type']);
            $this->addRowError($errorMessage, $rowNumber);
        }
    }

    /**
     * Validate conditions model
     *
     * @param string $model
     * @param int $rowNumber
     * @return bool
     */
    protected function validateModel($model, $rowNumber)
    {
        if (!class_exists($model)) {
            $errorMessage = __(
                'conditions type %1 not found. To import an rule, you need to install the module %2.',
                $model,
                $this->getModuleName($model)
            );
            $this->addRowError($errorMessage, $rowNumber);

            return false;
        }
        return true;
    }

    /**
     * Retrieve module name
     *
     * @param string $model
     * @return string
     */
    protected function getModuleName($model)
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
     * Delete row
     *
     * @param array $rowData
     * @return $this
     */
    protected function delete(array $rowData)
    {
        $rule = $this->ruleFactory->create();
        $rule->load($rowData[self::COLUMN_RULE_ID]);

        if ($rule->getId()) {
            $rule->delete();
            $this->countItemsDeleted++;
        }
        return $this;
    }

    /**
     * Update entity
     *
     * @param array $rowData
     * @return $this
     */
    protected function save(array $rowData)
    {
        $rule = $this->ruleFactory->create();
        if (!empty($rowData[self::COLUMN_RULE_ID])) {
            $rule->load($rowData[self::COLUMN_RULE_ID]);
        }

        if ($rule->getId()) {
            $this->countItemsUpdated++;
        } else {
            $this->countItemsCreated++;
            $rowData[self::COLUMN_RULE_ID] = null;
        }

        $conditions = $this->serializer->unserialize(
            $rowData['conditions_serialized']
        );

        foreach ($rowData as $field => $value) {
            if (!in_array($field, $this->fields)) {
                unset($rowData[$field]);
            }
        }

        $rule->addData($rowData);
        $rule->setConditions(null);
        $rule->setConditionsSerialized(
            $this->serializer->serialize(
                $this->prepareConditions($conditions)
            )
        );

        $rule->save();

        return $this;
    }

    /**
     * Prepare row conditions
     *
     * @param array $conditions
     * @return array
     */
    protected function prepareConditions(array $conditions)
    {
        if (!empty($conditions['type']) && !empty($conditions['attribute'])) {
            $conditions['value'] = $this->prepareAttributeValue($conditions);
        }

        if (!empty($conditions['conditions']) && is_array($conditions['conditions'])) {
            foreach ($conditions['conditions'] as $key => $condition) {
                $conditions['conditions'][$key] = $this->prepareConditions($condition);
            }
        }
        return $conditions;
    }

    /**
     * Prepare conditions attribute value
     *
     * @param array $conditions
     * @return string|array
     */
    protected function prepareAttributeValue($conditions)
    {
        $condition = $this->conditionFactory->create($conditions['type']);
        $attributes = $condition->loadAttributeOptions()->getAttributeOption();

        if (isset($attributes[$conditions['attribute']])) {
            $condition->setAttribute($conditions['attribute']);
            if (in_array($condition->getInputType(), ['select', 'multiselect'])) {
                // reload options flag
                $condition->unsetData('value_select_options');
                $condition->unsetData('value_option');

                $options = $condition->getValueOption();
                if (is_array($conditions['value'])) {
                    foreach ($conditions['value'] as $key => $value) {
                        $optionId = array_search($value, $options);
                        $conditions['value'][$key] = $optionId;
                    }
                } else {
                    $optionId = array_search($conditions['value'], $options);
                    $conditions['value'] = $optionId;
                }
            }
        }
        return $conditions['value'];
    }

    /**
     * Inner source object getter
     *
     * @return \Magento\ImportExport\Model\Import\AbstractSource
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
     * Save Validated Bunches
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _saveValidatedBunches()
    {
        $source = $this->_getSource();
        $currentDataSize = 0;
        $bunchRows = [];
        $startNewBunch = false;
        $nextRowBackup = [];
        $maxDataSize = $this->resourceHelper->getMaxDataSize();
        $bunchSize = $this->importExportData->getBunchSize();

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
                $currentDataSize = strlen($this->jsonHelper->jsonEncode($bunchRows));
                $startNewBunch = false;
                $nextRowBackup = [];
            }

            if ($source->valid()) {
                try {
                    $rowData = $source->current();
                } catch (\InvalidArgumentException $e) {
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
}
