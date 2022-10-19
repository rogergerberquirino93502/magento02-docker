<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Api\Import;

use Firebear\ImportExport\Api\Import\ProcessResponseInterface;

/**
 * Job process response
 *
 * @api
 */
class ProcessResponse implements ProcessResponseInterface
{
    /**
     * @var bool
     */
    private $result;

    /**
     * @var int
     */
    private $count;

    /**
     * Retrieve result
     *
     * @return bool
     */
    public function getResult()
    {
        return (bool)$this->result;
    }

    /**
     * Retrieve count
     *
     * @return string
     */
    public function getCount()
    {
        return (int)$this->count;
    }

    /**
     * Set result
     *
     * @param bool $result
     * @return void
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * Set count
     *
     * @param int $count
     * @return void
     */
    public function setCount($count)
    {
        $this->count = $count;
    }
}
