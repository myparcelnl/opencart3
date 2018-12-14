/**
 * Checkout including MyParcel Delivery Options
 * AJAX QUICK CHECKOUT TEMPLATE
 * @version 1.0
 * **/

var MYPARCEL_CHECKOUT = MYPARCEL_CHECKOUT || {};

(function ($) {

    MYPARCEL_CHECKOUT.initialize = {
        onReady: function () {
            MYPARCEL_CHECKOUT.event.addEventListener();
            window.mypajQuery = $;
        },
    };

    MYPARCEL_CHECKOUT.event = {
        addEventListener: function() {

            /**
             * [Event Ajax Success]
             * Executed when an ajax success is fired
             * Update the delivery options when shipping country is changed
             * **/
            $( document ).ajaxSuccess(function( event, xhr, settings ) {

                // If delivery iFrame is not enabled then ignore this function
                if (!MYPARCEL_CHECKOUT.isActive()) {
                    return true;
                }

                var is_payment_update = settings.url.indexOf('d_quickcheckout/payment_address/update');
                var is_shipping_update = settings.url.indexOf('d_quickcheckout/shipping_address/update');

                if (
                    is_payment_update >= 0 ||
                    is_shipping_update >= 0 ||
                    settings.url.indexOf('d_quickcheckout/login/updateAll') >= 0
                ) {
                    var same_shipping_address = false;
                    if ($('#payment_address_shipping_address').prop('checked')) {
                        same_shipping_address = true;
                    }

                    var need_compare_address = false;
                    if (is_payment_update >= 0 && !same_shipping_address) {
                        return true;
                    } else {
                        // Compare current address
                        if (is_payment_update >=0 || is_shipping_update) {
                            need_compare_address = true;
                        }
                    }

                    var aqcRes = xhr.responseJSON;
                    if (typeof aqcRes === 'undefined' || !aqcRes) {
                        aqcRes = JSON.parse(xhr.responseText);
                    }

                    if (need_compare_address && aqcRes) {
                        var addressData = {};

                        if (is_payment_update >= 0) {
                            addressData = aqcRes.payment_address;
                        } else {
                            addressData = aqcRes.shipping_address;
                        }

                        if (
                            $.trim(window.mypa.settings.street + ' ' + window.mypa.settings.number)     === $.trim(addressData.address_1 + ' ' + addressData.address_2) &&
                            window.mypa.settings.postal_code                                            === addressData.postcode
                        ) {
                           return true;
                        }
                    }

                    MYPARCEL_CHECKOUT.retrieveAddressComponents(aqcRes);
                }
            });

            $(document).on('change', '#mypa-input', function() {

                $.ajax({
                    url: window.myparcel_ajax_set_session,
                    data: {
                        'mypa_data': $('#mypa-input').val(),
                        'mypa_signed': ($('#mypa-signed').prop('checked') ? 'on' : 'off'),
                        'mypa_recipient_only': ($('#mypa-recipient-only').prop('checked') ? 'on' : 'off'),
                    },
                    type: 'POST',
                    dataType: 'json',
                    success: function(res) {
                        if (res.status == 'success') {
                            preloaderStart();
                            if (window.myparcel_oc_version < '2.0.0.0') {
                                $('.qc-product-qantity').trigger('change');
                            } else {
                                qc.cart.updateCart();
                            }
                        }
                    }
                });
            });

            /**
             * [Event Radio Change]
             * Executed when user change data on the radio button
             * Existing Shipping Address or not
             * @action Update MyParcel delivery form
             * **/
            $(document).on('change', 'input[name="shipping_address"]', function() {

                if (!MYPARCEL_CHECKOUT.isActive()) {
                    return true;
                }

                if ($(this).val() == 'new') {
                    $('#mypa-input').val('');
                    $('#input-shipping-address-1').trigger('change');
                } else {
                    $('#shipping-existing select').trigger('change');
                }
            });

            /**
             * [Event Radio Change]
             * Executed when user change data on the radio button
             * Switching between shipping methods in step 4
             * @action Update MyParcel delivery form
             * **/
            $(document).on('change', 'input[name="shipping_method"]', function() {

                if (!MYPARCEL_CHECKOUT.isActive()) {
                    return true;
                }

                var shipping_method_quote = $(this).val();
                var parts = shipping_method_quote.split(".");
                if (parts && parts.length == 2) {
                    var shipping_method = parts[0];
                    // If current shipping method does not belong to Parcel, hide iframe or saved delivery options
                    if ($.inArray(shipping_method, window.myparcel_delivery_options_shipping_methods) === -1) {
                        $('#delivery-options-wrapper').hide();
                        //$("#modal-shipping-method-warning").modal();
                    } else {
                        if (MYPARCEL_CHECKOUT.helper.checkCountry()) {
                            $('#delivery-options-wrapper').show();
                        }
                    }
                }
            });

            $(document).on('change', '#payment_address_country_id', function() {
                var same_shipping_address = false;
                if ($('#payment_address_shipping_address').prop('checked')) {
                    same_shipping_address = true;
                }

                if ($(this).val() != 150 && same_shipping_address) {
                    $('#delivery-options-wrapper').hide();
                } else if (same_shipping_address) {
                    $('#delivery-options-wrapper').show();
                }
            });

            $(document).on('change', '#shipping_address_country_id', function() {
                if ($(this).val() != 150) {
                    $('#delivery-options-wrapper').hide();
                } else {
                    $('#delivery-options-wrapper').show();
                }
            });

            /**
             * [Event Click]
             * Executed when user click on "Details" button in checkout confirm step
             * @action Show myparcel total details
             * **/
            $(document).on('click', '.button-myparcel-total-details', function() {

                if (!MYPARCEL_CHECKOUT.isActive()) {
                    return true;
                }

                var button = $(this);

                if (button.data('collapse') == '1') {
                    $.ajax({
                        url: window.myparcel_ajax_get_total_details_url,
                        type: 'POST',
                        data: {
                            'aqc': true,
                            'label_class': $(this).closest('label').attr('class'),
                            'price_class': $(this).closest('label').next('div').attr('class')
                        },
                        dataType: 'json',
                        beforeSend: function () {
                            button.data('backup', button.html());
                            button.prop('disabled', true).html('<img src="' + window.myparcel_loading_icon + '" />');
                        },
                        success: function (res) {
                            if (res.status == 'success') {
                                button.closest('.row', $('div.qc-totals')).after(res.html);
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
                    button.closest('.row').nextAll('.row.myparcel-total').remove();
                }
                return false;
            });
        },
    };

    MYPARCEL_CHECKOUT.helper = {
        checkCountry: function() {
            if (window.myparcel_country != 'NL') {
                $('#delivery-options-wrapper').hide();
                //$('#shipping-existing select').prop('disabled', false);
                return false;
            } else {
                $('#delivery-options-wrapper').show();
                return true;
            }
        }
    };

    MYPARCEL_CHECKOUT.isActive = function() {
        return window.enable_delivery;
    };

    MYPARCEL_CHECKOUT.updateDeliveryForm = function() {
        iframeWindow.myparcel_variable.updatePage();
    };

    MYPARCEL_CHECKOUT.loading = function()
    {
        $("input[name='shipping_address']").prop('disabled', true);
        $('#shipping-existing select').prop('disabled', true);
        //$('#button-shipping-address').prop('disabled', true);
    };

    MYPARCEL_CHECKOUT.loadingComplete = function()
    {
        $("input[name='shipping_address']").prop('disabled', false);
        $('#shipping-existing select').prop('disabled', false);
        //$('#button-shipping-address').prop('disabled', false);
    };

    MYPARCEL_CHECKOUT.eventError = function()
    {
        $('#delivery-options-wrapper').hide();
    };

    MYPARCEL_CHECKOUT.activateIframe = function() {
        // If delivery iFrame is not enabled then ignore this function
        if (!MYPARCEL_CHECKOUT.isActive()) {
            return true;
        }

        var delivery_options_wrapper = $('#delivery-options-wrapper')

        el = document.getElementById('myparcel-iframe');

        if (typeof el === 'undefined' || !el) {
            delivery_options_wrapper.data('loading', false);
            return false;
        }

        iframeWindow = el.contentWindow;

        $(el).load(function() {

            iframeWindow = this.contentWindow;
            iframeWindow.mypa = {};
            if (window.mypa.settings.street) {
                iframeWindow.mypa.settings = window.mypa.settings;
            } else {
                // In case shipping address is empty from js
                // Retrieve address from session
                $.ajax({
                    url: window.myparcel_ajax_get_address_from_session_url,
                    type: 'POST',
                    dataType: 'json',
                    beforeSend: function() {
                        $('#myparcel-iframe').before('<p>' + window.entry_loading + '</p>');
                        $('#delivery-options-wrapper').hide();
                    },
                    success: function(res) {
                        if (res.status == 'success') {
                            var address_data = res.address_data;
                            MYPARCEL_CHECKOUT.setAddressData(address_data, address_data.postcode);

                            if (!MYPARCEL_CHECKOUT.helper.checkCountry()) {
                                return false;
                            }
                        }
                    },
                    complete: function() {
                        $('#myparcel-iframe').prev('p').remove();
                        $('#delivery-options-wrapper').show();
                    }
                });

                iframeWindow.mypa.settings = window.mypa.settings;
            }

            postJson = function(event){
                el = document.getElementById('event-log');
                el.innerHTML = el.innerHTML + $(event.currentTarget).val() + "<br>";
            };
            postCheck = function(event){
                el = document.getElementById('event-log');
                el.innerHTML = el.innerHTML + $(event.currentTarget).prop('checked') + "<br>";
            };
            $(this.contentDocument.getElementById('mypa-input')).on('change', postJson);
            $(this.contentDocument.getElementById('mypa-signed')).on('change', postCheck);
            $(this.contentDocument.getElementById('mypa-recipient-only')).on('change', postCheck);
            delivery_options_wrapper.data('loaded', true);
            delivery_options_wrapper.data('loading', false);

            if ($('input[name="shipping_address[address_id]"]').length) {
                $('input[name="shipping_address[address_id]"]').trigger('change');
            }
        });

        if ($('#delivery-options-wrapper').is(":visible")) {
            el.setAttribute('src', window.myparcel_delivery_iframe_url + Date.now());
        }
    };

    MYPARCEL_CHECKOUT.retrieveAddressComponents = function(aqcRes) {

        if (aqcRes.shipping_address.iso_code_2) {
            window.myparcel_country = aqcRes.shipping_address.iso_code_2;
            MYPARCEL_CHECKOUT.helper.checkCountry();
        }

        var address_1 = aqcRes.shipping_address.address_1;
        if (typeof aqcRes.shipping_address.address_2 !== 'undefined') {
            address_1 += ' ' + aqcRes.shipping_address.address_2;
        }
        var data = {
            address_1: address_1,
        };

        $.ajax({
            url: window.myparcel_ajax_get_address_components_url,
            data: data,
            type: 'POST',
            dataType: 'json',
            success: function(res) {
                if (res.status == 'success') {
                    var address_data = res.address_data;
                    MYPARCEL_CHECKOUT.setAddressData(address_data, aqcRes.shipping_address.postcode);
                }
            },
        });
    };

    MYPARCEL_CHECKOUT.setAddressData = function(address_data, postcode) {

        window.mypa.settings.street = address_data.street;
        window.mypa.settings.number = address_data.number;
        window.mypa.settings.postal_code = postcode;

        el = document.getElementById('myparcel-iframe');
        if ((typeof el !== 'undefined' && el !== null)) {
            iframeWindow = el.contentWindow;
        }

        iframeWindow.mypa.settings = window.mypa.settings;

        if ($('#delivery-options-wrapper').data('loaded')) {
            MYPARCEL_CHECKOUT.updateDeliveryForm();
        } else {
            MYPARCEL_CHECKOUT.activateIframe();
        }
    };

    $(document).ready(function ($) {
        MYPARCEL_CHECKOUT.initialize.onReady();
    });

    window.mypaLoaded = function() {
        return true;
        //iframeWindow.initSettings( window.mypa.settings );
    };
})(jQuery);