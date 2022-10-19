<?php
/**
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Test\Unit\Model\Import\Product\Price\Rule;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Class ConditionTest
 *
 * @package Firebear\ImportExport\Test\Unit\Model\Import
 */
class ConditionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    protected $priceRuleCondition;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->priceRuleCondition = $this->objectManagerHelper->getObject(
            \Firebear\ImportExport\Model\Import\Product\Price\Rule\Condition::class,
            []
        );
    }

    /**
     * @dataProvider validateConditionsDataProvider
     *
     * @param $data
     * @param $expectedResult
     *
     * @return void
     */
    public function testValidateConditions($data, $expectedResult)
    {
        $this->assertEquals(
            $expectedResult,
            $this->priceRuleCondition->validatePriceRuleConditions($data),
            ''
        );
    }

    /**
     * @return array
     */
    public function validateConditionsDataProvider()
    {
        $row = [
            'sku' => '123',
            'price' => 9.99
        ];

        $categories = [1, 4, 7, 15, 78, 124, 534, 1008];
        $categoryId = 4;

        return [
            [[
                'conditions' => [
                    '1--1' => ['attribute' => 'price', 'operator' => '<', 'value' => 80],
                    '1--2' => ['attribute' => 'category_ids', 'operator' => '{}', 'value' => $categoryId],
                ],
                'row' => $row,
                'aggregator' => 'all',
                'value' => 1,
                'categories' => $categories
            ], true],
            [[
                'conditions' => [
                    '1--1' => ['attribute' => 'price', 'operator' => '<', 'value' => 80],
                    '1--2' => ['attribute' => 'category_ids', 'operator' => '{}', 'value' => $categoryId],
                ],
                'row' => $row,
                'aggregator' => 'any',
                'value' => 1,
                'categories' => $categories
            ], true],
            [[
                'conditions' => [
                    '1--1' => ['attribute' => 'price', 'operator' => '<', 'value' => 80],
                    '1--2' => ['attribute' => 'category_ids', 'operator' => '{}', 'value' => $categoryId],
                ],
                'row' => $row,
                'aggregator' => 'all',
                'value' => 0,
                'categories' => $categories
            ], false],
            [[
                'conditions' => [
                    '1--1' => ['attribute' => 'price', 'operator' => '<', 'value' => 80],
                    '1--2' => ['attribute' => 'category_ids', 'operator' => '{}', 'value' => $categoryId],
                ],
                'row' => $row,
                'aggregator' => 'any',
                'value' => 0,
                'categories' => $categories
            ], false],
            [[
                'conditions' => [
                    '1--1' => ['attribute' => 'price', 'operator' => '<', 'value' => 80],
                    '1--2' => ['attribute' => 'category_ids', 'operator' => '{}', 'value' => 5],
                ],
                'row' => $row,
                'aggregator' => 'all',
                'value' => 1,
                'categories' => $categories
            ], false],
            [[
                'conditions' => [
                    '1--1' => ['attribute' => 'price', 'operator' => '>', 'value' => 80],
                    '1--2' => ['attribute' => 'category_ids', 'operator' => '{}', 'value' => 5],
                ],
                'row' => $row,
                'aggregator' => 'any',
                'value' => 1,
                'categories' => $categories
            ], false],
            [[
                'conditions' => [
                    '1--1' => ['attribute' => 'price', 'operator' => '>', 'value' => 80],
                    '1--2' => ['attribute' => 'category_ids', 'operator' => '{}', 'value' => 5],
                ],
                'row' => $row,
                'aggregator' => 'all',
                'value' => 0,
                'categories' => $categories
            ], true],
            [[
                'conditions' => [
                    '1--1' => ['attribute' => 'price', 'operator' => '<', 'value' => 80],
                    '1--2' => ['attribute' => 'category_ids', 'operator' => '{}', 'value' => $categoryId],
                ],
                'row' => $row,
                'aggregator' => 'any',
                'value' => 0,
                'categories' => $categories
            ], false],
            [[
                'conditions' => [
                    '1--1' => ['attribute' => 'price', 'operator' => '>', 'value' => 80],
                    '1--2' => ['attribute' => 'category_ids', 'operator' => '{}', 'value' => $categoryId],
                ],
                'row' => $row,
                'aggregator' => 'any',
                'value' => 0,
                'categories' => $categories
            ], true],
        ];
    }
}
