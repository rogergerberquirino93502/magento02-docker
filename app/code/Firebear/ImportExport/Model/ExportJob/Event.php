<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

declare(strict_types=1);

namespace Firebear\ImportExport\Model\ExportJob;

use Firebear\ImportExport\Model\ResourceModel\ExportJob\Event as ResourceModel;
use Magento\Framework\Model\AbstractModel;
use Firebear\ImportExport\Api\Data\ExportEventInterface;

class Event extends AbstractModel implements ExportEventInterface
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * @return string
     */
    public function getEvent(): string
    {
        return (string) $this->getData(self::EVENT);
    }

    /**
     * @param string $name
     * @return void
     */
    public function setEvent(string $name)
    {
        $this->setData(self::EVENT, $name);
    }

    /**
     * @return int
     */
    public function getJobId(): int
    {
        return (int) $this->getData(self::JOB_ID);
    }

    /**
     * @param int $id
     * @return void
     */
    public function setJobId(int $id)
    {
        $this->setData(self::JOB_ID, $id);
    }
}
