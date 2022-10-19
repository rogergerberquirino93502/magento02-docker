<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\File\Validator;

/**
 * Validator for check not protected file extensions
 */
class NotProtectedExtension extends \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension
{
    /**
     * Initialize protected file extensions
     *
     * @return $this
     */
    protected function _initProtectedFileExtensions()
    {
        parent::_initProtectedFileExtensions();
        unset($this->_protectedFileExtensions['xml']);
        return $this;
    }
}
