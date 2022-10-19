<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

declare(strict_types=1);

namespace Firebear\ImportExport\Model\Export\FilterProcessor\Filter;

use Firebear\ImportExport\Model\Export\FilterProcessor\FilterProcessorInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime;

/**
 * @inheritdoc
 */
class Date implements FilterProcessorInterface
{
    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * Date constructor.
     *
     * @param DateTime $dateTime
     */
    public function __construct(
        DateTime $dateTime
    ) {
        $this->dateTime = $dateTime;
    }

    /**
     * @param Collection $collection
     * @param string $columnName
     * @param array|string $value
     * @return void
     * @throws LocalizedException
     */
    public function process(Collection $collection, string $columnName, $value)
    {
        if (is_array($value)) {
            $from = $value[0] ?? null;
            $to = $value[1] ?? null;

            if (is_scalar($from) && !empty($from)) {
                $date = $this->dateTime->formatDate($from, false);
                $collection->addFieldToFilter($columnName, ['from' => $date, 'date' => true]);
            }
            if (is_scalar($to) && !empty($to)) {
                $date = $this->dateTime->formatDate($to, false);
                $collection->addFieldToFilter($columnName, ['to' => $date, 'date' => true]);
            }
        }
    }
}
