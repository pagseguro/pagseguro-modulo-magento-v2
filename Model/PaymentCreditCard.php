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

namespace UOL\PagSeguro\Model;

use Magento\Sales\Model\Order\Payment as PaymentOrder;

/**
 * Class Payment
 * @package UOL\PagSeguro\Model
 */
class PaymentCreditCard extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     *
     */
    const PAYMENT_METHOD_PAGSEGURO_CODE = 'pagseguro_credit_card';
    /**
     * @var string
     */
    protected $_code       = self::PAYMENT_METHOD_PAGSEGURO_CODE;
    /**
     * @var bool
     */
    protected $_isGateway = true;
    /**
     * @var bool
     */
    protected $_canCapture = true;
    /**
     * @var bool
     */
    protected $_canUseForMultishipping = true;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    private $cart;

    /**
     * Payment constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \UOL\PagSeguro\Helper\Library $helper
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $attributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Checkout\Model\Cart $cart
    ) {

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $attributeFactory,
            $paymentData,
            $scopeConfig,
            $logger
        );
        /** @var \Magento\Checkout\Model\Cart _cart */
        $this->_cart = $cart;
    }

    /**
     * Assign data to info model instance
     *
     * @param \Magento\Framework\DataObject $data
     * @return \Magento\Payment\Model\Info
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        $info = $this->getInfoInstance();
        if (isset($data->getData('additional_data')['credit_card_document'])) {
            $info->setAdditionalInformation('credit_card_document', $data->getData('additional_data')['credit_card_document']);
        }

        if (isset($data->getData('additional_data')['credit_card_hash'])) {
            $info->setAdditionalInformation('hash', $data->getData('additional_data')['credit_card_hash']);
        }

        if (isset($data->getData('additional_data')['credit_card_token'])) {
            $info->setAdditionalInformation('credit_card_token', $data->getData('additional_data')['credit_card_token']);
        }

        if (isset($data->getData('additional_data')['credit_card_holder_name'])) {
            $info->setAdditionalInformation('credit_card_holder_name', $data->getData('additional_data')['credit_card_holder_name']);
        }

        if (isset($data->getData('additional_data')['credit_card_holder_birthdate'])) {
            $info->setAdditionalInformation('credit_card_holder_birthdate', $data->getData('additional_data')['credit_card_holder_birthdate']);
        }

        if (isset($data->getData('additional_data')['credit_card_installment'])) {
            $info->setAdditionalInformation('credit_card_installment', $data->getData('additional_data')['credit_card_installment']);
        }

        if (isset($data->getData('additional_data')['credit_card_installment_value'])) {
            $info->setAdditionalInformation('credit_card_installment_value', $data->getData('additional_data')['credit_card_installment_value']);
        }

        return $this;
    }

    /**
     * Get standard checkout payment url
     *
     * @return url
     */
    public function getStandardCheckoutPaymentUrl()
    {
        return $this->_cart->getQuote()->getStore()->getUrl("pagseguro/payment/request/");
    }


    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null){
        return true
        && parent::isAvailable($quote);
    }
}
