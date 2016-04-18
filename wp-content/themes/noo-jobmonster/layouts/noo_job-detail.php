<?php
	$noo_single_jobs_layout = noo_get_option('noo_single_jobs_layout', 'right_company');
	$preview_class = '';
	if( $in_preview ) {
		if ($noo_single_jobs_layout == 'left_company') {
			$preview_class = ' col-md-8 left-sidebar';
		} elseif ($noo_single_jobs_layout == 'right_company') {
			$preview_class = ' col-md-8';
		} else {
			$preview_class = ' col-md-12';
		}
	}
?>

<div class="<?php if( $in_preview != true ) noo_main_class(); else echo $preview_class; ?>" role="main">
	<?php if( $in_preview == true ) : ?>
		<div class="job-head">
			<div class="job-title">
				<?php the_title( ); ?>
			</div>
			<div class="job-sub">
				<?php Noo_Job::noo_contentjob_meta(array('show_company' => false, 'show_closing_date' => true, 'show_category' => true)); ?>
			</div>
		</div>
	<?php endif; ?>
	<div class="job-desc" itemprop="description">
		<?php
			$default_fields = Noo_Job::get_default_fields();
			$custom_fields = noo_get_custom_fields( Noo_Job::get_setting('noo_job_custom_field', array()), 'noo_jobs_custom_fields_' );
			$fields = array_merge( array_diff_key($default_fields, $custom_fields), $custom_fields );
			$field_pos = Noo_Job::get_custom_fields_option('display_position', 'after');

			if( $field_pos != 'before' ) {
				the_content();
			}
		?>
		<?php if(!empty($fields)):?>
		<div class="job-custom-fields">
			<?php 
			foreach ((array) $fields as $field):
				if( !isset( $field['name'] ) || empty( $field['name'] )) continue;
				$field['type'] = isset( $field['type'] ) ? $field['type'] : 'text';
				$id = '_noo_job_field_'.sanitize_title($field['name']);
				if( array_key_exists($field['name'], $default_fields) ) {
					if( isset( $field['is_disabled'] ) && ($field['is_disabled'] == 'yes') )
						continue;
					$id = $field['name'];
				}
				$label = isset( $field['label_translated'] ) ? $field['label_translated'] : $field['label'];
				$value = noo_get_post_meta(get_the_ID(), $id, '');
				noo_display_custom_fields( $field, $value, $id, $field['type'], $label );
			endforeach; ?>
		</div>
		<?php endif;?>
		<?php
			if( $field_pos == 'before' ) {
				the_content();
			}
		?>
	</div>
	<div class="job-action hidden-print clearfix">
		<?php if( 'expired' == get_post_status( $job_id ) ) : ?>
			<div class="noo-messages noo-message-error">
				<ul>
					<li><?php echo __('This job is expired!', 'noo'); ?></li>
				</ul>
			</div>
		<?php else : ?>
			<?php if( Noo_Member::is_candidate() ) : ?>
				<div class="noo-ajax-result" style="display: none"></div>
			<?php endif; ?>
			<?php if($in_preview != true) :
				$login_apply = Noo_Job::get_setting('noo_job_linkedin', 'member_apply',Noo_Job::get_setting('noo_job_general', 'member_apply','')) == 'yes';
				$is_candidate = Noo_Member::is_candidate();
				$has_applied = $is_candidate ? Noo_Application::has_applied( 0, $job_id ) : false;
				$custom_apply_link = Noo_Job::get_setting('noo_job_linkedin', 'custom_apply_link' );
				$apply_url = !empty( $custom_apply_link ) ? noo_get_post_meta( $job_id, '_custom_application_url', '' ) : '';
			?>
				<?php if( $login_apply && !$is_candidate ) : ?>
					<a class="btn btn-primary member-login-link" data-target="#memberModalLogin" href="#" data-toggle="modal"><?php _e('Login to apply','noo');?></a>
				<?php else : ?>
					<?php if( $has_applied ) : ?>
						<div class="noo-messages noo-message-notice pull-left">
							<ul>
								<li><?php echo __('You have already applied for this job', 'noo'); ?></li>
							</ul>
						</div>
					<?php else: ?>
						<?php if( empty( $apply_url ) ) : ?>
							<a class="btn btn-primary" data-target="#applyJobModal" href="#" data-toggle="modal"><?php _e('Apply for this job','noo');?></a>
							<?php //noo_get_layout('apply_job_form'); 
								include(locate_template("layouts/apply_job_form.php"));
							?>
						<?php else : ?>
							<a class="btn btn-primary" href="<?php echo esc_url( $apply_url ); ?>" target="_blank" ><?php _e('Apply for this job','noo');?></a>
						<?php endif; ?>
						<?php 
							if(Noo_Job::get_setting('noo_job_linkedin','use_apply_with_linkedin') == 'yes'):
								// noo_get_layout('apply_job_via_linkedin_form');
								include(locate_template("layouts/apply_job_via_linkedin_form.php"));
							endif;
						?>
					<?php endif; ?>
				<?php endif; ?>
			<?php endif;?>
			<?php if( Noo_Member::is_candidate() ) : ?>
				<a class="bookmark-job <?php echo ( Noo_Job::is_bookmarked(0, $job_id) ? 'bookmarked' : '' ); ?> pull-right" href="javascript:void(0);" data-toggle="tooltip" data-job-id="<?php echo esc_attr($job_id); ?>" data-action="noo_bookmark_job" data-security="<?php echo wp_create_nonce( 'noo-bookmark-job' );?>" title="<?php _e('Bookmark Job', 'noo'); ?>"><i class="fa fa-heart"></i></a>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
		//  -- Check display company
		if ( $noo_single_jobs_layout == 'left_sidebar' || $noo_single_jobs_layout == 'fullwidth' || $noo_single_jobs_layout == 'sidebar' ) :
		// -- check option turn on/off show soccical
			if( noo_get_option('noo_job_social', true) ) :
				Noo_Job::social_share($job_id, __('Share this job','noo'));
			endif;

		// -- check option turn on/off show company info
			if( noo_get_option('noo_company_info_in_jobs', true) ) :
				Noo_Company::display_sidebar($company_id, true);
			endif;

		endif;
	?>
	<?php if ( $in_preview != true ) : ?>
		<?php if (  noo_get_option( 'noo_job_related', true ) ) : ?>
			<?php Noo_Job::related_jobs($job_id, __('Related Jobs','noo')); ?>
		<?php endif; ?>
		<?php if ( noo_get_option( 'noo_job_comment', false ) && comments_open() ) : ?>
			<?php comments_template( '', true ); ?>
		<?php endif; ?>
	<?php endif; ?>
</div> <!-- /.main -->
<?php if( $noo_single_jobs_layout != 'fullwidth' ) : ?>
<div class="<?php noo_sidebar_class(); ?> hidden-print">
	<div class="noo-sidebar-wrap">
	<?php
		//  -- Check display company
		if ( $noo_single_jobs_layout != 'left_sidebar' && $noo_single_jobs_layout != 'sidebar' ) :

			// -- check option turn on/off show soccical
				if( noo_get_option('noo_job_social', true) ) :
					Noo_Job::social_share($job_id, __('Share this job','noo'));
				endif;

			// -- show company info
				Noo_Company::display_sidebar($company_id, true);

		elseif ( $in_preview != true ) :
			// -- show siderbar
				if ( ! function_exists( 'dynamic_sidebar' ) || ! dynamic_sidebar( ) ) :
					dynamic_sidebar( noo_get_option('noo_single_jobs_sidebar', true) );
				endif;
		endif;
	?>
	</div>
</div>
<?php endif; ?>
