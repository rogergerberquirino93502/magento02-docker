<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\Field\Job;

use Firebear\ImportExport\Model\Migration\Field\JobInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\Serializer\Serialize;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class MapJson implements JobInterface
{
    /**
     * @var Json
     */
    protected $jsonSerializer;
    /**
     * @var Serialize
     */
    protected $unSerialize;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @param Json $jsonSerializer
     * @param Serialize $unSerialize
     * @param LoggerInterface $logger
     */
    public function __construct(Json $jsonSerializer, Serialize $unSerialize, LoggerInterface $logger)
    {
        $this->jsonSerializer = $jsonSerializer;
        $this->unSerialize = $unSerialize;
        $this->logger = $logger;
    }
    /**
     * @inheritdoc
     */
    public function job(
        $sourceField,
        $sourceValue,
        $destinationFiled,
        $destinationValue,
        $sourceDataRow
    ) {
        if ($this->isSerialized($sourceValue)) {
            try {
                return $this->jsonSerializer->serialize($this->unSerialize->unserialize($sourceValue));
            } catch (\Exception $e) {
                $this->logger->critical('Error message', ['exception' => $e]);
            }
        }

        return $sourceValue;
    }

    /**
     * @param $data
     * @return bool
     */
    private function isSerialized($data)
    {
        // if it isn't a string, it isn't serialized
        if (!is_string($data)) {
            return false;
        }
        $data = trim($data);
        if ('N;' == $data) {
            return true;
        }

        if (!preg_match('/^([adObis]):/', $data, $badions)) {
            return false;
        }

        switch ($badions[1]) {
            case 'a':
            case 'O':
            case 's':
                if (preg_match("/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data)) {
                    return true;
                }
                break;
            case 'b':
            case 'i':
            case 'd':
                if (preg_match("/^{$badions[1]}:[0-9.E-]+;\$/", $data)) {
                    return true;
                }
                break;
        }
        return false;
    }
}
