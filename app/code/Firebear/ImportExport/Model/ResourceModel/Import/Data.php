<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\ResourceModel\Import;

use Magento\Framework\Exception\ValidatorException;

/**
 * Class Data
 *
 * @package Firebear\ImportExport\Model\ResourceModel\Import
 */
class Data extends \Magento\ImportExport\Model\ResourceModel\Import\Data
{
    /**
     * @var int
     */
    protected $jobId;

    /**
     * @var string
     */
    protected $file;

    /**
     * @var int
     */
    protected $offset = 0;

    protected function _construct()
    {
        $this->_init('firebear_importexport_importdata', 'id');
    }

    /**
     * @param string $code
     * @return mixed|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getUniqueColumnData($code)
    {
        if ($this->getJobId()) {
            $connection = $this->getConnection();
            $values = array_unique(
                $connection->fetchCol(
                    $connection->select()->from($this->getMainTable(), [$code])
                        ->where('job_id = ?', $this->getJobId())
                )
            );
            return $values[0];
        } else {
            return parent::getUniqueColumnData($code);
        }
    }

    /**
     * Retrieve an external iterator
     *
     * @return \Iterator
     */
    public function getIterator()
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from($this->getMainTable(), ['data'])
            ->order('id ASC')
            ->limit(1, $this->getOffset());

        if ($this->getJobId()) {
            $select->where('job_id=?', $this->getJobId());
        }

        if ($this->getFile()) {
            $select->where('file=?', $this->getFile());
        }

        $stmt = $connection->query($select);
        $stmt->setFetchMode(\Zend_Db::FETCH_NUM);
        if ($stmt instanceof \IteratorAggregate) {
            $iterator = $stmt->getIterator();
        } else {
            // Statement doesn't support iterating, so fetch all records and create iterator ourself
            $rows = $stmt->fetchAll();
            $iterator = new \ArrayIterator($rows);
        }

        return $iterator;
    }

    /**
     * Clean all bunches from table.
     *
     * @return int
     * @throws \Exception
     */
    public function cleanBunches()
    {
        $where = '';
        if ($this->getJobId()) {
            $where = ['job_id=?' => $this->getJobId()];
        }
        return $this->getConnection()->delete($this->getMainTable(), $where);
    }

    /**
     * @param $entity
     * @param $behavior
     * @param null $jobId
     * @param null $file
     * @param array $data
     * @return int
     */
    public function saveBunches(
        $entity,
        $behavior,
        $jobId = null,
        $file = null,
        array $data = []
    ) {
        $encodedData = $this->jsonHelper->jsonEncode($data);
        if (json_last_error() !== JSON_ERROR_NONE && empty($encodedData)) {
            throw new ValidatorException(
                __('Error in data: ' . json_last_error_msg())
            );
        }

        return $this->getConnection()->insert(
            $this->getMainTable(),
            [
                'behavior' => $behavior,
                'entity' => $entity,
                'job_id' => $jobId,
                'file' => $file,
                'data' => $encodedData
            ]
        );
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param string $file
     * @return $this
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @return int
     */
    public function getJobId()
    {
        return $this->jobId;
    }

    /**
     * @param int $jobId
     * @return $this
     */
    public function setJobId($jobId)
    {
        $this->jobId = $jobId;

        return $this;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @return array
     */
    public function getCounts($jobId, $file)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from($this->getMainTable(), ['count(id)'])->order('id ASC');
        $select->where('job_id=?', $jobId);
        $select->where('file=?', $file);
        $stmt = $connection->query($select);
        $stmt->setFetchMode(\Zend_Db::FETCH_NUM);

        return $stmt->fetch();
    }

    /**
     * @return integer
     */
    public function getCount($jobId, $file)
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), ['COUNT(*)'])
            ->where('job_id=?', $jobId)
            ->where('file=?', $file);

        return $this->getConnection()->fetchOne($select);
    }
}
