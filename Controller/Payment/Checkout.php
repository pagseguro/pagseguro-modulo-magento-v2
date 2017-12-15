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

namespace UOL\PagSeguro\Controller\Payment;

use UOL\PagSeguro\Model\PaymentMethod;

/**
 * Class Checkout
 * @package UOL\PagSeguro\Controller\Payment
 */
class Checkout extends \Magento\Framework\App\Action\Action
{

    /** @var  \Magento\Framework\View\Result\Page */
    protected $resultPageFactory;

    /**
     * @var \UOL\PagSeguro\Model\PaymentMethod
     */
    protected $payment;


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
        $this->resultPageFactory = $resultPageFactory;
        $this->payment = new PaymentMethod(
            $this->_objectManager
                ->create('\Magento\Framework\App\Config\ScopeConfigInterface'),
            $this->_objectManager->create('\Magento\Checkout\Model\Session'),
            $this->_objectManager
                ->create('\Magento\Directory\Api\CountryInformationAcquirerInterface'),
			$this->_objectManager->create('Magento\Framework\Module\ModuleList')
        );
    }

    /**
     * Show payment page
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        $result = $this->payment->createPaymentRequest();
        $resultPage = $this->resultPageFactory->create();
        $code = $result->getCode();
        $resultPage->getLayout()->getBlock('pagseguro.payment.checkout')
            ->setCode($code);
        $resultPage->getLayout()->getBlock('pagseguro.payment.checkout')
            ->setPaymentJs($this->getPagSeguroPaymentJs());
        $resultPage->getLayout()->getBlock('pagseguro.payment.checkout')
            ->setPaymentUrl($this->payment->checkoutUrl($code, 'paymentService'));

        return $resultPage;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getPagSeguroPaymentJs()
    {
        if (\PagSeguro\Configuration\Configure::getEnvironment()->getEnvironment() == 'sandbox') {
            return \UOL\PagSeguro\Helper\Library::SANDBOX_JS;
        } else {
            return \UOL\PagSeguro\Helper\Library::STANDARD_JS;
        }
    }
}
