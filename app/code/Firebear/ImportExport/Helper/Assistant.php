<?php
/**
 * Created by PhpStorm.
 * User: ded
 * Date: 12/23/17
 * Time: 12:56 PM
 */

namespace Firebear\ImportExport\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Class Data
 *
 * @package Firebear\ImportExport\Helper\Assistant
 */
class Assistant extends AbstractHelper
{
    /**
     * @param array  $categoryArray
     * @param string $categorySeparator
     *
     * @return array
     */
    public function parsingCategories($categoryArray = [], $categorySeparator = '')
    {
        $categoryArrayAfterParse = [];
        $iterator = 1;
        foreach ($categoryArray as $categories) {
            $explodeArray = explode($categorySeparator, $categories);
            foreach ($explodeArray as $categoryPath) {
                if (!$this->matchSearch($categoryArrayAfterParse, $categoryPath)) {
                    $categoryArrayAfterParse[] = trim($categoryPath);
                }
            }
            $iterator++;
        }

        return $categoryArrayAfterParse;
    }

    public function matchSearch($array, $value)
    {
        foreach ($array as $category) {
            if ($category == $value) {
                return true;
            }
        }

        return false;
    }
}
