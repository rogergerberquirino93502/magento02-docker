<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Lib;

/**
 * Install Spout lib
 */
class SpoutLib implements LibInterface
{
    /**
     * Spout lib class name
     *
     * @var string
     */
    private $className;

    /**
     * Initialize lib
     *
     * @param string $className
     */
    public function __construct(
        $className
    ) {
        $this->className = $className;
    }

    /**
     * Retrieve message
     *
     * @return string
     */
    public function getMessage()
    {
        return __(
            'To use the ODS and XLSX file format, you need to install '.
            'the library Spout (composer require box/spout:~2.7).'
        );
    }

    /**
     * Check whether the extension is allowed
     *
     * @param string $extension
     * @return bool
     */
    public function isAvailable($extension)
    {
        return !in_array($extension, ['ods', 'xlsx']) || $this->isInstalled();
    }

    /**
     * Check whether lib is installed
     *
     * @return bool
     */
    public function isInstalled()
    {
        return interface_exists($this->className);
    }
}
