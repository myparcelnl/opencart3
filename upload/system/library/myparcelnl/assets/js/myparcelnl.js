$(document).ready(function() {
    $('.shipping_methods_package_types').select2();
    $('.dropoff_days').select2();
    var colorOptions = {
        onShow: function (colpkr) {
            $(colpkr).fadeIn(500);
            return false;
        },
        onHide: function (colpkr) {
            $(colpkr).fadeOut(500);
            return false;
        },
        onSubmit: function (colpkr) {
            $(colpkr).fadeOut(500);
            return false;
        }
    };

    $('#color-selector-base').ColorPicker(
        $.extend(
            colorOptions,
            {
                onChange: function (hsb, hex, rgb) {
                    $('#color-selector-base div').css('backgroundColor', '#' + hex);
                    $('#color-picker-input-base').val(hex);
                }
            }
        )
    );

    $('#color-selector-highlight').ColorPicker(
        $.extend(
            colorOptions,
            {
                onChange: function (hsb, hex, rgb) {
                    $('#color-selector-highlight div').css('backgroundColor', '#' + hex);
                    $('#color-picker-input-highlight').val(hex);
                }
            }
        )
    );

    $("#enable_delivery").change(function(){
        if($(this).prop('checked') == true){
    		if ($('#settings_delivery').hasClass("hidden")) {
    			$('#settings_delivery').removeClass("hidden");
    			$("#settings_delivery").addClass('show');
    		}
        }else{
        	if ($('#settings_delivery').hasClass("show")) {
    			$('#settings_delivery').removeClass("show");
    			$("#settings_delivery").addClass('hidden');
    		}
        }
    });

	$("#enable_order_status_automation").change(function(){
		if($(this).prop('checked') == true){
			$('#automatic_order_status_wrapper').show();
		}else{
			$('#automatic_order_status_wrapper').hide();
		}
	});

    $(".checkbox_delivery_options").change(function(){

        var $table = $(this).closest('.checkbox').next();
        if ($(this).prop('checked')) {
            if ($table.hasClass("hidden")) {
                $table.removeClass("hidden").addClass('show');
            }
        } else {
            if ($table.hasClass("show")) {
                $table.removeClass("show").addClass('hidden');
            }
        }
    });

    $("#checkbox_insured").change(function(){
        if($(this).prop('checked') == true){
            if ($('#div_checkbox_insured').hasClass("hidden")) {
                $('#div_checkbox_insured').removeClass("hidden");
                $("#div_checkbox_insured").addClass('show');
            }
        }else{
            if ($('#div_checkbox_insured').hasClass("show")) {
                $('#div_checkbox_insured').removeClass("show");
                $("#div_checkbox_insured").addClass('hidden');
            }
        }
    });

    $('#select_insured_amount').on('change', function()
    {
        if ($(this).val() == ''){
            if ($('#input_insured_amount_custom').hasClass("hidden")) {
                $('#input_insured_amount_custom').removeClass("hidden");
                $("#input_insured_amount_custom").addClass('show');
            }
        }else{
            if ($('#input_insured_amount_custom').hasClass("show")) {
                $('#input_insured_amount_custom').removeClass("show");
                $("#input_insured_amount_custom").addClass('hidden');
                $("input#insured_amount_custom.insured_amount").val("0");
            }
        }
    });

    $('#checkout_cut_off_weekday').on('change', function() {
        if($(this).prop('checked') === true) {
            $('#cut_off_time_all_wrapper').hide();
            $('#cut_off_time_weekdays_wrapper').show();
        } else {
            $('#cut_off_time_all_wrapper').show();
            $('#cut_off_time_weekdays_wrapper').hide();
        }
    });
});
