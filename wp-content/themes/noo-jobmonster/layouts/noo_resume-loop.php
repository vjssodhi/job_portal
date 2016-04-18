<?php
if($wp_query->have_posts() || !$is_shortcode):
	$title = empty($title) ? __('Resumes', 'noo') : $title;
?>
	<?php if(!$ajax_item || $ajax_item == null )://ajax item ?>
		<div class="resumes posts-loop" data-paginate="<?php echo $paginate; ?>">
			<div class="posts-loop-title">
				<h3><?php echo $title; ?></h3>
			</div>
			<div class="posts-loop-content resume-table">
				<table class="table">
					<thead>
						<tr>
							<th><?php _e('Candidate','noo')?></th>
							<th><?php _e('Resume Title', 'noo'); ?></th>
							<th class="hidden-xs"><?php _e('Location','noo')?></th>
							<th class="hidden-xs"><?php _e('Category','noo')?></th>
						</tr>
					</thead>
					<tbody class="<?php echo $paginate; ?>-wrap">
	<?php endif; ?>
	<?php if($wp_query->have_posts()):?>
		<?php if(Noo_Resume::can_view_resume(null,true) || $is_shortcode):?>
			<?php while ($wp_query->have_posts()): $wp_query->the_post();global $post;?>
				<tr>
					<td>
						<?php
							$candidate_avatar	= '';
							$candidate_name	= '';
							if( !empty( $post->post_author ) ) :
								$candidate_avatar 	= noo_get_avatar( $post->post_author, 40 );
								$candidate = get_user_by( 'id', $post->post_author );
								$candidate_name = $candidate->display_name;
								$candidate_link = esc_url( apply_filters( 'noo_resume_candidate_link', get_the_permalink(), $post->ID, $post->post_author ) );
							?>
							<div class="loop-item-wrap">
							    <div class="item-featured">
									<a href="<?php echo $candidate_link; ?>">
										<?php echo $candidate_avatar;?>
									</a>
								</div>
								
								<div class="loop-item-content">
									<h2 class="loop-item-title">
										<a href="<?php echo $candidate_link;; ?>" ><?php echo esc_html( $candidate_name ); ?></a>
									</h2>
								</div>
							</div>
						<?php endif; ?>
					</td>
					<td><a href="<?php the_permalink()?>"><strong><?php the_title()?></strong></a></td>
					<td class="hidden-xs">
					<?php
						$job_location = noo_get_post_meta($post->ID,'_job_location','');
						$job_locations = array();
						if( !empty( $job_location ) ) :
							$json_location = noo_json_decode($job_location);
							$job_locations = empty( $json_location ) ? array() : get_terms( 'job_location', array('include' => $json_location, 'hide_empty' => 0, 'fields' => 'names') );
						?>
						<i class="fa fa-map-marker"></i>&nbsp;<em><?php echo implode(', ', $job_locations ); ?></em>
						<?php endif; ?>
					</td>
					<td class="hidden-xs"><strong><?php
						$job_category = noo_get_post_meta($post->ID,'_job_category','');
						if( !empty( $job_category ) ) {
							$json_category = noo_json_decode($job_category);
							$job_categories = empty( $json_category ) ? array() : get_terms( 'job_category', array('include' => $json_category, 'hide_empty' => 0, 'fields' => 'names') );
							echo implode(', ', $job_categories );
						}
					?></strong></td>
				</tr>
			<?php endwhile;?>
		<?php else:?>
			<?php
				$wp_query->max_num_pages = 0;
				list($title, $link) = Noo_Resume::get_resume_permission_message();
			?>
			<tr>
				<td colspan="6">
					<h3><?php echo $title; ?></h3>
					<?php if( !empty( $link ) ) echo $link; ?>
				</td>
			</tr>
		<?php endif;?>
	<?php else:?>
		<tr>
			<td colspan="6"><h3><?php _e('No Resume available','noo')?></h3></td>
		</tr>
	<?php endif;?>
	<?php if(!$ajax_item || $ajax_item == null ) ://ajax item ?>
				</tbody>
			</table>
		</div>
		<?php if($pagination) :
			if( $paginate == 'resume_nextajax') :
				if ( 1 < $wp_query->max_num_pages ) :
					?>
					<div class="pagination list-center" 
						data-job-category="<?php echo esc_attr($job_category);?>"
						data-job-location="<?php echo esc_attr($job_location);?>"
						data-orderby="<?php echo esc_attr($orderby);?>"
						data-order="<?php echo esc_attr($order);?>"
						data-posts-per-page="<?php echo absint($posts_per_page)?>"
						data-current-page="1"
						data-max-page="<?php echo absint($wp_query->max_num_pages)?>">
						<a href="#" class="prev page-numbers disabled">
							<i class="fa fa-long-arrow-left"></i>
						</a>
						
						<a href="#" class="next page-numbers">
							<i class="fa fa-long-arrow-right"></i>
						</a>
					</div>
					<?php
				endif;
			else :
				
				( $live_search ? noo_pagination( '', $wp_query, $live_search) : noo_pagination( '', $wp_query) );
				
			endif;
		endif;
	?>
	</div>
	<?php
	endif;
endif;
?>