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
                                    '<a class="refund" data-target="refund_'+ i +'" data-block="'+item.details+'" data-id="'+item.magento_id+'" style="cursor:pointer;">Estorno total</a><br/>'+
                                    '<a class="partial-refund" data-target="refund_'+ i +'" data-block="'+item.details+'" data-value="'+item.value+'" data-id="'+item.magento_id+'" style="cursor:pointer;">Estorno parcial</a>', 
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
            'Refund' : function(url, data, row, value = null)
            {
                var t = jQuery('#pagseguro-datatable').DataTable();
                jQuery.ajax( {
                    url: url + '/pagseguro/refund/refund',
                    data: {form_key: window.FORM_KEY, data: data, value: value},
                    type: 'POST',
                    showLoader: true,
                }).success(function(response) {
                    if (response.success) {
                        t.row( row ).remove().draw();
                        Modal.Load('Estorno', 'Transações estornada com sucesso!');
                    } else {
                        if (response.payload.error == 'Need to conciliate') {
                            Modal.Load('Estorno', 'Não foi possível executar esta ação. Utilize a conciliação de transações primeiro ou tente novamente mais tarde.');
                        } else if(response.payload.error == '14002' || response.payload.error == '14013') {
                            Modal.Load('Estorno', 'Valor do estorno está em um formato inválido!');
                        } else if (response.payload.error == '14003') {
                            Modal.Load('Estorno', 'Valor do estorno inválido! O valor não pode ser negativo.');
                        } else if (response.payload.error == '14004') {
                            Modal.Load('Estorno', 'Valor do estorno é menor do que o permitido.');
                        } else if (response.payload.error == '14005') {
                            Modal.Load('Estorno', 'Valor do estorno é maior do que o permitido.');
                        } else if (response.payload.error == '14006') {
                            Modal.Load('Estorno', 'Saldo insuficiente para estornar a transação.');
                        } else if (response.payload.error == '14007') {
                            Modal.Load('Estorno', 'Status da transação é inválido para ser estornada.');
                        } else if (response.payload.error == '14008') {
                            Modal.Load('Estorno', 'Transação não encontrada.');
                        } else if (response.payload.error == '14009') {
                            Modal.Load('Estorno', "Sua conta PagSeguro não tem permissão para realizar esta ação. Em caso de dúvidas acesse <a href='http://forum.pagseguro.uol.com.br' target='_blank'>http://forum.pagseguro.uol.com.br</a>");
                        }else {
                            Modal.Load('Estorno', 'Não foi possível executar esta ação. Tente novamente mais tarde.');
                        }
                    }
                });
            }
        },

        /**
         * Transactions method's
         */
        'Transactions' : {
            'Search' : function(url)
            {
                jQuery.ajax( {
                    url: url + '/pagseguro/transactions/request',
                    data: {
                        form_key: window.FORM_KEY,
                        date_begin: jQuery('#date_begin').val(),
                        date_end: jQuery('#date_end').val(),
                        id_magento: jQuery('#id_magento').val(),
                        id_pagseguro: jQuery('#id_pagseguro').val(),
                        status: jQuery('#status').val()
                    },
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
                                    '<a href="' + url + '/sales/order/view/order_id/' + item.order_id + '/key/' + window.FORM_KEY + '" target="_blank">' + item.magento_id + '</a>',
                                    item.pagseguro_id,
                                    item.environment,
                                    item.magento_status,
                                    '<a class="link" data-transaction="'+ item.pagseguro_id +'" data-order="'+ item.order_id +'">Ver detalhes do pagamento</a>'
                                ] );

                                //Adjust column width
                                t.columns.adjust().draw(false);
                                i++;
                            });
                            jQuery('.loading-mask').css('display', 'none');
                        } else {
                            //Alert
                            Modal.Load('Listar transações', 'Nenhum resultado para o filtro informado!.');
                        }
                    } else {
                        //Alert
                        Modal.Load('Listar transações', 'Não foi possível executar esta ação. Tente novamente mais tarde.');
                    }
                });

            },

            'Details' : function (url, transaction_code, order) {
                jQuery.ajax({
                    url: url + '/pagseguro/transactions/transaction',
                    type: "POST",
                    data: {
                        form_key: window.FORM_KEY,
                        transaction: transaction_code
                    },
                    showLoader: true
                }).success(function(result) {
                    if (result.success) {
                        result = result.payload.data;

                            jQuery('#transaction-group').append('<div></div>');
                            jQuery('#transaction-group').append('<div></div>');
                            jQuery('#payment-group').append('<div></div>');
                            jQuery('#payment-group').append('<div></div>');
                            var listTransactionLine1 = jQuery('#transaction-group div:eq(0)');
                            var listTransactionLine2 = jQuery('#transaction-group div:eq(1)');
                            var listPaymentLine1 = jQuery('#payment-group div:eq(0)');
                            var listPaymentLine2 = jQuery('#payment-group div:eq(1)');

                            if(result.date != undefined && result.date != false){
                                listTransactionLine1.append('<dl class=""><dt>Data e hora: </dt><dd>' + result.date + '</dd></dl>');
                            }

                            if(result.type != undefined && result.type != false){
                                listTransactionLine1.append('<dl class=""><dt>Tipo: </dt><dd>' + result.type + '</dd></dl>');
                            }

                            if(result.status != undefined && result.status != false){
                                listTransactionLine1.append('<dl><dt>Status: </dt><dd>' + result.status + '</dd></dl>');
                            }

                            if(result.code != undefined && result.code != false){
                                listTransactionLine1.append('<dl><dt>Código da transação: </dt><dd>' + result.code + '</dd></dl>');
                            }

                            if(result.reference != undefined && result.reference != false){
                                listTransactionLine1.append('<dl><dt>Código de referência: </dt><dd>' + result.reference + '</dd></dl>');
                            }

                            if(result.lastEventDate != undefined && result.lastEventDate != false){
                                listTransactionLine2.append('<dl><dt>Último evento: </dt><dd>' + result.lastEventDate + '</dd></dl>');
                            }

                            if(result.cancelationSource != undefined && result.cancelationSource != false){
                                listTransactionLine2.append('<dl><dt>Origem do cancelamento: </dt><dd>' + result.cancelationSource + '</dd></dl>');
                            }

                            if(result.itemCount != undefined && result.itemCount != false){
                                listTransactionLine2.append('<dl><dt>Total de itens: </dt><dd>' + result.itemCount + '</dd></dl>');
                            }

                            jQuery('#transaction-group').append('<span id="btn-hidden-itens" class="link ">Exibir todos os itens &#9660</span>');

                            jQuery('#transaction-group').append('<div id="itens" class="hidden-groups table"></div>');

                            if(result.paymentMethod.titleType != undefined && result.paymentMethod.titleType != false){
                                listPaymentLine1.append('<dl><dt>Tipo de pagamento: </dt><dd>' + result.paymentMethod.titleType + '</dd></dl>');
                            }

                            if(result.paymentMethod.titleCode != undefined && result.paymentMethod.titleCode != false){
                                listPaymentLine1.append('<dl><dt>Meio de pagamento: </dt><dd>' + result.paymentMethod.titleCode +'</dd></dl>');
                            }

                            if(result.paymentLink != false){
                                listPaymentLine1.append('<dl><dt>Link para pagamento: </dt><dd>' + '<a href=' + result.paymentLink + '>' + 'Clique aqui para acessar' +'</a>' + '</dd></dl>');
                            }

                            if(result.installmentCount != undefined && result.installmentCount != false){
                                listPaymentLine1.append('<dl><dt>Nº de parcelas: </dt><dd>' + result.installmentCount + '</dd></dl>');
                            }

                            if(result.extraAmount != undefined && result.extraAmount != false){
                                listPaymentLine2.append('<dl class=""><dt>Valor extra: </dt><dd>R$ ' + formatReal(result.extraAmount) + '</dd></dl>');
                            }

                            if(result.discountAmount != undefined && result.discountAmount != false){
                                listPaymentLine2.append('<dl class=""><dt>Desconto: </dt><dd> R$' + formatReal(result.discountAmount) + '</dd></dl>');
                            }

                            if(result.grossAmount != undefined && result.grossAmount != false){
                                listPaymentLine2.append('<dl class=""><dt>Valor bruto: </dt><dd>R$ ' + formatReal(result.grossAmount) +'</dd></dl>');
                            }

                            if(result.netAmount != undefined && result.netAmount != false){
                                listPaymentLine2.append('<dl class=""><dt>Valor líquido: </dt><dd>R$ ' + formatReal(result.netAmount) + '</dd></dl>');
                            }

                            if(result.promoCode != undefined && result.promoCode != false){
                                listPaymentLine2.append('<dl><dt>Código de promoção: </dt><dd>' + result.promoCode + '</dd></dl>');
                            }

                            if(result.escrowEndDate != undefined && result.escrowEndDate != false){
                                listPaymentLine1.append('<dl><dt>Data de crédito: </dt><dd>' + result.escrowEndDate +'</dd></dl>');
                            }

                            jQuery('#payment-group').append('<span id="btn-hidden-data" class="link">Exibir todos os dados de custos cobrados &#9660</span>');

                            jQuery('#payment-group').append('<div id="cost-data" class="hidden-groups table"></div>');

                            var listItens = jQuery('#itens');
                            listItens.append('<h4>Itens do carrinho</h4>');
                            listItens.append('<div class="group-title"></div>');
                            listItens.append('<div class="group-table-itens"></div>');
                            var listItensTitle = jQuery('.group-title');

                            if(result.itemCount > 0){
                                listItensTitle.append('<div class="itens-cell">ID</div>');
                                listItensTitle.append('<div class="description-cell">Produto</div>');
                                listItensTitle.append('<div class="itens-cell">Quantidade</div>');
                                listItensTitle.append('<div class="itens-cell">Valor</div>');
                                listItensTitle.append('<div class="itens-cell">Total</div>');
                                for(var i = 0; i< result.itemCount; i++){
                                    var total = (result.items[i].quantity * result.items[i].amount).toFixed(2);
                                    listItensBody = jQuery('.group-table-itens');
                                    listItensBody.append('<div id="item' + i + '" class="itens-line"></div>');
                                    listItensBody = jQuery('#item' + i);
                                    listItensBody.append('<div class="itens-cell">' + result.items[i].id +'</div>');
                                    listItensBody.append('<div class="description-cell">' + result.items[i].description +'</div>');
                                    listItensBody.append('<div class="itens-cell">' + result.items[i].quantity + '</div>');
                                    listItensBody.append('<div class="itens-cell">R$ ' + formatReal(result.items[i].amount) + '</div>');
                                    listItensBody.append('<div class="itens-cell"> R$ ' + formatReal(total) +'</div>');
                                }
                            }

                            jQuery('#cost-data').append('<h4>Dados dos custos cobrados</h4>');

                            jQuery('#cost-data').append('<div class="rate"></div>');

                            var listData = jQuery('#cost-data div');

                            if(result.creditorFees.installmentFeeAmount != undefined && result.creditorFees.installmentFeeAmount != false){
                                listData.append('<dl><dt>Taxa de parcelamento: </dt><dd> R$' + formatReal(result.creditorFees.installmentFeeAmount) + '</dd></dl>');
                            }

                            if(result.creditorFees.operationalFeeAmount != undefined && result.creditorFees.operationalFeeAmount != false){
                                listData.append('<dl><dt>Taxa de operação: </dt><dd> R$'+ formatReal(result.creditorFees.operationalFeeAmount) +'</dd></dl>');
                            }

                            if(result.creditorFees.intermediationRateAmount != undefined && result.creditorFees.intermediationRateAmount != false){
                                listData.append('<dl><dt>Tarifa de intermediação: </dt><dd>R$ ' + formatReal(result.creditorFees.intermediationRateAmount) + '</dd></dl>');
                            }

                            if(result.creditorFees.intermediationFeeAmount != undefined && result.creditorFees.intermediationFeeAmount != false){
                                listData.append('<dl><dt>Taxa de intermediação: </dt><dd>R$ ' + formatReal(result.creditorFees.intermediationFeeAmount) + '</dd></dl>');
                            }

                            if(result.creditorFees.comissionFeeAmount != undefined && result.creditorFees.comissionFeeAmount != false){
                                listData.append('<dl><dt>Taxa de comissão: </dt><dd>R$ ' + formatReal(result.creditorFees.comissionFeeAmount) + '</dd></dl>');
                            }

                            jQuery('.modals-overlay').css('display', 'block');
                            jQuery('#modal-details').addClass('_show');
                        
                    }else{
                        if(result.payload.error == "need to conciliate"){
                            Modal.Load('Atenção', 'É necessário utilizar a conciliação de transações primeiro.');
                        }
                    }
                });
            }
        }
    }
}

/**
 *
 * Date
 *
 */

function dateMask (date, fieldName) {
    var mydate = '';
    var field = document.getElementById(fieldName);
    mydate = mydate + date;
    if (mydate.length == 2 && event.keyCode != 8){
        mydate = mydate + '/';
        field.value = mydate;
    } 
    if (mydate.length == 5 && event.keyCode != 8){
        mydate = mydate + '/';
        field.value = mydate;
    } 
    if (mydate.length == 10){
        dateVerify(field); 
    }
    
    if (field.value == "") {
        field.classList.remove('field-error');
    }
} 
        
function dateVerify (fieldName) { 

    day = (fieldName.value.substring(0,2)); 
    month = (fieldName.value.substring(3,5)); 
    year = (fieldName.value.substring(6,10)); 

    situacao = "";

    if ( isNaN(day) || ((day < 01)||(day < 01 || day > 30) && (  month == 04 || month == 06 || month == 09 || month == 11 ) || day > 31)) {
        situacao = "false";
    }

    if ( isNaN(month) || month < 01 || month > 12 ) {
        situacao = "false";
    }

    if ( isNaN(year) || month == 2 && ( day < 01 || day > 29 || ( day > 28 && (parseInt(year / 4) != year / 4)))) {
        situacao = "false";
    }

    if (situacao == "false") {
       fieldName.classList.add('field-error');
    }else{
       fieldName.classList.remove('field-error');
    }
    return;
}

function dateVerifyOnLosesFocus(fieldName){
    var mydate = '';
    mydate = mydate + fieldName.value;

    if(mydate.length > 0 && mydate.length < 10){
        fieldName.classList.add('field-error');
    }else if(mydate.length == 0) {
        fieldName.classList.remove('field-error');
    } else {
        dateVerify(fieldName);
    }
}

function validateSearchByDate() {
    var fieldDateFromValue = document.getElementById('date_begin').value;
    var fieldDateToValue = document.getElementById('date_end').value;

    if ((fieldDateFromValue.length > 0 && fieldDateToValue.length == 0) || (fieldDateFromValue.length == 0 && fieldDateToValue.length > 0)) {
        Modal.Load('Erro ao filtrar!', 'Data de início e fim devem ser informadas!');
        return false;
    }

    dayFrom = (fieldDateFromValue.substring(0,2));
    monthFrom = (fieldDateFromValue.substring(3,5));
    yearFrom = (fieldDateFromValue.substring(6,10));

    dayTo = (fieldDateToValue.substring(0,2));
    monthTo = (fieldDateToValue.substring(3,5));
    yearTo = (fieldDateToValue.substring(6,10));

    var dateFrom = new Date(yearFrom, monthFrom, dayFrom);
    var dateTo = new Date(yearTo, monthTo, dayTo);

    if (dateFrom > dateTo) {
        Modal.Load('Erro ao filtrar', 'Data de início maior que a data de fim!');
        return false;
    }

    return true;
}

/**
 *
 * Money
 *
 */
function formatReal( int )
{
    var tmp = int+'';
    tmp = tmp.replace(".", "");
    tmp = tmp.replace(/([0-9]{2})$/g, ",$1");
    if( tmp.length > 6 )
        tmp = tmp.replace(/([0-9]{3}),([0-9]{2}$)/g, ".$1,$2");
    return tmp;
}

function formatRealInput( field )
{
    var tmp = field.value;
    tmp = tmp.replace(",", "");
    tmp = tmp.replace(".", "");

    valueIsNumber(tmp);

    tmp = tmp.replace(/([0-9]{2})$/g, ",$1");

    if ( tmp.length > 6 ) {
        tmp = tmp.replace(/([0-9]{3}),([0-9]{2}$)/g, ".$1,$2");
    }
    field.value = tmp;
}

function valueHasThreeDigits( field ){
    var tmp = field.value;
    if(tmp.length == 1){
        field.value = tmp + "00";
    }
    formatRealInput( field )
}

function valueIsNumber(tmp){

    if(tmp.indexOf(",") == 0){
        jQuery('#refund-value').addClass('field-error');
        jQuery('.error').text('Valor inválido.');
        return false;
    }

    tmp = tmp.replace(",", "");
    tmp = tmp.replace(".", "");

    if(isNaN(tmp)) {
        jQuery('#refund-value').addClass('field-error');
        jQuery('.error').text('Valor inválido.');
        return false;
    } else if(tmp.indexOf('-') != -1) {
        jQuery('#refund-value').addClass('field-error');
        jQuery('.error').text('Valor não pode ser negativo.');
        return false;
    } else {
        jQuery('.error').text('');
        jQuery('#refund-value').removeClass('field-error');
        return true;
    }
}

function getMoney( strMoney )
{
    strMoney = strMoney.replace(".", "");
    strMoney = strMoney.replace(",", ".");
    return parseFloat(strMoney);
}