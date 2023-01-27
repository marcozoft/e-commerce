    jQuery(document).ready(function ($) {
		$(".showcoupon").parent().hide();
		$('body').on('click', '.apply_coupon_copy_submit', function(e){
			e.preventDefault();
			var val = '';
			$( ".coupon_code_copy" ).each(function( index ) {
			  	if($(this).val() != ''){
			  		val = $(this).val();
			  	}
			});
			$('#coupon_code').val(val);
			//alert(val);
			$('.checkout_coupon').submit();
		});
    });


