<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
declare(strict_types=1);

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Indexer;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Indexer\Model\Indexer\Collection;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @param Collection $collection
     */
    public function __construct(
        Collection $collection
    ) {
        $this->collection = $collection;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $indexers = $this->collection->getItems();
        foreach ($indexers as $indexer) {
            $options[] = ['label' => $indexer->getTitle(), 'value' => $indexer->getId()];
        }

        return $options;
    }
}
