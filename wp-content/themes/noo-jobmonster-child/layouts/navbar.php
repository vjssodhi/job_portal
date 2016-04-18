<?php

$blog_name		= get_bloginfo( 'name' );
$blog_desc		= get_bloginfo( 'description' );
$image_logo		= '';
$mobile_logo	= '';
$page_logo		= '';
if ( noo_get_option( 'noo_header_use_image_logo', false ) ) {
	if ( noo_get_image_option( 'noo_header_logo_image', '' ) !=  '' ) {
		$image_logo = noo_get_image_option( 'noo_header_logo_image', '' );
		$mobile_logo = noo_get_image_option( 'noo_header_logo_mobile_image', $image_logo );
	}
	if( is_page() && noo_get_post_meta(get_the_ID(),'_noo_wp_page_menu_transparent') ) {
		$page_logo = noo_get_post_meta(get_the_ID(),'_noo_wp_page_menu_transparent_logo');
		$page_logo = !empty( $page_logo ) ? wp_get_attachment_url( $page_logo ) : '';
	}
}

?>
<div class="navbar-wrapper">
	<div class="navbar navbar-default <?php echo noo_navbar_class(); ?>" role="navigation">
		<div class="container-boxed max">
			<div class="navbar-header">
				<?php if ( is_front_page() ) : echo '<h1 class="sr-only">' . $blog_name . '</h1>'; endif; ?>
				<a class="navbar-toggle collapsed" data-toggle="collapse" data-target=".noo-navbar-collapse">
					<span class="sr-only"><?php echo __( 'Navigation', 'noo' ); ?></span>
					<i class="fa fa-bars"></i>
				</a>
				<a class="navbar-toggle member-navbar-toggle collapsed" data-toggle="collapse" data-target=".noo-user-navbar-collapse">
					<i class="fa fa-user"></i>
				</a>
				<a href="<?php echo home_url( '/' ); ?>" class="navbar-brand" title="<?php echo esc_attr($blog_desc); ?>">
				<?php echo ( $image_logo == '' ) ? $blog_name : '<img class="noo-logo-img noo-logo-normal" src="' . esc_url($image_logo) . '" alt="' . esc_attr($blog_desc) . '">'; ?>
				<?php echo ( $mobile_logo == '' ) ? '' : '<img class="noo-logo-mobile-img noo-logo-normal" src="' . esc_url($mobile_logo) . '" alt="' . esc_attr($blog_desc) . '">'; ?>
				<?php echo ( $page_logo == '' ) ? '' : '<img class="noo-logo-img noo-logo-floating" src="' . esc_url($page_logo) . '" alt="' . esc_attr($blog_desc) . '">'; ?>
				</a>
			</div> <!-- / .nav-header -->
			<nav class="collapse navbar-collapse noo-user-navbar-collapse">
				<ul class="navbar-nav sf-menu">
					<?php if(Noo_Member::is_logged_in()):?>
						<?php if(Noo_Member::is_employer()):?>
							<li class="menu-item fly-right" ><a href="<?php echo Noo_Member::get_endpoint_url('post-job')?>"><i class="fa fa-edit"></i> <?php _e('Post a Job','noo')?></a></li>
							<?php //if(Noo_Job::use_woocommerce_package()) : ?>
								<li class="menu-item fly-right" ><a href="<?php echo Noo_Member::get_endpoint_url('manage-plan')?>"><i class="fa fa-file-text-o"></i> <?php _e('Manage Plan','noo')?></a></li>
							<?php //endif; ?>
							<li class="menu-item fly-right" ><a href="<?php echo Noo_Member::get_endpoint_url('manage-application')?>" style="white-space: nowrap;"><i class="fa fa-newspaper-o"></i> <?php _e('Manage Application','noo')?></a></li>
							<li class="menu-item fly-right" ><a href="<?php echo Noo_Member::get_endpoint_url('manage-job')?>"><i class="fa fa-file-text-o"></i> <?php _e('Manage Job','noo')?></a></li>
							<li class="menu-item fly-right" ><a href="<?php echo Noo_Member::get_company_profile_url()?>"><i class="fa fa-user"></i> <?php _e('Company Profile','noo')?></a></li>
							<li class="menu-item fly-right" ><a href="<?php echo Noo_Member::get_logout_url() ?>"><i class="fa fa-sign-out"></i> <?php _e('Sign Out','noo')?></a></li>
						<?php elseif(Noo_Member::is_candidate()):?>
							<li class="menu-item fly-right" ><a href="<?php echo Noo_Member::get_post_resume_url()?>"><i class="fa fa-edit"></i> <?php _e('Post a Resume','noo')?></a></li>
							<li class="menu-item fly-right" ><a href="<?php echo Noo_Member::get_endpoint_url('manage-resume')?>" style="white-space: nowrap;"><i class="fa fa-file-text-o"></i> <?php _e('Manage Resume','noo')?></a></li>
							<li class="menu-item fly-right" ><a href="<?php echo Noo_Member::get_endpoint_url('manage-job-applied')?>" style="white-space: nowrap;"><i class="fa fa-newspaper-o"></i> <?php _e('Manage Application','noo')?></a></li>
							<li class="menu-item fly-right" ><a href="<?php echo Noo_Member::get_endpoint_url('bookmark-job')?>"><i class="fa fa-heart"></i> <?php _e('Bookmarked Jobs','noo')?></a></li>
							<?php if( Noo_Job_Alert::enable_job_alert() ) : ?>
								<li class="menu-item fly-right" ><a href="<?php echo Noo_Member::get_endpoint_url('job-alert')?>"><i class="fa fa-bell-o"></i> <?php _e('Jobs Alert','noo')?></a></li>
							<?php endif;?>
							<li class="menu-item fly-right" ><a href="<?php echo Noo_Member::get_endpoint_url('candidate-profile')?>"><i class="fa fa-user"></i> <?php _e('My Profile','noo')?></a></li>
							<li class="menu-item fly-right" ><a href="<?php echo Noo_Member::get_logout_url() ?>"><i class="fa fa-sign-out"></i> <?php _e('Sign Out','noo')?></a></li>
						<?php endif; ?>
					<?php else:?>
						<li class="menu-item" >
							<a href="<?php echo Noo_Member::get_login_url()?>"><i class="fa fa-sign-in"></i> <?php _e('Login', 'noo')?></a>
							<?php if ( Noo_Member::get_setting('login_using_social') ) : ?>

								<!-- <ul class="sub-menu login-socical" style="display: none;"> -->
									<?php 
										$id_facebook = uniqid();
										$id_google = uniqid();
										$linkedin = uniqid();
										$setting_facebook = Noo_Member::get_setting('id_facebook', '');
										$setting_google = Noo_Member::get_setting('google_client_id', '');
										$setting_linkedin = Noo_Job::get_setting('noo_job_linkedin','api_key', '');
									?>
									<?php if( !empty( $setting_facebook ) ) : ?>
										<li class="menu-item button_socical fb">
											<i data-id="<?php echo $id_facebook; ?>" id="<?php echo $id_facebook; ?>" class="fa fa-facebook-square"></i>
											<em data-id="<?php echo $id_facebook; ?>" class="fa-facebook-square"><?php _e('Login with Facebook', 'noo'); ?></em>
										</li>

									<?php endif; ?>
									<?php if( !empty( $setting_google ) ) : ?>
										<li class="menu-item button_socical gg">
											<i data-id="<?php echo $id_google; ?>" id="i_<?php echo $id_google; ?>" class="fa fa-google-plus"></i>
											<em data-id="<?php echo $id_google; ?>" id="<?php echo $id_google; ?>" class="fa-google-plus"><?php _e('Login with Google', 'noo'); ?></em>
										</li>
									<?php endif; ?>
									<?php if( !empty( $setting_linkedin ) ) : ?>
										<li class="menu-item button_socical linkedin">
											<i data-id="<?php echo $linkedin; ?>" id="<?php echo $linkedin; ?>" class="fa fa-linkedin-square"></i>
											<em data-id="<?php echo $linkedin; ?>" class="fa-linkedin-square"><?php _e('Login with LinkedIn', 'noo'); ?></em>
										</li>
									<?php endif; ?>
								<!-- </ul> -->
							<?php endif; ?>
						</li>
						<?php if( Noo_Member::can_register() ) : ?>
							<li class="menu-item" ><a href="<?php echo esc_url(wp_registration_url())?>"><i class="fa fa-key"></i> <?php _e('Register', 'noo')?></a></li>						
						<?php endif;?>
					<?php endif;?>
				</ul>
			</nav>
			<nav class="collapse navbar-collapse noo-navbar-collapse">
	        <?php
				if ( has_nav_menu( 'primary' ) ) :
					wp_nav_menu( array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'navbar-nav sf-menu'
						) );
				else :
					echo '<ul class="navbar-nav nav"><li><a href="' . home_url( '/' ) . 'wp-admin/nav-menus.php">' . __( 'No menu assigned!', 'noo' ) . '</a></li></ul>';
				endif;
			?>
			</nav> <!-- /.navbar-collapse -->
		</div> <!-- /.container-fluid -->
	</div> <!-- / .navbar -->
</div>
