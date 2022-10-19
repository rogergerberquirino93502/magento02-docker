<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Email\TransportBuilder;

use Firebear\ImportExport\Model\Email\AddressConverter;
use Firebear\ImportExport\Model\Email\EmailMessageInterfaceFactory;
use Firebear\ImportExport\Model\Email\MimeMessageInterfaceFactory;
use Firebear\ImportExport\Model\Email\MimePartInterfaceFactory;
use Firebear\ImportExport\Model\Email\TransportBuilderInterface;
use Magento\Framework\App\TemplateTypesInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\Exception\InvalidArgumentException;
use Magento\Framework\Mail\TemplateInterface;
use Magento\Framework\Mail\Template\FactoryInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\Phrase;
use Laminas\Mime\Mime;
use Laminas\Mime\Part;
use Magento\Framework\App\ObjectManager;

/**
 * Email Transport Builder
 */
class LaminasEmailTransportBuilder implements TransportBuilderInterface
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
     * @inheritDoc
     */
    protected $message;

    /**
     * Sender resolver
     *
     * @var SenderResolverInterface
     */
    protected $senderResolver;

    /**
     * Transport Factory
     *
     * @var TransportInterfaceFactory
     */
    protected $mailTransportFactory;

    /**
     * Param that used for storing all message data until it will be used
     *
     * @var array
     */
    protected $messageData = [];

    /**
     * Email Message Factory
     *
     * @var EmailMessageInterfaceFactory
     */
    protected $emailMessageInterfaceFactory;

    /**
     * Mime Message Factory
     *
     * @var MimeMessageInterfaceFactory
     */
    protected $mimeMessageInterfaceFactory;

    /**
     * Mime Part Factory
     *
     * @var MimePartInterfaceFactory
     */
    protected $mimePartInterfaceFactory;

    /**
     * Email Address Converter
     *
     * @var AddressConverter
     */
    protected $addressConverter;

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
     * @param EmailMessageInterfaceFactory $emailMessageInterfaceFactory
     * @param MimeMessageInterfaceFactory $mimeMessageInterfaceFactory
     * @param MimePartInterfaceFactory $mimePartInterfaceFactory
     * @param AddressConverter $addressConverter
     */
    public function __construct(
        FactoryInterface $templateFactory,
        SenderResolverInterface $senderResolver,
        TransportInterfaceFactory $mailTransportFactory,
        EmailMessageInterfaceFactory $emailMessageInterfaceFactory,
        MimeMessageInterfaceFactory $mimeMessageInterfaceFactory,
        MimePartInterfaceFactory $mimePartInterfaceFactory,
        AddressConverter $addressConverter
    ) {
        $this->templateFactory = $templateFactory;
        $this->senderResolver = $senderResolver;
        $this->mailTransportFactory = $mailTransportFactory;
        $this->emailMessageInterfaceFactory = $emailMessageInterfaceFactory;
        $this->mimeMessageInterfaceFactory = $mimeMessageInterfaceFactory;
        $this->mimePartInterfaceFactory = $mimePartInterfaceFactory;
        $this->addressConverter = $addressConverter;
    }

    /**
     * Add cc address
     *
     * @param array|string $address
     * @param string $name
     *
     * @return $this
     */
    public function addCc($address, $name = '')
    {
        $this->addAddressByType('cc', $address, $name);

        return $this;
    }

    /**
     * Add to address
     *
     * @param array|string $address
     * @param string $name
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function addTo($address, $name = '')
    {
        $this->addAddressByType('to', $address, $name);

        return $this;
    }

    /**
     * Add bcc address
     *
     * @param array|string $address
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function addBcc($address)
    {
        $this->addAddressByType('bcc', $address);

        return $this;
    }

    /**
     * Set Reply-To Header
     *
     * @param string $email
     * @param string|null $name
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setReplyTo($email, $name = null)
    {
        $this->addAddressByType('replyTo', $email, $name);

        return $this;
    }

    /**
     * Set mail from address by scopeId
     *
     * @param string|array $from
     * @param string|int $scopeId
     *
     * @return $this
     * @throws InvalidArgumentException
     * @throws MailException
     */
    public function setFromByScope($from, $scopeId = null)
    {
        $result = $this->senderResolver->resolve($from, $scopeId);
        $this->addAddressByType('from', $result['email'], $result['name']);

        return $this;
    }

    /**
     * Set template identifier
     *
     * @param string $templateIdentifier
     *
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
     *
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
     *
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
     * @throws LocalizedException
     */
    public function getTransport()
    {
        try {
            $this->prepareMessage();
            $mailTransport = $this->mailTransportFactory->create(
                ['message' => clone $this->message]
            );
        } finally {
            $this->reset();
        }

        return $mailTransport;
    }

    /**
     * Reset object state
     *
     * @return $this
     */
    protected function reset()
    {
        $this->messageData = [];
        $this->attachments = [];
        $this->templateIdentifier = null;
        $this->templateVars = null;
        $this->templateOptions = null;
        return $this;
    }

    /**
     * Get template
     *
     * @return TemplateInterface
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
     * @throws LocalizedException if template type is unknown
     */
    protected function prepareMessage()
    {
        $template = $this->getTemplate();
        $content = $template->processTemplate();
        switch ($template->getType()) {
            case TemplateTypesInterface::TYPE_TEXT:
                $part['type'] = 'text/plain';
                break;

            case TemplateTypesInterface::TYPE_HTML:
                $part['type'] = 'text/html';
                break;

            default:
                throw new LocalizedException(
                    new Phrase('Unknown template type')
                );
        }

        $mimePart = $this->mimePartInterfaceFactory->create(['content' => (string)$content]);
        $parts = count($this->attachments) ? array_merge([$mimePart], $this->attachments) : [$mimePart];
        $this->messageData['body'] = $this->mimeMessageInterfaceFactory->create(
            ['parts' => $parts]
        );

        $this->messageData['subject'] = html_entity_decode(
            (string)$template->getSubject(),
            ENT_QUOTES
        );

        $this->message = $this->emailMessageInterfaceFactory->create($this->messageData);

        return $this;
    }

    /**
     * Handles possible incoming types of email (string or array)
     *
     * @param string $addressType
     * @param string|array $email
     * @param string $name
     *
     * @return void
     * @throws InvalidArgumentException
     */
    protected function addAddressByType(string $addressType, $email, $name = null)
    {
        if (is_string($email)) {
            $this->messageData[$addressType][] = $this->addressConverter->convert($email, $name);
            return;
        }
        $convertedAddressArray = $this->addressConverter->convertMany($email);
        if (isset($this->messageData[$addressType])) {
            $this->messageData[$addressType] = array_merge(
                $this->messageData[$addressType],
                $convertedAddressArray
            );
        }
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

        $attachmentPart = new \Laminas\Mime\Part($content);
        $attachmentPart->type = $fileType;
        $attachmentPart->fileName = $fileType;
        $attachmentPart->disposition = Mime::DISPOSITION_ATTACHMENT;
        $attachmentPart->encoding = Mime::ENCODING_BASE64;
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
