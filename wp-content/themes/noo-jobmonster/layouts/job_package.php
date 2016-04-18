<div class="job-package clearfix">
	<?php
	global $noo_view_job_package;
	$noo_view_job_package = true;
	$product_args = array(
		'post_type'      => 'product',
		'posts_per_page' => 6,
		'suppress_filters' => false,
		'tax_query'      => array(
			array(
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => array( 'job_package' )
			)
		),
		'orderby'   => 'menu_order title',
		'order'     => 'ASC',
	);
	if( isset( $product_cat ) && !empty( $product_cat ) ) {
		$product_args['tax_query'][] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => explode(',', $product_cat)
			);
	}
	$packages = get_posts( $product_args );
	$noo_view_job_package = false;
	$can_view_resume_setting = Noo_Resume::get_setting('can_view_resume','employer');
	$user_id = get_current_user_id();
	$purchased_free_package = Noo_Job_Package::is_purchased_free_package( $user_id );
	?>
	<?php if($packages): ?>
		<?php do_action( 'noo_job_package_before' ); ?>
		<div class="noo-pricing-table classic pricing-<?php  echo esc_attr(count($packages))?>-col package-pricing">
			<?php foreach ($packages as $package):?>
				<?php $product = get_product($package->ID);
					$checkout_url = isset($add_to_cart) ? Noo_Member::get_checkout_url( $product->id ) : add_query_arg('package_id',$product->id);
					$redirect_package_free = isset($add_to_cart) ? Noo_Member::get_endpoint_url('manage-plan') : add_query_arg('package_id',$product->id);
				?>
				<div class="noo-pricing-column <?php echo ( $product->is_featured() ? 'featured' : '' ); ?>">
				    <div class="pricing-content">
				        <div class="pricing-header">
				            <h2 class="pricing-title"><?php echo esc_html($product->get_title())?></h2>
				            <h3 class="pricing-value"><span class="noo-price"><?php echo wp_kses_post($product->get_price_html())?></span></h3>
				        </div>
				        <div class="pricing-info">
				            <ul class="noo-ul-icon fa-ul">
				            	<?php 
					                $is_unlimited = $product->is_unlimited_job_posting();
			            			$job_limit = $product->get_post_job_limit();
			            			$featured_limit = $product->get_job_feature_limit();
					                $can_view_resume = ( ( $can_view_resume_setting == 'premium_package' ) && $product->get_price() > 0 ) ||
				                					( ( $can_view_resume_setting == 'package' ) && $product->get_can_view_resume() === '1' );
				                ?>
				                <li class="noo-li-icon"><i class="fa fa-check-circle"></i> <?php echo ( $is_unlimited ? __('Unlimited job posting', 'noo') : ( $job_limit > 0 ? sprintf( __('%s job posting','noo'), $job_limit ) : __('No job posting', 'noo') ) );?></li>
				                <li class="noo-li-icon"><i class="fa fa-check-circle"></i> <?php echo ( $featured_limit > 0 ? sprintf( __('%s featured job','noo'), $featured_limit ) : __('No featured job', 'noo') );?></li>
				                <li class="noo-li-icon"><i class="fa fa-check-circle"></i> <?php echo sprintf(__( 'Job displayed for %s days','noo'), $product->job_display_duration );?></li>
				                <?php if( $can_view_resume ) : ?>
				                	<li class="noo-li-icon"><i class="fa fa-check-circle"></i> <?php _e('Allowing access to Resumes','noo');?></li>
				            	<?php endif; ?>
				            </ul>
				            <?php if( !empty( $package->post_excerpt ) ) : ?> 
				            	<div class="short-desc">
				            	<?php echo apply_filters( 'noo_package_short_description', $package->post_excerpt ); ?>
				            	</div>
				            <?php endif; ?>
				            <?php if( !empty( $package->post_content ) ) : ?> 
				            	<a href="javascript:void(0)" class="readmore package-modal" data-toggle="modal" data-target="#package-content-<?php echo $package->ID; ?>"><i class="fa fa-arrow-circle-right"></i><?php echo __('More info', 'noo'); ?></a>
				            <?php endif; ?>
				        </div>
				        <div class="pricing-footer">
				        	<a class="btn btn-lg btn-primary <?php echo ( ($purchased_free_package && $product->get_price() <= 0 ) ? 'disabled' : ''); ?> <?php echo ($product->get_price() == 0 && is_user_logged_in() ) ? ' auto_create_order_free' : ''; ?>" data-id="<?php echo get_current_user_id(); ?>"<?php echo ($product->get_price() == 0 && is_user_logged_in() ) ? ' data-security="' . wp_create_nonce( 'noo-free-package' ) . '" data-url-package="' . $redirect_package_free . '"' : ' href="' . esc_url($checkout_url) .'"'; ?> data-package="<?php echo $product->id ?>"><?php echo wp_kses_post($product->add_to_cart_text())?></a>
				        </div>
				        <?php if( !empty( $package->post_content ) ) : ?> 
					        <div id="package-content-<?php echo $package->ID; ?>" class="package-content modal fade" tabindex="-1" role="dialog" aria-labelledby="package-content-<?php echo $package->ID; ?>Label" aria-hidden="true">
					        	<div class="modal-dialog package-modal">
					        		<div class="modal-content">
					        			<div class="modal-header">
					        				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					        				<h2 class="modal-title"><?php echo esc_html($product->get_title())?></h2>
					        			</div>
					        			<div class="modal-body">
					        				<div class="row">
					        					<div class="col-md-5 pricing-header">
									            	<h3 class="pricing-value"><span class="noo-price"><?php echo wp_kses_post($product->get_price_html())?></span></h3>
									            </div>
									            <div class="col-md-7 pull-right pricing-info">
									            	<ul class="noo-ul-icon fa-ul">
										                <li class="noo-li-icon"><i class="fa fa-check-circle"></i> <?php echo ( $is_unlimited ? __('Unlimited job posting', 'noo') : ( $job_limit > 0 ? sprintf( __('%s job posting','noo'), $job_limit ) : __('No job posting', 'noo') ) );?></li>
										                <li class="noo-li-icon"><i class="fa fa-check-circle"></i> <?php echo ( $featured_limit > 0 ? sprintf( __('%s featured job','noo'), $featured_limit ) : __('No featured job', 'noo') );?></li>
										                <li class="noo-li-icon"><i class="fa fa-check-circle"></i> <?php echo sprintf(__( 'Job displayed for %s days','noo'), $product->job_display_duration );?></li>
										                <?php 
										                if( $can_view_resume ) : ?>
										                	<li class="noo-li-icon"><i class="fa fa-check-circle"></i> <?php _e('Allowing access to Resumes','noo');?></li>
										            	<?php endif; ?>
										            </ul>
									            </div>
									            <div class="col-md-12 package-content">
					        						<?php echo apply_filters( 'noo_package_content', $package->post_content ); ?>
					        					</div>
									        </div>
					        			</div>
					        			<div class="modal-footer">
						        			<a class="btn btn-lg btn-primary <?php echo ( ($purchased_free_package && $product->get_price() <= 0 ) ? 'disabled' : ''); ?> <?php echo ($product->get_price() == 0 && is_user_logged_in() ) ? ' auto_create_order_free' : ''; ?>" data-id="<?php echo get_current_user_id(); ?>"<?php echo ($product->get_price() == 0 && is_user_logged_in() ) ? ' data-security="' . wp_create_nonce( 'noo-free-package' ) . '" data-url-package="' . $redirect_package_free . '"' : ' href="' . esc_url($checkout_url) .'"'; ?> data-package="<?php echo $product->id ?>"><?php echo wp_kses_post($product->add_to_cart_text())?></a>
					        			</div>
					        		</div>
					        	</div>
					        </div>
					    <?php endif; ?>
				    </div>
				</div>
			<?php endforeach;?>
			<script>
			</script>
		</div>
	<?php endif;?>
</div>
