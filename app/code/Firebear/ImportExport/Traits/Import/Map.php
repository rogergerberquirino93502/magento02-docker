<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Traits\Import;

use Symfony\Component\Console\Output\OutputInterface;

trait Map
{
    use ReplacingTrait;

    /** @var bool  */
    protected $replaceWithDefault = false;
    /**
     * @param $data
     * @return $this
     */
    public function setMap($data)
    {
        $this->maps = $data;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMap()
    {
        if (!$this->maps && !is_array($this->maps)) {
            $this->maps = [];
        }
        return $this->maps;
    }

    /**
     * @param $data
     * @return mixed
     */
    protected function changeFields($data)
    {
        $maps = $this->getMap();
        if (count($maps)) {
            foreach ($maps as $field) {
                if (isset($data[$field['import']])) {
                    $temp = $data[$field['import']];
                    $data[$field['system']] = $temp;
                }
            }
        }

        return $data;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function changeField($key)
    {
        $newKey = $key;
        $maps = $this->getMap();
        if (!empty($maps)) {
            foreach ($maps as $field) {
                if ($field['import'] == $key) {
                    $newKey = $field['system'];
                }
            }
        }

        return $newKey;
    }

    /**
     * @param $rowData
     * @return mixed
     */
    protected function replaceValue($rowData)
    {
        if ($this->getPlatform()) {
            $rowData = $this->getPlatform()->deleteColumns($rowData);
        }
        $maps = $this->getMap();
        if (count($maps)) {
            foreach ($maps as $field) {
                if ($field['default'] != '') {
                    if ($field['system'] == 'image') {
                        $field['system'] = 'base_image';
                    }
                    $default = $field['default'];
                    if (strlen($default)) {
                        if (isset($rowData[$field['system']])
                            && \strlen($rowData[$field['system']]) == 0
                            && $this->getReplaceWithDefault() == 0
                        ) {
                            $rowData[$field['system']] = $default;
                        } elseif (!isset($rowData[$field['system']])) {
                            $rowData[$field['system']] = $default;
                        } elseif ($this->getReplaceWithDefault() == 1) {
                            $rowData[$field['system']] = $default;
                        }
                    }
                }
            }
        }

        $rowData = $this->handleReplacing($rowData);

        return $rowData;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function replaceColumns($data)
    {

        if ($this->getPlatform()) {
            $data = $this->getPlatform()->prepareColumns($data);
        }

        $iter = [];
        $maps = $this->getMap();
        if (count($maps)) {
            foreach ($maps as $field) {
                if (in_array($field['import'], $data) && !in_array($field['import'], $iter)) {
                    $key = array_search($field['import'], $data);
                    $data[$key] = $field['system'];
                    $iter[] = $field['import'];
                }
                if (empty($field['import'])) {
                    $data[] = $field['system'];
                }
            }
        }
        if ($this->getPlatform()) {
            $data = $this->getPlatform()->afterColumns($data, $maps);
        }

        return $data;
    }

    /**
     * @param $file
     * @return bool|\Magento\Framework\Phrase
     */
    public function checkMimeType($file)
    {
        $message = true;
        $error = __('Incorrect file format');

        if (function_exists('mime_content_type') && !empty($this->mimeTypes)) {
            try {
                $result = mime_content_type($file);
                if (!in_array($result, $this->mimeTypes)) {
                    $message = $error;
                }
            } catch (\Exception $e) {
                $message = __('The file is not exist');
            }
        } else {
            $result = pathinfo($file, PATHINFO_EXTENSION);
            if ($result != $this->extension) {
                $message = $error;
            }
        }

        return $message;
    }

    /**
     * @return bool
     */
    public function getReplaceWithDefault()
    {
        return $this->replaceWithDefault;
    }

    /**
     * @param bool $default
     *
     * @return bool
     */
    public function setReplaceWithDefault($default = false)
    {
        return $this->replaceWithDefault = $default;
    }
}
