(function ($) {
    $(document).ready(function () {
        console.info('cccccccc');
//        if ($('#billing_country').val()) {
//            
//            callAjax('billing', $('#billing_country').val());
//        }
//        if ($('#shipping_country').val()) {
//            callAjax('shipping', $('#shipping_country').val());
//        }


        $('#billing_country').change(function () {
            var country_id = $(this).val();
            callAjax('billing', country_id);
        });
        $('#shipping_country').change(function () {
            var country_id = $(this).val();
            callAjax('shipping', country_id);
        });


        $('#shipping_city').change(function () {
            $('body').trigger('update_checkout');
        });
        $('#billing_city').change(function () {
            $('body').trigger('update_checkout');
        });


        function callAjax(addr, country) {
            var selector = '#' + addr + '_city';
            $.ajax({
                cache: false,
                type: "POST",
                url: ajax_object.ajax_url,
                data: {
                    action: 'get_cities',
                    country_code: country,
                },
                success: function (citydata)
                {
//                    console.info(citydata);
                    var option = '';
                    var count = 0;
                    var cities = $.parseJSON(citydata);

                    if (Array.isArray(cities)) {
                        $(cities).map(function ()
                        {
                            //this refers to the current item being iterated over
                            option += '<option value="' + this + '"> ' + this + '</option>';
                            count++;

                        });
                    }

                    $(selector).html('');
                    $('#mfSpanError').html('');

                    if (count > 1) {
                        $(selector).replaceWith("<select id='" + addr + "_city' name='" + addr + "_city' />");
                        $(selector).html(option);

                        $(selector).change(function () {
                            $('body').trigger('update_checkout');
                        });
                        

//                        jQuery('label[for=' + addr + '_city]').show();
//                        jQuery(selector).show();
                    } else {

                        $(selector).replaceWith("<input id='" + addr + "_city' class='input-text' type='text' value='' />");

                        if (typeof cities === 'string') {
                            $(selector).after('<span id="mfSpanError" style="color: #a00;">' + cities + '</span>');
                        }

//                        jQuery('label[for=' + addr + '_city]').hide();
//                        jQuery(selector).hide();

                    }
                }
            });
        }

    });
})(jQuery);
