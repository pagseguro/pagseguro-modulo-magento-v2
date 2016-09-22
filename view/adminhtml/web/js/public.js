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

        'Conciliation' : {

            'Search' : function(url)
            {
                jQuery.ajax( {
                    url: url + '/pagseguro/conciliation/request',
                    data: {form_key: window.FORM_KEY, days: jQuery('#conciliation-days').val()},
                    type: 'POST',
                    showLoader: true,
                }).success(function(response) {

                    if (response.success) {

                        var t = jQuery('#pagseguro-datatable').DataTable();

                        //Cleans up the table
                        t.clear().draw();

                        //Check the array for data, if not empty insert data else clear the table.
                        if (response.payload.data.length > 0) {
                            // Create a new table row for all array positions
                            response.payload.data.forEach(function(item){
                                t.row.add( [
                                    "<input type='checkbox' data-target='conciliation' data-block='"+item.details+"'/>",
                                    item.date,
                                    item.magento_id,
                                    item.pagseguro_id,
                                    item.magento_status,
                                    item.pagseguro_status,
                                    '<a href="'+url+'/sales/order/view/order_id/'+item.order_id+'/key/'+window.FORM_KEY+'" target="_blank">Ver detalhes</a>'
                                ] );
                                //Adjust column width
                                t.columns.adjust().draw(false);
                            });
                        } else {
                            //Alert
                            Modal.Load('Conciliação', 'Sem resultados para o período solicitado.');
                        }
                    } else {
                        //Alert
                        Modal.Load('Conciliação', 'Não foi possível executar esta ação. Tente novamente mais tarde.');
                    }
                });

            },
            'Conciliate' : function(url)
            {
                var t    = jQuery('#pagseguro-datatable').DataTable();
                var rows = jQuery('#pagseguro-datatable').find('[data-target=conciliation]:checked');

                // Get all serialized data from rows
                var data = [];
                jQuery.each(rows, function(index, value) {
                    // Find row index
                    var tr = jQuery(value).parent().parent();
                    // push row data to an array of rows
                    data[index] = jQuery(value).attr('data-block');
                    // remove this row
                    t.row( tr ).remove().draw();
                });

                jQuery.ajax( {
                    url: url + '/pagseguro/conciliation/conciliate',
                    data: {form_key: window.FORM_KEY, data: data},
                    type: 'POST',
                    showLoader: true,
                }).success(function(response) {

                    if (response.success) {

                        if (response.success == true) {
                            //Alert
                            Modal.Load('Conciliação', 'Transações conciliadas com sucesso!');
                        }

                        if (response.success == false) {
                            //Alert
                            Modal.Load('Conciliação', 'Não foi possível executar esta ação. Utilize a conciliação de transações primeiro ou tente novamente mais tarde.');
                        }
                    } else {
                        //Alert
                        Modal.Load('Conciliação', 'Não foi possível executar esta ação. Tente novamente mais tarde.');
                    }
                });
            }
        },

        /**
         * Abandoned method's
         */
        'Abandoned' : {

            'Search': function (url) {

                jQuery.ajax({
                    url: url + '/pagseguro/abandoned/request',
                    data: {form_key: window.FORM_KEY, days: jQuery('#abandoned-days').val()},
                    type: 'POST',
                    showLoader: true,
                }).success(function (response) {

                    if (response.success) {

                        var t = jQuery('#pagseguro-datatable').DataTable();

                        //Cleans up the table
                        t.clear().draw();

                        //Check the array for data, if not empty insert data else clear the table.
                        if (response.payload.data.length > 0) {
                            // Create a new table row for all array positions
                            response.payload.data.forEach(function (item) {
                                t.row.add([
                                    "<input type='checkbox' data-target='abandoned' data-block='" + item.details + "'/>",
                                    item.date,
                                    item.magento_id,
                                    item.validate,
                                    item.sent,
                                    '<a href="' + url + '/sales/order/view/order_id/' + item.order_id + '/key/' + window.FORM_KEY + '" target="_blank">Ver detalhes</a>'
                                ]);
                                //Adjust column width
                                t.columns.adjust().draw(false);
                            });
                        } else {
                            //Alert
                            Modal.Load('Abandonadas', 'Sem resultados para o período solicitado.');
                        }
                    } else {
                        //Alert
                        Modal.Load('Abandonadas', 'Não foi possível executar esta ação. Tente novamente mais tarde.');
                    }

                });
            },
            'Transport' : function(url)
            {
                var t    = jQuery('#pagseguro-datatable').DataTable();
                var rows = jQuery('#pagseguro-datatable').find('[data-target=abandoned]:checked');

                // Get all serialized data from rows
                var data = [];
                jQuery.each(rows, function(index, value) {
                    // Find row index
                    var tr = jQuery(value).parent().parent();
                    // push row data to an array of rows
                    data[index] = jQuery(value).attr('data-block');
                });

                jQuery.ajax( {
                    url: url + '/pagseguro/abandoned/transport',
                    data: {form_key: window.FORM_KEY, data: data},
                    type: 'POST',
                    showLoader: true,
                }).success(function(response) {

                    if (response.success) {

                        if (response.success == true) {

                            //Cleans up the table
                            t.clear().draw();

                            WS.Ajax.Abandoned.Search(url);

                            Modal.Load('Abandonadas', 'Código de recuperação enviado com sucesso!');

                        }
                        if (response.success == false) {
                            //Alert
                            Modal.Load('Abandonadas', 'Não foi possível executar esta ação. Utilize a recuperação de transações primeiro ou tente novamente mais tarde.');
                        }
                    } else {
                        //Alert
                        Modal.Load('Abandonadas', 'Não foi possível executar esta ação. Tente novamente mais tarde.');
                    }
                });
            }
        },

        /**
         * Cancellation method's
         */
        'Cancellation' : {
            'Search' : function(url)
            {
                jQuery.ajax( {
                    url: url + '/pagseguro/cancellation/request',
                    data: {form_key: window.FORM_KEY, days: jQuery('#cancellation-days').val()},
                    type: 'POST',
                    showLoader: true,
                }).success(function(response) {

                    if (response.success) {

                        var t = jQuery('#pagseguro-datatable').DataTable();

                        //Cleans up the table
                        t.clear().draw();

                        //Check the array for data, if not empty insert data else clear the table.
                        if (response.payload.data.length > 0) {
                            var i = 0;
                            // Create a new table row for all array positions
                            response.payload.data.forEach(function(item){
                                t.row.add( [
                                    item.date,
                                    item.magento_id,
                                    item.pagseguro_id,
                                    item.magento_status,
                                    '<a class="cancellation-cancel" data-target="cancellation_'+ i +'" data-block="'+item.details+'">Cancelar</a>'
                                ] );
                                //Adjust column width
                                t.columns.adjust().draw(false);
                                i++;
                            });
                        } else {
                            //Alert
                            Modal.Load('Cancelamento', 'Sem resultados para o período solicitado.');
                        }
                    } else {
                        //Alert
                        Modal.Load('Cancelamento', 'Não foi possível executar esta ação. Tente novamente mais tarde.');
                    }
                });

            },
            'Cancel' : function(url, data, row)
            {
                var t = jQuery('#pagseguro-datatable').DataTable();

                jQuery.ajax( {
                    url: url + '/pagseguro/cancellation/cancel',
                    data: {form_key: window.FORM_KEY, data: data},
                    type: 'POST',
                    showLoader: true,
                }).success(function(response) {

                    if (response.success) {

                        t.row( row ).remove().draw();

                        Modal.Load('Cancelamento', 'Transações cancelada com sucesso!');

                    } else {
                        if (response.payload.error == 'Need to conciliate') {
                            //Alert
                            Modal.Load('Cancelamento', 'Não foi possível executar esta ação. Utilize a conciliação de transações primeiro ou tente novamente mais tarde.');
                        } else {
                            //Alert
                            Modal.Load('Cancelamento', 'Não foi possível executar esta ação. Tente novamente mais tarde.');
                        }
                    }
                });
            }
        },

        /**
         * Refund method's
         */
        'Refund' : {
            'Search' : function(url)
            {
                jQuery.ajax( {
                    url: url + '/pagseguro/refund/request',
                    data: {form_key: window.FORM_KEY, days: jQuery('#refund-days').val()},
                    type: 'POST',
                    showLoader: true,
                }).success(function(response) {

                    if (response.success) {

                        var t = jQuery('#pagseguro-datatable').DataTable();

                        //Cleans up the table
                        t.clear().draw();

                        //Check the array for data, if not empty insert data else clear the table.
                        if (response.payload.data.length > 0) {
                            var i = 0;
                            // Create a new table row for all array positions
                            response.payload.data.forEach(function(item){
                                t.row.add( [
                                    item.date,
                                    item.magento_id,
                                    item.pagseguro_id,
                                    item.magento_status,
                                    '<a class="refund" data-target="refund_'+ i +'" data-block="'+item.details+'">Estornar</a>'
                                ] );
                                //Adjust column width
                                t.columns.adjust().draw(false);
                                i++;
                            });
                        } else {
                            //Alert
                            Modal.Load('Estorno', 'Sem resultados para o período solicitado.');
                        }
                    } else {
                        //Alert
                        Modal.Load('Estorno', 'Não foi possível executar esta ação. Tente novamente mais tarde.');
                    }
                });

            },
            'Refund' : function(url, data, row)
            {
                var t = jQuery('#pagseguro-datatable').DataTable();

                jQuery.ajax( {
                    url: url + '/pagseguro/refund/refund',
                    data: {form_key: window.FORM_KEY, data: data},
                    type: 'POST',
                    showLoader: true,
                }).success(function(response) {

                    if (response.success) {

                        t.row( row ).remove().draw();

                        Modal.Load('Estorno', 'Transações estornada com sucesso!');

                    } else {
                        if (response.payload.error == 'Need to conciliate') {
                            //Alert
                            Modal.Load('Estorno', 'Não foi possível executar esta ação. Utilize a conciliação de transações primeiro ou tente novamente mais tarde.');
                        } else {
                            //Alert
                            Modal.Load('Estorno', 'Não foi possível executar esta ação. Tente novamente mais tarde.');
                        }
                    }
                });
            }
        },
    }
}



