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

namespace UOL\PagSeguro\Block\Product\View;

use Magento\Framework\View\Element\Template;

/**
 * Get all the data to display an installment list in the product view
 *
 */
class Installments extends Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    /**
     * InstallmentMethod auto-generated factory
     * @var \UOL\PagSeguro\Model\Direct\InstallmentsMethod
     */
    protected $_installmentFactory;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \UOL\PagSeguro\Model\Direct\InstallmentsMethodFactory $installmentFactory
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \UOL\PagSeguro\Model\Direct\InstallmentsMethodFactory $installmentFactory
    ) {
        $this->_coreRegistry = $context->getRegistry();
        $this->_installmentFactory = $installmentFactory;
        parent::__construct($context);
    }
   
    /**
     * Retrieve currently viewed product object
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        if (!$this->hasData('product')) {
            $this->setData('product', $this->_coreRegistry->registry('product'));
        }
        return $this->getData('product');
    }

    /**
     * Get the bigger installment list for the value passed
     * @param mixed $value
     * @return array
     */
    public function getInstallment($value)
    {
        $installments = $this->_installmentFactory->create();
        $output = $installments->create(round($value, 2), true);

        return $output['installments'];
    }
    
    /**
     * Validate if the PagSeguro installments list in the product view is enabled
     * @return bool
     */
    public function isEnabled() {
        $status = $this->_scopeConfig->getValue('payment/pagseguro/installments');
        return (! is_null($status) && $status == 1) ? true : false;
    }
}
