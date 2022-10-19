<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Lib;

use Magento\Framework\Exception\InputException;

/**
 * Install lib pool
 */
class LibPool implements LibPoolInterface
{
    /**
     * Install libs
     *
     * @var LibInterface[]
     */
    private $libs = [];

    /**
     * Initialize pool
     *
     * @param LibInterface[] $libs
     */
    public function __construct(
        $libs = []
    ) {
        foreach ($libs as $lib) {
            if (!$lib instanceof LibInterface) {
                throw new InputException(
                    __('Install lib must implement %1.', LibInterface::class)
                );
            }
        }
        $this->libs = $libs;
    }

    /**
     * Retrieve registered libs
     *
     * @return LibInterface[]
     */
    public function get()
    {
        return $this->libs;
    }
}
