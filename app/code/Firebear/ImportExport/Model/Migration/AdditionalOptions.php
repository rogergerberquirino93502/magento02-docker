<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

declare(strict_types=1);

namespace Firebear\ImportExport\Model\Migration;

class AdditionalOptions
{
    /**
     * @var string|null
     */
    protected $migrateFromDate;

    /**
     * @var int
     */
    protected $testBunchSize = 2000;

    /**
     * @return string|null
     */
    public function getMigrateFromDate()
    {
        return $this->migrateFromDate;
    }

    /**
     * @param string|null $migratedFromDate
     */
    public function setMigrateFromDate($migratedFromDate)
    {
        $this->migrateFromDate = $migratedFromDate;
    }

    /**
     * @return int
     */
    public function getTestBunchSize()
    {
        return $this->testBunchSize;
    }

    /**
     * @param int $testBunchSize
     */
    public function setBatchSize(int $testBunchSize)
    {
        $this->testBunchSize = $testBunchSize;
    }
}
