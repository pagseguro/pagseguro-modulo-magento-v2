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

namespace UOL\PagSeguro\Model\Transactions\Methods;

use UOL\PagSeguro\Model\Transactions\Method;

/**
 * Class Conciliation
 *
 * @package UOL\PagSeguro\Model\Transactions
 */
class Conciliation extends Method
{

    /**
     * @var integer
     */
    protected $_days;

    /**
     * @var array
     */
    protected $_arrayPayments = array();

    /**
     * @var \PagSeguro\Parsers\Transaction\Search\Date\Response
     */
    protected $_PagSeguroPaymentList;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Grid
     */
    protected $_salesGrid;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $_session;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * @var \UOL\PagSeguro\Helper\Library
     */
    protected $_library;

    /**
     * @var \UOL\PagSeguro\Helper\Crypt
     */
    protected $_crypt;

    /**
     * Conciliation constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Magento\Backend\Model\Session $session
     * @param \Magento\Sales\Model\Order $order
     * @param \UOL\PagSeguro\Helper\Library $library
     * @param $days
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Backend\Model\Session $session,
        \Magento\Sales\Model\Order $order,
        \UOL\PagSeguro\Helper\Library $library,
        \UOL\PagSeguro\Helper\Crypt $crypt,
        $days = null
    ) {
        /** @var \Magento\Framework\App\Config\ScopeConfigInterface _scopeConfig */
        $this->_scopeConfig = $scopeConfigInterface;
        /** @var  \Magento\Backend\Model\Session  _session */
        $this->_session = $session;
        /** @var  \Magento\Framework\App\ResourceConnection _resource */
        $this->_resource = $resourceConnection;
        /** @var \Magento\Sales\Model\Order _order */
        $this->_order = $order;
        /** @var \UOL\PagSeguro\Helper\Library _library */
        $this->_library = $library;
        /** @var \UOL\PagSeguro\Helper\Crypt _crypt */
        $this->_crypt = $crypt;
        /** @var int _days */
        $this->_days = $days;
        /** @var \Magento\Sales\Model\ResourceModel\Grid _salesGrid */
        $this->_salesGrid = new \Magento\Sales\Model\ResourceModel\Grid(
            $context,
            'pagseguro_orders',
            'sales_order_grid',
            'order_id'
        );
    }

    /**
     * Get all transactions and orders and return formatted data
     *
     * @return array
     * @throws \Exception
     */
    public function request()
    {
        $this->getTransactions();
        if (! is_null($this->_PagSeguroPaymentList->getTransactions())) {

            foreach ($this->_PagSeguroPaymentList->getTransactions() as $payment) {
                if (! $this->addPayment($this->decryptOrderById($payment), $payment))
                    continue;
            }
        }
        return $this->_arrayPayments;
    }

    /**
     * Conciliate one or many transactions
     *
     * @param $data
     * @return bool
     * @throws \Exception
     */
    public function execute($data) {

        try {
            foreach ($data as $row) {
                $config = $this->sanitizeConfig($row);
                if (! $this->doUpdates($config))
                    throw new \Exception('impossible to conciliate.');
            }
            return true;
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Execute magento data updates
     *
     * @param $config
     * @return boolean
     * @throws \Exception
     */
    private function doUpdates($config)
    {
        try {
            $this->addStatusToOrder($config->order_id, $config->pagseguro_status);
            $this->updateSalesOrder($config->order_id, $config->pagseguro_id);
            $this->updatePagSeguroOrders($config->order_id, $config->pagseguro_id);
            return true;
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Change the magento order status
     *
     * @param $id int of order id
     * @param $status string of payment status
     */
    private function addStatusToOrder($id, $status)
    {
        $order = $this->_order->load($id);
        $order->addStatusToHistory($this->getStatusFromPaymentKey($status), null, true);
        $order->save();
    }

    /**
     * Add a needle conciliate payment to a list
     *
     * @param $order
     * @param $payment
     * @return bool
     */
    private function addPayment($order, $payment)
    {
        if ($this->compareStore($payment) && $this->hasOrder($order) && $this->compareStatus($order, $payment)){
            array_push($this->_arrayPayments, $this->build($payment, $order));
            return true;
        }
        return false;
    }

    /**
     * Build data for dataTable
     *
     * @param $payment
     * @param $order
     * @return array
     */
    protected function build($payment, $order)
    {
        return  [
            'date'             => $this->formatDate($order),
            'magento_id'       => $this->formatMagentoId($order),
            'magento_status'   => $this->formatMagentoStatus($order),
            'pagseguro_id'     => $payment->getCode(),
            'pagseguro_status' => $this->formatPagSeguroStatus($payment),
            'order_id'         => $order->getId(),
            'details'          => $this->details($order, $payment)
        ];
    }

    /**
     * Get data for details
     *
     * @param $order
     * @param $payment
     * @return string
     */
    protected function details($order, $payment, $options = null)
    {
        unset($options);
        return $this->_crypt->encrypt('!QAWRRR$HU%W34tyh59yh544%',
            json_encode([
                'order_id'         => $order->getId(),
                'pagseguro_status' => $payment->getStatus(),
                'pagseguro_id'     => $payment->getCode()
            ])
        );
    }

    /**
     * Compare stores
     *
     * @param $payment
     * @return bool
     */
    private function compareStore($payment)
    {
        if ($this->getStoreReference() != $this->decryptReference($payment))
            return false;
        return true;
    }

    /**
     * Compare between magento status and PagSeguro transaction status
     *
     * @param $order
     * @param $payment
     * @return bool
     */
    private function compareStatus($order, $payment)
    {
        if ($order->getStatus() == $this->getStatusFromPaymentKey($payment->getStatus()))
            return false;
        return true;
    }

    /**
     * Check if has a order
     *
     * @param $order
     * @return bool
     */
    private function hasOrder($order)
    {
        if (! $order)
            return false;
        return true;
    }
}
