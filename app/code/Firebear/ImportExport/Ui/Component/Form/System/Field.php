<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Form\System;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

/**
 * Class Field
 *
 * @package Firebear\ImportExport\Ui\Component\Form\System
 */
class Field extends \Magento\Ui\Component\Form\Field
{

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * Field constructor.
     *
     * @param ContextInterface                         $context
     * @param UiComponentFactory                       $uiComponentFactory
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param array                                    $components
     * @param array                                    $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        $components,
        array $data = []
    ) {
        $this->jsonEncoder  = $jsonEncoder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare component configuration
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepare()
    {
        if ($model = $this->getDataByKey('source_options')) {
            $sourceOptions = $model->toOptionArray();
            $config = $this->getData('config');
            $config['sourceOptions'] = $this->jsonEncoder->encode($sourceOptions);
            $this->setData('config', $config);
        }

        parent::prepare();
    }
}
