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

use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\ResourceModel\Grid;
use UOL\PagSeguro\Model\Transactions\Method;

/**
 * Class Abandoned
 *
 * @package UOL\PagSeguro\Model\Transactions
 */
class Abandoned extends Method
{

    /**
     * @var int
     */
    const VALID_RANGE_DAYS = 10;

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
     * @var \UOL\PagSeguro\Helper\Crypt
     */
    protected $_timezone;

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
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Backend\Model\Session $session,
        \Magento\Sales\Model\Order $order,
        \UOL\PagSeguro\Helper\Library $library,
        \UOL\PagSeguro\Helper\Crypt $crypt,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        $days = null
    ) {
        /** @var \Magento\Framework\App\Config\ScopeConfigInterface _scopeConfig */
        $this->_scopeConfig = $scopeConfigInterface;
        /** @var Magento\Framework\App\ResourceConnection _resource */
        $this->_resource = $resourceConnection;
        /** @var  \Magento\Backend\Model\Session  _session */
        $this->_session = $session;
        /** @var \Magento\Sales\Model\Order _order */
        $this->_order = $order;
        /** @var \Magento\Framework\Mail\Template\TransportBuilder _transportBuilder */
        $this->_transportBuilder = $transportBuilder;
        /** @var \UOL\PagSeguro\Helper\Library _library */
        $this->_library = $library;
        /** @var \UOL\PagSeguro\Helper\Crypt _crypt */
        $this->_crypt = $crypt;
        /** @var int _days */
        $this->_days = $days;
        /** @var \Magento\Sales\Model\ResourceModel\Grid _salesGrid */
        $this->_salesGrid = new Grid(
            $context,
            'pagseguro_orders',
            'sales_order_grid',
            'order_id'
        );
        $this->_timezone = $timezone;
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
            foreach ($data as $transaction) {
                // decrypt data from ajax
                $config = $this->_crypt->decrypt('!QAWRRR$HU%W34tyh59yh544%', $transaction);
                // sanitize special chars for url format
                $config = filter_var($config, FILTER_SANITIZE_URL);
                // decodes json to object
                $config = json_decode($config);
                // load order by id
                $order = $this->_order->load($config->order_id);
                // Send email
                $this->sendEmail($order, $config->recovery_code);
                //increment sent
                $sent = current($this->getSent($config->order_id));
                $sent++;
                $this->setSent($config->order_id, $sent);
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
    public function request()
    {
        //load payments by date
        $this->getPagSeguroAbandoned();

        if ($this->_PagSeguroPaymentList->getTransactions()) {
            foreach ($this->_PagSeguroPaymentList->getTransactions() as $payment) {
                date_default_timezone_set('UTC');
                $order = \UOL\PagSeguro\Helper\Data::getReferenceDecryptOrderID($payment->getReference());
                $order = $this->_order->load($order);
                if ($this->getStoreReference() == \UOL\PagSeguro\Helper\Data::getReferenceDecrypt(
                        $payment->getReference())
                ) {
                    if (!is_null($this->_session->getData('store_id'))) {
                        array_push($this->_arrayPayments, $this->build($payment, $order));
                    }
                    if ($order) {
                        array_push($this->_arrayPayments, $this->build($payment, $order));
                    }
                }
            }
        }
        date_default_timezone_set($this->_timezone->getConfigTimezone());

        return $this->_arrayPayments;
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
        return $this->toArray($payment, $order);
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
            'validate'         => $this->abandonedIntervalToDate(
                new \DateTime(
                    $order->getCreatedAtFormatted(\IntlDateFormatter::MEDIUM)
                )
            ),
            'sent'             => current($this->getSent($order->getId())),
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
        unset($options);
        return $this->_crypt->encrypt('!QAWRRR$HU%W34tyh59yh544%',
            json_encode([
                'order_id'      => $order->getId(),
                'recovery_code' => $payment->getRecoveryCode()
            ])
        );
    }

    /**
     * Get PagSeguro payments
     *
     * @param null $page
     * @return \PagSeguro\Parsers\Transaction\Search\Date\Response|string
     * @throws \Exception
     */
    private function getPagSeguroAbandoned($page = null)
    {
        //check if has a page, if doesn't have one then start at the first.
        if (is_null($page)) $page = 1;

        try {
            //check if is the first step, if is just add the response object to local var
            if (is_null($this->_PagSeguroPaymentList)) {
                $this->_PagSeguroPaymentList = $this->requestPagSeguroAbandoned($page);
            } else {

                $response = $this->requestPagSeguroAbandoned($page);
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
     * Send email with abandoned template
     *
     * @param $order
     * @param $recoveryCode
     * @return bool
     * @throws \Exception
     */
    private function sendEmail($order, $recoveryCode)
    {

        //Get system general e-mail addresses
        $sender  = [
            'email' => $this->_scopeConfig->getValue('trans_email/ident_general/email', ScopeInterface::SCOPE_STORE),
            'name'  => $this->_scopeConfig->getValue('trans_email/ident_general/name',ScopeInterface::SCOPE_STORE)
        ];

        // Get customer info
        $receiver  = [
            'email' => $order->getCustomerEmail(),
            'name'  => sprintf('%s %s', $order->getCustomerFirstname(), $order->getCustomerLastname())
        ];

        // Send e-mail
        $transport = $this->_transportBuilder->setTemplateIdentifier('abandoned_template')
            ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => 1])
            ->setTemplateVars([
                'order' => $order,
                'pagseguro_recover_url' => $this->abandonedRecoveryUrl($recoveryCode)
            ])
            ->setFrom($sender)
            ->addTo($receiver)
            ->getTransport();

        try {
            $transport->sendMessage();
            return true;
        } catch (\Exception $exception) {
            throw $exception;
        }

    }

    /**
     * Request all PagSeguroTransaction in this _date interval
     *
     * @param $page
     * @return string
     * @throws \Exception
     */
    protected function requestPagSeguroAbandoned($page)
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

            return \PagSeguro\Services\Transactions\Search\Abandoned::search(
                $this->_library->getPagSeguroCredentials(),
                $options
            );

        } catch (Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Converts a day interval to date.
     *
     * @param \DateTime $date
     * @return string
     */
    private function abandonedIntervalToDate($date)
    {
        $dateInterval = "P".(String)self::VALID_RANGE_DAYS."D";
        $date->add(new \DateInterval($dateInterval));

        return $date->format('d/m/Y H:i:s');
    }

    /**
     * Build a URL for recovery a PagSeguroTransaction
     *
     * @param string $recoveryCode
     * @return URI
     */
    private function abandonedRecoveryUrl($recoveryCode)
    {

        if (strtolower($this->_library->getEnvironment()) == "sandbox") {
            return 'https://sandbox.pagseguro.uol.com.br/checkout/v2/resume.html?r=' . $recoveryCode;
        }
        return 'https://pagseguro.uol.com.br/checkout/v2/resume.html?r=' . $recoveryCode;
    }

    /**
     * Get date interval from days qty.
     *
     * @return array
     */
    protected function getDates()
    {
        $date = new \DateTime ( "20 minutes ago" );
        $date->setTimezone ( new \DateTimeZone ( "America/Sao_Paulo" ) );
        $final = $date->format ( "Y-m-d\TH:i:s" );

        $date->sub ( new \DateInterval ( "P" . ( string ) $this->_days . "D" ) );
        $date->setTime ( 00, 00, 00 );
        $initial = $date->format ( "Y-m-d\TH:i:s" );

        return [
            'initial' => $initial,
            'final'   => $final
        ];
    }

    /**
     * Get quantity of email was sent
     *
     * @param int $orderId
     * @return array
     */
    private function getSent($orderId)
    {
        //Getting connection
        $connection  = $this->_resource->getConnection();
        //Getting full table name
        $tableName = $this->_resource->getTableName('pagseguro_orders');
        //Update sales_order_grid query
        $mapsDeleteQuery = "SELECT sent FROM {$tableName} WHERE order_id={$orderId}";
        return $connection->query($mapsDeleteQuery)->fetch();
    }


    /**
     * Increments a sent for a order in pagseguro_orders table.
     *
     * @param $orderId
     * @param $sent
     */
    private function setSent($orderId, $sent)
    {
        //Getting connection
        $connection  = $this->_resource->getConnection();
        //Getting full table name
        $tableName = $this->_resource->getTableName('pagseguro_orders');
        //Update sales_order_grid query
        $mapsDeleteQuery = "UPDATE {$tableName} SET sent={$sent} WHERE order_id={$orderId}";
        $connection->query($mapsDeleteQuery);
    }
}