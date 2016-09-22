<?php
/**
 * 2007-2016 [PagSeguro Internet Ltda.]
 *
 * NOTICE OF LICENSE
 *
 *Licensed under the Apache License, Version 2.0 (the "License");
 *you may not use this file except in compliance with the License.
 *You may obtain a copy of the License at
 *
 *http://www.apache.org/licenses/LICENSE-2.0
 *
 *Unless required by applicable law or agreed to in writing, software
 *distributed under the License is distributed on an "AS IS" BASIS,
 *WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *See the License for the specific language governing permissions and
 *limitations under the License.
 *
 *  @author    PagSeguro Internet Ltda.
 *  @copyright 2016 PagSeguro Internet Ltda.
 *  @license   http://www.apache.org/licenses/LICENSE-2.0
 */

namespace UOL\PagSeguro\Controller\Adminhtml\Cancellation;
use UOL\PagSeguro\Controller\Pageable;

/**
 * Class Index
 * @package UOL\PagSeguro\Controller\Adminhtml
 */
class Index extends Pageable
{

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context, $resultPageFactory);
    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /** @var \UOL\PagSeguro\Helper\Auth $authHelper */
        $authHelper = $this->_objectManager->create('UOL\PagSeguro\Helper\Auth');

        /** Check for credentials **/
        if (!$authHelper->hasCredentials())
            return $this->_redirect('pagseguro/credentials/error');

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Cancelamento'));
        $resultPage->getLayout()->getBlock('adminhtml.block.pagseguro.cancellation.content')->setData('adminurl', $this->getAdminUrl());
        return $resultPage;
    }

    /**
     * Cancellation access rights checking
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('UOL_PagSeguro::Cancellation');
    }
}
