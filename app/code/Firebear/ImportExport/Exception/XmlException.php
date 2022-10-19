<?php

/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
declare(strict_types=1);

namespace Firebear\ImportExport\Exception;

use LibXMLError;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class XmlException
 * @ref https://stackoverflow.com/questions/10025247/libxml-error-handler-with-oop
 * @package Firebear\ImportExport\Exception
 */
class XmlException extends LocalizedException
{

    /**
     * @var array
     */
    private $errorMessages = [];

    /**
     * XmlException constructor.
     * @param array $errors
     */
    public function __construct($errors = [])
    {
        $errorMessage = 'Unknown Error XmlException';
        $lineNo = 1;

        if (!empty($errors) && is_array($errors)) {
            foreach ($errors as $error) {
                if ($error instanceof LibXMLError) {
                    $this->errorMessages[] = $this->parseError($error, $lineNo);
                    $lineNo++;
                }
            }
        }

        if (!empty($this->errorMessages)) {
            $errorMessage = implode("\n", $this->errorMessages);
        } elseif (is_string($errors)) {
            $errorMessage = $errors;
        }

        parent::__construct(__($errorMessage));
    }

    /**
     * @param LibXMLError $error
     * @param $lineNo
     * @return string
     */
    public function parseError(LibXMLError $error, $lineNo)
    {
        $messages[] = sprintf('Error No.%d', $lineNo);

        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $messages[] = sprintf("\t Warning %s: %s", $error->code, trim($error->message));
                break;
            case LIBXML_ERR_ERROR:
                $messages[] = sprintf("\t Error %s: %s", $error->code, trim($error->message));
                break;
            case LIBXML_ERR_FATAL:
                $messages[] = sprintf("\t Fatal Error %s: %s", $error->code, trim($error->message));
                break;
        }

        $messages[] = sprintf("\t  Line: %s", $error->line);
        $messages[] = sprintf("\t  Column: %s", $error->column);

        if ($error->file) {
            $messages[] = sprintf('\t  File: %s', trim($error->file));
        }

        return implode("\n", $messages);
    }
}
