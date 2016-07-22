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

            'Search' : function()
            {
                jQuery.ajax( {
                    url: 'http://uol-pagseguro-moscou.stage1.server/magento2-package/admin_mzry2d/pagseguro/conciliation/request',
                    data: {form_key: window.FORM_KEY, days: jQuery('#conciliation-days').val()},
                    type: 'POST',
                    showLoader: true,
                }).success(function(response) {

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
                                '<a href="http://uol-pagseguro-moscou.stage1.server/magento2-package/admin_mzry2d/sales/order/view/order_id/'+item.order_id+'/key/'+window.FORM_KEY+'" target="_blank">Ver detalhes</a>'
                            ] );
                            //Adjust column width
                            t.columns.adjust().draw(false);
                        });
                    } else {
                        //Alert
                        Modal.Load('Conciliação', 'Sem resultados para o período solicitado.');
                    }

                });
            },
            'Conciliate' : function()
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
                    url: 'http://uol-pagseguro-moscou.stage1.server/magento2-package/admin_mzry2d/pagseguro/conciliation/conciliate',
                    data: {form_key: window.FORM_KEY, data: data},
                    type: 'POST',
                    showLoader: true,
                }).success(function(response) {

                    if (response.success == true) {
                        //Alert
                        Modal.Load('Conciliação', 'Transações conciliadas com sucesso!');
                    }

                    if (response.success == false) {
                        //Alert
                        Modal.Load('Conciliação', 'Não foi possível executar esta ação. Utilize a conciliação de transações primeiro ou tente novamente mais tarde.');
                    }
                });
            }
        },
    }
}


