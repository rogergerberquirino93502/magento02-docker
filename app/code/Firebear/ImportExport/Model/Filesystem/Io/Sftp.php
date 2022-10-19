<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Filesystem\Io;

use Magento\Framework\Exception\LocalizedException;

/**
 * Extended SFTP client
 */
class Sftp extends \Magento\Framework\Filesystem\Io\Sftp
{
    const SOURCE_LOCAL_FILE = 1;

    /**
     * @param array $args
     *
     * @throws LocalizedException
     */
    public function open(array $args = [])
    {
        if (!isset($args['timeout'])) {
            $args['timeout'] = self::REMOTE_TIMEOUT;
        }
        $host = $args['host'];
        $port = ($args['port']) ? $args['port'] : self::SSH2_PORT;
        $username = $args['username'];
        $password = $args['password'];
        if (class_exists('\phpseclib3\Net\SFTP')) {
            $this->_connection = new \phpseclib3\Net\SFTP($host, $port, $args['timeout']);
        } elseif (class_exists('\phpseclib\Net\SFTP')) {
            $this->_connection = new \phpseclib\Net\SFTP($host, $port, $args['timeout']);
        } else {
            throw new LocalizedException(
                __("Unable to open SFTP connection")
            );
        }
        if (!$this->_connection->login($username, $password)) {
            throw new LocalizedException(
                __("Unable to open SFTP connection as %1@%2", $username, $password)
            );
        }
    }

    /**
     * @param      $filename
     * @param      $source
     * @param null $mode
     *
     * @return mixed
     */
    public function write($filename, $source, $mode = null)
    {
        return $this->_connection->put($filename, $source, self::SOURCE_LOCAL_FILE);
    }

    /**
     * Creates a directory.
     *
     * @param string $dir
     * @param int $mode
     * @param bool $recursive
     * @return bool
     */
    public function mkdir($dir, $mode = -1, $recursive = true)
    {
        if ($this->_connection->is_dir($dir)) {
            $result = true;
        } else {
            $result = $this->_connection->mkdir($dir, $mode = -1, $recursive = true);
        }
        return $result;
    }

    /**
     * @param $filename
     * @return mixed
     */
    public function mdtm($filename)
    {
        return $this->_connection->filemtime($filename);
    }

    /**
     * @param null $grep
     * @return array
     */
    public function search($grep = null)
    {
        $fileList = [];
        foreach ($this->ls($grep) as $list) {
            if ($list['text'] === '.' || $list['text'] === '..') {
                continue;
            }
            $fileList[] = $list['id'];
        }
        return $fileList;
    }

    /**
     * @param $fileName
     * @param null $destination
     * @return bool
     */
    public function checkIsPath($fileName, $destination = null)
    {
        $result = false;
        if ($this->read($fileName, $destination)) {
            $result = true;
        }
        return $result;
    }
}
