/**
 * Checkout including MyParcel Delivery Options
 * DEFAULT CHECKOUT TEMPLATE
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
             * [Event Ajax Complete]
             * Executed when an ajax after checkout is fired
             * Render the delivery address form if shipping_address ajax is finished
             * **/
            $( document ).ajaxComplete(function( event, xhr, settings ) {
                // If delivery iFrame is not enabled then ignore this function
                if (!MYPARCEL_CHECKOUT.isActive()) {
                    return true;
                }

                // For journal theme
                if (settings.url.indexOf('route=journal2/checkout/shipping') > 0) {
                    var height = '450px';
                    if($('#myparcel_delivery_iframe_height').length > 0){
                        height = $('#myparcel_delivery_iframe_height').val();
                    }

                    $("#myparcel-iframe").css({'height': height});
                    var a = MYPARCEL_CHECKOUT.activateIframe();
                    return true;
                }

                if(settings.url.indexOf('route=journal3/checkout/payment') > 0){

                    var height = '450px';
                    if($('#myparcel_delivery_iframe_height').length > 0){
                        height = $('#myparcel_delivery_iframe_height').val();
                    }

                    $("#myparcel-iframe").css({'height': height});
                    var a = MYPARCEL_CHECKOUT.activateIframe();
                    return true;
                }


                if (
                    settings.url.indexOf('checkout/shipping_address') >= 0
                    ||
                    settings.url.indexOf('checkout/shipping_method') >= 0
                    ||
                    (settings.url.indexOf('checkout/guest_shipping') >= 0 && settings.url.indexOf('checkout/guest_shipping/save') == -1)
                ){

                    if (settings.url.indexOf('checkout/shipping_method') >= 0 && $('#delivery-options-wrapper').data('loaded') == true) {
                        return false;
                    }
                    MYPARCEL_CHECKOUT.activateIframe();

                }
            });

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

                if ( settings.url.indexOf('checkout/checkout/country') >= 0 ) {
                    if ($('#input-shipping-country').is(':visible')) {
                        window.myparcel_country = xhr.responseJSON.iso_code_2;
                        MYPARCEL_CHECKOUT.helper.checkCountry();
                    }
                }
            });

            /**
             * [Event Select Change]
             * Executed when user change value of the select-box "I want to use an existing address" on checkout delivery address form
             * Return an array of address data
             * **/
            $(document).on('click', '#btn-delivery-reset', function() {

                $.ajax({
                    url: window.myparcel_ajax_get_reset_delivery_url,
                    type: 'POST',
                    dataType: 'json',
                    beforeSend: function() {
                        $('#btn-delivery-reset').prop('disabled', true);
                    },
                    success: function(res) {
                        if (res.status == 'success') {
                            if (MYPARCEL_CHECKOUT.helper.checkCountry()) {
                                $('#delivery-options-wrapper').before('<div id="delivery-options-wrapper-temp"></div>').remove();
                                $('#delivery-options-wrapper-temp').after(res.html).remove();
                                MYPARCEL_CHECKOUT.activateIframe();
                            } else {
                                $('#delivery-options-wrapper').html('');
                            }
                        }
                    },
                    complete: function() {
                        $(this).prop('disabled', false);
                    }
                });

                return false;
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

                if (typeof iframeWindow === 'undefined' || !iframeWindow) {
                    return true;
                }

                var shipping_method_quote = $(this).val();
                var parts = shipping_method_quote.split(".");
                if (parts && parts.length == 2) {
                    var shipping_method = parts[0];
                    // If current shipping method does not belong to Parcel, hide iframe or saved delivery options
                    if ($.inArray(shipping_method, window.myparcel_delivery_options_shipping_methods) === -1) {
                        window.parent.window.MYPARCEL_CHECKOUT.eventDeactivated()
                    } else {
                        iframeWindow.myparcel_variable.activateForm();
                    }
                }
            });

            $(document).on('change', '#mypa-input', function() {
                if ($(this).val()) {
                    $('#button-shipping-method').prop('disabled', false);
                    document.cookie = "myparcel_empty_results=0";
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
                        dataType: 'json',
                        beforeSend: function () {
                            button.data('backup', button.html());
                            button.prop('disabled', true).html('<img src="' + window.myparcel_loading_icon + '" />');
                        },
                        success: function (res) {
                            if (res.status == 'success') {
                                button.closest('tr').after(res.html);
                                // For journal theme
                                if (button.parents('.cart-wrapper').length > 0) {
                                    button.closest('tbody').find('.myparcel-total td:first').removeAttr('colspan');
                                }
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
        }
    };

    MYPARCEL_CHECKOUT.helper = {
        updateiFrameAddress: function (address_data) {
            console.log(address_data);
            var shipping_postcode = address_data.postcode;
            var shipping_house_number = address_data.number;
            var shipping_street_name = address_data.street;

            if (shipping_postcode && shipping_house_number) {
                window.mypa.settings.postal_code = shipping_postcode.replace(/\s+/g, '');
                window.mypa.settings.number = shipping_house_number;
                window.mypa.settings.street = shipping_street_name;
                window.mypa.settings.cc = address_data.iso_code_2;
            }

            if (address_data.iso_code_2) {
                window.myparcel_country = address_data.iso_code_2;
                MYPARCEL_CHECKOUT.helper.checkCountry();
            }

            if (typeof iframeWindow !== 'undefined') {
                iframeWindow.mypa = {};
                iframeWindow.mypa.settings = window.mypa.settings;
            }
        },

        updateiFrameAddressByShippingAddress: function (address_1, postcode) {

            if (!$('#myparcel-iframe').length && $('#btn-delivery-reset').length) {
                $('#btn-delivery-reset').trigger('click');
                return false;
            }

            var data = {
                address_1: address_1,
            };

            $.ajax({
                url: window.myparcel_ajax_get_address_components_url,
                data: data,
                type: 'POST',
                dataType: 'json',
                beforeSend: function() {
                    iframeWindow.myparcel_variable.showLoading();
                },
                success: function(res) {
                    if (res.status == 'success') {
                        var address_data = res.address_data;
                        console.log(address_data);
                        window.mypa.settings.street = address_data.street;
                        window.mypa.settings.number = address_data.number;
                        window.mypa.settings.postal_code = postcode;
                        window.mypa.settings.cc = address_data.iso_code_2;
                        iframeWindow.mypa.settings = window.mypa.settings;
                        if (res.iso_code_2) {
                            window.myparcel_country = res.iso_code_2;
                            MYPARCEL_CHECKOUT.helper.checkCountry();
                        }
                    }
                },
                complete: function() {
                    MYPARCEL_CHECKOUT.updateDeliveryForm();
                }
            });
        },

        checkCountry: function() {
            if (window.myparcel_country != 'NL') {
                if (window.myparcel_country == 'BE' && window.enable_belgium) {
                    $('#delivery-options-wrapper').show();
                    return true;
                } else {
                    $('#delivery-options-wrapper').hide();
                    $('#shipping-existing select').prop('disabled', false);
                    return false;
                }
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
    };

    MYPARCEL_CHECKOUT.loadingComplete = function()
    {
        $("input[name='shipping_address']").prop('disabled', false);
        $('#shipping-existing select').prop('disabled', false);
        $('.parcel-shipping-method').prop('checked', true);

        /*
        * Fix delivery options height in mobile
        * */
        var w = window.innerWidth;
        if (w < 407) {
            console.log(w);
            $('#myparcel-iframe').attr('height', 460);
        }
    };

    MYPARCEL_CHECKOUT.eventActivated = function()
    {
        $('.parcel-shipping-method').prop('checked', true);
        $('#myparcel-iframe').attr('height', 450);
        $('#button-shipping-method').prop('disabled', false);
    };

    MYPARCEL_CHECKOUT.eventDeactivated = function()
    {
        iframeWindow.myparcel_variable.resetForm();
        $('#myparcel-iframe').attr('height', 300);
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

        el = document.getElementById('myparcel-iframe');
        if (typeof el === 'undefined' || !el) {
            return false;
        }
        $('#button-shipping-method').prop('disabled', true);
        iframeWindow = el.contentWindow;

        $(el).on('load', function() {
            iframeWindow = this.contentWindow;
            iframeWindow.mypa = {};
            iframeWindow.mypa.settings = window.mypa.settings;

            postJson = function(event){

            };
            postCheck = function(event){

            };
            $(this.contentDocument.getElementById('mypa-input')).on('change', postJson);
            $(this.contentDocument.getElementById('mypa-signed')).on('change', postCheck);
            $(this.contentDocument.getElementById('mypa-recipient-only')).on('change', postCheck);
            $('#delivery-options-wrapper').data('loaded', true);
        });

        // journal2 theme guest checkout
        var data = {};
        var currentTheme = window.myparcel_current_theme;//$(".journal-checkout").length;
        if (currentTheme == 'journal2') {
            if($('input[name="account"]').is(":checked") && ($('input[name="account"]:checked').val() == 'guest' || $('input[name="account"]:checked').val() == 'register')) {
                var type = $('input[name="shipping_address"]').is(":checked") ? 'payment' : 'shipping';
                data.address_1 = $('input[name="' + type + '_address_1"]').val();
                data.address_2 = $('input[name="' + type + '_address_2"]').val();
                data.city = $('input[name="' + type + '_city"]').val();
            }
        }

        // Retrieve address from session
        return $.ajax({
            url: window.myparcel_ajax_get_address_from_session_url,
            type: 'POST',
            dataType: 'json',
            data: data,
            beforeSend: function() {
                $('#myparcel-iframe').before('<p>' + window.entry_loading + '</p>');
                $('#delivery-options-wrapper').hide();
            },
            success: function(res) {
                if (res.status == 'success') {
                    var address_data = res.address_data;
                    MYPARCEL_CHECKOUT.helper.updateiFrameAddress(address_data);

                    if (!MYPARCEL_CHECKOUT.helper.checkCountry()) {
                        return false;
                    }
                }
            },
            complete: function() {
                $('#myparcel-iframe').prev('p').remove();
                $('#delivery-options-wrapper').show();
                if ($('#delivery-options-wrapper').is(":visible")) {
                    el.setAttribute('src', window.myparcel_delivery_iframe_url + Date.now());
                }
            }
        });
    };

    MYPARCEL_CHECKOUT.journalThemeEventActivated = function (upload_page = false, option_change = false) {
        var currentTheme = window.myparcel_current_theme;//$(".journal-checkout").length;
        if (currentTheme == 'journal2') {
            $.ajax({
                cache: false,
                url: 'index.php?route=journal2/checkout/save',
                type: 'post',
                data: $('.parcel-shipping-method, #mypa-input, #mypa-signed:checked, #mypa-recipient-only:checked'),
                dataType: 'json',
                success: function () {
                    $.ajax({
                        cache: false,
                        url: 'index.php?route=journal2/checkout/cart_update',
                        type: 'post',
                        dataType: 'json',
                        success: function (json) {
                            setTimeout(function () {
                                $('#cart-total').html(json['total']);
                            }, 100);

                            $('#cart ul').load('index.php?route=common/cart/info ul li');
                        }
                    });
                    $(document).trigger('journal_checkout_reload_payment');
                    $(document).trigger('journal_checkout_reload_cart');
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    console.error && console.error(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                }
            });
        }
        else if(currentTheme == 'journal3'){
            option_change = (option_change) ? 1 : 0;
            $('#is_change_option').val(option_change);
            $.ajax({
                cache: false,
                url: 'index.php?route=extension/myparcelnl/myparcel_delivery/set_session',
                type: 'post',
                data: $('.parcel-shipping-method, #mypa-input, #mypa-signed:checked, #mypa-recipient-only:checked, #is_change_option'),
                dataType: 'json',
                success: function (response) {
                    if(response.status == 'success'){
                        if((typeof response.update_page != 'undefined' && response.update_page) || upload_page){
                            window['_QuickCheckout'].save();
                        }
                        if(response.html != ''){
                            var element = $('.cart-section').find('.table-bordered');
                            element = element[element.length-1];
                            $(element).find('tfoot').remove();
                            $(element).html(response.html);
                        }

                        if(typeof response.shipping_method_code !='undefined'){
                            if(typeof response.myparcel_shipping_code != 'undefined' && response.myparcel_shipping_code == response.shipping_method_code ){
                                window['_QuickCheckout'].order_data.shipping_code = response.shipping_method_code;
                            }
                            var shipping_method_elements = $("input[name=shipping_method]");
                            for (var i = 0; i< shipping_method_elements.length ; i++ ){
                                if($(shipping_method_elements[i]).val() == response.shipping_method_code){
                                    $(shipping_method_elements[i]).prop('checked', true);
                                }
                                else{
                                    $(shipping_method_elements[i]).prop('checked', false);
                                }
                            }
                        }
                        if(typeof response.is_close_delivery_options != 'undefined' && response.is_close_delivery_options){

                            var shipping_method_quote = $('input[name="shipping_method"]:checked').val();

                            var parts = shipping_method_quote.split(".");
                            if (parts && parts.length == 2) {
                                var shipping_method = parts[0];
                                // If current shipping method does not belong to Parcel, hide iframe or saved delivery options
                                if ($.inArray(shipping_method, window.myparcel_delivery_options_shipping_methods) === -1) {
                                    window.parent.window.MYPARCEL_CHECKOUT.eventDeactivated()
                                }
                            }
                        }
                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    console.error && console.error(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                }
            });
        }
        
        //support onepage checkout plugin 10-10-2021
        var onepagecheckout_status = window.onepagecheckout_status;
        if(onepagecheckout_status){
            $.ajax({
                url: 'index.php?route=extension/onepagecheckout/shipping_method/saveshipping',
                type:'post',
                data:$('.delivery-method-content input[type="radio"]:checked,.parcel-shipping-method, #mypa-input, #mypa-signed:checked, #mypa-recipient-only:checked'),
                dataType: 'json',
                success: function(json){
                    $('.alert, .text-danger').remove();
                    if(json['error']){
                        //$('.delivery-method-content').before('<div class="alert alert-danger">' + json['error']['warning'] + '<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
                    }
                    if(json['success']){
                        LoadCartWithoutloader();
                        $('.common-confirm-button').prop('disabled', false);
                    }
                }
            })
        }
    }

    $(document).ready(function ($) {
        MYPARCEL_CHECKOUT.initialize.onReady();
        $(document).delegate('input[name*="address_1"], input[name*="address_2"]', 'change', function () {
            var $this = $('input[name="shipping_address"]');

            if ($this.is(':checked')) {
                $(document).trigger('journal_checkout_address_changed', 'payment');
            } else {
                $(document).trigger('journal_checkout_address_changed', 'payment');
                $(document).trigger('journal_checkout_address_changed', 'shipping');
            }
        });
    });

    window.mypaLoaded = function() {
        return true;
    };
})(jQuery);
