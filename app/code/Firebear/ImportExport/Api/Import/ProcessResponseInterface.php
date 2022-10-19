<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Import;

/**
 * Job process response
 *
 * @api
 */
interface ProcessResponseInterface
{
    /**
     * Retrieve result
     *
     * @return bool
     */
    public function getResult();

    /**
     * Retrieve count
     *
     * @return string
     */
    public function getCount();

    /**
     * Set result
     *
     * @param bool $result
     * @return void
     */
    public function setResult($result);

    /**
     * Set count
     *
     * @param int $count
     * @return void
     */
    public function setCount($count);
}
