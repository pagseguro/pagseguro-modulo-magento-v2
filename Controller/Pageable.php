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

namespace UOL\PagSeguro\Controller;

abstract class Pageable extends \Magento\Backend\App\Action
{
    /**
     * Result page factory
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->_resultPageFactory = $resultPageFactory;
    }

    /**
     * Generate Admin Url
     *
     * @return string
     */
    protected function getAdminUrl()
    {
        return sprintf("%s%s",$this->getBaseUrl(), $this->getAdminSuffix());
    }

    /**
     * Get admin suffix from config
     *
     * @return string
     */
    private function getAdminSuffix()
    {
        $configReader = $this->_objectManager->create('Magento\Framework\App\DeploymentConfig\Reader');
        $config = $configReader->load();
        return $config['backend']['frontName'];
    }

    /**
     * Get store base url
     *
     * @return string
     */
    private function getBaseUrl()
    {
        $storeManager = $this->_objectManager->create('Magento\Store\Model\StoreManagerInterface');
        return $storeManager->getStore()->getBaseUrl();
    }
}