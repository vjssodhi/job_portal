<?php
if( is_front_page() || is_home()) {
	$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : ( ( get_query_var( 'page' ) ) ? get_query_var( 'page' ) : 1 );
} else {
	$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
}

$user = wp_get_current_user();
$viewed_messages = get_user_meta( $user->ID, '_check_view_applied', true );
$viewed_messages = empty( $viewed_messages ) || !is_array( $viewed_messages ) ? array() : $viewed_messages;

$args = array(
	'post_type'=>'noo_application',
	'paged' => $paged,
	'post_status'=>array('publish','pending','rejected','inactive'),
	'meta_query'=>array(
		array(
			'key' => '_candidate_email',
			'value' => $user->user_email,
		),
	)
);

$r = new WP_Query($args);
ob_start();
do_action('noo_member_manage_application_before');
$title_text = '';
if( $r->found_posts <= 1 ) {
	$title_text = sprintf(__("You've applied %s job",'noo'),$r->found_posts);
} else {
	$title_text = sprintf(__("You've applied for %s jobs",'noo'),$r->found_posts);
}
?>
<div class="member-manage">
	<h3><?php echo $title_text; ?></h3>
	<form method="post">
		<div class="member-manage-toolbar top-toolbar hidden-xs clearfix">
		</div>
		<div style="display: none">
		<?php wp_nonce_field('application-manage-action')?>
		</div>
		<section id="table_info">
<div class="container">
<div class="table-responsive">
		<div class="member-manage-table">
			<table class="table">
				<thead>
					<tr>
						<th><?php _e('Applied job','noo')?></th>
						<th class="hidden-xs hidden-sm"><?php _e('Applied Date','noo')?></th>
						<th class=""><?php _e('Employer\'s message','noo')?></th>
						<th class="hidden-xs text-center"><?php _e('Action','noo')?></th>
						<th class="text-center"><?php _e('Status','noo')?></th>
					</tr>
				</thead>
				<tbody>
					<?php if($r->have_posts()):?>
						<?php 
						while ($r->have_posts()): $r->the_post();global $post;
							$job = get_post( $post->post_parent );
							
							//print_r($job);
							// don't display if there's no job.
							if( empty( $job ) ) continue;
							$company_id = Noo_Job::get_employer_company($job->post_author);
							$company_logo = Noo_Company::get_company_logo( $company_id, 'medium' );
							$employer_message_title = noo_get_post_meta($post->ID, '_employer_message_title', '');
							$employer_message_body = noo_get_post_meta($post->ID, '_employer_message_body', '');
							$mesage_excerpt = empty($employer_message_title) ? wp_trim_words( $employer_message_body, 10 ) : $employer_message_title;
							$mesage_excerpt = !empty($mesage_excerpt) ? $mesage_excerpt . __('...', 'noo') : '';
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
							<tr>
								<td>
									<div class="loop-item-wrap">
									<?php 
									if( !empty( $company_logo ) ) :
									?>
										<div class="item-featured">
											<?php echo $company_logo; ?>
										</div>
									<?php
									endif;
									if ( $job && $job->post_type === 'noo_job' ) :
									?>
										<div class="loop-item-content">
											<h3 class="loop-item-title"><a href="<?php echo get_permalink( $job->ID ); ?>"><?php echo esc_html($job->post_title); ?></a></h3>
										</div>
									<?php
									else :
										echo ('<span class="na">&ndash;</span>');
									endif;
									?>
									</div>
								</td>
								<td class="hidden-xs hidden-sm"><span><i class="fa fa-calendar"></i> <em><?php echo date_i18n( get_option('date_format'), strtotime( $post->post_date ) )?></em></span></td>
								<td class="">
									<?php if( $post->post_status == 'rejected' || $post->post_status == 'publish' ) : ?>
										<?php
											$tag = !in_array($post->ID, $viewed_messages) ? 'strong' : 'span'; 
											$readmore_link = '<a href="#" data-application-id="' . esc_attr($post->ID) . '" class="member-manage-action view-employer-message"><em class="text-primary">' . __('Continue reading', 'noo') . '&nbsp;<i class="fa fa-long-arrow-right"></i></em></a>';
											$readmore_link = apply_filters( 'noo-manage-job-applied-message-link', $readmore_link, $post->ID );

											if( !in_array($post->ID, $viewed_messages) ) :
										?>
											<strong class="hidden-xs hidden-sm">
												<?php echo esc_html($mesage_excerpt); ?>
											</strong>&nbsp;<?php echo $readmore_link; ?>
										<?php else : ?>
											<span class="hidden-xs hidden-sm">
												<?php echo esc_html($mesage_excerpt); ?>
											</span>&nbsp;<?php echo $readmore_link; ?>
										<?php endif; ?>
									<?php endif; ?>
								</td>
								<td class="member-manage-actions hidden-xs text-center">
									<?php do_action( 'noo-manage-job-applied-action', get_the_ID() ); ?>
									<?php 
									//echo $post->post_status;
									if( $post->post_status == 'pending' ) : ?>
										<a href="<?php echo wp_nonce_url( add_query_arg(array('action'=>'withdraw','application_id'=>get_the_ID())), 'job-applied-manage-action' );?>" class="member-manage-action action-delete" data-toggle="tooltip" title="<?php esc_attr_e('Withdraw','noo')?>"><i class="fa fa-history"></i></a>
									<?php elseif( $post->post_status == 'inactive' ) : ?>
										<a href="<?php echo wp_nonce_url( add_query_arg(array('action'=>'delete','application_id'=>get_the_ID())), 'job-applied-manage-action' );?>" class="member-manage-action action-delete" data-toggle="tooltip" title="<?php esc_attr_e('Delete Application','noo')?>"><i class="fa fa-trash-o"></i></a>
									<?php elseif( $post->post_status == 'publish' ) : ?>
									<a href="<?php echo wp_nonce_url( add_query_arg(array('action'=>'acceptOffer','application_id'=>get_the_ID())), 'job-applied-manage-action' );?>" class="member-manage-action action-delete" data-toggle="tooltip" title="<?php esc_attr_e('Accept Offer','noo')?>"><i class="fa fa-check-square-o"></i></a>
			
									<?php endif;?>
									
								</td>
								<td class="text-center">
									<span class="job-application-status job-application-status-<?php echo sanitize_html_class($status_class) ?>">
									<?php echo esc_html($status)?>
									</span>
								</td>
								
							</tr>
						<?php endwhile;?>
					<?php else:?>
					<tr>
						<td colspan="4" class="text-center"><h3><?php _e('No Application','noo')?></h3></td>
					</tr>
					<?php endif;?>
				</tbody>
			</table>
		</div>
		<div class="member-manage-toolbar bottom-toolbar clearfix">
			<div class="member-manage-page pull-right">
				<?php noo_pagination(array(),$r)?>
			</div>
		</div>
	</form>
</div></div></div>
</section>
<?php
do_action('noo_member_manage_application_after');
wp_reset_query();