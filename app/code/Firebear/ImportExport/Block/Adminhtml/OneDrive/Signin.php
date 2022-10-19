<?php

namespace Firebear\ImportExport\Block\Adminhtml\OneDrive;

use Firebear\ImportExport\Model\OneDrive\OneDrive as OneDriveModel;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Signin extends Field
{
    /**
     * @var OneDriveModel
     */
    protected $oneDrive;

    /**
     * Signin constructor.
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        OneDriveModel $oneDrive,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->oneDrive = $oneDrive;
    }

    /**
     * @var string
     */
    protected $_template = 'Firebear_ImportExport::import/onedrive/signin.phtml';

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * @return string
     */
    public function getAuthUrl()
    {
        return $this->oneDrive->signin();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            [
                'id' => 'onedrive-signin',
                'label' => __('SignIn OneDrive'),
            ]
        );

        return $button->toHtml();
    }
}
