<?php

$company_name		= get_post_field( 'post_title', $company_id );
$company_website 	= noo_get_post_meta($company_id, '_website', '');
$logo_company 		= Noo_Company::get_company_logo( $company_id );
?>
<div class="company-desc" itemscope itemtype="http://schema.org/Organization">
	<div class="company-header">
		<div class="company-featured"><a href="<?php echo get_permalink( $company_id ); ?>"><?php echo $logo_company;?></a></div>
		<h3 class="company-title" itemprop="name"><?php if( !is_singular( 'noo_company' ) ) : ?><a href="<?php echo get_permalink( $company_id ); ?>"><?php endif; ?><?php echo esc_html( $company_name );?><?php if( !is_singular( 'noo_company' ) ) : ?></a><?php endif; ?></h3>
	</div>
	<div class="company-info">
		<?php
		 	echo get_post_field( 'post_content', $company_id );
		 ?>

		<?php 
			// Job's social info

			$facebook		= noo_get_post_meta( $company_id, "_facebook", '' );
			$twitter		= noo_get_post_meta( $company_id, "_twitter", '' );
			$google_plus	= noo_get_post_meta( $company_id, "_googleplus", '' );
			$linkedin		= noo_get_post_meta( $company_id, "_linkedin", '' );
			$instagram		= noo_get_post_meta( $company_id, "_instagram", '' );
			if($facebook || $twitter || $google_plus || $linkedin || $instagram):
			?>
				<div class="job-social clearfix">
					<span class="noo-social-title"><?php _e('Connect with us','noo');?></span>
					<a href="<?php echo esc_url($company_website);?>" class="company_website" target="_blank"><span><?php echo esc_url($company_website);?></span></a>
					<?php echo ( !empty($facebook) ? '<a class="noo-icon fa fa-facebook" href="' . $facebook . '" target="_blank"></a>' : '' ); ?>
					<?php echo ( !empty($twitter) ? '<a class="noo-icon fa fa-twitter" href="' . $twitter . '" target="_blank"></a>' : '' ); ?>
					<?php echo ( !empty($google_plus) ? '<a class="noo-icon fa fa-google-plus" href="' . $google_plus . '" target="_blank"></a>' : '' ); ?>
					<?php echo ( !empty($linkedin) ? '<a class="noo-icon fa fa-linkedin" href="' . $linkedin . '" target="_blank"></a>' : '' ); ?>
					<?php echo ( !empty($instagram) ? '<a class="noo-icon fa fa-instagram" href="' . $instagram . '" target="_blank"></a>' : '' ); ?>
				</div>
			<?php endif; ?>
			<?php
			if( $show_more_job ) :
				$exclude_this_job =  array();
				if( is_singular( 'noo_job' ) ) {
					$post_object = get_queried_object();
					$exclude_this_job  = get_queried_object_id();
				}

				$more_jobs = array();//Noo_Company::get_more_jobs($company_id, (array) $exclude_this_job );
				if( !empty( $more_jobs ) ) :
			?>
					<div class="more-jobs clearfix">
						<strong><?php echo sprintf(__('More job from %s', 'noo'), $company_name); ?></strong>
						<ul>
							<?php foreach ($more_jobs as $job) : ?>
								<li><a class="more-job-title" href="<?php echo get_permalink( $job->ID );?>"><?php echo esc_html( $job->post_title );?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			<?php endif; ?>
	</div>
</div>