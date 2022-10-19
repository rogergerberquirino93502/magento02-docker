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
interface StrategyInterface extends \Iterator
{
    /**
     * Set job
     *
     * @param ImportInterface $job
     * @return $this
     */
    public function setJob(ImportInterface $job);

    /**
     * Checks if strategy is available
     *
     * @return bool
     */
    public function isAvailable();

    /**
     * Return the current element
     *
     * @return ImportInterface
     */
    #[\ReturnTypeWillChange]
    public function current();

    /**
     * Move forward to next element
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function next();

    /**
     * Return the key of the current element
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function key();

    /**
     * Checks if current position is valid
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function valid();

    /**
     * Rewind the \Iterator to the first element
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function rewind();

    /**
     * @param bool $result
     * @return void
     */
    public function setLastResult(bool $result);
}
