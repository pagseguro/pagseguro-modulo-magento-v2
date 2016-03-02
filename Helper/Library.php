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

namespace UOL\PagSeguro\Helper;

/**
 * Class Library
 * @package UOL\PagSeguro\Helper
 */
class Library
{

    /**
     *
     */
    const STANDARD_JS = "https://stc.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.lightbox.js";
    /**
     *
     */
    const SANDBOX_JS = "https://stc.sandbox.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.lightbox.js";

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Library constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
    ) {
        $this->loader();
        $this->_scopeConfig = $scopeConfigInterface;
    }

    /**
     * Get the access credential
     * @return PagSeguroAccountCredentials
     */
    public function getPagSeguroCredentials()
    {
        $email = $this->_scopeConfig->getValue('payment/pagseguro/email');
        $token = $this->_scopeConfig->getValue('payment/pagseguro/token');
        return new \PagSeguroAccountCredentials($email, $token);
    }

    /**
     * @return bool
     */
    public function isLightboxCheckoutType()
    {
        if ($this->_scopeConfig->getValue('payment/pagseguro/checkout')
            == \UOL\PagSeguro\Model\System\Config\Checkout::LIGHTBOX) {
            return true;
        }
        return false;
    }

    /**
     * Load library vendor
     */
    private function loader()
    {
        \PagSeguroLibrary::init();
    }
}
