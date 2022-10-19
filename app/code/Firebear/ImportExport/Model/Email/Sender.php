<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Email;

use Firebear\ImportExport\Api\Data\AbstractInterface;
use Firebear\ImportExport\Logger\Logger as ProcessLogger;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Store\Model\Store;
use Symfony\Component\Console\Output\ConsoleOutput;
use Psr\Log\LoggerInterface;

/**
 * Email Sender
 */
class Sender
{
    /**
     * Bit mask 0001
     */
    const FAILED = 1 << 0;

    /**
     * Bit mask 0010
     */
    const SUCCESS = 1 << 1;

    /**
     * Max Filesize
     */
    const MAX_FILESIZE = 80000000;

    /**
     * Transport Builder Factory
     *
     * @var TransportBuilderFactory
     */
    protected $transportBuilderFactory;

    /**
     * Inline Translation
     *
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * Process Logger
     *
     * @var ProcessLogger
     */
    protected $processLogger;

    /**
     * @var ConsoleOutput
     */
    protected $output;

    /**
     * Reader
     *
     * @var ReadInterface
     */
    protected $dir;

    /**
     * Filesystem
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Entity list
     *
     * @var array
     */
    protected $options = [];

    /**
     * Initialize Sender
     *
     * @param TransportBuilderFactory $transportBuilderFactory
     * @param StateInterface $inlineTranslation
     * @param ProcessLogger $processLogger
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     * @param ConsoleOutput $output
     * @param array $options
     */
    public function __construct(
        TransportBuilderFactory $transportBuilderFactory,
        StateInterface $inlineTranslation,
        ProcessLogger $processLogger,
        Filesystem $filesystem,
        LoggerInterface $logger,
        ConsoleOutput $output,
        array $options = []
    ) {
        $this->transportBuilderFactory = $transportBuilderFactory;
        $this->inlineTranslation = $inlineTranslation;
        $this->processLogger = $processLogger;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->output = $output;
        $this->options = $options;
    }

    /**
     * Send email
     *
     * @param AbstractInterface $job
     * @param string $file
     * @param int $status
     * @return void
     */
    public function sendEmail(AbstractInterface $job, $file, $status)
    {
        $data = $job->getSourceData();
        if (!isset($data['email_type'])) {
            $data = $job->getExportSource();
        }

        if (null !== $data && $this->isSend($data, $status)) {
            $this->processLogger->setFileName($file);
            $this->addLogWriteln(__('sending email'));
            $this->inlineTranslation->suspend();

            $transportBuilder = $this->transportBuilderFactory->create();
            $transportBuilder->setTemplateIdentifier(
                $data['template'] ?? 'import_export_notification_email'
            )->setTemplateOptions(
                $this->getTemplateOptions()
            )->setTemplateVars(
                $this->getTemplateVars($job, $data, $status)
            )->setFromByScope(
                $data['sender']
            )->addTo(
                $data['receiver']
            );

            if ($this->isAllowAttachment($data)) {
                $this->addAttachment($transportBuilder, $file);
            }

            if (!empty($data['copy'])) {
                $copyEmails = explode(',', $data['copy']);
                $method = $data['copy_method'] ?? 'bcc';
                foreach ($copyEmails as $email) {
                    if ($method == 'bcc') {
                        $transportBuilder->addBcc($email);
                    } else {
                        $transportBuilder->addTo($email);
                    }
                }
            }

            try {
                $transport = $transportBuilder->getTransport();
                $transport->sendMessage();
            } catch (MailException $e) {
                $this->logger->error($e->getMessage());
            }

            $this->inlineTranslation->resume();
        }
    }

    /**
     * Add attachment to email
     *
     * @param TransportBuilderInterface $transportBuilder
     * @param string $file
     * @return void
     */
    protected function addAttachment(TransportBuilderInterface $transportBuilder, $file)
    {
        $transportBuilder->addAttachment($this->readFile(), $file . '.txt', 'text/plain');
    }

    /**
     * Retrieve template options
     *
     * @return array
     */
    protected function getTemplateOptions()
    {
        return [
            'area' => Area::AREA_ADMINHTML,
            'store' => Store::DEFAULT_STORE_ID
        ];
    }

    /**
     * Retrieve template vars
     *
     * @param AbstractInterface $job
     * @param array $data
     * @param int $status
     * @return array
     */
    protected function getTemplateVars(AbstractInterface $job, array $data, $status)
    {
        $operation = isset($data['import_source']) ? 'import' : 'export';
        return [
            'job' => $job,
            'status' => $status ? 'Successful' : 'Failed',
            'entity' => $this->getEntity($operation, $job->getEntity()),
            'operation' => ucfirst($operation)
        ];
    }

    /**
     * Retrieve import/export entity name
     *
     * @param string $operation
     * @param string $entityType
     * @return string
     */
    protected function getEntity($operation, $entityType)
    {
        if (isset($this->options[$operation])) {
            $options = $this->options[$operation]->toOptionArray();
            foreach ($options as $option) {
                if ($option['value'] == $entityType) {
                    return (string)$option['label'];
                }
            }
        }
        return $entityType;
    }

    /**
     * @inheritDoc
     *
     * @param array $data
     * @param bool $status
     * @return bool
     */
    protected function isSend($data, $status)
    {
        return ($status && $this->isSendSuccess($data)) ||
            (!$status && $this->isSendFailed($data));
    }

    /**
     * @inheritDoc
     *
     * @param array $data
     * @return bool
     */
    protected function isSendFailed($data)
    {
        return $data['email_type'] & self::FAILED;
    }

    /**
     * @inheritDoc
     *
     * @param array $data
     * @return bool
     */
    protected function isSendSuccess($data)
    {
        return $data['email_type'] & self::SUCCESS;
    }

    /**
     * @inheritDoc
     *
     * @param array $data
     * @return bool
     */
    protected function isAllowAttachment($data)
    {
        if (!empty($data['is_attach']) &&
            $this->getDirectory()->isReadable($this->getFilePath())
        ) {
            /* check filesize */
            $size = $this->getFileSize();
            if ($size && $size < self::MAX_FILESIZE) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieve File Path
     *
     * @return string
     */
    protected function getFilePath()
    {
        return $this->processLogger->getFileName();
    }

    /**
     * Retrieve File Size
     *
     * @return string|null
     */
    protected function getFileSize()
    {
        $stat = $this->getDirectory()->stat($this->getFilePath());
        return $stat['size'] ?? null;
    }

    /**
     * Retrieve file contents
     *
     * @return string
     */
    protected function readFile()
    {
        return (string)strip_tags(
            $this->getDirectory()->readFile($this->getFilePath())
        );
    }

    /**
     * Create an instance of directory with read permissions
     *
     * @return ReadInterface
     */
    protected function getDirectory()
    {
        if (null === $this->dir) {
            $this->dir = $this->filesystem->getDirectoryRead(DirectoryList::LOG);
        }
        return $this->dir;
    }

    /**
     * Add message to log
     *
     * @param string $debugData
     * @return void
     */
    protected function addLogWriteln($message)
    {
        $this->processLogger->info($message);
        if ($this->output) {
            $this->output->writeln('<info>' . $message . '</info>');
        }
    }
}
