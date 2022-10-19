<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\OneDrive;

use Exception;
use League\OAuth2\Client\Provider\GenericProviderFactory as OAuth2GenericProviderFactory;
use Magento\Framework\App\Cache\TypeListInterface as CacheTypeListInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as StorageWriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Microsoft\Graph\GraphFactory;
use Microsoft\Graph\Model\DriveItem;
use Microsoft\Graph\Model\DriveItemUploadableProperties;
use Microsoft\Graph\Model\UploadSession;
use Magento\Framework\HTTP\ZendClientFactory as HttpClientFactory;
use GuzzleHttp\Psr7\Utils;
use Firebear\ImportExport\Model\OneDrive\Graph\Entity as GraphEntity;

/**
 * Class OneDrive
 * @package Firebear\ImportExport\Model\OneDrive
 */
class OneDrive
{
    const OAUTH_AUTHORITY_URL = 'https://login.microsoftonline.com/common';
    const CONFIG_PATH_REFRESH_TOKEN = 'firebear_importexport/onedrive/refresh_token';
    const CONFIG_PATH_CLIENT_ID = 'firebear_importexport/onedrive/client_id';
    const CONFIG_PATH_CLIENT_SECRET = 'firebear_importexport/onedrive/client_secret';
    const SCOPES = 'offline_access files.read Files.ReadWrite Files.ReadWrite.All';
    const ACCESS_TOKEN_CACHE_ID = 'one_drive_access_token';
    const AUTH_STATE_CACHE_ID = 'one_drive_auth_state';

    /**
     * @var OAuth2GenericProviderFactory
     */
    protected $genericProviderFactory;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var CacheTypeListInterface
     */
    protected $cacheTypeList;
    /**
     * @var GraphFactory
     */
    protected $graphFactory;
    /**
     * @var StorageWriterInterface
     */
    protected $storageWriterInterface;
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var \Microsoft\Graph\Graph
     */
    protected $client;
    /**
     * @var HttpClientFactory
     */
    protected $httpClientFactory;

    public function __construct(
        OAuth2GenericProviderFactory $genericProviderFactory,
        StorageWriterInterface $storageWriterInterface,
        ScopeConfigInterface $scopeConfig,
        CacheTypeListInterface $cacheTypeList,
        GraphFactory $graphFactory,
        CacheInterface $cache,
        HttpClientFactory $httpClientFactory
    ) {
        $this->genericProviderFactory = $genericProviderFactory;
        $this->storageWriterInterface = $storageWriterInterface;
        $this->scopeConfig = $scopeConfig;
        $this->cacheTypeList = $cacheTypeList;
        $this->graphFactory = $graphFactory;
        $this->cache = $cache;
        $this->httpClientFactory = $httpClientFactory;
    }

    /**
     * @return string
     * @throw \Exception
     */
    public function signin()
    {
        $oauthClient = $this->genericProviderFactory->create(['options' => $this->prepareOAuthCred()]);
        $authUrl = $oauthClient->getAuthorizationUrl();
        $this->saveAuthState($oauthClient->getState());

        return $authUrl ?? '';
    }

    /**
     * @return string[]
     */
    public function prepareOAuthCred()
    {
        return [
            'clientId' => $this->getClientId(),
            'clientSecret' => $this->getClientSecret(),
            'redirectUri' => $this->getRedirectUri(),
            'urlAuthorize' => $this->getUrlAuthorize(),
            'urlAccessToken' => $this->getUrlAccessToken(),
            'urlResourceOwnerDetails' => '',
            'scopes' => $this->getScopes(),
        ];
    }

    /**
     * @param $state
     */
    public function saveAuthState($state)
    {
        $this->cache->save($state, static::AUTH_STATE_CACHE_ID, ['config_scopes'], 3600);
    }

    /**
     * @param bool $forget
     * @return string
     */
    public function getAuthState($forget = true)
    {
        $state = $this->cache->load(static::AUTH_STATE_CACHE_ID);

        if ($forget) {
            $this->cache->remove(static::AUTH_STATE_CACHE_ID);
        }

        return $state ?? '';
    }

    /**
     * @param $providedState
     * @throws Exception
     */
    public function checkAuthState($providedState)
    {
        $expectedState = $this->getAuthState();
        if (!isset($providedState) || $expectedState != $providedState) {
            throw new LocalizedException(__('The provided auth state did not match the expected value'));
        }
    }

    /**
     * @param $authCode
     * @return \League\OAuth2\Client\Token\AccessTokenInterface
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function receiveAccessToken($authCode)
    {
        $oauthClient = $this->genericProviderFactory->create(['options' => $this->prepareOAuthCred()]);

        return $oauthClient->getAccessToken('authorization_code', [
            'code' => $authCode
        ]);
    }

    /**
     * @param $accessToken
     * @throws LocalizedException
     */
    public function saveAccessToken($accessToken)
    {
        if (!$accessToken instanceof \League\OAuth2\Client\Token\AccessTokenInterface) {
            throw new \InvalidArgumentException(__('The object must be of type AccessTokenInterface'));
        }

        $refreshToken = $accessToken->getRefreshToken();
        if (!$refreshToken) {
            throw new LocalizedException(__('Refresh token not provided'));
        }

        $this->storageWriterInterface->save(static::CONFIG_PATH_REFRESH_TOKEN, $refreshToken);
        $this->clearConfigCache();
        $this->cache->remove(static::ACCESS_TOKEN_CACHE_ID);
    }

    /**
     * @param $filePath
     * @return string
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     * @throws \Microsoft\Graph\Exception\GraphException
     */
    public function downloadFileContent($filePath)
    {
        $filePath = '/' . ltrim($filePath, '/');

        $url = '/me/drive/root:' . $filePath . ':/content';

        $graph = $this->getClient();
        $result = $graph->createRequest('GET', $url)->execute();

        return (string)$result->getRawBody();
    }

    /**
     * @return \Microsoft\Graph\Graph
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->client = $this->graphFactory->create();
        }
        $this->client->setAccessToken($this->getAccessToken());

        return $this->client;
    }

    /**
     * @return \Microsoft\Graph\Http\GraphResponse|mixed
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     * @throws \Microsoft\Graph\Exception\GraphException
     * @TODO Revert 'ae421462' commit after release 'microsoft/microsoft-graph' with php8.1 support
     */
    public function createUploadSession($driveItem, $filesize)
    {
        if (!$driveItem instanceof GraphEntity) {
            throw new \InvalidArgumentException(__('The object must be of type DriveItem'));
        }

        $graph = $this->getClient();
        $url = '/me/drive/items/' . $driveItem->getId() . '/createUploadSession';

        $driveItemUploadableProperties = (new GraphEntity())
            ->setName($driveItem->getName())
            ->setFileSize($filesize);

        return $graph->createRequest('POST', $url)
            ->setReturnType(GraphEntity::class)
            ->attachBody([
                'item' => $driveItemUploadableProperties,
                'deferCommit' => false
            ])
            ->execute();
    }

    /**
     * @param $pathToFile
     * @return \Microsoft\Graph\Http\GraphResponse|mixed
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     * @throws \Microsoft\Graph\Exception\GraphException
     * @TODO Revert 'ae421462' commit after release 'microsoft/microsoft-graph' with php8.1 support
     */
    public function prepareFileOnOneDrive($pathToFile)
    {
        $graph = $this->getClient();
        $url = '/me/drive/root:' . $pathToFile . ':/content';
        return $graph->createRequest('PUT', $url)
            ->setReturnType(GraphEntity::class)
            ->execute();
    }

    /**
     * @param $pathOnOneDrive
     * @param $filePath
     * @throws LocalizedException
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     * @throws \Microsoft\Graph\Exception\GraphException
     */
    public function uploadFile($pathOnOneDrive, $filePath)
    {
        $driveItem = $this->prepareFileOnOneDrive($pathOnOneDrive);

        $stream = Utils::streamFor(Utils::tryFopen($filePath, 'r'));
        $fullSize = $stream->getSize();

        $uploadSession = $this->createUploadSession($driveItem, $fullSize);

        if (!$uploadSession->getUploadUrl()) {
            throw new LocalizedException(__('Failed to get a link to upload a file on OneDrive'));
        }

        try {
            foreach ($this->createRanges($fullSize) as $range) {
                $start = $range['start'];
                $end = $range['end'];
                $size = $end - $start;

                $uploadClient = $this->httpClientFactory->create(['uri' => $uploadSession->getUploadUrl()]);
                $stream->seek($start);
                $uploadClient->setRawData($stream->read($size));
                $uploadClient->setHeaders([
                   'Content-Length' => $end - $start,
                   'Content-Range' => sprintf('bytes %d-%d/%d', $start, $end - 1, $fullSize),
                ]);

                $response = $uploadClient->request('PUT');
                if ($response->isError()) {
                    throw new LocalizedException(__(
                        'Upload Status: %1; Body: %2',
                        $response->getStatus(),
                        $response->getBody()
                    ));
                }
            }
        } catch (Exception $e) {
            $this->httpClientFactory->create(['uri' => $uploadSession->getUploadUrl()])
               ->request('DELETE');
            throw $e;
        }
    }

    /**
     * @param $sizeFile
     * @return array
     */
    protected function createRanges($sizeFile)
    {
        $ranges = [];
        $maxBatch = 60 * 1024 * 1024;
        $piece = 320 * 1024;
        $batchCountPiece = $maxBatch / $piece;
        $batch = ceil($sizeFile / $piece);

        $end = $prevEnd = 0;
        for ($i = 1; $i <= $batch; $i++) {
            if ($i % $batchCountPiece === 0) {
                $ranges[] = ['start' => $prevEnd, 'end' => $end];
                $prevEnd = $end;
            }

            $end += $piece;
        }

        if ($prevEnd !== $end) {
            $ranges[] = ['start' => $prevEnd, 'end' => $sizeFile];
        }

        return $ranges;
    }

    /**
     * @return string
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function getAccessToken()
    {
        $accessTokenString = $this->cache->load(static::ACCESS_TOKEN_CACHE_ID);

        if (!$accessTokenString) {
            $accessToken = $this->refreshAccessToken();
            $accessTokenString = $accessToken->getToken();
            if ($accessTokenString) {
                $lifetime = $accessToken->getValues()['ext_expires_in'] ?? 3600;
                $this->cache->save($accessToken, static::ACCESS_TOKEN_CACHE_ID, ['config_scopes'], $lifetime);
            }
        }

        return $accessTokenString ?? '';
    }

    /**
     * @return \League\OAuth2\Client\Token\AccessToken|\League\OAuth2\Client\Token\AccessTokenInterface
     * @throws LocalizedException
     */
    public function refreshAccessToken()
    {
        if (!$this->getRefreshToken()) {
            throw new LocalizedException(__('You are not authorized in the OneDrive.'));
        }

        $oauthClient = $this->genericProviderFactory->create(['options' => $this->prepareOAuthCred()]);

        return $oauthClient->getAccessToken('refresh_token', [
            'refresh_token' => $this->getRefreshToken()
        ]);
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->scopeConfig->getValue(static::CONFIG_PATH_REFRESH_TOKEN) ?? '';
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->scopeConfig->getValue(static::CONFIG_PATH_CLIENT_ID) ?? '';
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->scopeConfig->getValue(static::CONFIG_PATH_CLIENT_SECRET) ?? '';
    }

    /**
     * @return string
     */
    public function getRedirectUri()
    {
        $secureBaseUrl = $this->scopeConfig->getValue('web/secure/base_url') ?? '';
        $unsecureBaseUrl = $this->scopeConfig->getValue('web/unsecure/base_url') ?? '';
        $baseUrl = $secureBaseUrl ?? $unsecureBaseUrl;
        return $baseUrl . 'import/onedrive/signincallback';
    }

    /**
     * @return string
     */
    public function getUrlAuthorize()
    {
        return static::OAUTH_AUTHORITY_URL . '/oauth2/v2.0/authorize';
    }

    /**
     * @return string
     */
    public function getUrlAccessToken()
    {
        return static::OAUTH_AUTHORITY_URL . '/oauth2/v2.0/token';
    }

    /**
     * @return string
     */
    public function getScopes()
    {
        return static::SCOPES;
    }

    public function clearConfigCache()
    {
        $this->cacheTypeList
            ->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
    }
}
