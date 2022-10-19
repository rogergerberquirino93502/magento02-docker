<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Api\Data;

/**
 * Interface DataSourceReplacingInterface
 * @package Firebear\ImportExport\Api\Data
 */
interface DataSourceReplacingInterface
{
    const DATA_SOURCE_REPLACING_ATTRIBUTE = 'data_source_replacing_attribute';
    const DATA_SOURCE_REPLACING_ENTITY_TYPE = 'data_source_replacing_entity_type';
    const DATA_SOURCE_REPLACING_TARGET = 'data_source_replacing_target';
    const DATA_SOURCE_REPLACING_IS_CASE_SENSITIVE = 'data_source_replacing_is_case_sensitive';
    const DATA_SOURCE_REPLACING_FIND = 'data_source_replacing_find';
    const DATA_SOURCE_REPLACING_REPLACE = 'data_source_replacing_replace';
    const SOURCE_DATA_REPLACING = 'source_data_replacing';
}
