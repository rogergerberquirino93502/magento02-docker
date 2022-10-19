<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Job\Strategy;

use Firebear\ImportExport\Api\Data\ImportInterface;

/**
 * @api
 */
class SimpleStrategy implements StrategyInterface
{
    /**
     * @var ImportInterface|null
     */
    private $job;

    /**
     * @var int
     */
    private $key = 0;

    /**
     * @var bool
     */
    private $lastResult = true;

    /**
     * Set job
     *
     * @param ImportInterface $job
     * @return $this
     */
    public function setJob(ImportInterface $job)
    {
        $this->job = $job;
        return $this;
    }

    /**
     * Checks if strategy is available
     *
     * @return bool
     */
    public function isAvailable()
    {
        return $this->job instanceof ImportInterface;
    }

    /**
     * Return the current element
     *
     * @return ImportInterface
     */
    public function current()
    {
        return $this->job;
    }

    /**
     * Move forward to next element
     *
     * @return void
     */
    public function next()
    {
        $this->key++;
    }

    /**
     * Return the key of the current element
     *
     * @return int
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * Checks if current position is valid
     *
     * @return bool
     */
    public function valid()
    {
        return $this->key === 0;
    }

    /**
     * Rewind the \Iterator to the first element
     *
     * @return void
     */
    public function rewind()
    {
        $this->key = 0;
    }

    /**
     * @param bool $result
     */
    public function setLastResult(bool $result)
    {
        $this->lastResult = $result;
    }
}
