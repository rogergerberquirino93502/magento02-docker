<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export;

/**
 * Export Entity Interface
 */
interface EntityInterface
{
    /**
     * Retrieve entity field columns
     *
     * @return array
     */
    public function getFieldColumns();

    /**
     * Retrieve entity field for filter
     *
     * @return array
     */
    public function getFieldsForFilter();

    /**
     * Retrieve entity field for export
     *
     * @return array
     */
    public function getFieldsForExport();
}
