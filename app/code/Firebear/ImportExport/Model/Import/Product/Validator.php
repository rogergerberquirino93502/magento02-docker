<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product;

use Magento\CatalogImportExport\Model\Import\Product;
use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface;

/**
 * Class Validator
 *
 * @api
 * @since 100.0.2
 */
class Validator extends \Magento\CatalogImportExport\Model\Import\Product\Validator
{
    /**
     * @var array
     */
    protected $parameters = [];

    public function setParameters($params)
    {
        $this->parameters = $params;
        return $this;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getContext()
    {
        return $this->context;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($rowData)
    {
        $isValid = parent::isValid($rowData);
        if (!$isValid && !empty($this->_messages) && $rowData['sku']) {
            $message = array_pop($this->_messages);
            $message = ($this->context->retrieveMessageTemplate($message)) ?: $message;
            $this->_addMessages([$message . '. For SKU: "' . $rowData['sku'] . '"']);
        }
        return $isValid;
    }
}
