<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\SearchSynonyms;

use Magento\Search\Api\Data\SynonymGroupInterface;

interface SynonymsInterface extends SynonymGroupInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const GROUP_ID   = 'group_id';
    const SYNONYMS   = 'synonyms';
    const STORE_ID   = 'store_id';
    const WEBSITE_ID = 'website_id';
    /**#@-*/
}
