<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Export\Job\Validator;

use Firebear\ImportExport\Api\Data\ExportInterface;
use Firebear\ImportExport\Api\Export\ValidatorInterface;
use Firebear\ImportExport\Api\Export\GetListInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;

/**
 * Check that file path is valid
 */
class FilePathValidator implements ValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var GetListInterface
     */
    private $getListCommand;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param GetListInterface $getListCommand
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GetListInterface $getListCommand
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->getListCommand = $getListCommand;
    }

    /**
     * @inheritdoc
     */
    public function validate(ExportInterface $job)
    {
        $errors = [];
        $path = trim($this->getPath($job) ?? '', '/');
        $this->searchCriteriaBuilder->addFilter(ExportInterface::ENTITY_ID, $job->getId(), 'nin');
        $items = $this->getListCommand->execute(
            $this->searchCriteriaBuilder->create()
        )->getItems();

        foreach ($items as $item) {
            if ($this->isExistPath($item, $path)) {
                $errors[] = __(
                    'The Path "%path" was already exist. You need to specify the unique path.',
                    ['path' => $path]
                );
                break;
            }
        }
        return $this->validationResultFactory->create(['errors' => $errors]);
    }

    /**
     * @inheritdoc
     */
    private function getPath(ExportInterface $job)
    {
        $data = $job->getData('export_source');
        $entity = $data['export_source_entity'] ?? null;
        if ($entity == 'file') {
            return $data['export_source_file_file_path'];
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    private function isExistPath(ExportInterface $job, $path)
    {
        $existPath = $this->getPath($job);
        if ($existPath) {
            $existPath = trim($existPath, '/');
        }
        return $existPath && $path && $path == $existPath;
    }
}
