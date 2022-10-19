<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\OneDrive;

use Exception;
use Firebear\ImportExport\Model\OneDrive\OneDrive;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Signin
 * @package Firebear\ImportExport\Controller\Adminhtml\OneDrive
 */
class SigninCallback extends Action
{

    /**
     * @var OneDrive
     */
    protected $oneDrive;

    public function __construct(
        Context $context,
        OneDrive $oneDrive
    ) {
        parent::__construct($context);
        $this->oneDrive = $oneDrive;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        try {
            $this->oneDrive->checkAuthState($this->getRequest()->getParam('state'));

            if ($this->getRequest()->getParam('error')) {
                throw new LocalizedException(__($this->getRequest()->getParam('error_description')));
            }

            $accessToken = $this->oneDrive->receiveAccessToken($this->getRequest()->getParam('code'));
            $this->oneDrive->saveAccessToken($accessToken);

            $message = __('Success! We have made changes to the module settings.');
        } catch (Exception $e) {
            $message = __('Error! Message: %1', $e->getMessage());
        }

        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);

        $content = '<p><b> ' . $message . '</b></p>
        <button onclick="closeAndRefreshParent()">Close window and refresh configs</button>
        <script>
            function closeAndRefreshParent() {
                window.opener.location.reload();
                window.close();
            }
        </script>';

        $result->setHeader('Content-Type', 'text/html');
        $result->setContents($content);
        return $result;
    }
}
