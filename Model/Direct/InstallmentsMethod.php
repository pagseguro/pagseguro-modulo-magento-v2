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
/**
 * Class PaymentMethod
 * @package UOL\PagSeguro\Model
 */
class InstallmentsMethod
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
     * @var \Magento\Directory\Api\CountryInformationAcquirerInterface
     */
    protected $_countryInformation;
    
    /**
     * Installment options:
     *      amount
     *      card_brand
     * @var array
     */
    private $options;

    /**
     * InstallmentsMethod constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Framework\Module\ModuleList $moduleList
     */
    public function __construct(
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
        /** @var \UOL\PagSeguro\Helper\Library _library */
        $this->_library = $library;
    }

    /**
     * Create the request to return the installments
     * If $amount is passed it will be use as the default value
     * If $maxInstallment is true it will return the bigger installment list available
     * 
     * @param mixed $amount (optional)
     * @param midex $maxInstallment (optional)
     * @return array
     * @throws \Exception
     */
    public function create($amount = false, $maxInstallment = false)
    {
        try {
            $this->config();
            $this->setOptions($this->getTotalAmount($amount), $this->getBrand());
            $installments = \PagSeguro\Services\Installment::create(
                $this->_library->getPagSeguroCredentials(),
                $this->getOptions()
            );

            return $this->output($installments->getInstallments(), $maxInstallment);
        } catch (PagSeguroServiceException $exception) {
            throw $exception;
        }
    }

    /**
     * Getter of the options attribute
     * @return array
     */
    private function getOptions() {
        return $this->options;
    }

    /**
     * Setter the options attribute
     * @param mixed $amount
     * @param string $brand
     */
    private function setOptions($amount, $brand) {
        $this->options = [
            'amount' => $amount,
            'card_brand' => $brand
        ];
    }
    
    /**
     * Get the brand from the attribute _data or return an empty string
     * @return string
     */
    private function getBrand()
    {
        return (isset($this->_data['brand'])) ? $this->_data['brand'] : '';
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
     * Get the total amount of the current order until the second decimal place
     * if the amount is not passed to the function
     * @return type
     */
    private function getTotalAmount($amount)
    {   
        return (!$amount) ? round($this->_order->getGrandTotal(),2) : $amount;
        
    }

    /**
     * Return a formated output of installments
     *
     * @param array $installments
     * @param bool $maxInstallments
     * @return array
     */
    private function output($installments, $maxInstallment)
    {
        return ($maxInstallment) ?
            $this->formatOutput($this->getMaxInstallment($installments)) :
            $this->formatOutput($installments);
    }
    
    /**
     * Format the installment to the be show in the view
     * @param  array $installments
     * @return array
     */
    private function formatOutput($installments)
    {
        $response = $this->getOptions();
        foreach($installments as $installment) {
            $response['installments'][] = $this->formatInstallments($installment);
        }
        return $response;
    }

    /**
     * Format a installment for output
     *
     * @param $installment
     * @return array
     */
    private function formatInstallments($installment)
    {
        return [
            'quantity' => $installment->getQuantity(),
            'amount' => $installment->getAmount(),
            'totalAmount' => round($installment->getTotalAmount(), 2),
            'text' => str_replace('.', ',', $this->getInstallmentText($installment))
        ];
    }
    
    /**
     * Mount the text message of the installment
     * @param  object $installment
     * @return string
     */
    private function getInstallmentText($installment)
    {
        return sprintf(
            "%s x de R$ %.2f %s juros",
            $installment->getQuantity(),
            $installment->getAmount(),
            $this->getInterestFreeText($installment->getInterestFree()));
    }
    
    /**
     * Get the string relative to if it is an interest free or not
     * @param string $insterestFree
     * @return string
     */
    private function getInterestFreeText($insterestFree)
    {
        return ($insterestFree == 'true') ? 'sem' : 'com';
    }
    
    /**
     * Get the bigger installments list in the installments
     * @param array $installments
     * @return array
     */
    private function getMaxInstallment($installments)
    {
        $final = $current = ['brand' => '', 'start' => 0, 'final' => 0, 'quantity' => 0];

        foreach ($installments as $key => $installment) {
            if ($current['brand'] !== $installment->getCardBrand()) {
                $current['brand'] = $installment->getCardBrand();
                $current['start'] = $key;
            }

            $current['quantity'] = $installment->getQuantity();
            $current['end'] = $key;

            if ($current['quantity'] > $final['quantity']) {
                $final = $current;
            }
        }
        
        return array_slice(
            $installments,
            $final['start'],
            $final['end'] - $final['start'] + 1
        );
    }
}
