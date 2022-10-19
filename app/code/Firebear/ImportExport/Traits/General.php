<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Traits;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

trait General
{
    /**
     * Json Serializer
     *
     * @var SerializerInterface
     */
    protected $serializer;

    protected $phpSerializer = null;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var ConsoleOutput
     */
    protected $output;

    /**
     * @param SerializerInterface $serializer
     * @return $this
     */
    public function setSerializer($serializer)
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * @return SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * @param $data
     * @return bool|string
     */
    public function phpSerialize($data)
    {
        return $this->getPhpSerializer()->serialize($data);
    }

    /**
     * @param $data
     * @return array|bool|float|int|mixed|string|null
     */
    public function phpUnserialize($data)
    {
        return $this->getPhpSerializer()->unserialize($data);
    }

    /**
     * @return \Magento\Framework\Serialize\Serializer\Serialize|mixed|null
     */
    public function getPhpSerializer()
    {
        if (!$this->phpSerializer) {
            $om = ObjectManager::getInstance();
            $this->phpSerializer = $om->get(\Magento\Framework\Serialize\Serializer\Serialize::class);
        }
        return $this->phpSerializer;
    }

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;

        return $this;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->_logger;
    }

    /**
     * @param $debugData
     * @param OutputInterface|null $output
     * @param null $type
     *
     * @return $this
     */
    public function addLogWriteln($debugData, OutputInterface $output = null, $type = null)
    {
        $text = $debugData;
        if ($debugData instanceof Phrase) {
            $text = $debugData->__toString();
        }

        switch ($type) {
            case 'error':
                $this->_logger->error($text);
                break;
            case 'warning':
                $this->_logger->warning($text);
                break;
            case 'debug':
                $this->_logger->debug($text);
                break;
            default:
                $this->_logger->info($text);
        }

        if ($output) {
            switch ($type) {
                case 'error':
                    $text = '<error>' . $text . '</error>';
                    break;
                case 'info':
                    $text = '<info>' . $text . '</info>';
                    break;
                default:
                    $text = '<comment>' . $text . '</comment>';
                    break;
            }
            $output->writeln($text, $output->getVerbosity());
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function setErrorMessages()
    {
        return true;
    }

    /**
     * @param ConsoleOutput $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * @return ConsoleOutput
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param array $data
     * @return array
     */
    public function customChangeData($data)
    {
        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    public function customBunchesData($data)
    {
        return $data;
    }
}
