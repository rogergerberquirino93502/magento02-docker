<?php
declare(strict_types=1);

namespace Firebear\ImportExport\Setup\Operations;

use Firebear\ImportExport\Model\ResourceModel\FieldEncryptor;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\AggregatedFieldDataConverter;
use Magento\Framework\DB\DataConverter\DataConversionException;
use Magento\Framework\DB\DataConverter\DataConverterInterface;
use Magento\Framework\DB\FieldToConvert;

/**
 * Encrypt operation
 */
class EncryptPasswordFields implements DataConverterInterface
{
    /**
     * Field data converter
     *
     * @var \Magento\Framework\DB\AggregatedFieldDataConverter
     */
    private $aggregatedFieldConverter;

    /**
     * @var FieldEncryptor
     */
    private $encryptor;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Initialize operation
     *
     * @param FieldEncryptor $encryptor
     * @param SerializerInterface $serializer
     * @param AggregatedFieldDataConverter $aggregatedFieldConverter
     */
    public function __construct(
        FieldEncryptor $encryptor,
        SerializerInterface $serializer,
        AggregatedFieldDataConverter $aggregatedFieldConverter
    ) {
        $this->encryptor = $encryptor;
        $this->serializer = $serializer;
        $this->aggregatedFieldConverter = $aggregatedFieldConverter;
    }

    /**
     * Convert data
     *
     * @param string $value
     * @return string
     * @throws DataConversionException
     */
    public function convert($value)
    {
        if (empty($value)) {
            return $value;
        }

        $data = $this->serializer->unserialize($value);
        $data = $this->encryptor->encrypt($data);
        return $this->serializer->serialize($data);
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @return void
     */
    public function execute($setup)
    {
        $this->aggregatedFieldConverter->convert(
            [
                new FieldToConvert(
                    self::class,
                    $setup->getTable('firebear_export_jobs'),
                    'entity_id',
                    'export_source'
                ),
            ],
            $setup->getConnection()
        );

        $this->aggregatedFieldConverter->convert(
            [
                new FieldToConvert(
                    self::class,
                    $setup->getTable('firebear_import_jobs'),
                    'entity_id',
                    'source_data'
                ),
            ],
            $setup->getConnection()
        );
    }
}
