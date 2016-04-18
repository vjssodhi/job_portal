<?php
if( is_front_page() || is_home()) {
	$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : ( ( get_query_var( 'page' ) ) ? get_query_var( 'page' ) : 1 );
} else {
	$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
}

$bookmarked_ids = Noo_Job::get_bookmarked_job_ids();

if( !empty( $bookmarked_ids ) ) {
	$args = array(
		'post_type'=>'noo_job',
		'post_status'=>array('publish'),
		'paged'=>$paged,
		'post__in'=>array_keys($bookmarked_ids)
	);

	$r = new WP_Query($args);
} else {
	$r = new stdClass();
	$r->found_posts = 0;
}

ob_start();
do_action('noo_member_manage_bookmark_job_before');

$title_text = '';
if( empty( $bookmarked_ids ) || $r->found_posts <= 1 ) {
	$title_text = sprintf(__("You've bookmarked %s job",'noo'),$r->found_posts);
} else {
	$title_text = sprintf(__("You've bookmarked %s jobs",'noo'),$r->found_posts);
}
?>
<div class="member-manage">
	<h3><?php echo $title_text; ?></h3>
	<form method="post">
		<div class="member-manage-toolbar top-toolbar hidden-xs clearfix">
		</div>
		<div style="display: none">
		<?php wp_nonce_field('job-manage-action')?>
		</div>
		<div class="member-manage-table">
			<table class="table">
				<thead>
					<tr>
						<th><?php _e('Job Title','noo')?></th>
						<th class="hidden-xs"><?php _e('Information','noo')?></th>
						<th class="text-center">&nbsp;</th>
					</tr>
				</thead>
				<tbody>
					<?php if(!empty( $bookmarked_ids ) && $r->have_posts()):?>
						<?php while ($r->have_posts()): $r->the_post();global $post;
							$job = get_post( $post->post_parent );
							$company_id = $job->_company_id; //Noo_Job::get_employer_company($job->post_author);
							$company = !empty( $company_id ) ? get_post($company_id) : '';
						?>
							<tr>
								<td>
									<div class="loop-item-wrap">
									<?php 
									if( !empty( $company ) ) :
										$company_logo = Noo_Company::get_company_logo($company->ID);
										if( !empty( $company_logo ) ) :
									?>
										<div class="item-featured">
											<?php echo $company_logo; ?>
										</div>
									<?php
										endif;
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
								<td class="hidden-xs">
									<?php Noo_Job::noo_contentjob_meta(array('show_date'=>false,'show_category'=>true));?>
								</td>
								<td class="member-manage-actions text-center">
									<a href="<?php echo wp_nonce_url( add_query_arg(array('action'=>'delete_bookmark','job_id'=>get_the_ID())), 'bookmark-job-manage-action' );?>" class="btn btn-primary"><?php _e('Remove', 'noo'); ?></a>
								</td>
							</tr>
						<?php endwhile;?>
					<?php else:?>
					<tr>
						<td colspan="3"><h3><?php _e('No Job bookmarked','noo')?></h3></td>
					</tr>
					<?php endif;?>
				</tbody>
			</table>
		</div>
		<?php if( !empty( $bookmarked_ids ) ) : ?>
		<div class="member-manage-toolbar bottom-toolbar clearfix">
			<div class="member-manage-page pull-right">
				<?php noo_pagination(array(),$r)?>
			</div>
		</div>
		<?php endif; ?>
	</form>
</div>
<?php
do_action('noo_member_manage_bookmark_job_after');
wp_reset_query();