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
     * PaymentMethod constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->_scopeConfig = $scopeConfigInterface;
        $this->_checkoutSession = $checkoutSession;
        $this->_library = new Library($scopeConfigInterface);
    }

    /**
     * @return \PagSeguroPaymentRequest
     */
    public function createPaymentRequest()
    {
        $paymentRequest = new \PagSeguroPaymentRequest();

        // Set the currency
        $paymentRequest->setCurrency("BRL");
        $paymentRequest->setReference($this->getStoreReference()); //Order ID
        $paymentRequest->setShipping($this->getShippingInformation()); //Shipping
        $paymentRequest->setSender($this->getSenderInformation()); //Sender
        $paymentRequest->setItems($this->getItensInformation()); //Itens
        $paymentRequest->setShippingType(\PagSeguroShippingType::getCodeByType('NOT_SPECIFIED')); //Shipping Type
        $paymentRequest->setShippingCost(number_format($this->getShippingAmount(), 2, '.', '')); //Shipping Coast

        try {

            return $paymentRequest->register(
                $this->_library->getPagSeguroCredentials(),
                $this->_library->isLightboxCheckoutType()
            );

        } catch (PagSeguroServiceException $ex) {
            $this->logger->debug($ex->getMessage());
            $this->getCheckoutRedirectUrl();
        }
    }

    /**
     * Get information of purchased items
     * @return PagSeguroItem
     */
    private function getItensInformation()
    {
        $items = array();
        foreach ($this->_checkoutSession->getLastRealOrder()->getAllVisibleItems() as $product) {
            $item = new \PagSeguroItem();
            $item->setId($product->getId());
            $item->setDescription(\UOL\PagSeguro\Helper\Data::fixStringLength($product->getName(), 255));
            $item->setQuantity($product->getSimpleQtyToShip());
            $item->setWeight(round($product->getWeight()));
            $item->setAmount(\UOL\PagSeguro\Helper\Data::toFloat($product->getPrice()));
            array_push($items, $item);
        }
        return $items;
    }

    /**
     * Customer information that are sent
     * @return PagSeguroSender
     */
    private function getSenderInformation()
    {
        $sender = new \PagSeguroSender();
        $sender->setEmail($this->_checkoutSession->getLastRealOrder()->getCustomerEmail());
        $sender->setName($this->_checkoutSession->getLastRealOrder()->getCustomerName());
        return $sender;
    }

    /**
     * Get the shipping information
     * @return PagSeguroShipping
     */
    private function getShippingInformation()
    {
        $shipping = $this->getShippingData();
        $address = \UOL\PagSeguro\Helper\Data::addressConfig($shipping['street']);
        $street = $this->getShippingAddress($address[0], $shipping);
        $number = $this->getShippingAddress($address[1]);
        $complement = $this->getShippingAddress($address[2]);
        $district = $this->getShippingAddress($address[3]);
        return $this->setPagSeguroShipping($street, $number, $complement, $district);
    }

    /**
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
     * Get the shipping Data of the Order
     * @return object $orderParams - Return parameters, of shipping of order
     */
    private function getShippingData()
    {
        if ($this->_checkoutSession->getLastRealOrder()->getIsVirtual()) {
            return $this->_checkoutSession->getLastRealOrder()->getBillingAddress();
        }
        return $this->_checkoutSession->getLastRealOrder()->getShippingAddress();
    }

    /**
     * @return mixed
     */
    private function getShippingAmount()
    {
        return $this->_checkoutSession->getLastRealOrder()->getBaseShippingAmount();
    }

    /**
     * @param $street
     * @param $number
     * @param $complement
     * @param $district
     * @return PagSeguroShipping
     */
    private function setPagSeguroShipping($street, $number, $complement, $district)
    {
        $shipping = new \PagSeguroShipping();
        $shipping->setAddress($this->setPagSeguroShipppingAddress($street, $number, $complement, $district));
        return $shipping;
    }

    /**
     * @param $street
     * @param $number
     * @param $complement
     * @param $district
     * @return \PagSeguroAddress
     */
    private function setPagSeguroShipppingAddress($street, $number, $complement, $district)
    {
        $shipping = $this->getShippingData();
        $address = new \PagSeguroAddress();
        $address->setCity($shipping['city']);
        $address->setPostalCode(\UOL\PagSeguro\Helper\Data::fixPostalCode($shipping['postcode']));
        $address->setState($shipping['region']);
        $address->setStreet($street);
        $address->setNumber($number);
        $address->setComplement($complement);
        $address->setDistrict($district);

        return $address;
    }

    /***
     * @param $code
     * @return string
     */
    public function checkoutUrl($code, $serviceName)
    {
        $connectionData = new \PagSeguroConnectionData($this->_library->getPagSeguroCredentials(), $serviceName);
        return $connectionData->getPaymentUrl() . $connectionData->getResource('checkoutUrl') . "?code=$code";
    }

    /**
     * @return string
     */
    private function getStoreReference()
    {
        return \UOL\PagSeguro\Helper\Data::getStoreReference(
            $this->_scopeConfig->getValue('pagseguro/store/reference'),
            $this->_checkoutSession->getLastRealOrder()->getEntityId()
        );
    }
}
