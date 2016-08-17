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
     * InstallmentsMethod constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Framework\Module\ModuleList $moduleList
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Sales\Model\Order $order,
        \Magento\Framework\Module\ModuleList $moduleList
    ) {
        $this->_scopeConfig = $scopeConfigInterface;
        $this->_order = $order;
        $this->_library = new Library($scopeConfigInterface, $moduleList);
    }
    /**
     * Create the request to return the installments
     * @return \PagSeguroPaymentRequest
     */
    public function create($creditCardBrand, $creditCardInternational = false)
    {   
        $options = [
            'amount' => $this->getTotalAmount(),
            'card_brand' => $creditCardBrand
        ];

        try {
            $this->_library->setEnvironment();
            $this->_library->setCharset();
            $this->_library->setLog();
            
            $installments = \PagSeguro\Services\Installment::create(
                $this->_library->getPagSeguroCredentials(),
                $options
            );
            
            return $this->formatInstallmentsOutput(
                $installments->getInstallments(),
                $creditCardBrand,
                $creditCardInternational
            );

        } catch (PagSeguroServiceException $ex) {
            $this->logger->debug($ex->getMessage());
            $this->getCheckoutRedirectUrl();
        }
    }
    
    /**
     * Get the total amount of the current order until the second decimal place
     * @return type
     */
    private function getTotalAmount()
    {   
        return round($this->_order->getGrandTotal(),2);
        
    }
    
    /**
     * Format the installment to the be show in the view
     * @param  array $installments
     * @return array
     */
    private function formatInstallmentsOutput($installments, $creditCardBrand, $creditCardInternational)
    {        
        $formatedInstallments = [
            'cardBrand' => $creditCardBrand,
            'cardInternational' => $creditCardInternational
        ];

        foreach($installments as $installment) {
           $formatedInstallments['installments'][] = [
               'quantity' => $installment->getQuantity(),
               'amount' => $installment->getAmount(),
               'totalAmount' => round($installment->getTotalAmount(), 2),
               'text' => $this->getInstallmentText($installment)
            ];
        }

        return $formatedInstallments;
    }
    
    /**
     * Mount the text message of the installment
     * @param  object $installment
     * @return string
     */
    private function getInstallmentText($installment)
    {
        return sprintf(
            "%sx de R$ %.2f %s juros",
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
    
}
