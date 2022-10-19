<?php

namespace Firebear\ImportExport\Test\Integration;

class FileTest extends \Firebear\ImportExport\Test\Integration\AbstractImport
{

    public function _jobProvider()
    {
        return [
            // file - csv - product_all_types
            [
                'job-data' => [
                    'import_source' => 'file',
                    'source_data' => [
                        'import_source' => 'file',
                        'type_file' => 'csv',
                        'file_path' => __DIR__ . '/_files/csv/products_all_types.csv',
                    ],
                ],
                'expectations' => [
                    'result' => true,
                    'products-count' => 18,
                ],
            ],

            // file - json - product_all_types
            [
                'job-data' => [
                    'import_source' => 'file',
                    'source_data' => [
                        'import_source' => 'file',
                        'type_file' => 'json',
                        'file_path' => __DIR__ . '/_files/json/products_all_types.json',
                    ],
                ],
                'expectations' => [
                    'result' => true,
                    'products-count' => 18,
                ],
            ],

            // file - xlsx - product_all_types
            [
                'job-data' => [
                    'import_source' => 'file',
                    'source_data' => [
                        'import_source' => 'file',
                        'type_file' => 'xlsx',
                        'file_path' => __DIR__ . '/_files/xlsx/products_all_types.xlsx',
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
