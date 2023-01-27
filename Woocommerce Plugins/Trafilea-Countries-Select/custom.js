    jQuery(document).ready(function ($) {
        $('body').on('change', '#billing_country',function(e){
                    
            $("#shipping_country").val($("#billing_country").val());
                        
        });
    });



