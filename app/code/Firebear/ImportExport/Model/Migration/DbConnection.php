<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ResourceConnection\ConnectionFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Class DbConnection
 * @package Firebear\ImportExport\Model\Migration
 */
class DbConnection
{
    /**
     * @var AdapterInterface
     */
    protected $source;

    /**
     * @var AdapterInterface
     */
    protected $destination;

    /**
     * @param ConnectionFactory $connectionFactory
     * @param ResourceConnection $resourceConnection
     * @param Config $config
     */
    public function __construct(
        ConnectionFactory $connectionFactory,
        ResourceConnection $resourceConnection,
        Config $config
    ) {
        $this->source = $connectionFactory->create([
            'host' => $config->getHost(),
            'dbname' => $config->getDatabase(),
            'username' => $config->getUsername(),
            'password' => $config->getPassword(),
        ]);

        $this->destination = $resourceConnection->getConnection();
    }

    /**
     * @return AdapterInterface
     */
    public function getSourceChannel()
    {
        return $this->source;
    }

    /**
     * @return AdapterInterface
     */
    public function getDestinationChannel()
    {
        return $this->destination;
    }
}
