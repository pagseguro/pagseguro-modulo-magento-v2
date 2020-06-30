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
/*
 * browser:true
 * global define
 */
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'pagseguro_default_lightbox',
                component: 'UOL_PagSeguro/js/view/payment/method-renderer/default_lightbox-method'
            },
            {
                type: 'pagseguro_credit_card',
                component: 'UOL_PagSeguro/js/view/payment/method-renderer/credit_card-method'
            },
            {
                type: 'pagseguro_boleto',
                component: 'UOL_PagSeguro/js/view/payment/method-renderer/boleto-method'
            },
            {
                type: 'pagseguro_online_debit',
                component: 'UOL_PagSeguro/js/view/payment/method-renderer/online_debit-method'
            }
        );

        return Component.extend({});
    }
);
