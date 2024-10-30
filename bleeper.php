<?php
/*
  Plugin Name: Bleeper Live Chat
  Plugin URI: https://bleeper.io
  Description: Engage with customers with beautiful sales, support and marketing tools
  Version: 1.04
  Author: Bleeper
  Author URI: https://bleeper.io/contact-us
  Text Domain: bleeper
  Domain Path: /languages
 */


/**
 * 1.04 - 30 Aug 2019
 * Fixed undefined index errors in core
 * Fixed issues with account activation
 * Fixed issues with automatic API key retrieval and code set up
 *
 * 1.03 - 27 Mar 2019
 * Fixed TLS validation security issue and replaced CURL calls to use the WP HTTP API
 *
 * 1.02 - 23 Dec 2018
 * Fixed Blips showing up when the current blip was disabled in settings
 * 
 * 1.01
 * Added support for capturing Ninjaform submissions as Blips
 * 
 * 1.00
 * Launch!
 */


define( "BLEEPER_BLIP_LOCALE","bleeper" );
define( "BLEEPER_VERSION", "1.04");



add_action( 'user_register', 'bleeper_registration_save', 10, 1 );
function bleeper_registration_save( $user_id ) {
	$bleeper_blip_data = get_option('BLEEPER_BLIP');

	if ( !isset( $bleeper_blip_data['bleeper_blip_apikey'] ) || $bleeper_blip_data['bleeper_blip_apikey'] == '' ){
		/* No API key set, stopping */
		return;
	}
	if ( !isset( $bleeper_blip_data['bleeper_blip_track_users_blip_key'] ) || $bleeper_blip_data['bleeper_blip_track_users_blip_key'] == '' ){
		/* No Blip Key set, stopping */
		return;
	}



    if ( isset( $_POST['user_login'] ) ) {


        if ( !isset( $bleeper_blip_data['bleeper_blip_track_users_msg'] ) || $bleeper_blip_data['bleeper_blip_track_users_msg'] == '' ) { 
        	$m = '{displayname} registered on the site.';
        } else {
        	$m = $bleeper_blip_data['bleeper_blip_track_users_msg'];
        }

        $user = get_user_by( 'login', sanitize_text_field( $_POST['user_login'] ) );
        $display_name = $user->display_name;

        $m = str_replace("{displayname}", $display_name, $m);
        $m = str_replace("{username}", sanitize_text_field( $_POST['user_login'] ), $m);

		
		$test = array(
			"m" => $m,
			"i" => "https://bleeper.io/app/assets/images/blip_red.png",
			"t" => "reg",
			"d" => get_site_url(),
			"uip" => blip_get_client_ip(),
			"blip_key" => sanitize_text_field( $bleeper_blip_data['bleeper_blip_track_users_blip_key'] ),
			"api" => sanitize_text_field( $bleeper_blip_data['bleeper_blip_apikey'] )
		);
		

		bleeper_blip_send_custom_data( $test );


	}

}

add_action( 'admin_menu', 'bleeper_blip_admin_menu' );
function bleeper_blip_admin_menu() {
	
	$main_page = add_menu_page('Bleeper', __('Bleeper',BLEEPER_BLIP_LOCALE), 'manage_options', 'bleeper', 'bleeper_dashboard', plugins_url('/img/bleeper-icon-small.png', __FILE__));

    add_submenu_page('bleeper', __('Settings','sola'), __('Settings','sola'), 'manage_options' , 'bleeper-settings', 'bleeper_blip_menu_layout');

}





add_action( 'init', 'bleeper_version_control' );
/**
 * To be run once we detect a change in version
 * @return void
 */
function bleeper_version_control() {


    $current_version = get_option("bleeper_current_version");
    if (!isset($current_version) || $current_version != BLEEPER_VERSION) {

    	/* we've updated the plugin! */
    	update_option("bleeper_current_version",BLEEPER_VERSION);
    }
}

add_action( 'admin_init', 'bleeper_redirect_on_activate' );
add_action( "activated_plugin", "bleeper_redirect_on_activate" );

/**
 * Redirect the user to the welcome page on plugin activate
 * @param  string $plugin
 * @return void
 */
function bleeper_redirect_on_activate( $plugin ) {
	//delete_option("BLEEPER_FIRST_RUN");
	if( $plugin == plugin_basename( __FILE__ ) ) {
		if ( !get_option("BLEEPER_FIRST_RUN") ) {
	    	update_option( "BLEEPER_FIRST_RUN", true );
	    	// clean the output header and redirect the user
	    	@ob_flush();
			@ob_end_flush();
			@ob_end_clean();
	    	exit( wp_redirect( admin_url( 'admin.php?page=bleeper&action=welcome' ) ) );
	    }
	}
}

function bleeper_dashboard() {
	
	if ( !isset($_GET['action'] ) ) {
		/* check if the user actually has an API key. If not, send them to the welcome screen */
		$bleeper_blip_data = get_option('BLEEPER_BLIP');
		if (!$bleeper_blip_data || !isset( $bleeper_blip_data['bleeper_blip_apikey'] ) || $bleeper_blip_data['bleeper_blip_apikey'] == '' ) {
			echo "<script>window.location = '".admin_url('admin.php?page=bleeper&action=welcome')."'</script>";
			exit();
		}

		?>
		<div class='bleeper_div'>
			<div class='bleeper_inner_div'>
				<?php
					if ( isset ( $_COOKIE['bleeper_token']) ) {
						$cookie = $_COOKIE['bleeper_token'];
						if (!get_option("BLEEPER_FIRST_LOGIN_COMPLETED")) {
							update_option("BLEEPER_FIRST_LOGIN_COMPLETED", true);
							?>
							<iframe src="https://bleeper.io/app/?action=firsttime&tmp_access=<?php echo $cookie; ?>" class='bleeper_iframe'></iframe>


							<?php } else { 
							?>
							<iframe src="https://bleeper.io/app/?wp_email=<?php echo get_option('admin_email'); ?>" class='bleeper_iframe'></iframe>
						
						<?php }

						
					} else {
						?>
						<iframe src="https://bleeper.io/app/?wp_email=<?php echo get_option('admin_email'); ?>" class='bleeper_iframe'></iframe>
						<?php
					}

				?>
				
			</div>

		</div>
		<?php
	} else if ( $_GET['action'] === 'welcome' ) {
		delete_option("BLEEPER_FIRST_LOGIN_COMPLETED");
		?>
		<div class='bleeper_div'>
			
			<div class='bleeper_inner_div bleeper_center'>
				<br><br>
				<h1><?php _e("Welcome!",BLEEPER_BLIP_LOCALE); ?></h1>
				<!--<h2>to</h2>
				<img src='<?php echo plugins_url('/img/bleeper-300x60.png', __FILE__); ?>' style='width:200px;' /> -->
				<h3><?php _e("We're happy to have you on board!",BLEEPER_BLIP_LOCALE); ?></h3>
				
				<div class='bleeper_grid_inner bleeper_center'>
					<p style='font-style: italic;'>
						<?php echo sprintf(__("A free account (using %s) will be created on bleeper.io", BLEEPER_BLIP_LOCALE),get_option('admin_email')); ?>
						<br>
						<?php echo sprintf(__("If you already have an account, we will get things set up for you", BLEEPER_BLIP_LOCALE),get_option('admin_email')); ?>
					</p>

					<br>
					
					<a href='#' title='Activate Live Chat Now!' class='bleeper_activate_live_chat bleeper_primary_button'>
						<?php _e("Activate Live Chat Now!",BLEEPER_BLIP_LOCALE); ?>
					</a>
				</div>

				<p class='bleeper_error'>&nbsp;</p>

				<h2><?php _e("What you get with your Bleeper.io account",BLEEPER_BLIP_LOCALE); ?></h2>
				<div class='bleeper_welcome_div'>
					<div class='bleeper_grid'>
						<div class='bleeper_grid_4'>
							<div class='bleeper_grid_inner bleeper_center bleeper_material_card'>
								<img src='<?php echo plugins_url('img/livechat-icon.png', __FILE__); ?>' style='width:220px;' />
								<h2><?php _e("Live Chat",BLEEPER_BLIP_LOCALE); ?></h2>
								<p><?php _e("Chat directly with website visitors!",BLEEPER_BLIP_LOCALE); ?></p>
							</div>
						</div>
						<div class='bleeper_grid_4'>
							<div class='bleeper_grid_inner bleeper_center bleeper_material_card'>
								<img src='<?php echo plugins_url('img/supportdesk-icon.png', __FILE__); ?>' style='width:220px;' />
								<h2><?php _e("Support Desk",BLEEPER_BLIP_LOCALE); ?></h2>
								<p><?php _e("Engage with users on a more personal level with our real time support desk.",BLEEPER_BLIP_LOCALE); ?></p>
							</div>
						</div>
						<div class='bleeper_grid_4'>
							<div class='bleeper_grid_inner bleeper_center bleeper_material_card'>
								<img src='<?php echo plugins_url('img/blips-icon.png', __FILE__); ?>'' style='width:220px;' />
								<h2><?php _e("Blips",BLEEPER_BLIP_LOCALE); ?></h2>
								<p><?php _e("Increasing conversation rates by creating FOMO with powerful blips!",BLEEPER_BLIP_LOCALE); ?></p>
							</div>
						</div>
					</div>
				</div>
			</div>

		</div>
		<?php
	}
}

add_action( 'admin_init', 'bleeper_set_cookie' );
function bleeper_set_cookie() {
	if (isset( $_GET['action'] ) && $_GET['action'] === 'setapi' ) {
		
		if ( isset( $_GET['apikey'] ) && isset( $_GET['token'] ) ) {
	    	@ob_flush();
			@ob_end_flush();
			@ob_end_clean();
			@setcookie('bleeper_token', sanitize_text_field( $_GET['token'] ), time()+86400);
			$bleeper_blip_data = get_option('BLEEPER_BLIP');
			$bleeper_blip_data['bleeper_blip_apikey'] = sanitize_text_field( $_GET['apikey'] );
			update_option('BLEEPER_BLIP', $bleeper_blip_data);
			echo "<script>window.location = '".admin_url('admin.php?page=bleeper')."'</script>";
			exit();
		}
	}
}


add_action( 'admin_head', 'bleeper_favicon' );
function bleeper_favicon() {

	if ( isset( $_GET['page'] ) && $_GET['page'] === 'bleeper' ) {
  		printf( "<link rel=\"shortcut icon\" type=\"image/vnd.microsoft.icon\" href=\"%s\" />\n", plugins_url('/img/bleeper-icon-small.png', __FILE__));
	}
}



function bleeper_blip_menu_layout() {
	$bleeper_blip_data = get_option('BLEEPER_BLIP');
	
	if (!isset($bleeper_blip_data['bleeper_blip_apikey'])) { $bleeper_blip_data['bleeper_blip_apikey'] = ''; }
	if (!isset($bleeper_blip_data['bleeper_enabled'])) { $bleeper_blip_data['bleeper_enabled'] = '1'; }
	//if (!isset($bleeper_blip_data['bleeper_blip_reg_title'])) { $bleeper_blip_data['bleeper_blip_reg_title'] = ''; }
	//if (!isset($bleeper_blip_data['bleeper_blip_reg_message'])) { $bleeper_blip_data['bleeper_blip_reg_message'] = ''; }
	//if (!isset($bleeper_blip_data['bleeper_blip_reg_type'])) { $bleeper_blip_data['bleeper_blip_reg_type'] = ''; }

	if (!isset($bleeper_blip_data['bleeper_blip_track_users'])) { $bleeper_blip_data['bleeper_blip_track_users'] = '0'; }
	if (!isset($bleeper_blip_data['bleeper_blip_track_wooorders'])) { $bleeper_blip_data['bleeper_blip_track_wooorders'] = '0'; }
	if (!isset($bleeper_blip_data['bleeper_blip_track_ninjaforms'])) { $bleeper_blip_data['bleeper_blip_track_ninjaforms'] = '0'; }
	if (!isset($bleeper_blip_data['bleeper_blip_track_custom'])) { $bleeper_blip_data['bleeper_blip_track_custom'] = '0'; }

	if (!isset($bleeper_blip_data['bleeper_blip_track_users_blip_key'])) { $bleeper_blip_data['bleeper_blip_track_users_blip_key'] = ''; }
	if (!isset($bleeper_blip_data['bleeper_blip_track_wooorders_blip_key'])) { $bleeper_blip_data['bleeper_blip_track_wooorders_blip_key'] = ''; }
	if (!isset($bleeper_blip_data['bleeper_blip_track_ninjaforms_blip_key'])) { $bleeper_blip_data['bleeper_blip_track_ninjaforms_blip_key'] = ''; }
	if (!isset($bleeper_blip_data['bleeper_blip_track_custom_blip_key'])) { $bleeper_blip_data['bleeper_blip_track_custom_blip_key'] = ''; }


	


	if (!isset($bleeper_blip_data['bleeper_blip_track_users_msg']) || $bleeper_blip_data['bleeper_blip_track_users_msg'] == '') { 
		$bleeper_blip_data['bleeper_blip_track_users_msg'] = '{displayname} registered on the site.';
	}
	if (!isset($bleeper_blip_data['bleeper_blip_track_wooorders_msg']) || $bleeper_blip_data['bleeper_blip_track_wooorders_msg'] == '') { 
		$bleeper_blip_data['bleeper_blip_track_wooorders_msg'] = '{displayname} placed an order for {currency}{ordertotal}.';
	}
	if (!isset($bleeper_blip_data['bleeper_blip_track_ninjaforms_msg']) || $bleeper_blip_data['bleeper_blip_track_ninjaforms_msg'] == '') { 
		$bleeper_blip_data['bleeper_blip_track_ninjaforms_msg'] = 'Someone contacted us recently.';
	}

	$bleeper_enabled = '';
	$bleeper_disabled = '';
	if ( $bleeper_blip_data['bleeper_enabled'] == '1' ) {
		$bleeper_enabled = 'selected=selected';
	} else {
		$bleeper_disabled = 'selected=selected';
	}


?>
	<h1><?php _e("Bleeper",BLEEPER_BLIP_LOCALE); ?></h1>

	<form action='' method='POST' name='bleeper_blip_save_settings'>
		<div id="bleeper_tabs" class="ui-tabs-nav ui-tabs-vertical">
	      
			<ul>
		  		<li><a href="#main-settings"><?php _e("Main Settings",BLEEPER_BLIP_LOCALE); ?></a></li>
		  		<li><a href="#blips"><?php _e("Blips",BLEEPER_BLIP_LOCALE); ?></a></li>
		  
	  		</ul>



	
			<div id="main-settings">
				<h3><?php _e("Main Settings",BLEEPER_BLIP_LOCALE); ?></h3>
				<table class="form-table">

					<tbody>
						<tr>
							<th scope="row"><label for="bleeper_enabled"><?php _e("System",BLEEPER_BLIP_LOCALE); ?></label></th>
							<td><select name="bleeper_enabled" id="bleeper_enabled">
									<option value="1" <?php echo $bleeper_enabled; ?>><?php _e("Enabled",BLEEPER_BLIP_LOCALE); ?></option>
									<option value="0" <?php echo $bleeper_disabled; ?>><?php _e("Disabled",BLEEPER_BLIP_LOCALE); ?></option>
									
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="bleeper_blip_apikey"><?php _e("Bleeper API Key",BLEEPER_BLIP_LOCALE); ?></label></th>
							<td><input name="bleeper_blip_apikey" type="text" id="bleeper_blip_apikey" value="<?php echo $bleeper_blip_data['bleeper_blip_apikey']; ?>" class="regular-text" /> <a target='_BLANK' href='https://bleeper.io/app/?action=getapi' title='Get your API key'><?php _e("Find API Key",BLEEPER_BLIP_LOCALE); ?></a></td>
						</tr>
						
						

					</tbody>
				</table>
			</div>
			<div id="blips">
				<h3><?php _e("Blip Settings",BLEEPER_BLIP_LOCALE); ?></h3>
				<table class="form-table">

					<tbody>
						<tr>
							<th scope="row"><label for="bleeper_blip_track_table"><?php _e("Track",BLEEPER_BLIP_LOCALE); ?></label></th>
							<td>
								<table class='form-table wp-list-table widefat fixed striped comments'>
									<thead>
										<tr>
											<th><?php _e("Type",BLEEPER_BLIP_LOCALE); ?></th>
											<th style='width:10%'><?php _e("Action",BLEEPER_BLIP_LOCALE); ?></th>
											<th><?php _e("Blip Key",BLEEPER_BLIP_LOCALE); ?> <small><a target='_BLANK' href='https://bleeper.io/app/?action=blip-control' title='Get your Blip keys'><?php _e("(Get my Blip Keys)",BLEEPER_BLIP_LOCALE); ?></a></small></th>
											<th><?php _e("Options",BLEEPER_BLIP_LOCALE); ?></th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td><?php _e("User registrations",BLEEPER_BLIP_LOCALE); ?></td>
											<td><input type='checkbox' name='bleeper_blip_track_users' id='bleeper_blip_track_users' value='1' <?php echo ($bleeper_blip_data['bleeper_blip_track_users'] == '0') ? '' : 'checked="checked"'; ?>'> <?php _e("Enabled",BLEEPER_BLIP_LOCALE); ?></td>
											<td><input name="bleeper_blip_track_users_blip_key" type="text" id="bleeper_blip_track_users_blip_key" value="<?php echo $bleeper_blip_data['bleeper_blip_track_users_blip_key']; ?>" class="medium-text" /></td>
											<td><input name="bleeper_blip_track_users_msg" type="text" id="bleeper_blip_track_users_msg" value="<?php echo $bleeper_blip_data['bleeper_blip_track_users_msg']; ?>" class="medium-text" /></td>
										</tr>
										<tr>
											<td><?php _e("WooCommerce Orders",BLEEPER_BLIP_LOCALE); ?></td>
											<td><input type='checkbox' name='bleeper_blip_track_wooorders' id='bleeper_blip_track_wooorders'  value='1' <?php echo ($bleeper_blip_data['bleeper_blip_track_wooorders'] == '0') ? '' : 'checked="checked"'; ?>'> <?php _e("Enabled",BLEEPER_BLIP_LOCALE); ?></td>
											<td><input name="bleeper_blip_track_wooorders_blip_key" type="text" id="bleeper_blip_track_wooorders_blip_key" value="<?php echo $bleeper_blip_data['bleeper_blip_track_wooorders_blip_key']; ?>" class="medium-text" /></td>
											<td><input name="bleeper_blip_track_wooorders_msg" type="text" id="bleeper_blip_track_wooorders_msg" value="<?php echo $bleeper_blip_data['bleeper_blip_track_wooorders_msg']; ?>" class="medium-text" /></td>
										</tr>
										<tr>
											<td><?php _e("Ninja Form Submissions",BLEEPER_BLIP_LOCALE); ?></td>
											<td><input type='checkbox' name='bleeper_blip_track_ninjaforms' id='bleeper_blip_track_ninjaforms'  value='1' <?php echo ($bleeper_blip_data['bleeper_blip_track_ninjaforms'] == '0') ? '' : 'checked="checked"'; ?>'> <?php _e("Enabled",BLEEPER_BLIP_LOCALE); ?></td>
											<td><input name="bleeper_blip_track_ninjaforms_blip_key" type="text" id="bleeper_blip_track_ninjaforms_blip_key" value="<?php echo $bleeper_blip_data['bleeper_blip_track_ninjaforms_blip_key']; ?>" class="medium-text" /></td>
											<td>
												<input name="bleeper_blip_track_ninjaforms_msg" type="text" id="bleeper_blip_track_ninjaforms_msg" value="<?php echo $bleeper_blip_data['bleeper_blip_track_ninjaforms_msg']; ?>" class="medium-text" />
												[<a href='https://bleeper.io/docs/ninjaforms-integration/' target='_BLANK'><small>Documentation</small></a>]
											</td>
										</tr>
										<tr>
											<td><?php _e("Custom",BLEEPER_BLIP_LOCALE); ?></td>
											<td><input type='checkbox' name='bleeper_blip_track_custom' id='bleeper_blip_track_custom'  value='1' <?php echo ($bleeper_blip_data['bleeper_blip_track_custom'] == '0') ? '' : 'checked="checked"'; ?>'> <?php _e("Enabled",BLEEPER_BLIP_LOCALE); ?></td>
											<td><input name="bleeper_blip_track_custom_blip_key" type="text" id="bleeper_blip_track_custom_blip_key" value="<?php echo $bleeper_blip_data['bleeper_blip_track_custom_blip_key']; ?>" class="medium-text" /></td>
											<td>
												<small>Set via your own custom code</small></a>
											</td>
										</tr>


									</tbody>

								</table>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="bleeper_blip_track_table"><?php _e("Available Tags",BLEEPER_BLIP_LOCALE); ?></label></th>
							<td>
								<table class='form-table wp-list-table widefat fixed striped comments'>
						
									<tbody>
										<tr>
											<td><code>{displayname}</code></td>
											<td><em><?php _e("eg. John Smith",BLEEPER_BLIP_LOCALE); ?></em></td>
											<td><?php _e("User display name (WordPress display name field)",BLEEPER_BLIP_LOCALE); ?></td>
										</tr>
										<tr>
											<td><code>{username}</code></td>
											<td><em><?php _e("eg. johnsmith2",BLEEPER_BLIP_LOCALE); ?></em></td>
											<td><?php _e("User name (WordPress log in field)",BLEEPER_BLIP_LOCALE); ?></td>
										</tr>
										<tr>
											<td><code>{ordertotal}</code></td>
											<td><em><?php _e("eg. 43.99",BLEEPER_BLIP_LOCALE); ?></em></td>
											<td><?php _e("Order total excluding the currency symbol (WooCommerce field)",BLEEPER_BLIP_LOCALE); ?></td>
										</tr>
										<tr>
											<td><code>{currency}</code></td>
											<td><em><?php _e("eg. $",BLEEPER_BLIP_LOCALE); ?></em></td>
											<td><?php _e("Currency symbol (WooCommerce field)",BLEEPER_BLIP_LOCALE); ?></td>
										</tr>
										

									</tbody>

								</table>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="bleeper_blip_track_table"><?php _e("Ninjaform Tags",BLEEPER_BLIP_LOCALE); ?></label></th>
							<td>
								<table class='form-table wp-list-table widefat fixed striped comments'>
						
									<tbody>
										<tr>
											<td><code>{field_key}</code></td>
											<td><em><?php _e("eg. message",BLEEPER_BLIP_LOCALE); ?></em></td>
											<td><?php _e("Replace 'field_key' with the actual FIELD KEY (found in FIELD -> Administration) in order to use the value of that field",BLEEPER_BLIP_LOCALE); ?></td>
										</tr>
										
										

									</tbody>

								</table>
							</td>
						</tr>

					</tbody>
				</table>
			</div>

			<p class='submit'><input type='submit' name='bleeper_blip_save_settings' class='bleeper-save button-primary' value='<?php _e("Save Settings",BLEEPER_BLIP_LOCALE); ?>' &raquo;/></p>

		</div>
		
		
		
	
		
	</form>

	<?php
}


add_filter( 'ninja_forms_submit_data', 'bleeper_ninja_forms_submit_data' );
function bleeper_ninja_forms_submit_data( $form_data ) {
 
	$bleeper_blip_data = get_option('BLEEPER_BLIP');

	if ( !isset( $bleeper_blip_data['bleeper_blip_apikey'] ) || $bleeper_blip_data['bleeper_blip_apikey'] == '' ){
		/* No API key set, stopping */
		return;
	}
	if ( !isset( $bleeper_blip_data['bleeper_blip_track_ninjaforms_blip_key'] ) || $bleeper_blip_data['bleeper_blip_track_ninjaforms_blip_key'] == '' ){
		/* No Blip Key set, stopping */
		return;
	}


	if ( !isset( $bleeper_blip_data['bleeper_blip_track_ninjaforms_msg'] ) || $bleeper_blip_data['bleeper_blip_track_ninjaforms_msg'] == '' ) { 
		$m = 'Someone contacted us recently.';
	} else {
		$m = $bleeper_blip_data['bleeper_blip_track_ninjaforms_msg'];
	}
	


	preg_match_all('/{(.*?)}/', $m, $matches);
	
  	foreach( $form_data[ 'fields' ] as $field ) { 
   
    	$current_id = intval($field['id']);
    	foreach( $matches[1] as $match) {
    		if (intval($match) == $current_id) {
    			// match..!
    			
    			$m = str_replace("{".$match."}", $field['value'], $m);
    		}
    	}
  	  
    
    


  	}

	
	$test = array(
		"m" => $m,
		"i" => "https://bleeper.io/app/assets/images/blip_blue.png",
		"t" => "conv",
		"d" => get_site_url(),
		"uip" => blip_get_client_ip(),
		"api" => sanitize_text_field( $bleeper_blip_data['bleeper_blip_apikey'] ),
		"blip_key" => sanitize_text_field( $bleeper_blip_data['bleeper_blip_track_ninjaforms_blip_key'] )
	);

	
	bleeper_blip_send_custom_data( $test );
  
  	return $form_data;
}




add_action('admin_head', 'bleeper_blip_save_settings');
function bleeper_blip_save_settings() {
if (isset($_POST['bleeper_blip_save_settings'])){
    global $wpdb;
    $bleeper_blip_data['bleeper_blip_apikey'] = sanitize_text_field($_POST['bleeper_blip_apikey']);
    $bleeper_blip_data['bleeper_enabled'] = sanitize_text_field($_POST['bleeper_enabled']);
    //$bleeper_blip_data['bleeper_blip_reg_title'] = sanitize_text_field($_POST['bleeper_blip_reg_title']);
    //$bleeper_blip_data['bleeper_blip_reg_message'] = sanitize_text_field($_POST['bleeper_blip_reg_message']);
    //$bleeper_blip_data['bleeper_blip_reg_icon'] = sanitize_text_field($_POST['bleeper_blip_reg_icon']);
    //$bleeper_blip_data['bleeper_blip_reg_type'] = sanitize_text_field($_POST['bleeper_blip_reg_type']);
    
    /* on/off options */
    if (isset( $_POST['bleeper_blip_track_users'] ) ) { 
    	$bleeper_blip_data['bleeper_blip_track_users'] = sanitize_text_field($_POST['bleeper_blip_track_users']); 
    } else { 
    	$bleeper_blip_data['bleeper_blip_track_users'] = '0';
    }
    if ( isset( $_POST['bleeper_blip_track_wooorders'] ) ) {
    	$bleeper_blip_data['bleeper_blip_track_wooorders'] = sanitize_text_field($_POST['bleeper_blip_track_wooorders']);	
    } else {
    	$bleeper_blip_data['bleeper_blip_track_wooorders'] = '0';
    }
    if ( isset( $_POST['bleeper_blip_track_ninjaforms'] ) ) {
    	$bleeper_blip_data['bleeper_blip_track_ninjaforms'] = sanitize_text_field($_POST['bleeper_blip_track_ninjaforms']);	
    } else {
    	$bleeper_blip_data['bleeper_blip_track_ninjaforms'] = '0';
    }
    if ( isset( $_POST['bleeper_blip_track_custom'] ) ) {
    	$bleeper_blip_data['bleeper_blip_track_custom'] = sanitize_text_field($_POST['bleeper_blip_track_custom']);	
    } else {
    	$bleeper_blip_data['bleeper_blip_track_custom'] = '0';
    }
    
    /* msg settings */
    if (isset( $_POST['bleeper_blip_track_users_msg'] ) ) { 
    	$bleeper_blip_data['bleeper_blip_track_users_msg'] = sanitize_text_field($_POST['bleeper_blip_track_users_msg']); 
    } else { 
    	$bleeper_blip_data['bleeper_blip_track_users_msg'] = '{displayname} registered on the site.';
    }
    if ( isset( $_POST['bleeper_blip_track_wooorders_msg'] ) ) {
    	$bleeper_blip_data['bleeper_blip_track_wooorders_msg'] = sanitize_text_field($_POST['bleeper_blip_track_wooorders_msg']);	
    } else {
    	$bleeper_blip_data['bleeper_blip_track_wooorders_msg'] = '{displayname} placed an order for {currency}{ordertotal}.';
    }
    if ( isset( $_POST['bleeper_blip_track_ninjaforms_msg'] ) ) {
    	$bleeper_blip_data['bleeper_blip_track_ninjaforms_msg'] = sanitize_text_field($_POST['bleeper_blip_track_ninjaforms_msg']);	
    } else {
    	$bleeper_blip_data['bleeper_blip_track_ninjaforms_msg'] = 'Someone contacted us recently.';
    }



    /* blip keys */
    if (isset( $_POST['bleeper_blip_track_users_blip_key'] ) ) { 
    	$bleeper_blip_data['bleeper_blip_track_users_blip_key'] = sanitize_text_field($_POST['bleeper_blip_track_users_blip_key']); 
    } else { 
    	$bleeper_blip_data['bleeper_blip_track_users_blip_key'] = '';
    }
    if (isset( $_POST['bleeper_blip_track_wooorders_blip_key'] ) ) { 
    	$bleeper_blip_data['bleeper_blip_track_wooorders_blip_key'] = sanitize_text_field($_POST['bleeper_blip_track_wooorders_blip_key']); 
    } else { 
    	$bleeper_blip_data['bleeper_blip_track_wooorders_blip_key'] = '';
    }
    if (isset( $_POST['bleeper_blip_track_ninjaforms_blip_key'] ) ) { 
    	$bleeper_blip_data['bleeper_blip_track_ninjaforms_blip_key'] = sanitize_text_field($_POST['bleeper_blip_track_ninjaforms_blip_key']); 
    } else { 
    	$bleeper_blip_data['bleeper_blip_track_ninjaforms_blip_key'] = '';
    }
    if (isset( $_POST['bleeper_blip_track_custom_blip_key'] ) ) { 
    	$bleeper_blip_data['bleeper_blip_track_custom_blip_key'] = sanitize_text_field($_POST['bleeper_blip_track_custom_blip_key']); 
    } else { 
    	$bleeper_blip_data['bleeper_blip_track_custom_blip_key'] = '';
    }

    update_option('BLEEPER_BLIP', $bleeper_blip_data);
    
    echo "<div class='updated below-h1'>".__("Your settings have been saved.",BLEEPER_BLIP_LOCALE)."</div>";
    


    }
}


add_action("woocommerce_thankyou", "bleeper_blip_woocommerce_hook", 10, 1);
function bleeper_blip_woocommerce_hook( $order_id ) {
	if ( ! $order_id )
        return;

    // Getting an instance of the order object
    $order = new WC_Order( $order_id );
    
    $user_id = $order->get_user_id();

	$total = $order->get_total();
    $currency_code = $order->get_currency();
	$currency_symbol = html_entity_decode( get_woocommerce_currency_symbol( $currency_code ) );

	$user = get_userdata( $user_id );
	$user_login = $user->user_login;
	$display_name = $user->display_name;


	$bleeper_blip_data = get_option('BLEEPER_BLIP');

	if ( !isset( $bleeper_blip_data['bleeper_blip_apikey'] ) || $bleeper_blip_data['bleeper_blip_apikey'] == '' ){
		/* No API key set, stopping */
		return;
	}
	if ( !isset( $bleeper_blip_data['bleeper_blip_track_wooorders_blip_key'] ) || $bleeper_blip_data['bleeper_blip_track_wooorders_blip_key'] == '' ){
		/* No Blip Key set, stopping */
		return;
	}


    if ( !isset( $bleeper_blip_data['bleeper_blip_track_wooorders_msg'] ) || $bleeper_blip_data['bleeper_blip_track_wooorders_msg'] == '' ) { 
    	$m = '{displayname} placed an order for {currency}{ordertotal}';
    } else {
    	$m = $bleeper_blip_data['bleeper_blip_track_wooorders_msg'];
    }

    
    $m = str_replace("{displayname}", $display_name, $m);
    $m = str_replace("{username}", $user_login, $m);
    $m = str_replace("{ordertotal}", $total, $m);
    $m = str_replace("{currency}", $currency_symbol, $m);


	
	$test = array(
		"m" => $m,
		"i" => "https://bleeper.io/app/assets/images/blip_blue.png",
		"t" => "conv",
		"d" => get_site_url(),
		"uip" => blip_get_client_ip(),
		"api" => sanitize_text_field( $bleeper_blip_data['bleeper_blip_apikey'] ),
		"blip_key" => sanitize_text_field( $bleeper_blip_data['bleeper_blip_track_wooorders_blip_key'] )
	);

	
	bleeper_blip_send_custom_data( $test );



}

function blip_get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = '';
    return $ipaddress;
}

add_action( 'bleeper_blip_send_data', 'bleeper_blip_send_custom_data' );

function bleeper_blip_send_custom_data( $data ) {

	if ( !isset($data['api'] ) || $data['api'] == '' ) { return json_encode(array('error'=>'No API Key set')); } else { $api_key = esc_attr( $data['api'] ); }

	if ( !isset($data['blip_key'] ) || $data['blip_key'] == '' ) { return json_encode(array('error'=>'No BLIPKEY Key set')); } else { $blip_key = esc_attr( $data['blip_key'] ); }




	if ( !isset($data['h'] ) ) { $h = null; } else { $h = $data['h']; }
	if ( !isset($data['m'] ) ) { return json_encode(array('error'=>'No message')); } else { $m = $data['m']; }
	if ( !isset($data['i'] ) ) { $i = 'https://bleeper.io/app/assets/images/blip_red.png'; } else { $i = $data['i']; }
	if ( !isset($data['t'] ) ) { $t = 'reg'; } else { $t = $data['t']; }
	if ( !isset($data['d'] ) ) { $d = get_site_url(); } else { $d = $data['d']; }


	
	$data = json_encode($data);

	
	$data = urlencode($data);
	

	$bleeper_uri = "https://nifty.us-2.evennode.com/api/v1/send-blip/?api_key=".$api_key."&blip_key=".$blip_key."&blip=".$data;
    $response = wp_remote_get( $bleeper_uri );
    $response = wp_remote_retrieve_body( wp_remote_get( $bleeper_uri) );

    if ( is_wp_error( $response ) || $response === false ) {
    	echo 'Remote fetch error';
    }

}

add_action( 'wp_enqueue_scripts', 'bleeper_front_end_scripts' );
function bleeper_front_end_scripts() {
	$bleeper_blip_data = get_option('BLEEPER_BLIP');

	if ( !isset( $bleeper_blip_data['bleeper_blip_apikey'] ) || $bleeper_blip_data['bleeper_blip_apikey'] == '' ){
		/* No API key set, stopping */
		return;
	}

	if ( isset( $bleeper_blip_data['bleeper_enabled'] ) && $bleeper_blip_data['bleeper_enabled'] == '0' ) {
		/* system disabled */
		return;
	}

	$key_enabled = false;
	$key_array = array();
	if (!empty($bleeper_blip_data['bleeper_blip_track_users']) && $bleeper_blip_data['bleeper_blip_track_users'] == '1' && !empty($bleeper_blip_data['bleeper_blip_track_users_blip_key'])) {
		$key_enabled = true;
		array_push($key_array, $bleeper_blip_data['bleeper_blip_track_users_blip_key']);
	} 
	if (!empty($bleeper_blip_data['bleeper_blip_track_wooorders']) && $bleeper_blip_data['bleeper_blip_track_wooorders'] == '1' && !empty($bleeper_blip_data['bleeper_blip_track_wooorders_blip_key'])) {
		$key_enabled = true;
		array_push($key_array, $bleeper_blip_data['bleeper_blip_track_wooorders_blip_key']);
	} 
	if (!empty($bleeper_blip_data['bleeper_blip_track_ninjaforms']) && $bleeper_blip_data['bleeper_blip_track_ninjaforms'] == '1' && !empty($bleeper_blip_data['bleeper_blip_track_ninjaforms_blip_key'])) {
		$key_enabled = true;
		array_push($key_array, $bleeper_blip_data['bleeper_blip_track_ninjaforms_blip_key']);
	} 
	if (!empty($bleeper_blip_data['bleeper_blip_track_custom']) && $bleeper_blip_data['bleeper_blip_track_custom'] == '1' && !empty($bleeper_blip_data['bleeper_blip_track_custom_blip_key'])) {
		$key_enabled = true;
		array_push($key_array, $bleeper_blip_data['bleeper_blip_track_custom_blip_key']);
	} 

	

	wp_enqueue_script( 
		'bleeper', 
		'https://bleeper.io/app/api/v1.1/load-chatbox/'.esc_attr( $bleeper_blip_data['bleeper_blip_apikey'] ).'/', 
		array(), 
		null, 
		true
	);

	if ($key_enabled && count($key_array) > 0) {
		wp_localize_script( 'bleeper', 'blipids', $key_array );
	} else {
		wp_localize_script( 'bleeper', 'blipids', 'x' );
	}
}


add_action('admin_enqueue_scripts', 'bleeper_add_admin_stylesheet');
function bleeper_add_admin_stylesheet() {
    if( isset( $_GET['page'] ) && $_GET['page'] === 'bleeper' ) {
        
            wp_register_style( 'bleeper-admin-style', plugins_url('/css/bleeper-admin.css', __FILE__), array(), BLEEPER_VERSION );
            wp_enqueue_style( 'bleeper-admin-style' );

        	wp_register_script( 'bleeper-script', plugins_url('/js/bleeper.js', __FILE__), array('jquery'), BLEEPER_VERSION );
		 	wp_enqueue_script( 'bleeper-script' );

		 	$current_user = wp_get_current_user();
		 	wp_localize_script( 'bleeper-script', 'bleeper_blog_name', esc_attr( get_option('blogname' ) ) );
		 	wp_localize_script( 'bleeper-script', 'bleeper_blog_nickname', esc_attr( $current_user->display_name ) );

		 	wp_localize_script( 'bleeper-script', 'bleeper_blog_url', esc_attr( get_site_url() ) );
		 	wp_localize_script( 'bleeper-script', 'bleeper_admin_url', esc_attr( admin_url() ) );
		 	wp_localize_script( 'bleeper-script', 'bleeper_admin_e', esc_attr( get_option('admin_email' ) ) ) ;

    } else if ( isset( $_GET['page'] ) && $_GET['page'] === 'bleeper-settings' ) {
	        wp_enqueue_script('jquery-ui-core');
	        wp_enqueue_script( 'jquery-ui-tabs');

        	wp_register_script( 'bleeper-tabs', plugins_url('/js/tabs.js', __FILE__), array('jquery'), BLEEPER_VERSION );
		 	wp_enqueue_script( 'bleeper-tabs' );

	            wp_register_style( 'bleeper-tabs-jquery-ui-theme', plugins_url('/css/jquery-ui.theme.css', __FILE__) );
            //wp_enqueue_style( 'bleeper-tabs-jquery-ui-theme' );
            wp_register_style( 'bleeper-tabs-jquery-ui-css', plugins_url('/css/jquery-ui.css', __FILE__) );
            //wp_enqueue_style( 'bleeper-tabs-jquery-ui-css' );

            
            wp_register_style( 'bleeper-admin-style', plugins_url('/css/bleeper-admin.css', __FILE__), array(), BLEEPER_VERSION );
            wp_enqueue_style( 'bleeper-admin-style' );
            

    }
}
