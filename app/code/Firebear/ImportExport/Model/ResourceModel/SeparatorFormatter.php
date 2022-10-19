<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\ResourceModel;

use Firebear\ImportExport\Api\Data\SeparatorFormatterInterface;

/**
 * Separator Formatter
 */
class SeparatorFormatter implements SeparatorFormatterInterface
{
    /**
     * Format a string
     *
     * @param string $separator
     * @return string
     */
    public function format($separator)
    {
        if ($separator == 't') {
            $separator = "\t";
        }
        return $separator;
    }
}
