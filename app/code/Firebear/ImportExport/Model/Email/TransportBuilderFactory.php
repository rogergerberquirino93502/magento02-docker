<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Email;

use Magento\Framework\App\ProductMetadataInterface;
use Firebear\ImportExport\Model\Email\TransportBuilder\LaminasEmailTransportBuilder;
use Firebear\ImportExport\Model\Email\TransportBuilder\EmailTransportBuilder;
use Firebear\ImportExport\Model\Email\TransportBuilder\MailTransportBuilder;
use Firebear\ImportExport\Model\Email\TransportBuilder\TransportBuilder;

/**
 * Email Transport Builder Factory
 */
class TransportBuilderFactory
{
    /**
     * Transport Builder
     *
     * @var TransportBuilderInterface
     */
    protected $transportBuilder;

    /**
     * Initialize Factory
     *
     * @param ProductMetadataInterface $metadata
     * @param LaminasEmailTransportBuilder $laminasEmailTransportBuilder
     * @param EmailTransportBuilder $emailTransportBuilder
     * @param MailTransportBuilder $mailTransportBuilder
     * @param TransportBuilder $transportBuilder
     */
    public function __construct(
        ProductMetadataInterface $metadata,
        LaminasEmailTransportBuilder $laminasEmailTransportBuilder,
        EmailTransportBuilder $emailTransportBuilder,
        MailTransportBuilder $mailTransportBuilder,
        TransportBuilder $transportBuilder
    ) {
        $version = $metadata->getVersion();
        /* version_compare incorrectly compares some versions of magento,
        for example dev-2.4-develop */
        $version = preg_replace('#[a-z\-]#si', '', $version);

        if (version_compare($version, '2.4', '>=')) {
            $this->transportBuilder = $laminasEmailTransportBuilder;
        } elseif (version_compare($version, '2.3.3', '>=')) {
            $this->transportBuilder = $emailTransportBuilder;
        } elseif (version_compare($version, '2.2.7', '<=')) {
            $this->transportBuilder = $transportBuilder;
        } else {
            $this->transportBuilder = $mailTransportBuilder;
        }
    }

    /**
     * Get Transport Builder
     *
     * @return TransportBuilderInterface
     */
    public function create()
    {
        return $this->transportBuilder;
    }
}
