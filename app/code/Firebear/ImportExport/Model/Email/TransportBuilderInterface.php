<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Email;

use Magento\Framework\Mail\TransportInterface;

/**
 * Email Transport Builder Interface
 */
interface TransportBuilderInterface
{
    /**
     * Add cc address
     *
     * @param array|string $address
     * @param string $name
     *
     * @return $this
     */
    public function addCc($address, $name = '');

    /**
     * Add to address
     *
     * @param array|string $address
     * @param string $name
     *
     * @return $this
     */
    public function addTo($address, $name = '');

    /**
     * Add bcc address
     *
     * @param array|string $address
     *
     * @return $this
     */
    public function addBcc($address);

    /**
     * Set Reply-To Header
     *
     * @param string $email
     * @param string|null $name
     *
     * @return $this
     */
    public function setReplyTo($email, $name = null);

    /**
     * Set mail from address by scopeId
     *
     * @param string|array $from
     * @param string|int $scopeId
     *
     * @return $this
     */
    public function setFromByScope($from, $scopeId = null);

    /**
     * Set template identifier
     *
     * @param string $templateIdentifier
     *
     * @return $this
     */
    public function setTemplateIdentifier($templateIdentifier);

    /**
     * Set template model
     *
     * @param string $templateModel
     *
     * @return $this
     */
    public function setTemplateModel($templateModel);

    /**
     * Set template vars
     *
     * @param array $templateVars
     *
     * @return $this
     */
    public function setTemplateVars($templateVars);

    /**
     * Set template options
     *
     * @param array $templateOptions
     * @return $this
     */
    public function setTemplateOptions($templateOptions);

    /**
     * Get mail transport
     *
     * @return TransportInterface
     */
    public function getTransport();

    /**
     * Add attachment to email
     *
     * @param string $content
     * @param string $fileName
     * @param string $fileType
     * @return $this
     */
    public function addAttachment($content, $fileName, $fileType);
}
