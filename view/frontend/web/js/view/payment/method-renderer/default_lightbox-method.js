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
    ],
    function ($, Component, quote, fullScreenLoader, setPaymentInformationAction, placeOrder) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'UOL_PagSeguro/payment/default-lightbox-form'
            },

            context: function() {
                return this;
            },

            getCode: function() {
                return "pagseguro_default_lightbox"
            },

            /**
             * @override
             */
            placeOrder: function () {

                var self = this;
                var paymentData = quote.paymentMethod();
                var messageContainer = this.messageContainer;
                fullScreenLoader.startLoader();
                this.isPlaceOrderActionAllowed(false);
                $.when(setPaymentInformationAction(this.messageContainer, {
                    'method': self.getCode()
                })).done(function () {
                        delete paymentData['title'];
                        $.when(placeOrder(paymentData, messageContainer)).done(function () {
                           if (window.checkoutConfig.payment.pagseguro.isLightbox){
                                $.mage.redirect(window.checkoutConfig.payment.pagseguro.checkout.lightbox);
                            } else {
                                $.mage.redirect(window.checkoutConfig.payment.pagseguro.checkout.standard);
                            }
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
