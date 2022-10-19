<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Lib;

/**
 * Install Json lib
 */
class JsonLib implements LibInterface
{
    /**
     * Json lib class names
     *
     * @var string[]
     */
    private $classNames;

    /**
     * Initialize lib
     *
     * @param string[] $classNames
     */
    public function __construct(
        $classNames
    ) {
        $this->classNames = $classNames;
    }

    /**
     * Retrieve message
     *
     * @return string
     */
    public function getMessage()
    {
        return __(
            'To use the JSON file format, you need to install ' .
            'the a streaming parse library (composer require salsify/json-streaming-parser) ' .
            'and a JSON Writer (composer require bcncommerce/json-stream).'
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
        return 'json' !== $extension || $this->isInstalled();
    }

    /**
     * Check whether lib is installed
     *
     * @return bool
     */
    public function isInstalled()
    {
        $installed = true;
        foreach ($this->classNames as $className) {
            if (!class_exists($className)) {
                $installed = false;
            }
        }
        return $installed;
    }
}
