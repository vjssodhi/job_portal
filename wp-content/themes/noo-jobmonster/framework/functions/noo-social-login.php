<?php
/**
 * Social Login
 */

class Noo_Social_Login {

	public function __construct() {

		if( Noo_Member::get_setting('login_using_social', false) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'load_enqueue_script' ) );

			add_action( 'wp_ajax_check_login', array( $this, 'check_login' ) );
			add_action( 'wp_ajax_nopriv_check_login', array( $this, 'check_login' ) );

			add_action( 'wp_ajax_nopriv_create_user', array( $this, 'create_user' ) );

			$setting_linkedin = Noo_Job::get_setting('noo_job_linkedin','api_key', '');
			if( !empty( $setting_linkedin ) ) {
				add_action( 'wp_head', array( 'Noo_Job', 'load_linkedin_script' ) );
			}

		}

		if( is_admin() ) {
			add_action( 'noo_setting_member_fields', array( $this, 'setting_fields') );
		}
	}

	public function load_enqueue_script() {

		$setting_google = Noo_Member::get_setting('google_client_id', '');
		$setting_linkedin = Noo_Job::get_setting('noo_job_linkedin','api_key', '');

		if( !empty( $setting_google ) ) {
			wp_register_script( 'api-google', 'https://apis.google.com/js/api:client.js');
			wp_enqueue_script('api-google');
		}

		if( !SCRIPT_DEBUG ) {
			wp_register_script( 'login-social', NOO_ASSETS_URI . '/js/min/noo.login.social.min.js', array( 'jquery'), null, true );
		} else {
			wp_register_script( 'login-social', NOO_ASSETS_URI . '/js/noo.login.social.js', array( 'jquery'), null, true );
		}
		
		
		$noo_social = array(
			'ajax_url'              => admin_url( 'admin-ajax.php' ),
			'allow'                 => Noo_Member::get_setting('allow_register', 'both'),
			'using_register_email'  => Noo_Member::get_setting('register_using_email'),
			'google_client_id'      => Noo_Member::get_setting('google_client_id'),
			'google_client_secret'  => Noo_Member::get_setting('google_client_secret'),
			'facebook_api'          => Noo_Member::get_setting('id_facebook'),
			'msgLoginSuccessful'    => '<span class="success-response">' . __( 'Login successful, redirecting...','noo' ) . '</span>',
			'msgFacebookModalTitle' => __( 'Sign Up Via Facebook','noo' ),
			'msgGoogleModalTitle'   => __( 'Sign Up Via Google','noo' ),
			'msgLinkedInModalTitle' => __( 'Sign Up Via LinkedIn','noo' ),
			'msgHi'                 => __( 'Hi, ','noo' ),
			'msgServerError'        => '<span class="error-response">' . __( 'There\'s a problem when processing your data. Please try again or contact Administrator!','noo' ) . '</span>',
			'msgMissingEmail'       => '<span class="error-response">' . __( 'You need to provide your email!','noo' ) . '</span>',
			'msgMissingAppID'       => '<span class="error-response">' . __( 'There is a notification: it cannot take user`s information, please check your App ID installation!','noo' ) . '</span>',
		);
		wp_localize_script( 'login-social', 'nooSocial', $noo_social );

		wp_enqueue_script('login-social');

	}

	public function load_meta_login() {
		?>
		<script type="text/javascript" src="http://platform.linkedin.com/in.js">
			api_key: <?php echo Noo_Job::get_setting('noo_job_linkedin','api_key') ?> 
			authorize: true
		</script>
		<?php
	}

	public function check_login() {

		if( !isset($_POST['id'] ) || empty( $_POST['id'] ) ) {
			echo 'error';
			wp_die();
		}

		$user = get_user_by( 'email', $_POST['id'] );

		if ( $user ) :

			wp_set_current_user($user->ID, $user->user_login);
			wp_set_auth_cookie($user->ID);
			echo 'ok';
			wp_die();

		else :

			echo 'not user';
			wp_die();

		endif;

	}

	public static function get_setting($id = null ,$default = null){
		$noo_member_setting = get_option('noo_member');
		if(isset($noo_member_setting[$id]))
			return $noo_member_setting[$id];
		return $default;
	}

	public function create_user() {
		$user_email = $_POST['id'];

		$user_id = username_exists( $user_email );

		if ( !$user_id and email_exists($user_email) == false ) :

			$random_password = wp_generate_password( 12, false );

			$userdata = array(
				'user_login'    =>  $user_email,
				'user_email'    =>  $user_email,
				'display_name'  =>  $_POST['name'],
				'user_pass'     =>  $random_password,
				'role'			=>  $_POST['capabilities']
			);

			// -- check show/hide admin bar
				if( self::get_setting('hide_admin_bar', true) ) {
					$userdata['show_admin_bar_front'] = false;
				}

			$user_id = wp_insert_user( $userdata ) ;
			$user = get_user_by( 'id', $user_id ); 
			if( $user ) :
				// -- update user meta
					if ( isset($_POST['birthday'] ) ) update_user_meta( $user_id, 'birthday', $_POST['birthday'] );
					if ( isset($_POST['address'] ) ) update_user_meta( $user_id, 'address', $_POST['address'] );

				// -- Set autologin
					wp_set_current_user($user_id, $user->user_login);
					wp_set_auth_cookie($user_id);
					do_action( 'wp_login', $user->user_login );
					echo 'ok';
				
				wp_die();	
			
			endif;
		
		endif;

	}

	public static function get_user_by_meta_data( $meta_key, $meta_value, $count = false ) {

		$user_query = new WP_User_Query(
			array(
				'meta_key'	  =>	$meta_key,
				'meta_value'	=>	$meta_value
			)
		);

		$users = $user_query->get_results();
		if ( $count ) return count( $users );
		else return $users[0]->user_login;
	} // end get_user_by_meta_data

	public function setting_fields() {
		?>
				<!-- Using register by email -->
					<tr>
						<th>
							<?php _e('Enable Login Using Email','noo')?>
						</th>
						<td>
							<input type="checkbox" name="noo_member[register_using_email]" value="1" <?php checked( Noo_Member::get_setting('register_using_email', false) );?> />
							<small><?php _e('This option will replace username and used email for login/register.', 'noo'); ?></small>
						</td>
					</tr>
				<!-- / Using register by email -->

				<!-- Add custom login using social -->
					<script type="text/javascript">
						jQuery(document).ready(function($) {
							if ( $('#login_using_social').is(':checked') ) {
								$('.item_api').show();
							}else {
								$('.item_api').hide();
							}
							$('#login_using_social').change(function(event) {
								if ( $('#login_using_social').is(':checked') ) {
									$('.item_api').show();
								} else {
									$('.item_api').hide();
								}
							});
						});
					</script>
					<tr>
						<th>
							<?php _e('Enable Social Login','noo')?>
						</th>
						<td>
							<input id="login_using_social" type="checkbox" name="noo_member[login_using_social]" value="1" <?php checked( Noo_Member::get_setting('login_using_social', false) );?> />
						</td>
					</tr>
					<tr class="item_api">
						<th>
							<label for="facebook_api"><?php _e( 'Facebook App API', 'noo' ); ?></label>
						</th>
						<td>
							<input id="facebook_api" type="text" name="noo_member[id_facebook]" value="<?php echo Noo_Member::get_setting('id_facebook') ?>" placeholder="<?php _e( 'Application API', 'noo' ); ?>" size="50" />
							<p>
								<?php echo sprintf( __('<b>%s</b> requires that you create an application inside its framework to allow access from your website to their API.<br/> To know how to create this application, ', 'noo' ), 'Facebook' ); ?>
								<a href="javascript:void(0)" onClick="jQuery('#facebook-help').toggle();return false;"><?php _e('click here and follow the steps.', 'noo'); ?></a>
							</p>
							<div id="facebook-help" class="noo-setting-help" style="display: none; max-width: 1200px;" >
								<hr/>
								<br/>
								<?php _e('<em>Application ID</em> and <em>Secret</em> (also sometimes referred as <em>Consumer Key</em> and <em>Secret</em> or <em>Client ID</em> and <em>Secret</em>) are what we call an application credentials', 'noo') ?>. 
								<?php echo sprintf( __( 'This application will link your website <code>%s</code> to <code>%s API</code> and these credentials are needed in order for <b>%s</b> users to access your website', 'noo'), $_SERVER["SERVER_NAME"], 'Facebook', 'Facebook' ) ?>. 
								<br/>
								<br/>
								<?php echo sprintf( __('To register a new <b>%s API Application</b> and enable authentication, follow the steps', 'noo'), 'Facebook' ) ?>
								<br/>
								<?php $setupsteps = 0; ?>
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e( 'Go to', 'noo'); ?>&nbsp;<a href="https://developers.facebook.com/apps" target ="_blank">https://developers.facebook.com/apps</a></p>
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e("Select <b>Add a New App</b> from the <b>Apps</b> menu at the top", 'noo') ?>.</p> 
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e("Choose Website as your app platform", 'noo') ?>.</p> 
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e("Fill out Name and click <b>Create New Facebook App ID</b>", 'noo') ?>.</p> 
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e("Choose a Category for your app and click <b>Create App ID</b>", 'noo') ?>.</p> 
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e("Input your website URL. It should match with the current site", 'noo') ?> <em><?php echo get_option('siteurl'); ?></em></p> 
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e("Skip the rest of the setup and Go to the Developer Dashboard to see your Application", 'noo') ?>.</p>  
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e("Go to the App <b>Settings</b> and add an email to your app. It's required before publishing your app", 'noo') ?>.</p>  
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e("Go to the <b>Status & Review</b> and publish your application.", 'noo') ?>.</p>  
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e("Go back to the App <b>Dashboard</b> and copy the <em>Application ID</em> then paste to the setting above.", 'noo') ?>.</p>  
								<p>
									<b><?php _e("And that's it!", 'noo') ?></b> 
									<br />
									<?php echo __( 'For more reference, you can see: ', 'noo' ); ?><a href="https://developers.facebook.com/docs/apps/register", target="_blank"><?php _e('Facebook Document', 'noo'); ?></a>, <a href="https://www.google.com/search?q=Facebook API create application" target="_blank"><?php _e('Google', 'noo'); ?></a>, <a href="http://www.youtube.com/results?search_query=Facebook API create application " target="_blank"><?php _e('Youtube', 'noo'); ?></a>
								</p>
								<div style="margin-bottom:12px;" class="noo-thumb-wrapper">
									<a href="http://update.nootheme.com/wp-content/uploads/2015/09/facebook_api_1.png" target="_blank"><img src="http://update.nootheme.com/wp-content/uploads/2015/09/facebook_api_1.png"></a>
									<a href="http://update.nootheme.com/wp-content/uploads/2015/09/facebook_api_2.png" target="_blank"><img src="http://update.nootheme.com/wp-content/uploads/2015/09/facebook_api_2.png"></a>
									<a href="http://update.nootheme.com/wp-content/uploads/2015/09/facebook_api_3.png" target="_blank"><img src="http://update.nootheme.com/wp-content/uploads/2015/09/facebook_api_3.png"></a>
									<a href="http://update.nootheme.com/wp-content/uploads/2015/09/facebook_api_4.png" target="_blank"><img src="http://update.nootheme.com/wp-content/uploads/2015/09/facebook_api_4.png"></a>
									<a href="http://update.nootheme.com/wp-content/uploads/2015/09/facebook_api_5.png" target="_blank"><img src="http://update.nootheme.com/wp-content/uploads/2015/09/facebook_api_5.png"></a>
									<a href="http://update.nootheme.com/wp-content/uploads/2015/09/facebook_api_6.png" target="_blank"><img src="http://update.nootheme.com/wp-content/uploads/2015/09/facebook_api_6.png"></a>
									<a href="http://update.nootheme.com/wp-content/uploads/2015/09/facebook_api_7.png" target="_blank"><img src="http://update.nootheme.com/wp-content/uploads/2015/09/facebook_api_7.png"></a>
								</div> 
								<br/>
								<hr/>
							</div>
						</td>
					</tr>
					<tr class="item_api">
						<th>
							<label for="google_api"><?php _e( 'Google App API', 'noo' ); ?></label>
						</th>
						<td>
							<input id="google_api" type="text" name="noo_member[google_client_id]" value="<?php echo Noo_Member::get_setting('google_client_id') ?>" placeholder="<?php _e( 'Client ID', 'noo' ); ?>" size="60"/>
							<input type="text" name="noo_member[google_client_secret]" value="<?php echo Noo_Member::get_setting('google_client_secret') ?>" placeholder="<?php _e( 'Client Secret', 'noo' ); ?>" size="50" />
							<p><?php echo sprintf( __('<b>%s</b> requires that you create an application inside its framework to allow access from your website to their API.<br/> To know how to create this application, ', 'noo' ), 'Google' ); ?>
								<a href="javascript:void(0)" onClick="jQuery('#google-help').toggle();return false;"><?php _e('click here and follow the steps.', 'noo'); ?></a>
							</p>
							<div id="google-help" class="noo-setting-help" style="display: none; max-width: 1200px;" >
								<hr/>
								<br/>
								<?php _e('<em>Application ID</em> and <em>Secret</em> (also sometimes referred as <em>Consumer Key</em> and <em>Secret</em> or <em>Client ID</em> and <em>Secret</em>) are what we call an application credentials', 'noo') ?>. 
								<?php echo sprintf( __( 'This application will link your website <code>%s</code> to <code>%s API</code> and these credentials are needed in order for <b>%s</b> users to access your website', 'noo'), $_SERVER["SERVER_NAME"], 'Google', 'Google' ) ?>. 
								<br/>
								<br/>
								<?php echo sprintf( __('To register a new <b>%s API Application</b> and enable authentication, follow the steps', 'noo'), 'Google' ) ?>
								<br/>
								<?php $setupsteps = 0; ?>
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e( 'Go to', 'noo'); ?>&nbsp;<a href="https://console.developers.google.com/project" target ="_blank">https://console.developers.google.com/project</a></p>
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e('Select <b>Create Project</b> button', 'noo') ?>.</p> 
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e('Fill in <b>Project name</b> then click <b>Create</b> button', 'noo') ?>.</p> 
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e('In the sidebar, under <b>APIs & auth</b>, select <b>Credentials</b> then switch to <b>OAuth consent screen</b> tab', 'noo') ?>.</p> 
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e('Choose an Email Address and specify a <b>Product Name</b>', 'noo') ?>.</p> 
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e('Switch back to <b>Credentials</b> tab and add a new <b>OAuth 2.0 client ID</b>', 'noo') ?>
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e('In the <b>Application type</b> section, select <b>Web application</b> then input your site URL to the <b>Authorized JavaScript origins</b>. It should match with the current site', 'noo') ?> <em><?php echo get_option('siteurl'); ?></em></p> 
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e('In the resulting section, you should see the <em>Client ID</em> and <em>Client Secret</em>', 'noo') ?>.</p> 
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e('Go back to this setting page and paste the created Client ID and Client Secret into the settings above', 'noo') ?>.</p> 
								<p>
									<b><?php _e("And that's it!", 'noo') ?></b> 
									<br />
									<?php echo __( 'For more reference, you can see: ', 'noo' ); ?><a href="https://developers.google.com/identity/sign-in/web/devconsole-project", target="_blank"><?php _e('Google Document', 'noo'); ?></a>, <a href="https://www.google.com/search?q=Google API create application" target="_blank"><?php _e('Google', 'noo'); ?></a>, <a href="http://www.youtube.com/results?search_query=Google API create application " target="_blank"><?php _e('Youtube', 'noo'); ?></a>
								</p> 
								<div style="margin-bottom:12px;" class="noo-thumb-wrapper">
									<a href="http://update.nootheme.com/wp-content/uploads/2015/09/google_api_1.png" target="_blank"><img src="http://update.nootheme.com/wp-content/uploads/2015/09/google_api_1.png"></a>
									<a href="http://update.nootheme.com/wp-content/uploads/2015/09/google_api_2.png" target="_blank"><img src="http://update.nootheme.com/wp-content/uploads/2015/09/google_api_2.png"></a>
									<a href="http://update.nootheme.com/wp-content/uploads/2015/09/google_api_3.png" target="_blank"><img src="http://update.nootheme.com/wp-content/uploads/2015/09/google_api_3.png"></a>
									<a href="http://update.nootheme.com/wp-content/uploads/2015/09/google_api_4.png" target="_blank"><img src="http://update.nootheme.com/wp-content/uploads/2015/09/google_api_4.png"></a>
									<a href="http://update.nootheme.com/wp-content/uploads/2015/09/google_api_5.png" target="_blank"><img src="http://update.nootheme.com/wp-content/uploads/2015/09/google_api_5.png"></a>
								</div> 
								<br/>
								<hr/>
							</div>
						</td>
					</tr>
					<tr class="item_api">
						<th>
							<label for="linkedin_api"><?php _e( 'LinkedIn App API', 'noo' ); ?></label>
						</th>
						<td>
						<a href="<?php echo admin_url( 'edit.php?post_type=noo_job&page=manage_noo_job&tab=application#linkedin-app-api' ); ?>"><?php _e('Switch to Job Application setting', 'noo'); ?></a> <?php _e('to add your Linkedin Client ID (we use one LinkedIn application for both <b>Apply with LinkedIn</b> and <b>Login with LinkedIn</b>)', 'noo'); ?>
						</td>
					</tr>

				<!-- / Add custom login using social -->

		<?php
	}

}

new Noo_Social_Login();