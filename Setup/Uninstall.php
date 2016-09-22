<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace UOL\PagSeguro\Setup;

use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

/**
 * Used to uninstall all of the data of the UOL_PagSeguro module from the Magento
 */
class Uninstall implements UninstallInterface
{
    /**
     * Called when run the command: php bin/magento module:uninstall UOL_PagSeguro
     * to uninstall UOL_PagSeguro data from the DB
     * @param Magento\Framework\Setup\SchemaSetupInterface $setup
     * @param Magento\Framework\Setup\ModuleContextInterface $context
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $statuses = [
            'pagseguro_iniciado',
            'pagseguro_aguardando_pagamento',
            'pagseguro_cancelada',
            'pagseguro_chargeback_debitado',
            'pagseguro_devolvida',
            'pagseguro_disponivel',
            'pagseguro_em_analise',
            'pagseguro_em_contestacao',
            'pagseguro_em_disputa',
            'pagseguro_paga'
        ];
        
        $paths = [
            'pagseguro/store/reference',
            'payment/pagseguro/active',
            'payment/pagseguro/title',
            'payment/pagseguro/email',
            'payment/pagseguro/token',
            'payment/pagseguro/redirect',
            'payment/pagseguro/notification',
            'payment/pagseguro/charset',
            'payment/pagseguro/log',
            'payment/pagseguro/log_file',
            'payment/pagseguro/checkout',
            'payment/pagseguro/environment',
            'payment/pagseguro/abandoned_active'
        ];

        $setup->startSetup();
        $this->dropPagSeguroOrdersTable($setup);
        $this->dropColumnsFromSalesOrderGrid($setup);
        $this->removeDataFromSalesOrderStatus($setup, $statuses);
        $this->removeDataFromSalesOrderStatusState($setup, $statuses);
        $this->removeDataFromCoreConfigData($setup, $paths);
        $setup->endSetup();
    }

    private function dropPagSeguroOrdersTable($setup) 
    {
        $setup->getConnection()->dropTable($setup->getTable('pagseguro_orders'));
    }
    
    private function dropColumnsFromSalesOrderGrid($setup)
    {
        $setup->getConnection()->dropColumn(
            $setup->getTable('sales_order_grid'),
            'transaction_code'
        );
        
        $setup->getConnection()->dropColumn(
            $setup->getTable('sales_order_grid'),
            'environment'
        );
    }
    //sales_order_status
    private function removeDataFromSalesOrderStatus($setup, $statuses)
    {
        foreach ($statuses as $status) {
            $setup->getConnection()
                ->delete($setup->getTable('sales_order_status'), "status='$status'");
        }
    }
    //sales_order_status_state
    private function removeDataFromSalesOrderStatusState($setup, $statuses)
    {
        foreach ($statuses as $status) {
            $setup->getConnection()
                ->delete($setup->getTable('sales_order_status_state'), "status='$status'");
        }
    }
    //core_config_data
    private function removeDataFromCoreConfigData($setup, $paths)
    {
        foreach ($paths as $path) {
            $setup->getConnection()
                ->delete($setup->getTable('core_config_data'), "path='$path'");
        }
    }
    
    private function removeModuleSetup($setup)
    {
        $setup->getConnection()
            ->delete($setup->getTable('setup_module'), "module='UOL_PagSeguro'");
    }
}