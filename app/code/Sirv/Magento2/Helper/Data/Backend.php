<?php

namespace Sirv\Magento2\Helper\Data;

/**
 * Backend helper
 *
 * @author    Sirv Limited <support@sirv.com>
 * @copyright Copyright (c) 2018-2022 Sirv Limited <support@sirv.com>. All rights reserved
 * @license   https://sirv.com/
 * @link      https://sirv.com/integration/magento/
 */
class Backend extends \Sirv\Magento2\Helper\Data
{
    /**
     * Get config scope
     *
     * @return string
     */
    public function getConfigScope()
    {
        return static::$configScope;
    }

    /**
     * Get config scope id
     *
     * @return integer
     */
    public function getConfigScopeId()
    {
        return static::$configScopeId;
    }

    /**
     * Get config parent scope
     *
     * @return string|false
     */
    public function getParentConfigScope()
    {
        return static::$configScope == self::SCOPE_STORE ? self::SCOPE_WEBSITE : (static::$configScope == self::SCOPE_WEBSITE ? self::SCOPE_DEFAULT : false);
    }

    /**
     * Get default profile option names
     *
     * @return array
     */
    public function getDefaultProfileOptions()
    {
        return $this->defaultProfileOptions;
    }

    /**
     * Get config
     *
     * @param string $name
     * @param string $scope
     * @return mixed
     */
    public function getConfig($name = null, $scope = null)
    {
        if ($scope === null) {
            $config =& static::$sirvConfig;
        } elseif (isset(static::$fullConfig[$scope])) {
            $config =& static::$fullConfig[$scope];
        } else {
            $config = [];
        }

        return $name ? (isset($config[$name]) ? $config[$name] : null) : $config;
    }

    /**
     * Delete config
     *
     * @param string $name
     * @return void
     */
    public function deleteConfig($name)
    {
        if (isset($this->defaultProfileOptions[$name])) {
            $scope = self::SCOPE_DEFAULT;
            $scopeId = 0;
        } else {
            $scope = static::$configScope;
            $scopeId = static::$configScopeId;
        }

        $collection = $this->getConfigModel()->getCollection();
        $collection->addFieldToFilter('scope', $scope);
        $collection->addFieldToFilter('scope_id', $scopeId);
        $collection->addFieldToFilter('name', $name);

        $model = $collection->getFirstItem();
        $id = $model->getId();
        if ($id !== null) {
            $model->delete();
        }

        if (isset(static::$fullConfig[$scope][$name])) {
            unset(static::$fullConfig[$scope][$name]);
            if (isset(static::$sirvConfig[$name])) {
                unset(static::$sirvConfig[$name]);
                /*if (isset(static::$fullConfig[self::SCOPE_STORE][$name])) {
                    static::$sirvConfig[$name] = static::$fullConfig[self::SCOPE_STORE][$name];
                } else */
                if (isset(static::$fullConfig[self::SCOPE_WEBSITE][$name])) {
                    static::$sirvConfig[$name] = static::$fullConfig[self::SCOPE_WEBSITE][$name];
                } elseif (isset(static::$fullConfig[self::SCOPE_DEFAULT][$name])) {
                    static::$sirvConfig[$name] = static::$fullConfig[self::SCOPE_DEFAULT][$name];
                }
            }
        }
    }

    /**
     * Get Magento Catalog Images Cache data
     *
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getMagentoCatalogImagesCacheData()
    {
        static $data = null;

        if ($data === null) {
            $data = ['count' => 0];

            /** @var \Magento\Framework\Filesystem $filesystem */
            $filesystem = $this->objectManager->get(\Magento\Framework\Filesystem::class);
            /** @var \Magento\Framework\Filesystem\Directory\ReadInterface $mediaDirectory */
            $mediaDirectory = $filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
            $mediaDirAbsPath = $mediaDirectory->getAbsolutePath();
            $cacheDirAbsPath = rtrim($mediaDirAbsPath, '\\/') . '/catalog/product/cache';

            if (is_dir($cacheDirAbsPath)) {
                /** @var \Magento\Framework\Shell $shell */
                $shell = $this->objectManager->get(\Magento\Framework\Shell::class);
                $command = 'find ' . $cacheDirAbsPath . ' -type f | wc -l';
                try {
                    $output = $shell->execute($command);
                    $data['count'] = $output;
                } catch (\Exception $e) {
                    $flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS;
                    try {
                        $iterator = new \RecursiveIteratorIterator(
                            new \RecursiveDirectoryIterator($cacheDirAbsPath, $flags),
                            \RecursiveIteratorIterator::CHILD_FIRST
                        );
                        $count = 0;
                        foreach ($iterator as $item) {
                            if ($item->isFile()) {
                                $count++;
                            }
                        }
                        $data['count'] = $count;
                    } catch (\Exception $e) {
                        throw new \Magento\Framework\Exception\FileSystemException(
                            new \Magento\Framework\Phrase($e->getMessage()),
                            $e
                        );
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Get list of user accounts
     *
     * @param bool $force
     * @return array
     */
    public function getSirvUsersList($force = false)
    {
        static $users = null;

        if ($users === null || $force) {
            $email = $this->getConfig('email') ?: '';
            $password = $this->getConfig('password') ?: '';
            $cacheId = 'sirv_accounts_' . hash('md5', $email . $password);
            $cache = $this->getAppCache();

            $data = $force ? false : $cache->load($cacheId);
            if (false !== $data) {
                $users = $this->getUnserializer()->unserialize($data);
            }

            if (!is_array($users)) {
                $users = $this->getSirvClient()->getUsersList();
                natsort($users);
                $cache->save($this->getSerializer()->serialize($users), $cacheId, [], 600);
            }
        }

        return $users;
    }

    /**
     * Get list of account profiles
     *
     * @return array
     */
    public function getProfiles()
    {
        static $profiles = null;

        if ($profiles === null) {
            $profiles = $this->getSirvClient()->getProfiles();
            if (!is_array($profiles)) {
                $profiles = [];
            }
        }

        return $profiles;
    }

    /**
     * Get list of Sirv folders
     *
     * @param string $path
     * @return array
     */
    public function getSirvDirList($path)
    {
        static $list = [];

        if (!isset($list[$path])) {
            $list[$path] = [];
            $contents = $this->getSirvClient()->getFolderContents($path);
            foreach ($contents as $item) {
                if ($item->isDirectory) {
                    $list[$path][] = $item->filename;
                }
            }
        }

        return $list[$path];
    }

    /**
     * Disable spin scanning for image folder
     *
     * @param string $imageFolder
     * @return void
     */
    public function disableSpinScanning($imageFolder)
    {
        if (empty($imageFolder)) {
            $imageFolder = 'catalog';
        }

        $imageFolder = '/' . ltrim($imageFolder, '/');

        $disableSpinScanning = false;

        /** @var \Sirv\Magento2\Model\Api\Sirv $apiClient */
        $apiClient = $this->getSirvClient();

        //NOTE: make sure that folder exists and spin scanning is enabled
        $options = $apiClient->getFolderOptions($imageFolder);

        if ($options) {
            $disableSpinScanning = (!isset($options->scanSpins) || $options->scanSpins) ? true : false;
        } else {
            $disableSpinScanning = true;
            $apiClient->uploadFile($imageFolder . '/sirv_tmp.txt', '', "\n");
            $apiClient->deleteFile($imageFolder . '/sirv_tmp.txt');
        }

        if ($disableSpinScanning) {
            $apiClient->setFolderOptions($imageFolder, ['scanSpins' => false]);
        }
    }

    /**
     * Get account identifier (hash)
     *
     * @return string
     */
    protected function getAccountId()
    {
        static $hash = null;

        if ($hash === null) {
            $email = $this->getConfig('email') ?: '';
            $account = $this->getConfig('account') ?: '';
            $hash = hash('md5', $email . $account);
        }

        return $hash;
    }

    /**
     * Get account config
     *
     * @param bool $force
     * @return array
     */
    public function getAccountConfig($force = false)
    {
        static $config = null;

        if ($config === null || $force) {
            $cacheId = 'sirv_account_info_' . $this->getAccountId();
            $cache = $this->getAppCache();

            $data = $force ? false : $cache->load($cacheId);
            if (false !== $data) {
                $data = $this->getUnserializer()->unserialize($data);
            }

            if (!is_array($data)) {
                /** @var \Sirv\Magento2\Model\Api\Sirv $apiClient */
                $apiClient = $this->getSirvClient();

                $data = [];
                $info = $apiClient->getAccountInfo();
                if ($info) {
                    $data['alias'] = $alias = isset($info->alias) ? $info->alias : '';
                    $data['cdn_url'] = isset($info->cdnURL) ? $info->cdnURL : '';
                    $data['aliases'] = [];
                    foreach ($info->aliases as $_alias => $_data) {
                        $data['aliases'][$_alias] = isset($_data->customDomain) ? $_data->customDomain : $_alias . '.sirv.com';
                    }

                    if (isset($info->aliases->{$alias})) {
                        if (isset($info->aliases->{$alias}->customDomain)) {
                            $data['cdn_url'] = $info->aliases->{$alias}->customDomain;
                        }
                    }
                    $data['fetching_enabled'] = false;
                    $data['fetching_url'] = '';
                    if (isset($info->fetching)) {
                        $data['fetching_enabled'] = isset($info->fetching->enabled) ? $info->fetching->enabled : false;
                        $data['fetching_url'] = isset($info->fetching->http, $info->fetching->http->url) ? $info->fetching->http->url : '';
                        if ($data['fetching_url']) {
                            $data['fetching_url'] = rtrim($data['fetching_url'], '/') . '/';
                        }
                    }
                    $data['minify'] = false;
                    if (isset($info->minify)) {
                        $data['minify'] = isset($info->minify->enabled) ? $info->minify->enabled : false;
                    }
                    $data['date_created'] = isset($info->dateCreated) ? $info->dateCreated : '';
                } else {
                    $code = $apiClient->getResponseCode();
                    $message = 'Can\'t get Sirv account info. ' .
                        'Code: ' . $code . ' ' . $apiClient->getErrorMsg();
                    $this->_logger->error($message);
                    if ($code == 401 || $code == 403) {
                        return [];
                    }
                    throw new \Magento\Framework\Exception\LocalizedException(
                        new \Magento\Framework\Phrase($message)
                    );
                }

                $cache->save($this->getSerializer()->serialize($data), $cacheId, [], 60);
            }

            $config = $data;
        }

        return $config;
    }

    /**
     * Set account config
     *
     * @param bool $fetching
     * @param string $url
     * @return void
     */
    public function setAccountConfig($fetching, $url)
    {
        $config = $this->getAccountConfig();
        $data = [];

        if ($fetching != $config['fetching_enabled'] || $url != $config['fetching_url']) {
            $data['fetching'] = [
                'enabled' => $fetching
            ];

            if (!empty($url)) {
                $data['fetching']['type'] = 'http';
                $data['fetching']['http'] = [
                    'url' => $url
                ];
            }
        }

        if ($fetching && $config['minify']) {
            $data['minify'] = [
                'enabled' => false
            ];
        }

        /** @var \Sirv\Magento2\Model\Api\Sirv $apiClient */
        $apiClient = null;

        if ($fetching && ($fetching != $config['fetching_enabled'])) {
            $apiClient = $this->getSirvClient();
            $apiClient->enableJsAndHtmlServing(true);
        }

        if (!empty($data)) {
            if ($apiClient === null) {
                $apiClient = $this->getSirvClient();
            }

            $updated = $apiClient->updateAccountInfo($data);
            if ($updated) {
                $cacheId = 'sirv_account_info_' . $this->getAccountId();
                $cache = $this->getAppCache();
                $config['fetching_enabled'] = $fetching;
                if (!empty($url)) {
                    $config['fetching_url'] = $url;
                }
                $cache->save($this->getSerializer()->serialize($config), $cacheId, [], 60);
            }
        }
    }

    /**
     * Sync config value
     *
     * @param string $name
     * @return string
     */
    public function syncConfig($name)
    {
        $config = $this->getAccountConfig();
        switch ($name) {
            case 'cdn_url':
                $value = $config['cdn_url'];
                $this->saveConfig('cdn_url', $value);
                break;
            case 'auto_fetch':
                //NOTE: auto_fetch: custom|all|none
                //      fetching_enabled: true|false
                if ($config['fetching_enabled']) {
                    $value = $this->getConfig('auto_fetch');
                    if ($value != 'custom' && $value != 'all') {
                        $value = 'custom';
                    }
                } else {
                    $value = 'none';
                }
                $this->saveConfig('auto_fetch', $value);
                break;
            case 'url_prefix':
                $value = $config['fetching_url'];
                $this->saveConfig('url_prefix', $value);
                break;
            default:
                $value = null;
        }

        return $value;
    }

    /**
     * Get a list of domains
     *
     * @return array
     */
    public function getDomains()
    {
        static $list = null;

        if ($list === null) {
            $list = [];
            /** @var \Magento\Store\Model\StoreRepository $repository */
            $repository = $this->objectManager->get(\Magento\Store\Api\StoreRepositoryInterface::class);
            $backendConfig = $this->objectManager->get(\Magento\Backend\App\ConfigInterface::class);
            $isBackendUrlSecure = $backendConfig->isSetFlag(\Magento\Store\Model\Store::XML_PATH_SECURE_IN_ADMINHTML);
            $stores = $repository->getList();
            $adminUrls = [];
            foreach ($stores as $store) {
                /** @var Magento\Store\Model\Store $store */

                $isUrlSecure = $store->getCode() == 'admin' ? $isBackendUrlSecure : $store->isFrontUrlSecure();
                $url = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK, $isUrlSecure);
                if (preg_match('#^(?:(?:https?\:)?//)?[^/]++#', $url, $match)) {
                    $url = $match[0] . '/';
                }

                if ($store->getCode() == 'admin') {
                    $adminUrls[hash('md5', $url)] = $url;
                    continue;
                }

                $list[hash('md5', $url)] = $url;
            }

            $list = array_merge($list, $adminUrls);
        }

        return $list;
    }

    /**
     * Get account usage data
     *
     * @param bool $force
     * @return array
     */
    public function getAccountUsageData($force = false)
    {
        static $data = null;

        if ($data === null || $force) {
            $cacheId = 'sirv_account_usage_' . $this->getAccountId();
            $cache = $this->getAppCache();

            $data = $force ? false : $cache->load($cacheId);
            if (false !== $data) {
                $data = $this->getUnserializer()->unserialize($data);
            }

            if (!is_array($data)) {
                $data = $this->collectAccountUsageData();
                //NOTE: 900 - cache lifetime (in seconds)
                $cache->save($this->getSerializer()->serialize($data), $cacheId, [], 900);
            }
        }

        return $data;
    }

    /**
     * Collect account usage data
     *
     * @return array
     */
    protected function collectAccountUsageData()
    {
        $data = [];
        $data['account'] = $this->getConfig('account') ?: 'unknown';
        $data['email'] = $this->getConfig('email') ?: 'unknown';

        $sirvClient = $this->getSirvClient();

        $billingPlanInfo = $sirvClient->getBillingPlanInfo();
        $dataTransferLimit = 0;
        $data['plan'] = [];
        if ($billingPlanInfo) {
            $planName = isset($billingPlanInfo->name) ? $billingPlanInfo->name : 'unknown';
            $storageLimit = isset($billingPlanInfo->storage) ? (int)$billingPlanInfo->storage : 0;
            $dataTransferLimit = isset($billingPlanInfo->dataTransferLimit) ? (int)$billingPlanInfo->dataTransferLimit : 0;
            $data['plan'] = [
                'name' => $planName,
                'storage_limit' => $this->getFormatedSize($storageLimit),
                'data_transfer_limit' => $dataTransferLimit ? $this->getFormatedSize($dataTransferLimit) : '&#8734',
            ];
        }

        $data['storage'] = [];
        $storageInfo = $sirvClient->getStorageInfo();
        if ($storageInfo) {
            $allowance = (int)$storageInfo->plan + (int)$storageInfo->extra;
            $used = (int)$storageInfo->used;
            $available = $allowance - $used;
            $data['storage'] = [
                'allowance' => $this->getFormatedSize($allowance),
                'used' => $this->getFormatedSize($used),
                'used_percent' => number_format($used / $allowance * 100, 2, '.', ''),
                'available' => $this->getFormatedSize($available),
                'available_percent' => number_format($available / $allowance * 100, 2, '.', ''),
                'files' => (int)$storageInfo->files
            ];
        }

        $data['traffic'] = [
            'allowance' => $this->getFormatedSize($dataTransferLimit),
            'traffic' => []
        ];
        $dates = [
            'This month' => [
                date('Y-m-01'),
                date('Y-m-t')
            ],
            date('F Y', strtotime('first day of -1 month')) => [
                date('Y-m-01', strtotime('first day of -1 month')),
                date('Y-m-t', strtotime('last day of -1 month'))
            ],
            date('F Y', strtotime('first day of -2 month')) => [
                date('Y-m-01', strtotime('first day of -2 month')),
                date('Y-m-t', strtotime('last day of -2 month'))
            ],
            date('F Y', strtotime('first day of -3 month')) => [
                date('Y-m-01', strtotime('first day of -3 month')),
                date('Y-m-t', strtotime('last day of -3 month'))
            ]
        ];
        $dataTransferLimit = $dataTransferLimit ? $dataTransferLimit : PHP_INT_MAX;

        foreach ($dates as $label => $date) {
            $traffic = $sirvClient->getHttpStats($date[0], $date[1]);
            if (empty($traffic)) {
                break;
            }

            $traffic = get_object_vars($traffic);
            $size = 0;
            foreach ($traffic as $v) {
                $size += (isset($v->total->size) ? (int)$v->total->size : 0);
            }
            $sizePercent = ($size / $dataTransferLimit) * 100;
            $trafficAttr = $sizePercent > 100 ? 'exceeded' : ($sizePercent ? 'normal' : 'empty');

            $data['traffic']['traffic'][$label] = [
                'size' => $this->getFormatedSize($size),
                'size_percent' => number_format($sizePercent, 2, '.', ''),
                'traffic_attr' => $trafficAttr
            ];
        }

        $limitsData = $this->getApiLimitsData();
        $data['limits'] = empty($limitsData) ? [] : $limitsData['limits'];
        $data['current_time'] = empty($limitsData) ? date('H:i:s e', time()) : $limitsData['current_time'];
        $data['fetch_file_limit'] = isset($limitsData['fetch_file_limit']) ? $limitsData['fetch_file_limit'] : 0;

        return $data;
    }

    /**
     * Get API limits data
     *
     * @return array
     */
    public function getApiLimitsData()
    {
        $data = [];
        $limits = $this->getSirvClient()->getAPILimits();
        if ($limits) {
            $currentTime = time();
            $data['limits'] = [];
            foreach ($limits as $type => $limitData) {
                $remaining = (int)$limitData->remaining;
                $reset = '-';
                if ($remaining <= 0) {
                    $expireTime = (int)$limitData->reset;
                    if ($expireTime >= $currentTime) {
                        $timeIsLeft = $expireTime - $currentTime;
                        if ($timeIsLeft < 60) {
                            $timeIsLeft = $timeIsLeft . ' second' . ($timeIsLeft > 1 ? 's' : '');
                        } else {
                            $timeIsLeft = floor($timeIsLeft / 60);
                            $timeIsLeft = $timeIsLeft . ' minute' . ($timeIsLeft > 1 ? 's' : '');
                        }
                        $reset = $timeIsLeft . ' (' . date('Y-m-d\TH:i:s.v\Z e', $expireTime) . ')';
                    }
                }
                $data['limits'][] = [
                    'type' => $type,
                    'limit' => $limitData->limit,
                    'count' => $limitData->count,
                    'reset' => $reset,
                ];
            }
            $data['current_time'] = date('H:i:s e', $currentTime);
            $data['fetch_file_limit'] = $limits->{'fetch:file'}->limit;
        }

        return $data;
    }

    /**
     * Get formated size
     *
     * @param int $size
     * @param int $precision
     * @return string
     */
    protected function getFormatedSize($size, $precision = 2)
    {
        $sign = ($size >= 0) ? '' : '-';
        $size = abs($size);

        $units = [' Bytes', ' KB', ' MB', ' GB', ' TB'];
        for ($i = 0; $size >= 1000 && $i < 4; $i++) {
            $size /= 1000;
        }

        return $sign . round($size, $precision) . $units[$i];
    }
}
