<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Form;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\Json\EncoderInterface;

/**
 * Class Container
 */
class Container extends \Magento\Ui\Component\Container
{
    /**
     * @var EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * Container constructor.
     * @param ContextInterface $context
     * @param EncoderInterface $jsonEncoder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        EncoderInterface $jsonEncoder,
        $components,
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->jsonEncoder = $jsonEncoder;
    }

    /**
     *
     */
    public function prepare()
    {
        if ($model = $this->getDataByKey('options')) {
            $options = $model->toOptionArray();
            $config = $this->getData('config');
            $config['options'] = $this->jsonEncoder->encode($options);
            $this->setData('config', $config);
        }

        parent::prepare();
    }
}
