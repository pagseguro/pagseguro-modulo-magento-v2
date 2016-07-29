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

namespace UOL\PagSeguro\Controller\Adminhtml\Abandoned;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use UOL\PagSeguro\Model\Transactions\Abandoned;

/**
 * Class Conciliation
 * @package UOL\PagSeguro\Controller\Adminhtml
 */
class Request extends \Magento\Backend\App\Action
{

    /**
     * Result json factory
     *
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->_resultJsonFactory = $resultJsonFactory;
    }
    /**

     * @return void
     */
    public function execute()
    {

        $abandoned = new Abandoned(
              $this->_objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface'),
              $this->_objectManager->create('Magento\Framework\Mail\Template\TransportBuilder'),
              $this->_objectManager->create('Magento\Backend\Model\Session'),
              $this->_objectManager->create('Magento\Sales\Model\Order'),
              $this->_objectManager->create('UOL\PagSeguro\Helper\Library'),
              $this->_objectManager->create('UOL\PagSeguro\Helper\Crypt'),
              $this->getRequest()->getParam('days')
        );

        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->_resultJsonFactory->create();

        try {
            return $result->setData([
                'success' => true,
                'payload' => [
                    'data' => $abandoned->requestAbandonedTransactions()
                ]
            ]);
        } catch (\Exception $exception) {
            return $result->setData([
                'success' => false,
                'payload' => [
                    'error' => $exception->getMessage()
                ]
            ]);
        }
    }

    /**
     * News access rights checking
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('UOL_PagSeguro::Conciliation');
    }
}
