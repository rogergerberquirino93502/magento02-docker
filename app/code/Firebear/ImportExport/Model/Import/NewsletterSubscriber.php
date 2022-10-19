<?php
/**
 * Copyright (c) 2019. All rights reserved.
 */
namespace Firebear\ImportExport\Model\Import;

use Firebear\ImportExport\Traits\Import\Entity as ImportTrait;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\ImportExport\Model\Import\AbstractEntity;
use Magento\ImportExport\Model\ImportFactory;
use Magento\ImportExport\Model\Import;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Newsletter\Model\Subscriber;

/**
 * Newsletter Subscriber Import
 */
class NewsletterSubscriber extends AbstractEntity implements ImportAdapterInterface
{
    use ImportTrait;

    /**
     * Subscriber id column name
     */
    const COL_SUBSCRIBER_ID = 'subscriber_id';

    /**
     * Subscriber email column name
     */
    const COL_SUBSCRIBER_EMAIL = 'subscriber_email';

    /**
     * Store id column name
     */
    const COL_STORE_ID = 'store_id';

    /**
     * Subscriber status column name
     */
    const COL_STATUS = 'subscriber_status';

    /**
     * Customer id column name
     */
    const COL_CUSTOMER_ID = 'customer_id';

    /**
     * Password hash column name
     */
    const COL_PASSWORD_HASH = 'password_hash';

    /**
     * Error codes
     */
    const ERROR_SUBSCRIBER_ID_IS_EMPTY = 'subscriberIdIsEmpty';
    const ERROR_STORE_ID_IS_EMPTY = 'storeIdIsEmpty';
    const ERROR_EMAIL_FORMAT = 'emailFormatInvalid';
    const ERROR_STATUS_VALUE = 'statusInvalid';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_SUBSCRIBER_ID_IS_EMPTY => 'Subscriber id is empty',
        self::ERROR_STORE_ID_IS_EMPTY => 'Store id is empty',
        self::ERROR_EMAIL_FORMAT => 'Email has a wrong format',
        self::ERROR_STATUS_VALUE => 'Unsupported subscriber status value',
    ];

    /**
     * Field list
     *
     * @var array
     */
    protected $fields = [
        'subscriber_id',
        'store_id',
        'change_status_at',
        'subscriber_email',
        'firstname',
        'lastname',
        'password_hash',
        'subscriber_status',
        'subscriber_confirm_code'
    ];

    /**
     * Subscriber factory
     *
     * @var SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Customer factory
     *
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * Encryptor
     *
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * Statuse codes
     *
     * @var array
     */
    protected $status = [
        Subscriber::STATUS_SUBSCRIBED,
        Subscriber::STATUS_NOT_ACTIVE,
        Subscriber::STATUS_UNSUBSCRIBED,
        Subscriber::STATUS_UNCONFIRMED
    ];

    /**
     * Initialize import
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param ImportFactory $importFactory
     * @param SubscriberFactory $subscriberFactory
     * @param CustomerFactory $customerFactory
     * @param StoreManagerInterface $storeManager
     * @param EncryptorInterface $encryptor
     * @param array $data
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        ImportFactory $importFactory,
        SubscriberFactory $subscriberFactory,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        EncryptorInterface $encryptor,
        array $data = []
    ) {
        $this->_logger = $context->getLogger();
        $this->output = $context->getOutput();
        $this->_importExportData = $context->getImportExportData();
        $this->_resourceHelper = $context->getResourceHelper();
        $this->jsonHelper = $context->getJsonHelper();
        $this->subscriberFactory = $subscriberFactory;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;

        parent::__construct(
            $context->getStringUtils(),
            $scopeConfig,
            $importFactory,
            $context->getResourceHelper(),
            $context->getResource(),
            $context->getErrorAggregator(),
            $data
        );
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
        return 'newsletter_subscriber';
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
        if (!empty($rowData[self::COL_SUBSCRIBER_EMAIL])) {
            $this->validateStore($rowData, $rowNumber);
            $this->validateEmail($rowData, $rowNumber);
        } elseif (empty($rowData[self::COL_SUBSCRIBER_ID])) {
            $this->addRowError(self::ERROR_SUBSCRIBER_ID_IS_EMPTY, $rowNumber);
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
        $this->validateRowForDelete($rowData, $rowNumber);
        $this->validateStatus($rowData, $rowNumber);
    }

    /**
     * Validate email string
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    protected function validateEmail(array $rowData, $rowNumber)
    {
        if (!filter_var($rowData[self::COL_SUBSCRIBER_EMAIL], FILTER_VALIDATE_EMAIL)) {
            $this->addRowError(self::ERROR_EMAIL_FORMAT, $rowNumber);
        }
    }

    /**
     * Validate store data
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    protected function validateStore(array $rowData, $rowNumber)
    {
        if (!$this->storeManager->isSingleStoreMode()) {
            if (empty($rowData[self::COL_STORE_ID])) {
                $this->addRowError(self::ERROR_STORE_ID_IS_EMPTY, $rowNumber);
            }
        }
    }

    /**
     * Validate status string
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    protected function validateStatus(array $rowData, $rowNumber)
    {
        if (!empty($rowData[self::COL_STATUS])) {
            if (!in_array($rowData[self::COL_STORE_ID], $this->status)) {
                $this->addRowError(self::ERROR_STATUS_VALUE, $rowNumber);
            }
        }
    }

    /**
     * Delete row
     *
     * @param array $rowData
     * @return $this
     */
    protected function delete(array $rowData)
    {
        $subscriber = $this->subscriberFactory->create();
        if (!empty($rowData[self::COL_SUBSCRIBER_EMAIL])) {
            $subscriber->loadByEmail($rowData[self::COL_SUBSCRIBER_EMAIL]);
        } elseif (!empty($rowData[self::COL_SUBSCRIBER_ID])) {
            $subscriber->load($rowData[self::COL_SUBSCRIBER_ID]);
        }

        if ($subscriber->getId()) {
            $subscriber->delete();
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
        $subscriber = $this->subscriberFactory->create();
        $email = $rowData[self::COL_SUBSCRIBER_EMAIL] ?? null;

        if (!empty($email)) {
            $subscriber->loadByEmail($email);
            if ($subscriber->getId()) {
                unset($rowData[self::COL_SUBSCRIBER_EMAIL]);
            }
        } elseif (!empty($rowData[self::COL_SUBSCRIBER_ID])) {
            $subscriber->load($rowData[self::COL_SUBSCRIBER_ID]);
            unset($rowData[self::COL_SUBSCRIBER_ID]);
        }

        if ($subscriber->getId()) {
            $this->countItemsUpdated++;
        } else {
            $this->countItemsCreated++;
            $rowData[self::COL_SUBSCRIBER_ID] = null;
        }

        unset($rowData[self::COL_CUSTOMER_ID]);
        $subscriber->addData($rowData);

        if (!$subscriber->getCustomerId()) {
            $customer = $this->customerFactory->create();
            if ($customer->getSharingConfig()->isWebsiteScope()) {
                $store = empty($rowData[self::COL_STORE_ID])
                    ? $this->storeManager->getDefaultStoreView()
                    : $this->storeManager->getStore($rowData[self::COL_STORE_ID]);

                $customer->setWebsiteId($store->getWebsiteId());
            }

            $customer->loadByEmail($email);
            if ($customer->getId()) {
                $subscriber->setCustomerId($customer->getId());
            } else {
                if (!empty($rowData['firstname']) &&
                    !empty($rowData['lastname']) &&
                    !empty($rowData[self::COL_PASSWORD_HASH])
                ) {
                    $customer->setFirstname($rowData['firstname']);
                    $customer->setLastname($rowData['lastname']);
                    $customer->setEmail($subscriber->getSubscriberEmail());
                    $customer->setPasswordHash($this->getPasswordHash($rowData));
                    $customer->save();

                    $subscriber->setCustomerId($customer->getId());
                }
            }
        }

        try {
            $subscriber->save();
        } catch (\Exception $e) {
            $this->addLogWriteln($e->getMessage(), $this->getOutput(), 'error');

        }
        return $this;
    }

    /**
     * Retrieve customer password hash
     *
     * @param array $rowData
     * @return string
     */
    protected function getPasswordHash(array $rowData)
    {
        if ($this->encryptor->validateHashVersion($rowData[self::COL_PASSWORD_HASH])) {
            // m2 pasword hash
            return $rowData[self::COL_PASSWORD_HASH];
        }
        // m1 pasword hash
        $parts = explode(Encryptor::DELIMITER, $rowData[self::COL_PASSWORD_HASH]);
        $count = count($parts);
        if (2 == $count) {
            list($hash, $salt) = $parts;
            $version = (32 == strlen($hash)) ? Encryptor::HASH_VERSION_MD5 : Encryptor::HASH_VERSION_SHA256;
            return implode(
                Encryptor::DELIMITER,
                [
                    $hash,
                    $salt,
                    $version
                ]
            );
        }
        return null;
    }

    /**
     * Save Validated Bunches
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _saveValidatedBunches()
    {
        $source = $this->getSource();
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
