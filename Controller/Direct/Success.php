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

namespace UOL\PagSeguro\Controller\Direct;

use UOL\PagSeguro\Model\PaymentMethod;

/**
 * Class Checkout
 * @package UOL\PagSeguro\Controller\Payment
 */
class Success extends \Magento\Framework\App\Action\Action
{

    /** @var  \Magento\Framework\View\Result\Page */
    protected $_resultPageFactory;

    /**
     * Checkout constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);

        /** @var  _resultPageFactory */
        $this->_resultPageFactory = $resultPageFactory;
    }

    /**
     * Show payment page
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {

        /** @var \UOL\PagSeguro\Helper\Crypt $crypt */
        $crypt = $this->_objectManager->create('UOL\PagSeguro\Helper\Crypt');
        /** @var $_POST['payment'] $data */
        $data = base64_decode($this->getRequest()->getParam('payment'));

        $payment = unserialize($crypt->decrypt('A3c$#g5R', $data));
        
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($payment[1]);
        
        /** @var \Magento\Framework\View\Result\PageFactory $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->getLayout()->getBlock('pagseguro.payment.success')->setPaymentLink(
            $payment[0]
        );
        $resultPage->getLayout()->getBlock('pagseguro.payment.success')->setOrderId($order->getIncrementId());
        $resultPage->getLayout()->getBlock('pagseguro.payment.success')->setPaymentType($payment[2]);
        $resultPage->getLayout()->getBlock('pagseguro.payment.success')->setCanViewOrder(true);

        return $resultPage;
    }
}
