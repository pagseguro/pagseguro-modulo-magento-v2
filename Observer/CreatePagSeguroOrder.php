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

namespace UOL\PagSeguro\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\ResourceModel\Grid;
use UOL\PagSeguro\Model\Orders;

/**
 * Observer for saving the order and his environment when a PagSeguro order is done
 * in the pagseguro_orders and in the sales_order_grid
 *
 */
class CreatePagSeguroOrder implements ObserverInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \UOL\PagSeguro\Model\System\Config\Environment
     */
    protected $_environment;

    /**
     * Automatic generated factory class
     * @var \UOL\PagSeguro\Model\OrdersFactory;
     */
    protected $_ordersFactory;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\Context
     */
    protected $_context;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Grid;
     */
    protected $_grid;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param OrdersFactory $ordersFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \UOL\PagSeguro\Model\OrdersFactory $ordersFactory,
        \UOL\PagSeguro\Model\System\Config\Environment $environmentConfig
    ) {
        $this->_objectManager = $objectManager;
        $this->_scopeConfig = $scopeConfigInterface;
        $this->_environment = $environmentConfig;
        $this->_ordersFactory = $ordersFactory;
        // Unavaliable for DI
        $this->_grid = new Grid($context, 'pagseguro_orders', 'sales_order_grid', 'order_id');
    }
    
    /**
     * checkout_submit_all_after event handler to store pagseguro order info
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        //verify pagseguro transaction
        if ($order->getStatus() == 'pagseguro_iniciado') {
            $orderId = $order->getId();
            $environment = $this->_scopeConfig->getValue('payment/pagseguro/environment');
            
            //save order in pagseguro_orders table
            $this->saveOrderAndEnvironment($orderId, $environment);

            //$this->getEnvironmentName($environment);
            $this->updateSalesOrderGridEnvironment($orderId, $environment);
        }

        return $this;
    }
    
    /**
     * Create the order in the pagseguro_orders table, saving the order id and 
     * the environment
     * 
     * @param string $orderId
     * @param string $environment
     * @return void
     */
    private function saveOrderAndEnvironment($orderId, $environment)
    {
        $this->_ordersFactory->create()
            ->setData([
                    'order_id' => $orderId,
                    'environment' => $environment
            ])->save();
    }
    
    /**
     * Get the environment translated name
     * @return string
     */
    private function getEnvironmentName($environment)
    {
        $environmentName = $this->_environment->toOptionArray();
        return $environmentName[$environment]->getText();
    }
    
    /**
     * Update environment in sales_order_grid table
     *
     * @param int $orderId
     * @param string $environment
     */
    private function updateSalesOrderGridEnvironment($orderId, $environment)
    {
        $environmentName = $this->getEnvironmentName($environment);

        $resource = $this->_objectManager->create('Magento\Framework\App\ResourceConnection');

        //Getting connection
        $connection  = $resource->getConnection();
        //Getting full table name
        $tableName = $resource->getTableName('sales_order_grid');
        //Update sales_order_grid query
        $mapsDeleteQuery = "UPDATE $tableName SET environment='$environmentName' WHERE entity_id=$orderId";
        $connection->query($mapsDeleteQuery);
    }
}