<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Data;

/**
 * Separator Formatter
 */
interface SeparatorFormatterInterface
{
    /**
     * Format a string
     *
     * @param string $separator
     * @return string
     */
    public function format($separator);
}
