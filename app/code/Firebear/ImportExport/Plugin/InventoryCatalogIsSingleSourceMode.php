<?php

namespace Firebear\ImportExport\Plugin;

use Firebear\ImportExport\Model\IsSingleSourceModeCacheProcess;

class InventoryCatalogIsSingleSourceMode
{

    /**
     * @var IsSingleSourceModeCacheProcess
     */
    protected $isSingleSourceModeCacheProcess;

    public function __construct(IsSingleSourceModeCacheProcess $isSingleSourceModeCacheProcess)
    {
        $this->isSingleSourceModeCacheProcess = $isSingleSourceModeCacheProcess;
    }

    public function aroundExecute(\Magento\InventoryCatalog\Model\IsSingleSourceMode $subject, callable $proceed)
    {
        if ($this->isSingleSourceModeCacheProcess->isCacheEnabled()) {
            if ($this->isSingleSourceModeCacheProcess->isExecutedOnce()) {
                $result = $this->isSingleSourceModeCacheProcess->getCachedResult();
            } else {
                $result = $proceed();
                $this->isSingleSourceModeCacheProcess->setCachedResult($result);
            }
        } else {
            $result = $proceed();
        }
        return $result;
    }
}
