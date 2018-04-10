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
     * Get all transactions where there is a pagseguro transaction code
     * @return array
     * @throws \Exception
     */
    public function searchTransactions()
    {
        try {
            $connection = $this->getConnection();
            $select = $connection->select()
                ->from( ['order' => 'sales_order'], ['status', 'created_at', 'increment_id', 'store_id', 'entity_id'] )
                ->join( ['ps' => 'pagseguro_orders'], 'order.entity_id = ps.order_id')
                ->where('ps.transaction_code != ?', '')
                ->order('order.created_at DESC');

            if (!is_null($this->_session->getData('store_id'))) {
                $select = $select->where('order.store_id = ?', $this->_session->getData('store_id'));
            }

            if ($this->_scopeConfig->getValue('payment/pagseguro/environment')) {
                $select = $select->where('ps.environment = ?', $this->_scopeConfig->getValue('payment/pagseguro/environment'));
            }

            if (!empty($this->_idMagento)) {
                $select = $select->where('order.increment_id = ?', $this->_idMagento);
            }

            if (!empty($this->_idPagseguro)) {
                $select = $select->where('ps.transaction_code = ?', $this->_idPagseguro);
            }

            if (!empty($this->_status)) {
                $select = $this->getStatusFromPaymentKey($this->_status) == 'partially_refunded'
                    ? $select->where('ps.partially_refunded = ?', 1)
                    : $select->where('order.status = ?', $this->getStatusFromPaymentKey($this->_status));
            }

            if (!empty($this->_dateBegin) && !empty($this->_dateEnd)) {
                $startDate = date('Y-m-d H:i:s', strtotime(str_replace("/", "-", $this->_dateBegin)));
                $endDate = date('Y-m-d'.' 23:59:59', strtotime(str_replace("/", "-", $this->_dateEnd)));
                $select = $select->where('order.created_at >= ?', $startDate)->where('order.created_at <= ?', $endDate);
            }

            $connection->prepare($select);
            return $connection->fetchAll($select);
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /** Get and formats transaction details
     * @param $transactionCode
     * @throws Exception
     */
    public function getDetailsTransaction($transactionCode)
    {
        $this->_detailsTransactionByCode = $this->getTransactionsByCode($transactionCode);

        if(!empty($this->_detailsTransactionByCode)){
            $order = $this->decryptOrderById($this->_detailsTransactionByCode);

            if ($this->getStoreReference() == $this->decryptReference($this->_detailsTransactionByCode)) {
                if ($this->_detailsTransactionByCode->getStatus() == $this->getKeyFromOrderStatus($order->getStatus())) {
                    $this->_detailsTransactionByCode = $this->buildDetailsTransaction();
                    $this->_needConciliate = false;
                }
            }
        }
    }

    /**
     * Request PagSeguroTransaction details by code
     * @param $code
     *
     * @return null|object
     * @throws Exception
     */
    public function getTransactionsByCode($code)
    {
        $this->_library->setEnvironment();
        $this->_library->setCharset();
        $this->_library->setLog();

        $response = null;
        try {
            $response = \PagSeguro\Services\Transactions\Search\Code::search(
                $this->_library->getPagSeguroCredentials(),
                $code
            );

            return $response;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
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
    protected function getKeyFromOrderStatus($status)
    {
        $param = is_object($status) ? $status->getStatus() : $status;
        return \UOL\PagSeguro\Helper\Data::getKeyFromStatus($param);
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
     * @param string $status
     * @param integer $isPartiallyRefunded
     * @return bool|string
     */
    protected function formatMagentoStatus($status, $isPartiallyRefunded = 0)
    {
        return $isPartiallyRefunded
            ? $this->getStatusString($this->getKeyFromOrderStatus($status)) . ' (estornada parcialmente)'
            : $this->getStatusString($this->getKeyFromOrderStatus($status));
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
        $param = is_object($order) ? $order->getCreatedAt() : $order;
        return date("d/m/Y H:i:s", strtotime($param));
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
     * Update column 'partially_refunded' the `pagseguro_orders` table
     *
     * @param int $orderId
     * @return void
     */
    protected function updatePartiallyRefundedPagSeguro($orderId)
    {
        $this->getConnection()
            ->query(sprintf(
                "UPDATE `%s` SET partially_refunded = 1 WHERE entity_id='%s'",
                $this->getPrefixTableName('pagseguro_orders'),
                $orderId
            ));
    }

    /**
     * Get all pagseguro partially refunded orders id
     *
     * @return array
     */
    protected function getPartiallyRefundedOrders()
    {
        $pagseguroOrdersIdArray = array();

        $connection = $this->getConnection();
        $select = $connection->select()
            ->from( ['ps' => $this->getPrefixTableName('pagseguro_orders')], ['order_id'] )
            ->where('ps.partially_refunded = ?', '1');

        if ($this->_scopeConfig->getValue('payment/pagseguro/environment')) {
            $select = $select->where('ps.environment = ?', $this->_scopeConfig->getValue('payment/pagseguro/environment'));
        }

        $connection->prepare($select);

        foreach ($connection->fetchAll($select) as $value) {
            $pagseguroOrdersIdArray[] = $value['order_id'];
        }

        return $pagseguroOrdersIdArray;
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

    /**
     * Build and format the transaction data for the listing
     * @return array
     */
    public function buildDetailsTransaction()
    {
        return array(
            'date'              => $this->formatDate($this->_detailsTransactionByCode->getDate()),
            'code'              => $this->_detailsTransactionByCode->getCode(),
            'reference'         => $this->_detailsTransactionByCode->getReference(),
            'type'              => \UOL\PagSeguro\Helper\Data::getTransactionTypeName($this->_detailsTransactionByCode->getType()),
            'status'            => \UOL\PagSeguro\Helper\Data::getPaymentStatusToString($this->_detailsTransactionByCode->getStatus()),
            'lastEventDate'     => $this->formatDate($this->_detailsTransactionByCode->getLastEventDate()),
            'installmentCount'  => $this->_detailsTransactionByCode->getInstallmentCount(),
            'cancelationSource' => \UOL\PagSeguro\Helper\Data::getTitleCancellationSourceTransaction($this->_detailsTransactionByCode->getCancelationSource()),
            'discountAmount'    => $this->_detailsTransactionByCode->getDiscountAmount(),
            'escrowEndDate'     => $this->formatDate($this->_detailsTransactionByCode->getEscrowEndDate()),
            'extraAmount'       => $this->_detailsTransactionByCode->getExtraAmount(),
            'feeAmount'         => $this->_detailsTransactionByCode->getFeeAmount(),
            'grossAmount'       => $this->_detailsTransactionByCode->getGrossAmount(),
            'netAmount'         => $this->_detailsTransactionByCode->getNetAmount(),
            'creditorFees'      => $this->prepareCreditorFees(),
            'itemCount'         => $this->_detailsTransactionByCode->getItemCount(),
            'items'             => $this->prepareItems(),
            'paymentMethod'     => $this->preparePaymentMethod(),
            'sender'            => $this->prepareSender(),
            'shipping'          => $this->prepareShipping(),
            'paymentLink'       => $this->_detailsTransactionByCode->getPaymentLink(),
            'promoCode'         => $this->_detailsTransactionByCode->getPromoCode()
        );
    }

    /**
     * Format transaction CreditorFees
     * @return array|string
     */
    private function prepareCreditorFees()
    {
        $creditorFees = "";
        if(!empty($this->_detailsTransactionByCode->getCreditorFees()))
        {
            $creditorFees = array(
                'intermediationRateAmount'  => $this->_detailsTransactionByCode->getCreditorFees()->getIntermediationRateAmount(),
                'intermediationFeeAmount'   => $this->_detailsTransactionByCode->getCreditorFees()->getIntermediationFeeAmount(),
                'installmentFeeAmount'      => $this->_detailsTransactionByCode->getCreditorFees()->getInstallmentFeeAmount(),
                'operationalFeeAmount'      => $this->_detailsTransactionByCode->getCreditorFees()->getOperationalFeeAmount(),
                'commissionFeeAmount'       => $this->_detailsTransactionByCode->getCreditorFees()->getCommissionFeeAmount()
            );
        }
        return $creditorFees;
    }

    /**
     * Format transaction Items
     * @return array
     */
    private function prepareItems()
    {
        $itens = array();

        if($this->_detailsTransactionByCode->getItemCount() > 0) {
            foreach ($this->_detailsTransactionByCode->getItems() as $item)
            {
                $itens[] = array(
                    'id'            => $item->getId(),
                    'description'   => $item->getDescription(),
                    'quantity'      => $item->getQuantity(),
                    'amount'        => $item->getAmount(),
                    'weight'        => $item->getWeight(),
                    'shippingCost'  => $item->getShippingCost()
                );
            }
        }
        return $itens;
    }

    /**
     * Format transaction PaymentMethod
     * @return array|string
     */
    private function preparePaymentMethod()
    {
        $paymentMethod = "";

        if(!empty($this->_detailsTransactionByCode->getPaymentMethod()))
        {
            $paymentMethod = array(
                'code' => $this->_detailsTransactionByCode->getPaymentMethod()->getCode(),
                'type' => $this->_detailsTransactionByCode->getPaymentMethod()->getType(),
                'titleType' => \UOL\PagSeguro\Helper\Data::getTitleTypePaymentMethod($this->_detailsTransactionByCode->getPaymentMethod()->getType()),
                'titleCode' => \UOL\PagSeguro\Helper\Data::getTitleCodePaymentMethod($this->_detailsTransactionByCode->getPaymentMethod()->getCode())
            );
        }
        return $paymentMethod;
    }

    /**
     * Format transaction Sender
     * @return array
     */
    private function prepareSender()
    {
        $documents = array();
        if(count($this->_detailsTransactionByCode->getSender()->getDocuments()) > 0) {
            foreach ($this->_detailsTransactionByCode->getSender()->getDocuments() as $doc)
            {
                $documents[] = array(
                    'type'      => $doc->getType(),
                    'identifier' => $doc->getIdentifier()
                );
            }
        }

        $sender = array();
        if(!empty($this->_detailsTransactionByCode->getSender())){
            $sender = array(
                'name'  => $this->_detailsTransactionByCode->getSender()->getName(),
                'email' => $this->_detailsTransactionByCode->getSender()->getEmail(),
                'phone' => array(
                    'areaCode' => $this->_detailsTransactionByCode->getSender()->getPhone()->getAreaCode(),
                    'number' => $this->_detailsTransactionByCode->getSender()->getPhone()->getNumber()
                ),
                'documents' => $documents
            );
        }
        return $sender;
    }

    /**
     * Format transaction Shipping
     * @return array
     */
    private function prepareShipping()
    {
        $shipping = array();
        if(!empty($this->_detailsTransactionByCode->getShipping())){
            $shipping = array(
                'addres' => array(
                    'street'    => $this->_detailsTransactionByCode->getShipping()->getAddress()->getStreet(),
                    'number'    => $this->_detailsTransactionByCode->getShipping()->getAddress()->getNumber(),
                    'complement' => $this->_detailsTransactionByCode->getShipping()->getAddress()->getComplement(),
                    'district'  => $this->_detailsTransactionByCode->getShipping()->getAddress()->getDistrict(),
                    'postalCode' => $this->_detailsTransactionByCode->getShipping()->getAddress()->getPostalCode(),
                    'city'      => $this->_detailsTransactionByCode->getShipping()->getAddress()->getCity(),
                    'state'     => $this->_detailsTransactionByCode->getShipping()->getAddress()->getState(),
                    'country'   => $this->_detailsTransactionByCode->getShipping()->getAddress()->getCountry()
                ),
                'type' => $this->_detailsTransactionByCode->getShipping()->getType()->getType(),
                'cost' => $this->_detailsTransactionByCode->getShipping()->getCost()->getCost()
            );
        }
        return $shipping;
    }

}