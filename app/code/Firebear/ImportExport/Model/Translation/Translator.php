<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Translation;

use Exception;
use Firebear\ImportExport\Traits\General as GeneralTrait;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class Translator
 * @package Firebear\ImportExport\Model\Translation
 */
class Translator
{
    use GeneralTrait;

    /**
     * @var TranslateAbstract
     */
    protected $languageTranslator;

    /**
     * @var array
     */
    private $translators;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var string
     */
    private $text;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Translator constructor.
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param ConsoleOutput $output
     * @param array $translators
     * @throws LocalizedException
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        ConsoleOutput $output,
        array $translators = []
    ) {
        foreach ($translators as $translator) {
            if (!($translator instanceof TranslateInterface)) {
                throw new LocalizedException(__(
                    'Translator must be instance of "%interface"',
                    ['interface' => TranslateInterface::class]
                ));
            }
        }
        $this->translators = $translators;
        $this->storeManager = $storeManager;
        $this->setLogger($logger);
        $this->setOutput($output);
    }

    /**
     * @param array $parameters
     * @return $this
     * @throws Exception
     */
    public function init(array $parameters)
    {
        $this->parameters = $parameters;
        $type = $parameters['translate_version'] ?? 'google_free';
        if (!isset($this->translators[$type])) {
            throw new LocalizedException(__(
                'No translator for "%type" given.',
                ['type' => $type]
            ));
        }
        $this->languageTranslator = $this->getTranslatorType($type)->setParams();
        return $this;
    }

    /**
     * @param $type
     * @return TranslateAbstract
     */
    private function getTranslatorType($type)
    {
        return $this->translators[$type] ?? null;
    }

    /**
     * @return bool
     */
    public function isTranslatorSet()
    {
        if (isset(
            $this->parameters['translate_from'],
            $this->parameters['translate_to'],
            $this->parameters['translate_attributes'],
            $this->parameters['translate_store_ids']
        )
        ) {
            return true;
        }
        return false;
    }

    /**
     * @param string $attrValue
     * @param string $translateAttribute
     * @param int $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function translateAttributeValue(string $attrValue, string $translateAttribute, int $storeId)
    {
        $this->addLogWriteln(
            __(
                'Translation of attribute %1 from %2 to %3 for storeCode %4',
                $translateAttribute,
                $this->getParameterValue('translate_from'),
                $this->getParameterValue('translate_to'),
                $this->storeManager->getStore($storeId)->getCode()
            ),
            $this->getOutput(),
            'info'
        );
        return $this->translate($attrValue);
    }

    /**
     * @param $key
     * @return string
     */
    private function getParameterValue($key)
    {
        return $this->parameters[$key] ?? null;
    }

    /**
     * @param null $text
     * @param bool $returnOrigText
     * @return string|null
     */
    public function translate($text = null, $returnOrigText = true)
    {
        $this->text = $text;
        if ($this->valid()) {
            $translatedText = $this->languageTranslator::translate($this->text, $this->parameters);
            if ((!$translatedText || $translatedText == null) && $returnOrigText == true) {
                return $this->text;
            } else {
                return $translatedText;
            }
        }
        return $text;
    }

    /**
     * @return bool
     */
    private function valid()
    {
        if ($this->validText() && $this->validLanguage()) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    private function validText()
    {
        if ($this->text == null || $this->text == '') {
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    private function validLanguage()
    {
        if ($this->getParameterValue('translate_from') == null || $this->getParameterValue('translate_to') == null) {
            return false;
        }
        return true;
    }
}
