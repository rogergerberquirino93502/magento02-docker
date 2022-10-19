<?php

namespace Firebear\ImportExport\Test\Integration\Helpers;

class JobFixturesManager
{

    /**
     * @var \Firebear\ImportExport\Model\JobFactory
     */
    private $jobFactory;
    /**
     * @var \Firebear\ImportExport\Api\JobRepositoryInterface
     */
    private $jobRepository;

    public function __construct(
        \Firebear\ImportExport\Model\JobFactory $jobFactory,
        \Firebear\ImportExport\Api\JobRepositoryInterface $jobRepository
    ) {
        $this->jobFactory = $jobFactory;
        $this->jobRepository = $jobRepository;
    }

    /**
     * @return array
     */
    public function getDefaultJobData()
    {
        return include(__DIR__ . '/../_files/defaultJobParams.php');
    }

    /**
     * @param array $overriddenData
     * @return \Firebear\ImportExport\Api\Data\ImportInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function createImportJob($overriddenData = [])
    {
        $defaultData = $this->getDefaultJobData();
        $data = array_replace_recursive($defaultData, $overriddenData);

        $model = $this->jobFactory->create();
        $model->addData($data);
        return $this->jobRepository->save($model);
    }
}
