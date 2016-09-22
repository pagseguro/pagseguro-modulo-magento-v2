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

use UOL\PagSeguro\Model\Direct\CreditCardMethod;

/**
 * Class Checkout
 * @package UOL\PagSeguro\Controller\Payment
 */
class CreditCard extends \Magento\Framework\App\Action\Action
{
    /** @var  \Magento\Framework\View\Result\Page */
    protected $resultJsonFactory;

    /**
     * @var \UOL\PagSeguro\Model\PaymentMethod
     */
    protected $payment;

    /** @var \Magento\Framework\Controller\Result\Json  */
    protected $result;

    /**
     * Checkout constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    )
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->result = $this->resultJsonFactory->create();
    }

    /**
     * Show payment page
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {

        //$cardInternational = $this->getRequest()->getParam('card_international');

        try {
            $creditCard = new CreditCardMethod(
                $this->_objectManager->create('Magento\Directory\Api\CountryInformationAcquirerInterface'),
                $this->_objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface'),
                $this->_objectManager->create('Magento\Framework\Module\ModuleList'),
                $this->loadOrder(),
                $this->_objectManager->create('UOL\PagSeguro\Helper\Library'),
                $data = [
                    'sender_document' => $this->helperData()->formatDocument($this->getRequest()->getParam('sender_document')),
                    'sender_hash' => $this->getRequest()->getParam('sender_hash'),
                    'order_id' => $this->lastRealOrderId(),
                    'installment' => [
                        'quantity' => $this->getRequest()->getParam('installment_quantity'),
                        'amount'   => $this->getRequest()->getParam('installment_amount')
                    ],
                    'token' => $this->getRequest()->getParam('card_token'),
                    'holder' => [
                        'name'       => $this->getRequest()->getParam('holder_name'),
                        'birth_date' => $this->getRequest()->getParam('holder_birthdate'),

                    ]
                ]
            );
            return $this->placeOrder($creditCard);

        } catch (\Exception $exception) {
            $this->changeOrderHistory('pagseguro_cancelada');
            $this->clearSession();
            return $this->whenError($exception->getMessage());
        }
    }

    /**
     * Place order
     *
     * @param $creditCard
     * @return CreditCard
     */
    private function placeOrder($creditCard)
    {
        $this->changeOrderHistory('pagseguro_aguardando_pagamento');
        return $this->whenSuccess($creditCard->createPaymentRequest());
    }

    /**
     * Return when success
     *
     * @param $response
     * @return $this
     */
    private function whenSuccess($response)
    {
        $this->makeSession();
        return $this->result->setData([
            'success' => true,
            'payload' => [
                'redirect' => sprintf('%s%s', $this->baseUrl(), 'pagseguro/direct/success')
            ]
        ]);
    }

    /**
     * Return when fails
     *
     * @param $message
     * @return $this
     */
    private function whenError($message)
    {
        return $this->result->setData([
            'success' => false,
            'payload' => [
                'error'    => $message,
                'redirect' => sprintf('%s%s', $this->baseUrl(), 'pagseguro/payment/failure')
            ]
        ]);
    }

    /**
     * Create new pogseguro payment session data
     *
     * @param $response
     */
    private function makeSession()
    {
        $this->session()->setData([
            'pagseguro_payment' => [
                'payment_type'  => strtolower(CreditCardMethod::class),
                'order_id'      => $this->lastRealOrderId(),
            ]
        ]);
    }

    /**
     * Clear session storage
     */
    private function clearSession()
    {
        $this->_objectManager->create('Magento\Framework\Session\SessionManager')->clearStorage();
    }

    /**
     * Load a order by id
     *
     * @return \Magento\Sales\Model\Order
     */
    private function loadOrder()
    {
        return $this->_objectManager->create('Magento\Sales\Model\Order')->load($this->lastRealOrderId());
    }

    /**
     * Load PagSeguro helper data
     *
     * @return \UOL\PagSeguro\Helper\Data
     */
    private function helperData()
    {
        return $this->_objectManager->create('UOL\PagSeguro\Helper\Data');
    }

    /**
     * Get base url
     *
     * @return string url
     */
    private function baseUrl()
    {
        return $this->_objectManager->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getBaseUrl();
    }

    /**
     * Get last real order id
     *
     * @return string id
     */
    private function lastRealOrderId()
    {
        return $this->_objectManager->create('\Magento\Checkout\Model\Session')->getLastRealOrder()->getId();
    }

    /**
     * Create a new session object
     *
     * @return \Magento\Framework\Session\SessionManager
     */
    private function session()
    {
        return $this->_objectManager->create('Magento\Framework\Session\SessionManager');
    }

    /**
     * Change the magento order status
     *
     * @param $status
     */
    private function changeOrderHistory($status)
    {
        $order = $this->loadOrder();
        /** change payment status in magento */
        $order->addStatusToHistory($status, null, true);
        /** save order */
        $order->save();
    }
}
