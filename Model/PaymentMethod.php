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

use UOL\PagSeguro\Helper\Library;
use PagSeguro\Domains\Requests\Payment as PS_Payment;

/**
 * Class PaymentMethod
 * @package UOL\PagSeguro\Model
 */
class PaymentMethod
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    
    /**
     *
     * @var \PagSeguro\Domains\Requests\Payment
     */
    protected $_paymentRequest;

    /**
     *
     * @var \Magento\Directory\Api\CountryInformationAcquirerInterface
     */
    protected $_countryInformation;

    /**
     * PaymentMethod constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation,
		\Magento\Framework\Module\ModuleList $moduleList
    ) {
        /** @var \Magento\Framework\App\Config\ScopeConfigInterface _scopeConfig */
        $this->_scopeConfig = $scopeConfigInterface;
        /** @var  _checkoutSession */
        $this->_checkoutSession = $checkoutSession;
        /** @var \Magento\Checkout\Model\Session _countryInformation */
        $this->_countryInformation = $countryInformation;
        /** @var \Magento\Directory\Api\CountryInformationAcquirerInterface _library */
		$this->_library = new Library($scopeConfigInterface, $moduleList);
        /** @var  \Magento\Framework\Module\ModuleList _paymentRequest */
        $this->_paymentRequest = new PS_Payment();
    }
    /**
     * @return \PagSeguroPaymentRequest
     */
    public function createPaymentRequest()
    {
        // Currency
        $this->_paymentRequest->setCurrency("BRL");
        // Order ID
        $this->_paymentRequest->setReference($this->getOrderStoreReference());
        // Cart discount
        $lastRealOrder = $this->_checkoutSession->getLastRealOrder();
        $this->_paymentRequest->setExtraAmount(round($lastRealOrder->getDiscountAmount(), 2));
        // PagSeguro Payment Method discounts
        $this->setPagSeguroDiscountsByPaymentMethod();
        //Shipping
        $this->setShippingInformation();
        // Sender
        $this->setSenderInformation();
        // Itens
        $this->setItemsInformation();
        //Redirect Url
        $this->_paymentRequest->setRedirectUrl($this->getRedirectUrl());
        // Notification Url
        $this->_paymentRequest->setNotificationUrl($this->getNotificationUrl());
        // Shopping cart recovery
        $this->setShoppingCartRecovery();
        try {
            $this->_library->setEnvironment();
            $this->_library->setCharset();
            $this->_library->setLog();
            return $this->_paymentRequest->register(
                $this->_library->getPagSeguroCredentials(),
                $this->_library->isLightboxCheckoutType()
            );
        } catch (PagSeguroServiceException $ex) {
            $this->logger->debug($ex->getMessage());
            $this->getCheckoutRedirectUrl();
        }
    }
    /**
     * Get information of purchased items and set in the attribute $_paymentRequest
     *
     * @return PagSeguroItem
     */
    private function setItemsInformation()
    {
        foreach ($this->_checkoutSession->getLastRealOrder()->getAllVisibleItems() as $product) {
            $this->_paymentRequest->addItems()->withParameters(
                $product->getProduct()->getId(), //id
                \UOL\PagSeguro\Helper\Data::fixStringLength($product->getName(), 255), //description
                $product->getSimpleQtyToShip(), //quantity
                \UOL\PagSeguro\Helper\Data::toFloat($product->getPrice()), //amount
                round($product->getWeight()) //weight
            );
        }
    }
    /**
     * Get customer information that are sent and set in the attribute $_paymentRequest
     */
    private function setSenderInformation()
    {
        $senderName = $this->_checkoutSession->getLastRealOrder()->getCustomerName();
        // If Guest
        if (
            $senderName == (string)__('Guest')
            || $senderName == 'Convidado'
            || $senderName == 'Visitante'
                
        ) {
            $address = $this->getBillingAddress();
            $senderName = $address->getFirstname() . ' ' . $address->getLastname();
        }
        $this->_paymentRequest->setSender()->setName($senderName);
        $this->_paymentRequest->setSender()->setEmail($this->_checkoutSession
            ->getLastRealOrder()->getCustomerEmail());
        $this->setSenderPhone();
        
    }
    /**
     * Get the shipping information and set in the attribute $_paymentRequest
     */
    private function setShippingInformation()
    {
        if ($this->_checkoutSession->getLastRealOrder()->getIsVirtual()) {
            $this->_paymentRequest->setShipping()->setAddressRequired()->withParameters('false');
        } else {
            $this->_paymentRequest->setShipping()->setAddressRequired()->withParameters('true');
            $shipping = $this->_checkoutSession->getLastRealOrder()->getShippingAddress();
            if ($shipping) {
                if (count($shipping->getStreet()) === 4) {
                    $this->_paymentRequest->setShipping()->setAddress()->withParameters(
                        $shipping->getStreetLine(1),
                        $shipping->getStreetLine(2),
                        $shipping->getStreetLine(4),
                        \UOL\PagSeguro\Helper\Data::fixPostalCode($shipping->getPostcode()),
                        $shipping->getCity(),
                        $this->getRegionAbbreviation($shipping),
                        $this->getCountryName($shipping['country_id']),
                        $shipping->getStreetLine(3)
                    );
                } else {
                    $address = \UOL\PagSeguro\Helper\Data::addressConfig($shipping['street']);

                    $this->_paymentRequest->setShipping()->setAddress()->withParameters(
                        $this->getShippingAddress($address[0], $shipping),
                        $this->getShippingAddress($address[1]),
                        $this->getShippingAddress($address[3]),
                        \UOL\PagSeguro\Helper\Data::fixPostalCode($shipping->getPostcode()),
                        $shipping->getCity(),
                        $this->getRegionAbbreviation($shipping),
                        $this->getCountryName($shipping['country_id']),
                        $this->getShippingAddress($address[2])
                    );
                }

                $this->_paymentRequest->setShipping()->setType()
                    ->withParameters(\PagSeguro\Enum\Shipping\Type::NOT_SPECIFIED); //Shipping Type
                $this->_paymentRequest->setShipping()->setCost()
                    ->withParameters(number_format($this->getShippingAmount(), 2, '.', '')); //Shipping Coast
            }
        }
    }
    /**
     * Get shipping address
     *
     * @param $address
     * @param bool $shipping
     * @return array|null
     */
    private function getShippingAddress($address, $shipping = null)
    {
        if (!is_null($address) or !empty($adress)) {
            return $address;
        }
        if ($shipping) {
            return \UOL\PagSeguro\Helper\Data::addressConfig($shipping['street']);
        }
        return null;
    }

    /**
     * Get shipping amount from session
     *
     * @return mixed
     */
    private function getShippingAmount()
    {
        return $this->_checkoutSession->getLastRealOrder()->getBaseShippingAmount();
    }

    /**
     * Get checkout url
     *
     * @param $code
     * @return string
     */
    public function checkoutUrl($code, $serviceName)
    {
        $connectionData = new \PagSeguro\Resources\Connection\Data($this->_library->getPagSeguroCredentials());
        return $connectionData->buildPaymentResponseUrl() . "?code=$code";
    }

    /**
     * Get store reference from magento core_config_data table
     *
     * @return string
     */
    private function getOrderStoreReference()
    {
        return \UOL\PagSeguro\Helper\Data::getOrderStoreReference(
            $this->_scopeConfig->getValue('pagseguro/store/reference'),
            $this->_checkoutSession->getLastRealOrder()->getEntityId()
        );
    }
    
    /**
     * Get a brazilian region name and return the abbreviation if it exists
     *
     * @param shipping $shipping
     * @return string
     */
    private function getRegionAbbreviation($shipping)
    {
        if (strlen($shipping->getRegionCode()) == 2) {
            return $shipping->getRegionCode();
        }

        $regionAbbreviation = new \PagSeguro\Enum\Address();

        return (is_string($regionAbbreviation->getType($shipping->getRegion()))) ?
            $regionAbbreviation->getType($shipping->getRegion()) :
            $shipping->getRegion();
    }
    
    /**
     * Get the store notification url
     *
     * @return string
     */
    public function getNotificationUrl()
    {
        return $this->_scopeConfig->getValue('payment/pagseguro/notification');
    }
    
    /**
     * Get the store redirect url
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->_scopeConfig->getValue('payment/pagseguro/redirect');
    }
    
    /**
     * Set the sender phone if it exist
     */
    private function setSenderPhone()
    {
        $addressData = ($this->getBillingAddress())
            ? $this->getBillingAddress()
            : $this->_checkoutSession->getLastRealOrder()->getShippingAddress();

        if (! empty($addressData['telephone'])) {
            $phone = \UOL\PagSeguro\Helper\Data::formatPhone($addressData['telephone']);
            $this->_paymentRequest->setSender()->setPhone()->withParameters(
                $phone['areaCode'],
                $phone['number']
            );
        }
    }
    
    /**
     * Get the billing address data of the Order
     *
     * @return \Magento\Sales\Model\Order\Address|null
     */
    private function getBillingAddress()
    {
        return $this->_checkoutSession->getLastRealOrder()->getBillingAddress();
    }

	/**
     * Get the country name based on the $countryId
     * 
     * @param string $countryId
     * @return string
     */
    private function getCountryName($countryId)
    {
        return (!empty($countryId)) ?
            $this->_countryInformation->getCountryInfo($countryId)->getFullNameLocale() :
            $countryId;
    }

    /**
     * Set PagSeguro recovery shopping cart value
     *
     * @return void
     */
    private function setShoppingCartRecovery()
    {
        if ($this->_scopeConfig->getValue('payment/pagseguro/shopping_cart_recovery') == true) {
            $this->_paymentRequest->addParameter()->withParameters('enableRecovery', 'true');
        } else {
            $this->_paymentRequest->addParameter()->withParameters('enableRecovery', 'false');
        }
    }

    /**
     * Get the discount configuration for PagSeguro store configurarion and
     * set in the payment request the discount amount for every payment method configured
     *
     * @return void
     */
    private function setPagSeguroDiscountsByPaymentMethod()
    {
        $storeId = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        if ($this->_scopeConfig->getValue('payment/pagseguro_default_lightbox/discount_credit_card', $storeId) == 1) {
            $creditCard = (double)$this->_scopeConfig->getValue('payment/pagseguro_default_lightbox/discount_credit_card_value', $storeId);
            if ($creditCard && $creditCard != 0.00) {
                $this->_paymentRequest->addPaymentMethod()->withParameters(
                    \PagSeguro\Enum\PaymentMethod\Group::CREDIT_CARD,
                    \PagSeguro\Enum\PaymentMethod\Config\Keys::DISCOUNT_PERCENT,
                    $creditCard
                );
            }
        }
        if ($this->_scopeConfig->getValue('payment/pagseguro_default_lightbox/discount_online_debit', $storeId) == 1) {
            $eft = (double)$this->_scopeConfig->getValue('payment/pagseguro_default_lightbox/discount_online_debit_value', $storeId);
            if ($eft && $eft != 0.00) {
                $this->_paymentRequest->addPaymentMethod()->withParameters(
                    \PagSeguro\Enum\PaymentMethod\Group::EFT,
                    \PagSeguro\Enum\PaymentMethod\Config\Keys::DISCOUNT_PERCENT,
                    $eft
                );
            }
        }
        if ($this->_scopeConfig->getValue('payment/pagseguro_default_lightbox/discount_boleto', $storeId) == 1) {
            $boleto = (double)$this->_scopeConfig->getValue('payment/pagseguro_default_lightbox/discount_boleto_value', $storeId);
            if ($boleto && $boleto != 0.00) {
                $this->_paymentRequest->addPaymentMethod()->withParameters(
                    \PagSeguro\Enum\PaymentMethod\Group::BOLETO,
                    \PagSeguro\Enum\PaymentMethod\Config\Keys::DISCOUNT_PERCENT,
                    $boleto
                );
            }
        }
        if ($this->_scopeConfig->getValue('payment/pagseguro_default_lightbox/discount_deposit_account', $storeId)) {
            $deposit = (double)$this->_scopeConfig->getValue('payment/pagseguro_default_lightbox/discount_deposit_account_value', $storeId);
            if ($deposit && $deposit != 0.00) {
                $this->_paymentRequest->addPaymentMethod()->withParameters(
                    \PagSeguro\Enum\PaymentMethod\Group::DEPOSIT,
                    \PagSeguro\Enum\PaymentMethod\Config\Keys::DISCOUNT_PERCENT,
                    $deposit
                );
            }
        }
        if ($this->_scopeConfig->getValue('payment/pagseguro_default_lightbox/discount_balance', $storeId)) {
            $balance = (double)$this->_scopeConfig->getValue('payment/pagseguro_default_lightbox/discount_balance_value', $storeId);
            if ($balance && $balance != 0.00) {
                $this->_paymentRequest->addPaymentMethod()->withParameters(
                    \PagSeguro\Enum\PaymentMethod\Group::BALANCE,
                    \PagSeguro\Enum\PaymentMethod\Config\Keys::DISCOUNT_PERCENT,
                    $balance
                );
            }
        }
    }
}
