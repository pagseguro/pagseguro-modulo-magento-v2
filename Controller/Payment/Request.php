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

use UOL\PagSeguro\Model\Direct\BoletoMethod;
use UOL\PagSeguro\Model\Direct\CreditCardMethod;
use UOL\PagSeguro\Model\Direct\DebitMethod;
use UOL\PagSeguro\Model\PaymentMethod;

/**
 * Class Request
 *
 * @package UOL\PagSeguro\Controller\Payment
 */
class Request extends \Magento\Framework\App\Action\Action
{
    
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    /**
     * @var \UOL\PagSeguro\Model\PaymentMethod
     */
    private $_payment;
    
    private $order;
    
    private $orderId;
    
    /** @var \Magento\Framework\Controller\Result\Json  */
    protected $result;
    
    /** @var  \Magento\Framework\View\Result\Page */
    protected $resultJsonFactory;

    /**
     * Request constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context
        //\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $this->_objectManager->create('\Magento\Framework\Controller\Result\JsonFactory');
        $this->result = $this->resultJsonFactory->create();

        /** @var \Magento\Checkout\Model\Session _checkoutSession */
        $this->_checkoutSession = $this->_objectManager->create('\Magento\Checkout\Model\Session');

        /** @var \UOL\PagSeguro\Model\PaymentMethod payment */
        $this->_payment = new PaymentMethod(
            $this->_objectManager->create('\Magento\Framework\App\Config\ScopeConfigInterface'),
            $this->_checkoutSession,
            $this->_objectManager->create('\Magento\Directory\Api\CountryInformationAcquirerInterface'),
            $this->_objectManager->create('Magento\Framework\Module\ModuleList')
        );
    }

    /**
     * Redirect to payment
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $lastRealOrder = $this->_checkoutSession->getLastRealOrder();

        if (is_null($lastRealOrder->getPayment())) {
            throw new \Magento\Framework\Exception\NotFoundException(__('No order associated.'));
        }

        $paymentData = $lastRealOrder->getPayment()->getData();

        if ($paymentData['method'] === 'pagseguro_boleto') {
            try {
                $this->orderId = $lastRealOrder->getId();
                if (is_null($this->orderId)) {
                    throw new \Exception("There is no order associated with this session.");
                }

                if (! isset($paymentData['additional_information']['boleto_document'])
                    || ! isset($paymentData['additional_information']['hash'])) {
                    throw new \Exception("Error passing data from checkout page to pagseguro Request Controller");
                }

                $this->order = $this->loadOrder($this->orderId);
                /** @var \UOL\PagSeguro\Model\Direct\BoletoMethod $boleto */
                $boleto = new BoletoMethod(
                    $this->_objectManager->create('Magento\Directory\Api\CountryInformationAcquirerInterface'),
                    $this->_objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface'),
                    $this->_objectManager->create('Magento\Framework\Module\ModuleList'),
                    $this->order,
                    $this->_objectManager->create('UOL\PagSeguro\Helper\Library'),
                    $data = [
                        'sender_document' => $this->helperData()->formatDocument($paymentData['additional_information']['boleto_document']),
                        'sender_hash' => $paymentData['additional_information']['hash'],
                        'order_id' => $this->orderId
                    ]
                );
                $this->placeOrder($boleto, $paymentData['method']);
                return $this->_redirect(sprintf('%s%s', $this->baseUrl(), 'pagseguro/direct/success'));
            } catch (\Exception $exception) {
                if (!is_null($this->order)) {
                    $this->changeOrderHistory('pagseguro_cancelada');
                }
                $this->pagseguroLogger($exception->getMessage());
                $this->clearSession();
                $this->whenError($exception->getMessage());
                return $this->_redirect(sprintf('%s%s', $this->baseUrl(), 'pagseguro/payment/failure'));
            }
        }

        if ($paymentData['method'] === 'pagseguro_online_debit') {
            try {
                $this->orderId = $lastRealOrder->getId();

                if (is_null($this->orderId)) {
                    throw new \Exception("There is no order associated with this session.");
                }
                
                if (!isset($paymentData['additional_information']['online_debit_document'])
                    || ! isset($paymentData['additional_information']['hash'])
                    || ! isset($paymentData['additional_information']['online_debit_bank'])
                    ) {
                    throw new \Exception("Error passing data from checkout page to pagseguro Request Controller");
                }
                $this->order = $this->loadOrder($this->orderId);
                /** @var \UOL\PagSeguro\Model\Direct\DebitMethod $debit */

                $debit = new DebitMethod(
                    $this->_objectManager->create('Magento\Directory\Api\CountryInformationAcquirerInterface'),
                    $this->_objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface'),
                    $this->_objectManager->create('Magento\Framework\Module\ModuleList'),
                    $this->order,
                    $this->_objectManager->create('UOL\PagSeguro\Helper\Library'),
                    $data = [
                        'sender_document' => $this->helperData()->formatDocument($paymentData['additional_information']['online_debit_document']),
                        'sender_hash' => $paymentData['additional_information']['hash'],
                        'bank_name' => $paymentData['additional_information']['online_debit_bank'],
                        'order_id' => $this->orderId
                    ]
                );
                $this->placeOrder($debit, $paymentData['method']);
                return $this->_redirect(sprintf('%s%s', $this->baseUrl(), 'pagseguro/direct/success'));
            } catch (\Exception $exception) {
                $this->pagseguroLogger($exception->getMessage());
                if (!is_null($this->order)) {
                    $this->changeOrderHistory('pagseguro_cancelada');
                }
                $this->clearSession();
                $this->whenError($exception->getMessage());
                return $this->_redirect(sprintf('%s%s', $this->baseUrl(), 'pagseguro/payment/failure'));
            }
        }

         if ($paymentData['method'] === 'pagseguro_credit_card') {
            try {
                $this->orderId = $lastRealOrder->getId();

                if (is_null($this->orderId)) {
                    throw new \Exception("There is no order associated with this session.");
                }

                if (!isset($paymentData['additional_information']['credit_card_document'])
                    || ! isset($paymentData['additional_information']['hash'])
                    || ! isset($paymentData['additional_information']['credit_card_token'])
                    || ! isset($paymentData['additional_information']['credit_card_holder_name'])
                    || ! isset($paymentData['additional_information']['credit_card_holder_birthdate'])
                    || ! isset($paymentData['additional_information']['credit_card_installment'])
                    || ! isset($paymentData['additional_information']['credit_card_installment_value'])
                    ) {
                    throw new \Exception("Error passing data from checkout page to pagseguro Request Controller");
                }
                $this->order = $this->loadOrder($this->orderId);
                /** @var \UOL\PagSeguro\Model\Direct\CreditCardMethod $creditCard */

                $creditCard = new CreditCardMethod(
                    $this->_objectManager->create('Magento\Directory\Api\CountryInformationAcquirerInterface'),
                    $this->_objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface'),
                    $this->_objectManager->create('Magento\Framework\Module\ModuleList'),
                    $this->order,
                    $this->_objectManager->create('UOL\PagSeguro\Helper\Library'),
                    $data = [
                        'sender_document' => $this->helperData()->formatDocument($paymentData['additional_information']['credit_card_document']),
                        'sender_hash' => $paymentData['additional_information']['hash'],
                        'order_id' => $this->orderId,
                        'installment' => [
                            'quantity' => $paymentData['additional_information']['credit_card_installment'],
                            'amount'   => $paymentData['additional_information']['credit_card_installment_value']
                        ],
                        'token' => $paymentData['additional_information']['credit_card_token'],
                        'holder' => [
                            'name'       => $paymentData['additional_information']['credit_card_holder_name'],
                            'birth_date' => $paymentData['additional_information']['credit_card_holder_birthdate'],

                        ]
                    ]
                );
                $this->placeOrder($creditCard, $paymentData['method']);
                return $this->_redirect(sprintf('%s%s', $this->baseUrl(), 'pagseguro/direct/success'));
            } catch (\Exception $exception) {
                $this->pagseguroLogger($exception->getMessage());
                if (!is_null($this->order)) {
                    $this->changeOrderHistory('pagseguro_cancelada');
                }
                $this->clearSession();
                $this->whenError($exception->getMessage());
                return $this->_redirect(sprintf('%s%s', $this->baseUrl(), 'pagseguro/payment/failure'));
            }
        }

        try {
            return $this->_redirect($this->_payment->createPaymentRequest());
        } catch (\Exception $exception) {
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
    }
    
    /**
     * Load a order by id
     *
     * @return \Magento\Sales\Model\Order
     */
    private function loadOrder($orderId)
    {
        return $this->_objectManager->create('Magento\Sales\Model\Order')->load($orderId);
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
     * Place order
     *
     * @param $payment
     * @return string
     */
    private function placeOrder($payment, $method)
    {
        $this->changeOrderHistory('pagseguro_aguardando_pagamento');
        return $this->whenSuccess($payment->createPaymentRequest(), $method);
    }
    
    /**
     * Change the magento order status
     *
     * @param $status
     */
    private function changeOrderHistory($status)
    {
        /** change payment status in magento */
        $this->order->addStatusToHistory($status, null, true);
        /** save order */
        $this->order->save();
    }
    
    /**
     * Return when success
     *
     * @param $response
     * @return $this
     */
    private function whenSuccess($response, $method)
    {
        $this->makeSession($response, $method);
        return $this->result->setData([
            'success' => true,
            'payload' => [
                'redirect' => sprintf('%s%s', $this->baseUrl(), 'pagseguro/direct/success')
            ]
        ]);
    }
    
    /**
     * Create new pagseguro payment session data
     *
     * @param $response
     */
    private function makeSession($response, $method)
    {
        if ($method === 'pagseguro_credit_card') {
            $this->session()->setData([
                'pagseguro_payment' => [
                    'payment_type'  => $method,
                    'order_id'      => $this->orderId,
                ]
            ]);
        } else {
            $this->session()->setData([
                'pagseguro_payment' => ['payment_link' => $response->getPaymentLink(),
                    'payment_type'  => $method,
                    'order_id'      => $this->orderId,
                ]
            ]);
        }
    }
    
    /**
     * Clear session storage
     */
    private function clearSession()
    {
        $this->_objectManager->create('Magento\Framework\Session\SessionManager')->clearStorage();
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
     * Get base url
     *
     * @return string url
     */
    private function baseUrl()
    {
        return $this->_objectManager->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getBaseUrl();
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
     * Save error log in system.log
     * @param string $errorMessage
     */
    private function pagseguroLogger($errorMessage)
    {
        $logger = $this->_objectManager->create('\Psr\Log\LoggerInterface');
        $logger->addError('[Uol PagSeguro Module][Payment Error] ' .$errorMessage);
    }
}
