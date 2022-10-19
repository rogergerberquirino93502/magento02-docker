<?php

namespace Firebear\ImportExport\Model\QueueMessage;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Amqp\Config as AmqpConfig;
use Magento\Framework\MessageQueue\ExchangeRepository;
use Magento\Framework\MessageQueue\MessageValidator;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\MessageQueue\EnvelopeFactory;
use Magento\Framework\MessageQueue\Publisher\ConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;

/**
 * Class ImageManagerQueueMessage
 * @package Firebear\ImportExport\Model\QueueMessage
 */
class ImagePublisher
{
    const HOST = 'host';

    const QUEUE_CONFIG = 'queue';

    const AMQP_CONFIG = 'amqp';

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var string;
     */
    protected $currentTopic = 'import_export.import_db';

    /**
     * @var DeploymentConfig
     */
    protected $deploymentConfig;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var EnvelopeFactory
     */
    protected $envelopeFactory;

    /**
     * ImagePublisher constructor.
     * @param ProductMetadataInterface $productMetadata
     * @param EncryptorInterface $encryptor
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        EncryptorInterface $encryptor,
        DeploymentConfig $deploymentConfig,
        EnvelopeFactory $envelopeFactory
    ) {
        $this->deploymentConfig = $deploymentConfig;
        $this->productMetadata = $productMetadata;
        $this->encryptor = $encryptor;
        $this->envelopeFactory = $envelopeFactory;
    }

    /**
     * @param $data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function publish($data)
    {
        $this->currentTopic = $this->isAmqp() ? 'import_export.import_amqp' : 'import_export.import_db';
        $messageValidator = ObjectManager::getInstance()->get(MessageValidator::class);
        $messageValidator->validate($this->currentTopic, $data);
        $messageEncoder = ObjectManager::getInstance()->get(MessageEncoder::class);
        $data = $messageEncoder->encode($this->currentTopic, $data);

        $envelope = $this->envelopeFactory->create(
            [
                'body' => $data,
                'properties' => [
                    'delivery_mode' => 2,
                    'message_id' => $this->encryptor->hash(uniqid($this->currentTopic))
                ]
            ]
        );
        $publisherConfig = ObjectManager::getInstance()->get(ConfigInterface::class);
        $connName = $publisherConfig->getPublisher($this->currentTopic)->getConnection()->getName();
        $connName = ($connName === self::AMQP_CONFIG && !$this->isAmqpConfigured()) ? 'db' : $connName;
        $exchangeRepository = ObjectManager::getInstance()->get(ExchangeRepository::class);
        $exchange = $exchangeRepository->getByConnectionName($connName);
        $exchange->enqueue($this->currentTopic, $envelope);
    }

    /**
     * Check Amqp is configured.
     *
     * @return bool
     */
    protected function isAmqpConfigured()
    {
        $amqpConfig = ObjectManager::getInstance()->get(AmqpConfig::class);
        return $amqpConfig->getValue(self::HOST) ? true : false;
    }

    /**
     * @return bool
     */
    protected function isAmqp()
    {
        $versionNoAmqp = !version_compare($this->productMetadata->getVersion(), '2.3.0', '>=')
            && $this->productMetadata->getEdition() == 'Community';
        $queueConfig = $this->deploymentConfig->getConfigData(self::QUEUE_CONFIG);
        if ($versionNoAmqp) {
            return false;
        }
        if (isset($queueConfig[self::AMQP_CONFIG])) {
            return true;
        }

        return false;
    }
}
