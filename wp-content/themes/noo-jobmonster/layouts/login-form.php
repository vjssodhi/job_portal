<?php
$submit_label = empty( $submit_label ) ? __('Sign In', 'noo') : $submit_label;
$prefix = uniqid();
$redirect_to = isset($_GET['redirect_to']) && !empty($_GET['redirect_to']) ? $_GET['redirect_to'] : noo_current_url();
$redirect_to = esc_url( apply_filters( 'noo_login_redirect', add_query_arg( array( 'logged_in' => 1 ), $redirect_to ) ) ); // This parameter help resolve the issue with Cache plugins

$user_login = isset($_REQUEST['log']) ? wp_unslash($_REQUEST['log']) : '';
$rememberme = ! empty( $_REQUEST['rememberme'] );
?>

<?php if ( Noo_Member::get_setting('login_using_social') ) : ?>
	<div class="form-group row login-socical">
	    <div class="col-sm-9 col-sm-offset-3">
	    	<?php 
	    		$id_facebook = uniqid();
	    		$id_google = uniqid();
	    		$linkedin = uniqid();
	    		$setting_facebook = Noo_Member::get_setting('id_facebook', '');
	    		$setting_google = Noo_Member::get_setting('google_client_id', '');
	    		$setting_linkedin = Noo_Job::get_setting('noo_job_linkedin','api_key', '');
	    	?>
	    	<?php if( !empty( $setting_facebook ) ) : ?>
	    		
	    		<div class="button_socical fb">
	    			<i data-id="<?php echo $id_facebook; ?>" id="<?php echo $id_facebook; ?>" class="fa fa-facebook-square"></i>
		    		<em data-id="<?php echo $id_facebook; ?>" class="fa-facebook-square"><?php _e('Login with Facebook', 'noo'); ?></em>
	    		</div>
	    		
	    	<?php endif; ?>
	    	<?php if( !empty( $setting_google ) ) : ?>
	    		<div class="button_socical gg">
	    			<i data-id="<?php echo $id_google; ?>" id="i_<?php echo $id_google; ?>" class="fa fa-google-plus"></i>
		    		<em data-id="<?php echo $id_google; ?>" id="<?php echo $id_google; ?>" class="fa-google-plus"><?php _e('Login with Google', 'noo'); ?></em>
	    		</div>
	    	<?php endif; ?>
	    	<?php if( !empty( $setting_linkedin ) ) : ?>
	    		<div class="button_socical linkedin">
	    			<i data-id="<?php echo $linkedin; ?>" id="<?php echo $linkedin; ?>" class="fa fa-linkedin-square"></i>
		    		<em data-id="<?php echo $linkedin; ?>" class="fa-linkedin-square"><?php _e('Login with LinkedIn', 'noo'); ?></em>
	    		</div>
			<?php endif; ?>
		</div>
	</div>
<?php endif; ?>
<form style="max-width: 600px; margin: auto" class="noo-ajax-login-form form-horizontal" action="<?php echo wp_login_url($redirect_to); ?>">
	<div style="display: none">
		<input type="hidden" name="action" value="noo_ajax_login">
		<input type="hidden" class="security" name="security" value="<?php echo wp_create_nonce( 'noo-ajax-login' ) ?>" />
	</div>
	<div class="form-group text-center noo-ajax-result" style="display: none"></div>
	<?php do_action( 'noo_login_form_start' ); ?>
	
	<div class="form-group row">
	    <label for="<?php echo $prefix; ?>_log" class="col-sm-3 control-label">
	    	<?php if( Noo_Member::get_setting('register_using_email') ) : ?>
	    		<?php _e('Email','noo')?></label>
	    	<?php else : ?>
	    		<?php _e('Username','noo')?></label>
	    	<?php endif; ?>
	    <div class="col-sm-9">
	      <input type="text" class="log form-control" id="<?php echo $prefix; ?>_log" name="log" required value="<?php echo $user_login; ?>" placeholder="<?php echo ( Noo_Member::get_setting('register_using_email') ?  esc_attr__('Email','noo') : esc_attr__('Username','noo') ) ;?>">
	    </div>
	 </div>
	 <div class="form-group row">
	    <label for="<?php echo $prefix; ?>_pwd" class="col-sm-3 control-label"><?php _e('Password','noo')?></label>
	    <div class="col-sm-9">
	      <input type="password" id="<?php echo $prefix; ?>_pwd" class="pwd form-control" required value="" name="pwd" placeholder="<?php echo esc_attr__('Password','noo')?>">
	    </div>
	 </div>

	<?php do_action( 'noo_login_form' ); ?>

	<div class="form-group row">
	    <div class="col-sm-9 col-sm-offset-3">
	    	<div class="checkbox">
	    		<div class="form-control-flat"><label class="checkbox"><input type="checkbox" id="<?php echo $prefix; ?>_rememberme" class="rememberme" name="rememberme" <?php checked( $rememberme ); ?> value="forever"><i></i> <?php _e('Remember Me', 'noo'); ?></label></div>
		    </div>
		</div>
	</div>
	<div class="form-actions form-group text-center">
	 	<?php if( !empty($redirect_to) ) :?>
	 		<input type="hidden" class="redirect_to" name="redirect_to" value="<?php echo esc_url( urldecode( $redirect_to ) ); ?>" />
	 	<?php endif; ?>
	 	<button type="submit" class="btn btn-primary"><?php echo esc_html($submit_label)?></button>
	 	<div class="login-form-links">
	 		<span><a href="<?php echo wp_lostpassword_url()?>"><i class="fa fa-question-circle"></i> <?php _e('Forgot Password?','noo')?></a></span>
	 		<?php if(Noo_Member::can_register()):?>
	 		<span><?php echo sprintf(__('Don\'t have an account yet? <a href="%s" class="member-register-link" >Register Now <i class="fa fa-long-arrow-right"></i></a>','noo'),wp_registration_url())?></span>
	 		<?php endif;?>
	 	</div>
	 </div>
	 <?php do_action( 'noo_login_form_end' ); ?>
</form>