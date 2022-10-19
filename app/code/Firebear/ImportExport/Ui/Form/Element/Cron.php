<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

/**
 * Form time element
 */
namespace Firebear\ImportExport\Ui\Form\Element;

use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;

/**
 * Class Cron
 *
 * @package Firebear\ImportExport\Ui\Form\Element
 */
class Cron extends \Magento\Framework\Data\Form\Element\AbstractElement
{
    /**
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param array $data
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        $data = []
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
        $this->setType('cron');
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        $name = parent::getName();
        if (strpos($name, '[]') === false) {
            $name .= '[]';
        }
        return $name;
    }

    /**
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getElementHtml()
    {
        $this->addClass('input-text admin__control-text');

        $valueMin = '';
        $valueHrs = '';
        $valueDom = '';
        $valueMon = '';
        $valueDow = '';

        if ($value = $this->getValue()) {
            $values = explode(' ', $value);
            if (is_array($values) && count($values) == 5) {
                $valueMin = $values[0];
                $valueHrs = $values[1];
                $valueDom = $values[2];
                $valueMon = $values[3];
                $valueDow = $values[4];
            }
        }

        $html = '';
        /**
         * Cron Minutes
         */
        $html .= '<div class="col-m-2">';
        $html .= '<input type="hidden" id="' . $this->getHtmlId() . '" ' . $this->_getUiId() . '/>';
        $html .= '<input type="text" name="' . $this->getName() . '" value="' . $valueMin . '" style="width:80px" '
            . $this->serialize(
                $this->getHtmlAttributes()
            ) . $this->_getUiId(
                'minute'
            ) . ' />' . "\n";
        $html .= '<div class="note admin__field-note"><span>Minutes</span></div>';
        $html .= '</div>';

            /**
         * Cron Hours
         */
        $html .= '<div class="col-m-2">';
        $html .= '<input type="text" name="' . $this->getName() . '" value="' . $valueHrs . '" style="width:80px" '
            . $this->serialize(
                $this->getHtmlAttributes()
            ) . $this->_getUiId(
                'hour'
            ) . ' />' . "\n";
        $html .= '<div class="note admin__field-note"><span>Hours</span></div>';
        $html .= '</div>';

        /**
         * Cron Day of Month
         */
        $html .= '<div class="col-m-2">';
        $html .= '<input type="text" name="' . $this->getName() . '" value="' . $valueDom . '" style="width:80px" '
            . $this->serialize(
                $this->getHtmlAttributes()
            ) . $this->_getUiId(
                'day'
            ) . ' />' . "\n";
        $html .= '<div class="note admin__field-note"><span>Days</span></div>';
        $html .= '</div>';

        /**
         * Cron Month
         */
        $html .= '<div class="col-m-2">';
        $html .= '<input type="text" name="' . $this->getName() . '" value="' . $valueMon . '" style="width:80px" '
            . $this->serialize(
                $this->getHtmlAttributes()
            ) . $this->_getUiId(
                'month'
            ) . ' />' . "\n";
        $html .= '<div class="note admin__field-note"><span>Months</span></div>';
        $html .= '</div>';

        /**
         * Cron Day of Week
         */
        $html .= '<div class="col-m-2">';
        $html .= '<input type="text" name="' . $this->getName() . '" value="' . $valueDow . '" style="width:80px" '
            . $this->serialize(
                $this->getHtmlAttributes()
            ) . $this->_getUiId(
                'dow'
            ) . ' />' . "\n";
        $html .= '<div class="note admin__field-note"><span>Days of Week</span></div>';
        $html .= '</div>';

        $html .= '<div class="col-m-12"><div class="note admin__field-note">'
            .'<span>↑ Use this field if you have good cron knowledge.</span>'
            .'</div></div>';

        $html .= $this->getAfterElementHtml();
        return $html;
    }
}
