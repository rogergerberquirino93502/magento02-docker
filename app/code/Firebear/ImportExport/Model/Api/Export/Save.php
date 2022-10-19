<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Api\Export;

use Firebear\ImportExport\Api\Data\ExportInterface;
use Firebear\ImportExport\Api\Export\SaveInterface;
use Firebear\ImportExport\Api\Export\ValidatorInterface;
use Firebear\ImportExport\Model\ResourceModel\ExportJob as JobResource;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Validation\ValidationException;

/**
 * Save command (Service Provider Interface - SPI)
 *
 * @api
 */
class Save implements SaveInterface
{
    /**
     * @var JobResource
     */
    private $resource;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * Initialize command
     *
     * @param JobResource $resource
     * @param ValidatorInterface $validator
     */
    public function __construct(
        JobResource $resource,
        ValidatorInterface $validator
    ) {
        $this->resource = $resource;
        $this->validator = $validator;
    }

    /**
     * Save job
     *
     * @param ExportInterface $job
     * @return ExportInterface
     * @throws CouldNotSaveException
     * @throws ValidationException
     */
    public function execute(ExportInterface $job)
    {
        $validationResult = $this->validator->validate($job);
        if (!$validationResult->isValid()) {
            throw new ValidationException(__('Validation Failed'), null, 0, $validationResult);
        }

        try {
            $this->resource->save($job);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the job: %1',
                $exception->getMessage()
            ));
        }
        return $job;
    }
}
