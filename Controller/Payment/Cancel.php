<?php
/**
 * 2007-2017 [PagSeguro Internet Ltda.]
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
 *  @copyright 2017 PagSeguro Internet Ltda.
 *  @license   http://www.apache.org/licenses/LICENSE-2.0
 */

namespace UOL\PagSeguro\Controller\Payment;

use UOL\PagSeguro\Model\PaymentMethod;

/**
 * Class Checkout
 * @package UOL\PagSeguro\Controller\Payment
 */
class Cancel extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * Checkout constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        /** @var  \Magento\Framework\View\Result\PageFactory _resultPageFactory*/
        $this->_resultPageFactory = $resultPageFactory;

    }
    /**
     * Show cancel page
     *
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {

        $this->_orderFactory = $this->_objectManager->get('\Magento\Sales\Model\OrderFactory');
        $this->_checkoutSession = $this->_objectManager->get('\Magento\Checkout\Model\Session');
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->_objectManager->create('\Magento\Sales\Model\Order')->load(
            $this->_checkoutSession->getLastRealOrderId());

        /** change payment status in magento */
        $order->addStatusToHistory('pagseguro_cancelada', null, true);
        /** save order */
        $order->save();

        return $this->_redirect('/');

    }
}
