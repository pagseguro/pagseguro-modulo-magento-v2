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


abstract class Method
{

    /**
     * Sanitize configuration
     *
     * @param $data
     * @return mixed
     */
    protected function sanitizeConfig($data)
    {
        $config = $this->_crypt->decrypt('!QAWRRR$HU%W34tyh59yh544%', $data);
        $config = filter_var($config, FILTER_SANITIZE_URL);
        return json_decode($config);
    }

    /**
     * Get PagSeguro payments
     *
     * @param null $page
     * @return \PagSeguro\Parsers\Transaction\Search\Date\Response|string
     * @throws \Exception
     */
    protected function getTransactions($page = null)
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
    protected function requestPagSeguroPayments($page)
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
    protected function getDates()
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
     * @param $payment
     * @return mixed
     */
    protected function decryptOrderById($payment)
    {
        return $this->_order->load(\UOL\PagSeguro\Helper\Data::getReferenceDecryptOrderID($payment->getReference()));
    }

    /**
     * @param $status
     * @return bool|string
     */
    protected function getStatusFromPaymentKey($status)
    {
        return \UOL\PagSeguro\Helper\Data::getStatusFromKey($status);
    }

    /**
     * @param $status
     * @return bool|string
     */
    protected function getStatusString($status)
    {
        return \UOL\PagSeguro\Helper\Data::getPaymentStatusToString($status);
    }

    /**
     * @param $order
     * @return mixed
     */
    protected function getKeyFromOrderStatus($order)
    {
        return \UOL\PagSeguro\Helper\Data::getKeyFromStatus($order->getStatus());
    }

    /**
     * @param $payment
     * @return bool|string
     */
    protected function formatPagSeguroStatus($payment)
    {
        return $this->getStatusString($payment->getStatus());
    }

    /**
     * @param $order
     * @return bool|string
     */
    protected function formatMagentoStatus($order)
    {
        return $this->getStatusString($this->getKeyFromOrderStatus($order));
    }

    /**
     * @param $order
     * @return string
     */
    protected function formatMagentoId($order)
    {
        return sprintf('#%s', $order->getIncrementId());
    }

    /**
     * @param $order
     * @return false|string
     */
    protected function formatDate($order)
    {
        return date("d/m/Y H:i:s", strtotime($order->getCreatedAt()));
    }

    /**
     * @param $payment
     * @return string
     */
    protected function decryptReference($payment)
    {
        return \UOL\PagSeguro\Helper\Data::getReferenceDecrypt($payment->getReference());
    }

    /**
     * Get store reference
     *
     * @return mixed
     */
    protected function getStoreReference()
    {
        return $this->_scopeConfig->getValue('pagseguro/store/reference');
    }

    /**
     * Update the sales_order_grid table transaction code
     *
     * @param int $orderId
     * @param string $transactionCode
     */
    protected function updateSalesOrder($orderId, $transactionCode)
    {
        $this->updateOrders('sales_order_grid', $orderId, $transactionCode);
    }

    /**
     * Update the `pagseguro_orders` table
     *
     * @param int $orderId
     * @param string $transactionCode
     * @return void
     */
    protected function updatePagSeguroOrders($orderId, $transactionCode)
    {
        $this->updateOrders('pagseguro_orders', $orderId, $transactionCode);
    }

    /**
     * @param $table
     * @param $orderId
     * @param $transactionCode
     */
    private function updateOrders($table, $orderId, $transactionCode)
    {
        $this->getConnection()
             ->query(sprintf(
                 "UPDATE `%s` SET transaction_code='%s' WHERE entity_id='%s'",
                 $this->getPrefixTableName($table),
                 $transactionCode,
                 $orderId
             ));
    }

    /**
     * @return mixed
     */
    private function getConnection()
    {
        return $this->_resource->getConnection();
    }

    /**
     * @param $table
     * @return mixed
     */
    private function getPrefixTableName($table)
    {
        return $this->_resource->getTableName($table);
    }

    /**
     * @param $order
     * @param $payment
     * @param $options
     * @return mixed
     */
    abstract protected function details($order, $payment, $options);

    /**
     * @param $data
     * @return mixed
     */
    abstract public function execute($data);

    /**
     * @param $payment
     * @param $order
     * @return mixed
     */
    abstract protected function build($payment, $order);
}