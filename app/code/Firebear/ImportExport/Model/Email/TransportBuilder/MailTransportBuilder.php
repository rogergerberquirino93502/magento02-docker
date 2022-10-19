<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Email\TransportBuilder;

use Firebear\ImportExport\Model\Email\TransportBuilderInterface;
use Magento\Framework\App\TemplateTypesInterface;
use Magento\Framework\Mail\Template\FactoryInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\MessageInterfaceFactory;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Zend\Mime\Mime;
use Zend\Mime\Part;
use Magento\Framework\App\ObjectManager;

/**
 * Mail Transport Builder
 */
class MailTransportBuilder implements TransportBuilderInterface
{
    /**
     * Template Identifier
     *
     * @var string
     */
    protected $templateIdentifier;

    /**
     * Template Model
     *
     * @var string
     */
    protected $templateModel;

    /**
     * Template Variables
     *
     * @var array
     */
    protected $templateVars;

    /**
     * Template Options
     *
     * @var array
     */
    protected $templateOptions;

    /**
     * Mail from address
     *
     * @var string|array
     */
    protected $from;

    /**
     * Mail Transport
     *
     * @var TransportInterface
     */
    protected $transport;

    /**
     * Template Factory
     *
     * @var FactoryInterface
     */
    protected $templateFactory;

    /**
     * Message
     *
     * @var MessageInterface
     */
    protected $message;

    /**
     * Sender resolver
     *
     * @var SenderResolverInterface
     */
    protected $senderResolver;

    /**
     * @var TransportInterfaceFactory
     */
    protected $mailTransportFactory;

    /**
     * @var MessageInterfaceFactory
     */
    protected $messageFactory;

    /**
     * Email Attachments
     *
     * @var array
     */
    protected $attachments = [];

    /**
     * Part Factory
     *
     * @var mixed
     */
    protected $partFactory;

    /**
     * Initialize Builder
     *
     * @param FactoryInterface $templateFactory
     * @param SenderResolverInterface $senderResolver
     * @param TransportInterfaceFactory $mailTransportFactory
     * @param MessageInterfaceFactory $messageFactory
     */
    public function __construct(
        FactoryInterface $templateFactory,
        SenderResolverInterface $senderResolver,
        TransportInterfaceFactory $mailTransportFactory,
        MessageInterfaceFactory $messageFactory
    ) {
        $this->templateFactory = $templateFactory;
        $this->senderResolver = $senderResolver;
        $this->mailTransportFactory = $mailTransportFactory;
        $this->messageFactory = $messageFactory;
        $this->message = $this->messageFactory->create();
    }

    /**
     * Add cc address
     *
     * @param array|string $address
     * @param string $name
     * @return $this
     */
    public function addCc($address, $name = '')
    {
        $this->message->addCc($address, $name);
        return $this;
    }

    /**
     * Add to address
     *
     * @param array|string $address
     * @param string $name
     * @return $this
     */
    public function addTo($address, $name = '')
    {
        $this->message->addTo($address, $name);
        return $this;
    }

    /**
     * Add bcc address
     *
     * @param array|string $address
     * @return $this
     */
    public function addBcc($address)
    {
        $this->message->addBcc($address);
        return $this;
    }

    /**
     * Set Reply-To Header
     *
     * @param string $email
     * @param string|null $name
     * @return $this
     */
    public function setReplyTo($email, $name = null)
    {
        $this->message->setReplyTo($email, $name);
        return $this;
    }

    /**
     * Set mail from address
     *
     * @param string|array $from
     * @return $this
     */
    public function setFrom($from)
    {
        $this->from = $from;
        return $this;
    }

    /**
     * Set mail from address by scopeId
     *
     * @param string|array $from
     * @param string|int $scopeId
     */
    public function setFromByScope($from, $scopeId = null)
    {
        $this->from = $this->senderResolver->resolve($from, $scopeId);
        return $this;
    }

    /**
     * Set template identifier
     *
     * @param string $templateIdentifier
     * @return $this
     */
    public function setTemplateIdentifier($templateIdentifier)
    {
        $this->templateIdentifier = $templateIdentifier;
        return $this;
    }

    /**
     * Set template model
     *
     * @param string $templateModel
     * @return $this
     */
    public function setTemplateModel($templateModel)
    {
        $this->templateModel = $templateModel;
        return $this;
    }

    /**
     * Set template vars
     *
     * @param array $templateVars
     * @return $this
     */
    public function setTemplateVars($templateVars)
    {
        $this->templateVars = $templateVars;
        return $this;
    }

    /**
     * Set template options
     *
     * @param array $templateOptions
     * @return $this
     */
    public function setTemplateOptions($templateOptions)
    {
        $this->templateOptions = $templateOptions;
        return $this;
    }

    /**
     * Get mail transport
     *
     * @return TransportInterface
     */
    public function getTransport()
    {
        $this->prepareMessage();
        $mailTransport = $this->mailTransportFactory->create(
            ['message' => clone $this->message]
        );
        $this->reset();

        return $mailTransport;
    }

    /**
     * Reset object state
     *
     * @return $this
     */
    protected function reset()
    {
        $this->message = $this->messageFactory->create();
        $this->attachments = [];
        $this->templateIdentifier = null;
        $this->templateVars = null;
        $this->templateOptions = null;
        $this->from = null;
        return $this;
    }

    /**
     * Get template
     *
     * @return \Magento\Framework\Mail\TemplateInterface
     */
    protected function getTemplate()
    {
        return $this->templateFactory->get($this->templateIdentifier, $this->templateModel)
            ->setVars($this->templateVars)
            ->setOptions($this->templateOptions);
    }

    /**
     * Prepare message
     *
     * @return $this
     */
    protected function prepareMessage()
    {
        $template = $this->getTemplate();
        $types = [
            TemplateTypesInterface::TYPE_TEXT => MessageInterface::TYPE_TEXT,
            TemplateTypesInterface::TYPE_HTML => MessageInterface::TYPE_HTML,
        ];

        $body = $template->processTemplate();
        $this->message->setMessageType($types[$template->getType()])
            ->setBody($body)
            ->setSubject(html_entity_decode($template->getSubject(), ENT_QUOTES));

        if ($this->from) {
            if (is_string($this->from)) {
                $this->from = $this->senderResolver->resolve(
                    $this->from,
                    $template->getDesignConfig()->getStore()
                );
            }
            $this->message->setFrom($this->from['email'], $this->from['name']);
        }

        if (0 < count($this->attachments)) {
            $body = $this->message->getBody();
            foreach ($this->attachments as $part) {
                $body->addPart($part);
            }
            $this->message->setBody($body);
        }

        return $this;
    }

    /**
     * Add attachment to email
     *
     * @param string $content
     * @param string $fileName
     * @param string $fileType
     * @return $this
     */
    public function addAttachment($content, $fileName, $fileType)
    {
        if ($attachmentPart = $this->createPart()) {
            $attachmentPart->setContent($content)
                ->setType($fileType)
                ->setFileName($fileName)
                ->setDisposition(Mime::DISPOSITION_ATTACHMENT)
                ->setEncoding(Mime::ENCODING_BASE64);

            $this->attachments[] = $attachmentPart;
            return $this;
        }

        $attachmentPart = new \Zend_Mime_Part($content);
        $attachmentPart->type = $fileType;
        $attachmentPart->fileName = $fileType;
        $attachmentPart->disposition = \Zend_Mime::DISPOSITION_ATTACHMENT;
        $attachmentPart->encoding = \Zend_Mime::ENCODING_BASE64;
        $this->attachments[] = $attachmentPart;
        return $this;
    }

    /**
     * @return mixed
     */
    protected function createPart()
    {
        if (class_exists(Part::class)) {
            return ObjectManager::getInstance()->create(Part::class);
        }

        return false;
    }
}
