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
     *
     */
    const DIRECT_PAYMENT_URL = "https://stc.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js";
    /**
     *
     */
    const DIRECT_PAYMENT_URL_SANDBOX= "https://stc.sandbox.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js";

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
	/**
     * @var \Magento\Framework\Module\ModuleList
     */
	protected $_moduleList;
    /**
     * Library constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
		\Magento\Framework\Module\ModuleList $moduleList
    ) {
		$this->_moduleList = $moduleList;
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
        //Set the credentials
        \PagSeguro\Configuration\Configure::setAccountCredentials($email, $token);
        return \PagSeguro\Configuration\Configure::getAccountCredentials();
    }
    /**
     * @return bool
     */
    public function isLightboxCheckoutType()
    {
        if ($this->_scopeConfig->getValue('payment/pagseguro_default_lightbox/checkout')
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
        /** @var \Magento\Framework\App\ObjectManager $objectManager */
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        $timezone = $objectManager->create('\Magento\Framework\Stdlib\DateTime\TimezoneInterface');
        /** @var \Magento\Framework\App\ProductMetadataInterface $productMetadata */

        //set the store timezone to the script
        date_default_timezone_set($timezone->getConfigTimezone());
        \PagSeguro\Library::initialize();
		\PagSeguro\Library::cmsVersion()->setName("Magento")->setRelease($productMetadata->getVersion());
        \PagSeguro\Library::moduleVersion()->setName($this->_moduleList->getOne('UOL_PagSeguro')['name'])
			->setRelease($this->_moduleList->getOne('UOL_PagSeguro')['setup_version']);
    }

    /**
     * Set the environment configured in the PagSeguro module
     */
    public function setEnvironment()
    {
        \PagSeguro\Configuration\Configure::setEnvironment(
            $this->_scopeConfig->getValue('payment/pagseguro/environment')
        );
    }
    /**
     * Set the environment configured in the PagSeguro module
     */
    public function getEnvironment()
    {
       return $this->_scopeConfig->getValue('payment/pagseguro/environment');
    }

    /**
     * Set the charset configured in the PagSeguro module
     */
    public function setCharset()
    {
        \PagSeguro\Configuration\Configure::setCharset(
            $this->_scopeConfig->getValue('payment/pagseguro/charset')
        );
    }

    /**
     * Set the log and log location configured in the PagSeguro module
     */
    public function setLog()
    {
        \PagSeguro\Configuration\Configure::setLog(
            $this->_scopeConfig->getValue('payment/pagseguro/log'),
            $this->_scopeConfig->getValue('payment/pagseguro/log_file')
        );
    }


    /**
     * Get session
     *
     * @return mixed
     * @throws \Exception
     */
    public function getSession()
    {
        try {
            $session = \PagSeguro\Services\Session::create(
                $this->getPagSeguroCredentials()
            );
            return $session->getResult();
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Get direct payment url
     *
     * @return string
     */
    public function getDirectPaymentUrl()
    {
        if ($this->getEnvironment() == 'sandbox') {
            return Library::DIRECT_PAYMENT_URL_SANDBOX;
        } else {
            return Library::DIRECT_PAYMENT_URL;
        }
    }

    /**
     * Get image full frontend url
     * @return type
     */
    public function getImageUrl($imageModulePath)
    {
        /** @var \Magento\Framework\App\ObjectManager $om */
	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
	/** @var \Magento\Framework\View\Asset\Repository */
	$viewRepository = $objectManager->get('\Magento\Framework\View\Asset\Repository');
	return $viewRepository->getUrl($imageModulePath);
    }
}
