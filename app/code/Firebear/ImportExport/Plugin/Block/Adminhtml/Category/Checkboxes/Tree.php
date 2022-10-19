<?php
/**
 * @copyright: Copyright © 2018 Firebear Studio GmbH. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

/**
 * Plugin for config class.
 * Replace default magento classes.
 */

namespace Firebear\ImportExport\Plugin\Block\Adminhtml\Category\Checkboxes;

/**
 * Class Tree
 *
 * @package Firebear\ImportExport\Plugin\Block\Adminhtml\Category\Checkboxes
 */
class Tree
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * Tree constructor.
     *
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->request = $request;
    }

    /**
     * @see Magento_Catalog::catalog/category/checkboxes/tree.phtml $block->getJsFormObject()
     * @see Firebear_ImportExport::import/form/conditions.phtml $_jsObjectName
     * @see Firebear_ImportExport::js/rules.js showChooserElement() 'record' param in Ajax request
     * @see Firebear_ImportExport::js/form/components/html.js initContainer()
     * replace '__recordId__' to dynamic row index
     *
     * @param \Magento\Catalog\Block\Adminhtml\Category\Checkboxes\Tree $model
     * @param \Closure $proceed
     * @param                                                           $key
     *
     * @return mixed|string
     */
    public function aroundGetData(
        \Magento\Catalog\Block\Adminhtml\Category\Checkboxes\Tree $model,
        \Closure $proceed,
        $key
    ) {
        $value = $proceed($key);
        $params = $this->request->getParams();
        if ($key == 'js_form_object' && $value && isset($params['record'])) {
            return $value . $this->request->getParam('record');
        }

        return $value;
    }
}
