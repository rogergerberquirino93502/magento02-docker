<?php

namespace Firebear\ImportExport\Test\Integration;

class FtpTest extends \Firebear\ImportExport\Test\Integration\AbstractImport
{

    public function _jobProvider()
    {
        return [
            // ftp - csv - product_all_types
            [
                'job-data' => [
                    'import_source' => 'ftp',
                    'source_data' => [
                        'import_source' => 'ftp',
                        'type_file' => 'csv',
                        'ftp_file_path' => __DIR__ . '/_files/csv/products_all_types.csv',
                    ],
                ],
                'expectations' => [
                    'result' => true,
                    'products-count' => 18,
                ],
            ],

            // ftp - json - product_all_types
            [
                'job-data' => [
                    'import_source' => 'ftp',
                    'source_data' => [
                        'import_source' => 'ftp',
                        'type_file' => 'json',
                        'ftp_file_path' => __DIR__ . '/_files/json/products_all_types.json',
                    ],
                ],
                'expectations' => [
                    'result' => true,
                    'products-count' => 18,
                ],
            ],

            // ftp - xlsx - product_all_types
            [
                'job-data' => [
                    'import_source' => 'ftp',
                    'source_data' => [
                        'import_source' => 'ftp',
                        'type_file' => 'xlsx',
                        'ftp_file_path' => __DIR__ . '/_files/xlsx/products_all_types.xlsx',
                    ],
                ],
                'expectations' => [
                    'result' => true,
                    'products-count' => 18,
                ],
            ],

        ];
    }

    /**
     * Simple job. Check results and products count
     * @dataProvider _jobProvider
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testSimpleJob(array $jobData, array $expectations)
    {
        // $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        // Мокаем Filesystem\Io\Ftp для подмены методов
        $ioFtpMock = $this->getMockBuilder(\Firebear\ImportExport\Model\Filesystem\Io\Ftp::class)
            ->onlyMethods(['read'])
            ->getMock();
        $ioFtpMock->method('read')->willReturn(true);

        // Мокаем модель источника для подмены методов
        $sourceTypeFtpMock = $this->getMockBuilder(\Firebear\ImportExport\Model\Source\Type\Ftp::class)
            ->setConstructorArgs(
                [
                    $this->objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class),
                    $this->objectManager->get(\Magento\Framework\Filesystem::class),
                    $this->objectManager->get(\Firebear\ImportExport\Model\Filesystem\File\ReadFactory::class),
                    $this->objectManager->get(\Magento\Framework\Filesystem\Directory\WriteFactory::class),
                    $this->objectManager->get(\Magento\Framework\Filesystem\File\WriteFactory::class),
                    $this->objectManager->get(\Magento\Framework\Stdlib\DateTime\Timezone::class),
                    $this->objectManager->get(\Firebear\ImportExport\Model\Source\Factory::class),
                    $this->objectManager->get(\Magento\Framework\App\CacheInterface::class),
                    $this->objectManager->get(\Firebear\ImportExport\Api\Export\History\CompressInterface::class),
                    $this->objectManager->get(\Magento\Framework\Filesystem\Io\File::class),
                    $this->objectManager->get(\Firebear\ImportExport\Model\Filesystem\Io\Ftp::class),
                ]
            )
            ->onlyMethods(['getClient'])
            ->getMock();
        $sourceTypeFtpMock->method('getClient')->willReturn($ioFtpMock);

        // Мокаем фабрику, для подмены модели источника
        $sourceFactoryMock = $this->getMockBuilder(\Firebear\ImportExport\Model\Source\Factory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $sourceFactoryMock->method('create')->willReturn($sourceTypeFtpMock);

        // Подсовываем в objectManager наш мок вместо фабрики по di
        $this->objectManager->addSharedInstance(
            $sourceFactoryMock,
            \Firebear\ImportExport\Model\Source\Factory::class
        );

        // Подготавливаем файл во временную директорию
        if (isset($jobData['source_data']['ftp_file_path'])) {
            $sourceTypeFtpMock->setFtpFilePath($jobData['source_data']['ftp_file_path']);
            $tmpFilePath = $sourceTypeFtpMock->getTempFilePath();
            $this->copyFile(
                $jobData['source_data']['ftp_file_path'],
                $tmpFilePath
            );
        }

        // Создаем job, с нашими параметрами
        $job = $this->jobFixtureManager->createImportJob($jobData);

        /** @var \Firebear\ImportExport\Api\Import\RunByIdsInterface $runByIds */
        $runByIds = $this->objectManager->create(\Firebear\ImportExport\Api\Import\RunByIdsInterface::class);

        $result = $runByIds->execute([$job->getId()], 'console');

        // asserts
        $products = $this->getAllProducts();

        // Результат ипорта
        $this->assertEquals($expectations['result'], $result);
        // Количество товаров
        $this->assertEquals($expectations['products-count'], $products->getTotalCount());
        // Конфиг
        $configProduct = $this->productRepository->get('TST-Conf');
        $this->assertEquals('configurable', $configProduct->getTypeId());
        // Количество симплов у него
        $children = $configProduct->getTypeInstance()->getUsedProducts($configProduct);
        $this->assertEquals(9, count($children));
    }
}
