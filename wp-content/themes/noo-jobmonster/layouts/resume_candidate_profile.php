<?php 
global $post;
$candidate_id = isset($_GET['candidate_id']) ? absint($_GET['candidate_id']) : get_current_user_id();
$enable_upload = Noo_Resume::get_setting('enable_upload_resume', '1');
if( get_the_ID() == Noo_Member::get_member_page_id() ) {
	$candidate_id = get_current_user_id();
} else {
	$resume_id = get_query_var('post_id');
	if( 'noo_resume' == get_post_type( $resume_id ) ) {
		$candidate_id = get_post_field( 'post_author', $resume_id);
	}
}

$candidate = !empty($candidate_id) ? get_userdata($candidate_id) : false;

if( $candidate ) :
	$current_job = get_the_author_meta( 'current_job', $candidate->ID );
	$current_company = get_the_author_meta( 'current_company', $candidate->ID );
	$birthday = get_the_author_meta( 'birthday', $candidate->ID );
	$address = get_the_author_meta( 'address', $candidate->ID );
	$phone = get_the_author_meta( 'phone', $candidate->ID );
	$email = $candidate->user_email;
	$facebook = get_the_author_meta( 'facebook', $candidate->ID );
	$twitter = get_the_author_meta( 'twitter', $candidate->ID );
	$linkedin = get_the_author_meta( 'linkedin', $candidate->ID );
	$behance = get_the_author_meta( 'behance', $candidate->ID );
	$instagram = get_the_author_meta( 'instagram', $candidate->ID );

?>
<div class="resume-candidate-profile">
	<div class="row">
		<div class="col-sm-3 profile-avatar">
			<?php echo noo_get_avatar( $candidate_id, 160); ?>
		</div>
		<div class="col-sm-9 candidate-detail">
			<div class="candidate-title clearfix">
				<h2><?php echo esc_html( $candidate->display_name ); ?></h2>
				<?php if( $candidate_id == get_current_user_id() ) : ?>
					<a class="btn btn-default pull-right" href="<?php echo esc_url( Noo_Member::get_candidate_profile_url('candidate-profile') ); ?>">
						<i class="fa fa-pencil"></i>
						<?php _e('Edit Profile', 'noo'); ?>
					</a>
				<?php endif; ?>
			</div>
			
			<div class="candidate-info">
				<div class="row">
					<?php if( !empty( $current_job ) ) : ?>
						<div class="current_job col-sm-6"><i class="fa fa-suitcase text-primary"></i>&nbsp;&nbsp;<?php echo esc_html($current_job) . ( !empty( $current_company ) ? sprintf( __(' for %s', 'noo'), esc_html($current_company) ) : '' ); ?></div>
					<?php endif; ?>
					<?php if( !empty( $address ) ) : ?>
						<div class="address col-sm-6"><i class="fa fa-map-marker text-primary"></i>&nbsp;&nbsp;<?php echo esc_html($address); ?></div>
					<?php endif; ?>
					<?php if( !empty( $birthday ) ) : ?>
						<div class="birthday col-sm-6"><i class="fa fa-birthday-cake text-primary"></i>&nbsp;&nbsp;<?php echo esc_html(date_i18n(get_option('date_format'), strtotime($birthday) )); ?></div>
					<?php endif; ?>
					<?php if( !empty( $phone ) ) : ?>
						<div class="phone col-sm-6"><i class="fa fa-phone text-primary"></i>&nbsp;&nbsp;<?php echo esc_html($phone); ?></div>
					<?php endif; ?>
					<?php if( !empty( $email ) ) : ?>
						<div class="email col-sm-6 pull-right"><a href="mailto:<?php echo esc_attr($email); ?>"><i class="fa fa-envelope text-primary"></i>&nbsp;&nbsp;<?php echo esc_html($email); ?></a></div>
					<?php endif; ?>
				</div>
				<div class="row">
					<div class="candidate-social col-sm-6 pull-left" >
					<?php
						if ( $enable_upload ) {
							$file_cv = noo_json_decode( noo_get_post_meta( $post->ID, '_noo_file_cv' ) );
							// echo($file_cv[0]); die;
							if ( !empty($file_cv[0]) ) {
								echo '<div class="download pull-left">';
								echo '<i class="fa fa-download text-primary"></i>';
								echo '<a target="_blank" href="' . noo_get_file_upload( $file_cv[0] ) .'" title="' . __('Download My Attachment', 'noo') . '">' . __('Download My Attachment', 'noo') . '</a>';
								echo '</div>';
							}
						}
					?>
					</div>
					<div class="candidate-social col-sm-6 pull-right" >
						<?php if( !empty( $facebook ) ) : ?>
							<a class="noo-icon fa fa-facebook" href="<?php echo esc_url($facebook); ?>" target="_blank"></a>
						<?php endif; ?>
						<?php if( !empty( $twitter ) ) : ?>
							<a class="noo-icon fa fa-twitter" href="<?php echo esc_url($twitter); ?>" target="_blank"></a>
						<?php endif; ?>
						<?php if( !empty( $linkedin ) ) : ?>
							<a class="noo-icon fa fa-linkedin" href="<?php echo esc_url($linkedin); ?>" target="_blank"></a>
						<?php endif; ?>
						<?php if( !empty( $behance ) ) : ?>
							<a class="noo-icon fa fa-behance" href="<?php echo esc_url($behance); ?>" target="_blank"></a>
						<?php endif; ?>
						<?php if( !empty( $instagram ) ) : ?>
							<a class="noo-icon fa fa-instagram" href="<?php echo esc_url($instagram); ?>" target="_blank"></a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		
	</div>
</div>
<?php else: 
	echo '<h2 class="text-center" style="min-height:200px">'.__('Can find this Candidate !','noo').'</h2>';
endif; ?>
<hr/>