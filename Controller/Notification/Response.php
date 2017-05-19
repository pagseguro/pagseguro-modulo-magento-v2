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

namespace UOL\PagSeguro\Controller\Notification;

/**
 * Class Checkout
 * @package UOL\PagSeguro\Controller\Payment
 */
class Response extends \Magento\Framework\App\Action\Action
{

    /**
     * Response constructor.
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * Update a order
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        try {
            $nofitication = new \UOL\PagSeguro\Model\NotificationMethod(
                $this->_objectManager->create('\Magento\Framework\App\Config\ScopeConfigInterface'),
                $this->_objectManager->create('\Magento\Sales\Api\OrderRepositoryInterface'),
                $this->_objectManager->create('\Magento\Sales\Api\Data\OrderStatusHistoryInterface'),
                $this->_objectManager->create('Magento\Framework\Module\ModuleList'),
                $this->_objectManager->create('\Magento\Framework\Model\ResourceModel\Db\Context')
            );
            $nofitication->init();
        } catch (\Exception $ex) {
            //log already written in your pagseguro log file if pagseguro log is enabled in admin
            exit;
        }
    }
}
