<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\Field\Job\Configuration;

use Firebear\ImportExport\Model\Migration\Field\JobInterface;
use Magento\Framework\Encryption\EncryptorInterface;

/**
 * @package Firebear\ImportExport\Model\Migration\Field\Job\Configuration
 */
class DecryptEncryptValue implements JobInterface
{
    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var DecryptValue
     */
    protected $decryptValueProcessor;

    /**
     * @param DecryptValue $decryptValueProcessor
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        DecryptValue $decryptValueProcessor,
        EncryptorInterface $encryptor
    ) {
        $this->decryptValueProcessor = $decryptValueProcessor;
        $this->encryptor = $encryptor;
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
        $decrypted = $this->decryptValueProcessor->job(
            $sourceField,
            $sourceValue,
            $destinationFiled,
            $destinationValue,
            $sourceDataRow
        );

        return $this->encryptor->encrypt($decrypted);
    }
}
