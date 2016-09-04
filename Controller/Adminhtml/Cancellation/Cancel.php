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

namespace UOL\PagSeguro\Controller\Adminhtml\Cancellation;

use Magento\Backend\App\Action\Context;
use UOL\PagSeguro\Model\Transactions\CancellationMethod;

/**
 * Class Conciliation
 * @package UOL\PagSeguro\Controller\Adminhtml
 */
class Cancel extends \Magento\Backend\App\Action
{

    /**
     * Result json factory
     *
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /** @var \Magento\Framework\Controller\Result\Json  */
    protected $_result;

    /**
     * Conciliate constructor.
     *
     * @param Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->_resultJsonFactory = $resultJsonFactory;
        /** @var \Magento\Framework\Controller\Result\Json $_result */
        $this->_result = $this->_resultJsonFactory->create();
    }
    /**

     * @return void
     */
    public function execute()
    {

        $requests = $this->getRequest()->getParams();

        $cancellation = new CancellationMethod(
            $this->_objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface'),
            $this->_objectManager->create('Magento\Framework\App\ResourceConnection'),
            $this->_objectManager->create('Magento\Framework\Model\ResourceModel\Db\Context'),
            $this->_objectManager->create('Magento\Backend\Model\Session'),
            $this->_objectManager->create('Magento\Sales\Model\Order'),
            $this->_objectManager->create('UOL\PagSeguro\Helper\Library'),
            $this->_objectManager->create('UOL\PagSeguro\Helper\Crypt')
        );

        try {
            return $this->whenSuccess($cancellation->cancel($requests['data']));
        } catch (\Exception $exception) {
            return $this->whenError($exception->getMessage());
        }
    }

    /**
     * Return when success
     *
     * @param $response
     * @return $this
     */
    private function whenSuccess($response)
    {
        return $this->_result->setData([
            'success' => true,
            'payload' => [
                'data' => $response
            ]
        ]);
    }

    /**
     * Return when fails
     *
     * @param $message
     * @return $this
     */
    private function whenError($message)
    {
        return $this->_result->setData([
            'success' => false,
            'payload' => [
                'error'    => $message,
            ]
        ]);
    }

    /**
     * Cancellation access rights checking
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('UOL_PagSeguro::Cancellation');
    }
}