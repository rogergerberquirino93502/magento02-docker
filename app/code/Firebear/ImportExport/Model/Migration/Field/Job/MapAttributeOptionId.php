<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\Field\Job;

class MapAttributeOptionId extends MapAttributeId
{
    /**
     * @var array
     */
    protected $attributeOptionIdMapping = [];

    /**
     * @return array
     */
    protected function fetchAttributeOptionIdMapping()
    {
        $attributeIdMapping = $this->fetchAttributeIdMapping();
        $attributeOptionIdMapping = [];

        /**
         * Map option ids by value in default store
         */
        foreach ($attributeIdMapping as $sourceAttributeId => $destinationAttributeId) {
            $sourceOptionsSelect = $this->dbConnection->getSourceChannel()
                ->select()
                ->from('eav_attribute_option', ['option_id'])
                ->joinLeft(
                    'eav_attribute_option_value',
                    join(
                        ' AND ',
                        [
                            'eav_attribute_option.option_id = eav_attribute_option_value.option_id',
                            'eav_attribute_option_value.store_id = 0'
                        ]
                    ),
                    ['value']
                )
                ->where('eav_attribute_option.attribute_id = ?', $sourceAttributeId);

            $sourceOptions = $this->dbConnection->getSourceChannel()->fetchPairs($sourceOptionsSelect);
            $sourceOptions = array_flip($sourceOptions);

            $destinationOptionsSelect = $this->dbConnection->getDestinationChannel()
                ->select()
                ->from('eav_attribute_option', ['option_id'])
                ->joinLeft(
                    'eav_attribute_option_value',
                    join(
                        ' AND ',
                        [
                            'eav_attribute_option.option_id = eav_attribute_option_value.option_id',
                            'eav_attribute_option_value.store_id = 0'
                        ]
                    ),
                    ['value']
                )
                ->where('eav_attribute_option.attribute_id = ?', $destinationAttributeId);

            $destinationOptions = $this->dbConnection->getDestinationChannel()->fetchPairs($destinationOptionsSelect);
            $destinationOptions = array_flip($destinationOptions);

            foreach ($sourceOptions as $value => $optionId) {
                if (isset($destinationOptions[$value])) {
                    $destinationOptionId = $destinationOptions[$value];
                } else {
                    $destinationOptionId = null;
                }

                $attributeOptionIdMapping[$sourceAttributeId][$optionId] = $destinationOptionId;
            }
        }

        return $attributeOptionIdMapping;
    }

    /**
     * @inheritdoc
     */
    public function job(
        $sourceField,
        $sourceValue,
        $destinationFiled,
        $destinationValue,
        $sourceDataRow
    ) {
        if (empty($this->attributeOptionIdMapping)) {
            $this->attributeOptionIdMapping = $this->fetchAttributeOptionIdMapping();
        }

        $attributeId = $sourceDataRow['attribute_id'];

        if (!isset($this->attributeOptionIdMapping[$attributeId])) {
            return $sourceValue;
        }

        // We might have multiple values separated by ',' for multiselect attributes
        $sourceValues = explode(',', $sourceValue);
        $destinationValues = [];

        foreach ($sourceValues as $sourceValue) {
            if (isset($this->attributeOptionIdMapping[$attributeId][$sourceValue])) {
                $destinationValues[] = $this->attributeOptionIdMapping[$attributeId][$sourceValue];
            }
        }

        if (!empty($destinationValues)) {
            return implode(',', $destinationValues);
        }

        return null;
    }
}
