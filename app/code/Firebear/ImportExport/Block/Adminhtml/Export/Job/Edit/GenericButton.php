<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Block\Adminhtml\Export\Job\Edit;

use Magento\Backend\Block\Widget\Context;
use Firebear\ImportExport\Api\ExportJobRepositoryInterface;
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
     * @var exportJobRepositoryInterface
     */
    protected $exportJobRepository;

    /**
     * GenericButton constructor.
     *
     * @param Context                      $context
     * @param ExportJobRepositoryInterface $exportJobRepository
     */
    public function __construct(
        Context $context,
        ExportJobRepositoryInterface $exportJobRepository
    ) {
        $this->context = $context;
        $this->exportJobRepository = $exportJobRepository;
    }

    public function getExportJobId()
    {
        try {
            return $this->exportJobRepository->getById(
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
