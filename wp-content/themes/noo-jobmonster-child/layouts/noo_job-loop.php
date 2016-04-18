<?php
		if($wp_query->have_posts()):
			if( empty($title) ) {
				if( is_post_type_archive( 'noo_job' )
					|| is_tax( 'job_category' )
					|| is_tax( 'job_location' )
					|| is_tax( 'job_type' ) ) {
					$title = __('Latest Jobs', 'noo');
				}
				if( is_search() || $title_type == 'job_count' ) {
					$title = sprintf(__('We found %s available job(s) for you','noo'),'<span class="text-primary">' . number_format_i18n($wp_query->found_posts) . '</span>' );
				}
			}
		?>
			<?php if(!$ajax_item || $ajax_item == null )://ajax item
				$attributes = 'class="jobs posts-loop ' . $class . '"' . ( !empty( $paginate ) ? ' data-paginate="'. esc_attr($paginate) .'"' : '' );
			?>
			<div <?php echo $attributes; ?>>
				<?php if( !empty($title) ): ?>
					<div class="posts-loop-title<?php if( is_singular( 'noo_job' ) ) echo ' single_jobs' ?>">
						<h3><?php echo $title;?></h3>
					</div>
				<?php endif;?>
				
				<div class="container">
                  <div class="table-responsive">
				<div class="posts-loop-content">
					<div class="<?php echo esc_attr($paginate)?>-wrap">
			<?php endif;//ajax item?>
					<?php  ?>
					<?php do_action( 'job_list_before', $loop_args, $wp_query ); ?>

					<?php while ( $wp_query->have_posts() ) : $wp_query->the_post(); global $post; ?>
						<?php
							$company_name		= '';
							$logo_company		= '';
							$type 				= Noo_Job::get_job_type( $post );

							$cover_image		= '';
			
							$company_id			= Noo_Job::get_employer_company($post->post_author);
							$locations			= get_the_terms( get_the_ID(), 'job_location' );
							if( !empty( $company_id ) ) {
								$company_name        = get_the_title( $company_id );
								$company_featured	 = noo_get_post_meta($company_id, '_company_featured') == 'yes';

								$cover_image_id      = noo_get_post_meta(get_the_ID(), '_cover_image');
								$cover_image		 = wp_get_attachment_image($cover_image_id, 'noo-thumbnail-square', false, array( 'alt' => get_the_title() ) );
								
								if( noo_get_option( 'noo_jobs_show_company_logo', true ) ) {
									$logo_company    = Noo_Company::get_company_logo( $company_id );
								}
							}
						?>
							<?php do_action( 'job_list_single_before', $loop_args, $wp_query ); ?>
							<article <?php post_class($item_class); ?> data-url="<?php the_permalink(); ?>">
								
								<div class="loop-item-wrap">
									<?php if( !empty( $logo_company ) ) : ?>
										<div class="item-featured">
											<a href="<?php the_permalink() ?>" title="<?php the_title(); ?>">
												<?php echo $logo_company;?>
											</a>
										</div>
									<?php endif; ?>
									<div class="loop-item-content"<?php echo $show_view_more == 'yes' ? ' style="width: 60% !important;float: left"' : ''; ?>>
										<h2 class="loop-item-title">
											<a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf( __( 'Permanent link to: "%s"','noo' ), the_title_attribute( 'echo=0' ) ) ); ?>"><?php the_title(); ?></a>
										</h2>
										<?php Noo_Job::noo_contentjob_meta($list_contentjob_meta); ?>
									</div>
									<?php if ( $show_view_more == 'yes' ) : ?>
										<div class="show-view-more" style="float: right;">
											<a class="btn btn-primary" href="<?php echo get_permalink($post->ID)?>">
												<?php _e('View more', 'noo')?>
											</a>
										</div>
									<?php endif; ?>
									
								</div>
							</article>
						<?php do_action( 'job_list_single_after', $loop_args, $wp_query ); ?>
					<?php endwhile; ?>
					<?php do_action( 'job_list_after', $loop_args, $wp_query ); ?>
					<?php if(!$ajax_item)://ajax item?>
							</div>
						</div></div></div>
						<?php if($paginate == 'loadmore' && 1 < $wp_query->max_num_pages):?>
							<div class="loadmore-action">			
								<a href="#" class="btn btn-default btn-block btn-loadmore" title="<?php _e('Load More','noo')?>"><?php _e('Load More','noo')?></a>
								<div class="noo-loader loadmore-loading"><span></span><span></span><span></span><span></span><span></span></div>
							</div>
						<?php endif;?>
						<?php 
							if($paginate == 'nextajax'){
								if ( 1 < $wp_query->max_num_pages ){
									?>
									<div class="pagination list-center" 
										<?php
										if( is_array( $paginate_data ) && !empty( $paginate_data ) ) :
											foreach ($paginate_data as $key => $value) :
												echo ' data-' . $key . '="' . $value . '"';
											endforeach;
										endif;
										?>
										data-show="<?php echo esc_attr($featured)?>"
										data-show_view_more="<?php echo esc_attr($show_view_more);?>"
										data-current_page="1"
										data-max_page="<?php echo absint($wp_query->max_num_pages)?>">
										<a href="#" class="prev page-numbers disabled">
											<i class="fa fa-long-arrow-left"></i>
										</a>
										
										<a href="#" class="next page-numbers">
											<i class="fa fa-long-arrow-right"></i>
										</a>
									</div>
									<?php
								}
							}else{
								if($pagination) {
									$pagination_args = isset( $pagination_args ) ? $pagination_args : array();
									noo_pagination($pagination_args,$wp_query);
								}
							}
						?>
					</div>
				<?php endif;//ajax item?>
		<?php else: ?>
			<?php
			if( $no_content == 'text' && !$in_related ) {
				noo_get_layout( 'no-content' );
			}
			?>
		<?php endif; ?>