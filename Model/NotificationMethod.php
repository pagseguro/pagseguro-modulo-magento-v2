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

use UOL\PagSeguro\Helper\Data;
use UOL\PagSeguro\Helper\Library;
use Magento\Sales\Model\ResourceModel\Grid;

/**
 * Class NotificationMethod
 * @package UOL\PagSeguro\Model
 */
class NotificationMethod
{

    /**
     * @var \UOL\PagSeguro\Helper\Library
     */
    private $_library;
    /**
     * @var \UOL\PagSeguro\Helper\Data
     */
    private $_helperData;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $_order;
    /**
     * @var Magento\Sales\Model\ResourceModel\Grid;
     */
    protected $_grid;

    /**
     * Notification constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Magento\Sales\Api\OrderRepositoryInterface $order
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Sales\Api\OrderRepositoryInterface $order,
        \Magento\Sales\Api\Data\OrderStatusHistoryInterface $history,
        \Magento\Framework\Module\ModuleList $moduleList,
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        //dependency injection
        $this->_order = $order;
        $this->_history = $history;
        // create required objects
        $this->_library = new Library(
            $scopeConfigInterface,
            $moduleList
        );
        $this->_helperData = new Data();
        $this->_grid = new Grid($context, 'pagseguro_orders', 'sales_order_grid', 'order_id');
    }

    /**
     * Initialize a notification
     */
    public function init()
    {
        $this->updateOrderStatus();
    }

    /**
     * Update status in Magento2 Order
     *
     * @return bool
     */
    private function updateOrderStatus()
    {
        $this->_library->setEnvironment();
        $this->_library->setCharset();
        $this->_library->setLog();
        $transaction = $this->getTransaction();
        $order = $this->_order->get(
            $this->_helperData->getReferenceDecryptOrderID(
                $transaction->getReference()
            )
        );

        $status = $this->_helperData->getStatusFromKey(
            $transaction->getStatus()
        );

        if (!$this->compareStatus($status, $order->getStatus())) {
            $history = array (
                'status' => $this->_history->setStatus($status),
                'comment' => $this->_history->setComment('PagSeguro Notification')
            );
            $transactionCode = $transaction->getCode();
            $orderId = $order->getId();

            $order->setStatus($status);
            $order->setStatusHistories($history);
            $order->save();
            
            $this->updateSalesOrderGridTransactionCode($orderId, $transactionCode);
            $this->updatePagSeguroOrdersTransactionCode($orderId, $transactionCode);
        }
        return true;
    }

    /**
     * Get payload information from Post
     * @param $post
     * @return array
     */
    private function payload($post)
    {
        return array(
            'type' => filter_var($post['notificationType'], FILTER_SANITIZE_STRING),
            'code' => filter_var($post['notificationCode'], FILTER_SANITIZE_STRING)
        );
    }

    /**
     * Get transaction from PagSeguro WS.
     * @param $code
     * @return \PagSeguroTransaction
     * @throws \Exception
     * @throws \PagSeguroServiceException
     */
    private function getTransaction()
    {
        return \PagSeguro\Services\Transactions\Notification::check(
            $this->_library->getPagSeguroCredentials()
        );
    }

    /**
     * Compare statuses
     * @param $pagseguro
     * @param $magento
     * @return bool
     */
    private function compareStatus($pagseguro, $magento)
    {
        if ($pagseguro == $magento) {
            return true;
        }
        return false;
    }
    
    /**
     * Update the sales_order_grid table transaction code
     * @param int $orderId
     * @param string $transactionCode
     */
    private function updateSalesOrderGridTransactionCode($orderId, $transactionCode) 
    {
        $this->_grid->getConnection()->query(
            "UPDATE sales_order_grid
            SET transaction_code='$transactionCode'
            WHERE entity_id=$orderId"
        );
    }
    
    /**
     * Update the pagseguro_orders table transaction code
     * @param int $orderId
     * @param string $transactionCode
     */
    private function updatePagSeguroOrdersTransactionCode($orderId, $transactionCode) 
    {
        $this->_grid->getConnection()->query(
            "UPDATE pagseguro_orders
            SET transaction_code='$transactionCode'
            WHERE order_id=$orderId"
        );
    }
}
