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

namespace UOL\PagSeguro\Model\Direct;

use UOL\PagSeguro\Helper\Library;
use PagSeguro\Domains\Requests\DirectPayment\OnlineDebit;

/**
 * Class DebitMethod
 * @package UOL\PagSeguro\Model
 */
class DebitMethod
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
     * @var array
     */
    protected $_data;

    /**
     * DebitMethod constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation
     * @param \Magento\Framework\Module\ModuleList $moduleList
     */
    public function __construct(
        \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Framework\Module\ModuleList $moduleList,
        \Magento\Sales\Model\Order $order,
        \UOL\PagSeguro\Helper\Library $library,
        $data = array()
    ) {
        $this->_data = $data;
        /** @var \Magento\Framework\App\Config\ScopeConfigInterface _scopeConfig */
        $this->_scopeConfig = $scopeConfigInterface;
        /** @var \Magento\Sales\Model\Order _order */
        $this->_order = $order;
        /** @var \Magento\Directory\Api\CountryInformationAcquirerInterface _countryInformation */
        $this->_countryInformation = $countryInformation;
        /** @var \UOL\PagSeguro\Helper\Library _library */
        $this->_library = $library;
        /** @var \PagSeguro\Domains\Requests\DirectPayment\OnlineDebit _paymentRequest */
        $this->_paymentRequest = new OnlineDebit();
    }
    /**
     * @return string
     * @throws \Exception
     */
    public function createPaymentRequest()
    {
        try {
            $this->currency();
            $this->reference();
            $this->discounts();
            $this->shipping();
            $this->sender();
            $this->urls();
            $this->items();
            $this->config();
            $this->bank();
            $this->setShoppingCartRecovery();
            return $this->register();
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Register a new payment
     *
     * @return string
     */
    private function register()
    {
        return $this->_paymentRequest->register($this->_library->getPagSeguroCredentials());
    }

    /**
     * Set configuration for payment
     */
    private function config()
    {
        $this->_library->setEnvironment();
        $this->_library->setCharset();
        $this->_library->setLog();
    }

    /**
     * Set redirect and notification url's
     */
    private function urls()
    {
        //Redirect Url
        $this->_paymentRequest->setRedirectUrl($this->getRedirectUrl());
        // Notification Url
        $this->_paymentRequest->setNotificationUrl($this->getNotificationUrl());
    }

    /**
     * Set currency for payment
     */
    private function currency()
    {
        $this->_paymentRequest->setCurrency("BRL");
    }

    /**
     * Set reference for payment
     */
    private function reference()
    {
        $this->_paymentRequest->setReference($this->getOrderStoreReference());
    }

    /**
     * Set shipping for payment
     */
    private function shipping()
    {
        if ($this->_order->getIsVirtual()) {
            $this->_paymentRequest->setShipping()->setAddressRequired()->withParameters('false');
        } else {
            $this->_paymentRequest->setShipping()->setAddressRequired()->withParameters('true');
            $this->setShippingInformation();
        }
    }

    /**
     * Set sender for payment
     */
    private function sender()
    {
        $this->setSenderHash();
        $this->setSenderDocument();
        $this->setSenderPhone();
        $this->setSenderInformation();
    }

    /**
     * Set items for payment
     */
    private function items()
    {
        foreach ($this->_order->getAllVisibleItems() as $product) {
            $this->setItemsInformation($product);
        }
    }

    /**
     * Set bank
     */
    public function bank()
    {
        $this->_paymentRequest->setBankName(htmlentities($this->_data['bank_name']));
    }

    /**
     * Set sender hash
     */
    private function setSenderHash()
    {
        $this->_paymentRequest->setSender()->setHash(htmlentities($this->_data['sender_hash']));
    }

    /**
     * Set sender document
     */
    private function setSenderDocument()
    {
        $this->_paymentRequest->setSender()->setDocument()->withParameters(
            $this->_data['sender_document']['type'],
            $this->_data['sender_document']['number']
        );
    }

    /**
     * Get information of purchased items and set in the attribute $_paymentRequest
     */
    private function setItemsInformation($product)
    {
        $this->_paymentRequest->addItems()->withParameters(
            $product->getProduct()->getId(), //id
            \UOL\PagSeguro\Helper\Data::fixStringLength($product->getName(), 255), //description
            $product->getSimpleQtyToShip(), //quantity
            \UOL\PagSeguro\Helper\Data::toFloat($product->getPrice()), //amount
            round($product->getWeight()) //weight
        );
    }

    /**
     * Get customer information that are sent and set in the attribute $_paymentRequest
     */
    private function setSenderInformation()
    {
        if (
            $this->_order->getCustomerName() == (string)__('Guest')
            || $this->_order->getCustomerName() == 'Convidado'
            || $this->_order->getCustomerName() == 'Visitante'
        ) {
            $this->guest();
        } else {
            $this->loggedIn();
        }

        $this->_paymentRequest->setSender()->setEmail($this->getEmail());
    }

    /**
     * Set guest info
     */
    private function guest()
    {
        $address = $this->getBillingAddress();
        $this->_paymentRequest->setSender()->setName($address->getFirstname() . ' ' . $address->getLastname());
    }

    /**
     * Set logged in user info
     */
    private function loggedIn()
    {
        $this->_paymentRequest->setSender()->setName($this->_order->getCustomerName());
    }

    /**
     * Return a mock for sandbox if this is the active environment
     *
     * @return string
     */
    private function getEmail()
    {
//        if ($this->_scopeConfig->getValue('payment/pagseguro/environment') == "sandbox") {
//            return "magento2@sandbox.pagseguro.com.br"; //mock for sandbox
//        }
        return $this->_order->getCustomerEmail();
    }

    /**
     * Set the sender phone if it exist
     */
    private function setSenderPhone()
    {
        $addressData = ($this->getBillingAddress())
            ? $this->getBillingAddress()
            : $this->_order->getShippingAddress();

        if (! empty($addressData['telephone'])) {
            $phone = \UOL\PagSeguro\Helper\Data::formatPhone($addressData['telephone']);
            $this->_paymentRequest->setSender()->setPhone()->withParameters(
                $phone['areaCode'],
                $phone['number']
            );
        }
    }

    /**
     * Get the shipping information and set in the attribute $_paymentRequest
     */
    private function setShippingInformation()
    {
        $shipping = $this->_order->getShippingAddress();
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

    /**
     * Get shipping address
     *
     * @param $address
     * @param bool $shipping
     * @return array|null
     */
    private function getShippingAddress($address, $shipping = null)
    {
        if (!is_null($address) or !empty($adress))
            return $address;
        if ($shipping)
            return \UOL\PagSeguro\Helper\Data::addressConfig($shipping['street']);
        return null;
    }

    /**
     * Get shipping amount from magento order
     *
     * @return mixed
     */
    private function getShippingAmount()
    {
        return $this->_order->getBaseShippingAmount();
    }

    /**
     * Get store reference from magento core_config_data
     *
     * @return string
     */
    private function getOrderStoreReference()
    {
        return \UOL\PagSeguro\Helper\Data::getOrderStoreReference(
            $this->_scopeConfig->getValue('pagseguro/store/reference'),
            $this->_order->getEntityId()
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
     * Get the billing address data of the Order
     *
     * @return \Magento\Sales\Model\Order\Address|null
     */
    private function getBillingAddress()
    {
        return $this->_order->getBillingAddress();
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
     * Set discounts using PagSeguro "extra amount" parameter
     */
    private function discounts()
    {
        $this->_paymentRequest->setExtraAmount(round($this->_order->getDiscountAmount(), 2));
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
}
