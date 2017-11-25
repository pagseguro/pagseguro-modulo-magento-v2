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
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/action/place-order',
        'UOL_PagSeguro/js/model/boleto-validator',
        window.checkoutConfig.library.directPaymentJs
    ],
    function ($, Component, quote, fullScreenLoader, setPaymentInformationAction, placeOrder, boletoValidator) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'UOL_PagSeguro/payment/online-debit-form',
                brazilFlagPath: window.checkoutConfig.brazilFlagPath
            },

            initObservable: function () {

                this._super()
                    .observe([
                        'onlineDebitDocument',
                        'checkedBank'
                    ]);
                return this;
            },

            context: function() {
                return this;
            },

            getCode: function() {
                return "pagseguro_online_debit"
            },

            /**
             * @override
             */
            placeOrder: function () {
                var self = this;
                var paymentData = quote.paymentMethod();
                var messageContainer = this.messageContainer;
                /* @TODO verify if session id is already set */
                PagSeguroDirectPayment.setSessionId(window.checkoutConfig.library.session);
                fullScreenLoader.startLoader();
                this.isPlaceOrderActionAllowed(false);
                if (self.onlineDebitDocument() == '') {
                  fullScreenLoader.stopLoader();
                  boletoValidator.documentValidator();
                  this.isPlaceOrderActionAllowed(true);
                  return;
                }
                $.when(setPaymentInformationAction(this.messageContainer, {
                    'method': self.getCode(),
                    'additional_data': {
                        'online_debit_document': self.onlineDebitDocument(),
                        'online_debit_hash': PagSeguroDirectPayment.getSenderHash(),
                        'online_debit_bank' : self.checkedBank()
                    }
                })).done(function () {
                        delete paymentData['title'];
                        $.when(placeOrder(paymentData, messageContainer)).done(function () {
                          $.mage.redirect(window.checkoutConfig.pagseguro_boleto);
                        });
                }).fail(function () {
                    self.isPlaceOrderActionAllowed(true);
                }).always(function(){
                    fullScreenLoader.stopLoader();
                });
            }
        });
    }
);
