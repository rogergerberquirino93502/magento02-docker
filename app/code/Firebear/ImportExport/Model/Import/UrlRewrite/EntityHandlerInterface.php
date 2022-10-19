<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\UrlRewrite;

use \Magento\ImportExport\Model\Import\AbstractEntity;

/**
 * Entity handler interface
 */
interface EntityHandlerInterface
{
    /**
     * Initialize handler
     *
     * @param AbstractEntity $importEntity
     * @return $this
     */
    public function init(AbstractEntity $importEntity);

    /**
     * Validate row data for replace behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    public function validateRowForReplace(array $rowData, $rowNumber);

    /**
     * Validate row data for delete behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    public function validateRowForDelete(array $rowData, $rowNumber);

    /**
     * Validate row data for update behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    public function validateRowForUpdate(array $rowData, $rowNumber);

    /**
     * Prepare row data for update behaviour
     *
     * @param array $rowData
     * @return array
     */
    public function prepareRowForUpdate(array $rowData);

    /**
     * Prepare row data for replace behaviour
     *
     * @param array $rowData
     * @return array
     */
    public function prepareRowForReplace(array $rowData);

    /**
     * Prepare row data for delete behaviour
     *
     * @param array $rowData
     * @return array
     */
    public function prepareRowForDelete(array $rowData);
}
