<?php
if( is_front_page() || is_home()) {
	$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : ( ( get_query_var( 'page' ) ) ? get_query_var( 'page' ) : 1 );
} else {
	$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
}

$args = array(
	'post_type'=>'noo_resume',
	'paged' => $paged,
	'post_status'=>array('publish'),
	'author'=>get_current_user_id(),
);
$r = new WP_Query($args);
ob_start();
do_action('noo_member_manage_resume_before');

$max_viewable_resumes = absint( Noo_Resume::get_setting('max_viewable_resumes', 1) );
$viewable_resumes = absint( Noo_Resume::count_viewable_resumes( get_current_user_id() ) );
$disable_set_viewable = $viewable_resumes >= $max_viewable_resumes;

$note_text = '';
if( $max_viewable_resumes < 1 ) {
	$note_text = __('No resume is publicly viewable/searchable.','noo');
} elseif( $max_viewable_resumes == 1 ) {
	$note_text = __('Only 1 resume is publicly viewable/searchable.','noo');
} else {
	$note_text = sprintf( __('Only %d resumes are publicly viewable/searchable.', 'noo'), $max_viewable_resumes );	
}
?>
<div class="member-manage">
	<?php if($r->have_posts()):?>
		<h3><?php echo ( $r->found_posts > 1 ? sprintf(__("You've saved %s resumes",'noo'),'<span class="text-primary">'.$r->found_posts.'</span>') : sprintf(__("You've saved %s resume",'noo'),'<span class="text-primary">'.$r->found_posts.'</span>') );?></h3>
		<em><strong><?php _e('Note:','noo')?></strong> <?php echo esc_html( $note_text ); ?></em>
		<form method="post">
			<div style="display: none">
			<?php wp_nonce_field('resume-manage-action')?>
			</div>
			<div class="member-manage-table">
				<table class="table">
					<thead>
						<tr>
							<th><?php _e('Title','noo')?></th>
							<th class="hidden-xs text-center"><?php _e('Viewable', 'noo'); ?></th>
							<th class="hidden-xs"><?php _e('Category','noo')?></th>
							<th class="hidden-xs hidden-sm"><?php _e('Location','noo')?></th>
							<th class="hidden-xs hidden-sm"><?php _e('Date Modified','noo')?></th>
							<th class="text-center"><?php _e('Action','noo')?></th>
						</tr>
					</thead>
					<tbody>
						<?php while ($r->have_posts()): $r->the_post();global $post;?>
							<tr>
								<td class="title-col"><a href="<?php the_permalink()?>"><strong><?php the_title()?></strong></a></td>
								<td class="hidden-xs text-center viewable-col">
									<?php 
									$viewable = noo_get_post_meta($post->ID,'_viewable');

									if ( 'yes' === $viewable ) {
										echo '<a href="' . wp_nonce_url( add_query_arg(array('action'=>'toggle_viewable','resume_id'=>$post->ID)), 'resume-manage-action' ) . '" class="noo-resume-viewable" data-toggle="tooltip" title="'.esc_attr__('Disable viewable','noo').'"><i class="fa fa-star text-primary"></i></a>';
									} else {
										echo ( $disable_set_viewable ? '<span class="noo-resume-viewable" not-viewable" data-toggle="tooltip" ><i class="fa fa-star-o"></i></span>' : '<a href="' . wp_nonce_url( add_query_arg(array('action'=>'toggle_viewable','resume_id'=>$post->ID)), 'resume-manage-action' ) . '" class="noo-resume-viewable not-viewable" data-toggle="tooltip" title="'.esc_attr__('Set Viewable','noo').'"><i class="fa fa-star-o"></i></a>' );
									}
									?>
								</td>
								<td class="hidden-xs category-col"><em><?php
									$job_category = noo_get_post_meta($post->ID,'_job_category','');
									$job_categories = array();
									if( !empty( $job_category ) ) {
										$job_category = noo_json_decode($job_category);
										$job_categories = empty( $job_category ) ? array() : get_terms( 'job_category', array('include' => $job_category, 'hide_empty' => 0, 'fields' => 'names') );
										echo implode(', ', $job_categories );
									}
								?></em></td>
								<td class="hidden-xs hidden-sm location-col">
									<?php
									$job_location = noo_get_post_meta($post->ID,'_job_location','');
									$job_locations = array();
									if( !empty( $job_location ) ) :
										$job_location = noo_json_decode($job_location);
										$job_locations = empty( $job_location ) ? array() : get_terms( 'job_location', array('include' => $job_location, 'hide_empty' => 0, 'fields' => 'names') );
									?>
									<i class="fa fa-map-marker"></i>&nbsp;<em><?php echo implode(', ', $job_locations ); ?></em>
									<?php endif; ?>
								</td>
								<td class="hidden-xs hidden-sm date-col"><span><i class="fa fa-calendar"></i>&nbsp;<em><?php the_modified_date(); ?></em></span></td>
								<td class="member-manage-actions text-center">
									<a href="<?php echo Noo_Member::get_edit_resume_url($post->ID)?>" class="member-manage-action" data-toggle="tooltip" title="<?php esc_attr_e('Edit Resume','noo')?>"><i class="fa fa-pencil"></i></a>
									<a href="<?php echo wp_nonce_url( add_query_arg(array('action'=>'delete','resume_id'=>$post->ID)), 'resume-manage-action' );?>" class="member-manage-action action-delete" data-toggle="tooltip" title="<?php esc_attr_e('Delete Resume','noo')?>"><i class="fa fa-trash-o"></i></a>
								</td>
							</tr>
						<?php endwhile;?>
					</tbody>
				</table>
			</div>
			<div class="member-manage-toolbar bottom-toolbar clearfix">
				<div class="member-manage-page pull-left">
					<a href="<?php echo Noo_Member::get_post_resume_url(); ?>" class="btn btn-primary"><?php _e('Create New Resume', 'noo'); ?></a>
				</div>
				<div class="member-manage-page pull-right">
					<?php noo_pagination(array(),$r)?>
				</div>
			</div>
		</form>
	<?php else:?>
		<h4><?php echo __("You have no resume, why don't you start posting one.",'noo')?></h4>
		<p>
			<a href="<?php echo Noo_Member::get_post_resume_url(); ?>" class="btn btn-primary"><?php _e('Post Resume', 'noo')?></a>
		</p>
	<?php endif;?>
</div>
<?php
do_action('noo_member_manage_resume_after');
wp_reset_query();