<?php
$r = Noo_Job::get_job_by_employer();
$package_data = Noo_Job::get_employer_package();
$remain_featured_job = Noo_Job::get_remain_job_feature();
ob_start();
do_action('noo_member_manage_job_before');
$bulk_actions = (array) apply_filters('noo_member_manage_job_bulk_actions', array(
	'publish'=>__('Publish','noo'),
	'unpublish'=>__('Unpublish','noo'),
	'delete'=>__('Delete','noo')
));

$job_need_approve = Noo_Job::get_setting('noo_job_general', 'job_approve','' ) == 'yes';
?>
<div class="member-manage">
	<?php if($r->have_posts()):?>
		<h3><?php echo sprintf(__("You've posted %s jobs",'noo'),$r->found_posts)?></h3>
		<!--<em><strong><?php _e('Note:','noo')?></strong> <?php _e('Expired listings will be removed from public view.','noo')?></em><br/>
		<em><?php echo sprintf(__('You can set %d more job(s) featured. Featured jobs cannot be reverted.','noo'), $remain_featured_job);?></em>-->
		<form method="post">
			<div class="member-manage-toolbar top-toolbar hidden-xs clearfix">
				<div class="bulk-actions clearfix">
					<strong><?php _e('Action:','noo')?></strong>
					<div class="form-control-flat">
						<select name="action">
							<option selected="selected" value="-1"><?php _e('-Bulk Actions-','noo')?></option>
							<?php foreach ($bulk_actions as $action=>$label):?>
							<option value="<?php echo esc_attr($action)?>"><?php echo esc_html($label)?></option>
							<?php endforeach;?>
						</select>
						<i class="fa fa-caret-down"></i>
					</div>
					<button type="submit" class="btn btn-primary"><?php _e('Go', 'noo')?></button>
				</div>
			</div>
			<div style="display: none">
			<?php wp_nonce_field('job-manage-action')?>
			</div>
			<section id="table_info">
<div class="container">
<div class="table-responsive">
			<div class="member-manage-table">
				<table class="table">
					<thead>
						<tr>
							<th class="check-column"><div class="form-control-flat"><label class="checkbox"><input type="checkbox"><i></i></label></div></th>
							<th><?php _e('Order','noo')?></th>
							<th><?php _e('Job Title','noo')?></th>
							<th class="hidden-xs">&nbsp;</th>
							<th><?php _e('Catagory','noo')?></th>
							<!--<th class="hidden-xs hidden-sm"><?php _e('Location','noo')?></th>-->
							<th class="hidden-xs"><?php _e('Closing','noo')?></th>
							<th class="text-center"><?php _e('Apps','noo')?></th>
							<?php if( $job_need_approve ) : ?>
							<th class="text-center hidden-xs"><?php _e('Status','noo')?></th>
							<?php endif; ?>
							<th class="text-center hidden-xs"><?php _e('Action','noo')?></th>
						</tr>
					</thead>
					<tbody>
						<?php while ($r->have_posts()): $r->the_post();global $post;
						
							$status = $status_class = get_post_status(get_the_ID());
							$statuses = Noo_Job::get_job_status();
							$status_text = '';
							if ( isset( $statuses[ $status ] ) ) {
								$status_text = $statuses[ $status ];
							} else {
								$status_text = __( 'Inactive', 'noo' );
								$status_class = 'inactive';
							}
							
						?>
							<tr>
								<td class="check-column"><div class="form-control-flat"><label class="checkbox"><input type="checkbox" name="ids[]" value="<?php the_ID()?>"><i></i></label></div></td>
								<td>
									<?php 
									
									if( $status == 'pending' ) : ?>
										<a href="<?php echo esc_url(add_query_arg( 'job_id', get_the_ID(), Noo_Member::get_endpoint_url('preview-job') )); ?>"><strong><?php the_ID()?></strong></a>
									<?php else : ?>
										<a href="<?php the_permalink()?>"><strong><?php the_ID()?></strong></a>
									<?php endif; ?>
								</td>
								<td>
									<?php if( $status == 'pending' ) : ?>
										<a href="<?php echo esc_url(add_query_arg( 'job_id', get_the_ID(), Noo_Member::get_endpoint_url('preview-job') )); ?>"><strong><?php the_title()?></strong></a>
									<?php else : ?>
										<a href="<?php the_permalink()?>"><strong><?php the_title()?></strong></a>
									<?php endif; ?>
								</td>
								<td class="hidden-xs">
									<?php
									$featured = noo_get_post_meta($post->ID,'_featured');
									if( empty( $featured ) ) {
										// Update old data
										update_post_meta( $post->ID, '_featured', 'no' );
									}
									if ( 'yes' === $featured ) :
										echo '<span class="noo-job-feature" data-toggle="tooltip" title="'.esc_attr__('Featured','noo').'"><i class="fa fa-star"></i></span>';
									elseif( Noo_Job::can_set_job_feature() ) :
									?>
										<a href="<?php echo wp_nonce_url( add_query_arg(array('action'=>'featured','job_id'=>get_the_ID())), 'job-manage-action' )?>">
											<span class="noo-job-feature not-featured" data-toggle="tooltip"  title="<?php _e('Set Featured','noo'); ?>"><i class="fa fa-star-o"></i></span>
										</a>
									<?php else : ?>
										<span class="noo-job-feature not-featured" title="<?php _e('Set Featured','noo'); ?>"><i class="fa fa-star-o"></i></span>
									<?php endif; ?>
								</td>
								<td>
									<?php 
									
									if( $status == 'pending' ) : ?>
										<a href="<?php echo esc_url(add_query_arg( 'job_id', get_the_ID(), Noo_Member::get_endpoint_url('preview-job') )); ?>"><strong><?php the_ID()?></strong></a>
									<?php else : ?>
										<a href="<?php the_permalink()?>"><strong><?php the_ID()?></strong></a>
									<?php endif; 
                               ?>
								</td>
								<!--<td class="hidden-xs hidden-sm"><i class="fa fa-map-marker"></i>&nbsp;<em><?php //echo get_the_term_list(get_the_ID(),'job_location','',', ')?></em></td>-->

								<td class="job-manage-expires hidden-xs"><span><i class="fa fa-calendar"></i>&nbsp;<em><?php echo date_i18n( get_option('date_format'), strtotime( $post->_closing ) )?></em></span></td>
								<td class="job-manage-app text-center">
									<span>
									<?php 
									$applications = get_posts(array(
										'post_type' => 'noo_application',
										'posts_per_page'=>-1,
										'post_parent'=>$post->ID,
										'post_status'=>array('publish','pending','rejected')
									));
									echo absint(count($applications));
									?>
									</span>
								</td>
								<?php if( $job_need_approve ) : ?>
								<td class="text-center">
									<span class="job-application-status job-application-status-<?php echo esc_attr($status_class) ?>">
									<?php echo esc_html($status_text)?>
									</span>
								</td>
								<?php endif; ?>
								<td class="member-manage-actions hidden-xs text-center">
									<?php if(Noo_Member::can_change_job_state( $post->ID, get_current_user_id() )):?>
										<?php if($status == 'publish'):?>
										<a href="<?php echo wp_nonce_url( add_query_arg(array('action'=>'unpublish','job_id'=>get_the_ID())), 'job-manage-action' );?>" class="member-manage-action"  data-toggle="tooltip" title="<?php esc_attr_e('Unpublish Job','noo')?>"><i class="fa fa-toggle-on"></i></a>
										<?php else:?>
										<a href="<?php echo wp_nonce_url( add_query_arg(array('action'=>'publish','job_id'=>get_the_ID())), 'job-manage-action' );?>" class="member-manage-action" data-toggle="tooltip" title="<?php esc_attr_e('Publish Job','noo')?>"><i class="fa fa-toggle-off"></i></a>
										<?php endif;?>
									<?php endif;?>
									<?php if(Noo_Member::can_edit_job( $post->ID, get_current_user_id() )):?>
										<a href="<?php echo Noo_Member::get_edit_job_url(get_the_ID())?>" class="member-manage-action" data-toggle="tooltip" title="<?php esc_attr_e('Edit Job','noo')?>"><i class="fa fa-pencil"></i></a>
									<?php endif; ?>
									<?php if( $status == 'expired' ) : ?>
										<a href="#" class="member-manage-action" data-toggle="tooltip" title="<?php esc_attr_e('Job Expired','noo')?>"><i class="fa fa-clock-o"></i></a>
									<?php endif;?>
									<a href="<?php echo wp_nonce_url( add_query_arg(array('action'=>'delete','job_id'=>get_the_ID())), 'job-manage-action' );?>" class="member-manage-action action-delete" data-toggle="tooltip" title="<?php esc_attr_e('Delete Job','noo')?>"><i class="fa fa-trash-o"></i></a>
								</td>
							</tr>
						<?php endwhile;?>
					</tbody>
				</table>
			</div>
			<div class="member-manage-toolbar bottom-toolbar clearfix">
				<div class="bulk-actions clearfix pull-left">
					<strong class="hidden-xs"><?php _e('Action:','noo')?></strong>
					<div class="form-control-flat">
						<select name="action2">
							<option selected="selected" value="-1"><?php _e('-Bulk Actions-','noo')?></option>
							<?php foreach ($bulk_actions as $action=>$label):?>
							<option value="<?php echo esc_attr($action)?>"><?php echo esc_html($label)?></option>
							<?php endforeach;?>
						</select>
						<i class="fa fa-caret-down"></i>
					</div>
					<button type="submit" class="btn btn-primary"><?php _e('Go', 'noo')?></button>
				</div>
				<div class="member-manage-page pull-right">
					<?php noo_pagination(array(),$r)?>
				</div>
			</div>
		</form>
	<?php else:?>
		<h4><?php echo __("You have no job, why don't you start posting one.",'noo')?></h4>
		<p>
			<a href="<?php echo Noo_Member::get_post_job_url(); ?>" class="btn btn-primary"><?php _e('Post Job', 'noo')?></a>
		</p>
	<?php endif;?>
</div></div></div>
</section>
<?php
do_action('noo_member_manage_job_after');
wp_reset_query();