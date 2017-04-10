/**
 *
 * Modals
 *
 */
var Modal = {
    'Load' : function(title, content){
        require([
            'Magento_Ui/js/modal/alert'
        ], function(alert) {
            alert({
                title: title,
                content: content,
                clickableOverlay: true,

            });
        });
    }
}

/**
 *
 * Ajax call's
 *
 */
var WS = {

    'Ajax' : {

        /**
         * Direct Payment method's
         */
        'Direct' : {

            'Boleto': {

                'Payment' : function (url, id, hash, document) {

                    jQuery.ajax({
                        url: url + 'pagseguro/direct/boleto',
                        data: {form_key: window.FORM_KEY, order_id: id, sender_hash : hash, sender_document : document},
                        type: 'POST',
                        showLoader: true,
                    }).success(function (response) {
                        window.location.href = response.payload.redirect;
                    });
                }
            },
            'OnlineDebit': {

                'Payment' : function (url, id, hash, document, bank) {

                    jQuery.ajax({
                        url: url + 'pagseguro/direct/debit',
                        data: {
                            form_key: window.FORM_KEY,
                            order_id: id,
                            sender_hash : hash,
                            sender_document : document,
                            bank_name:bank
                        },
                        type: 'POST',
                        showLoader: true,
                    }).success(function (response) {
                        window.location.href = response.payload.redirect;
                    });
                }
            },
            'CreditCard': {
                'Installments' : function (url, id, brand, isInternational) {
                    jQuery.ajax({
                        url: url + 'pagseguro/direct/installments',
                        data: {
                            form_key: window.FORM_KEY,
                            order_id: id,
                            credit_card_brand : brand,
                            credit_card_international : isInternational
                        },
                        type: 'POST',
                        showLoader: true,
                    }).success(function (response) {
                        if (response.success) {
                            //remove if already exists installment options
                          jQuery('#card_installments option').each(function(){
                              if (!jQuery(this).val() === false) {
                                 jQuery(this).remove();
                              }
                          });

                          //add installments options
                          jQuery.each(response.payload.data.installments, function (i, item) {
                              jQuery('#card_installments').append(jQuery('<option>', { 
                                  value: item.totalAmount,
                                  text : item.text,
                                  'data-amount': item.amount,
                                  'data-quantity': item.quantity
                              }));
                          });

                          //add card international status
                          jQuery('#card-international').attr('data-target', response.payload.data.cardInternational);
                          
                          //add card brand
                          jQuery('#card-brand').attr('data-target', response.payload.data.cardBrand);

                          //show installments option and total amount of it
                          jQuery('#card_installments').parents('.form-group').show();
                          jQuery('.form-group.credit-total').show()
                        } else {
                          window.location.href = response.payload.redirect;
                        }
                    });
                },

               'Payment' : function (url, id, hash, document, token, international, quantity, amount, holderName, holderBirthdate) {

                   jQuery.ajax({
                       url: url + 'pagseguro/direct/creditcard',
                       data: {
                           form_key: window.FORM_KEY,
                           order_id: id,
                           sender_hash : hash,
                           sender_document : document,
                           card_token: token,
                           card_international: international,
                           installment_quantity: quantity,
                           installment_amount: amount,
                           holder_name: holderName,
                           holder_birthdate: holderBirthdate
                       },
                       type: 'POST',
                       showLoader: true,
                   }).success(function (response) {
                       window.location.href = response.payload.redirect;
                   });
               }
            }
        }
    }
}
