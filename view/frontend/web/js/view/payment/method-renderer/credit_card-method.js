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
        'UOL_PagSeguro/js/model/direct-payment-validator',
        'UOL_PagSeguro/js/model/credit-card',
        window.checkoutConfig.library.directPaymentJs
    ],
    function ($, Component, quote, fullScreenLoader, setPaymentInformationAction, placeOrder, directPaymentValidator, creditCard) {
        'use strict';
        //set pagseguro session
        PagSeguroDirectPayment.setSessionId(window.checkoutConfig.library.session);

        //console.log(quote.totals);
        return Component.extend({
            defaults: {
                template: 'UOL_PagSeguro/payment/credit-card-form',
                brazilFlagPath: window.checkoutConfig.brazilFlagPath
//                totals: parseFloat(
//                  _.findLast(q.getTotals()()['total_segments'], 'value').value
//                )
            },

            initObservable: function () {

                this._super()
                    .observe([
                        'creditCardDocument'
                    ]);
                return this;
            },

            grandTotal: function() {
              var totals = quote.getTotals()();
              var x = (totals ? totals : quote)['grand_total'];
              //var y = _.findLast(quote.getTotals()()['total_segments'], 'value').value;
              console.log(x);
              //console.log(y);
              return parseFloat(x);
            },

            getPagSeguroCcMonthsValues: function() {
              var months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
              return _.map(months, function (value, key) {
                return {
                    'value': key,
                    'month': value
                };
            });
          },

          getPagSeguroCcYearsValues: function() {
              var thisYear = (new Date()).getFullYear();
              var maxYear = thisYear + 20;
              var years = [];
              var i = thisYear;
              for (i = thisYear; i < maxYear; i++) {
                years.push(i);
              }

              return _.map(years, function (value, key) {
                return {
                    'value': value,
                    'year': value
                };
            });
          },

            context: function() {
                return this;
            },

            getCode: function() {
                return "pagseguro_credit_card"
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
                            if (window.checkoutConfig.payment.pagseguro.isDirect) {
                                $.mage.redirect(window.checkoutConfig.payment.pagseguro.checkout.direct);
                            } else if (window.checkoutConfig.payment.pagseguro.isLightbox){
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
