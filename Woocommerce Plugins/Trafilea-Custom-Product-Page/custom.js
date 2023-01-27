    jQuery(document).ready(function ($) {
		$(".single_variation").css("visibility","hidden");
		$(".single_variation").hide();
		
		if($("#type").length > 0){
			$("body").on("change", "#type", function () {
				$(".price-wrapper").html($(".single_variation").html());
				$(".single_variation").css("visibility","hidden");
				$(".single_variation").hide();
			});
		} else {
			$("body").on("change", "#set", function () {
				$(".price-wrapper").html($(".single_variation").html());
				$(".single_variation").css("visibility","hidden");
				$(".single_variation").hide();
			});			
		}
		
         setTimeout(function(){	             
            $("#type").trigger("change");
		});
        	
           /*
           if($('.single_variation').html() != ''){
			 $(".price-wrapper").html($(".single_variation").html());
		   }
           $("#type").val($("#type option:first").val());
           */
    });


