<?php
declare(strict_types=1);

namespace Firebear\ImportExport\Setup\Operations;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\AggregatedFieldDataConverter;
use Magento\Framework\DB\DataConverter\DataConverterInterface;
use Magento\Framework\DB\FieldToConvert;

/**
 * Encrypt operation
 */
class UpdateFilePathWithPrefix implements DataConverterInterface
{
    /**
     * Field data converter
     *
     * @var \Magento\Framework\DB\AggregatedFieldDataConverter
     */
    private $aggregatedFieldConverter;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Initialize operation
     *
     * @param SerializerInterface $serializer
     * @param AggregatedFieldDataConverter $aggregatedFieldConverter
     */
    public function __construct(
        SerializerInterface $serializer,
        AggregatedFieldDataConverter $aggregatedFieldConverter
    ) {
        $this->serializer = $serializer;
        $this->aggregatedFieldConverter = $aggregatedFieldConverter;
    }

    /**
     * Convert data
     *
     * @param string $value
     * @return string
     */
    public function convert($value)
    {
        if (empty($value)) {
            return $value;
        }

        $data = $this->serializer->unserialize($value);
        $source = $data['import_source'] ?? '';

        if ($source != 'file') {
            $filePathLabel = $source . '_file_path';
            if (!isset($data[$filePathLabel])) {
                $data[$filePathLabel] = $data['file_path'] ?? '';
            }
        }
        return $this->serializer->serialize($data);
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @throws \Magento\Framework\DB\FieldDataConversionException
     */
    public function execute(ModuleDataSetupInterface $setup)
    {
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
