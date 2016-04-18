<?php
/*
Template Name: Post Job
*/
if ( !isset( $_POST['action'] ) || empty( $_POST[ 'action' ] ) ) {
	if ( empty( $_GET[ 'action' ] ) ) {
		$GLOBALS[ 'action' ] = '';
	} else {
		$GLOBALS[ 'action' ] = $_GET[ 'action' ];
	}
} else {
	$GLOBALS[ 'action' ] = $_POST[ 'action' ];
}

$step_1 = 1;
$step_2 = 2;
$step_3 = 3;
$step_4 = 4;
if(!Noo_Job::use_woocommerce_package()){
	$step_3 = 2;
	$step_4 = 3;
}
$step_completed = '<i class="fa fa-check"></i>';
$current_step = 1;
$step_content='';
switch ($action){
	case 'register':
	case 'login':
		Noo_Job::login_handler();
		$layout='login_register';
	break;
	case 'job_package':
		if(Noo_Job::need_login()){
			wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'login',get_permalink())));
			exit;
		} else {
			if( !Noo_Member::is_employer() ) {
				wp_safe_redirect( home_url('/') );
				exit;
			}
		}
		Noo_Job::jobpackage_handler();
		$current_step = 2;
		$step_1 = $step_completed;
		$layout='job_package';
		ob_start();
		?>
		<div id="step_content_package" class="jstep-content">
			<div class="jpanel jpanel-package">
				<div class="jpanel-title">
					<h3><?php _e('Choose a Package That Fits Your Need','noo')?></h3>
				</div>
				<div class="jpanel-body">
				<?php noo_get_layout('job_package')?>
				</div>
			</div>
		</div>
		<?php
		$step_content = ob_get_clean();
	break;
	case 'post_job':
		if(Noo_Job::need_login()){
			wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'login',get_permalink())));
			exit;
		} else {
			if( !Noo_Member::is_employer() ) {
				wp_safe_redirect( home_url('/') );
				exit;
			}
		}

		if(!Noo_Job::can_add_job()){
			noo_message_add(__('You can not add job','noo'),'error');
			if(Noo_Job::use_woocommerce_package() && Noo_Member::is_employer()){
                wp_safe_redirect(Noo_Member::get_endpoint_url('manage-plan'));
			}else{
				wp_safe_redirect(Noo_Member::get_member_page_url());
			}
			exit();
		}
		Noo_Form_Handler::post_job_action();
		if(!Noo_Job::get_employer_package() && Noo_Job::use_woocommerce_package() && !isset($_GET['package_id'])){
			wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'job_package',get_permalink())));
			die;
		}
		$step_1 = $step_2 = $step_completed;
		$layout = 'job_form';
		ob_start();
		?>
		<div id="step_content_form" class="jstep-content">
			<div class="jpanel jpanel-job-form">
				<div class="jpanel-title">
					<h3><?php _e('Describe your company and vacancy','noo')?></h3>
				</div>
				<div class="jpanel-body">
					<div class="form-title">
						<h3><?php _e('Job Details','noo')?></h3>
					</div>
					<?php noo_get_layout('job_form')?>
				</div>
			</div>
			<div class="form-actions form-group text-center clearfix">
				<?php if(!Noo_Job::get_employer_package() && Noo_Job::use_woocommerce_package()):?>
				<a type="button" class="btn btn-primary" href="<?php echo esc_url(add_query_arg('action','jobpackage'))?>"><?php _e('Back','noo')?></a>
		 		<?php endif;?>
		 		<button type="submit" class="btn btn-primary"><?php _e('Continue','noo')?></button>
		 	</div>
		</div>
		<?php
		$step_content = ob_get_clean();
	break;
	case 'preview_job':
		Noo_Form_Handler::preview_job_action();
		if(Noo_Job::need_login()){
			wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'login',get_permalink())));
			exit;
		} else {
			if( !Noo_Member::is_employer() ) {
				wp_safe_redirect( home_url('/') );
				exit;
			}
		}
		if(!Noo_Job::can_add_job() && Noo_Job::use_woocommerce_package()){
			wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'job_package',get_permalink())));
			die;
		}
		$step_1 = $step_2 = $step_3 = $step_completed;
		$layout = 'job_preview';
		ob_start();
		?>
		<div id="step_content_preview" class="jstep-content">
			<?php noo_get_layout('job_preview')?>
		</div>
		<?php
		$step_content = ob_get_clean();
	break;
	default:
		if(Noo_Job::need_login()) {
			if($action != 'register')
				wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'login')));
		} elseif (!Noo_Member::is_employer()) {
			wp_safe_redirect( home_url('/') );
		} elseif( Noo_Job::use_woocommerce_package() ) {
			if( $package = Noo_Job::get_employer_package() ) {
				if( Noo_Job::get_job_remain() <= 0 ) {
					noo_message_add(__('You used up your Job Limit. Please select a new package.','noo'),'error');
					wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'job_package' )));
				} else {
					wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'post_job' )));
				}
			} else {
				wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'job_package' )));
			}
		} else {
			wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'post_job' )));
		}

	break;
}
?>
<?php get_header(); ?>
<div class="container-wrap">	
	<div class="main-content container-fullwidth">
		<div class="row">
			<div class="<?php noo_main_class(); ?>" role="main">
				<div class="jform">
					<div class="jform-header">
						<div class="container-boxed max">
							<div class="jform-steps">
								<ul class="jsteps jsteps-<?php echo esc_attr($step_4) ?>">
									<li data-layout="login_register"  data-step="1" class="<?php if($action == 'login' || $action == 'register'){?> active<?php }?><?php if($step_1 == $step_completed){?> completed<?php }?>">
										<span class="jstep-num">
											<a href="javascript:void(0);"><?php echo wp_kses_post($step_1)?></a>
										</span>
										<div class="jstep-line">
											<span class="jstep-dot"></span>
										</div>
										<div class="jstep-label"><?php echo Noo_Member::can_register() ? __('Login or create an account','noo') : __('Login', 'noo'); ?></div>
									</li>
									<?php if(Noo_Job::use_woocommerce_package()):?>
									<li data-layout="package" data-step="2" class="<?php if($action == 'job_package'){?> active<?php }?><?php if($step_2 == $step_completed){?> completed<?php }?>">
										<span class="jstep-num">
											<?php 
											
											if($action != 'login' && $action != 'job_package' && !Noo_Job::get_employer_package()){
												$jobpackage_location = esc_url(add_query_arg('action','job_package'));
											}else{
												$jobpackage_location = 'javascript:void(0);';
											}
											?>
											<a href="<?php echo ($jobpackage_location)?>"><?php echo wp_kses_post($step_2)?></a>
										</span>
										<div class="jstep-line">
											<span class="jstep-dot"></span>
										</div>
										<div class="jstep-label"><?php _e('Choose a package','noo')?></div>
									</li>
									<?php endif;?>
									<li  data-layout="form"  data-step="3" class="<?php if($action == 'post_job'){?> active<?php }?><?php if($step_3 == $step_completed){?> completed<?php }?>">
										<span class="jstep-num">
											<?php 
											if($action == 'preview_job'){
												$postjob_location_query = array('action'=>'post_job');
												$job_id = isset($_GET['job_id']) ? absint($_GET['job_id']) : 0;
												if($job_id)
													$postjob_location_query['job_id'] = $job_id;
												$postjob_location = esc_url(add_query_arg($postjob_location_query));
											}else{
												$postjob_location='javascript:void(0);';
											}
											?>
											<a href="<?php echo ($postjob_location)?>"><?php echo wp_kses_post($step_3)?></a>
										</span>
										<div class="jstep-line">
											<span class="jstep-dot"></span>
										</div>
										<div class="jstep-label"><?php _e('Describe your company and vacancy','noo')?></div>
									</li>
									<li data-layout="preview"  data-step="4" class="<?php if($action == 'preview_job'){?> active<?php }?><?php if($step_4 == $step_completed){?> completed<?php }?>">
										<span class="jstep-num">
											<a href="javascript:void(0);"><?php echo wp_kses_post($step_4)?></a>
										</span>
										<div class="jstep-line">
											<span class="jstep-dot"></span>
										</div>
										<div class="jstep-label"><?php _e('Preview and submit your job','noo')?></div>
									</li>
								</ul>
							</div>
						</div>
					</div>
					<div class="jform-body">
						<div class="container-boxed max">
							<?php if($action=='login' || $action == 'register'):?>
								<div id="step_content_login" class="jstep-content" style="display: block;">
									<?php noo_get_layout('login_register')?>
								</div>
							<?php else:?>
								<form id="post_job_form" class="form-horizontal" autocomplete="on" method="post" novalidate="novalidate">
									<div style="display: none;">
										<input type="hidden" name="action" id="hiddenaction" value="<?php echo esc_attr($action)?>">
										<input type="hidden" name="page_id" value="<?php echo get_the_ID()?>">
										<input type="hidden" name="job_id" value="<?php echo isset($_GET['job_id']) ? absint($_GET['job_id']) : 0?>">
										<?php if(Noo_Job::use_woocommerce_package() && isset($_GET['package_id'])):?>
										<input type="hidden" name="package_id" value="<?php echo isset($_GET['package_id']) ? absint($_GET['package_id']) : 0?>">
										<?php endif;?>
										<?php wp_nonce_field('noo-post-job')?>
									</div>
									<?php echo ($step_content);?>
								</form>
							<?php endif;?>
						</div>
					</div>
				</div>
			</div> <!-- /.main -->
		</div><!--/.row-->
	</div><!--/.container-full-->
</div><!--/.container-wrap-->
	
<?php get_footer(); ?>