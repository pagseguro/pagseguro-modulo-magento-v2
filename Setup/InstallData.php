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

namespace UOL\PagSeguro\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /**
         * Prepare database for install
         */
        $setup->startSetup();

        /**
         * PagSeguro Order Status
         */
        $statuses = [
            'pagseguro_iniciado'  => __('PagSeguro Iniciado'),
            'pagseguro_aguardando_pagamento' => __('PagSeguro Aguardando Pagamento'),
            'pagseguro_cancelada' => __('PagSeguro Cancelada'),
            'pagseguro_chargeback_debitado'  => __('PagSeguro Chargeback Debitado'),
            'pagseguro_devolvida'  => __('PagSeguro Devolvida'),
            'pagseguro_disponivel'  => __('PagSeguro Disponível'),
            'pagseguro_em_analise'  => __('PagSeguro Em Análise'),
            'pagseguro_em_contestacao'  => __('PagSeguro Em Contestação'),
            'pagseguro_em_disputa'  => __('PagSeguro Em Disputa'),
            'pagseguro_paga'  => __('PagSeguro Paga')
        ];

        foreach ($statuses as $code => $info) {
            $status[] = [
                'status' => $code,
                'label' => $info
            ];
            $state[] = [
                'status' => $code,
                'state' => 'new',
                'is_default' => 0,
                'visible_on_front' => '1'
            ];
        }
        $setup->getConnection()
            ->insertArray($setup->getTable('sales_order_status'), ['status', 'label'], $status);

        /**
         * PagSeguro Order State
         */
        $state[0]['is_default'] = 1;
        $setup->getConnection()
            ->insertArray(
                $setup->getTable('sales_order_status_state'),
                ['status', 'state', 'is_default', 'visible_on_front'],
                $state
            );
        unset($data);

        /**
         * PagSeguro Store Reference
         */
        $data[] = [
            'scope' => 'default',
            'scope_id' => 0,
            'path' => 'pagseguro/store/reference',
            'value' => \UOL\PagSeguro\Helper\Data::generateStoreReference()
        ];
        $setup->getConnection()
            ->insertArray($setup->getTable('core_config_data'), ['scope', 'scope_id', 'path', 'value'], $data);

        /**
         * Prepare database after install
         */
        $setup->endSetup();
    }
}
