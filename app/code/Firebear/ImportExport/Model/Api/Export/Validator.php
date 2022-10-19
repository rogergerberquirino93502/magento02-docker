<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Api\Export;

use Firebear\ImportExport\Api\Data\ExportInterface;
use Firebear\ImportExport\Api\Export\ValidatorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;

/**
 * Chain of validators
 *
 * @api
 */
class Validator implements ValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var ValidatorInterface[]
     */
    private $validators;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param ValidatorInterface[] $validators
     * @throws LocalizedException
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        array $validators = []
    ) {
        foreach ($validators as $validator) {
            if (!$validator instanceof ValidatorInterface) {
                throw new LocalizedException(
                    __('Job Validator must implement ValidatorInterface.')
                );
            }
        }

        $this->validationResultFactory = $validationResultFactory;
        $this->validators = $validators;
    }

    /**
     * @inheritdoc
     */
    public function validate(ExportInterface $job)
    {
        $errors = [];
        foreach ($this->validators as $validator) {
            $validationResult = $validator->validate($job);

            if (!$validationResult->isValid()) {
                $errors = array_merge($errors, $validationResult->getErrors());
            }
        }
        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}
