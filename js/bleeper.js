var bleeper_original_button_text;
document.addEventListener("DOMContentLoaded", function(event) { 


  	var bleeper_live_chat_reg = document.querySelector('.bleeper_activate_live_chat');
  	if (bleeper_live_chat_reg) {
		bleeper_live_chat_reg.addEventListener('click', function() { 
			var orig_elem = this;
			bleeper_original_button_text = this.innerHTML;
			this.disabled = true;
			this.innerHTML = 'please wait...';

			if (typeof bleeper_blog_name !== 'undefined' && 
				typeof bleeper_admin_e !== 'undefined' && 
				typeof bleeper_blog_url !== 'undefined' && 
				typeof bleeper_blog_nickname !== 'undefined' && 
				bleeper_admin_e !== '') {
				if (bleeper_blog_name === '') bleeper_blog_name = 'WordPress Blog';


				var xhr = new XMLHttpRequest();
				
				xhr.onreadystatechange = function() {
				    if (xhr.readyState == XMLHttpRequest.DONE) {
				        try {
				        	var jsono = JSON.parse(xhr.responseText);
				        	
				        	if (typeof jsono[0] !== 'undefined' && jsono[0] === 'error') {
				        		var bleeper_error_elem = document.querySelector('.bleeper_error');
				        		if (jsono[1] === 'e100') {
				        			/* address already registered */
				        			bleeper_error_elem.innerHTML = "Email address already registered.<br />Please log in to <a href='https://bleeper.io/app/?action=getapi' target='_BLANK'>bleeper.io</a> and then insert your API key <a href='admin.php?page=bleeper-settings'>here</a>.";
				        		} else {
				        			bleeper_error_elem.innerHTML = jsono[1];

				        			

				        		}
				        	} 

				        	if (typeof jsono.apikey !== 'undefined' && typeof jsono.token !== 'undefined') {
				        		/* we have received an API key */
				        		console.log(jsono);
				        		var bleeper_api_key = jsono.apikey;
				        		var bleeper_token = jsono.token;

				        		window.location = bleeper_admin_url+'/admin.php?page=bleeper&action=setapi&apikey='+bleeper_api_key+"&token="+bleeper_token;


				        	}
				        } catch (e) {
				        	console.log(e);
				        	alert('Something went wrong..');
				        }
				        
				        orig_elem.disabled = false;
				        orig_elem.innerHTML = bleeper_original_button_text;
				    }
				}
				xhr.onload = function() {
				    if (xhr.status === 200) {
				        var userInfo = JSON.parse(xhr.responseText);
				    }
				};

				xhr.open('POST', 'https://api.bleeper.io/app/api/api.php');
				xhr.setRequestHeader('Content-Type', "application/x-www-form-urlencoded");

				xhr.send("action=create_wordpress_account"+
					"&nifty_email="+bleeper_admin_e+
				    "&domain="+bleeper_blog_url+
				    "&nifty_company_name="+bleeper_blog_name+
				    "&nifty_nickname="+bleeper_blog_nickname
				);
			}

		}, false);
	}
});


