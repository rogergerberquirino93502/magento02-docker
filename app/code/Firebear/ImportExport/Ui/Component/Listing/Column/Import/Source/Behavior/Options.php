<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Behavior;

use Magento\Framework\Data\OptionSourceInterface;
use Firebear\ImportExport\Model\Import;
use Magento\ImportExport\Model\Source\Import\Behavior\Factory;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{

    /**
     * @var array
     */
    protected $options;

    /**
     * @var Import
     */
    protected $import;

    /**
     * @var Factory
     */
    protected $behavior;

    /**
     * @param Import  $import
     * @param Factory $behavior
     */
    public function __construct(
        Import $import,
        Factory $behavior
    ) {
        $this->import = $import;
        $this->behavior = $behavior;
    }
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $list            = [];
        $uniqueBehaviors = $this->import->getUniqueEntityBehaviors();

        foreach ($uniqueBehaviors as $behaviorCode => $behaviorClass) {
            $array = $this->behavior->create($behaviorClass)->toOptionArray();
            foreach ($array as &$element) {
                $element['code'] = $behaviorCode;
            }
            $list = array_merge($list, $array);
        }

        return $list;
    }
}
