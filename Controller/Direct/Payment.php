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
 * Class Payment
 * @package UOL\PagSeguro\Controller\Direct\Payment
 */
class Payment extends \Magento\Framework\App\Action\Action
{

    /** @var  \Magento\Framework\View\Result\Page */
    protected $_resultPageFactory;

    /** @var \Magento\Checkout\Model\Session */
    protected $_checkoutSession;

    /** @var \UOL\PagSeguro\Helper\Library */
    protected $_library;

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
        /** @var \Magento\Checkout\Model\Session _checkoutSession */
        $this->_checkoutSession = $this->_objectManager->create('\Magento\Checkout\Model\Session');
        /** @var  _library */
        $this->_library = $this->_objectManager->create('\UOL\PagSeguro\Helper\Library');
    }

    /**
     * Show payment page
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->getLayout()->getBlock('pagseguro.direct.payment')->setData('order', $this->_checkoutSession->getLastRealOrder()->getId());
        try {
            $this->_library->setEnvironment();
            $resultPage->getLayout()->getBlock('pagseguro.direct.payment')->setData(
                'sessionCode',
                $this->_library->getSession()
            );
            $resultPage->getLayout()->getBlock('pagseguro.direct.payment')->setData(
                'paymentUrl',
                $this->_library->getDirectPaymentUrl()
            );
        } catch (\Exception $exc) {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->_objectManager->create('\Magento\Sales\Model\Order')->load(
                $this->_checkoutSession->getLastRealOrder()->getId()
            );
            /** change payment status in magento */
            $order->addStatusToHistory('pagseguro_cancelada', null, true);
            /** save order */
            $order->save();

            return $this->_redirect('pagseguro/payment/failure');
        }
        return $resultPage;
    }
}
