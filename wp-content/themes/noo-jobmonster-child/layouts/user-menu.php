<?php if(Noo_Member::is_logged_in()):?>
	<li class="menu-item-has-children nav-item-member-profile login-link align-right">
		<a id="thumb-info" href="<?php echo Noo_Member::get_member_page_url(); ?>">
			<span class="profile-name"><?php echo Noo_Member::get_display_name(); ?></span>
			<span class="profile-avatar"><?php echo noo_get_avatar( get_current_user_id(), 40 ); ?></span>
			<?php echo user_notifications_number(); ?>
		</a>
		<ul class="sub-menu">
			<?php if(Noo_Member::is_employer()):?>
				<li class="menu-item" ><a href="<?php echo Noo_Member::get_post_job_url()?>"><i class="fa fa-edit"></i> <?php _e('Post a Job','noo')?></a></li>
				<li class="menu-item" ><a href="<?php echo Noo_Member::get_endpoint_url('manage-application')?>" style="white-space: nowrap;"><i class="fa fa-newspaper-o"></i> <?php _e('Manage Application','noo')?></a></li>
				<li class="menu-item" ><a href="<?php echo Noo_Member::get_endpoint_url('manage-job')?>"><i class="fa fa-file-text-o"></i> <?php _e('Manage Job','noo')?></a></li>
				<?php do_action( 'noo-member-employer-menu' ); ?>
				<li class="divider" role="presentation"></li>
				<?php //if(Noo_Job::use_woocommerce_package()) : ?>
					<li class="menu-item" ><a href="<?php echo Noo_Member::get_endpoint_url('manage-plan')?>"><i class="fa fa-file-text-o"></i> <?php _e('Manage Plan','noo')?></a></li>
				<?php //endif; ?>
				<li class="menu-item" ><a href="<?php echo Noo_Member::get_company_profile_url()?>"><i class="fa fa-users"></i> <?php _e('Company Profile','noo')?></a></li>
			<?php elseif(Noo_Member::is_candidate()):?>
				<li class="menu-item" ><a href="<?php echo Noo_Member::get_post_resume_url()?>"><i class="fa fa-edit"></i> <?php _e('Add User','noo')?></a></li>
				<li class="menu-item" ><a href="<?php echo Noo_Member::get_endpoint_url('manage-resume')?>" style="white-space: nowrap;"><i class="fa fa-file-text-o"></i> <?php _e('Manage User','noo')?></a></li>
				<li class="menu-item" ><a href="<?php echo Noo_Member::get_endpoint_url('manage-job-applied')?>" style="white-space: nowrap;"><i class="fa fa-newspaper-o"></i> <?php _e('Manage Application','noo')?></a></li>
				<li class="menu-item" ><a href="<?php echo Noo_Member::get_endpoint_url('bookmark-job')?>"><i class="fa fa-heart"></i> <?php _e('Bookmarked Jobs','noo')?></a></li>
				<?php if( Noo_Job_Alert::enable_job_alert() ) : ?>
					<li class="menu-item" ><a href="<?php echo Noo_Member::get_endpoint_url('job-alert')?>"><i class="fa fa-bell-o"></i> <?php _e('Jobs Alert','noo')?></a></li>
				<?php endif; ?>
				<?php do_action( 'noo-member-candidate-menu' ); ?>
				<li class="divider" role="presentation"></li>
				<li class="menu-item" ><a href="<?php echo Noo_Member::get_candidate_profile_url('candidate-profile')?>"><i class="fa fa-user"></i> <?php _e('My Profile','noo')?></a></li>
			<?php endif; ?>
			<li class="menu-item" ><a href="<?php echo Noo_Member::get_logout_url() ?>"><i class="fa fa-sign-out"></i> <?php _e('Sign Out','noo')?></a></li>
		</ul>
	</li>
<?php else:?>
	<li class="menu-item nav-item-member-profile login-link align-center">
		<a href="#" class="member-links member-login-link"><i class="fa fa-sign-in"></i>&nbsp;<?php _e('Login', 'noo')?></a>
		<?php
		$setting_facebook = Noo_Member::get_setting('id_facebook', '');
		$setting_google = Noo_Member::get_setting('google_client_id', '');
		$setting_linkedin = Noo_Job::get_setting('noo_job_linkedin','api_key', '');

		if ( Noo_Member::get_setting('login_using_social') && ( !empty( $setting_facebook ) || !empty( $setting_google ) || !empty( $setting_linkedin ) ) ) : ?>
			<ul class="sub-menu login-socical" style="display: none;">
				<?php 
					$id_facebook = uniqid();
					$id_google = uniqid();
					$linkedin = uniqid();
				?>
				<?php if( !empty( $setting_facebook ) ) : ?>
					<li class="button_socical fb">
						<i data-id="<?php echo $id_facebook; ?>" id="<?php echo $id_facebook; ?>" class="fa fa-facebook-square"></i>
						<em data-id="<?php echo $id_facebook; ?>" class="fa-facebook-square"><?php _e('Login with Facebook', 'noo'); ?></em>
					</li>

				<?php endif; ?>
				<?php if( !empty( $setting_google ) ) : ?>
					<li class="button_socical gg">
						<i data-id="<?php echo $id_google; ?>" id="i_<?php echo $id_google; ?>" class="fa fa-google-plus"></i>
						<em data-id="<?php echo $id_google; ?>" id="<?php echo $id_google; ?>" class="fa-google-plus"><?php _e('Login with Google', 'noo'); ?></em>
					</li>
				<?php endif; ?>
				<?php if( !empty( $setting_linkedin ) ) : ?>
					<li class="button_socical linkedin">
						<i data-id="<?php echo $linkedin; ?>" id="<?php echo $linkedin; ?>" class="fa fa-linkedin-square"></i>
						<em data-id="<?php echo $linkedin; ?>" class="fa-linkedin-square"><?php _e('Login with LinkedIn', 'noo'); ?></em>
					</li>
				<?php endif; ?>
			</ul>
		<?php endif; ?>
	</li>
	<?php if( Noo_Member::can_register() ) : ?>
		<li class="menu-item nav-item-member-profile register-link">
			<a class="member-links member-register-link" href="<?php echo esc_url(wp_registration_url())?>"><i class="fa fa-key"></i>&nbsp;<?php _e('Register', 'noo')?></a>
		</li>
	<?php endif; ?>
<?php endif;?>