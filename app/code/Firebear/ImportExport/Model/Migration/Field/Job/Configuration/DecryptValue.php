<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\Field\Job\Configuration;

use Firebear\ImportExport\Model\Migration\Field\JobInterface;
use Magento\Framework\Encryption\Adapter\Mcrypt;

/**
 * @package Firebear\ImportExport\Model\Migration\Field\Job\Configuration
 */
class DecryptValue implements JobInterface
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var Mcrypt
     */
    protected $mcrypt;

    /**
     * @param string $key
     *
     * @throws \Exception
     */
    public function __construct(string $key)
    {
        $this->key = $key;
        $this->mcrypt = new Mcrypt($this->key, MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
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
        return $this->mcrypt->decrypt(base64_decode($sourceValue));
    }
}
