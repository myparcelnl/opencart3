var MYPARCEL_SHIPMENT = MYPARCEL_SHIPMENT || {};

(function ($) {

    MYPARCEL_SHIPMENT.initialize = {
        onReady: function () {
            MYPARCEL_SHIPMENT.event.addEventListener();
        }
    };

    MYPARCEL_SHIPMENT.event = {
        addEventListener: function() {

            /**
             * [Event Click]
             * Export An Order To MyParcel
             * Call API through Ajax then refresh the row containing the button clicked or reload order detail page
             * **/
            $(document).on('click', '.btn-myparcel-action', function(e) {
                var loader_content = '<img src="' + $(this).data('loader') + '">';
                var order_id = $(this).data('order-id');
                var action = $(this).data('action');
                var screen = $(this).data('screen');
                var button_id = '#btn-myparcel-' + action + '-' + order_id;

                var data = {
                    order_ids: [order_id],
                    action: action,
                    screen: screen
                };

                $.ajax({
                    url: $(this).attr('href'),
                    data: data,
                    type: 'POST',
                    dataType: 'json',
                    beforeSend: function() {
                        MYPARCEL_SHIPMENT.helper.showLoadingIconInButton(button_id, loader_content);
                        MYPARCEL_SHIPMENT.helper.disableActionButtons(order_id);
                    },
                    success: function(res) {
                        if (res.status == 'success') {
                            if (res.action == 'reload' || res.current_screen == 'order_detail') {
                                location.reload();
                            } else if (res.action == 'refresh') {
                                $('#' + res.element_id).html(res.html);
                                if (res.new_order_status) {
                                    $('#' + res.order_status_element_id).html(res.new_order_status)
                                }
                            }
                        }
                        else{
                            swal(res.errors.join('.'));
                        }
                        MYPARCEL_SHIPMENT.helper.enableActionButtons(order_id);
                    },
                    complete: function() {
                        MYPARCEL_SHIPMENT.helper.hideLoadingIconInButton(button_id);
                    }
                });

                return false;
            });

            /**
             * [Event Click]
             * **/
            $(document).on('click', '.get_labels', function() {
                // setTimeout( function() {
                //     location.reload();
                // }, 2000);
                // return true;
            });

            $(document).on('click','.btn-print-label-order',function () {
                var element = this;
                var order_id = $(element).attr('data-order-id');
                var position_label = new Array();
                var url = $(element).attr('data-url');

                var elePosition = $('#position_label_modal_' + order_id +' .a6-label');

                for(var i = 0; i < elePosition.length ; i++){
                    if($(elePosition[i]).hasClass('active')){
                        var number = $(elePosition[i]).find('.fa-check').attr('data-position_number');
                        position_label.push(number)
                    }
                }
                if(position_label.length > 1){
                    $('#position_label_modal_' + order_id).modal('hide');
                    swal('You only choose 1 position.');
                    return false;
                }

                url += '&position=' + position_label.join(',');
                window.open(url,'_blank');
                setTimeout( function() {
                    location.reload();
                }, 2000);
                return true;
            });

            /**
             * [Event Click]
             * Show Shipment Options Popup
             * Before Submit Related Return Shipment To MyParcel
             * Load The Form Via Ajax And Show Popup
             * **/
            $(document).on('click', '.btn-myparcel-return', function() {

                var order_id = $(this).data('order-id');
                var action = $(this).data('action');
                var screen = $(this).data('screen');
                var button_id = '#btn-myparcel-' + action + '-' + order_id;
                var loader_content = '<img src="' + $(this).data('loader') + '">';
                var popup_loader_content = '<img class="myparcel-loader" src="' + $(this).data('popup-loader') + '">';

                var data = {
                    order_ids: [order_id],
                    action: action,
                    screen: screen
                };

                $.ajax({
                    url: $(this).attr('href'),
                    data: data,
                    type: 'POST',
                    dataType: 'json',
                    beforeSend: function() {
                        MYPARCEL_SHIPMENT.helper.showLoadingIconInButton(button_id, loader_content);
                        MYPARCEL_SHIPMENT.helper.disableActionButtons(order_id);
                        $('#modal-body-myparcel-shipment').html(popup_loader_content)
                    },
                    success: function(res) {
                        $('#modal-body-myparcel-shipment').html(res.html)
                        $('.package_type').change();

                        if (res.status == 'success') {
                            // TODO process the successful response
                        }

                        $.each(res.order_ids, function( index, order_id ) {
                            var button_id = '#btn-myparcel-' + action + '-' + order_id;
                            MYPARCEL_SHIPMENT.helper.hideLoadingIconInButton(button_id);
                            MYPARCEL_SHIPMENT.helper.enableActionButtons(order_id);
                        });
                    }
                });

                var popup_elem = $("#modal-myparcel-shipment");
                if (popup_elem.data('version') == 1) {
                    $.magnificPopup.open({
                        items: {
                            src: '#modal-myparcel-shipment'
                        },
                        type: 'inline'
                    });
                } else {
                    popup_elem.modal();
                }

                return false;
            });

            /**
             * [Event Click]
             * Add Related Return Shipment For An Order To MyParcel
             * Call API through Ajax then refresh the row containing the button clicked or reload order detail page
             * **/
            $(document).on('click', '#btn-myparcel-submit-return', function() {

                var order_id        = $(this).data('order-id');
                var loader_content  = '<img src="' + $(this).data('loader') + '">';
                var $wrapper        = $( this ).closest('#modal-body-myparcel-shipment');
                var screen          = $( this ).data('screen');
                var button_id       = '#btn-myparcel-submit-return';

                var data = {
                    order_ids: [order_id],
                    data: $wrapper.find(":input").serialize(),
                    screen: screen
                };

                $.ajax({
                    url: $(this).data('url'),
                    data: data,
                    type: 'POST',
                    dataType: 'json',
                    beforeSend: function() {
                        MYPARCEL_SHIPMENT.helper.showLoadingIconInButton(button_id, loader_content);
                        MYPARCEL_SHIPMENT.helper.disableActionButtons(order_id);
                    },
                    success: function(res) {

                        if (res.status == 'success') {
                            if (res.current_screen == 'order_detail') {
                                location.reload();
                            } else {
                                $('#' + res.element_id).html(res.html);
                            }

                            // Close the modal
                            var popup_elem = $("#modal-myparcel-shipment");
                            if (popup_elem.data('version') == 1) {
                                $.magnificPopup.close();
                            } else {
                                popup_elem.modal('hide');
                            }

                        } else {
                            $('#myparcel-error-messages-wrapper').remove();
                            $('#modal-body-myparcel-shipment').prepend(res.html);
                            MYPARCEL_SHIPMENT.helper.hideLoadingIconInButton(button_id);
                            MYPARCEL_SHIPMENT.helper.enableActionButtons(order_id);
                        }

                        $(button_id).prop('disabled', false);
                        $.each(res.order_ids, function( index, order_id ) {
                            MYPARCEL_SHIPMENT.helper.hideLoadingIconInButton(button_id);
                            MYPARCEL_SHIPMENT.helper.enableActionButtons(order_id);
                        });
                    }
                });
            });


            /**
             * [Event Click]
             * Click export batch in list order
             * **/
            $(document).on('click', '#button-export-batch, #button-export-print-batch', function() {

                if ($('input[name="selected[]"]:checked').length <= 0) {
                    return false;
                }

                var btn = $(this);
                btn.prop('disabled', true);
                var button_id = '#'+$(this).attr("id");
                var loader_content = '<img src="' + $(this).data('loader') + '">';
                var order_ids = [];
                $("input[name='selected[]']:checked").each(function ()
                {
                    order_ids.push(parseInt($(this).val()));
                });

                var print = '';
                var button_action = $(this).data('action');
                if (button_action == 'export') {
                    print = 'no';
                }else if(button_action == 'export-print'){
                    print = 'yes';
                }
                var data = {
                    order_ids : order_ids,
                    print     : print,
                };

                var url_export = $(this).data('url');
                url_print = url_export.replace("exportPrintBatch", "exportBatch");

                var error_text = $(this).data('error-text');

                $.ajax({
                    url: url_export,
                    data: data,
                    type: 'POST',
                    dataType: 'json',
                    beforeSend: function() {
                        MYPARCEL_SHIPMENT.helper.showLoadingIconInButton(button_id, loader_content);
                    },
                    success: function(res) {
                        if (res.status == 'success') {
                            if (print == 'no') {
                                location.reload();
                            } else {
                                if (res.multiple_returned) {
                                    $.each(res.items, function (key, item) {
                                        $('#' + item.element_id).html(item.html);
                                        if (item.new_order_status) {
                                            $('#' + item.order_status_element_id).html(item.new_order_status)
                                        }
                                    });
                                } else {
                                    $('#' + res.element_id).html(res.html);
                                    if (res.new_order_status) {
                                        $('#' + res.order_status_element_id).html(res.new_order_status)
                                    }
                                }

                                $("#button-print-batch")[0].click();
                            }

                        } else{
                            swal(error_text);
                            console.log(res.errors);
                            location.reload();
                        }
                        btn.prop('disabled', false);
                        MYPARCEL_SHIPMENT.helper.hideLoadingIconInButton(button_id);
                    }
                });
                return false;
            });

            /**
             * [Event Click]
             * Click print batch in list order
             * **/
            $(document).on('click', '#button-print-batch', function() {
                if ($('input[name="selected[]"]:checked').length <= 0) {
                    return false;
                }

                var eleInputSeleted = $('input[name="selected[]"]:checked');
                //order have not been exported yet
                var orderNotExported = new Array();
                var errorMessage = '';
                for (i = 0; i < eleInputSeleted.length; i++){
                    if($('#btn-myparcel-get_labels-' + $(eleInputSeleted[i]).val()).length == 0){
                        orderNotExported.push('#'+ $(eleInputSeleted[i]).val());
                        $(eleInputSeleted[i]).prop('checked',false);
                    }
                }
                if(orderNotExported.length > 0){
                    if(orderNotExported.length > 1){
                        var lastOrderId = orderNotExported.pop();
                        errorMessage = 'You are trying to download labels but Orders ' + orderNotExported.join(',') + ' and ' + lastOrderId + ' have not been exported yet.'
                    }
                    else{
                        errorMessage = 'You are trying to download labels but Order ' + orderNotExported.pop() + ' has not been exported yet.'
                    }

                    if ($('input[name="selected[]"]:checked').length <= 0) {
                        // alert(errorMessage  + ' Please choose other orders!');
                        swal(errorMessage  + ' Please choose other orders!');
                        return false;
                    }
                    extensionMessage = 'Do you want to download the labels of the orders which have been exported?';
                    swal({
                            title: "",
                            text: errorMessage + extensionMessage,
                            type: "warning",
                            showCancelButton: true,
                            confirmButtonClass: "btn-primary",
                            confirmButtonText: "OK",
                            cancelButtonText: "Cancel",
                            closeOnConfirm: true,
                            closeOnCancel: true
                        },
                        function(isConfirm) {
                            if (isConfirm) {
                                showPositionModal();
                            }
                        });
                    return false;
                }

                showPositionModal();
                return false;

            });

            $(document).on('click','.btn-print-multi-label-order',function (e) {
                if ($('input[name="selected[]"]:checked').length <= 0) {
                    return false;
                }
                var elePosition = $('#position_label_modal .a6-label');
                var eleInputSeleted = $('input[name="selected[]"]:checked');
                var position_label = new Array();
                for(var i = 0; i < elePosition.length ; i++){
                    if($(elePosition[i]).hasClass('active')){
                        var number = $(elePosition[i]).find('.fa-check').attr('data-position_number');
                        position_label.push(number)
                    }
                }
                if(position_label.length > eleInputSeleted.length){
                    $('#position_label_modal').modal('hide');
                    swal('You can only select up to '+ eleInputSeleted.length +' position.');
                    return false;
                }
                //position_label.sort();
                $('#form-order').append('<input type="hidden" value="'+ position_label.join(';') +'" name="positions">');
                setTimeout( function() {
                    location.reload();
                }, 2000);
                return true;
            });

            $(document).on('click','.a6-label',function () {
                var element = this;

                if($(element).hasClass('active')){
                    $(element).removeClass('active');
                    $(element).find('.fa-check').addClass('hidden');
                    $(element).find('.fa-times').removeClass('hidden');
                }
                else{
                    $(element).addClass('active');
                    $(element).find('.fa-check').removeClass('hidden');
                    $(element).find('.fa-times').addClass('hidden');
                }

            });

            function showPositionModal() {
                unCheckAllPaper();
                var elePosition = $('#position_label_modal .a6-label');
                var eleInputSeleted = $('input[name="selected[]"]:checked');
                var count = elePosition.length;
                if(count > eleInputSeleted.length){
                    count = eleInputSeleted.length
                }
                for (var i = 0; i < count; i++){
                    $(elePosition[i]).click();
                }
                $('#position_label_modal').modal('show');
                return false;
            }

            function unCheckAllPaper(){
                var element = $('#position_label_modal .a6-label');
                for(var i = 0; i< element.length; i++){
                    if($(element).hasClass('active')){
                        $(element).removeClass('active');
                        $(element).find('.fa-check').addClass('hidden');
                        $(element).find('.fa-times').removeClass('hidden');
                    }
                }
            }

            $(document).on('change', '.checkbox-for-all', function() {
                $('#button-print-batch').prop('disabled', !$(this).prop('checked'));
                $('#button-export-batch').prop('disabled', !$(this).prop('checked'));
                $('#button-export-print-batch').prop('disabled', !$(this).prop('checked'));
            });

            /**
             * [Event Click]
             * Executed when user click on "Details" button in checkout confirm step
             * @action Show myparcel total details
             * **/
            $(document).on('click', '.button-myparcel-total-details', function() {

                var button = $(this);

                if (button.data('collapse') == '1') {
                    $.ajax({
                        url: window.myparcel_ajax_get_total_details_url,
                        type: 'POST',
                        data: {delivery_options: window.myparcel_delivery_options, myparcel_order_id: window.myparcel_order_id, admin: true},
                        dataType: 'json',
                        beforeSend: function () {
                            button.data('backup', button.html());
                            button.prop('disabled', true).html('<img src="' + window.myparcel_loading_icon + '" />');
                        },
                        success: function (res) {
                            if (res.status == 'success') {
                                $('.button-myparcel-total-details').closest('tr').after(res.html);
                            }
                        },
                        complete: function () {
                            button.prop('disabled', false).html(button.data('backup'));
                            button.find('i').addClass('fa-caret-up').removeClass('fa-caret-down');
                            button.data('collapse', '0');
                        }
                    });
                } else {
                    button.data('collapse', '1');
                    button.find('i').addClass('fa-caret-down').removeClass('fa-caret-up');
                    button.closest('tr').nextAll('tr.myparcel-total').remove();
                }
                return false;
            });

            /*tap.nguyen*/
            // show summary when clicked
            $('.wcmp_show_shipment_summary').click( function ( event ) {
                event.preventDefault();
                $( this ).next('.wcmp_shipment_summary_list').slideToggle();
            });

            // hide summary when click outside
            $(document).click(function(event) {
                if(!$(event.target).closest('.wcmp_shipment_summary_list').length) {
                    if( !( $(event.target).hasClass('wcmp_show_shipment_summary') || $(event.target).parent().hasClass('wcmp_shipment_summary') ) && $('.wcmp_shipment_summary_list').is(":visible")) {
                        console.log(event.target);
                        $('.wcmp_shipment_summary_list').slideUp();
                    }
                }
            })
            /*tap.nguyen*/


            $('.oc_save_shipment_settings').on( 'click', 'a.button.save', function() {
                var order_id                   = $( this ).data().order;
                var url                        = $( this ).data().url;
                var $form                      = $( this ).closest('.oc_shipment_options').find('.oc_shipment_options_form');
                var form_data                  = $form.find(":input").serialize();
                var package_type               = $form.find('select.package_type option:selected').text();
                var $package_type_text_element = $( this ).closest('.oc_shipment_options').find('.oc_package_type');
                var data = {
                    order_id:   order_id,
                    form_data:  form_data,
                };

                $.ajax({
                    url: url,
                    data: data,
                    type: 'POST',
                    dataType: 'json',
                    beforeSend: function() {
                        $form.find('.oc_save_shipment_settings .waiting').show();
                    },
                    success: function(res) {
                        if (res.status == 'error') {
                            $form.find('.err').remove();
                            $("<div class='alert alert-danger err " + (is_opencart1 ? 'opencart1' : '') + "'>"+res.msg+"</div>").prependTo($form);
                            // hide spinner
                            $form.find('.oc_save_shipment_settings .waiting').hide();
                        }else{
                            // set main text to selection
                            $package_type_text_element.text(package_type);

                            // hide spinner
                            $form.find('.oc_save_shipment_settings .waiting').hide();
                            //remove div errors
                            $form.find('.err').remove();

                            // hide the form
                            $form.slideUp();

                        }
                    },

                });
            });


            // hide all options if not a parcel
            $(document).on('change', 'select.package_type', function () {
                var parcel_options  = $( this ).closest('table').parent().find('.parcel_options');
                var digital_stamp_options  = $( this ).closest('table').parent().find('.digital_stamp_options');
                if ( $( this ).val() == '1') {
                    // parcel
                    $( parcel_options ).find('input, textarea, button, select').prop('disabled', false);
                    $( parcel_options ).show();
                    $( parcel_options ).find('.insured').change();
                    $( digital_stamp_options ).find(' select').prop('disabled', true);
                    $( digital_stamp_options ).hide();
                } else if($( this ).val() == '4'){
                    //digital stamp type
                    $( digital_stamp_options ).find(' select').prop('disabled', false);
                    $( digital_stamp_options ).show();

                    $( parcel_options ).find('input, textarea, button, select').prop('disabled', true);
                    $( parcel_options ).hide();

                } else {
                    // not a parcel
                    $( parcel_options ).find('input, textarea, button, select').prop('disabled', true);
                    $( parcel_options ).hide();

                    //disable digital stamp options
                    $( digital_stamp_options ).find(' select').prop('disabled', true);
                    $( digital_stamp_options ).hide();
                    // No need the 2 lines below because "not a parcel" packages have no addition options and "insured" will be filtered in backend code
                    //$( parcel_options ).find('.insured').prop('checked', false);
                    //$( parcel_options ).find('.insured').change();
                }
            }).change(); //ensure visible state matches initially
            $('.package_type').change();

            // hide insurance options if insured not checked
            $(document).on('change', 'input.insured', function () {
                var insured_select = $( this ).closest('table').parent().find('select.insured_amount');
                var insured_input  = $( this ).closest('table').parent().find('input.insured_amount');
                var insured_enable  = $( this ).closest('table').parent().find('input.insured_enable');

                if (this.checked) {
                    $( insured_select ).prop('disabled', false);
                    $( insured_select ).closest('tr').show();
                    $( insured_enable ).val(1);
                    $('select.insured_amount').change();
                } else {
                    $( insured_enable ).val(0);
                    $( insured_select ).prop('disabled', true);
                    $( insured_select ).closest('tr').hide();
                    $( insured_input ).closest('tr').hide();
                }
            }).change(); //ensure visible state matches initially

            // show options when clicked
            $('.oc_show_shipment_options').click( function ( event ) {
                event.preventDefault();
                $( this ).nextAll('.oc_shipment_options_form').slideToggle();
            });
            // hide options form when click outside
            $(document).click(function(event) {
                if(!$(event.target).closest('.oc_shipment_options_form').length) {
                    if( !( $(event.target).hasClass('oc_show_shipment_options') || $(event.target).parent().hasClass('oc_show_shipment_options') ) && $('.oc_shipment_options_form').is(":visible")) {
                        $('.oc_shipment_options_form').slideUp();
                    }
                }
            })

            // select > 500 if insured amount input is >499
            $( '.oc_shipment_options input.insured_amount' ).each( function( index ) {
                if ( $( this ).val() > 499 ) {
                    var insured_select = $( this ).closest('table').parent().find('select.insured_amount');
                    $( insured_select ).val('');
                };
            });

            // hide & disable insured amount input if not needed
            $(document).on('change', '.ocmyparcel_settings_table select.insured_amount', function () {
                var insured_check  = $( this ).closest('table').parent().find('input.insured');
                var insured_select = $( this ).closest('table').parent().find('select.insured_amount');
                var insured_input  = $( this ).closest('table').find('input.insured_amount');
                var insured_enable  = $( this ).closest('table').parent().find('input.insured_enable');

                if ( $( insured_select ).val() ) {
                    $( insured_input ).val('');
                    $( insured_input ).prop('disabled', true);
                    $( insured_input ).closest('tr').hide();
                } else {
                    if (insured_check.prop('checked')) {
                        $( insured_input ).prop('disabled', false);
                        $( insured_input ).closest('tr').show();
                    }else{
                        $( insured_input ).prop('disabled', true);
                        $( insured_input ).closest('tr').hide();
                    }

                }
            }).change(); //ensure visible state matches initially
            $('.ocmyparcel_settings_table select.insured_amount').change();
        },
    };

    MYPARCEL_SHIPMENT.helper = {
        showLoadingIconInButton: function (button_id, loader_content) {
            var button = $(button_id);
            button.data('backup-html', button.html());
            button.html(loader_content);
            button.attr('disabled', 'disabled');
        },
        hideLoadingIconInButton: function (button_id) {
            var button = $(button_id);
            button.html(button.data('backup-html'));
            button.removeAttr('disabled', 'disabled');
        },
        disableActionButtons: function(order_id) {
            $('.btn-myparcel-' + order_id).attr('disabled', true);
        },
        enableActionButtons: function(order_id) {
            $('.btn-myparcel-' + order_id).attr('disabled', false);
        }
    };

    $(document).ready(function ($) {
        MYPARCEL_SHIPMENT.initialize.onReady();
    });
})(jQuery);

function htmlEntities(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
