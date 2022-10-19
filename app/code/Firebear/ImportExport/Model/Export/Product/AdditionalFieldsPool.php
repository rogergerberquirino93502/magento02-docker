<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\Product;

/**
 * Class AdditionalFieldsPool
 * @package Firebear\ImportExport\Model\Export\Product
 */
class AdditionalFieldsPool
{
    /**
     * @var AdditionalFieldsInterface[]
     */
    protected $fieldsPool;

    /**
     * AdditionalFieldsPool constructor.
     * @param array $fieldsPool
     */
    public function __construct($fieldsPool = [])
    {
        $this->fieldsPool = $fieldsPool;
    }

    /**
     * @return AdditionalFieldsInterface[]
     */
    public function getEntities()
    {
        return $this->fieldsPool;
    }
}
