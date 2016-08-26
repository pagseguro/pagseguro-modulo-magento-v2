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

use UOL\PagSeguro\Model\Direct\BoletoMethod;

/**
 * Class Checkout
 * @package UOL\PagSeguro\Controller\Payment
 */
class Boleto extends \Magento\Framework\App\Action\Action
{

    /** @var  \Magento\Framework\View\Result\Page */
    protected $resultJsonFactory;

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
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    )
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Show payment page
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        /** @var $_POST['order_id'] $orderEntity */
        $orderEntity = $this->getRequest()->getParam('order_id');
        /** @var $_POST['sender_hash'] $senderHash */
        $senderHash = $this->getRequest()->getParam('sender_hash');
        /** @var $_POST['sender_document'] $senderDocument */
        $senderDocument = $this->getRequest()->getParam('sender_document');

        /** @var \UOL\PagSeguro\Helper\Data $helperData */
        $helperData = $this->_objectManager->create('UOL\PagSeguro\Helper\Data');

        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();

        /** @var \Magento\Store\Model\StoreManagerInterface $storeManager */
        $storeManager = $this->_objectManager->create('Magento\Store\Model\StoreManagerInterface');

        /** @var UOL\PagSeguro\Helper\Crypt $crypt */
        $crypt = $this->_objectManager->create('UOL\PagSeguro\Helper\Crypt');

        try {

            /** @var \UOL\PagSeguro\Model\Direct\BoletoMethod $boleto */
            $boleto = new BoletoMethod(
                $this->_objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface'),
                $this->_objectManager->create('Magento\Sales\Model\Order')->load($orderEntity),
                $this->_objectManager->create('Magento\Directory\Api\CountryInformationAcquirerInterface'),
                $this->_objectManager->create('Magento\Framework\Module\ModuleList')
            );

            $boleto->setSenderDocument($helperData->formatDocument($senderDocument));
            $boleto->setSenderHash($senderHash);

            /** @var \PagSeguro\Parsers\Transaction\Boleto\Response $response */
            $response = $boleto->createPaymentRequest();

            $this->changeOrderHistory($orderEntity, 'pagseguro_aguardando_pagamento');

            return $result->setData([
                'success' => true,
                'payload' => [
                    'data' => $response,
                    'redirect' => sprintf(
                        '%s%s?payment=%s',
                        $storeManager->getStore()->getBaseUrl(),
                        'pagseguro/direct/success',
                        base64_encode($crypt->encrypt('A3c$#g5R', serialize([$response->getPaymentLink(), $orderEntity, 'boleto'])))
                    )
                ]
            ]);

        } catch (\Exception $exception) {

            $this->changeOrderHistory($orderEntity, 'pagseguro_cancelada');

            return $result->setData([
                'success' => false,
                'payload' => [
                    'error'    => $exception->getMessage(),
                    'redirect' => sprintf('%s%s', $storeManager->getStore()->getBaseUrl(), 'pagseguro/payment/failure')
                ]
            ]);
        }
    }

    /**
     * Change the magento order status
     *
     * @param $orderId
     * @param $status
     */
    private function changeOrderHistory($orderId, $status)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->_objectManager->create('\Magento\Sales\Model\Order')->load(
            $orderId
        );
        /** change payment status in magento */
        $order->addStatusToHistory($status, null, true);
        /** save order */
        $order->save();
    }
}
