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

namespace UOL\PagSeguro\Model\Transactions;
use UOL\PagSeguro\Helper\Data;

/**
 * Class Conciliation
 *
 * @package UOL\PagSeguro\Model\Transactions
 */
class ConciliationMethod
{

    /**
     * @var integer
     */
    private $_days;

    /**
     * @var array
     */
    private $_arrayPayments = array();

    /**
     * @var \PagSeguro\Parsers\Transaction\Search\Date\Response
     */
    private $_PagSeguroPaymentList;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $_scopeConfig;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Grid
     */
    private $_salesGrid;

    /**
     * @var \Magento\Backend\Model\Session
     */
    private $_session;

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $_order;

    /**
     * @var \UOL\PagSeguro\Helper\Library
     */
    private $_library;

    /**
     * @var \UOL\PagSeguro\Helper\Crypt
     */
    private $_crypt;

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
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Backend\Model\Session $session,
        \Magento\Sales\Model\Order $order,
        \UOL\PagSeguro\Helper\Library $library,
        \UOL\PagSeguro\Helper\Crypt $crypt,
        $days = null
    ) {
        //load magento dependencies by di
        $this->_scopeConfig = $scopeConfigInterface;
        $this->_session = $session;
        $this->_order = $order;
        //load helpers by di
        $this->_library = $library;
        $this->_crypt = $crypt;
        //load days by di
        $this->_days = $days;
        // create new instanceof \Magento\Sales\Model\ResourceModel\Grid(
        $this->_salesGrid = new \Magento\Sales\Model\ResourceModel\Grid($context, 'pagseguro_orders', 'sales_order_grid', 'order_id');
    }

    /**
     * Conciliate one or many transactions
     *
     * @param $data
     * @return bool
     * @throws \Exception
     */
    public function conciliate($data) {

        try {
            foreach ($data as $transaction) {
                // decrypt data from ajax
                $config = $this->_crypt->decrypt('!QAWRRR$HU%W34tyh59yh544%', $transaction);
                // sanitize special chars for url format
                $config = filter_var($config, FILTER_SANITIZE_URL);
                // decodes json to object
                $config = json_decode($config);
                // load order by id
                $order = $this->_order->load($config->order_id);
                // change payment status in magento
                $order->addStatusToHistory(Data::getStatusFromKey($config->pagseguro_status), null, true);
                // save order
                $order->save();

                $this->updateSalesOrderGridTransactionCode($config->order_id, $config->pagseguro_id);
                $this->updatePagSeguroOrdersTransactionCode($config->order_id, $config->pagseguro_id);
                unset($order);
            }
            return true;
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Get all transactions and orders and return builded data
     *
     * @return array
     */
    public function requestTransactionsToConciliation()
    {

        //load payments by date
        $this->getPagSeguroPayments();

        if ($this->_PagSeguroPaymentList->getTransactions()) {
            foreach ($this->_PagSeguroPaymentList->getTransactions() as $payment) {

                $order = \UOL\PagSeguro\Helper\Data::getReferenceDecryptOrderID($payment->getReference());
                $order = $this->_order->load($order);

                if ($this->getStoreReference() == \UOL\PagSeguro\Helper\Data::getReferenceDecrypt($payment->getReference())) {
                    if (!is_null($this->_session->getData('store_id'))) {
                        if ($order->getStatus()
                            != \UOL\PagSeguro\Helper\Data::getStatusFromKey($payment->getStatus)
                        ) {
                            array_push($this->_arrayPayments, $this->build($payment, $order));
                        }
                    }
                    if ($order) {
                        if ($order->getStatus()
                            != \UOL\PagSeguro\Helper\Data::getStatusFromKey($payment->getStatus())
                        ) {
                            array_push($this->_arrayPayments, $this->build($payment, $order));
                        }
                    }
                }
            }
        }
        return $this->_arrayPayments;
    }

    /**
     * Build data for datatable
     *
     * @param $payment
     * @param $order
     * @return array
     */
    private function build($payment, $order)
    {
        return  [
            'date'             => date("d/m/Y H:i:s", strtotime($order->getCreatedAt())),
            'magento_id'       => sprintf('#%s', $order->getIncrementId()),
            'magento_status'   => \UOL\PagSeguro\Helper\Data::getPaymentStatusToString(
                \UOL\PagSeguro\Helper\Data::getKeyFromStatus($order->getStatus())
            ),
            'pagseguro_id'     => $payment->getCode(),
            'pagseguro_status' => \UOL\PagSeguro\Helper\Data::getPaymentStatusToString($payment->getStatus()),
            'order_id'         => $order->getId(),
            'details'          => $this->_crypt->encrypt('!QAWRRR$HU%W34tyh59yh544%', json_encode([
                'order_id' => $order->getId(),
                'pagseguro_status' => $payment->getStatus(),
                'pagseguro_id' => $payment->getCode()
            ]))
        ];
    }

    /**
     * Get PagSeguro payments
     *
     * @param null $page
     * @return \PagSeguro\Parsers\Transaction\Search\Date\Response|string
     * @throws \Exception
     */
    private function getPagSeguroPayments($page = null)
    {
        //check if has a page, if doesn't have one then start at the first.
        if (is_null($page)) $page = 1;

        try {
            //check if is the first step, if is just add the response object to local var
            if (is_null($this->_PagSeguroPaymentList)) {
                $this->_PagSeguroPaymentList = $this->requestPagSeguroPayments($page);
            } else {

                $response = $this->requestPagSeguroPayments($page);
                //update some important data
                $this->_PagSeguroPaymentList->setDate($response->getDate());
                $this->_PagSeguroPaymentList->setCurrentPage($response->getCurrentPage());
                $this->_PagSeguroPaymentList->setResultsInThisPage(
                    $response->getResultsInThisPage() + $this->_PagSeguroPaymentList->getResultsInThisPage()
                );
                //add new transactions
                $this->_PagSeguroPaymentList->addTransactions($response->getTransactions());
            }

            //check if was more pages
            if ($this->_PagSeguroPaymentList->getTotalPages() > $page) {
                $this->getPagSeguroPayments(++$page);
            }
        } catch (\Exception $exception) {
            throw $exception;
        }
        return $this->_PagSeguroPaymentList;
    }

    /**
     * Request all PagSeguroTransaction in this _date interval
     *
     * @param $page
     * @return string
     * @throws \Exception
     */
    private function requestPagSeguroPayments($page)
    {

        $date = $this->getDates();

        $options = [
            'initial_date' => $date['initial'],
            'final_date' => $date['final'],
            'page' => $page,
            'max_per_page' => 1000,
        ];

        try {

            $this->_library->setEnvironment();
            $this->_library->setCharset();
            $this->_library->setLog();

            return \PagSeguro\Services\Transactions\Search\Date::search(
                $this->_library->getPagSeguroCredentials(),
                $options
            );

        } catch (Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Get date interval from days qty.
     *
     * @return array
     */
    private function getDates()
    {
        $date = new \DateTime ( "now" );
        $date->setTimezone ( new \DateTimeZone ( "America/Sao_Paulo" ) );

        $final = $date->format ( "Y-m-d\TH:i:s" );

        $dateInterval = "P" . ( string ) $this->_days . "D";
        $date->sub ( new \DateInterval ( $dateInterval ) );
        $date->setTime ( 00, 00, 00 );
        $initial = $date->format ( "Y-m-d\TH:i:s" );

        return [
            'initial' => $initial,
            'final'   => $final
        ];
    }

    /**
     * Get store reference
     *
     * @return mixed
     */
    private function getStoreReference()
    {
        return $this->_scopeConfig->getValue('pagseguro/store/reference');
    }

    /**
     * Update the sales_order_grid table transaction code
     *
     * @param int $orderId
     * @param string $transactionCode
     */
    private function updateSalesOrderGridTransactionCode($orderId, $transactionCode)
    {
        $this->_salesGrid->getConnection()->query(
            "UPDATE sales_order_grid
            SET transaction_code='$transactionCode'
            WHERE entity_id=$orderId"
        );
    }

    /**
     * Update the pagseguro_orders table transaction code
     *
     * @param int $orderId
     * @param string $transactionCode
     */
    private function updatePagSeguroOrdersTransactionCode($orderId, $transactionCode)
    {
        $this->_salesGrid->getConnection()->query(
            "UPDATE pagseguro_orders
            SET transaction_code='$transactionCode'
            WHERE order_id=$orderId"
        );
    }
}