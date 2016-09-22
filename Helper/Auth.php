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
 * Class Auth
 * @package UOL\PagSeguro\Helper
 */
class Auth
{

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
        $this->_scopeConfig = $scopeConfigInterface;
    }


    /**
     * Check if has e-mail and token are correctly configured in the settings
     *
     * @return bool
     */
    public function hasCredentials()
    {
        if ($this->hasEmail() and $this->hasToken())
            return true;
        return false;
    }

    /**
     * Check if has a valid e-mail
     *
     * @return bool
     */
    private function hasEmail()
    {
        $email = $this->_scopeConfig->getValue('payment/pagseguro/email');

        // check for a e-mail
        if (!isset($email))
            return false;

        // check is the e-mail is valid
        if ($this->isValidEmail($email))
            return true;
        return false;
    }

    /**
     * Check if has a valid token
     *
     * @return bool
     */
    private function hasToken()
    {
        $token = $this->_scopeConfig->getValue('payment/pagseguro/token');
        // check for a e-mail
        if (!isset($token))
            return false;

        // check is the e-mail is valid
        if ($this->isValidToken($token))
            return true;
        return false;
    }

    /**
     * Validate e-mail
     *
     * @param $email
     * @return bool
     */
    private function isValidEmail($email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL))
            return true;
        return false;
    }

    /**
     * Validate token
     *
     * @param $token
     * @return bool
     */
    private function isValidToken($token)
    {
        if (strlen($token) >= 32)
            return true;
        return false;
    }
}
