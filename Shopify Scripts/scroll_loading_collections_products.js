				var aux = 20;
				var semaphore = true;

				$( window ).scroll(function() {
					var scrollTop = $(window).height() + $(window).scrollTop();
					if(semaphore && scrollTop > aux){
						console.log(scrollTop + ' - ' + aux);
						semaphore = false;
						$( ".basel-products-load-more" ).each(function( index ) {
							if(scrollTop > $(this).offset().top){
								//console.log(scrollTop + ' ++ ' + $(this).offset().top);
								$(this).click();
							}
						});						
						aux = aux + 20;
						semaphore = true;
					}
				});