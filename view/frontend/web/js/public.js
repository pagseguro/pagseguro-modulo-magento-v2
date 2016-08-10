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
                        console.log(response);
                        window.location.href = response.payload.redirect;
                    });
                }
            }
        }
    }
}


