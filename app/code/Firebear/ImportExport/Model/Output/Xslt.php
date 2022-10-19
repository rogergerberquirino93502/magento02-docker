<?php
/*
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
declare(strict_types=1);

namespace Firebear\ImportExport\Model\Output;

use DOMDocument;
use Exception;
use Firebear\ImportExport\Exception\XmlException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DomDocument\DomDocumentFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\XsltProcessor\XsltProcessorFactory;
use XSLTProcessor;

class Xslt
{
    const XML_PATH_HANDLE_PROCESSOR_ERRORS = 'firebear_importexport/xslt/handle_processor_errors';
    const EVENT_KEY = 'firebear_xslt_';

    /**
     * @var array|null
     */
    protected $restrictedPHPFunction = null;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var DomDocumentFactory
     */
    private $domDocumentFactory;

    /**
     * @var XsltProcessorFactory
     */
    private $xsltProcessorFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Xslt constructor.
     * @param DomDocumentFactory $domDocumentFactory
     * @param XsltProcessorFactory $xsltProcessorFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param ManagerInterface $eventManager
     * @param array $data
     */
    public function __construct(
        DomDocumentFactory $domDocumentFactory,
        XsltProcessorFactory $xsltProcessorFactory,
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $eventManager,
        array $data = []
    ) {
        $this->domDocumentFactory = $domDocumentFactory;
        $this->xsltProcessorFactory = $xsltProcessorFactory;
        $this->scopeConfig = $scopeConfig;
        $this->restrictedPHPFunction = isset($data['restrict_php_functions']) ?: null;
        $this->eventManager = $eventManager;
    }

    /**
     * @param string $file
     * @param string $xsl
     * @return false|string
     * @throws LocalizedException
     * @throws XmlException
     */
    public function convert(string $file, string $xsl)
    {
        if (!class_exists('\XSLTProcessor')) {
            throw new LocalizedException(__(
                'The XSLTProcessor class could not be found. This means your PHP installation is missing XSL features.'
            ));
        }
        $result = '';
        $useErrors = libxml_use_internal_errors(false);
        if ($this->getScopeConfig()->isSetFlag(self::XML_PATH_HANDLE_PROCESSOR_ERRORS)) {
            $useErrors = libxml_use_internal_errors(true);
        }

        try {
            /**
             * Load Original XML Content
             */
            $xmlDoc = $this->getDom();
            $xmlDoc->loadXML($file, LIBXML_COMPACT | LIBXML_PARSEHUGE | LIBXML_NOWARNING);

            /**
             * Load XSLT template
             */
            $xslDoc = $this->getDom();
            $xslDoc->loadXML($xsl, LIBXML_COMPACT | LIBXML_PARSEHUGE | LIBXML_NOWARNING);

            $proc = $this->getXSLTProcessor();
            $proc->registerPHPFunctions($this->restrictedPHPFunction);
            $proc->importStylesheet($xslDoc);

            $this->eventManager->dispatch(
                self::EVENT_KEY . 'before_transform_to_doc',
                [
                    'adapter' => $this,
                    'xmlDoc' => $xmlDoc,
                    'xslDoc' => $xslDoc
                ]
            );

            $newDom = $proc->transformToDoc($xmlDoc);

            $this->eventManager->dispatch(
                self::EVENT_KEY . 'after_transform_to_doc',
                [
                    'adapter' => $this,
                    'xmlDoc' => $xmlDoc,
                    'xslDoc' => $xslDoc,
                    'newDom' => $newDom
                ]
            );

            if ($this->scopeConfig->isSetFlag(self::XML_PATH_HANDLE_PROCESSOR_ERRORS)
                && libxml_get_errors()
            ) {
                throw new XmlException(libxml_get_errors());
            }

            $result = $newDom->saveXML();
        } catch (Exception $e) {
            throw new XmlException($e->getMessage());
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($useErrors);
        }

        return $result;
    }

    /**
     * @return ScopeConfigInterface
     */
    public function getScopeConfig()
    {
        return $this->scopeConfig;
    }

    /**
     * @return DOMDocument
     */
    public function getDom()
    {
        return $this->domDocumentFactory->create();
    }

    /**
     * @return XSLTProcessor
     */
    public function getXSLTProcessor()
    {
        return $this->xsltProcessorFactory->create();
    }
}
