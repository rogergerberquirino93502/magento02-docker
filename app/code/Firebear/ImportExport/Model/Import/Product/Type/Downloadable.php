<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product\Type;

use Exception;
use Firebear\ImportExport\Traits\Import\Product\Type as TypeTrait;
use Magento\Framework\File\Uploader;
use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\CatalogImportExport\Model\Import\Product\Type\AbstractType;

/**
 * Class Downloadable
 */
class Downloadable extends \Magento\DownloadableImportExport\Model\Import\Product\Type\Downloadable
{
    use TypeTrait;

    const ERROR_LINK_URL_NOT_IN_DOMAIN_WHITELIST = 'linkUrlNotInDomainWhitelist';
    const ERROR_SAMPLE_URL_NOT_IN_DOMAIN_WHITELIST = 'sampleUrlNotInDomainWhitelist';

    /**
     * Array of cached import link
     *
     * @var array
     */
    protected $importLink = [];

    /**
     * Validation links option
     *
     * @param array $rowData
     * @return bool
     */
    protected function isRowValidLink(array $rowData)
    {
        $result = (
            isset($rowData[self::COL_DOWNLOADABLE_LINKS]) &&
            $rowData[self::COL_DOWNLOADABLE_LINKS] != ''
        );
        if (!$result) {
            return false;
        }
        $linkData = $this->prepareLinkData($rowData[self::COL_DOWNLOADABLE_LINKS]);

        if (!$result && !empty($rowData[self::COL_DOWNLOADABLE_LINKS])) {
            $rowSku = strtolower($rowData[ImportProduct::COL_SKU]);
            $linkData = $linkData[0];
            $key = hash(
                'sha256',
                $linkData['link_url'] .
                $linkData['link_file'] .
                $linkData['link_type'] .
                $linkData['sample_url'] .
                $linkData['sample_file'] .
                $linkData['sample_type'] .
                $rowSku,
                false
            );
            if (isset($this->importLink[$key])) {
                $this->_entityModel->addRowError(__('Duplicated downloadable_links attribute.'), $this->rowNum);
                return true;
            }
            $this->importLink[$key] = true;
        }
        return $result;
    }

    /**
     * Get fill data options with key link
     *
     * @param array $options
     *
     * @return array
     */
    protected function fillDataTitleLink(array $options)
    {
        $result = [];
        $select = $this->connection->select();
        $select->from(
            ['dl' => $this->_resource->getTableName('downloadable_link')],
            [
                'link_id',
                'product_id',
                'sort_order',
                'number_of_downloads',
                'is_shareable',
                'link_url',
                'link_file',
                'link_type',
                'sample_url',
                'sample_file',
                'sample_type'
            ]
        );
        $select->joinLeft(
            ['dlp' => $this->_resource->getTableName('downloadable_link_price')],
            'dl.link_id = dlp.link_id AND dlp.website_id=' . self::DEFAULT_WEBSITE_ID,
            ['price_id']
        );
        $select->where(
            'product_id in (?)',
            $this->productIds
        );
        $existingOptions = $this->connection->fetchAll($select);
        foreach ($options as $option) {
            foreach ($existingOptions as $key => $existingOption) {
                if ($this->isLinkOptionExist($option, $existingOption)) {
                    if (empty($existingOption['website_id'])) {
                        unset($existingOption['website_id']);
                    }
                    $existTitleOption = array_replace($this->dataLinkTitle, $option, $existingOption);
                    $existPriceOption = array_replace($this->dataLinkPrice, $option, $existingOption);
                    unset($existingOptions[$key]);
                    break;
                }
            }
            if (!empty($existTitleOption['link_id'])) {
                $linkId = $existTitleOption['link_id'];
                if (empty($existTitleOption['title']) && !empty($option['title'])) {
                    $existTitleOption['title'] = $option['title'];
                }
                $result['title'][$linkId] = $existTitleOption;
            }
            if (!empty($existPriceOption['link_id'])) {
                $linkId = $existPriceOption['link_id'];
                if (empty($existPriceOption['price'])) {
                    $existPriceOption['price'] = $option['price'] ?? 0;
                }
                $result['price'][$linkId] = $existPriceOption;
            }
        }

        return $result;
    }

    /**
     * Uploading files into the "downloadable/files" media folder.
     * Return a new file name if the same file is already exists.
     *
     * @param string $fileName
     * @param string $type
     * @param bool $renameFileOff
     *
     * @return string
     */
    protected function uploadDownloadableFiles($fileName, $type = 'links', $renameFileOff = false)
    {
        try {
            if ($this->_entityModel->getSourceType()
                && !in_array(
                    $this->_entityModel->getSourceType()->getCode(),
                    ['url', 'google']
                )
            ) {
                $dispersionPath = Uploader::getDispersionPath($fileName);
                $imageSting = mb_strtolower(
                    $dispersionPath . '/'
                        . preg_replace('/[^a-z0-9\._-]+/i', '', $fileName)
                );
                $this->_entityModel
                    ->getSourceType()
                    ->importImage($fileName, $imageSting);
                $res['file'] = $this->_entityModel
                    ->getSourceType()
                    ->getCode() . $imageSting;
            } else {
                $res = $this->uploaderHelper->getUploader(
                    $type,
                    $this->_entityModel->getParameters()
                )->move($fileName, $renameFileOff);
            }

            return $res['file'];
        } catch (Exception $e) {
            $this->_entityModel->addRowError(
                $this->_messageTemplates[self::ERROR_MOVE_FILE] . '. '
                    . $e->getMessage(),
                $this->rowNum
            );

            return '';
        }
    }

    public function isRowValid(array $rowData, $rowNum, $isNewProduct = true)
    {
        $this->rowNum = $rowNum;
        $error = false;
        if ($this->isRowValidSample($rowData) || $this->isRowValidLink($rowData)) {
            $error = true;
        }
        return !$error;
    }

    /**
     * Save product type specific data.
     *
     * @return AbstractType
     */
    public function saveData()
    {
        $newSku = $this->_entityModel->getNewSku();
        while ($bunch = $this->_entityModel->getNextBunch()) {
            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->_entityModel->isRowAllowedToImport($rowData, $rowNum)) {
                    continue;
                }

                if (version_compare($this->_entityModel->getProductMetadata()->getVersion(), '2.2.0', '>=')) {
                    $rowSku = strtolower($rowData[ImportProduct::COL_SKU]);
                } else {
                    $rowSku = $rowData[ImportProduct::COL_SKU];
                }
                $productData = $newSku[$rowSku];
                $this->parseOptions($rowData, $productData[$this->getProductEntityLinkField()]);
            }
            if (!empty($this->cachedOptions['sample']) || !empty($this->cachedOptions['link'])) {
                $this->saveOptions();
                $this->clear();
            }
        }
        return $this;
    }

    /**
     * Check if a Link Option Exist
     *
     * @param $option
     * @param $existingOption
     * @return bool
     */
    private function isLinkOptionExist($option, $existingOption)
    {
        return $option['link_url'] == $existingOption['link_url']
        && $option['link_file'] == $existingOption['link_file']
        && $option['link_type'] == $existingOption['link_type']
        && $option['sample_url'] == $existingOption['sample_url']
        && $option['sample_file'] == $existingOption['sample_file']
        && $option['sample_type'] == $existingOption['sample_type']
        && $option['product_id'] == $existingOption['product_id'];
    }

    /**
     * Clear current object
     *
     * @return void
     */
    public function clearObject()
    {
        $this->importLink = [];
    }

    /**
     * @param array $rowData
     * @return array
     */
    protected function addAdditionalAttributes(array $rowData)
    {
        $samplesTitle = $this->sampleGroupTitle($rowData);
        if (!$samplesTitle && !empty($rowData['samples_title'])) {
            $samplesTitle = $rowData['samples_title'];
        }
        $linksTitle = $this->linksAdditionalAttributes($rowData, 'group_title', self::DEFAULT_GROUP_TITLE);
        if (!$linksTitle && !empty($rowData['links_title'])) {
            $linksTitle = $rowData['links_title'];
        }
        return [
            'samples_title' => $samplesTitle,
            'links_title' => $linksTitle,
            'links_purchased_separately' => $this->linksAdditionalAttributes(
                $rowData,
                'purchased_separately',
                self::DEFAULT_PURCHASED_SEPARATELY
            )
        ];
    }
}
