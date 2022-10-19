<?php

namespace Firebear\ImportExport\Model;

class IsSingleSourceModeCacheProcess
{

    /**
     * @var bool
     */
    protected $isCacheEnable = false;

    /**
     * @var bool|null
     */
    protected $cachedResult = null;

    /**
     * @var bool
     */
    protected $isExecutedOnce = false;

    public function enableCache()
    {
        $this->isCacheEnable = true;
    }

    public function disableCache()
    {
        $this->isCacheEnable = false;
    }

    /**
     * @return bool
     */
    public function isCacheEnabled(): bool
    {
        return $this->isCacheEnable;
    }

    /**
     * @param $result
     * @param bool $withoutExecuted
     */
    public function setCachedResult($result, bool $withoutExecuted = false)
    {
        $this->cachedResult = $result;
        if (!$withoutExecuted) {
            $this->isExecutedOnce = true;
        }
    }

    /**
     * @return bool|null
     */
    public function getCachedResult()
    {
        return $this->cachedResult;
    }

    /**
     * @return bool
     */
    public function isExecutedOnce(): bool
    {
        return $this->isExecutedOnce;
    }
}
