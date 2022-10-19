<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Block\Adminhtml\Job\Edit;

use Magento\Backend\Block\Widget\Context;
use Firebear\ImportExport\Api\JobRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class GenericButton
 */
class GenericButton
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var jobRepositoryInterface
     */
    protected $jobRepository;

    /**
     * GenericButton constructor.
     *
     * @param Context                $context
     * @param JobRepositoryInterface $jobRepository
     */
    public function __construct(
        Context $context,
        jobRepositoryInterface $jobRepository
    ) {
        $this->context = $context;
        $this->jobRepository = $jobRepository;
    }

    /**
     * @return null
     */
    public function getExportJobId()
    {
        try {
            return $this->jobRepository->getById(
                $this->context->getRequest()->getParam('entity_id')
            )->getId();
        } catch (NoSuchEntityException $e) {
        }

        return null;
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
