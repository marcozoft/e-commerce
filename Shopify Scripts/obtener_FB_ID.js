  
  window.fbAsyncInit = function() {
    FB.init({
      appId            : '388305051538063',
      autoLogAppEvents : true,
      xfbml            : true,
      version          : 'v3.0'
    });
FB.getLoginStatus(function(response) {
	//console.log(JSON.stringify(response));
  if (response.status === 'connected') {
    console.log(response.authResponse.userID);
  }
});	    
  };

  (function(d, s, id){
     var js, fjs = d.getElementsByTagName(s)[0];
     if (d.getElementById(id)) {return;}
     js = d.createElement(s); js.id = id;
     js.src = "https://connect.facebook.net/en_US/sdk.js";
     fjs.parentNode.insertBefore(js, fjs);
   }(document, 'script', 'facebook-jssdk'));

