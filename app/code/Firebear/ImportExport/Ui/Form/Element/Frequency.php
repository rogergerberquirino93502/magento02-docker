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
 * Class Frequency
 *
 * @package Firebear\ImportExport\Ui\Form\Element
 */
class Frequency extends \Magento\Framework\Data\Form\Element\Select
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
        $this->setType('frequency');
    }

    /**
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getElementHtml()
    {
        $this->addClass('select admin__control-select');
        $html = '';
        if ($this->getBeforeElementHtml()) {
            $html .= '<label class="addbefore" for="' .
                $this->getHtmlId() .
                '">' .
                $this->getBeforeElementHtml() .
                '</label>';
        }

        $onchange = '';
        if ($this->getDepend()) {
            $fieldId = $this->getDepend();
            $onchange .= ' data-depend="' . $fieldId . '"';
        }

        $html .= '<select' . $onchange . ' id="' . $this->getHtmlId() . '" name="' . $this->getName() . '" ' .
            $this->serialize($this->getHtmlAttributes()) . $this->_getUiId() . '>' . "\n";

        $value = $this->getValue();
        if (!is_array($value)) {
            $value = [$value];
        }

        if ($values = $this->getValues()) {
            foreach ($values as $key => $option) {
                if (!is_array($option)) {
                    $html .= $this->_optionToHtml(['value' => $key, 'label' => $option], $value);
                } else {
                    $html .= '<option value="' . $this->_escape($option['value']) . '"';
                    $html .= isset($option['expr']) ? 'data-expr="' . $this->_escape($option['expr']) . '"' : '';
                    $html .= isset($option['note']) ? 'data-note="' . $this->_escape($option['note']) . '"' : '';
                    $html .= isset($option['custom']) ? 'data-custom="1"' : '';
                    $html .= isset($option['title']) ? 'title="' . $this->_escape($option['title']) . '"' : '';
                    $html .= isset($option['style']) ? 'style="' . $option['style'] . '"' : '';
                    if (in_array($option['value'], $value)) {
                        $html .= ' selected="selected"';
                    }
                    $html .= '>' . $this->_escape($option['label']) . '</option>' . "\n";
                }
            }
        }

        $html .= '</select>' . "\n";

        if ($this->getAfterElementHtml()) {
            $html .= '<label class="addafter" for="' .
                $this->getHtmlId() .
                '">' .
                "\n{$this->getAfterElementHtml()}\n" .
                '</label>' .
                "\n";
        }

        if ($this->getDepend()) {
            $html .= $this->getScript();
        }

        return $html;
    }

    /**
     * @return string
     */
    public function getScript()
    {
        return <<<EndSCRIPT
<script language="javascript">
    require([
        'jquery'
    ], function(jQuery, alert){

        function prepareExpr(elm) {
            elm = jQuery(elm);
            var depend = elm.data('depend');
            var dependElm = jQuery('input[name*=' + depend + ']');
            var expr = elm.find(':selected').data('expr');
            //expr = expr.replace(/%s/g, '1');
            var value = expr.split(' ');
            for(var i = 0; i < value.length; i++) {
                dependElm.eq(i).val(value[i]);
            }
        }

        function changeDepend(elm) {
            var currentExpr = [];
            for(var i = 0; i < elm.length; i++) {
                currentExpr.push(elm.eq(i).val());
            }
            currentExpr = currentExpr.join(' ');
            var option = jQuery('#{$this->getHtmlId()}').find('option[data-expr="' + currentExpr + '"]');
            if(option.length) {
                jQuery('#{$this->getHtmlId()}').val(option.val());
            } else {
                jQuery('#{$this->getHtmlId()}').val(customValue);
            }
        }

        var elm = jQuery('input[name*="{$this->getDepend()}"]');
        changeDepend(elm);

        var select = jQuery('#{$this->getHtmlId()}');
        select.on('change', function(){
            prepareExpr(this);
        });
        var expr = jQuery('#{$this->getHtmlId()}').find(':selected').data('expr');
        var customValue = jQuery('#{$this->getHtmlId()}').find('option[data-custom="1"]').val();
        elm.on('change', function(){
            changeDepend(elm);
        });
    });
</script>
EndSCRIPT;
    }
}
