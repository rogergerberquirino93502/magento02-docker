<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product\Price;

use Magento\Catalog\Model\Product;
use Magento\CatalogRule\Api\Data\RuleInterface;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\DataObject\IdentityInterface;

/**
 * Catalog Rule data model
 *
 * @method \Magento\CatalogRule\Model\Rule setFromDate(string $value)
 * @method \Magento\CatalogRule\Model\Rule setToDate(string $value)
 * @method \Magento\CatalogRule\Model\Rule setCustomerGroupIds(string $value)
 * @method string getWebsiteIds()
 * @method \Magento\CatalogRule\Model\Rule setWebsiteIds(string $value)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class Rule extends \Magento\Rule\Model\AbstractModel implements RuleInterface, IdentityInterface
{
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'import_price_rule';

    /**
     * Parameter name in event
     *
     * In observe method you can use $observer->getEvent()->getRule() in this case
     *
     * @var string
     */
    protected $_eventObject = 'price_rule';

    /**
     * Store matched product Ids
     *
     * @var array
     */
    protected $_productIds;

    /**
     * Limitation for products collection
     *
     * @var int|array|null
     */
    protected $_productsFilter = null;

    /**
     * Store current date at "Y-m-d H:i:s" format
     *
     * @var string
     */
    protected $_now;

    /**
     * Cached data of prices calculated by price rules
     *
     * @var array
     */
    protected static $priceRulesData = [];

    /**
     * Catalog rule data
     *
     * @var \Magento\CatalogRule\Helper\Data
     */
    protected $_catalogRuleData;

    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    protected $_cacheTypesList;

    /**
     * @var array
     */
    protected $_relatedCacheTypes;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Iterator
     */
    protected $_resourceIterator;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\CatalogRule\Model\Rule\Condition\CombineFactory
     */
    protected $_combineFactory;

    /**
     * @var \Magento\CatalogRule\Model\Rule\Action\CollectionFactory
     */
    protected $_actionCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var \Magento\CatalogRule\Model\Indexer\Rule\RuleProductProcessor;
     */
    protected $_ruleProductProcessor;

    /**
     * @var \Magento\CatalogRule\Model\Data\Condition\Converter
     */
    protected $ruleConditionConverter;

    /**
     * Rule constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param Rule\CombineFactory $combineFactory
     * @param \Magento\CatalogRule\Model\Rule\Action\CollectionFactory $actionCollectionFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Framework\Model\ResourceModel\Iterator $resourceIterator
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\CatalogRule\Helper\Data $catalogRuleData
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypesList
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\CatalogRule\Model\Indexer\Rule\RuleProductProcessor $ruleProductProcessor
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $relatedCacheTypes
     * @param array $data
     * @param ExtensionAttributesFactory|null $extensionFactory
     * @param AttributeValueFactory|null $customAttributeFactory
     * @param \Magento\Framework\Serialize\Serializer\Json|null $serializer
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Firebear\ImportExport\Model\Import\Product\Price\Rule\CombineFactory $combineFactory,
        \Magento\CatalogRule\Model\Rule\Action\CollectionFactory $actionCollectionFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Model\ResourceModel\Iterator $resourceIterator,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\CatalogRule\Helper\Data $catalogRuleData,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypesList,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\CatalogRule\Model\Indexer\Rule\RuleProductProcessor $ruleProductProcessor,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $relatedCacheTypes = [],
        array $data = [],
        ExtensionAttributesFactory $extensionFactory = null,
        AttributeValueFactory $customAttributeFactory = null,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->_combineFactory = $combineFactory;
        $this->_actionCollectionFactory = $actionCollectionFactory;
        $this->_productFactory = $productFactory;
        $this->_resourceIterator = $resourceIterator;
        $this->_customerSession = $customerSession;
        $this->_catalogRuleData = $catalogRuleData;
        $this->_cacheTypesList = $cacheTypesList;
        $this->_relatedCacheTypes = $relatedCacheTypes;
        $this->dateTime = $dateTime;
        $this->_ruleProductProcessor = $ruleProductProcessor;

        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $localeDate,
            $resource,
            $resourceCollection,
            $data,
            $extensionFactory,
            $customAttributeFactory,
            $serializer
        );
    }

    /**
     * Init resource model and id field
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setIdFieldName('rule_id');
    }

    /**
     * Getter for rule conditions collection
     *
     * @return \Magento\Rule\Model\Condition\Combine
     */
    public function getConditionsInstance()
    {
        return $this->_combineFactory->create();
    }

    /**
     * Getter for rule actions collection
     *
     * @return \Magento\CatalogRule\Model\Rule\Action\Collection
     */
    public function getActionsInstance()
    {
        return $this->_actionCollectionFactory->create();
    }

    /**
     * Get catalog rule customer group Ids
     *
     * @return array|null
     */
    public function getCustomerGroupIds()
    {
        if (!$this->hasCustomerGroupIds()) {
            $customerGroupIds = $this->_getResource()->getCustomerGroupIds($this->getId());
            $this->setData('customer_group_ids', (array)$customerGroupIds);
        }
        return $this->_getData('customer_group_ids');
    }

    /**
     * Retrieve current date for current rule
     *
     * @return string
     */
    public function getNow()
    {
        if (!$this->_now) {
            return (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        }
        return $this->_now;
    }

    /**
     * Set current date for current rule
     *
     * @param string $now
     * @return void
     * @codeCoverageIgnore
     */
    public function setNow($now)
    {
        $this->_now = $now;
    }

    /**
     * Get array of product ids which are matched by rule
     *
     * @return array
     */
    public function getMatchingProductIds()
    {
        if ($this->_productIds === null) {
            $this->_productIds = [];
            $this->setCollectedAttributes([]);

            if ($this->getWebsiteIds()) {
                /** @var $productCollection \Magento\Catalog\Model\ResourceModel\Product\Collection */
                $productCollection = $this->_productCollectionFactory->create();
                $productCollection->addWebsiteFilter($this->getWebsiteIds());
                if ($this->_productsFilter) {
                    $productCollection->addIdFilter($this->_productsFilter);
                }
                $this->getConditions()->collectValidatedAttributes($productCollection);
                $this->_resourceIterator->walk(
                    $productCollection->getSelect(),
                    [[$this, 'callbackValidateProduct']],
                    [
                        'attributes' => $this->getCollectedAttributes(),
                        'product' => $this->_productFactory->create()
                    ]
                );
            }
        }

        return $this->_productIds;
    }

    /**
     * Callback function for product matching
     *
     * @param array $args
     * @return void
     */
    public function callbackValidateProduct($args)
    {
        $product = clone $args['product'];
        $product->setData($args['row']);

        $websites = $this->_getWebsitesMap();
        $results = [];

        foreach ($websites as $websiteId => $defaultStoreId) {
            $product->setStoreId($defaultStoreId);
            $results[$websiteId] = $this->getConditions()->validate($product);
        }
        $this->_productIds[$product->getId()] = $results;
    }

    /**
     * Prepare website map
     *
     * @return array
     */
    protected function _getWebsitesMap()
    {
        $map = [];
        $websites = $this->_storeManager->getWebsites();
        foreach ($websites as $website) {
            // Continue if website has no store to be able to create catalog rule for website without store
            if ($website->getDefaultStore() === null) {
                continue;
            }
            $map[$website->getId()] = $website->getDefaultStore()->getId();
        }
        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function validateData(\Magento\Framework\DataObject $object)
    {
        $result = parent::validateData($object);
        if ($result === true) {
            $result = [];
        }
        $action = $object->getData('simple_action');
        $discount = $object->getData('discount_amount');
        $result = array_merge(
            $result,
            $this->validateDiscount($action, $discount)
        );

        return !empty($result) ? $result : true;
    }

    /**
     * Validate discount based on action
     *
     * @param string $action
     * @param string|int|float $discount
     *
     * @return array Validation errors
     */
    protected function validateDiscount($type, $discount)
    {
        $resultArray = [];
        switch ($type) {
            case 'by_fixed':
            case 'to_fixed':
                if ($discount < 0) {
                    $resultArray[] = __('Discount value should be 0 or greater.');
                }
                break;
            case 'by_percent':
            case 'to_percent':
                if ($discount < 0 || $discount > 100) {
                    $resultArray[] = __('Percentage discount should be between 0 and 100.');
                }
                break;
            case 'by_fixed':
            case 'to_fixed':
                if ($discount < 0) {
                    $resultArray[] = __('Discount value should be 0 or greater.');
                }
                break;
            default:
                $resultArray[] = __('Unknown action.');
        }
        return $resultArray;
    }

    /**
     * Calculate price using catalog price rule of product
     *
     * @param Product $product
     * @param float $price
     * @return float|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function calcProductPriceRule(Product $product, $price)
    {
        $priceRules = null;
        $pId = $product->getId();
        $storeId = $product->getStoreId();
        $websiteId = $this->_storeManager
            ->getStore($storeId)
            ->getWebsiteId();
        if ($product->hasCustomerGroupId()) {
            $groupId = $product->getCustomerGroupId();
        } else {
            $groupId = $this->_customerSession->getCustomerGroupId();
        }
        $dateTs = $this->_localeDate
            ->scopeTimeStamp($storeId);
        $cacheKey = date('Y-m-d', $dateTs)
            . "|{$websiteId}|{$groupId}|{$pId}|{$price}";

        if (!array_key_exists($cacheKey, self::$priceRulesData)) {
            $rulesDataArray = $this->_getRulesFromProduct(
                $dateTs,
                $websiteId,
                $groupId,
                $pId
            );
            if ($rulesDataArray) {
                foreach ($rulesDataArray as $ruleData) {
                    if ($product->getParentId()) {
                        $priceRules = $priceRules ? $priceRules : $price;
                        if ($ruleData['action_stop']) {
                            break;
                        }
                    } else {
                        $priceRules = $this->_catalogRuleData
                            ->calcPriceRule(
                                $ruleData['action_operator'],
                                $ruleData['action_amount'],
                                $priceRules ? $priceRules : $price
                            );
                        if ($ruleData['action_stop']) {
                            break;
                        }
                    }
                }
                return self::$priceRulesData[$cacheKey] = $priceRules;
            } else {
                self::$priceRulesData[$cacheKey] = null;
            }
        } else {
            return self::$priceRulesData[$cacheKey];
        }
        return null;
    }

    /**
     * Get rules from product
     *
     * @param string $dateTs
     * @param int $websiteId
     * @param array $customerGroupId
     * @param int $productId
     * @return array
     */
    protected function _getRulesFromProduct($dateTs, $websiteId, $customerGroupId, $productId)
    {
        return $this->_getResource()->getRulesFromProduct($dateTs, $websiteId, $customerGroupId, $productId);
    }

    /**
     * Filtering products that must be checked for matching with rule
     *
     * @param  int|array $productIds
     * @return void
     * @codeCoverageIgnore
     */
    public function setProductsFilter($ids)
    {
        $this->_productsFilter = $ids;
    }

    /**
     * Returns products filter
     *
     * @return array|int|null
     * @codeCoverageIgnore
     */
    public function getProductsFilter()
    {
        return $this->_productsFilter;
    }

    /**
     * Invalidate related cache types
     *
     * @return $this
     */
    protected function _invalidateCache()
    {
        if (count($this->_relatedCacheTypes)) {
            $this->_cacheTypesList
                ->invalidate($this->_relatedCacheTypes);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    public function afterSave()
    {
        if ($this->isObjectNew()) {
            $this->getMatchingProductIds();
            if (!empty($this->_productIds) && is_array($this->_productIds)) {
                $this->_getResource()->addCommitCallback([$this, 'reindex']);
            }
        } else {
            $this->_ruleProductProcessor
                ->getIndexer()
                ->invalidate();
        }
        return parent::afterSave();
    }

    /**
     * Init indexing process after rule save
     *
     * @return void
     */
    public function reindex()
    {
        $this->_ruleProductProcessor
            ->reindexList($this->_productIds);
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    public function afterDelete()
    {
        $this->_ruleProductProcessor
            ->getIndexer()
            ->invalidate();
        return parent::afterDelete();
    }

    /**
     * Check if rule behavior changed
     *
     * @return bool
     */
    public function isRuleBehaviorChanged()
    {
        if (!$this->isObjectNew()) {
            $arrayDiff = $this->dataDiff(
                $this->getOrigData(),
                $this->getStoredData()
            );
            unset($arrayDiff['name']);
            unset($arrayDiff['description']);
            if (empty($arrayDiff)) {
                return false;
            }
        }
        return true;
    }

    // @codeCoverageIgnoreStart

    /**
     * {@inheritdoc}
     */
    public function getRuleId()
    {
        return $this->getData(self::RULE_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * {@inheritdoc}
     */
    public function getIsActive()
    {
        return $this->getData(self::IS_ACTIVE);
    }

    /**
     * {@inheritdoc}
     */
    public function getSimpleAction()
    {
        return $this->getData(self::SIMPLE_ACTION);
    }

    /**
     * {@inheritdoc}
     */
    public function getDiscountAmount()
    {
        return $this->getData(self::DISCOUNT_AMOUNT);
    }

    /**
     * @return string
     */
    public function getFromDate()
    {
        return $this->getData('from_date');
    }

    /**
     * {@inheritdoc}
     *
     * @return \Magento\CatalogRule\Api\Data\RuleExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Get array with data differences
     * @param array $array1
     * @param array $array2
     *
     * @return array
     */
    protected function dataDiff($arr1, $arr2)
    {
        $result = [];
        foreach ($arr1 as $key => $value) {
            if (array_key_exists($key, $arr2)) {
                if ($value != $arr2[$key]) {
                    $result[$key] = true;
                }
            } else {
                $result[$key] = true;
            }
        }
        return $result;
    }

    /**
     * @param string $formName
     * @return string
     */
    public function getConditionsFieldSetId($formName = '')
    {
        return $formName
            . 'rule_conditions_fieldset_'
            . $this->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function setRuleId($id)
    {
        return $this->setData(
            self::RULE_ID,
            $id
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        return $this->setData(
            self::NAME,
            $name
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDescription($description)
    {
        return $this->setData(
            self::DESCRIPTION,
            $description
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setIsActive($flag)
    {
        return $this->setData(
            self::IS_ACTIVE,
            $flag
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRuleCondition()
    {
        return $this->getRuleConditionConverter()
            ->arrayToDataModel(
                $this->getConditions()->asArray()
            );
    }

    /**
     * {@inheritdoc}
     */
    public function setRuleCondition($condition)
    {
        $dataCondition = $this->getRuleConditionConverter()->dataModelToArray($condition);
        $this->getConditions()
            ->setConditions([])
            ->loadArray(
                $dataCondition
            );
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getStopRulesProcessing()
    {
        return $this->getData(self::STOP_RULES_PROCESSING);
    }

    /**
     * {@inheritdoc}
     */
    public function setStopRulesProcessing($flag)
    {
        return $this->setData(
            self::STOP_RULES_PROCESSING,
            $flag
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrder()
    {
        return $this->getData(self::SORT_ORDER);
    }

    /**
     * {@inheritdoc}
     */
    public function setSortOrder($order)
    {
        return $this->setData(self::SORT_ORDER, $order);
    }

    /**
     * {@inheritdoc}
     */
    public function setSimpleAction($action)
    {
        return $this->setData(
            self::SIMPLE_ACTION,
            $action
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDiscountAmount($amount)
    {
        return $this->setData(
            self::DISCOUNT_AMOUNT,
            $amount
        );
    }

    /**
     * @return string
     */
    public function getToDate()
    {
        return $this->getData('to_date');
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\CatalogRule\Api\Data\RuleExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Magento\CatalogRule\Api\Data\RuleExtensionInterface $attributes)
    {
        return $this->_setExtensionAttributes($attributes);
    }

    /**
     * @return \Magento\CatalogRule\Model\Data\Condition\Converter
     * @deprecated 100.1.0
     */
    private function getRuleConditionConverter()
    {
        if (null === $this->ruleConditionConverter) {
            $this->ruleConditionConverter =
                \Magento\Framework\App\ObjectManager::getInstance()
                ->get(
                    \Magento\CatalogRule\Model\Data\Condition\Converter::class
                );
        }
        return $this->ruleConditionConverter;
    }

    // @codeCoverageIgnoreEnd

    /**
     * @inheritDoc
     */
    public function getIdentities()
    {
        return ['price'];
    }
}
