<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Ui\DataProvider\Form\Modifier;

use Firebear\ImportExport\Helper\Config;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;

/**
 * Encryptor modifier
 */
class Encryptor implements ModifierInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * Fields that should be encrypted
     *
     * @var array
     */
    private $encryptableFields = [
        'ftp_user',
        'sftp_username',
        'export_source_ftp_user',
        'export_source_sftp_username',
        'export_source_ftp_password',
        'export_source_sftp_password',
        'ftp_password',
        'sftp_password'
    ];

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        if ($this->config->isEncryption()) {
            return $meta;
        }

        foreach ($this->encryptableFields as $field) {
            if (isset($meta['source']['children'][$field])) {
                unset($meta['source']['children'][$field]['arguments']['data']['config']['template']);
            }
        }
        return $meta;
    }
}
