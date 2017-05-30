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
 * Class Cancellation
 *
 * @package UOL\PagSeguro\Model\Transactions
 */
class Cancellation extends Method
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
        /** @var  \Magento\Framework\App\ResourceConnection _resource */
        $this->_resource = $resourceConnection;
        /** @var  \Magento\Backend\Model\Session  _session */
        $this->_session = $session;
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
     * Cancels one transaction
     *
     * @param $data
     * @return bool
     * @throws \Exception
     */
    public function execute($data) {

        try {
            $config = $this->sanitizeConfig($data);
            $this->isConciliate($config);
            if (! $this->doCancel($config))
                throw new \Exception('impossible to cancel.');

            $this->doUpdates($config);
            return true;
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    private function isConciliate($config)
    {
        if (!$config->needConciliate)
            throw new \Exception('Need to conciliate');
        return true;
    }

    /**
     * Execute magento data updates
     *
     * @param $config
     * @throws \Exception
     */
    private function doUpdates($config)
    {
        try {
            $this->addStatusToOrder($config->order_id, 'pagseguro_cancelada');
            $this->updateSalesOrder($config->order_id, $config->pagseguro_id);
            $this->updatePagSeguroOrders($config->order_id, $config->pagseguro_id);
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
        $order->addStatusToHistory($status, null, true);
        $order->save();
    }

    /**
     * Execute cancellation
     *
     * @param $config
     * @return bool
     * @throws \Exception
     */
    private function doCancel($config)
    {
        if ($this->requestCancel($config)->getResult() == "OK")
            return true;
        throw new \Exception("an error occurred.");
    }

    /**
     * Request a PagSeguro Cancel
     *
     * @param $config
     * @return \PagSeguro\Parsers\Cancel\Response
     * @throws \Exception
     */
    private function requestCancel($config)
    {
        \PagSeguro\Configuration\Configure::setEnvironment(
            $this->_library->getEnvironment()
        );
        try {
            return \PagSeguro\Services\Transactions\Cancel::create(
                $this->_library->getPagSeguroCredentials(),
                $config->pagseguro_id
            );
        } catch (\Exception $exception) {
            throw $exception;
        }
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
        return $this->toArray($payment, $order, $this->checkConciliation($payment, $order));
    }

    /**
     * Create array
     *
     * @param $payment
     * @param $order
     * @param bool $conciliate
     * @return array
     */
    private function toArray($payment, $order, $conciliate = false)
    {
        return  [
            'date'             => $this->formatDate($order),
            'magento_id'       => $this->formatMagentoId($order),
            'magento_status'   => $this->formatMagentoStatus($order),
            'pagseguro_id'     => $payment->getCode(),
            'order_id'         => $order->getId(),
            'details'          => $this->details($order, $payment, ['conciliate' => $conciliate])
        ];
    }

    /**
     * Get data for details
     *
     * @param $order
     * @param $payment
     * @param $options
     * @return string
     */
    protected function details($order, $payment, $options)
    {
        return $this->_crypt->encrypt('!QAWRRR$HU%W34tyh59yh544%',
            json_encode([
                'order_id'         => $order->getId(),
                'pagseguro_status' => $payment->getStatus(),
                'pagseguro_id'     => $payment->getCode(),
                'needConciliate' => $options['conciliate']
            ])
        );
    }

    /**
     * Check for conciliation
     *
     * @param $payment
     * @param $order
     * @return bool
     */
    private function checkConciliation($payment, $order)
    {
        if ($order->getStatus() == $this->getStatusFromPaymentKey($payment->getStatus()))
            return true;
        return false;
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
        if (! (in_array($order->getStatus(), [
                $this->getStatusFromPaymentKey(1),
                $this->getStatusFromPaymentKey(2)
            ]) || in_array($payment->getStatus(), [1, 2]))) {
            return false;
        }
        return true;
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
