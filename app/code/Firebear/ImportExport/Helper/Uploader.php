<?php
/**
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Helper;

/**
 * Class Uploader
 */
class Uploader extends \Magento\DownloadableImportExport\Helper\Uploader
{
    /**
     * Construct
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Downloadable\Helper\File $fileHelper
     * @param \Magento\CatalogImportExport\Model\Import\UploaderFactory $uploaderFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Downloadable\Helper\File $fileHelper,
        \Magento\CatalogImportExport\Model\Import\UploaderFactory $uploaderFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Filesystem $filesystem,
        \Firebear\ImportExport\Model\Import\UploaderFactory $firebearUploaderFactory
    ) {
        parent::__construct($context, $fileHelper, $uploaderFactory, $resource, $filesystem);
        $this->fileUploader = $firebearUploaderFactory->create();
        $this->fileUploader->init();
        $this->fileUploader->setAllowedExtensions($this->getAllowedExtensions());
        $this->fileUploader->removeValidateCallback('catalog_product_image');
    }
}
