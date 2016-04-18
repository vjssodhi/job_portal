<?php
if( is_front_page() || is_home()) {
	$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : ( ( get_query_var( 'page' ) ) ? get_query_var( 'page' ) : 1 );
} else {
	$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
}
$job_filter = isset($_POST['job']) ? absint($_POST['job']) : 0;
$job_ids = get_posts(array(
	'post_type'=>'noo_job',
	'post_status'=>array('publish','pending','expired'),
	'author'=>get_current_user_id(),
	'posts_per_page'=>-1,
	'fields' => 'ids'
));
$args = array(
	'post_type'=>'noo_application',
	'paged' => $paged,
	'post_parent__in'=>array_merge($job_ids, array(0)), // make sure return zero application if there's no job.
	'post_status'=>array('publish','pending','rejected'),
	
);
if(!empty($job_filter) && in_array($job_filter, $job_ids)){
	$args['post_parent__in'] = array($job_filter);
}
$r = new WP_Query($args);
ob_start();
do_action('noo_member_manage_application_before');
?>
<div class="member-manage">
	<h3><?php echo sprintf(__("You've received %s application(s)",'noo'),$r->found_posts)?></h3>
	<form method="post">
		<div class="member-manage-toolbar top-toolbar hidden-xs clearfix">
			<div class="bulk-actions clearfix">
				<strong><?php _e('Filter:','noo')?></strong>
				<div class="form-control-flat" style="width: 200px;">
					<select name="job">
						<option value="0"><?php _e('-All jobs-','noo')?></option>
						<?php foreach ($job_ids as $job_id):?>
						<option value="<?php echo esc_attr($job_id)?>" <?php selected($job_filter,$job_id)?> ><?php echo get_the_title($job_id)?></option>
						<?php endforeach;?>
					</select>
					<i  class="fa fa-caret-down"></i>
				</div>
				<button type="submit" class="btn btn-primary"><?php _e('Go', 'noo')?></button>
			</div>
		</div>
		<div style="display: none">
		<?php wp_nonce_field('application-manage-action')?>
		</div>
		<div class="member-manage-table">
			<table class="table">
				<thead>
					<tr>
						<th><?php _e('Name','noo')?></th>
						<th><?php _e('Applied job','noo')?></th>
						<th class="hidden-xs hidden-sm"><?php _e('Message','noo')?></th>
						<th class="hidden-xs hidden-sm"><?php _e('Applied Date','noo')?></th>
						<th class="hidden-xs text-center"><?php _e('Action','noo')?></th>
						<th class="text-center"><?php _e('CV','noo')?></th>
						<th class="text-center"><?php _e('Status','noo')?></th>
						
					</tr>
				</thead>
				<tbody>
					<?php if($r->have_posts()):?>
						<?php 
						while ($r->have_posts()): $r->the_post();global $post;
						$job = get_post( $post->post_parent );
						if ( $attachment = noo_get_post_meta( $post->ID, '_attachment', '' ) ) {
							$attachment_icon = 'fa-eye';
							$maybe_resume = is_numeric($attachment) ? get_post(absint($attachment)) : '';
							if($maybe_resume && $maybe_resume->post_type == 'noo_resume') {
								$attachment_link = get_permalink( $maybe_resume->ID );
								$attachment_link = esc_url( add_query_arg('application_id', $post->ID, $attachment_link) );
							} else {
								$attachment_icon = 'fa-file-text-o';
								$attachment_link = $attachment;
							}
							if( strpos($attachment_link,'linkedin') ) {
								$attachment_icon = 'fa-linkedin';
							}
						} else {
							$attachment_link = '';
							$attachment_icon = '';
						}
						
						$mesage_excerpt = !empty($post->post_content) ? wp_trim_words( $post->post_content, 10 ) : '';
						$mesage_excerpt = !empty($mesage_excerpt) ? $mesage_excerpt . __('...', 'noo') : '';
						$candidate_email = noo_get_post_meta($post->ID,'_candidate_email');
						$maybe_user_id = noo_get_post_meta($post->ID,'_candidate_user_id');

						if( !empty( $maybe_user_id ) ) {
							$maybe_user = get_user_by( 'email', $candidate_email );
							if( !empty( $maybe_user ) ) $maybe_user_id = $maybe_user->ID;
						}

						$avatar = !empty( $maybe_user_id ) ? noo_get_avatar($maybe_user_id, 40) : noo_get_avatar($candidate_email, 40);
						$display = get_the_title();
						?>
							<tr>
								<td>
									<?php $candidate_link = apply_filters( 'noo_application_candidate_link', '', $post->ID, $candidate_email ); ?>
									<span class="candidate-avatar">
										<?php echo ($avatar) ?>
									</span>
									<span class="candidate-name"><?php if(!empty($candidate_link)) echo '<a href="'. $candidate_link . '">'; ?><?php echo esc_html($display) ?><?php if(!empty($candidate_link)) echo '</a>'; ?></span>
								</td>
								<td>
									<?php 
									
									if ( $job && $job->post_type === 'noo_job' ) {
										echo ('<a href="' . get_permalink( $job->ID ) . '">' . $job->post_title . '</a>');
									} elseif ( $job = get_post_meta( $post->ID, '_job_applied_for', true ) ) {
										echo esc_html( $job );
									} else {
										echo ('<span class="na">&ndash;</span>');
									}
									?>
								</td>
								<td class="hidden-xs hidden-sm">
									<?php
										$readmore_link = '<a href="#" data-application-id="' . esc_attr($post->ID) . '" class="member-manage-action view-employer-message" data-mode="1"><em class="text-primary">' . __('Continue reading', 'noo') . '&nbsp;<i class="fa fa-long-arrow-right"></i></em></a>';
										$readmore_link = apply_filters( 'noo-manage-application-message-link', $readmore_link, $post->ID );
									?>
									<strong class="hidden-xs hidden-sm"><?php echo esc_html($mesage_excerpt); ?></strong>&nbsp;<?php echo $readmore_link; ?>
								</td>
								<td class="hidden-xs hidden-sm"><span><i class="fa fa-calendar"></i> <em><?php echo date_i18n( get_option('date_format'), strtotime( $post->post_date ) )?></em></span></td>
								<td class="member-manage-actions hidden-xs text-center">
									<?php
									if( $post->post_status == 'pending' ) :
										$approve_link = '<a href="#" class="member-manage-action approve-reject-action" data-hander="approve" data-application-id="' . get_the_ID() . '" data-toggle="tooltip" title="' . esc_attr__('Approve Application','noo') . '"><i class="fa fa-check-square-o"></i></a>';
										$reject_link = '<a href="#" class="member-manage-action approve-reject-action" data-hander="reject"  data-application-id="' . get_the_ID() . '" data-toggle="tooltip" title="' . esc_attr__('Reject Application','noo') . '"><i class="fa fa-ban"></i></a>';

										$approve_link = apply_filters( 'noo-manage-application-approve-link', $approve_link, get_the_ID() );
										$reject_link = apply_filters( 'noo-manage-application-reject-link', $reject_link, get_the_ID() );

										echo $approve_link;
										echo $reject_link;
									else: ?>
										<a class="member-manage-action action-no-link" title="<?php esc_attr_e('Approve Application','noo')?>"><i class="fa fa-check-square-o"></i></a>
										<a class="member-manage-action action-no-link" title="<?php esc_attr_e('Reject Application','noo')?>"><i class="fa fa-ban"></i></a>
									<?php endif;?>
									<?php
										$email_link = '<a href="mailto:' . $candidate_email . '" class="member-manage-action" data-toggle="tooltip" title="' . esc_attr__('Email Candidate','noo') . '"><i class="fa fa-envelope-o"></i></a>';

										$email_link = apply_filters( 'noo-manage-application-email-link', $email_link, get_the_ID() );
										echo $email_link;
									?>
									<?php do_action( 'noo-manage-application-action', get_the_ID() ); ?>
									<a href="<?php echo wp_nonce_url( add_query_arg(array('action'=>'delete','application_id'=>get_the_ID())), 'application-manage-action' );?>" class="member-manage-action action-delete" data-toggle="tooltip" title="<?php esc_attr_e('Delete Application','noo')?>"><i class="fa fa-trash-o"></i></a>
								</td>
								<td class="text-center">
									<?php 
									if ( !empty( $attachment_link ) ) {
										echo ('<a class="view_applications" data-application-id="'. get_the_ID() .'" href="' . $attachment_link . '" data-toggle="tooltip" title="'.esc_attr__('CV','noo').'"><i class="fa '.$attachment_icon.'"></i></a>');
									} else {
										echo ('<span class="na">&ndash;</span>');
									}
									?>
								</td>
								<td class="text-center">
									<?php 
									$status   = $post->post_status;
									$status_class = $status;
									$statuses = Noo_Application::get_application_status();
									if ( isset( $statuses[ $status ] ) ) {
										$status = $statuses[ $status ];
									} else {
										$status = __( 'Inactive', 'noo' );
										$status_class = 'inactive';
									}
									?>
									<span class="job-application-status job-application-status-<?php echo esc_attr($status_class) ?>">
										<?php echo esc_html($status)?>
									</span>
								</td>
								
							</tr>
						<?php endwhile;?>
					<?php else:?>
					<tr>
						<td colspan="8" class="text-center"><h3><?php _e('No Application','noo')?></h3></td>
					</tr>
					<?php endif;?>
				</tbody>
			</table>
		</div>
		<div class="member-manage-toolbar bottom-toolbar clearfix">
			<div class="bulk-actions clearfix pull-left">
				<strong class="hidden-xs"><?php _e('Filter:','noo')?></strong>
				<div class="form-control-flat" style="width: 200px;">
					<select name="job2">
						<option value="0"><?php _e('-All jobs-','noo')?></option>
						<?php foreach ($job_ids as $job_id):?>
						<option value="<?php echo esc_attr($job_id)?>" <?php selected($job_filter,$job_id)?>><?php echo get_the_title($job_id)?></option>
						<?php endforeach;?>
					</select>
					<i  class="fa fa-caret-down"></i>
				</div>
				<button type="submit" class="btn btn-primary"><?php _e('Go', 'noo')?></button>
			</div>
			<div class="member-manage-page pull-right">
				<?php noo_pagination(array(),$r)?>
			</div>
		</div>
	</form>
</div>
<?php
do_action('noo_member_manage_application_after');
wp_reset_query();