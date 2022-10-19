<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Behavior;

use Magento\Framework\Data\OptionSourceInterface;
use Firebear\ImportExport\Model\Import;

/**
 * Class Sourceoptions
 *
 * @package Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Behavior
 */
class Sourceoptions implements OptionSourceInterface
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
     * Sourceoptions constructor.
     *
     * @param Import $import
     */
    public function __construct(
        Import $import
    ) {
        $this->import = $import;
    }
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $behaviors = $this->import->getEntityBehaviors();
        foreach ($behaviors as $entityCode => $behavior) {
            $behaviors[$entityCode] = $behavior['notes'];
        }

        return $behaviors;
    }
}
