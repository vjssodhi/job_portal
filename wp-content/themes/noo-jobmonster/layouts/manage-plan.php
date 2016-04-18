<?php do_action('noo_member_manage_plan_before'); ?>
<div class="member-plan">
	<?php if(!($package_data = Noo_Job::get_employer_package())) : ?>
		<p class="no-plan-package text-center"><?php _e('No Package','noo') ?></p>
		<div class="member-plan-choose">
			<a class="btn btn-lg btn-primary" href="<?php echo esc_url(get_permalink( Noo_Job_Package::get_setting( 'package_page_id' )))?>"><?php _e('Choose a Package','noo')?></a>
		</div>
	<?php else :
		$package = Noo_Job::get_employer_package();
	?>
		<div class="row">
			<?php if(Noo_Job::use_woocommerce_package()):?>
				<div class="col-xs-6"><strong><?php _e('Plan','noo')?></strong></div>
				<div class="col-xs-6"><?php echo esc_html(get_the_title(absint($package['product_id']))) ?></div>
			<?php endif;?>
			<div class="col-xs-6"><strong><?php _e('Job Limit','noo')?></strong></div>
			<div class="col-xs-6"><?php echo sprintf(__('%s job(s)','noo'), $package['job_limit'] == 99999999 ? 'Unlimited' : $package['job_limit'] ) ?></div>
			<div class="col-xs-6"><strong><?php _e('Job Added','noo')?></strong></div>
			<div class="col-xs-6"><?php echo sprintf(__('%s job(s)','noo'), Noo_Job::get_job_added()); ?></div>
			<div class="col-xs-6"><strong><?php _e('Job Duration','noo')?></strong></div>
			<div class="col-xs-6"><?php echo esc_html(sprintf(__('%s day(s)','noo'),$package['job_duration'])) ?></div>
			<div class="col-xs-6"><strong><?php _e('Featured Job limit','noo')?></strong></div>
			<div class="col-xs-6"><?php echo esc_html($package['job_featured']) ?></div>
			<?php if(Noo_Job::use_woocommerce_package()):?>
				<div class="col-xs-6"><strong><?php _e('Date Activated','noo')?></strong></div>
				<div class="col-xs-6"><?php echo mysql2date('d/m/Y', @$package_data['created']) ?></div>
			<?php endif;?>
		</div>
		<?php if(Noo_Job::use_woocommerce_package()) : ?>
			<div class="member-plan-choose">
				<a class="btn btn-lg btn-primary" href="<?php echo esc_url(get_permalink( Noo_Job_Package::get_setting( 'package_page_id' )))?>"><?php _e('Upgrade Package','noo')?></a>
				<?php if(Noo_Job::use_woocommerce_package()) : ?>
					<p class="woo-order-history-link"><?php _e('Or', 'noo'); ?>&nbsp;<a href="<?php echo get_permalink(woocommerce_get_page_id( 'myaccount' ))?>"><?php _e('see your order history', 'noo'); ?></a></p>
				<?php endif; ?>
			</div>
		<?php endif;?>
	<?php endif; ?>
</div>