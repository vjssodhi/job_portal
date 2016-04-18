<?php
global $post;
if ( is_user_logged_in() ) {
	$user            = wp_get_current_user();
	$candidate_name  = $user->display_name;
	$candidate_email = $user->user_email;
	
}else{
	$candidate_name  = '';
	$candidate_email = '';
}
$member_apply = Noo_Job::get_setting('noo_job_linkedin', 'member_apply',Noo_Job::get_setting('noo_job_general', 'member_apply',''));
$disable_member_upload = Noo_Job::get_setting('noo_job_linkedin', 'disable_member_upload',Noo_Job::get_setting('noo_job_general', 'member_upload',''));
$show_button = true;

if ( is_user_logged_in() && Noo_Member::is_candidate() ):
	$args = apply_filters( 'noo_application_resume_query_args', array(
		'post_type'=>'noo_resume',
		'posts_per_page' => -1,
		'post_status'=>array('publish'),
		'author'=>get_current_user_id(),
	) );
	$r = new WP_Query($args);
else :
	$r = false;
endif;

$can_upload = $member_apply != 'yes' || $disable_member_upload != 'yes' || ( $r && $r->found_posts );

$allowed_file_types = Noo_Resume::get_setting('extensions_upload_resume', 'doc,pdf');
$allowed_file_types = !empty( $allowed_file_types ) ? explode(',', $allowed_file_types ) : array();
$allowed_exts = array();
foreach ($allowed_file_types as $type) {
	$type = trim($type);
	if( empty( $type ) ) continue;
	$type = substr($type, 0) != '.' ? '.' . $type : $type;
	$allowed_exts[] = $type;
}
?>
<div id="applyJobModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="applyJobModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title" id="applyJobModalLabel"><?php esc_html_e('Apply Job','noo')?></h4>
			</div>
			<div class="modal-body">
				<?php if( $can_upload ) : ?>
					<form id="apply_job_form" name="apply_job_form" class="form-horizontal jform-validate" method="post" enctype="multipart/form-data">
						<div style="display: none">
							<input type="hidden" name="action" value="apply_job"> 
							<input type="hidden" name="job_id" value="<?php echo esc_attr($post->ID)?>">			
							<?php wp_nonce_field('noo-apply-job')?>
						</div>
						<div class="form-group text-center noo-ajax-result" style="display: none"></div>
						<div class="form-group">
							<label for="candidate_name" class="control-label"><?php _e('Name','noo')?></label>
							<input type="text" class="form-control jform-validate" id="candidate_name" value="<?php echo esc_attr($candidate_name)?>" name="candidate_name" autofocus required placeholder="<?php echo esc_attr__('Name','noo')?>">
						</div>
						<div class="form-group">
							<label for="candidate_email" class="control-label"><?php _e('Email','noo')?></label>
							<input type="email" class="form-control jform-validate jform-validate-email" id="candidate_email" value="<?php echo esc_attr($candidate_email)?>" name="candidate_email" required placeholder="<?php echo esc_attr__('Email','noo')?>">
						</div>
						<div class="form-group">
							<label for="application_message" class="control-label"><?php _e('Message','noo')?></label>
							<textarea class="form-control jform-validate" id="application_message" name="application_message" rows="5" placeholder="<?php esc_attr_e('Your cover letter/message sent to the employer','noo')?>"></textarea>
						</div>
						<div class="form-group">
							<label for="application_message" class="control-label"><?php _e('Bid Amount','noo')?></label>
							<input class="form-control jform-validate" required="required" id="application_bid" name="application_bid"  placeholder="<?php esc_attr_e('30','noo')?>"/>
						</div>
						<div class="form-group">
							<div class="row">
								<?php if( $member_apply != 'yes' || $disable_member_upload != 'yes' ) : ?>
									<div class="col-sm-6">
										<label for="application_attachment" class="control-label"><?php _e('Upload to CV','noo')?></label>
										<div class="form-control-flat">
											<label class="form-control-file"> <span class="form-control-file-button"><i class="fa fa-folder-open"></i> <?php _e('Browse','noo')?></span>
												<input type="file" name="application_attachment" class="jform-validate-uploadcv" accept="<?php echo implode(',', $allowed_exts); ?>"> <input type="text" readonly value="" class="form-control" autocomplete="off">
											</label>
											<?php 
									    	$max_upload_size = wp_max_upload_size();
									    	if ( ! $max_upload_size ) {
									    		$max_upload_size = 0;
									    	}
									    	?>
										</div>
										<p class="help-block"><?php printf( __( 'Maximum upload file size: %s', 'noo' ), esc_html( size_format( $max_upload_size ) ) ); ?></p>
										<?php /*
									    <div class="upload-to-cv clearfix">
									    	<?php //noo_plupload_form('cv','pdf,doc,jpg,gif,png') ?>
										</div>
										*/ ?>
									</div>
								<?php endif; ?>
								<?php 
								
								
								if($r && $r->post_count) : ?>
									<div class="col-sm-6">
										<label for="email" class="control-label"><?php _e('Select','noo')?></label>
										<div class="form-control-flat">
											<select name="resume">
												<option value=""><?php _e('-Select-','noo')?></option>
												<?php while ($r->have_posts()): $r->the_post()?>
												<option value="<?php echo get_the_ID()?>" ><?php echo get_the_title(get_the_ID())?></option>
												<?php endwhile;?>
											</select>
											<i></i>
										</div>
									</div>
								<?php endif; ?>
							</div>
						</div>
						<?php do_action( 'after_apply_job_form' ); ?>
						<?php if ( $show_button == true ) : ?>
							<div class="modal-actions">
								<button type="button" id="send_app" onclick="check_bid();" class="btn btn-primary"><?php _e('Send application','noo')?></button>
							</div>
						<?php endif; ?>
					</form>
				<?php else : ?>
					<h4><?php echo __("You have no resume for application, please create a new resume first.",'noo')?></h4>
					<p>
						<a href="<?php echo Noo_Member::get_post_resume_url(); ?>" class="btn btn-primary"><?php _e('Create New Resume', 'noo'); ?></a>
					</p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<script>
function check_bid()
{
	var bid= jQuery("#application_bid").val();
	var max_bid = jQuery(".value-_noo_job_field__max_bid_amount").text();
	if(bid > max_bid)
	{
		alert("Your given bid amount is not accepted. It is more then the Maximum bid amount.");
		return false;
	}
	else{
		jQuery("#apply_job_form").submit();
	}
}
</script>