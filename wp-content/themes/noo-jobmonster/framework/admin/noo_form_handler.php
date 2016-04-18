<?php
if( !class_exists('Noo_Form_Handler') ) :
class Noo_Form_Handler {
	public static $no_html = array();
	public static $allowed_html = array(
				'a' => array(
					'href' => array(),
					'target' => array(),
					'title' => array(),
					'rel' => array(),
				),
				'img' => array(
					'src' => array()
				),
				'h1' => array(),
				'h2' => array(),
				'h3' => array(),
				'h4' => array(),
				'h5' => array(),
				'p' => array(),
				'br' => array(),
				'hr' => array(),
				'span' => array(),
				'em' => array(),
				'strong' => array(),
				'small' => array(),
				'b' => array(),
				'i' => array(),
				'u' => array(),
				'ul' => array(),
				'ol' => array(),
				'li' => array(),
				'blockquote' => array(),
			);

	public static function init(){
		add_action( 'init', array( __CLASS__, 'lost_password_action' ) );
		add_action( 'init', array( __CLASS__, 'edit_company_action' ) );
		add_action( 'init', array( __CLASS__, 'edit_candidate_profile_action' ) );
		add_action( 'init', array( __CLASS__, 'edit_job_action' ) );
		add_action( 'init', array( __CLASS__, 'edit_resume_action' ) );
		add_action( 'init', array( __CLASS__, 'edit_job_alert_action' ) );
		add_action( 'init', array( __CLASS__, 'delete_job_alert_action' ) );
		add_action( 'init', array( __CLASS__, 'manage_job_applied_action' ) );
		
		// page step post job action
		// add_action( 'init', array( __CLASS__, 'post_job_action' ) );
		// add_action( 'init', array( __CLASS__, 'preview_job_action' ) );
		
		add_action('wp_ajax_add_new_job_location', array(__CLASS__,'add_new_job_location_action'));
		
		add_action( 'init', array( __CLASS__, 'manage_job_action' ) );
		add_action( 'init', array( __CLASS__, 'manage_application_action' ) );
		
		add_action( 'init', array( __CLASS__, 'apply_job_action' ) );
		add_action( 'init', array( __CLASS__, 'apply_job_via_linkedin_action' ) );
		add_action( 'init', array( __CLASS__, 'manage_resume_action' ) );
		add_action( 'init', array( __CLASS__, 'delete_bookmark_action' ) );
		
		add_action( 'wp_ajax_noo_approve_reject_application_modal', array(__CLASS__, 'approve_reject_application_modal') );
		//add_action( 'wp_ajax_noo_approve_reject_application_action', array(__CLASS__, 'approve_reject_application_action') );
		add_action( 'wp_ajax_noo_employer_message_application_modal', array(__CLASS__, 'employer_message_application_modal') );
		
		add_action( 'wp_ajax_nopriv_noo_ajax_login', array(__CLASS__, 'ajax_login') );
		add_action( 'wp_ajax_noo_ajax_login', array(__CLASS__, 'ajax_login_priv') );
		add_action( 'wp_ajax_nopriv_noo_ajax_register', array(__CLASS__, 'ajax_register') );
		add_action( 'wp_ajax_noo_ajax_register', array(__CLASS__, 'ajax_register') );
		
		
		add_action( 'wp_ajax_nopriv_noo_bookmark_job', array(__CLASS__, 'ajax_bookmark_job') );
		add_action( 'wp_ajax_noo_bookmark_job', array(__CLASS__, 'ajax_bookmark_job') );
		add_action( 'wp_ajax_nopriv_noo_update_password', array(__CLASS__, 'ajax_update_password') );
		add_action( 'wp_ajax_noo_update_password', array(__CLASS__, 'ajax_update_password') );
	}
	
	public static function add_new_job_location_action(){
		if(!is_user_logged_in())
			$result['success'] = false;
		
		if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) )
			$result['success'] = false;
		
		if(!is_user_logged_in()){
			$result['success'] = false;
		}
		
		check_ajax_referer('noo-member-security','security');
		$new_location = isset($_POST['location']) ?  trim(stripslashes($_POST['location'])) : '';
		if(!empty($new_location)){
			$result = array();
			if ( ($t = get_term_by( 'slug', sanitize_title( $new_location ), 'job_location' ) )) {
				$result['success'] = true;
				$result['location_value'] = $t->slug;
				$result['location_title'] = $t->name;
			}else{
				$n_l = wp_insert_term( $new_location, 'job_location' );
				if ($n_l && !is_wp_error($n_l) && ($loca = get_term( absint($n_l['term_id']), 'job_location' ))) {
					$result['success'] = true;
					$result['location_value'] = $loca->slug;
					$result['location_title'] = $loca->name;
				}
			}
		}
		wp_send_json($result);
	}
	

	public static function lost_password_action(){
		global $wpdb, $wp_hasher;
	
		if(is_user_logged_in()) {
			return;
		}
	
		if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) )
			return;
	
		if ( empty( $_POST[ 'action' ] ) || 'lost_password' !== $_POST[ 'action' ] || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'lost-password' ) )
			return;
	
		if ( empty( $_POST['user_login'] ) ) {
			noo_message_add( __( 'Enter a username or e-mail address.', 'noo' ), 'error' );
			return false;
	
		} else {
			// Check on username first, as customers can use emails as usernames.
			$login = trim( $_POST['user_login'] );
			$user_data = get_user_by( 'login', $login );
		}
	
		// If no user found, check if it login is email and lookup user based on email.
		if ( ! $user_data && is_email( $_POST['user_login'] ) ) {
			$user_data = get_user_by( 'email', trim( $_POST['user_login'] ) );
		}
	
		do_action( 'lostpassword_post' );
	
		if ( ! $user_data ) {
			noo_message_add( __( 'Invalid username or e-mail.', 'noo' ), 'error' );
			return false;
		}
	
		if ( is_multisite() && ! is_user_member_of_blog( $user_data->ID, get_current_blog_id() ) ) {
			noo_message_add( __( 'Invalid username or e-mail.', 'noo' ), 'error' );
			return false;
		}
	
		// redefining user_login ensures we return the right case in the email
		$user_login = $user_data->user_login;
		$user_email = $user_data->user_email;
	
		do_action( 'retrieve_password', $user_login );
	
		$allow = apply_filters( 'allow_password_reset', true, $user_data->ID );
	
		if ( ! $allow ) {
	
			noo_message_add( __( 'Password reset is not allowed for this user', 'noo' ), 'error' );
	
			return false;
	
		} elseif ( is_wp_error( $allow ) ) {
	
			noo_message_add( $allow->get_error_message(), 'error' );
	
			return false;
		}
	
		$key = wp_generate_password( 20, false );
	
		do_action( 'retrieve_password_key', $user_login, $key );
	
		// Now insert the key, hashed, into the DB.
		if ( empty( $wp_hasher ) ) {
			require_once ABSPATH . 'wp-includes/class-phpass.php';
			$wp_hasher = new PasswordHash( 8, true );
		}
	
		$hashed = time() . ':' . $wp_hasher->HashPassword( $key );
	
		$wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user_login ) );
	
		// Send email notification
		$message = __('Someone requested that the password be reset for the following account:', 'noo') . '<br/><br/>';
		$message .= sprintf(__('Username: %s', 'noo'), $user_login) . '<br/><br/>';
		$message .= __('If this was a mistake, just ignore this email and nothing will happen.', 'noo') . '<br/><br/>';
		$message .= __('To reset your password, visit the following address:', 'noo') . "<br/><br/>";
		$reset_link = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login');
		$message .= '<a href="' .$reset_link . '" >'.$reset_link.'</a><br/>';
	
		if ( is_multisite() )
			$blogname = $GLOBALS['current_site']->site_name;
		else
		/*
		 * The blogname option is escaped with esc_html on the way into the database
		 * in sanitize_option we want to reverse this for the plain text arena of emails.
		 */
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
	
		$title = sprintf( __('[%s] Password Reset', 'noo'), $blogname );
	
		/**
		 * Filter the subject of the password reset email.
		 *
		 * @since 2.8.0
		 *
		 * @param string $title Default email title.
		*/
		$title = apply_filters( 'retrieve_password_title', $title );
		/**
		 * Filter the message body of the password reset mail.
		 *
		 * @since 2.8.0
		 *
		 * @param string $message Default mail message.
		 * @param string $key     The activation key.
		*/
		$message = apply_filters( 'retrieve_password_message', $message, $key );
	
		if ( $message && !noo_mail( $user_email, wp_specialchars_decode( $title ), $message, '', 'noo_user_password_reset' ) ) {
			noo_message_add( __( 'The e-mail could not be sent', 'noo' ), 'error' );
		} else {
			noo_message_add( __( 'Check your e-mail for the confirmation link.', 'noo' ) );
		}
		return true;
	}
	
	public static function apply_job_via_linkedin_action(){
		if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) )
			return;

		if ( empty( $_POST[ 'action' ] ) || 'apply_job_via_linkedin' !== $_POST[ 'action' ] || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'noo-apply-job-via-linkedin' ) ) {
			return;
		}
		
		try{
			$cover_letter = isset( $_POST['linkedin-cover-letter'] ) ? wp_kses_post( stripslashes( $_POST['linkedin-cover-letter'] ) ) : '';
			$profile_data = isset($_POST['in-profile-data']) ? json_decode( stripslashes( $_POST['in-profile-data'] ) ) : '';
			
			$job_id              = absint( $_POST['job_id'] );
			$job                 = get_post( $job_id );
			
			if ( empty( $job_id ) || ! $job || 'noo_job' !== $job->post_type ) {
				throw new Exception( __( 'Invalid job', 'noo' ) );
			}

			$meta = array();
			if( isset( $_POST['_attachment'] ) && !empty( $_POST['_attachment'] ) ) {
				$meta['_attachment'] = esc_url($_POST['_attachment']);
			}
			$application_id = Noo_Application::new_job_application(
				$job_id,
				$profile_data->formattedName,
				$profile_data->emailAddress,
				$cover_letter,
				$meta
			);
			do_action( 'new_job_application', $application_id );
			do_action( 'new_job_apply_via_linkedin', $job_id, $profile_data, $cover_letter );
			if ( ! $application_id ) {
				noo_message_add( __( 'There\'s an unknown error. Please retry or contact Administrator.', 'noo' ),'error');
				wp_safe_redirect(get_permalink($job_id));
				exit();
			}
			noo_message_add(__('Your job application has been submitted successfully','noo'));
			wp_safe_redirect(get_permalink($job_id));
			exit();
		}catch (Exception $e){
			noo_message_add($e->getMessage(),'error');
		}
	}
	
	public static function apply_job_action(){
		if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) )
			return;
		
		if ( empty( $_POST[ 'action' ] ) || 'apply_job' !== $_POST[ 'action' ] || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'noo-apply-job' ) ) {
			return;
		}
		
		try {
			// Get data from the form
			$candidate_name      = sanitize_text_field( $_POST['candidate_name'] );
			$candidate_email     = sanitize_text_field( $_POST['candidate_email'] );
			$application_message = str_replace( '[nl]', "\n", sanitize_text_field( str_replace( "\n", '[nl]', strip_tags( stripslashes( $_POST['application_message'] ) ) ) ) );
			$job_id              = absint( $_POST['job_id'] );
			$job                 = get_post( $job_id );
			$meta 				 = array();
			$meta['_attachment'] = '';
			if ( empty( $job_id ) || ! $job || 'noo_job' !== $job->post_type ) {
				noo_message_add( __( 'Invalid job', 'noo' ),'error');
				wp_safe_redirect(get_permalink($job_id));
				exit();
			}
			$employer = get_userdata($job->author);
			
			$captcha_code = isset( $_POST['security_code'] ) ? strtolower( $_POST['security_code'] ) : '';
			$captcha_input = isset( $_POST['noo_captcha'] ) ? strtolower( $_POST['noo_captcha'] ) : '';
			if ( $captcha_input !== $captcha_code ) {
				noo_message_add( __( 'Invalid confirmation code, please enter your code again.', 'noo' ) ,'error');
				wp_safe_redirect(get_permalink($job_id));
				exit();
			}

			if ( empty( $candidate_name ) ) {
				noo_message_add( __( 'Please enter your name', 'noo' ) ,'error');
				wp_safe_redirect(get_permalink($job_id));
				exit();
			}
			if ( empty( $candidate_email ) || ! is_email( $candidate_email ) ) {
				noo_message_add( __( 'Please provide a valid email address', 'noo' ) ,'error');
				wp_safe_redirect(get_permalink($job_id));
				exit();
			}
			if ( empty( $application_message ) ) {
				noo_message_add( __( 'Please write your application message', 'noo' ) ,'error');
				wp_safe_redirect(get_permalink($job_id));
				exit();
			}
			$application_args = array(
				'post_type'=>'noo_application',
				'posts_per_page' => -1,
				'post_status'=>array('publish','pending','rejected'),
				'post_parent'=>$job_id,
				'meta_query'=>array(
					array(
						'key' => '_candidate_email',
						'value' => $candidate_email,
					),
				)
			);
			$application = new WP_Query($application_args);
			/*echo "<pre>";
			print_r($application); exit;
			if ( $application->post_count ) {
				echo "1"; exit;
				noo_message_add( __( 'You have already applied for this job', 'noo' ), 'error');
				wp_safe_redirect(get_permalink($job_id));
				exit();
			}*/
			$attachment = self::upload_file( 'application_attachment' );
			if ( $attachment ) {
				$meta['_attachment'] = $attachment;
			}elseif(isset($_POST['resume'])){
				$resume = get_post(absint($_POST['resume']));
				if($resume && $resume->post_type === 'noo_resume'){
					$meta['_attachment'] = $resume->ID;
				}
			}

			$meta['_attachment'] = apply_filters( 'noo_application_attachment', $meta['_attachment'], $job_id, $candidate_email );
			if(empty($meta['_attachment'])){
				noo_message_add( __( 'Please upload CV file or select a resume', 'noo' ), 'error');
				wp_safe_redirect(get_permalink($job_id));
				exit();
			}
			
			if ( ! $application_id = Noo_Application::new_job_application( $job_id, $candidate_name, $candidate_email, $application_message, $meta ) ) {
				noo_message_add( __( 'Could not add new job application', 'noo' ),'error');
				wp_safe_redirect(get_permalink($job_id));
				exit();
			}

			do_action( 'new_job_application', $application_id );
			noo_message_add(__('Your job application has been submitted successfully', 'noo'));
			wp_safe_redirect(get_permalink($job_id));
			exit();
		}catch (Exception $e){
			noo_message_add($e->getMessage(),'error');
			wp_safe_redirect(get_permalink($job_id));
			exit();
		}
		return;
	}
	
	public static function upload_file( $field_key ) {
		if ( isset( $_FILES[ $field_key ] ) && ! empty( $_FILES[ $field_key ] ) && ! empty( $_FILES[ $field_key ]['name'] ) ) {
			include_once( ABSPATH . 'wp-admin/includes/file.php' );
			include_once( ABSPATH . 'wp-admin/includes/media.php' );
				
			$file               = $_FILES[ $field_key ];
			$allowed_mime_types = get_allowed_mime_types();
	
			if ( ! in_array( $_FILES[ $field_key ]["type"], $allowed_mime_types ) ) {
				throw new Exception( sprintf( __( 'Only the following file types are allowed: %s', 'noo'), implode( ', ', array_keys( $allowed_mime_types ) ) ) );
			}
	
			add_filter( 'upload_dir',  array( __CLASS__, 'upload_dir' ) );
			$upload = wp_handle_upload( $file, array( 'test_form' => false ) );
			remove_filter('upload_dir', array( __CLASS__, 'upload_dir' ) );
	
			if ( ! empty( $upload['error'] ) ) {
				return false;
			} else {
				return $upload['url'];
			}
		}
		return false;
	}
	
	public static function upload_dir( $pathdata ) {
		$subdir             = '/jobmonster/' . uniqid();
		$pathdata['path']   = str_replace( $pathdata['subdir'], $subdir, $pathdata['path'] );
		$pathdata['url']    = str_replace( $pathdata['subdir'], $subdir, $pathdata['url'] );
		$pathdata['subdir'] = str_replace( $pathdata['subdir'], $subdir, $pathdata['subdir'] );
		return $pathdata;
	}
	
	public static function edit_company_action(){
		if(!is_user_logged_in())
			return;
		else $user_ID = get_current_user_id(); 
		if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) ) 
			return;
		
		if ( empty( $_POST[ 'action' ] ) || 'edit_company' !== $_POST[ 'action' ] || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'edit-company' ) ) {
			return;
		}
		
		$company_id = self::save_company( $_POST, $user_ID);
		if( $company_id ) {
			noo_message_add(__('Company updated','noo'));
		}
		wp_safe_redirect(Noo_Member::get_company_profile_url());
		die;
		
	}
	
	public static function post_job_action(){
		if(!Noo_Member::is_logged_in()){
			wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'job_package')));
			return;
		}
		
		if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) )
			return;
		
		if(!isset($_POST['page_id']))
			return;
		
		$page_temp = get_page_template_slug($_POST['page_id']);
		
		if('page-post-job.php' !== $page_temp)
			return;
		else
			unset($_POST['page_id']); // unset to prevent strange behaviour of insert new page.
		
		if ( empty( $_POST[ 'action' ] ) || 'post_job' !== $_POST[ 'action' ] || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'noo-post-job' ) ) {
			return;
		}
		
		$_POST['post_status'] = 'draft'; // Job post is alway draft. Need to be reviewed first.

		$job_id = self::save_job($_POST);

		if( Noo_Job::use_woocommerce_package() && !Noo_Job::get_employer_package() ){
			update_post_meta($job_id, '_waiting_payment', 1);
		}		

		if(is_wp_error($job_id)){
			noo_message_add(__('You can not add job','noo'),'error');
			wp_safe_redirect(Noo_Member::get_member_page_url());
			exit;
		}else{
			$location = array('action'=>'preview_job','job_id'=>$job_id);
			wp_safe_redirect(esc_url_raw(add_query_arg($location)));
			exit;
		}
		
	}
	
	public static function preview_job_action(){
		
		if(!Noo_Member::is_logged_in()){
			wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'job_package')));
			return;
		}
		
		if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) )
			return;
		
		if(!isset($_POST['page_id']))
			return;
			
		$page_temp = get_page_template_slug($_POST['page_id']);
		
		if('page-post-job.php' !== $page_temp)
			return;
		
		if ( empty( $_POST[ 'action' ] ) || 'preview_job' !== $_POST[ 'action' ] || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'noo-post-job' ) ) {
			return;
		}
		if(empty($_POST['job_id']))
			return;

		$submit_agreement = Noo_Job::get_setting('noo_job_general', 'submit_agreement', null);
		if( is_null( $submit_agreement ) || !empty( $submit_agreement ) ) {
			if( !isset( $_POST['agreement'] ) || empty( $_POST['agreement'] ) ) {
				noo_message_add(__('You must agree with our condition.','noo'),'error');
				$location = array('action'=>'preview_job','job_id'=>$_POST['job_id']);
				wp_safe_redirect(esc_url_raw(add_query_arg($location)));
				exit;
			}
		}
		
		$job_id = absint($_POST['job_id']);
		$job_need_approve = Noo_Job::get_setting('noo_job_general', 'job_approve','' ) == 'yes';

		if(Noo_Job::use_woocommerce_package()){
			if(Noo_Job::get_job_remain() > 0){
				Noo_Job::increase_job_count(get_current_user_id());
				if( !$job_need_approve ) {
					wp_update_post(array(
						'ID'=>$job_id,
						'post_status'=>'publish',
					));
					Noo_Job::set_job_expires( $job_id );
				} else {
					wp_update_post(array(
						'ID'=>$job_id,
						'post_status'=>'pending',
					));
					update_post_meta($job_id, '_in_review', 1);
				}
				noo_message_add(__('Job successfully added','noo'));
				Noo_Job::send_notification($job_id);
				wp_safe_redirect( Noo_Member::get_endpoint_url('manage-job') );
				exit;
			}else{
				global $woocommerce;

				wp_update_post(array(
					'ID'=>$job_id,
					'post_status'=>'pending',
				));
				update_post_meta($job_id, '_waiting_payment', 1);

				if(isset($_POST['package_id'])){
					Noo_Job::increase_job_count(get_current_user_id());

					$job_package = get_product( absint($_POST['package_id']) );
					$quantity = empty( $_REQUEST['quantity'] ) ? 1 : wc_stock_amount( $_REQUEST['quantity'] );
					$passed_validation 	= apply_filters( 'woocommerce_add_to_cart_validation', true, $job_package->id, $quantity );
					if($job_package->is_type( 'job_package' ) && $passed_validation){
						// Add the product to the cart
						$woocommerce->cart->empty_cart();
						if($woocommerce->cart->add_to_cart( $job_package->id, $quantity,'','',array('_job_id'=>$job_id))){
							//woocommerce_add_to_cart_message( $job_package->id );
							wp_safe_redirect($woocommerce->cart->get_checkout_url());
							die;
						}

					}
				} else {
					wp_update_post(array(
						'ID'=>$job_id,
						'post_status'=>'trashed',
					));
				}
			}
		}else{
			Noo_Job::increase_job_count(get_current_user_id());

			if( !$job_need_approve ) {
				wp_update_post(array(
					'ID'=>$job_id,
					'post_status'=>'publish',
				));
				Noo_Job::set_job_expires( $job_id );
			} else {
				wp_update_post(array(
					'ID'=>$job_id,
					'post_status'=>'pending',
				));
				update_post_meta($job_id, '_in_review', 1);
			}
			noo_message_add(__('Job successfully added','noo'));
			Noo_Job::send_notification($job_id);
			wp_safe_redirect( Noo_Member::get_endpoint_url('manage-job') );
			exit;
		}
	}
	
	public static function edit_job_action(){
		if(!is_user_logged_in())
			return;
		
		if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) )
			return;
		
		if ( empty( $_POST[ 'action' ] ) || 'edit_job' !== $_POST[ 'action' ] || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'edit-job' ) ) {
			return;
		}
		unset( $_POST['post_status'] );
		$job_id = self::save_job($_POST);
		if(is_wp_error($job_id)){
			noo_message_add(__('There\'s a problem when you editing this Job, please try again or contact the Administrator','noo'),'error');
			wp_safe_redirect(Noo_Member::get_member_page_url());
		}else{
			if(isset($_POST['is_edit'])){
				noo_message_add(__('Job updated','noo'));
			}else{
				noo_message_add(__('Job saved','noo'));
			}
			wp_safe_redirect(Noo_Member::get_edit_job_url($job_id));
		}
		exit();
	}
	
	public static function approve_reject_application_modal(){
		
		if(!is_user_logged_in()){
			die(-1);
		}
		
		check_ajax_referer('noo-member-security','security');
		
		$application_id = isset($_POST['application_id']) ? absint($_POST['application_id']) : 0;
		$hander = isset($_POST['hander']) ? $_POST['hander'] : '';
		ob_start();
		Noo_Member::modal_application($application_id,$hander);
		$output = ob_get_clean();
		if(empty($output))
			die(-1);
		else 
			echo trim($output);
		die();
	}
	
	public static function manage_application_action(){
		if(!is_user_logged_in())
			return;
		$action = self::current_action();
		if ( ! empty( $action ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'application-manage-action' ) ) {
			if ( isset( $_REQUEST['application_id'] ) ) {
				$ids = explode( ',', $_REQUEST['application_id'] );
			} elseif ( !empty( $_REQUEST['ids'] ) ) {
				$ids = array_map('intval', $_REQUEST['ids']);
			}
			$msg_title = isset($_REQUEST['title']) ? trim($_REQUEST['title']) : '';
			$msg_body = isset($_REQUEST['message']) ? wp_kses_post( trim( stripslashes( $_REQUEST['message'] ) ) ) : '';
			$company = get_post(absint(Noo_Job::get_employer_company(get_current_user_id())));
			if ( is_multisite() )
				$blogname = $GLOBALS['current_site']->site_name;
			else
				$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
			if($company->post_type !='noo_company'){
				noo_message_add(__('Please add your company before do it','noo'),'error');
				wp_safe_redirect( Noo_Member::get_endpoint_url('manage-application') );
				die;
			}
			try {
				switch ($action){
					case 'approve':
						$approved = 0;
						foreach ((array) $ids as $application_id){
							if( !Noo_Application::can_edit_application( get_current_user_id(), $application_id ) ) {
								continue;
							}
							if(!wp_update_post(array(
								'ID'=>$application_id,
								'post_status'=>'publish',
							))){
								wp_die( __('Error when approving application.','noo') );
							}
							update_post_meta($application_id, '_employer_message_title', $msg_title);
							update_post_meta($application_id, '_employer_message_body', $msg_body);

							do_action('manage_application_action_approve',$application_id);

							$to = noo_get_post_meta($application_id,'_candidate_email');
							$candidate_name = get_the_title($application_id);
							if(is_email($to)){
								//candidate email
								$subject = sprintf(__('%1$s has responsed to your application','noo'),get_the_title($company->ID));
								$message = __( 'Hi %1$s,
%2$s has just response to your application for job  <a href="%3$s">%4$s</a> with message: 
<br/>
<div style="font-style: italic;">						
%5$s
</div>
<br/>
You can manage your applications in <a href="%6$s">Manage Application</a>.
<br/>
Best regards,<br/>
%7$s','noo');
								noo_mail($to, $subject, sprintf($message,$candidate_name,get_the_title($company->ID),get_permalink($application->post_parent),get_the_title($application->post_parent),$msg_body,Noo_Member::get_endpoint_url('manage-job-applied'),$blogname),array(),'noo_notify_job_apply_approve_candidate');
							}
							$approved ++;
						}
						noo_message_add(sprintf(__('Approved %s application','noo'),$approved));
						wp_safe_redirect( Noo_Member::get_endpoint_url('manage-application') );
						die;
						break;
					break;
					case 'reject':
						$rejected = 0;
						foreach ((array) $ids as $application_id){
							if( !Noo_Application::can_edit_application( get_current_user_id(), $application_id ) ) {
								continue;
							}
							if(!wp_update_post(array(
								'ID'=>$application_id,
								'post_status'=>'rejected',
							))){
								wp_die( __('Error when rejecting application.','noo') );
							}
							update_post_meta($application_id, '_employer_message_title', $msg_title);
							update_post_meta($application_id, '_employer_message_body', $msg_body);
						
							do_action('manage_application_action_reject',$application_id);

							$to = noo_get_post_meta($application_id,'_candidate_email');
							$candidate_name = get_the_title($application_id);
							if(is_email($to)){
								//candidate email
								$subject = sprintf(__('%1$s has responsed to your application','noo'),get_the_title($company->ID));
								$message = __( 'Hi %1$s,
%2$s has just response to your application for job  <a href="%3$s">%4$s</a> with message: 
<br/>
<div style="font-style: italic;">						
%5$s
</div>
<br/>
You can manage your applications in <a href="%6$s">Manage Application</a>.
<br/>
Best regards,<br/>
%7$s','noo');
								noo_mail($to, $subject, sprintf($message,$candidate_name,get_the_title($company->ID),get_permalink($application->post_parent),get_the_title($application->post_parent),$msg_body,Noo_Member::get_endpoint_url('manage-job-applied'),$blogname),array(),'noo_notify_job_apply_reject_candidate');
							}
							$rejected++;
						}
						noo_message_add(sprintf(__('Rejected %s application','noo'),$rejected));
						wp_safe_redirect( Noo_Member::get_endpoint_url('manage-application') );
						die;
						break;
					break;
					case 'delete':
						$deleted = 0;
						foreach ((array) $ids as $application_id){
							if( !Noo_Application::can_trash_application( get_current_user_id(), $application_id ) ) {
								continue;
							}
							
							// if ( !wp_delete_post($application_id) )
							// Version 2.7.0 Making application inactive instead of move to trash.
							if ( !wp_update_post(array( 'ID' => $application_id, 'post_status' => 'inactive' )) ) {
								wp_die( __('Error when deleting application.','noo') );
							}
							
							$deleted++;
						}
						noo_message_add(sprintf(__('Deleted %s application','noo'),$deleted));
						do_action('manage_application_action_delete',$ids);
						wp_safe_redirect( Noo_Member::get_endpoint_url('manage-application') );
						die;
					break;
					default:
					break;
				}
			}catch (Exception $e){
				throw new Exception($e->getMessage());
			}
		}
	}
	
	public static function manage_job_action(){
		if(!is_user_logged_in())
			return;

		$employer_id = get_current_user_id();
		$action = self::current_action();
		$job_need_approve = Noo_Job::get_setting('noo_job_general', 'job_approve','' ) == 'yes';
		if ( ! empty( $action ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'job-manage-action' ) ) {
			if ( isset( $_REQUEST['job_id'] ) ) {
				$ids = explode( ',', $_REQUEST['job_id'] );
			} elseif ( !empty( $_REQUEST['ids'] ) ) {
				$ids = array_map('intval', $_REQUEST['ids']);
			}
			try {
				switch ($action){
					case 'publish':
						$published = 0;
						foreach ((array) $ids as $job_id){
							$job = get_post($job_id);
							if ( $job->post_type !== 'noo_job' )
								return;
							if( !Noo_Member::can_change_job_state( $job_id, $employer_id ) ){
								continue;
							}
							if ( $job->post_author != $employer_id &&  !current_user_can( 'edit_post', $job_id ) ) {
								wp_die( __( 'You do not have sufficient permissions to access this page.', 'noo' ), '', array( 'response' => 403 ) );
							}
							if(!wp_update_post(array(
								'ID'=>$job_id,
								'post_status'=>'publish',
							))){
								wp_die( __('Error publish.','noo') );
							}
							Noo_Job::set_job_expires( $job_id );
							$published++;
						}
						noo_message_add(sprintf(__('Published %s job','noo'),$published));
						do_action('manage_job_action_publish',$ids);
						wp_safe_redirect( Noo_Member::get_endpoint_url('manage-job') );
						die;
						break;
					case 'unpublish':
						$unpublished = 0;
						foreach ((array) $ids as $job_id){
							$job = get_post($job_id);
							if ( $job->post_type !== 'noo_job' )
								return;
							if( !Noo_Member::can_change_job_state( $job_id, $employer_id ) ){
								continue;
							}
							if ( $job->post_author != $employer_id &&  !current_user_can( 'edit_post', $job_id ) ) {
								wp_die( __( 'You do not have sufficient permissions to access this page.', 'noo' ), '', array( 'response' => 403 ) );
							}
							if(!wp_update_post(array(
								'ID'=>$job_id,
								'post_status'=>'pending',
							))){
								wp_die( __('Error unpublish.','noo') );
							}
							$unpublished++;
						}
						noo_message_add(sprintf(__('Unpublished %s job','noo'),$unpublished));
						do_action('manage_job_action_pending',$ids);
						wp_safe_redirect( Noo_Member::get_endpoint_url('manage-job') );
						die;
						break;
					case 'featured':
						if(!Noo_Job::can_set_job_feature()){
							noo_message_add(__('You do not have sufficient permissions set job to featured! Please check your plan package!','noo'),'error');
							wp_safe_redirect( Noo_Member::get_endpoint_url('manage-job') );
							die;
						}
						$job_id = reset($ids);
						$job = get_post($job_id);
						if ( !Noo_Member::can_edit_job( $job_id, $employer_id ) )
							return;
						
						if(get_post_status($job_id) == 'expired'){
							noo_message_add(__('You can not toggle job expired to featured','noo'),'notice');
							wp_safe_redirect( Noo_Member::get_endpoint_url('manage-job') );
							die;
						}
						if ( $job->post_author != $employer_id &&  !current_user_can( 'edit_post', $job_id ) ) {
							wp_die( __( 'You do not have sufficient permissions to access this page.', 'noo' ), '', array( 'response' => 403 ) );
						}
				
						$featured = noo_get_post_meta( $job_id, '_featured' );
				
						if ( 'yes' !== $featured ) {
							update_post_meta( $job_id, '_featured', 'yes' );
							update_user_meta($job->post_author, '_job_featured', absint(get_user_meta($job->post_author,'_job_featured',true)) + 1 );
							noo_message_add(__('Job set to featured successfully.','noo'));
						// } else {
						// 	update_post_meta( $job_id, '_featured', 'no' );
						// 	noo_message_add(__('Set Job to no featured','noo'));
						}
						
						do_action('manage_job_action_featured',$job_id);
						wp_safe_redirect( Noo_Member::get_endpoint_url('manage-job') );
						die;
						break;
					case 'edit':
						break;
					case 'delete':
						$deleted=0;
						foreach ((array) $ids as $job_id){
							$job = get_post($job_id);
							if ( $job->post_type !== 'noo_job' )
								return;
							if ( $job->post_author != $employer_id && !current_user_can( 'delete_post', $job_id ) ) {
								wp_die( __( 'You do not have sufficient permissions to access this page.', 'noo' ), '', array( 'response' => 403 ) );
							}

							$old_status = get_post_status( $job_id );
							$in_review = (bool) noo_get_post_meta( $job_id, '_in_review', '' );
							$waiting_payment = (bool) noo_get_post_meta( $job_id, '_waiting_payment', '' );
							
							if ( !wp_delete_post($job_id) ) {
								wp_die( __('Error in deleting.','noo') );
							}

							// Correct the job count.
							if( 'pending' == $old_status && ( $in_review || $waiting_payment ) ) {
								Noo_Job::decrease_job_count( $employer_id );
								$featured = noo_get_post_meta( $job_id, '_featured' );
								if( $featured == 'yes' ) {
									$job_featured = Noo_Job::get_count_feature_job_by_employer( $employer_id );
									update_user_meta($employer_id, '_job_featured', max( $job_featured - 1, 0 ) );
								}
							}

							$deleted++;
						}
						noo_message_add(sprintf(__('Deleted %s job','noo'),$deleted));
						do_action('manage_job_action_delete',$ids);
						wp_safe_redirect( Noo_Member::get_endpoint_url('manage-job') );
						die;
					break;
					case 'deletes':
						
					break;
				}
			}catch (Exception $e){
				throw new Exception($e->getMessage());
			}
		}
	}
	
	public static  function ajax_login(){
		check_ajax_referer('noo-ajax-login','security');
		$info = array();
		$info['user_login'] = $_POST['log'];
		$info['user_password'] = $_POST['pwd'];

		$info['remember'] = (isset( $_POST['remember'] ) && $_POST['remember'] === true) ? true : false ;
		$info = apply_filters('noo_ajax_login_info', $info);
		
		$secure_cookie = is_ssl() ? true : false;
		$user_signon = wp_signon( $info, $secure_cookie );

		// it's possible that an old user used email instead of username
		if( is_wp_error( $user_signon ) && Noo_Member::get_setting('register_using_email') && is_email( $info['user_login'] ) ) {
			$user = get_user_by( 'email', $info['user_login'] );
			if( $user != false ) {
				$info['user_login'] = $user->user_login;
			}

			$user_signon = wp_signon( $info, $secure_cookie );
		}

		if ( is_wp_error( $user_signon ) ){
			$error_msg = $user_signon->get_error_message();
			wp_send_json(array( 'loggedin' => false, 'message' => '<span class="error-response">' . $error_msg . '</span>' ));
		} else {
			$redirecturl = isset( $_POST['redirect_to'] ) ? $_POST['redirect_to'] : '';
			$redirecturl = apply_filters( 'noo_login_redirect', $redirecturl, $user_signon );
			wp_send_json(array('loggedin'=>true, 'redirecturl' => $redirecturl, 'message'=> '<span class="success-response">' . __( 'Login successful, redirecting...','noo' ) . '</span>' ));
		}
		die;
	}
	
	public static  function ajax_login_priv(){
		$link = "javascript:window.location.reload();return false;";
		wp_send_json(array('loggedin'=>false, 'message'=> sprintf(__('You are already logged in. Please <a href="#" onclick="%s">refresh</a> page','noo'),$link)));
		die();
	}
	
	public static  function ajax_register(){
		check_ajax_referer('noo-ajax-register','security');
		if( !check_ajax_referer('noo-ajax-register', 'security', false) ) {
			$result = array(
				'success' => false,
				'message' => '<span class="error-response">'.__( 'Your session is expired or you submitted an invalid form.', 'noo' ).'</span>',
			);
		}

		$captcha_code = isset( $_POST['security_code'] ) ? strtolower( $_POST['security_code'] ) : '';
		$captcha_input = isset( $_POST['noo_captcha'] ) ? strtolower( $_POST['noo_captcha'] ) : '';
		if ( $captcha_input !== $captcha_code ) {

			$result = array(
				'success' => false,
				'message' => '<span class="error-response">'.__( 'Invalid confirmation code, please enter your code again.', 'noo' ).'</span>',
			);

		}elseif(get_option( 'users_can_register' )){
			$user_args = array();
			$user_args['user_login'] = isset($_POST['user_login']) ? stripslashes( esc_html( $_POST['user_login'] ) ) : '';
			$user_args['user_email'] = isset($_POST['user_email']) ?  stripslashes( esc_html( $_POST['user_email'] ) ) : '';
			$user_args['user_password']  = isset($_POST['user_password']) ? stripslashes( esc_html( $_POST['user_password'] ) ) : '';
			$user_args['cuser_password'] = isset($_POST['cuser_password']) ? stripslashes( esc_html( $_POST['cuser_password'] ) ) : '';
			$user_args['first_name'] = isset($_POST['first_name']) ? stripslashes( esc_html( $_POST['first_name'] ) ) : '';
			$user_args['last_name'] = isset($_POST['last_name']) ? stripslashes( esc_html( $_POST['last_name'] ) ) : '';
			$user_args['using'] = isset($_POST['using']) ? stripslashes( esc_html( $_POST['using'] ) ) : '';
			$user_args['using_id'] = isset($_POST['using_id']) ? stripslashes( esc_html(  $_POST['using_id'] ) ) : '';
			$allow_register = Noo_Member::get_setting('allow_register', 'both');
			switch( $allow_register ) {
				case 'candidate':
					$user_args['role'] = Noo_Member::CANDIDATE_ROLE;
					break;
				case 'employer':
					$user_args['role'] = Noo_Member::EMPLOYER_ROLE;
					break;
				default:
					$user_args['role'] = isset($_POST['user_role']) ? stripslashes( esc_html( $_POST['user_role'] ) ) : '';
					break;
			}
			
			$redirect_to = '';
			if( $user_args['role'] == Noo_Member::CANDIDATE_ROLE )
				$redirect_to = Noo_Member::get_candidate_profile_url();
			elseif( $user_args['role'] == Noo_Member::EMPLOYER_ROLE )
				$redirect_to = Noo_Member::get_company_profile_url();

			$redirect_to = isset($_POST['redirect_to']) && !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : $redirect_to;
			$errors = self::_register_new_user( $user_args );
			$result = array();
			if ( is_wp_error( $errors ) ) {
				$result = array(
					'success' => false,
					'message'   => '<span class="error-response">'.$errors->get_error_message().'</span>',
				);
			} else {
				$filter_tag = 'noo_register_redirect' . ( !empty($user_args['role']) ? '_'.$user_args['role'] : '' );
				$result = array(
					'success'     => true,
					'message'	=> '<span class="success-response">'.__( 'Registration complete.', 'noo' ).'</span>',
					'redirecturl' => apply_filters($filter_tag, $redirect_to),
				);
			}
		}else {
			$result = array(
				'success' => false,
				'message'   =>__( 'Not allow register in site.', 'noo' ),
			);
		}
		wp_send_json($result);
	}
	
	protected static function _register_new_user( $args = array() ) {
		$defaults = array( 
			'user_login'             => '',
			'user_email'             => '',
			'user_password'          => '',
			'cuser_password'         => '',
			'role'                   => '',
			'first_name'             => '',
			'last_name'              => '',
			'using'                  => '',
			'using_id'               => '',
		);
		extract( wp_parse_args( $args, $defaults) );

		$errors = new WP_Error();
		$sanitized_user_login = sanitize_user( $user_login );
		$user_email = apply_filters( 'user_registration_email', $user_email );
	
		// Check the username was sanitized
		if(empty($role)){
			$errors->add( 'empty_role', __( 'Please choose a role for your account.', 'noo' ) );
		}elseif ( $sanitized_user_login == '' ) {
			$errors->add( 'empty_username', __( 'Please enter a username.', 'noo' ) );
		} elseif ( ! validate_username( $user_login ) ) {
			$errors->add( 'invalid_username', __( 'This username is invalid because it uses illegal characters. Please enter a valid username.', 'noo' ) );
			$sanitized_user_login = '';
		} elseif ( username_exists( $sanitized_user_login ) ) {
			$errors->add( 'username_exists', __( 'This username is already registered. Please choose another one.', 'noo' ) );
		}
	
		// Check the email address
		if ( $user_email == '' ) {
			$errors->add( 'empty_email', __( 'Please type your email address.', 'noo' ) );
		} elseif ( ! is_email( $user_email ) ) {
			$errors->add( 'invalid_email', __( 'The email address isn\'t correct.', 'noo' ) );
			$user_email = '';
		} elseif ( email_exists( $user_email ) ) {
			$errors->add( 'email_exists', __( 'This email is already registered, please choose another one.', 'noo' ) );
		}
		//Check the password
	
		if(strlen($user_password) < 6){
			$errors->add( 'minlength_password', __( 'Password must be 6 character long.', 'noo' ) );
		}elseif (empty($cuser_password)){
			$errors->add( 'not_cpassword', __( 'Not see password confirmation field.', 'noo' ) );
		}elseif ($user_password != $cuser_password){
			$errors->add( 'unequal_password', __( 'Passwords do not match.', 'noo' ) );
		}
	
		$errors = apply_filters( 'registration_errors', $errors, $sanitized_user_login, $user_email );
	
		if ( $errors->get_error_code() )
			return $errors;
		
		$user_pass = $user_password;
		$new_user = array(
			'user_login' => $sanitized_user_login,
			'user_pass'  => $user_pass,
			'user_email' => $user_email,
			'role'       => $role,
			'first_name' => $first_name,
			'last_name'  => $last_name,
		);
		$user_id = wp_insert_user( apply_filters( 'noo_create_user_data', $new_user ) );
		//$user_id = wp_create_user( $sanitized_user_login, $user_pass, $user_email );
	
		if ( ! $user_id ) {
			$errors->add( 'registerfail', __( 'Couldn\'t register you... please contact the site administrator', 'noo' ) );
			return $errors;
		} else {
			do_action( 'noo_new_user_registered', $user_id, $role );
		}
	
		update_user_option( $user_id, 'default_password_nag', true, true ); // Set up the Password change nag.
		
		if ( !empty( $using ) ) :
			
			// -- checking user meta
				// $get_info_login = get_user_meta( $user_id, 'info_login', true );
			// -- Get list user
				// $list_user = is_array($get_info_login) ? $get_info_login : array();

			if ( $using == 'fb' ) :
				
				// -- Add id facebook in meta user
					// $user_new = array( 'id_facebook' => $using_id );
					update_user_meta( $user_id, 'id_facebook', $using_id );
			
			elseif ( $using == 'gg' ) :
				
				// -- Add id facebook in meta user
					// $user_new = array( 'id_google' => $using_id );
					update_user_meta( $user_id, 'id_google', $using_id );
			
			elseif ( $using == 'linkedin' ) :
				
				// -- Add id linkedin in meta user
					update_user_meta( $user_id, 'id_linkedin', $using_id );

			endif;
			
			// Update and replace user new to list
				// $list_user_new = array_merge( $list_user, $user_new );
				// update_user_meta( $user_id, 'info_login', $user_new );

		endif; // -- / Check $using

		$user = get_userdata( $user_id );
		
		if ( is_multisite() )
			$blogname = $GLOBALS['current_site']->site_name;
		else
			$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		// user email
		$subject = sprintf(__('Congratulation! You\'ve created successfully account on [%1$s]','noo'),$blogname);
		$to = $user->user_email;

		if( $role == Noo_Member::CANDIDATE_ROLE ) {
			$message = __('Dear %1$s,<br/>
Thank you for registering an account on %2$s as a candidate. You can start searching for your expected jobs or create your resume now.
<br/><br/>
Best regards,<br/>
%3$s','noo');
			noo_mail($to, $subject, sprintf($message,$user->display_name,$blogname,$blogname),array(),'noo_notify_register_candidate');
		} else if( $role == Noo_Member::EMPLOYER_ROLE ) {
			$message = __('Dear %1$s,<br/>
Thank you for registering an account on %2$s as an employer. You can start posting jobs or search for your potential candidates now.
<br/><br/>
Best regards,<br/>
%3$s','noo');
			noo_mail($to, $subject, sprintf($message,$user->display_name,$blogname,$blogname),array(),'noo_notify_register_employer');
			
			//notification to admin
			wp_new_user_notification( $user_id );
		}

		$data_login['user_login']             = $user->user_login;
		$data_login['user_password']          = $user_password;
		$secure_cookie						  = is_ssl() ? true : false;
		$user_login                           = wp_signon( $data_login, $secure_cookie );
	
		//@todo
		
		//wp_set_auth_cookie($user_id);
		return $user_id;
	}

	public static function ajax_update_password() {
		if ( !is_user_logged_in() ) {
			$result = array(
				'success' => false,
				'message' => '<span class="error-response">'.__( 'You are not logged in yet', 'noo' ).'</span>',
			);

			wp_send_json($result);
			return;
		}

		if( !check_ajax_referer('update-password', 'security', false) ) {
			$result = array(
				'success' => false,
				'message' => '<span class="error-response">'.__( 'Your session is expired or you submitted an invalid form.', 'noo' )
			);

			wp_send_json($result);
			return;
		}

		global $current_user;
		get_currentuserinfo();

		$user_id		= $current_user->ID;
		$submit_user_id	= intval( $_POST['user_id'] );
		if( $user_id != $submit_user_id ) {
			$result = array(
				'success' => false,
				'message' => '<span class="error-response">'.__('There\'s an unknown error. Please retry or contact Administrator.', 'noo').'</span>',
			);
		} else {
			$no_html			= array();
			$old_pass			= wp_kses( $_POST['old_pass'] ,$no_html) ;
			$new_pass			= wp_kses( $_POST['new_pass'] ,$no_html) ;
			$new_pass_confirm	= wp_kses( $_POST['new_pass_confirm'] ,$no_html) ;

			if( empty( $new_pass ) || empty( $new_pass_confirm ) ){
				$result = array(
					'success' => false,
					'message' => '<span class="error-response">'.__('The new password is blank.', 'noo').'</span>',
				);
			} elseif($new_pass != $new_pass_confirm){
				$result = array(
					'success' => false,
					'message' => '<span class="error-response">'.__('Passwords do not match.', 'noo').'</span>',
				);
			} else {
				$user = get_user_by( 'id', $user_id );
				if ( $user && wp_check_password( $old_pass, $user->data->user_pass, $user->ID) ){
					wp_set_password( $new_pass, $user->ID );

					$result = array(
						'success'     => true,
						'message'	=> '<span class="success-response">'.__( 'Password updated successfully.', 'noo' ).'</span>',
						'redirecturl'=>apply_filters('noo_update_password_redirect', ''),
					);
				} else {
					$result = array(
						'success' => false,
						'message' => '<span class="error-response">'.__('Old Password is not correct.', 'noo').'</span>',
					);
				}
			}
		}

		wp_send_json($result);
	}

	public static function ajax_bookmark_job() {
		if ( !is_user_logged_in() ) {
			$result = array(
				'success' => false,
				'message' => '<span class="error-response">'.__( 'You are not logged in yet', 'noo' ).'</span>',
			);

			wp_send_json($result);
			return;
		}

		if( !check_ajax_referer('noo-bookmark-job', 'security', false) ) {
			$result = array(
				'success' => false,
				'message' => '<span class="error-response">'.__( 'Your session is expired or you submitted an invalid form.', 'noo' ).'</span>',
			);

			wp_send_json($result);
			return;
		}

		if( ! isset($_POST['job_id']) || empty($_POST['job_id']) ) {
			$result = array(
				'success' => false,
				'message' => '<span class="error-response">'.__('There\'s an unknown error. Please retry or contact Administrator.', 'noo').'</span>',
			);

			wp_send_json($result);
			return;
		}

		global $current_user;
		get_currentuserinfo();

		$user_id		= $current_user->ID;
		$job_id			= $_POST['job_id'];

		if( Noo_Job::is_bookmarked($user_id, $job_id) ) {
			if( Noo_Job::clear_bookmark_job($user_id, $job_id) ) {
				$result = array(
					'success' => true,
					'message' => '<span class="success-response">'.__( 'Bookmark removed.', 'noo' )
				);
				wp_send_json($result);
			}
		} else {
			if( Noo_Job::set_bookmark_job($user_id, $job_id) ) {
				$result = array(
					'success' => true,
					'message' => '<span class="success-response">'.__( 'Job bookmarked.', 'noo' )
				);
				wp_send_json($result);
			}
		}
		
		$result = array(
			'success' => false,
			'message' => '<span class="error-response">'.__('There\'s an unknown error. Please retry or contact Administrator.', 'noo').'</span>',
		);

		wp_send_json($result);
	}
	
	public static function save_job($args=''){
		try{
			$defaults=array(
				'job_id'      =>'',
				'position'    =>'',
				'desc'        =>'',
				'feature'     =>'no',//isset($args['feature']) && $args['feature'] == 'yes' ? $args['feature'] : 'no',
				'location'    =>'',
				'type'        =>'',
				'category'    =>'',
				'closing'     =>'',
				'cover_image' => '',
				'post_status' => 'draft'
			);
			$args = wp_parse_args($args,$defaults);
			unset($args['page_id']); // unset to prevent strange behaviour of insert new page.
			$no_html = array();
			$job_data = array(
				'post_title'     => wp_kses( $args['position'], $no_html ),
				'post_content'   => wp_kses( $args['desc'], self::$allowed_html ),
				'post_type'      => 'noo_job',
				'comment_status' => 'closed',
			);
			$post_id = new WP_Error();
			$new_job = false;
			if(!empty($args['job_id']) && isset($args['is_edit'])){
				$job_data['ID'] = $args['job_id'];
				$post_id = wp_update_post($job_data);
			}else{
				if(Noo_Job::can_add_job()){
					$post_id = wp_insert_post($job_data);
					$new_job = true;
				}
			}
			if(!is_wp_error($post_id)){
				if( isset( $job_data['post_status'] ) && $job_data['post_status'] == 'publish' ) {
					Noo_Job::set_job_expires( $post_id );
				}
				
				$custom_fields = noo_get_custom_fields( Noo_Job::get_setting('noo_job_custom_field', array()), 'noo_jobs_custom_fields_' );
				if($custom_fields){
					foreach ($custom_fields as $custom_field){
						$id = '_noo_job_field_'.sanitize_title(@$custom_field['name']);
						if(isset($_POST[$id])){
							update_post_meta($post_id, $id, wp_kses_post($_POST[$id]));
						}
					}
				}
				
				//update_post_meta($post_id, '_location', $args['location']);
				if(!empty($args['location'])){
					$location_arr = array();
					foreach ((array) $args['location'] as $l){
						$slug = trim($l);
						if ( ($t = get_term_by( 'slug', sanitize_title( $slug ), 'job_location' ) )) {
							$location_arr[] = $t->term_id;
						}else{
							$n_l = wp_insert_term( $slug, 'job_location' );
							if ($n_l && !is_wp_error($n_l) && ($loca = get_term( absint($n_l['term_id']), 'job_location' ))) {
								$location_arr[] = $loca->term_id;
							}
						}
					}
					if(!empty($location_arr)){
						if ( Noo_Job::geolocation_enabled() ) {
							$noo_job_geolocation = get_option('noo_job_geolocation');
							if ( ! $noo_job_geolocation )
								$noo_job_geolocation = array();
							foreach ($location_arr as $location_ar){
								$lo = get_term($location_ar, 'job_location');
								if($lo && !is_wp_error($lo) ){
									if(!isset($noo_job_geolocation[$lo->slug])){
										$location_geo_data = Noo_Job::get_job_geolocation($lo->name);
										if($location_geo_data && !is_wp_error($location_geo_data)){
											$noo_job_geolocation[$lo->slug] = $location_geo_data;
										}
									}
								}
							}
							//update geo option
							update_option('noo_job_geolocation', $noo_job_geolocation);
						}
						wp_set_post_terms( $post_id, $location_arr, 'job_location', false );
					}
				}
				update_post_meta($post_id, '_closing', wp_kses( $args['closing'], $no_html ));
				update_post_meta($post_id, '_cover_image', wp_kses( $args['cover_image'], $no_html ));
				update_post_meta($post_id, '_application_email', wp_kses( $args['application_email'], $no_html ));
				$custom_apply_link = Noo_Job::get_setting('noo_job_linkedin', 'custom_apply_link' );
				if( $custom_apply_link == 'employer' ) {
					update_post_meta($post_id, '_custom_application_url', wp_kses( $args['custom_application_url'], $no_html ));
				}
					
				wp_set_post_terms( $post_id, array( $args['type'] ), 'job_type', false );
				wp_set_post_terms( $post_id, $args['category'], 'job_category', false );	
				
				//
				do_action('noo_save_job',$post_id);
			}
			if(isset($_POST['company_name']) && !Noo_Job::get_employer_company() && Noo_Member::is_employer() ){
				self::save_company($_POST);
			}

			do_action('noo_after_save_job',$post_id);
			return $post_id;
		}catch (Exception $e){
			throw new Exception($e->getMessage());
		}
	}
	
	public static function edit_resume_action(){
		if(!is_user_logged_in())
			return;
		
		if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) )
			return;
		
		if ( empty( $_POST[ 'action' ] ) || 'edit_resume' !== $_POST[ 'action' ] || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'edit-resume' ) ) {
			return;
		}

		$resume_id = false;
		if( !isset( $_POST['resume_id'] ) || empty( $_POST['resume_id'] ) ) {
			// Insert new
			$resume_id = self::_save_resume($_POST);
			if( $resume_id ) {
				$_POST['resume_id'] = $resume_id;
				$resume_id = self::_save_detail_resume($_POST);
			}

			if( $resume_id ) {
				$resume_id = wp_update_post( array(
					'ID'     => intval( $resume_id ),
					'post_status'	 => 'publish',
				) );
			}

			if( $resume_id ) {
				// Add email
				Noo_Resume::notify_candidate($resume_id);
			}
		} else {
			// Edit resume
			$resume_id = self::_save_resume($_POST);
			if( $resume_id ) {
				$resume_id = self::_save_detail_resume($_POST);
			}

			if( $resume_id ) {
				$resume_id = wp_update_post( array(
					'ID'     => intval( $resume_id ),
					'post_status'	 => 'publish',
				) );
			}
		}
		
		if( !$resume_id ) {
			wp_safe_redirect(Noo_Member::get_endpoint_url('post-resume'));
		} else {
			noo_message_add(__('Resume saved','noo'));
			wp_safe_redirect(Noo_Member::get_endpoint_url('manage-resume'));
		}
		
		exit();
	}

	public static function employer_message_application_modal(){
		
		if(!is_user_logged_in()){
			die(-1);
		}
		
		check_ajax_referer('noo-member-security','security');
		
		$application_id = isset($_POST['application_id']) ? absint($_POST['application_id']) : 0;
		$mode = isset($_POST['mode']) ? absint($_POST['mode']) : 0;
		
		ob_start();
		Noo_Member::modal_employer_message($application_id,$mode);
		$output = ob_get_clean();
		if(empty($output))
			die(-1);
		else 
			echo trim($output);
		die();
	}

	public static function manage_job_applied_action(){
		if(!is_user_logged_in())
			return;
		$action = self::current_action();
		if ( ! empty( $action ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'job-applied-manage-action' ) ) {
			if ( isset( $_REQUEST['application_id'] ) ) {
				$ids = explode( ',', $_REQUEST['application_id'] );
			} elseif ( !empty( $_REQUEST['ids'] ) ) {
				$ids = array_map('intval', $_REQUEST['ids']);
			}
			try {
				switch ($action){
					case 'withdraw':
						$withdrawed = 0;
						foreach ((array) $ids as $application_id){
							if( !Noo_Application::can_trash_application( get_current_user_id(), $application_id ) ) {
								continue;
							}
							
							if ( !wp_update_post(array( 'ID' => $application_id, 'post_status' => 'inactive' )) ) {
								wp_die( __('Error when withdrawing application.','noo') );
							}
							
							$withdrawed++;
						}
						noo_message_add(sprintf(__('Withdrawed %s application','noo'),$withdrawed));
						do_action('manage_application_action_withdraw',$ids);
						wp_safe_redirect( Noo_Member::get_endpoint_url('manage-job-applied') );
						die;
					break;
					case 'delete':
						$deleted = 0;
						foreach ((array) $ids as $application_id){
							if( !Noo_Application::can_delete_application( get_current_user_id(), $application_id ) ) {
								continue;
							}
							
							if ( !wp_delete_post($application_id) ) {
								wp_die( __('Error when deleting application.','noo') );
							}
							
							$deleted++;
						}
						noo_message_add(sprintf(__('Deleted %s application','noo'),$deleted));
						do_action('manage_application_action_delete',$ids);
						wp_safe_redirect( Noo_Member::get_endpoint_url('manage-job-applied') );
						die;
					break;
					default:
					break;
				}
			}catch (Exception $e){
				throw new Exception($e->getMessage());
			}
		}
	}
	
	public static function edit_job_alert_action(){
		if(!is_user_logged_in())
			return;
		
		if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) )
			return;
		
		if ( empty( $_POST[ 'action' ] ) || 'edit_job_alert' !== $_POST[ 'action' ] || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'edit-job-alert' ) ) {
			return;
		}
		$job_alert_id = self::_save_job_alert($_POST);
		
		if( $job_alert_id === false ) {
			wp_safe_redirect(Noo_Member::get_endpoint_url('add-job-alert'));
		} else {
			noo_message_add(__('Job alert saved','noo'));
			wp_safe_redirect(Noo_Member::get_endpoint_url('job-alert'));
		}
		
		exit();
	}
	
	public static function delete_job_alert_action(){
		if(!is_user_logged_in())
			return;
		
		if ( 'GET' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) )
			return;
		
		if ( empty( $_GET[ 'action' ] ) || 'delete_job_alert' !== $_GET[ 'action' ] || empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'edit-job-alert' ) ) {
			return;
		}
		$job_alert_id = isset($_GET['job_alert_id']) ? $_GET['job_alert_id'] : '';
		
		if( empty( $job_alert_id ) ) {
			noo_message_add(__('There is problem deleting this Job alert','noo'));
			wp_safe_redirect(Noo_Member::get_endpoint_url('job-alert'));
		} else {
			wp_delete_post( $job_alert_id );
			noo_message_add(__('Job alert deleted','noo'));
			wp_safe_redirect(Noo_Member::get_endpoint_url('job-alert'));
		}
		
		exit();
	}
	
	public static function edit_candidate_profile_action(){
		if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) )
			return;
		
		if ( empty( $_POST[ 'action' ] ) || 'edit_candidate' !== $_POST[ 'action' ] || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'edit-candidate' ) ) {
			return;
		}

		if(!is_user_logged_in())
			return;
		
		$candidate_id = isset( $_POST['candidate_id'] ) ? absint( $_POST['candidate_id'] ) : '';
		if( empty( $candidate_id) ) {
			noo_message_add(__('Missing Candidate ID.', 'noo'));
		} elseif( $candidate_id != get_current_user_id() ) {
			noo_message_add(__('You can not edit other\'s profile.', 'noo'));
		} else {
			$no_html = self::$no_html;

			$name = isset( $_POST['name'] ) ? wp_kses( $_POST['name'], $no_html ) : '';
			$splitted_name = explode( ' ', $name, 2 );
			$first_name = isset( $_POST['first_name'] ) && !empty( $_POST['first_name'] ) ? wp_kses( $_POST['first_name'], $no_html ) : $splitted_name[0];
			$last_name = isset( $_POST['last_name'] ) && !empty( $_POST['last_name'] ) ? wp_kses( $_POST['last_name'], $no_html ) : ( !isset( $splitted_name[1] ) ? '' : $splitted_name[1] );
			$email = isset( $_POST['email'] ) ? wp_kses( $_POST['email'], $no_html ) : '';
			$desc	= isset( $_POST['description'] ) ? wp_kses( $_POST['description'], self::$allowed_html ) : '';

			if( empty($name) ) {
				noo_message_add(__('Your Name can\'t be blank.', 'noo'), 'error');
			} else if( empty($email) || !is_email( $email ) ) {
				noo_message_add(__('Candidate needs a valid Email.', 'noo'), 'error');
			} else {
				$candidate = array(
					'ID'			=> $candidate_id,
					'first_name'	=> $first_name,
					'last_name'		=> $last_name,
					'display_name'	=> $first_name . ' ' . $last_name,
					'user_email'	=> $email,
					'description'	=> $desc
				);

				$user_id = wp_update_user( $candidate );
				if( is_wp_error( $user_id ) || $user_id != $candidate_id ) {
					noo_message_add(__('There\'s an unknown error. Please retry or contact Administrator.', 'noo'));
				} else {
					Noo_Member::save_user_profile($candidate_id);
					noo_message_add(__('Your profile is updated successfully','noo'));
				}
			}
		}
		wp_safe_redirect(Noo_Member::get_candidate_profile_url());
		die;
	}
	
	public static function post_resume_action(){
		if(!Noo_Member::is_logged_in()){
			wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'login')));
			return;
		}
		
		if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) )
			return;
		
		if(!isset($_POST['page_id']))
			return;
		
		$page_temp = get_page_template_slug($_POST['page_id']);
		
		if('page-post-resume.php' !== $page_temp)
			return;

		if ( empty( $_POST[ 'action' ] ) || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'noo-post-resume' ) ) {
			return;
		}

		$action = $_POST[ 'action' ];
		if( $action != 'resume_general' && $action != 'resume_detail' ) {
			return;
		}
		$location = array();
		if( $action  == 'resume_general' ) {
			$resume_id = self::_save_resume($_POST);
			if($resume_id === false){
				$location['action'] = $action;
			} else {
				// $_POST['resume_id'] = $resume_id;
				// $resume_id = self::_save_detail_resume($_POST);
				// if($resume_id === false){
				// 	$location['action'] = $action;
				// } else {
				$location['resume_id'] = $resume_id;
				$location['action'] = Noo_Resume::enable_resume_detail() ? 'resume_detail' : 'resume_preview';
				// }
			}
		} else {
			$resume_id = self::_save_detail_resume($_POST);
			if($resume_id === false){
				$location['resume_id'] = $resume_id;
				$location['action'] = $action;
			} else {
				$location['resume_id'] = $resume_id;
				$location['action'] = 'resume_preview';
			}
		}

		wp_safe_redirect(esc_url_raw(add_query_arg($location)));
		exit;
	}
	
	public static function preview_resume_action(){
		if(!Noo_Member::is_logged_in()){
			wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'login')));
			return;
		}
		
		if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) )
			return;
		
		if(!isset($_POST['page_id']))
			return;
			
		$page_temp = get_page_template_slug($_POST['page_id']);
		if('page-post-resume.php' !== $page_temp)
			return;
		
		if ( empty( $_POST[ 'action' ] ) || 'resume_preview' !== $_POST[ 'action' ] || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'noo-post-resume' ) ) {
			return;
		}

		$resume_id = isset($_POST['resume_id']) ? $_POST['resume_id'] : '';
		if( empty( $resume_id ) ) {
			return;
		}

		$candidate_id = isset($_POST['candidate_id']) ? $_POST['candidate_id'] : '';
		if( empty( $candidate_id ) ) {
			return;
		}

		if( !Noo_Member::can_post_resume($candidate_id) ) {
			return;
		}

		if( !Noo_Member::is_resume_owner( $candidate_id, $resume_id ) ) {
			return;
		}

		$resume = array(
			'ID'     => intval( $resume_id ),
			'post_status'	 => 'publish',
		);

		wp_update_post($resume);

		// Add email
		Noo_Resume::notify_candidate($resume_id);

		wp_safe_redirect(Noo_Member::get_endpoint_url('manage-resume'));
		die;
	}

	private static function _save_resume($args=''){
		try{
			$defaults=array(
				'candidate_id'=>'',
				'resume_id'=>'',
				'title'=>'',
				'desc'=>'',
				'status'=>'pending',
			);
			$args = wp_parse_args($args,$defaults);
			if( empty($args['candidate_id']) ) {
				noo_message_add(__('There\'s an unknown error. Please retry or contact Administrator.', 'noo'), 'error');
				return false;
			}

			if( !Noo_Member::can_post_resume($args['candidate_id']) ) {
				noo_message_add(__('Sorry, you can\'t post resume.', 'noo'), 'error');
				return false;
			}

			if( !empty($args['resume_id']) && !Noo_Member::is_resume_owner( $args['candidate_id'], $args['resume_id'] ) ) {
				noo_message_add(__('Sorry, you can\'t edit this resume.', 'noo'), 'error');
				return false;
			}

			$no_html = self::$no_html;

			$resume = array(
				'post_title'     => wp_kses( $args['title'], $no_html ),
				'post_content'   => wp_kses( $args['desc'], self::$allowed_html ),
				'post_type'      => 'noo_resume',
				'post_status'	 => wp_kses( $args['status'], $no_html ),
				'post_author'	 => absint( $args['candidate_id'] )
			);
			if( empty($resume['post_title']) ) {
				noo_message_add(__('Resume need a title.', 'noo'), 'error');
				return false;
			}

			if(!empty($args['resume_id'])){
				$resume['ID'] = intval( $args['resume_id'] );
				$post_id = wp_update_post($resume);
			}else{
				$post_id = wp_insert_post($resume);
			}
			if(!is_wp_error($post_id) && $post_id){

				$default_fields = Noo_Resume::get_default_fields();
				$custom_fields = noo_get_custom_fields( get_option( 'noo_resume' ), 'noo_resume_custom_fields_' );
				$fields = array_merge( array_diff_key($default_fields, $custom_fields), $custom_fields );

				if($fields) {
					foreach ($fields as $field) {
						if( !isset( $field['name'] ) || empty( $field['name'] )) {
							continue;
						}

						$id = '_noo_resume_field_'.sanitize_title($field['name']);
						if( array_key_exists($field['name'], $default_fields) ) {
							if( isset( $field['is_disabled'] ) && ($field['is_disabled'] == 'yes') ) {
								continue;
							}
							$id = $field['name'];
						}

						$value = isset( $args[$id] ) ? wp_kses( $args[$id], $no_html ) : '';
						if( $id == '_experience_year' ) {
							$value = absint( $value );
							$value = ( $value == 0 ) ? '' : $value;
						}
						if( $id == '_job_category' || $id == '_job_location' ) {
							$value = json_encode($value);
						}

						update_post_meta($post_id, $id, $value);
						
					}
				}

				update_post_meta($post_id, '_noo_file_cv', wp_kses( $args['file_cv'], $no_html ));
				update_post_meta($post_id, '_noo_url_video', wp_kses( $args['url_video'], $no_html ));

				// if( count_user_posts( $resume['post_author'], 'noo_resume' ) == 1 && absint( Noo_Resume::get_setting('max_viewable_resumes', 1) ) > 0 ) {
				// 	update_post_meta( $post_id, '_viewable', 'yes' );
				// }

				do_action('noo_save_resume',$post_id);
			} else {
				noo_message_add(__('There\'s an unknown error. Please retry or contact Administrator.', 'noo'), 'error');
				return false;
			}
			do_action('noo_after_save_resume',$post_id);
			return $post_id;
		}catch (Exception $e){
			throw new Exception($e->getMessage());
		}
	}
	
	private static function _save_detail_resume($args=''){
		try{
			$defaults=array(
				'resume_id'=>'',
				'_education_school'=>'',
				'_education_qualification'=>'',
				'_education_date'=>'',
				'_education_school'=>'',
				'_education_note'=>'',
				'_experience_employer'=>'',
				'_experience_job'=>'',
				'_experience_date'=>'',
				'_experience_note'=>'',
				'_skill_name'=>'',
				'_skill_percent'=>'',
			);
			$args = wp_parse_args($args,$defaults);

			$no_html = self::$no_html;

			if( empty($args['candidate_id']) || !is_numeric($args['candidate_id']) || empty($args['resume_id']) || !is_numeric($args['resume_id'])  ) {
				noo_message_add(__('There\'s an unknown error. Please retry or contact Administrator.', 'noo'), 'error');
				return false;
			}

			if( !Noo_Member::can_post_resume( $args['candidate_id']) ) {
				noo_message_add(__('Sorry, you can\'t post resume.', 'noo'), 'error');
				return false;
			}

			if( !Noo_Member::is_resume_owner( $args['candidate_id'], $args['resume_id'] ) ) {
				noo_message_add(__('Sorry, you can\'t edit this resume.', 'noo'), 'error');
				return false;
			}

			if( Noo_Resume::get_setting('enable_education', '1') ) {
				$education_school = $args['_education_school'];
				$education_qualification = $args['_education_qualification'];
				$education_date = $args['_education_date'];
				$education_note = $args['_education_note'];

				if( isset( $education_school ) && !empty( $education_school ) ) {
					$education_count = count( $education_school );
					for( $index = 0; $index < $education_count; $index++ ) {
						$education_school[$index] = addcslashes( stripslashes( wp_kses( $education_school[$index], $no_html ) ), '"' );
						$education_qualification[$index] = addcslashes( stripslashes( wp_kses( $education_qualification[$index], $no_html ) ), '"' );
						$education_date[$index] = addcslashes( stripslashes( wp_kses( $education_date[$index], $no_html ) ), '"' );
						$education_note[$index] = addcslashes( stripslashes( htmlentities( wp_kses( $education_note[$index], self::$allowed_html ), ENT_QUOTES ) ), '"' );
					}
				}

				update_post_meta($args['resume_id'], '_education_school', json_encode($education_school, JSON_UNESCAPED_UNICODE));
				update_post_meta($args['resume_id'], '_education_qualification', json_encode($education_qualification, JSON_UNESCAPED_UNICODE));
				update_post_meta($args['resume_id'], '_education_date', json_encode($education_date, JSON_UNESCAPED_UNICODE));
				update_post_meta($args['resume_id'], '_education_note', json_encode($education_note, JSON_UNESCAPED_UNICODE));
			}

			if( Noo_Resume::get_setting('enable_experience', '1') ) {
				$experience_employer = $args['_experience_employer'];
				$experience_job = $args['_experience_job'];
				$experience_date = $args['_experience_date'];
				$experience_note = $args['_experience_note'];

				if( isset( $experience_employer ) && !empty( $experience_employer ) ) {
					$experience_count = count( $experience_employer );
					for( $index = 0; $index < $experience_count; $index++ ) {
						$experience_employer[$index] = addcslashes( stripslashes( wp_kses( $experience_employer[$index], $no_html ) ), '"' );
						$experience_job[$index] = addcslashes( stripslashes( wp_kses( $experience_job[$index], $no_html ) ), '"' );
						$experience_date[$index] = addcslashes( stripslashes( wp_kses( $experience_date[$index], $no_html ) ), '"' );
						$experience_note[$index] = addcslashes( stripslashes( htmlentities( wp_kses( $experience_note[$index], self::$allowed_html ), ENT_QUOTES ) ), '"' );
					}
				}

				update_post_meta($args['resume_id'], '_experience_employer', json_encode($experience_employer, JSON_UNESCAPED_UNICODE));
				update_post_meta($args['resume_id'], '_experience_job', json_encode($experience_job, JSON_UNESCAPED_UNICODE));
				update_post_meta($args['resume_id'], '_experience_date', json_encode($experience_date, JSON_UNESCAPED_UNICODE));
				update_post_meta($args['resume_id'], '_experience_note', json_encode($experience_note, JSON_UNESCAPED_UNICODE));
			}

			if( Noo_Resume::get_setting('enable_skill', '1') ) {
				$skill_name = $args['_skill_name'];
				$skill_percent = $args['_skill_percent'];

				if( isset( $skill_name ) && !empty( $skill_name ) ) {
					$skill_count = count( $skill_name );
					for( $index = 0; $index < $skill_count; $index++ ) {
						$skill_name[$index] = addcslashes( stripslashes( wp_kses( $skill_name[$index], $no_html ) ), '"' );
						$skill_percent[$index] = addcslashes( stripslashes( wp_kses( $skill_percent[$index], $no_html ) ), '"' );
					}
				}

				update_post_meta($args['resume_id'], '_skill_name', json_encode($skill_name, JSON_UNESCAPED_UNICODE));
				update_post_meta($args['resume_id'], '_skill_percent', json_encode($skill_percent, JSON_UNESCAPED_UNICODE));
			}

			do_action('noo_save_detail_resume',$args['resume_id']);
			return $args['resume_id'];
		}catch (Exception $e){
			throw new Exception($e->getMessage());
		}
	}

	private static function _save_job_alert($args=''){
		try{
			$defaults=array(
				'candidate_id'=>get_current_user_id(),
				'job_alert_id'=>'',
				'title'=>'',
				'keywords'=>'',
				'job_location'=>'',
				'job_category'=>'',
				'job_type'=>'',
				'status'=>'publish',
			);
			$args = wp_parse_args($args,$defaults);
			if( empty($args['candidate_id']) ) {
				noo_message_add(__('There\'s an unknown error. Please retry or contact Administrator.', 'noo'), 'error');
				return false;
			}

			if( !Noo_Member::is_logged_in() ) {
				noo_message_add(__('Sorry, you can\'t post job_alert.', 'noo'), 'error');
				return false;
			}

			if( !empty($args['job_alert_id']) && $args['candidate_id'] != get_post_field( 'post_author', $args['job_alert_id'] ) ) {
				noo_message_add(__('Sorry, you can\'t edit this job alert.', 'noo'), 'error');
				return false;
			}

			$no_html = self::$no_html;

			$candidate_id = intval( $args['candidate_id'] );

			$job_alert = array(
				'post_title'     => wp_kses( $args['title'], $no_html ),
				'post_type'      => 'noo_job_alert',
				'post_status'	 => wp_kses( $args['status'], $no_html ),
				'post_author'	 => $candidate_id
			);
			if( empty($job_alert['post_title']) ) {
				noo_message_add(__('Job Alert need a name.', 'noo'), 'error');
				return false;
			}

			if(!empty($args['job_alert_id'])){
				$job_alert['ID'] = intval( $args['job_alert_id'] );
				if( !Noo_Member::is_job_alert_owner( $candidate_id, $job_alert['ID'] ) ) {
					noo_message_add(__('Sorry, you can\'t edit this job alert.', 'noo'), 'error');
					return false;
				}
				$post_id = wp_update_post($job_alert);
			}else{
				$post_id = wp_insert_post($job_alert);
			}

			if(!is_wp_error($post_id)){
				update_post_meta($post_id, '_keywords', wp_kses( $args['keywords'], $no_html ));
				update_post_meta($post_id, '_job_location', json_encode(wp_kses( $args['job_location'], $no_html )));
				update_post_meta($post_id, '_job_category', json_encode(wp_kses( $args['job_category'], $no_html )));
				update_post_meta($post_id, '_job_type', wp_kses( $args['job_type'], $no_html ));

				$frequency = wp_kses( $args['frequency'], $no_html );
				$old_frequency = noo_get_post_meta( $post_id, '_frequency' );
				if( $frequency != $old_frequency ) {
					update_post_meta($post_id, '_frequency', $frequency);

					// Schedule new alert
					Noo_Job_Alert::set_alert_schedule( $post_id, $frequency );
				}

				do_action('noo_save_job_alert',$post_id);
			} else {
				noo_message_add(__('There\'s an unknown error. Please retry or contact Administrator.', 'noo'), 'error');
				return false;
			}


			do_action('noo_after_save_job_alert',$post_id);
			return $post_id;
		}catch (Exception $e){
			throw new Exception($e->getMessage());
		}
	}

	public static function manage_resume_action(){
		if(!is_user_logged_in())
			return;
		$action = self::current_action();
		if ( ! empty( $action ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'resume-manage-action' ) ) {
			$resume_id = '';
			if ( isset( $_REQUEST['resume_id'] ) ) {
				$resume_id = absint( $_REQUEST['resume_id'] );
			} elseif ( !empty( $_REQUEST['id'] ) ) {
				$resume_id = absint( $_REQUEST['id']);
			}

			if( empty( $resume_id ) ) {
				wp_die( __('There\'s an unknown error. Please retry or contact Administrator.', 'noo') );
			}
			try {
				switch ($action){
					case 'edit':
						break;
					case 'toggle_viewable':
						$resume = get_post($resume_id);
						if ( empty( $resume ) || $resume->post_type !== 'noo_resume' ) {
							noo_message_add( __( 'Can not find this resume.', 'noo' ), 'error' );
							break;
						}
						if ( !Noo_Member::is_resume_owner( get_current_user_id(), $resume_id ) ) {
							noo_message_add( __( 'You can not edit this resume.', 'noo' ), 'error' );
							break;
						}
						$current_viewable = noo_get_post_meta( $resume_id, '_viewable', '' );
						if( $current_viewable == 'yes' ) {
							update_post_meta( $resume_id, '_viewable', 'no' );
						} else {
							$max_viewable_resumes = absint( Noo_Resume::get_setting('max_viewable_resumes', 1) );
							$viewable_resumes = absint( Noo_Resume::count_viewable_resumes( get_current_user_id() ) );

							if( $viewable_resumes >= $max_viewable_resumes ) {
								noo_message_add( sprintf( __('You already have %d viewable resume(s).','noo'), $max_viewable_resumes ), 'error' );
							}

							update_post_meta( $resume_id, '_viewable', 'yes' );
						}

						noo_message_add(sprintf(__('Resume viewable changed successfully.','noo'),$deleted));
						do_action('manage_resume_action_viewable',$resume_id);
						wp_safe_redirect( Noo_Member::get_endpoint_url('manage-resume') );
						break;
					case 'delete':
						$resume = get_post($resume_id);
						if ( empty( $resume ) || $resume->post_type !== 'noo_resume' ) {
							noo_message_add( __( 'Can not find this resume.', 'noo' ), 'error' );
							break;
						}
						if ( !Noo_Member::is_resume_owner( get_current_user_id(), $resume_id ) ) {
							noo_message_add( __( 'You can not delete this resume.', 'noo' ), 'error' );
							break;
						}
						if ( !wp_delete_post($resume_id) ) {
							noo_message_add( __('Error in deleting.','noo'), 'error' );
						}

						noo_message_add( __('Resume deleted successfully.','noo') );
						do_action('manage_resume_action_delete',$resume_id);
					break;
				}

				wp_safe_redirect( Noo_Member::get_endpoint_url('manage-resume') );
				die;
			}catch (Exception $e){
				throw new Exception($e->getMessage());
			}
		}
	}

	public static function delete_bookmark_action(){
		if(!is_user_logged_in())
			return;
		$action = self::current_action();
		if ( ! empty( $action ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'bookmark-job-manage-action' ) ) {
			$job_id = '';
			if ( isset( $_REQUEST['job_id'] ) ) {
				$job_id = absint( $_REQUEST['job_id'] );
			}

			if( empty( $job_id ) ) {
				noo_message_add( __('There\'s an unknown error. Please retry or contact Administrator.', 'noo'), 'error' );
			}
			try {
				switch ($action){
					case 'delete_bookmark':
						$user_id = get_current_user_id();
						$job = get_post($job_id);
						if ( empty( $job ) || $job->post_type !== 'noo_job' ) {
							noo_message_add( __( 'Can not find this job.', 'noo' ), 'error' );
							break;
						}

						if ( Noo_Job::clear_bookmark_job($user_id, $job_id) ) {
							noo_message_add( __( 'Bookmark cleared.', 'noo' ), 'success' );
						} else {
							noo_message_add(  __('There\'s an unknown error. Please retry or contact Administrator.', 'noo'), 'error' );
						}
						break;
				}

				wp_safe_redirect( Noo_Member::get_endpoint_url('bookmark-job') );
				die;
			}catch (Exception $e){
				throw new Exception($e->getMessage());
			}
		}
	}
	
	public static function current_action(){
		if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
			return $_REQUEST['action'];
		
		if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] )
			return $_REQUEST['action2'];
	}
	
	public static function save_company($args='', $user_ID = null){
		$defaults=array(
			'company_id'=>'',
			'company_name'=>'',
			'company_website'=>'',
			'company_desc'=>'',
			'company_logo'=>'',
			'cover_image'=>'',
			'company_googleplus'=>'',
			'company_twitter'=>'',
			'company_facebook'=>'',
			'company_linkedin'=>'',
			'company_instagram'=>'',
			'id_facebook'=>'',
			'id_google'=>'',
			'id_linkedin'=>'',
		);

		$no_html = self::$no_html;

		$company_name = isset( $args['company_name'] ) ? wp_kses( $args['company_name'], $no_html ) : '';
		if( empty( $company_name ) ) {
			noo_message_add(__('Company Name can\'t be blank.', 'noo'), 'error');
			return false;
		}
		$args = wp_parse_args($args,$defaults);
		$company_data = array(
			'post_title'     => $company_name,
			'post_content'   => wp_kses( $args['company_desc'], self::$allowed_html ),
			'post_type'      => 'noo_company',
			'comment_status' => 'closed',
			'post_status'	 => 'publish',
		);
		
		if(!empty($args['company_id'])){
			$company_data['ID'] = $args['company_id'];
			$company_id = wp_update_post($company_data);
		}else{
			$company_id = wp_insert_post($company_data);
		}
		
		if(!is_wp_error($company_id)){
			update_user_meta(get_current_user_id(), 'employer_company', $company_id);
			update_post_meta($company_id, '_website', wp_kses( $args['company_website'], $no_html ) );
			update_post_meta($company_id, '_logo', wp_kses( $args['company_logo'], $no_html ) );
			update_post_meta($company_id, '_cover_image', wp_kses( $args['cover_image'], $no_html ) );
			update_post_meta($company_id, '_googleplus', wp_kses( $args['company_googleplus'], $no_html ) );
			update_post_meta($company_id, '_facebook', wp_kses( $args['company_facebook'], $no_html ) );
			update_post_meta($company_id, '_twitter', wp_kses( $args['company_twitter'], $no_html ) );
			update_post_meta($company_id, '_linkedin', wp_kses( $args['company_linkedin'], $no_html ) );
			update_post_meta($company_id, '_instagram', wp_kses( $args['company_instagram'], $no_html ) );

			if ( !empty( $user_ID ) ) :
				
				update_user_meta( $user_ID,'id_facebook', absint( $args['id_facebook'] ) );
				update_user_meta( $user_ID,'id_google', absint( $args['id_google'] ) );
				update_user_meta( $user_ID,'id_linkedin', absint( $args['id_linkedin'] ) );

			endif;
			
			do_action('noo_save_company',$company_id);
			return $company_id;
		} else {
			noo_message_add( $company_id->get_error_message(), 'error' );
	
			return false;
		}
		return $company_id;
	}
}
Noo_Form_Handler::init();
endif;